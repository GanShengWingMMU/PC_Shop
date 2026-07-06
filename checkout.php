<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_message = "";


$user_query = "SELECT wallet_balance, reward_coins, lifetime_coins, membership_tier FROM customers WHERE customer_id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $customer_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$current_balance = $user_data['wallet_balance'];
$current_coins = $user_data['reward_coins'];
$lifetime_coins = $user_data['lifetime_coins'] ?? 0;
$current_tier = $user_data['membership_tier'];


$is_elite = ($current_tier === 'VIP' || $lifetime_coins >= 1000);

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


$default_address_html = "<span style='color: #ff4d4d;'><i class='fa-solid fa-triangle-exclamation'></i> Please select a shipping address below.</span>";
if (!empty($saved_addresses)) {
    $default_addr = $saved_addresses[0]; 
    foreach ($saved_addresses as $addr) {
        if ($addr['is_default']) { $default_addr = $addr; break; }
    }
    $recipient = !empty($default_addr['recipient_name']) ? $default_addr['recipient_name'] : 'Customer';
    $phone = !empty($default_addr['phone_number']) ? $default_addr['phone_number'] : '000-0000000';
    if (!empty($default_addr['address_line1'])) {
        $default_address_html = htmlspecialchars($recipient) . " | " . htmlspecialchars($phone) . "<br>" . htmlspecialchars($default_addr['address_line1']) . ", " . htmlspecialchars($default_addr['postcode']) . " " . htmlspecialchars($default_addr['city']) . ", " . htmlspecialchars($default_addr['state']);
    } else {
        $default_address_html = strpos($default_addr['full_address'], '|') !== false ? nl2br(htmlspecialchars($default_addr['full_address'])) : htmlspecialchars($recipient) . " | " . htmlspecialchars($phone) . "<br>" . nl2br(htmlspecialchars($default_addr['full_address']));
    }
}

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
$subtotal_components = 0; 
$subtotal_packages = 0;

if ($cart_result->num_rows === 0) {
    header("Location: components.php");
    exit();
}

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    
    if ($row['product_id']) {
        $price = $row['product_price'];
    } elseif ($row['pc_build']) {
        $price = $row['build_price'];
    } elseif ($row['package_id']) {
        $pkg_id = $row['package_id'];
        $dynamic_pkg_price = 0;
        
        $pkg_sql = "SELECT p.price, pi.quantity FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ?";
        $pkg_stmt = $conn->prepare($pkg_sql);
        $pkg_stmt->bind_param("i", $pkg_id);
        $pkg_stmt->execute();
        $pkg_res = $pkg_stmt->get_result();
        
        while ($p_row = $pkg_res->fetch_assoc()) {
            $dynamic_pkg_price += ($p_row['price'] * $p_row['quantity']);
        }
        $pkg_stmt->close();
        
        $price = $dynamic_pkg_price; 
        $cart_items[count($cart_items) - 1]['package_price'] = $dynamic_pkg_price; 
    } else {
        $price = 0;
    }
    
    $item_total = $price * $row['quantity'];
    $total_amount += $item_total;
    
    if (!empty($row['product_id']) && empty($row['pc_build']) && empty($row['package_id'])) {
        $subtotal_components += $item_total;
    } else {
        $subtotal_packages += $item_total;
    }
}
$stmt->close();

$promo_discount = 0;
$applied_promo_code = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $address_id = intval($_POST['shipping_address_id'] ?? 0);
        if ($address_id <= 0) throw new Exception("[SYSTEM ERROR]Please select a valid shipping address.");
        
        $addr_stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE address_id = ? AND customer_id = ?");
        $addr_stmt->bind_param("ii", $address_id, $customer_id);
        $addr_stmt->execute();
        $addr_res = $addr_stmt->get_result();
        if ($addr_res->num_rows === 0) throw new Exception("[SECURITY ALERT] Invalid shipping address selected.");
        $addr_data = $addr_res->fetch_assoc();
        $addr_stmt->close();

        $recipient = !empty($addr_data['recipient_name']) ? $addr_data['recipient_name'] : 'Customer';
        $phone = !empty($addr_data['phone_number']) ? $addr_data['phone_number'] : '000-0000000';
        if (!empty($addr_data['address_line1'])) {
            $shipping_address = $recipient . " | " . $phone . "\n" . $addr_data['address_line1'] . ", " . $addr_data['postcode'] . " " . $addr_data['city'] . ", " . $addr_data['state'];
        } else {
            $shipping_address = $recipient . " | " . $phone . "\n" . $addr_data['full_address'];
        }

        $final_payment_method = $_POST['payment_method'] ?? '';
        if (empty($final_payment_method)) throw new Exception("[SYSTEM ERROR] Please select a payment method before checking out.");

        $use_coins = isset($_POST['use_coins']) ? true : false;
        $coins_used = 0;
        $coin_discount = 0;
        $promo_discount = 0; 
        $applied_promo_code = trim($_POST['applied_promo_code'] ?? ''); 
        $promo_id_to_log = null;
        $cap_triggered_msg = ""; 

        if (!empty($applied_promo_code)) {
            $sub_comp = 0; $sub_pkg = 0;
            foreach ($cart_items as $item) {
                $price = $item['product_id'] ? $item['product_price'] : ($item['pc_build'] ? $item['build_price'] : $item['package_price']);
                $item_price = $price * $item['quantity'];
                if ($item['product_id']) { $sub_comp += $item_price; } else { $sub_pkg += $item_price; }
            }

            $promo_stmt = $conn->prepare("
                SELECT p.* FROM promo_codes p 
                LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = ? 
                WHERE p.code_name = ? AND p.status = 'Active' AND uv.promo_id IS NULL
                FOR UPDATE
            ");
            $promo_stmt->bind_param("is", $customer_id, $applied_promo_code);
            $promo_stmt->execute();
            $promo_res = $promo_stmt->get_result();
            
            if ($promo_row = $promo_res->fetch_assoc()) {
                
                if ($promo_row['is_vip_only'] == 1 && !$is_elite) {
                    throw new Exception("[ACCESS DENIED] The promo code '{$applied_promo_code}' is exclusive to ELITE members only.");
                }
                
                $target = $promo_row['target_category'];
                $target_subtotal = ($target === 'Components') ? $sub_comp : (($target === 'Packages') ? $sub_pkg : ($sub_comp + $sub_pkg));

                if ($target_subtotal < $promo_row['min_spend'] || $target_subtotal <= 0) {
                    throw new Exception("[SYSTEM ERROR] Order does not meet criteria for this promo code.");
                }

                if ($promo_row['discount_type'] === 'Fixed') {
                    $promo_discount = min($promo_row['discount_value'], $target_subtotal);
                } else {
                    $promo_discount = $target_subtotal * ($promo_row['discount_value'] / 100);
                    if ($promo_row['max_cap'] > 0 && $promo_discount > $promo_row['max_cap']) {
                        $promo_discount = $promo_row['max_cap'];
                        $cap_triggered_msg = " (Max Capped applied)";
                    }
                }
                $promo_id_to_log = $promo_row['promo_id']; 
            } else {
                throw new Exception("[SYSTEM ERROR] Invalid, expired, or already used promo code: '{$applied_promo_code}'.");
            }
            $promo_stmt->close();
        }

        $amount_after_promo = max(0, $total_amount - $promo_discount);

        if ($use_coins && $current_coins > 0) {
            $max_coin_discount = floor($current_coins / 10);
            $coin_discount = min($max_coin_discount, $amount_after_promo);
            $coins_used = $coin_discount * 10;
        }

        $discount_amount = $promo_discount + $coin_discount; 
        $final_amount = max(0, $total_amount - $discount_amount);

        $bank_account_id_to_deduct = null; 
        if ($final_payment_method === 'Credit Card') {
            $selected_card = $_POST['selected_card'] ?? '';
            if ($selected_card === 'new') {
                $card_num = str_replace([' ', '-'], '', $_POST['dummy_card_number']);
                $card_cvc = trim($_POST['dummy_card_cvc']);
                $card_name = trim($_POST['dummy_card_name'] ?? 'Cardholder'); 
                $card_expiry = trim($_POST['dummy_card_expiry'] ?? '');
                
                $exp_parts = explode('/', $card_expiry);
        if (count($exp_parts) === 2) {
            $exp_month = (int)$exp_parts[0];
            $exp_year = (int)$exp_parts[1];

            $current_year = (int)date('y'); 
            $current_month = (int)date('m'); 

            if ($exp_year < $current_year || ($exp_year === $current_year && $exp_month < $current_month)) {
                throw new Exception("[TRANSACTION FAILED] Your Credit Card has expired.");
            }
        } else {
            throw new Exception("[TRANSACTION FAILED] Invalid expiry date format. Please use MM/YY.");
        }

                $bank_stmt = $conn->prepare("SELECT id FROM bank WHERE card_number = ? AND cvc = ? AND expiry_date = ?");
                $bank_stmt->bind_param("sss", $card_num, $card_cvc, $card_expiry);
                $bank_stmt->execute();
                $bank_result = $bank_stmt->get_result();
                
                if ($bank_result->num_rows > 0) {
                    $bank_account_id_to_deduct = $bank_result->fetch_assoc()['id']; 
                    $last_four = substr($card_num, -4);
                    
                    $card_brand = 'Credit Card'; 
                    if (strpos($card_num, '4') === 0) {
                        $card_brand = 'Visa';
                    } elseif (strpos($card_num, '5') === 0 || strpos($card_num, '2') === 0) {
                        $card_brand = 'Mastercard';
                    } elseif (strpos($card_num, '3') === 0) {
                        $card_brand = 'Amex';
                    } elseif (strpos($card_num, '6') === 0) {
                        $card_brand = 'UnionPay';
                    }
                    $final_payment_method = $card_brand . " ending in " . $last_four; 
                    
                    $save_card = $conn->prepare("INSERT INTO saved_cards (customer_id, bank_id, cardholder_name, last_four_digits, expiry_date, card_brand) VALUES (?, ?, ?, ?, ?, ?)");
                    $save_card->bind_param("iissss", $customer_id, $bank_account_id_to_deduct, $card_name, $last_four, $card_expiry, $card_brand);
                    $save_card->execute();

                } else {
                    throw new Exception("[TRANSACTION FAILED] Bank Declined: Invalid Card Number, Expiry Date, or CVC. Please try again.");
                }
            } else {
                $card_id = intval($selected_card);
                $saved_stmt = $conn->prepare("SELECT card_brand, last_four_digits, bank_id FROM saved_cards WHERE card_id = ? AND customer_id = ?");
                $saved_stmt->bind_param("ii", $card_id, $customer_id);
                $saved_stmt->execute();
                if ($saved_row = $saved_stmt->get_result()->fetch_assoc()) {
                    $final_payment_method = $saved_row['card_brand'] . " ending in " . $saved_row['last_four_digits'];
                    $bank_account_id_to_deduct = $saved_row['bank_id']; 
                } else {
                    throw new Exception("Selected card not found.");
                }
            }
        } elseif ($final_payment_method === 'Online Banking (FPX)') {
            $fpx_user = trim($_POST['fpx_username'] ?? ''); 
            $fpx_pass = trim($_POST['fpx_password'] ?? '');
            $fpx_stmt = $conn->prepare("SELECT id FROM bank WHERE fpx_username = ? AND fpx_password = ?");
            $fpx_stmt->bind_param("ss", $fpx_user, $fpx_pass);
            $fpx_stmt->execute();
            if ($fpx_data = $fpx_stmt->get_result()->fetch_assoc()) {
                 $bank_account_id_to_deduct = $fpx_data['id']; 
                 $final_payment_method = "FPX - " . ($_POST['selected_bank'] ?? 'Bank');
            } else {
                 throw new Exception("[SYSTEM ERROR] FPX Login Failed: Invalid username or password.");
            }
        }

        $required_products = []; 
        foreach ($cart_items as $item) {
            $qty = $item['quantity'];
            if ($item['product_id']) {
                $pid = $item['product_id'];
                $required_products[$pid] = ($required_products[$pid] ?? 0) + $qty;
            } elseif ($item['pc_build']) {
                $build_stmt = $conn->prepare("SELECT product_id, quantity FROM build_items WHERE pc_build = ?");
                $build_stmt->bind_param("i", $item['pc_build']);
                $build_stmt->execute();
                $b_res = $build_stmt->get_result();
                while ($b_item = $b_res->fetch_assoc()) {
                    $pid = $b_item['product_id'];
                    $required_products[$pid] = ($required_products[$pid] ?? 0) + ($b_item['quantity'] * $qty);
                }
                $build_stmt->close();
            } elseif ($item['package_id']) {
                $pkg_stmt = $conn->prepare("SELECT product_id, quantity FROM package_items WHERE package_id = ?");
                $pkg_stmt->bind_param("i", $item['package_id']);
                $pkg_stmt->execute();
                $p_res = $pkg_stmt->get_result();
                while ($p_item = $p_res->fetch_assoc()) {
                    $pid = $p_item['product_id'];
                    $required_products[$pid] = ($required_products[$pid] ?? 0) + ($p_item['quantity'] * $qty);
                }
                $pkg_stmt->close();
            }
        }

        ksort($required_products); 

        foreach ($required_products as $pid => $req_qty) {
            $stock_stmt = $conn->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ? FOR UPDATE");
            $stock_stmt->bind_param("i", $pid);
            $stock_stmt->execute();
            $stock_check = $stock_stmt->get_result()->fetch_assoc();
            
            if (!$stock_check || $stock_check['stock_quantity'] < $req_qty) {
                $p_name = $stock_check ? $stock_check['product_name'] : "Unknown Part ID $pid";
                throw new Exception("[INVENTORY CONFLICT] Inventory Error: '{$p_name}' only has " . ($stock_check['stock_quantity'] ?? 0) . " left. Order aborted to prevent phantom stock.");
            }
            
            $deduct_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $deduct_stock->bind_param("ii", $req_qty, $pid);
            $deduct_stock->execute();
        }

        if ($final_amount > 0) {
            if ($bank_account_id_to_deduct !== null) {
                $deduct_stmt = $conn->prepare("UPDATE bank SET balance = balance - ? WHERE id = ? AND balance >= ?");
                $deduct_stmt->bind_param("did", $final_amount, $bank_account_id_to_deduct, $final_amount);
                $deduct_stmt->execute();
                if ($deduct_stmt->affected_rows === 0) throw new Exception("[TRANSACTION FAILED] Bank Declined: Insufficient funds in your bank account.");
            }

            if ($final_payment_method === 'E-Wallet') {
                $deduct_wallet = $conn->prepare("UPDATE customers SET wallet_balance = wallet_balance - ? WHERE customer_id = ? AND wallet_balance >= ?");
                $deduct_wallet->bind_param("did", $final_amount, $customer_id, $final_amount);
                $deduct_wallet->execute();
                if ($deduct_wallet->affected_rows === 0) throw new Exception("[TRANSACTION FAILED] Insufficient E-Wallet balance! Please top up.");
                
                $insert_trans = $conn->prepare("INSERT INTO wallet_transactions (customer_id, type, amount) VALUES (?, 'Payment', ?)");
                $neg_amount = -$final_amount; 
                $insert_trans->bind_param("id", $customer_id, $neg_amount);
                $insert_trans->execute();
            }
        } 
        
        if ($coins_used > 0) {
            $deduct_coins = $conn->prepare("UPDATE customers SET reward_coins = reward_coins - ? WHERE customer_id = ? AND reward_coins >= ?");
            $deduct_coins->bind_param("iii", $coins_used, $customer_id, $coins_used);
            $deduct_coins->execute();
            if ($deduct_coins->affected_rows === 0) throw new Exception("Coin deduction failed.");
        }

        $insert_order = $conn->prepare("INSERT INTO orders (customer_id, total_amount, discount_amount, coins_used, order_status, shipping_address) VALUES (?, ?, ?, ?, 'Pending', ?)");
        $insert_order->bind_param("iddis", $customer_id, $final_amount, $discount_amount, $coins_used, $shipping_address);
        $insert_order->execute();
        $order_id = $insert_order->insert_id;

        if ($promo_id_to_log) {
            $log_used = $conn->prepare("INSERT INTO used_vouchers (customer_id, promo_id, order_id) VALUES (?, ?, ?)");
            $log_used->bind_param("iii", $customer_id, $promo_id_to_log, $order_id);
            try {
                if (!$log_used->execute() || $log_used->affected_rows === 0) {
                    throw new Exception("Concurrency Error: Voucher already used.");
                }
            } catch (mysqli_sql_exception $e) {
                throw new Exception("Security Alert: Voucher usage conflict detected.");
            }
        }

        $insert_detail = $conn->prepare("INSERT INTO order_details (order_id, product_id, pc_build, package_id, affiliate_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
      
        $buyer_coins_earned = floor($final_amount / 10);
        if ($buyer_coins_earned > 0) {
            $buyer_reward = $conn->prepare("UPDATE customers SET reward_coins = reward_coins + ?, lifetime_coins = lifetime_coins + ? WHERE customer_id = ?");
            $buyer_reward->bind_param("iii", $buyer_coins_earned, $buyer_coins_earned, $customer_id);
            $buyer_reward->execute();
        }

        $affiliate_reward_stmt = $conn->prepare("UPDATE customers SET reward_coins = reward_coins + ?, lifetime_coins = lifetime_coins + ? WHERE customer_id = ?");
        $bounty_per_build = 500; 

        foreach ($cart_items as $item) {
            $pid = $item['product_id'] ?: NULL;
            $build_id = $item['pc_build'] ?: NULL;
            $pkg_id = $item['package_id'] ?: NULL;
            $aff_id = $item['affiliate_id'] ?: NULL; 
            
            $unit_price = $pid ? $item['product_price'] : ($build_id ? $item['build_price'] : ($pkg_id ? $item['package_price'] : 0));

            $insert_detail->bind_param("iiiiiid", $order_id, $pid, $build_id, $pkg_id, $aff_id, $item['quantity'], $unit_price);
            $insert_detail->execute();

            if ($aff_id) {
                $total_bounty = $bounty_per_build * $item['quantity']; 
                $affiliate_reward_stmt->bind_param("iii", $total_bounty, $total_bounty, $aff_id);
                $affiliate_reward_stmt->execute();
            }
        }

        $payment_status = ($final_payment_method == 'Cash on Delivery') ? 'Pending' : 'Paid';
        $insert_payment = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_status) VALUES (?, ?, ?)");
        $insert_payment->bind_param("iss", $order_id, $final_payment_method, $payment_status);
        $insert_payment->execute();

        $clear_cart = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id = ?");
        $clear_cart->bind_param("i", $customer_id);
        $clear_cart->execute();

        $conn->commit();
        $_SESSION['success_msg'] = "[TRANSMISSION SUCCESS] Order placed successfully! Your Order ID is #$order_id. Promo saved RM " . number_format($promo_discount, 2) . $cap_triggered_msg;
        header("Location: my_orders.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Checkout failed. " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; }
        .checkout-layout { display: grid; grid-template-columns: 1fr 420px; gap: 30px; align-items: start; margin-top: 30px; }
        @media(max-width: 1024px) { .checkout-layout { grid-template-columns: 1fr; } }
        .checkout-panel { background: rgba(10, 10, 15, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); margin-bottom: 25px; }
        .panel-title { color: #fff; font-size: 1.3rem; font-weight: 800; margin: 0 0 25px 0; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px; }
        .panel-title i { color: #00f2fe; }
        .info-card { display: flex; align-items: flex-start; cursor: pointer; margin-bottom: 15px; padding: 18px; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .info-card:hover { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); }
        .info-card input[type="radio"] { margin-right: 15px; margin-top: 5px; accent-color: #00f2fe; transform: scale(1.2); }
        .info-card .card-content { flex: 1; }
        .info-badge { background: #00f2fe; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; display: inline-block; }
        .pm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
        @media(max-width: 600px) { .pm-grid { grid-template-columns: 1fr; } }
        .pm-card { cursor: pointer; display: block; }
        .pm-card input[type="radio"] { display: none; }
        .pm-content { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; text-align: center; transition: 0.3s; color: #888; }
        .pm-content i { font-size: 1.8rem; margin-bottom: 12px; display: block; color: inherit; transition: 0.3s;}
        .pm-content span { font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;}
        .pm-card:hover .pm-content { border-color: rgba(0,242,254,0.4); background: rgba(0,242,254,0.05); color: #fff; }
        .pm-card input[type="radio"]:checked + .pm-content { border-color: #00f2fe; background: rgba(0,242,254,0.1); color: #00f2fe; box-shadow: 0 0 20px rgba(0,242,254,0.2); transform: translateY(-2px); }
        .bank-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; }
        .bank-card { display: flex; align-items: center; cursor: pointer; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .bank-card:hover { border-color: #00f2fe; box-shadow: 0 0 15px rgba(0,242,254,0.1); }
        .bank-card img { max-height: 35px; max-width: 100%; object-fit: contain; }
        .cyber-input { width: 100%; background: #0a0a0f; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 14px; border-radius: 8px; font-size: 1rem; font-family: 'Inter', sans-serif; box-sizing: border-box; transition: 0.3s; }
        .cyber-input:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 0 2px rgba(0, 242, 254, 0.15); }
        .cyber-button { background: #00f2fe; color: #000; border: none; padding: 16px; border-radius: 8px; font-weight: 900; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px; width: 100%; font-family: 'Inter', sans-serif; }
        .cyber-button:hover { background: #fff; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px); }
        .checkout-sidebar { position: -webkit-sticky; position: sticky; top: 100px; height: max-content; }
        .receipt-box { background: #0b0f16; border: 1px solid rgba(0, 242, 254, 0.3); border-radius: 12px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 18px; align-items: flex-start; }
        .receipt-item .name { color: #cbd5e1; font-weight: 600; font-size: 0.95rem; line-height: 1.4; flex: 1; padding-right: 15px; }
        .receipt-item .price { color: #fff; font-family: 'JetBrains Mono', monospace; font-weight: 700; white-space: nowrap; }
        .receipt-sub { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); color: #94a3b8; font-size: 0.9rem; }
        .receipt-sub .val { font-family: 'JetBrains Mono', monospace; color: #fff; }
        .receipt-discount { display: flex; justify-content: space-between; margin-top: 10px; color: #00f2fe; font-size: 0.9rem; font-weight: bold; }
        .receipt-discount.gold { color: #ffd700; }
        .receipt-discount .val { font-family: 'JetBrains Mono', monospace; }
        .receipt-total { border-top: 1px solid rgba(0, 242, 254, 0.3); margin-top: 20px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .receipt-total .label { font-size: 1.2rem; color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .receipt-total .val { font-size: 1.8rem; color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-weight: 900; text-shadow: 0 0 15px rgba(0,242,254,0.3); }
        .ewallet-card { background: linear-gradient(135deg, rgba(0,242,254,0.1) 0%, rgba(168,85,247,0.1) 100%); border: 1px solid #00f2fe; border-radius: 12px; padding: 25px; position: relative; overflow: hidden; }
        .ewallet-card::after { content: '\f555'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: -20px; bottom: -30px; font-size: 8rem; color: rgba(0, 242, 254, 0.05); transform: rotate(-15deg); }
        .voucher-card-item { display: flex; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; margin-bottom: 15px; cursor: pointer; transition: 0.3s; }
        .voucher-card-item.vip-only:hover { border-color: #ffd700; background: rgba(255,215,0,0.1); transform: scale(1.02); box-shadow: 0 5px 15px rgba(255,215,0,0.2); }
        .voucher-card-item.public-only:hover { border-color: #00f2fe; background: rgba(0,242,254,0.1); transform: scale(1.02); box-shadow: 0 5px 15px rgba(0,242,254,0.2); }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container" style="max-width: 1300px; margin: 0 auto; padding: 40px 20px;">
        
        <div style="margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; color: #fff;">SECURE <span style="color: #00f2fe; text-shadow: 0 0 20px rgba(0,242,254,0.4);">CHECKOUT</span></h1>
            <p style="color: #888; font-size: 1rem; margin-top: 5px;">Encrypted connection. Review your telemetry before transmitting order.</p>
        </div>

        <?php 
        if (isset($_SESSION['error_msg'])) {
            echo "<div style='background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 25px;'><i class='fa-solid fa-triangle-exclamation'></i> " . $_SESSION['error_msg'] . "</div>";
            unset($_SESSION['error_msg']);
        }
        if (!empty($error_message)) {
            echo "<div style='background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 25px;'><i class='fa-solid fa-triangle-exclamation'></i> " . $error_message . "</div>";
        }
        ?>

        <form action="checkout.php" method="POST" id="checkoutForm" class="checkout-layout">
            
            <div class="checkout-main">
                
                <div class="checkout-panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-location-dot"></i> Shipping Coordinates
                    </div>

                    <div id="active_address_display" class="info-card" style="border-color: #00f2fe; background: rgba(0,242,254,0.05);">
                        <div class="card-content" id="current_address_text" style="color: #fff; line-height: 1.6;">
                            <?php echo $default_address_html; ?>
                        </div>
                        <button type="button" onclick="toggleAddressList()" style="background: rgba(0,242,254,0.1); border: 1px solid #00f2fe; color: #00f2fe; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.8rem; margin-left: 15px;">CHANGE</button>
                    </div>

                    <div id="address_selection_list" style="display: none; margin-top: 20px;">
                        
                        <?php 
                        $has_default = false;
                        if (!empty($saved_addresses)) {
                            foreach ($saved_addresses as $a) {
                                if ($a['is_default']) { $has_default = true; break; }
                            }
                        }
                        $is_first = true;

                        if(!empty($saved_addresses)): foreach ($saved_addresses as $addr): 
                            $recipient = !empty($addr['recipient_name']) ? $addr['recipient_name'] : 'Customer';
                            $phone = !empty($addr['phone_number']) ? $addr['phone_number'] : '000-0000000';
                            if (!empty($addr['address_line1'])) {
                                $full_text = $recipient . " | " . $phone . "<br>" . $addr['address_line1'] . ", " . $addr['postcode'] . " " . $addr['city'] . ", " . $addr['state'];
                            } else {
                                $full_text = strpos($addr['full_address'], '|') !== false ? nl2br($addr['full_address']) : $recipient . " | " . $phone . "<br>" . nl2br($addr['full_address']);
                            }
                            $addr_id_val = isset($addr['address_id']) ? $addr['address_id'] : $addr['id'];
                            
                            $is_checked = false;
                            if ($addr['is_default']) {
                                $is_checked = true;
                            } else if (!$has_default && $is_first) {
                                $is_checked = true;
                            }
                            $is_first = false;
                        ?>
                            <label class="info-card">
                                <input type="radio" name="shipping_address_id" value="<?php echo htmlspecialchars($addr_id_val); ?>" data-text="<?php echo htmlspecialchars($full_text); ?>" onchange="updateActiveAddress(this)" <?php echo $is_checked ? 'checked' : ''; ?>>
                                <div class="card-content">
                                    <?php if($addr['is_default']) echo '<span class="info-badge">DEFAULT</span>'; ?>
                                    <strong style="color: #fff; display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($recipient); ?> | <?php echo htmlspecialchars($phone); ?></strong>
                                    <span style="color: #888; font-size: 0.85rem; line-height: 1.4;">
                                        <?php 
                                            if (!empty($addr['address_line1'])) {
                                                echo htmlspecialchars($addr['address_line1']) . "<br>" . htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']) . ", " . $addr['state'];
                                            } else {
                                                echo nl2br(htmlspecialchars($addr['full_address'])); 
                                            }
                                        ?>
                                    </span>
                                </div>
                            </label>
                        <?php endforeach; endif; ?>

                        <a href="profile.php" style="display: block; text-align: center; color: #00f2fe; text-decoration: none; font-size: 0.9rem; margin-top: 10px;"><i class="fa-solid fa-plus"></i> Register New Address</a>
                    </div>
                </div>

                <?php if ($current_coins >= 10): ?>
                <div class="checkout-panel" style="border-color: rgba(255,215,0,0.3); background: rgba(255,215,0,0.02); padding: 20px 30px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="use_coins" name="use_coins" style="margin-right: 15px; width: 20px; height: 20px; accent-color: #ffd700;">
                        <span id="coins-label-text" style="color: #fff; font-size: 1rem;">
                            Utilize up to <strong style="color: #ffd700;"><?php echo $current_coins; ?> Coins</strong> for 
                            <strong style="color: #ffd700; font-family: 'JetBrains Mono';">RM <?php echo number_format(floor($current_coins/10), 2); ?> OFF</strong>
                        </span>
                    </label>
                </div>
                <?php endif; ?>

                <div class="checkout-panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-money-check-dollar"></i> Payment Gateway
                    </div>
                    
                    <div class="pm-grid">
                        <label class="pm-card">
                            <input type="radio" name="payment_method" value="E-Wallet" onchange="togglePaymentSections()" required>
                            <div class="pm-content">
                                <i class="fa-solid fa-wallet"></i>
                                <span>Digital Wallet</span>
                            </div>
                        </label>
                        <label class="pm-card">
                            <input type="radio" name="payment_method" value="Credit Card" onchange="togglePaymentSections()">
                            <div class="pm-content">
                                <i class="fa-regular fa-credit-card"></i>
                                <span>Credit / Debit</span>
                            </div>
                        </label>
                        <label class="pm-card">
                            <input type="radio" name="payment_method" value="Online Banking (FPX)" onchange="togglePaymentSections()">
                            <div class="pm-content">
                                <i class="fa-solid fa-building-columns"></i>
                                <span>FPX Banking</span>
                            </div>
                        </label>
                    </div>

                    <div id="ewallet_section" class="ewallet-card" style="display: none; margin-top: 15px;">
                        <div style="position: relative; z-index: 1;">
                            <p style="color: #00f2fe; font-size: 0.8rem; margin: 0 0 5px 0; font-weight: 800; letter-spacing: 1px;">AVAILABLE BALANCE</p>
                            <h2 style="color: #fff; font-size: 2.2rem; margin: 0; font-family: 'JetBrains Mono', monospace; text-shadow: 0 0 15px rgba(0,242,254,0.5);">RM <?php echo number_format($current_balance, 2); ?></h2>
                            <a href="wallet_topup.php" style="color: #00f2fe; text-decoration: none; font-size: 0.85rem; font-weight: bold; margin-top: 15px; display: inline-block;"><i class="fa-solid fa-bolt"></i> Recharge Wallet</a>
                        </div>
                    </div>

                    <div id="credit_card_section" style="display: none; margin-top: 15px;">
                        <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase;">Saved Credentials</p>
                        <?php if(!empty($saved_cards)): ?>
                            <?php foreach ($saved_cards as $index => $card): ?>
                                <label class="info-card">
                                    <input type="radio" name="selected_card" value="<?php echo htmlspecialchars($card['card_id']); ?>" onchange="toggleNewCardForm()" <?php echo $card['is_default'] ? 'checked' : ''; ?>>
                                    <div class="card-content">
<span style="font-family: 'JetBrains Mono', monospace; letter-spacing: 2px;">
    <?php echo $card['card_brand']; ?> &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; <?php echo $card['last_four_digits']; ?>
</span>
                                        <?php if($card['is_default']) echo '<span class="info-badge" style="margin-left:10px;">DEFAULT</span>'; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <label class="info-card" style="border-style: dashed;">
                            <input type="radio" name="selected_card" value="new" onchange="toggleNewCardForm()">
                            <strong style="color: #fff;">+ Add New Card</strong>
                        </label>

                        <div id="new_card_form" style="display: none; margin-top: 15px; padding: 20px; background: rgba(0,0,0,0.4); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <p style="color: #facc15; font-size: 0.8rem; margin-top: 0;"><i class="fa-solid fa-shield-halved"></i> Simulated secure connection enabled.</p>
                            
                            <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                                <input type="text" name="dummy_card_name" placeholder="Name on Card" class="cyber-input" style="flex: 2; text-transform: uppercase;">
                                <label style="flex: 1; display: flex; align-items: center; cursor: pointer; color: #fff; font-size: 0.9rem; background: rgba(0,242,254,0.1); border: 1px solid rgba(0,242,254,0.3); padding: 0 15px; border-radius: 8px;">
                                    <input type="checkbox" name="save_new_card" value="1" style="margin-right: 10px; width: 18px; height: 18px; accent-color: #00f2fe;"> 
                                    Save this card
                                </label>
                            </div>
                            
                            <div style="display: flex; gap: 15px;">
                                <input type="text" id="dummy_card_number" name="dummy_card_number" placeholder="0000 0000 0000 0000" class="cyber-input" style="flex: 2;" maxlength="19">
                                <input type="text" id="dummy_card_expiry" name="dummy_card_expiry" placeholder="MM/YY" class="cyber-input" style="flex: 1;" maxlength="5">
                                <input type="password" name="dummy_card_cvc" placeholder="CVC" class="cyber-input" style="flex: 1;" maxlength="4">
                            </div>
                        </div>
                    </div>
                    
                    <div id="fpx_section" style="display: none; margin-top: 15px;">
                        <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase;">Select Banking Node</p>
                        <div class="bank-grid">
                            <?php 
                            $banks = ['Maybank2U' => 'maybank.png', 'CIMB Clicks' => 'cimb.png', 'Public Bank' => 'public.png', 'RHB Now' => 'rhb.png'];
                            foreach($banks as $bname => $bimg): 
                            ?>
                            <label class="bank-card">
                                <input type="radio" name="selected_bank" value="<?php echo $bname; ?>" style="margin-right: 15px; accent-color:#00f2fe;" onchange="toggleFPXForm()">
                                <img src="image/<?php echo $bimg; ?>" alt="<?php echo $bname; ?>">
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div id="fpx_login_form" style="display: none; margin-top: 20px; padding: 20px; background: rgba(0,0,0,0.4); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <p style="color: #facc15; font-size: 0.8rem; margin-top: 0;"><i class="fa-solid fa-lock"></i> Bank Authentication Required</p>
                            <div style="display: flex; gap: 15px;">
                                <input type="text" name="fpx_username" placeholder="Username" class="cyber-input">
                                <input type="password" name="fpx_password" placeholder="Password" class="cyber-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-panel" style="border-color: <?php echo $is_elite ? 'rgba(255,215,0,0.3)' : 'rgba(255,255,255,0.08)'; ?>;">
                    <div class="panel-title" style="color: <?php echo $is_elite ? '#ffd700' : '#fff'; ?>;">
                        <i class="fa-solid fa-ticket" style="color: inherit;"></i> Apply Voucher Code
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <input type="text" name="applied_promo_code" id="promo_code_input" class="cyber-input" placeholder="Enter override code..." style="font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">
                        <button type="button" onclick="openVoucherModal()" class="cyber-button" style="width: auto; background: transparent; border: 1px solid <?php echo $is_elite ? '#ffd700' : '#00f2fe'; ?>; color: <?php echo $is_elite ? '#ffd700' : '#00f2fe'; ?>; padding: 0 20px;">
                            Browse
                        </button>
                    </div>
                </div>

            </div>

            <div class="checkout-sidebar">
                <div class="receipt-box">
                    <h3 style="margin: 0 0 25px 0; color: #fff; font-size: 1.2rem; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 15px;"><i class="fa-solid fa-file-invoice" style="color: #00f2fe; margin-right: 10px;"></i> ORDER MANIFEST</h3>
                    
                    <div style="margin-bottom: 25px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="receipt-item">
                                <div class="name">
                                    <span style="color: #64748b; font-size: 0.8rem; margin-right: 5px;"><?php echo $item['quantity']; ?>x</span>
                                    <?php 
                                    if ($item['product_id']) echo htmlspecialchars($item['product_name'] ?? 'Unknown Component');
                                    elseif ($item['pc_build']) echo htmlspecialchars("Rig: " . ($item['build_name'] ?? 'Custom Build'));
                                    elseif ($item['package_id']) echo htmlspecialchars("Pkg: " . ($item['package_name'] ?? 'Standard Package'));
                                    ?>
                                </div>
                                <div class="price">
                                    RM <?php 
                                        if ($item['product_id']) echo number_format($item['product_price'] * $item['quantity'], 2);
                                        elseif ($item['pc_build']) echo number_format($item['build_price'] * $item['quantity'], 2);
                                        elseif ($item['package_id']) echo number_format($item['package_price'] * $item['quantity'], 2);
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="receipt-sub">
                        <span>Subtotal</span>
                        <span class="val" id="subtotal-display" data-subtotal="<?php echo $total_amount; ?>">RM <?php echo number_format($total_amount, 2); ?></span>
                    </div>

                    <div class="receipt-discount" id="promo-row" style="display: none;">
                        <span>Voucher (<span id="promo-name-display"></span>)</span>
                        <span class="val" id="promo-amount-display">- RM 0.00</span>
                    </div>
                    
                    <div class="receipt-discount gold" id="discount-row" style="display: none;">
                        <span>Coins Reclaimed</span>
                        <span class="val" id="discount-display" data-discount="<?php echo floor($current_coins/10); ?>">- RM <?php echo number_format(floor($current_coins/10), 2); ?></span>
                    </div>

                    <div class="receipt-total">
                        <span class="label">NET TOTAL</span>
                        <span class="val" id="final-total-display">RM <?php echo number_format($total_amount, 2); ?></span>
                    </div>

                    <button type="submit" class="cyber-button" style="margin-top: 30px;">
                        <i class="fa-solid fa-satellite-dish"></i> TRANSMIT ORDER
                    </button>
                    
                    <p style="text-align: center; color: #64748b; font-size: 0.75rem; margin-top: 15px;"><i class="fa-solid fa-shield-halved"></i> 256-bit Encrypted Transaction</p>
                </div>
            </div>

        </form>
    </main>

    <div id="voucherModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(10px);">
        <div style="background: #0b0f16; margin: 5% auto; padding: 0; width: 90%; max-width: 500px; border-radius: 12px; border: 1px solid #00f2fe; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
            
            <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(0,242,254,0.05);">
                <h3 style="margin: 0; color: #fff; font-weight: 800;"><i class="fa-solid fa-ticket" style="color: #00f2fe; margin-right: 10px;"></i> VOUCHER DATABASE</h3>
                <span onclick="closeVoucherModal()" style="color: #888; cursor: pointer; font-size: 1.5rem; transition: 0.3s;" onmouseover="this.style.color='#fff'">&times;</span>
            </div>

            <div style="padding: 25px; max-height: 500px; overflow-y: auto;">
                <?php
                $sql_vouchers = "SELECT p.* FROM promo_codes p 
                                 LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = $customer_id 
                                 WHERE p.status = 'Active' AND uv.promo_id IS NULL AND (p.is_vip_only = 0";
                if ($is_elite) { $sql_vouchers .= " OR p.is_vip_only = 1"; }
                $sql_vouchers .= ") ORDER BY p.is_vip_only DESC, p.discount_value DESC";
                
                $res_vouchers = $conn->query($sql_vouchers);

                if ($res_vouchers->num_rows > 0):
                    while ($v = $res_vouchers->fetch_assoc()):
                        $is_vip = ($v['is_vip_only'] == 1);
                        $v_color = $is_vip ? '#ffd700' : '#00f2fe';
                        $v_bg = $is_vip ? 'rgba(255,215,0,0.1)' : 'rgba(0,242,254,0.1)';
                        $card_class = $is_vip ? 'voucher-card-item vip-only' : 'voucher-card-item public-only';
                ?>
                    <div onclick="selectVoucher('<?php echo $v['code_name']; ?>')" class="<?php echo $card_class; ?>">
                        
                        <div style="background: <?php echo $v_color; ?>; color: #000; width: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px 10px; font-family: 'JetBrains Mono', monospace;">
                            <?php if($v['discount_type'] == 'Fixed'): ?>
                                <span style="font-weight: 900; font-size: 1.1rem;">RM<?php echo floatval($v['discount_value']); ?></span>
                            <?php else: ?>
                                <span style="font-weight: 900; font-size: 1.4rem;"><?php echo floatval($v['discount_value']); ?>%</span>
                            <?php endif; ?>
                            <span style="font-size: 0.65rem; font-weight: 800; font-family: 'Inter', sans-serif;">OFF</span>
                        </div>

                        <div style="padding: 15px; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <strong style="color: #fff; font-size: 1.1rem; letter-spacing: 1px;"><?php echo $v['code_name']; ?></strong>
                                <span style="background: <?php echo $v_bg; ?>; color: <?php echo $v_color; ?>; border: 1px solid <?php echo $v_color; ?>; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; font-weight: bold;">
                                    <?php echo $is_vip ? 'ELITE' : 'PUBLIC'; ?>
                                </span>
                            </div>
                            
                            <div style="color: #888; font-size: 0.8rem; font-family: 'Inter', sans-serif;">
                                <?php 
                                    $terms = [];
                                    if ($v['min_spend'] > 0) $terms[] = "Min RM " . floatval($v['min_spend']);
                                    if ($v['max_cap'] > 0) $terms[] = "Cap RM " . floatval($v['max_cap']);
                                    echo empty($terms) ? "No minimum spend" : implode(' • ', $terms);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                    echo '<p style="text-align: center; color: #666; font-style: italic;">Database empty. No active vouchers.</p>';
                endif;
                ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
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

        const isElite = <?php echo $is_elite ? 'true' : 'false'; ?>;
        const baseSubtotal = <?php echo (float)$total_amount; ?>;
        const maxUserCoins = <?php echo (int)$current_coins; ?>;
        
        let currentPromoDiscount = 0;
        let currentCoinsDiscount = 0;

        function toggleAddressList() {
            const list = document.getElementById('address_selection_list');
            const btn = event.target;
            if (list.style.display === 'none') {
                list.style.display = 'block';
                btn.innerText = 'CANCEL';
            } else {
                list.style.display = 'none';
                btn.innerText = 'CHANGE';
            }
        }

        function updateActiveAddress(radio) {
            document.getElementById('current_address_text').innerHTML = radio.getAttribute('data-text');
            document.getElementById('address_selection_list').style.display = 'none';
            const changeBtn = document.querySelector('#active_address_display button');
            if(changeBtn) changeBtn.innerText = 'CHANGE';
        }

        document.addEventListener('DOMContentLoaded', function() {
            togglePaymentSections();
            updateFinalTotal();
        });

        function togglePaymentSections() {
            const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
            const method = checkedRadio ? checkedRadio.value : '';
            
            const sections = {
                'Credit Card': document.getElementById('credit_card_section'),
                'Online Banking (FPX)': document.getElementById('fpx_section'),
                'E-Wallet': document.getElementById('ewallet_section')
            };
            const fpxForm = document.getElementById('fpx_login_form');

            Object.values(sections).forEach(s => { if(s) s.style.display = 'none'; });
            if(fpxForm) fpxForm.style.display = 'none';

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

        function updateFinalTotal() {
            const promoInput = document.getElementById('promo_code_input');
            const code = promoInput ? promoInput.value.trim().toUpperCase() : '';
            if (promoInput) promoInput.value = code; 
            const promoRow = document.getElementById('promo-row');
            
            currentPromoDiscount = 0;
            if (code && activeVouchers[code]) {
                const v = activeVouchers[code];
                const targetSubtotal = cartSubtotals[v.cat] || cartSubtotals['All'];
                const isVipValid = (v.vip === 0 || (v.vip === 1 && isElite)); 
                const isSpendValid = (targetSubtotal >= v.min);

                if (isVipValid && isSpendValid && targetSubtotal > 0) {
                    let capNotice = '';
                    if (v.type === 'Fixed') {
                        currentPromoDiscount = Math.min(v.val, targetSubtotal);
                    } else {
                        currentPromoDiscount = targetSubtotal * (v.val / 100);
                        if (v.max > 0 && currentPromoDiscount > v.max) {
                            currentPromoDiscount = v.max;
                            capNotice = ' <span style="color:#ffcc00; font-size:0.7rem; font-family:\'Inter\'; background:rgba(255,204,0,0.1); padding:2px 6px; border-radius:4px; margin-left:8px;">MAX CAPPED</span>';
                        }
                    }
                    
                    document.getElementById('promo-name-display').innerHTML = code + capNotice;
                    document.getElementById('promo-amount-display').innerText = '- RM ' + currentPromoDiscount.toFixed(2);
                    promoRow.style.display = 'flex';
                } else {
                    promoRow.style.display = 'none';
                }
            } else {
                promoRow.style.display = 'none';
            }

            const amountAfterPromo = Math.max(0, baseSubtotal - currentPromoDiscount);

            const useCoinsCheckbox = document.getElementById('use_coins');
            const discountRow = document.getElementById('discount-row');
            const coinsLabelText = document.getElementById('coins-label-text');
            
            currentCoinsDiscount = 0;
            if (useCoinsCheckbox) {
                let maxPossibleCoinValue = Math.floor(maxUserCoins / 10);
                
                if (useCoinsCheckbox.checked) {
                    currentCoinsDiscount = (maxPossibleCoinValue > amountAfterPromo) ? amountAfterPromo : maxPossibleCoinValue;
                    const coinsToDeduct = currentCoinsDiscount * 10;
                    document.getElementById('discount-display').innerText = '- RM ' + currentCoinsDiscount.toFixed(2);
                    coinsLabelText.innerHTML = `Deploy <strong>${coinsToDeduct} Coins</strong> for <strong style="color: #ffd700; font-family: 'JetBrains Mono';">RM ${currentCoinsDiscount.toFixed(2)} OFF</strong>`;
                    discountRow.style.display = 'flex';
                } else {
                    coinsLabelText.innerHTML = `Utilize up to <strong style="color: #ffd700;">${maxUserCoins} Coins</strong> for <strong style="color: #ffd700; font-family: 'JetBrains Mono';">RM ${maxPossibleCoinValue.toFixed(2)} OFF</strong>`;
                    discountRow.style.display = 'none';
                }
            }

            const finalAmount = Math.max(0, amountAfterPromo - currentCoinsDiscount);
            document.getElementById('final-total-display').innerText = 'RM ' + finalAmount.toFixed(2);
        }

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
            updateFinalTotal();
        }
        
        const dummyCardInput = document.getElementById('dummy_card_number');
        if (dummyCardInput) {
            dummyCardInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); 
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formattedValue += ' ';
                    formattedValue += value[i];
                }
                e.target.value = formattedValue;
            });
        }
        
        const expiryInput = document.getElementById('dummy_card_expiry');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    let month = value.substring(0, 2);
                    
                    if (value.length >= 2) {
                        if (parseInt(month) > 12) month = '12';
                        if (parseInt(month) === 0) month = '01';
                    }
                    
                    let year = value.substring(2, 4);

                    if (value.length === 4) {
                const currentYear = new Date().getFullYear() % 100; 
                const maxYear = currentYear + 10; 
                
                if (parseInt(year) < currentYear) {
                    year = currentYear.toString(); 
                } else if (parseInt(year) > maxYear) {
                    year = maxYear.toString(); 
                }
            }

                    if (value.length > 2) {
                        value = month + '/' + year;
                    } else {
                        value = month;
                    }
                }
                e.target.value = value;
            });
        }

        const promoInputNode = document.getElementById('promo_code_input');
        if(promoInputNode) promoInputNode.addEventListener('input', updateFinalTotal);
        const coinsCheckboxNode = document.getElementById('use_coins');
        if(coinsCheckboxNode) coinsCheckboxNode.addEventListener('change', updateFinalTotal);
        window.onclick = function(event) { if (event.target == document.getElementById('voucherModal')) closeVoucherModal(); }
    </script>

</body>
</html>