<?php
session_start();
require_once 'config.php';

// 1. 權限與購物車檢查
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_message = "";

// 取得顧客目前的錢包餘額與金幣
$user_query = "SELECT wallet_balance, reward_coins, membership_tier FROM customers WHERE customer_id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $customer_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$current_balance = $user_data['wallet_balance'];
$current_coins = $user_data['reward_coins'];
$current_tier = $user_data['membership_tier'];
$saved_addresses = [];
$address_query = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt_addr = $conn->prepare($address_query);
$stmt_addr->bind_param("i", $customer_id);
$stmt_addr->execute();
$addr_result = $stmt_addr->get_result();
while ($addr = $addr_result->fetch_assoc()) {
    $saved_addresses[] = $addr;
}
$stmt_addr->close();

$saved_cards = [];
$query_cards = "SELECT * FROM saved_cards WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt_cards = $conn->prepare($query_cards);
$stmt_cards->bind_param("i", $customer_id);
$stmt_cards->execute();
$res_cards = $stmt_cards->get_result();
while ($card = $res_cards->fetch_assoc()) {
    $saved_cards[] = $card;
}
$stmt_cards->close();

// 🌟 核心升級：抓取購物車內容，新增抓取 affiliate_id 用於帶貨分傭！
$cart_query = "SELECT c.cart_id, c.quantity, c.affiliate_id, 
                      p.product_id, p.product_name, p.price AS product_price,
                      b.pc_build, b.build_name, b.total_price AS build_price,
                      pk.package_id, pk.package_name, pk.price AS package_price
               FROM shopping_cart c
               LEFT JOIN products p ON c.product_id = p.product_id
               LEFT JOIN saved_builds b ON c.pc_build = b.pc_build
               LEFT JOIN packages pk ON c.package_id = pk.package_id
               WHERE c.customer_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$total_amount = 0;

if ($cart_result->num_rows === 0) {
    header("Location: components.php");
    exit();
}

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    
    // 🌟 动态计算引擎：判断是哪一种商品並抓取/計算對應價格
    if ($row['product_id']) {
        $price = $row['product_price'];
    } elseif ($row['pc_build']) {
        $price = $row['build_price'];
    } elseif ($row['package_id']) {
        // 🚨 这里是核心升级！套餐价格不再读取数据库的固定值，而是实时计算总和！
        $pkg_id = $row['package_id'];
        $dynamic_pkg_price = 0;
        
        $pkg_sql = "SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ?";
        $pkg_stmt = $conn->prepare($pkg_sql);
        $pkg_stmt->bind_param("i", $pkg_id);
        $pkg_stmt->execute();
        $pkg_res = $pkg_stmt->get_result();
        
        while ($p_row = $pkg_res->fetch_assoc()) {
            $dynamic_pkg_price += $p_row['price'];
        }
        $pkg_stmt->close();
        
        $price = $dynamic_pkg_price; // 赋给计算变量
        
        // 关键点：我们需要把计算出来的价格，反向塞回 $cart_items 数组里
        // 这样后面写入 order_details 的时候（大概在 193 行），拿到的才是真正的实时总价，而不是 0
        $cart_items[count($cart_items) - 1]['package_price'] = $dynamic_pkg_price; 
        
    } else {
        $price = 0;
    }
    
    $total_amount += ($price * $row['quantity']);
}
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $shipping_address = mysqli_real_escape_string($conn, $_POST['shipping_address']);
    $final_payment_method = $_POST['payment_method'] ?? '';

    if (empty($final_payment_method)) {
        $_SESSION['error_msg'] = "Please select a payment method before checking out.";
        header("Location: checkout.php");
        exit();
    }

   $use_coins = isset($_POST['use_coins']) ? true : false;
    $coins_used = 0;
    $coin_discount = 0;
    $vip_discount = 0;


if ($current_tier === 'VIP') {
        $vip_discount = $total_amount * 0.05;
    }


    if ($use_coins && $current_coins > 0) {
        $coins_used = $current_coins;
        $coin_discount = floor($current_coins / 10); 
    }


    $discount_amount = $vip_discount + $coin_discount;
    
    $final_amount = $total_amount - $discount_amount;
    if ($final_amount < 0) $final_amount = 0;
    if ($final_amount < 0) $final_amount = 0; 

 // ==========================================
    // 🏦 金流驗證與扣款邏輯 (全能銀行系統)
    // ==========================================
    $bank_account_id_to_deduct = null; // 用來記錄要扣款的銀行帳戶 ID

    if ($final_payment_method === 'Credit Card') {
        $selected_card = $_POST['selected_card'] ?? '';

        if ($selected_card === 'new') {
            $card_name = trim($_POST['dummy_card_name']);
            $card_number = str_replace([' ', '-'], '', $_POST['dummy_card_number']);
            $card_cvc = trim($_POST['dummy_card_cvc']);

            // 🌟 改變：查詢我們真實的 bank 表格，而不是 dummy_bank
            $bank_query = "SELECT * FROM bank WHERE card_number = ? AND cvc = ?";
            $bank_stmt = $conn->prepare($bank_query);
            $bank_stmt->bind_param("ss", $card_number, $card_cvc);
            $bank_stmt->execute();
            $bank_result = $bank_stmt->get_result();

            if ($bank_result->num_rows > 0) {
                $bank_data = $bank_result->fetch_assoc();
                $bank_account_id_to_deduct = $bank_data['id']; // 記錄要扣款的帳戶 ID

                $last_four = substr($card_number, -4);
                $final_payment_method = "Visa ending in " . $last_four; 
            } else {
                $_SESSION['error_msg'] = "Bank Declined: Invalid Card Number or CVC. Please try again.";
                header("Location: checkout.php");
                exit();
            }
            $bank_stmt->close();

        } else {
            $card_id = intval($selected_card);
            // 查詢這張儲存的卡片對應到哪一個 bank_id
            $saved_query = "SELECT card_brand, last_four_digits, bank_id FROM saved_cards WHERE card_id = ? AND customer_id = ?";
            $saved_stmt = $conn->prepare($saved_query);
            $saved_stmt->bind_param("ii", $card_id, $customer_id);
            $saved_stmt->execute();
            $saved_result = $saved_stmt->get_result();
            
            if ($saved_row = $saved_result->fetch_assoc()) {
                $final_payment_method = $saved_row['card_brand'] . " ending in " . $saved_row['last_four_digits'];
                $bank_account_id_to_deduct = $saved_row['bank_id']; // 記錄要扣款的帳戶 ID
            }
            $saved_stmt->close();
        }
    }
    elseif ($final_payment_method === 'Online Banking (FPX)') {
        $selected_bank = $_POST['selected_bank'] ?? '';
        
        if (empty($selected_bank)) {
            $_SESSION['error_msg'] = "Please select a bank for Online Banking.";
            header("Location: checkout.php");
            exit();
        }
        
        // 🌟 改變：模擬 FPX 登入驗證
        // 實務上這裡會是彈出視窗讓顧客輸入帳密，目前我們先寫死一組測試帳號來示範扣款邏輯
        $fpx_user = 'ganshengwing'; // 測試帳號
        $fpx_pass = '123456';       // 測試密碼

        $fpx_query = "SELECT id, balance FROM bank WHERE fpx_username = ? AND fpx_password = ?";
        $fpx_stmt = $conn->prepare($fpx_query);
        $fpx_stmt->bind_param("ss", $fpx_user, $fpx_pass);
        $fpx_stmt->execute();
        $fpx_result = $fpx_stmt->get_result();

        if ($fpx_result->num_rows > 0) {
             $fpx_data = $fpx_result->fetch_assoc();
             $bank_account_id_to_deduct = $fpx_data['id']; // 記錄要扣款的帳戶 ID
             $final_payment_method = "FPX - " . $selected_bank;
        } else {
             $_SESSION['error_msg'] = "FPX Login Failed: Invalid username or password.";
             header("Location: checkout.php");
             exit();
        }
        $fpx_stmt->close();
    }

    // ==========================================
    // 🏦 執行銀行扣款 (統一處理)
    // ==========================================
    if ($bank_account_id_to_deduct !== null) {
        // 先檢查餘額夠不夠
        $bal_check_stmt = $conn->prepare("SELECT balance FROM bank WHERE id = ?");
        $bal_check_stmt->bind_param("i", $bank_account_id_to_deduct);
        $bal_check_stmt->execute();
        $bank_balance = $bal_check_stmt->get_result()->fetch_assoc()['balance'];
        $bal_check_stmt->close();

        if ($bank_balance < $final_amount) {
            $_SESSION['error_msg'] = "Bank Declined: Insufficient funds in your bank account.";
            header("Location: checkout.php");
            exit();
        }

        // 餘額足夠，執行扣款
        $deduct_stmt = $conn->prepare("UPDATE bank SET balance = balance - ? WHERE id = ?");
        $deduct_stmt->bind_param("di", $final_amount, $bank_account_id_to_deduct);
        $deduct_stmt->execute();
        $deduct_stmt->close();
    }

    if ($final_payment_method === 'E-Wallet' && $current_balance < $final_amount) {
        $_SESSION['error_msg'] = "Insufficient E-Wallet balance! Please top up or choose another payment method.";
        header("Location: checkout.php");
        exit();
    } 
    
    $conn->begin_transaction();
    try {
        $order_status = 'Pending';
        $insert_order = "INSERT INTO orders (customer_id, total_amount, discount_amount, coins_used, order_status, shipping_address) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_order = $conn->prepare($insert_order);
        $stmt_order->bind_param("iddiss", $customer_id, $final_amount, $discount_amount, $coins_used, $order_status, $shipping_address);
        $stmt_order->execute();
        $order_id = $stmt_order->insert_id;
        $stmt_order->close();

        // 🌟 核心升級：寫入 order_details 支援 package_id
        $insert_detail = "INSERT INTO order_details (order_id, product_id, pc_build, package_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($insert_detail);
       // 🌟 核心升級：寫入 order_details 支援 package_id AND affiliate_id (帶貨賞金系統)
        $insert_detail = "INSERT INTO order_details (order_id, product_id, pc_build, package_id, affiliate_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($insert_detail);
        
        // 準備發放帶貨賞金的 SQL (每帶貨一套，原作者獲得 500 金幣)
        $reward_stmt = $conn->prepare("UPDATE customers SET reward_coins = reward_coins + ? WHERE customer_id = ?");
        $bounty_per_build = 500; 

        foreach ($cart_items as $item) {
            $pid = $item['product_id'] ? $item['product_id'] : NULL;
            $build_id = $item['pc_build'] ? $item['pc_build'] : NULL;
            $pkg_id = $item['package_id'] ? $item['package_id'] : NULL;
            // 抓取這個商品的帶貨人是誰
            $aff_id = $item['affiliate_id'] ? $item['affiliate_id'] : NULL; 
            $qty = $item['quantity'];
            
            if ($pid) $unit_price = $item['product_price'];
            elseif ($build_id) $unit_price = $item['build_price'];
            elseif ($pkg_id) $unit_price = $item['package_price'];
            else $unit_price = 0;

            // 1. 寫入訂單明細，把 affiliate_id 存進去作為歷史記錄
            $stmt_detail->bind_param("iiiiiii", $order_id, $pid, $build_id, $pkg_id, $aff_id, $qty, $unit_price);
            $stmt_detail->execute();

            // 🌟 2. 如果這筆訂單有帶貨人，立刻觸發分傭機制！
            if ($aff_id) {
                // 如果買了 2 套一樣的，獎金就翻倍
                $total_bounty = $bounty_per_build * $qty; 
                $reward_stmt->bind_param("ii", $total_bounty, $aff_id);
                $reward_stmt->execute();
            }
        }
        $stmt_detail->close();
        $reward_stmt->close();

        $payment_status = ($final_payment_method == 'Cash on Delivery') ? 'Pending' : 'Paid';
        $insert_payment = "INSERT INTO payments (order_id, payment_method, payment_status) VALUES (?, ?, ?)";
        $stmt_payment = $conn->prepare($insert_payment);
        $stmt_payment->bind_param("iss", $order_id, $final_payment_method, $payment_status);
        $stmt_payment->execute();
        $stmt_payment->close();

        if ($final_payment_method === 'E-Wallet') {
            $deduct_wallet = "UPDATE customers SET wallet_balance = wallet_balance - ? WHERE customer_id = ?";
            $stmt_deduct = $conn->prepare($deduct_wallet);
            $stmt_deduct->bind_param("di", $final_amount, $customer_id);
            $stmt_deduct->execute();
            
            $type = 'Payment';
            $insert_trans = "INSERT INTO wallet_transactions (customer_id, type, amount) VALUES (?, ?, ?)";
            $stmt_trans = $conn->prepare($insert_trans);
            $neg_amount = -$final_amount; 
            $stmt_trans->bind_param("isd", $customer_id, $type, $neg_amount);
            $stmt_trans->execute();
        }

        if ($coins_used > 0) {
            $deduct_coins = "UPDATE customers SET reward_coins = reward_coins - ? WHERE customer_id = ?";
            $stmt_coins = $conn->prepare($deduct_coins);
            $stmt_coins->bind_param("ii", $coins_used, $customer_id);
            $stmt_coins->execute();
        }

        $clear_cart = "DELETE FROM shopping_cart WHERE customer_id = ?";
        $stmt_clear = $conn->prepare($clear_cart);
        $stmt_clear->bind_param("i", $customer_id);
        $stmt_clear->execute();

        $conn->commit();
        $_SESSION['success_msg'] = "Order placed successfully! Your Order ID is #$order_id";
        header("Location: my_orders.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Checkout failed. Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Secure Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-title" style="text-align: left; margin-bottom: 30px;">
            <h2><i class="fa-solid fa-lock"></i> Secure Checkout</h2>
            <p class="specs">Complete your order details below.</p>
        </div>

        <?php 
        if (isset($_SESSION['error_msg'])) {
            echo "<div class='text-danger' style='margin-bottom: 20px; border-left: 4px solid #ff4d4d; padding-left: 10px;'><i class='fa-solid fa-circle-exclamation'></i> " . $_SESSION['error_msg'] . "</div>";
            unset($_SESSION['error_msg']);
        }
        if (!empty($error_message)) {
            echo "<div class='text-danger' style='margin-bottom: 20px;'><i class='fa-solid fa-circle-exclamation'></i> $error_message</div>";
        }
        ?>

        <div class="checkout-grid">
            
            <div class="auth-container" style="margin: 0; max-width: 100%;">
                <h3 style="margin-bottom: 20px; color: var(--text-main);"><i class="fa-solid fa-truck-fast"></i> Shipping & Payment</h3>
                
                <form action="checkout.php" method="POST" class="form" id="checkoutForm">
                    
                <div class="form-group input-group">
                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span><i class="fa-solid fa-location-dot"></i> Delivery Address</span>
                        <a href="profile.php" style="color: var(--accent-blue); font-size: 0.85rem; text-decoration: none;">
                            <i class="fa-solid fa-plus"></i> Add New Address
                        </a>
                    </label>

                    <?php if(!empty($saved_addresses)): ?>
                        <div id="active_address_display" style="padding: 15px; background: rgba(0, 243, 255, 0.05); border: 1px solid rgba(0, 243, 255, 0.3); border-radius: 8px; display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <div id="current_address_text" style="color: var(--text-main); line-height: 1.5; white-space: pre-wrap;">
                                Loading address...
                            </div>
                            <button type="button" onclick="toggleAddressList()" style="background: none; border: 1px solid var(--accent-blue); color: var(--accent-blue); padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; transition: 0.3s;" onmouseover="this.style.background='rgba(0,243,255,0.1)'" onmouseout="this.style.background='none'">
                                Change
                            </button>
                        </div>

                        <div id="address_selection_list" style="display: none; margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px;">
                            <?php foreach ($saved_addresses as $addr): ?>
                                
                                <?php 
                                    $recipient = !empty($addr['recipient_name']) ? $addr['recipient_name'] : 'Sheng Wing Gan';
                                    $phone = !empty($addr['phone_number']) ? $addr['phone_number'] : '0162058560';
                                    
                                    if (!empty($addr['address_line1'])) {
                                        $full_text = $recipient . " | " . $phone . "\n" . $addr['address_line1'] . ", " . $addr['postcode'] . " " . $addr['city'] . ", " . $addr['state'];
                                    } else {
                                        if (strpos($addr['full_address'], '|') !== false) {
                                            $full_text = $addr['full_address'];
                                        } else {
                                            $full_text = $recipient . " | " . $phone . "\n" . $addr['full_address'];
                                        }
                                    }
                                ?>

                                <label style="display: flex; align-items: flex-start; cursor: pointer; margin-bottom: 12px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" class="address-option">
                                    <input type="radio" name="shipping_address" value="<?php echo htmlspecialchars($full_text); ?>" style="margin-right: 15px; margin-top: 5px;" onchange="updateActiveAddress(this)" <?php echo $addr['is_default'] ? 'checked' : ''; ?>>
                                    <div style="flex: 1;">
                                        <?php if($addr['is_default']): ?>
                                            <span style="background: var(--accent-blue); color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; margin-bottom: 5px; display: inline-block;">Default</span><br>
                                        <?php endif; ?>
                                        
                                        <?php if (empty($addr['address_line1']) && strpos($addr['full_address'], '|') !== false): ?>
                                        <?php else: ?>
                                            <span style="font-weight: bold; color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($recipient); ?> | <?php echo htmlspecialchars($phone); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4; display: block;">
                                            <?php 
                                                if (!empty($addr['address_line1'])) {
                                                    echo htmlspecialchars($addr['address_line1']) . "<br>";
                                                    if (!empty($addr['address_line2'])) echo htmlspecialchars($addr['address_line2']) . "<br>";
                                                    echo htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']) . ", " . htmlspecialchars($addr['state']) . "<br>";
                                                    echo htmlspecialchars($addr['country'] ?? 'Malaysia');
                                                } else {
                                                    echo nl2br(htmlspecialchars($addr['full_address'])); 
                                                }
                                            ?>
                                        </span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; border: 1px dashed #ff4d4d; border-radius: 8px;">
                            <p style="color: #ff4d4d;">Please add a shipping address in your profile first.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const defaultRadio = document.querySelector('input[name="shipping_address"]:checked');
                        if (defaultRadio) {
                            updateActiveAddress(defaultRadio);
                        }
                    });

                    function toggleAddressList() {
                        const list = document.getElementById('address_selection_list');
                        const btn = event.target;
                        if (list.style.display === 'none') {
                            list.style.display = 'block';
                            btn.innerText = 'Cancel';
                        } else {
                            list.style.display = 'none';
                            btn.innerText = 'Change';
                        }
                    }

                    function updateActiveAddress(radio) {
                        document.getElementById('current_address_text').innerText = radio.value;
                        document.getElementById('address_selection_list').style.display = 'none';
                        const changeBtn = document.querySelector('#active_address_display button');
                        if(changeBtn) changeBtn.innerText = 'Change';
                    }
                </script>

                <?php if ($current_coins >= 10): ?>
                <div class="form-group" style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" id="use_coins" name="use_coins" style="margin-right: 10px; width: 18px; height: 18px;">
                        <span>
                            Use <strong><?php echo $current_coins; ?> Coins</strong> to get 
                            <strong style="color: #ffd700;">RM <?php echo number_format(floor($current_coins/10), 2); ?> OFF</strong>
                        </span>
                    </label>
                </div>
                <?php endif; ?>

                <div class="form-group input-group">
                    <label class="form-label" style="display: flex; justify-content: space-between;">
                        <span>Payment Method</span>
                    </label>
                    
                    <select id="payment_method" name="payment_method" class="form-control" required onchange="togglePaymentSections()" style="background-color: var(--bg-surface); color: var(--text-main);">
                        <option value="">-- Select Payment Method --</option>
                        <option value="E-Wallet">GridCitY E-Wallet (Bal: RM <?php echo number_format($current_balance, 2); ?>)</option>
                        <option value="Credit Card">💳 Credit / Debit Card</option>
                        <option value="Online Banking (FPX)">🏦 Online Banking (FPX)</option>
                        <option value="Cash on Delivery">🚚 Cash on Delivery (COD)</option>
                    </select>
                </div>

                <div id="credit_card_section" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 243, 255, 0.2); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="color: var(--accent-blue); margin-top: 0; margin-bottom: 15px; font-size: 1rem;"><i class="fa-regular fa-credit-card"></i> Select or Enter Card Details</h4>
                    
                    <?php if(!empty($saved_cards)): ?>
                        <?php foreach ($saved_cards as $index => $card): ?>
                            <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 10px; color: var(--text-muted); padding: 12px; background: rgba(255,255,255,0.02); border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                                <input type="radio" name="selected_card" value="<?php echo htmlspecialchars($card['card_id']); ?>" style="margin-right: 15px;" onchange="toggleNewCardForm()" <?php echo $card['is_default'] ? 'checked' : ''; ?>>
                                <div style="flex: 1;">
                                    <strong style="color: var(--text-main);"><?php echo htmlspecialchars($card['card_brand']); ?> ending in <?php echo htmlspecialchars($card['last_four_digits']); ?></strong>
                                    <?php echo $card['is_default'] ? '<span style="margin-left: 8px; background: var(--accent-blue); color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">Default</span>' : ''; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-main); padding: 12px; background: rgba(0, 243, 255, 0.05); border-radius: 6px; border: 1px dashed rgba(0, 243, 255, 0.5);">
                        <input type="radio" name="selected_card" value="new" style="margin-right: 15px;" onchange="toggleNewCardForm()">
                        <strong>➕ Pay with a New Card</strong>
                    </label>

                    <div id="new_card_form" style="display: none; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                        <p style="font-size: 0.85rem; color: #ffcc00; margin-top: 0; margin-bottom: 15px;">
                            <i class="fa-solid fa-shield-halved"></i> <strong>FYP System Note:</strong> Details entered here will be verified against the Dummy Bank database before processing.
                        </p>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <input type="text" name="dummy_card_name" placeholder="Name on Card (e.g., Ali Bin Abu)" class="form-control">
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <input type="text" name="dummy_card_number" placeholder="Card Number (16 digits)" class="form-control" style="flex: 2;">
                            <input type="text" name="dummy_card_cvc" placeholder="CVC" class="form-control" style="flex: 1;" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <div id="fpx_section" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 243, 255, 0.2); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="color: var(--accent-blue); margin-top: 0; margin-bottom: 15px; font-size: 1rem;"><i class="fa-solid fa-building-columns"></i> Select Your Bank</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Maybank2U" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/maybank.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="CIMB Clicks" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/cimb.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Public Bank" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/public.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="RHB Now" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/rhb.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>
                    </div>
                </div>

                <script>
                    function togglePaymentSections() {
                        var method = document.getElementById('payment_method').value;
                        var ccSection = document.getElementById('credit_card_section');
                        var fpxSection = document.getElementById('fpx_section');
                        var newCardForm = document.getElementById('new_card_form');
                        
                        ccSection.style.display = 'none';
                        fpxSection.style.display = 'none';
                        newCardForm.style.display = 'none';

                        if (method === 'Credit Card') {
                            ccSection.style.display = 'block';
                            toggleNewCardForm(); 
                        } else if (method === 'Online Banking (FPX)') {
                            fpxSection.style.display = 'block';
                        }
                    }

                    function toggleNewCardForm() {
                        var radios = document.getElementsByName('selected_card');
                        var newCardForm = document.getElementById('new_card_form');
                        for (var i = 0; i < radios.length; i++) {
                            if (radios[i].checked && radios[i].value === 'new') {
                                newCardForm.style.display = 'block';
                                return;
                            }
                        }
                        newCardForm.style.display = 'none';
                    }
                </script>

                <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-check-double"></i> Confirm & Place Order
                </button>
            </form>
        </div>

        <div class="auth-container" style="margin: 0; max-width: 100%; background: linear-gradient(145deg, var(--bg-darker), #151a25);">
            <h3 style="margin-bottom: 20px; color: var(--text-main);"><i class="fa-solid fa-receipt"></i> Order Summary</h3>
            
            <div class="summary-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <div style="flex: 3;">
                            <span style="color: var(--text-main); display: block; font-weight: bold;">
                                <?php 
                                    if ($item['product_id']) {
                                        echo htmlspecialchars($item['product_name']);
                                    } elseif ($item['pc_build']) {
                                        echo htmlspecialchars("Custom Rig: " . $item['build_name']);
                                    } elseif ($item['package_id']) {
                                        echo htmlspecialchars("Package: " . $item['package_name']);
                                    }
                                ?>
                            </span>
                            <span class="specs">Qty: <?php echo $item['quantity']; ?></span>
                        </div>
                        <div style="flex: 1; text-align: right; color: var(--text-muted);">
                            RM <?php 
                                if ($item['product_id']) echo number_format($item['product_price'], 2);
                                elseif ($item['pc_build']) echo number_format($item['build_price'], 2);
                                elseif ($item['package_id']) echo number_format($item['package_price'], 2);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-item" style="margin-top: 15px;">
                <span class="specs">Subtotal</span>
                <span class="specs" id="subtotal-display" data-subtotal="<?php echo $total_amount; ?>">RM <?php echo number_format($total_amount, 2); ?></span>
            </div>

<!-- 🌟 VIP 折扣顯示列 -->
            <?php if ($current_tier === 'VIP'): ?>
            <div class="summary-item" style="color: #ffd700;">
                <span><i class="fa-solid fa-crown"></i> ELITE 5% Discount</span>
                <span>- RM <?php echo number_format($total_amount * 0.05, 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="summary-item" id="discount-row" style="display: none; color: #ffd700;">
                <span>Coins Discount</span>
                <span id="discount-display" data-discount="<?php echo floor($current_coins/10); ?>">- RM <?php echo number_format(floor($current_coins/10), 2); ?></span>
            </div>

            <div class="summary-item total-row">
                <span>Total</span>
                <span id="final-total-display">RM <?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const useCoinsCheckbox = document.getElementById('use_coins');
        const discountRow = document.getElementById('discount-row');
        const finalTotalDisplay = document.getElementById('final-total-display');
        
        if (useCoinsCheckbox) {
            const subtotal = parseFloat(document.getElementById('subtotal-display').getAttribute('data-subtotal'));
            const discount = parseFloat(document.getElementById('discount-display').getAttribute('data-discount'));

            useCoinsCheckbox.addEventListener('change', function() {
                let finalAmount = subtotal;
                
                if (this.checked) {
                    discountRow.style.display = 'flex';
                    finalAmount = subtotal - discount;
                    if (finalAmount < 0) finalAmount = 0;
                } else {
                    discountRow.style.display = 'none';
                }
                
                finalTotalDisplay.innerHTML = 'RM ' + finalAmount.toFixed(2);
            });
        }
    });
</script>
</body>
</html>