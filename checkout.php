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
// 🌟 新增：為了前端即時計算，先準備好分類總額
$subtotal_components = 0; 
$subtotal_packages = 0;

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
    
// 🌟 新增：將商品價格分類，以供優惠券判斷
    $item_total = $price * $row['quantity'];
    $total_amount += $item_total;
    
    if (!empty($row['product_id']) && empty($row['pc_build']) && empty($row['package_id'])) {
        $subtotal_components += $item_total;
    } else {
        $subtotal_packages += $item_total;
    }
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
    $promo_discount = 0; // 🌟 變成 Promo 折扣
    $applied_promo_code = trim($_POST['applied_promo_code'] ?? ''); // 抓取輸入的代碼

    // ==========================================
    // 🛒 分類折扣引擎 (Smart Discount Engine)
    // ==========================================
    if (!empty($applied_promo_code)) {
        $subtotal_components = 0; 
        $subtotal_packages = 0;
        
        // 1. 將購物車商品分類算總價
        foreach ($cart_items as $item) {
            $price = 0;
            if ($item['product_id']) $price = $item['product_price'];
            elseif ($item['pc_build']) $price = $item['build_price'];
            elseif ($item['package_id']) $price = $item['package_price'];
            
            $item_price = $price * $item['quantity'];
            
            if (!empty($item['product_id']) && empty($item['pc_build']) && empty($item['package_id'])) {
                $subtotal_components += $item_price; // 單獨零件
            } else {
                $subtotal_packages += $item_price;   // 套裝機或自組機
            }
        }

        $promo_stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code_name = ? AND status = 'Active'");
        $promo_stmt->bind_param("s", $applied_promo_code);
        $promo_stmt->execute();
        $promo_res = $promo_stmt->get_result();
        
        if ($promo_row = $promo_res->fetch_assoc()) {
            if ($promo_row['is_vip_only'] == 1 && $current_tier !== 'VIP') {
                $_SESSION['error_msg'] = "The promo code '{$applied_promo_code}' is exclusive to ELITE members only.";
                header("Location: checkout.php");
                exit();
            } else {
                $target = $promo_row['target_category'];
                
                $target_subtotal = 0;
                if ($target === 'Components') $target_subtotal = $subtotal_components;
                elseif ($target === 'Packages') $target_subtotal = $subtotal_packages;
                else $target_subtotal = $subtotal_components + $subtotal_packages;

                if ($target_subtotal < $promo_row['min_spend']) {
                    $_SESSION['error_msg'] = "Min. spend of RM " . number_format($promo_row['min_spend'], 2) . " on {$target} is required to use this code.";
                    header("Location: checkout.php");
                    exit();
                }

                if ($target_subtotal <= 0) {
                    $_SESSION['error_msg'] = "No eligible {$target} items in your cart for this promo code.";
                    header("Location: checkout.php");
                    exit();
                }

                if ($promo_row['discount_type'] === 'Fixed') {
                    $promo_discount = min($promo_row['discount_value'], $target_subtotal);
                } else {
                    $promo_discount = $target_subtotal * ($promo_row['discount_value'] / 100);
                    
                    if ($promo_row['max_cap'] > 0 && $promo_discount > $promo_row['max_cap']) {
                        $promo_discount = $promo_row['max_cap'];
                    }
                }
            }
        } else {
            $_SESSION['error_msg'] = "Invalid or expired promo code: '{$applied_promo_code}'.";
            header("Location: checkout.php");
            exit();
        }
        $promo_stmt->close();
    }

// 🌟 升級：先計算扣除優惠券後的剩餘金額
    $amount_after_promo = $total_amount - $promo_discount;
    if ($amount_after_promo < 0) $amount_after_promo = 0;

    if ($use_coins && $current_coins > 0) {
        $max_coin_discount = floor($current_coins / 10); // 玩家最多可以扣多少錢

        if ($max_coin_discount > $amount_after_promo) {
            // 🚨 如果金幣折抵超過了結帳金額，只扣剛好可以抵銷的數量！
            $coin_discount = $amount_after_promo;
            $coins_used = $coin_discount * 10;
        } else {
            // 金幣不夠抵銷全部，就全扣
            $coin_discount = $max_coin_discount;
            $coins_used = $coin_discount * 10; 
        }
    }

    $discount_amount = $promo_discount + $coin_discount; 
    $final_amount = $total_amount - $discount_amount;
    if ($final_amount < 0) $final_amount = 0;

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
  // 🌟 升級：接收顧客輸入的真實 FPX 帳號密碼
        $fpx_user = trim($_POST['fpx_username'] ?? ''); 
        $fpx_pass = trim($_POST['fpx_password'] ?? '');

        if (empty($fpx_user) || empty($fpx_pass)) {
            $_SESSION['error_msg'] = "FPX Error: Please enter your internet banking username and password.";
            header("Location: checkout.php");
            exit();
        }

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

        // 🌟 核心邏輯：如果訂單成功且有使用優惠券，就記錄到使用表
if (isset($applied_promo_code) && !empty($applied_promo_code)) {
    // 1. 先抓出這張券的 promo_id
    $get_promo = $conn->prepare("SELECT promo_id FROM promo_codes WHERE code_name = ?");
    $get_promo->bind_param("s", $applied_promo_code);
    $get_promo->execute();
    $promo_data = $get_promo->get_result()->fetch_assoc();
    
    if ($promo_data) {
        $p_id = $promo_data['promo_id'];
        // 2. 插入使用紀錄 (假設 $new_order_id 是你剛產生的訂單 ID)
        $log_used = $conn->prepare("INSERT INTO used_vouchers (customer_id, promo_id, order_id) VALUES (?, ?, ?)");
        $log_used->bind_param("iii", $customer_id, $p_id, $order_id);
        $log_used->execute();
        $log_used->close();
    }
    $get_promo->close();
}

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
                        <span id="coins-label-text">
                            Use up to <strong><?php echo $current_coins; ?> Coins</strong> for 
                            <strong style="color: #ffd700;">RM <?php echo number_format(floor($current_coins/10), 2); ?> OFF</strong>
                        </span>
                    </label>
                </div>
                <?php endif; ?>

<div class="form-group input-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-money-check-dollar"></i> Payment Method</span>
                    </label>
                    
                    <select id="payment_method" name="payment_method" class="form-control" required onchange="togglePaymentSections()" style="background-color: #000; color: #fff; border: 1px solid rgba(0, 243, 255, 0.4); font-size: 1.05rem; padding: 12px; border-radius: 8px;">
                        <option value="">-- Select Payment Method --</option>
                        <option value="E-Wallet">💳 GridCitY Digital E-Wallet</option>
                        <option value="Credit Card">💳 Credit / Debit Card</option>
                        <option value="Online Banking (FPX)">🏦 Online Banking (FPX)</option>
                        <option value="Cash on Delivery">🚚 Cash on Delivery (COD)</option>
                    </select>
                </div>

                <div id="ewallet_section" style="display: none; background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); border: 1px solid #00f2fe; padding: 25px; border-radius: 12px; margin-bottom: 25px; position: relative; overflow: hidden; box-shadow: 0 0 20px rgba(0, 242, 254, 0.15);">
                    <i class="fa-solid fa-wallet" style="position: absolute; right: -20px; bottom: -20px; font-size: 8rem; color: rgba(0, 243, 255, 0.05); transform: rotate(-15deg);"></i>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
                        <div>
                            <p style="color: #00f2fe; font-size: 0.9rem; margin: 0 0 5px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">Available Balance</p>
                            <h2 style="color: #fff; font-size: 2.2rem; margin: 0; text-shadow: 0 0 10px rgba(0,242,254,0.3);">RM <?php echo number_format($current_balance, 2); ?></h2>
                        </div>
                        <div style="text-align: right;">
                            <a href="wallet_topup.php" style="background: #00f2fe; color: #000; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: 900; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4); transition: 0.3s; display: inline-block;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fa-solid fa-bolt"></i> Top Up Now
                            </a>
                            <p style="color: #aaa; font-size: 0.75rem; margin-top: 8px; margin-bottom: 0;">Instant reload via FPX</p>
                        </div>
                    </div>
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
                            <input type="radio" name="selected_bank" value="Maybank2U" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;" onchange="toggleFPXForm()">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/maybank.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>

                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="CIMB Clicks" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;" onchange="toggleFPXForm()">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/cimb.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>

                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Public Bank" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;" onchange="toggleFPXForm()">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/public.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>

                        <label style="display: flex; align-items: center; cursor: pointer; padding: 12px 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="RHB Now" style="transform: scale(1.2); flex-shrink: 0; margin-right: 15px;" onchange="toggleFPXForm()">
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center; height: 45px;">
                                <img src="image/rhb.png" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </label>
                    </div>

<div id="fpx_login_form" style="display: none; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                        <p style="font-size: 0.85rem; color: #ffcc00; margin-top: 0; margin-bottom: 15px;">
                            <i class="fa-solid fa-shield-halved"></i> <strong>Secure FPX Login:</strong> Please login to authorize the payment from your bank.
                        </p>
                        <div style="display: flex; gap: 15px;">
                            <input type="text" name="fpx_username" placeholder="FPX Username" class="form-control" style="flex: 1;">
                            <input type="password" name="fpx_password" placeholder="Password" class="form-control" style="flex: 1;">
                        </div>
                    </div>
                </div>

               

                <script>
function togglePaymentSections() {
                        var method = document.getElementById('payment_method').value;
                        var ccSection = document.getElementById('credit_card_section');
                        var fpxSection = document.getElementById('fpx_section');
                        var ewalletSection = document.getElementById('ewallet_section'); 
                        var newCardForm = document.getElementById('new_card_form');
                        
                        ccSection.style.display = 'none';
                        fpxSection.style.display = 'none';
                        ewalletSection.style.display = 'none';
                        newCardForm.style.display = 'none';

                        if (method === 'Credit Card') {
                            ccSection.style.display = 'block';
                            toggleNewCardForm(); 
                        } else if (method === 'Online Banking (FPX)') {
                            fpxSection.style.display = 'block';
                        } else if (method === 'E-Wallet') {
                            ewalletSection.style.display = 'block'; 
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
<div style="background: rgba(255,255,255,0.05); padding: 18px; border-radius: 12px; margin-bottom: 20px; border: 1px solid <?php echo ($current_tier === 'VIP') ? 'rgba(255,215,0,0.3)' : 'rgba(255,255,255,0.1)'; ?>;">
    <label style="display: block; color: <?php echo ($current_tier === 'VIP') ? '#ffd700' : '#fff'; ?>; font-size: 0.9rem; font-weight: bold; margin-bottom: 12px; text-transform: uppercase;">
        <i class="fa-solid fa-ticket"></i> Promo Code
    </label>
    
    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
        <input type="text" name="applied_promo_code" id="promo_code_input" placeholder="Enter Code here" 
               style="flex: 1; background: #000; border: 1px solid #333; color: #fff; padding: 12px; border-radius: 6px; font-family: monospace;">
        <button type="button" onclick="openVoucherModal()" 
                style="background: #222; color: #ffd700; border: 1px solid #ffd700; padding: 0 15px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; white-space: nowrap;">
            Select Voucher
        </button>
    </div>

    <?php if ($current_tier !== 'VIP'): ?>
        <p style="font-size: 0.8rem; color: #888; margin: 0;">ELITE members get up to 20% OFF. <a href="membership.php" style="color: #ffd700; text-decoration: none;">Join Now</a></p>
    <?php else: ?>
        <p style="font-size: 0.8rem; color: #ffd700; margin: 0;"><i class="fa-solid fa-crown"></i> ELITE Member Exclusive: High-value vouchers available!</p>
    <?php endif; ?>
</div>

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


            
<div class="summary-item" id="discount-row" style="display: none; color: #ffd700;">
                <span>Coins Discount</span>
                <span id="discount-display" data-discount="<?php echo floor($current_coins/10); ?>">- RM <?php echo number_format(floor($current_coins/10), 2); ?></span>
            </div>

            <div class="summary-item" id="promo-row" style="display: none; color: #00f2fe;">
                <span>Voucher (<span id="promo-name-display"></span>)</span>
                <span id="promo-amount-display">- RM 0.00</span>
            </div>

            <div class="summary-item total-row">
                <span>Total</span>
                <span id="final-total-display">RM <?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

    </div>
</main>

<div id="voucherModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px);">
    <div style="background: #1a1a1a; margin: 10% auto; padding: 0; width: 90%; max-width: 500px; border-radius: 16px; border: 1px solid #333; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
        
        <div style="padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; background: #222;">
            <h3 style="margin: 0; color: #fff;"><i class="fa-solid fa-ticket" style="color: #ffd700;"></i> Select Voucher</h3>
            <span onclick="closeVoucherModal()" style="color: #888; cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>

        <div style="padding: 20px; max-height: 400px; overflow-y: auto;">
            
            <?php
            // 抓取適合該使用者的優惠券
            // 邏輯：所有人都能看 is_vip_only=0，VIP 還能看 is_vip_only=1
// 🌟 升級版：過濾掉該顧客已經用過的優惠券
            $sql_vouchers = "SELECT p.* FROM promo_codes p 
                             LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = $customer_id 
                             WHERE p.status = 'Active' AND uv.promo_id IS NULL AND (p.is_vip_only = 0";
            if ($current_tier === 'VIP') {
                $sql_vouchers .= " OR p.is_vip_only = 1";
            }
            $sql_vouchers .= ") ORDER BY p.is_vip_only DESC, p.discount_value DESC";
            
            $res_vouchers = $conn->query($sql_vouchers);

            if ($res_vouchers->num_rows > 0):
                while ($v = $res_vouchers->fetch_assoc()):
                    $is_vip_code = ($v['is_vip_only'] == 1);
            ?>
<div onclick="selectVoucher('<?php echo $v['code_name']; ?>')" 
                     style="display: flex; background: #222; border: 1px solid <?php echo $is_vip_code ? '#ffd700' : '#00f2fe'; ?>; border-radius: 10px; margin-bottom: 15px; cursor: pointer; transition: 0.2s; position: relative; overflow: hidden;"
                     onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 5px 15px <?php echo $is_vip_code ? "rgba(255,215,0,0.2)" : "rgba(0,242,254,0.2)"; ?>';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                    
                    <div style="background: <?php echo $is_vip_code ? 'linear-gradient(135deg, #ffd700, #f39c12)' : 'linear-gradient(135deg, #00f2fe, #4facfe)'; ?>; color: #000; width: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 10px; border-right: 2px dashed #222;">
                        <?php if($v['discount_type'] == 'Fixed'): ?>
                            <span style="font-weight: 900; font-size: 1.1rem;">RM<?php echo floatval($v['discount_value']); ?></span>
                        <?php else: ?>
                            <span style="font-weight: 900; font-size: 1.2rem;"><?php echo floatval($v['discount_value']); ?>%</span>
                        <?php endif; ?>
                        <span style="font-size: 0.6rem; text-transform: uppercase; font-weight: bold;">OFF</span>
                    </div>

                    <div style="padding: 15px; flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <strong style="color: #fff; font-size: 1rem;"><?php echo $v['code_name']; ?></strong>
                            <?php if($is_vip_code): ?>
                                <span style="background: #000; color: #ffd700; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; border: 1px solid #ffd700;">ELITE ONLY</span>
                            <?php else: ?>
                                <span style="background: rgba(0,242,254,0.1); color: #00f2fe; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(0,242,254,0.3);">PUBLIC</span>
                            <?php endif; ?>
                        </div>
                        <p style="color: #ccc; font-size: 0.8rem; margin: 5px 0 2px 0;">For <?php echo $v['target_category']; ?></p>
                        
                        <div style="color: #888; font-size: 0.75rem;">
                            <?php 
                                $terms = [];
                                if ($v['min_spend'] > 0) $terms[] = "Min. Spend RM " . floatval($v['min_spend']);
                                if ($v['max_cap'] > 0) $terms[] = "Capped at RM " . floatval($v['max_cap']);
                                echo empty($terms) ? "No minimum spend" : implode(' | ', $terms);
                            ?>
                        </div>
                    </div>

                    <div style="padding: 15px; display: flex; align-items: center; color: <?php echo $is_vip_code ? '#ffd700' : '#00f2fe'; ?>; font-size: 1.2rem;">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
                echo '<p style="text-align: center; color: #666;">No vouchers available right now.</p>';
            endif;
            ?>


        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // ==========================================
    // 1. 資料初始化 (從 PHP 取得)
    // ==========================================
    const cartSubtotals = {
        'Components': <?php echo (float)$subtotal_components; ?>,
        'Packages': <?php echo (float)$subtotal_packages; ?>,
        'All': <?php echo (float)$total_amount; ?>
    };

    const activeVouchers = {
        <?php
        $js_promo_stmt = $conn->query("SELECT * FROM promo_codes WHERE status = 'Active'");
        while($p = $js_promo_stmt->fetch_assoc()) {
            echo "'{$p['code_name']}': { type: '{$p['discount_type']}', val: {$p['discount_value']}, min: {$p['min_spend']}, max: {$p['max_cap']}, cat: '{$p['target_category']}', vip: {$p['is_vip_only']} },\n";
        }
        ?>
    };

    const currentTier = '<?php echo $current_tier; ?>';
    const baseSubtotal = <?php echo (float)$total_amount; ?>;
    const maxUserCoins = <?php echo (int)$current_coins; ?>;
    
    let currentPromoDiscount = 0;
    let currentCoinsDiscount = 0;

    // ==========================================
    // 2. 支付方式切換與 UI 顯示控制 (FPX & Card)
    // ==========================================
    function togglePaymentSections() {
        const method = document.getElementById('payment_method').value;
        const sections = {
            'Credit Card': document.getElementById('credit_card_section'),
            'Online Banking (FPX)': document.getElementById('fpx_section'),
            'E-Wallet': document.getElementById('ewallet_section')
        };
        const fpxForm = document.getElementById('fpx_login_form');

        // 先隱藏所有區塊
        Object.values(sections).forEach(s => { if(s) s.style.display = 'none'; });
        if(fpxForm) fpxForm.style.display = 'none';

        // 顯示選中的區塊
        if (sections[method]) {
            sections[method].style.display = 'block';
            if (method === 'Credit Card') toggleNewCardForm();
            if (method === 'Online Banking (FPX)') toggleFPXForm();
        }
    }

    function toggleNewCardForm() {
        const radios = document.getElementsByName('selected_card');
        const newCardForm = document.getElementById('new_card_form');
        let isNew = false;
        for (let r of radios) { if (r.checked && r.value === 'new') isNew = true; }
        if(newCardForm) newCardForm.style.display = isNew ? 'block' : 'none';
    }

    function toggleFPXForm() {
        const radios = document.getElementsByName('selected_bank');
        const fpxForm = document.getElementById('fpx_login_form');
        let isSelected = false;
        for (let r of radios) { if (r.checked) isSelected = true; }
        if(fpxForm) fpxForm.style.display = isSelected ? 'block' : 'none';
    }

    // ==========================================
    // 3. 智慧計算核心大管家 (優惠券 & 金幣)
    // ==========================================
    function updateFinalTotal() {
        // A. 先處理優惠券 (原本的 applyVoucherLogic 邏輯被整合在這裡了)
        const promoInput = document.getElementById('promo_code_input');
        const code = promoInput ? promoInput.value.trim() : '';
        const promoRow = document.getElementById('promo-row');
        
        currentPromoDiscount = 0;
        if (code && activeVouchers[code]) {
            const v = activeVouchers[code];
            const targetSubtotal = cartSubtotals[v.cat] || cartSubtotals['All'];

            // 驗證 VIP 與最低消費
            const isVipValid = (v.vip === 0 || (v.vip === 1 && currentTier === 'VIP'));
            const isSpendValid = (targetSubtotal >= v.min);

            if (isVipValid && isSpendValid && targetSubtotal > 0) {
                if (v.type === 'Fixed') {
                    currentPromoDiscount = Math.min(v.val, targetSubtotal);
                } else {
                    currentPromoDiscount = targetSubtotal * (v.val / 100);
                    if (v.max > 0 && currentPromoDiscount > v.max) currentPromoDiscount = v.max;
                }
                document.getElementById('promo-name-display').innerText = code;
                document.getElementById('promo-amount-display').innerText = '- RM ' + currentPromoDiscount.toFixed(2);
                promoRow.style.display = 'flex';
            } else {
                promoRow.style.display = 'none';
            }
        } else {
            promoRow.style.display = 'none';
        }

        const amountAfterPromo = Math.max(0, baseSubtotal - currentPromoDiscount);

        // B. 再處理金幣 (智慧上限邏輯)
        const useCoinsCheckbox = document.getElementById('use_coins');
        const discountRow = document.getElementById('discount-row');
        const coinsLabelText = document.getElementById('coins-label-text');
        
        currentCoinsDiscount = 0;
        if (useCoinsCheckbox) {
            let maxPossibleCoinValue = Math.floor(maxUserCoins / 10);
            
            if (useCoinsCheckbox.checked) {
                if (maxPossibleCoinValue > amountAfterPromo) {
                    currentCoinsDiscount = amountAfterPromo;
                } else {
                    currentCoinsDiscount = maxPossibleCoinValue;
                }
                const coinsToDeduct = currentCoinsDiscount * 10;
                document.getElementById('discount-display').innerText = '- RM ' + currentCoinsDiscount.toFixed(2);
                coinsLabelText.innerHTML = `Use <strong>${coinsToDeduct} Coins</strong> to get <strong style="color: #ffd700;">RM ${currentCoinsDiscount.toFixed(2)} OFF</strong>`;
                discountRow.style.display = 'flex';
            } else {
                coinsLabelText.innerHTML = `Use up to <strong>${maxUserCoins} Coins</strong> for <strong style="color: #ffd700;">RM ${maxPossibleCoinValue.toFixed(2)} OFF</strong>`;
                discountRow.style.display = 'none';
            }
        }

        // C. 更新最終總金額
        const finalAmount = Math.max(0, amountAfterPromo - currentCoinsDiscount);
        document.getElementById('final-total-display').innerText = 'RM ' + finalAmount.toFixed(2);
    }

    // ==========================================
    // 4. 彈窗與事件綁定
    // ==========================================
    function openVoucherModal() {
        document.getElementById('voucherModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeVoucherModal() {
        document.getElementById('voucherModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function selectVoucher(code) {
        document.getElementById('promo_code_input').value = code;
        closeVoucherModal();
        updateFinalTotal(); // 選擇代碼後，呼叫大管家重新計算！
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 初始化：一進頁面先執行一次，確保畫面正確
        togglePaymentSections();
        updateFinalTotal();

        // 綁定輸入與點擊事件 (全部交給 updateFinalTotal 處理)
        const promoInput = document.getElementById('promo_code_input');
        if(promoInput) promoInput.addEventListener('input', updateFinalTotal);

        const coinsCheckbox = document.getElementById('use_coins');
        if(coinsCheckbox) coinsCheckbox.addEventListener('change', updateFinalTotal);
        
        // 綁定下拉選單切換事件
        const paymentMethodSelect = document.getElementById('payment_method');
        if(paymentMethodSelect) paymentMethodSelect.addEventListener('change', togglePaymentSections);
    });

    window.onclick = function(event) {
        const modal = document.getElementById('voucherModal');
        if (event.target == modal) closeVoucherModal();
    }
</script>


</body>
</html>