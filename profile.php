<?php
ob_start(); 
session_start();
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit(); }
$customer_id = $_SESSION['customer_id'];

$update_msg = $update_err = "";
$addr_msg = $addr_err = "";
$card_msg = $card_err = "";

$open_acc = 'account';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_user = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $new_birthday = trim($_POST['birthday']);

    $raw_phone = trim($_POST['phone_number']);
    if (!empty($raw_phone)) {
        $raw_phone = preg_replace('/^\+?60/', '', $raw_phone); 
        $raw_phone = ltrim($raw_phone, '0'); 
        $new_phone = "+60" . $raw_phone; 
    } else {
        $new_phone = "";
    }

    if (empty($new_user) || empty($new_email)) { 
        $update_err = "Username and Email cannot be empty."; 
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $new_user)) {
        $update_err = "Username must be 3-20 characters (letters, numbers, underscore).";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { 
        $update_err = "Invalid email format."; 
    } elseif (!empty($new_phone) && !preg_match('/^\+60[0-9]{8,10}$/', $new_phone)) {
        $update_err = "Phone number must be a valid Malaysian format (8 to 10 digits after +60).";
    } elseif (!empty($new_birthday)) {
        $input_time = strtotime($new_birthday);
        $min_allowed_time = strtotime('1900-01-01');
        $max_allowed_time = strtotime('-13 years');

        if ($input_time === false || $input_time < $min_allowed_time || $input_time > $max_allowed_time) {
            $update_err = "Invalid birthday. You must be at least 13 years old.";
        }
    } 
        
    if (empty($update_err)) {
        if (!empty($new_pass)) {
            $current_pass = $_POST['current_password'] ?? '';
            
            $stmt_pwd = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
            $stmt_pwd->bind_param("i", $customer_id);
            $stmt_pwd->execute();
            $curr_hash = $stmt_pwd->get_result()->fetch_assoc()['password'];
            $stmt_pwd->close();

            if (empty($current_pass)) {
                $update_err = "Please enter your Current Password to authorize password change.";
            } elseif (!password_verify($current_pass, $curr_hash)) {
                $update_err = "Incorrect Current Password.";
            } elseif (strlen($new_pass) < 12 || !preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[\W]/', $new_pass)) {
                $update_err = "New password must be at least 12 characters and include uppercase, number, and symbol.";
            } elseif ($new_pass !== $confirm_pass) { 
                $update_err = "New passwords do not match."; 
            }
        }
    }
        
    if (empty($update_err)) {
        $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE (email = ? OR username = ?) AND customer_id != ?");
        $check_stmt->bind_param("ssi", $new_email, $new_user, $customer_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $update_err = "The selected Username or Email is already taken by another account.";
        } else {
            $sql = "UPDATE customers SET username=?, email=?, phone_number=?, birthday=? " . (!empty($new_pass) ? ", password=?" : "") . " WHERE customer_id=?";
            $stmt = $conn->prepare($sql);
            
            if (!empty($new_pass)) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt->bind_param("sssssi", $new_user, $new_email, $new_phone, $new_birthday, $hashed, $customer_id);
            } else { 
                $stmt->bind_param("ssssi", $new_user, $new_email, $new_phone, $new_birthday, $customer_id); 
            }
            
            if ($stmt->execute()) { 
                $_SESSION['username'] = $new_user; 
                $update_msg = "Profile updated successfully."; 
            } else {
                $update_err = "Database error. Failed to update profile.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_address'])) {
    $open_acc = 'address'; 
    $recipient = htmlspecialchars(trim($_POST['recipient_name']));
    $line1 = htmlspecialchars(trim($_POST['address_line1']));
    $line2 = htmlspecialchars(trim($_POST['address_line2']));
    $city = htmlspecialchars(trim($_POST['city']));
    $state = htmlspecialchars(trim($_POST['state']));
    $postcode = trim($_POST['postcode']);
    
    $raw_addr_phone = trim($_POST['addr_phone']);
    $raw_addr_phone = preg_replace('/^\+?60/', '', $raw_addr_phone);
    $raw_addr_phone = ltrim($raw_addr_phone, '0');
    $phone = "+60" . $raw_addr_phone;
    
    if (!preg_match('/^\+60[0-9]{8,10}$/', $phone)) {
        $addr_err = "Delivery phone must be a valid Malaysian format (8 to 10 digits).";
    } elseif (!preg_match('/^[0-9]{5}$/', $postcode)) {
        $addr_err = "Postcode must be exactly 5 digits.";
    } else {
        $postcode = htmlspecialchars($postcode);
        $full_addr = $line1 . ($line2 ? ", " . $line2 : "") . ", " . $postcode . " " . $city . ", " . $state;
        
        $stmt = $conn->prepare("INSERT INTO customer_addresses (customer_id, recipient_name, phone_number, address_line1, address_line2, city, state, postcode, full_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $customer_id, $recipient, $phone, $line1, $line2, $city, $state, $postcode, $full_addr);
        if($stmt->execute()) { $addr_msg = "New address added."; }
        else { $addr_err = "Failed to add address."; }
        $stmt->close();
    }
}

if (isset($_GET['del_addr'])) {
    $open_acc = 'address';
    $addr_id = intval($_GET['del_addr']);
    $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE address_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $addr_id, $customer_id);
    $stmt->execute();
    header("Location: profile.php?tab=address"); exit();
}

if (isset($_GET['set_default'])) {
    $open_acc = 'address';
    $addr_id = intval($_GET['set_default']);
    
    $stmt_reset = $conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
    $stmt_reset->bind_param("i", $customer_id);
    $stmt_reset->execute();
    $stmt_reset->close();

    $stmt = $conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE address_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $addr_id, $customer_id);
    $stmt->execute();
    header("Location: profile.php?tab=address"); exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_card'])) {
    $open_acc = 'cards';
    $cardholder = htmlspecialchars(trim($_POST['cardholder_name']));
    $card_number = str_replace([' ', '-'], '', trim($_POST['card_number']));
    $expiry = htmlspecialchars(trim($_POST['expiry_date']));
    $cvc = htmlspecialchars(trim($_POST['cvc']));

    if (strlen($card_number) < 16) {
        $card_err = "Card number must be at least 16 digits.";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $expiry)) {
        $card_err = "Expiry date must be in MM/YY format (e.g., 12/25).";
    } else {
        $bank_stmt = $conn->prepare("SELECT id FROM bank WHERE card_number = ? AND cvc = ? AND expiry_date = ?");
        $bank_stmt->bind_param("sss", $card_number, $cvc, $expiry);
        $bank_stmt->execute();
        $bank_result = $bank_stmt->get_result();

        if ($bank_result->num_rows > 0) {
            $bank_account_id = $bank_result->fetch_assoc()['id'];
            $last_four = substr($card_number, -4);
            
            $card_brand = 'Credit Card'; 
            if (strpos($card_number, '4') === 0) {
                $card_brand = 'Visa';
            } elseif (strpos($card_number, '5') === 0 || strpos($card_number, '2') === 0) {
                $card_brand = 'Mastercard';
            } elseif (strpos($card_number, '3') === 0) {
                $card_brand = 'Amex';
            } elseif (strpos($card_number, '6') === 0) {
                $card_brand = 'UnionPay';
            }

            $stmt = $conn->prepare("INSERT INTO saved_cards (customer_id, bank_id, cardholder_name, last_four_digits, expiry_date, card_brand) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissss", $customer_id, $bank_account_id, $cardholder, $last_four, $expiry, $card_brand);
            
            if($stmt->execute()) { 
                $card_msg = "Card successfully verified by bank and secured."; 
            } else { 
                $card_err = "Failed to add card."; 
            }
            $stmt->close();
        } else {
            $card_err = "Bank validation failed! Incorrect Card Number, Expiry Date, or CVC.";
        }
        $bank_stmt->close();
    }
}

if (isset($_GET['del_card'])) {
    $open_acc = 'cards';
    $c_id = intval($_GET['del_card']);
    $conn->query("DELETE FROM saved_cards WHERE card_id = $c_id AND customer_id = $customer_id");
    header("Location: profile.php?tab=cards"); 
    exit();
}

if (isset($_GET['set_default_card'])) {
    $open_acc = 'cards';
    $c_id = intval($_GET['set_default_card']);
    $conn->query("UPDATE saved_cards SET is_default = 0 WHERE customer_id = $customer_id");
    $conn->query("UPDATE saved_cards SET is_default = 1 WHERE card_id = $c_id AND customer_id = $customer_id");
    header("Location: profile.php?tab=cards"); 
    exit();
}

if (isset($_GET['tab'])) { $open_acc = $_GET['tab']; }


$user = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
$addresses = $conn->query("SELECT * FROM customer_addresses WHERE customer_id = $customer_id ORDER BY is_default DESC, created_at DESC");
$saved_cards = $conn->query("SELECT * FROM saved_cards WHERE customer_id = $customer_id ORDER BY is_default DESC, created_at DESC");


$coins = isset($user['lifetime_coins']) ? intval($user['lifetime_coins']) : intval($user['reward_coins'] ?? 0);
$tier_status = $user['membership_tier'] ?? 'Standard';


$is_elite = ($tier_status === 'VIP' || $coins >= 1000);

if ($coins < 500) {
    $natural_tier = "Enthusiast";
    $target_coins = 500;
    $progress_pct = ($coins / 500) * 100;
    $natural_color = "#00f2fe"; 
    $next_color = "#a855f7"; 
    $next_tier_name = "Pro Builder";
} elseif ($coins < 1000) {
    $natural_tier = "Pro Builder";
    $target_coins = 1000;
    $progress_pct = ($coins / 1000) * 100; 
    $natural_color = "#a855f7"; 
    $next_color = "#ffd700"; 
    $next_tier_name = "Elite Architect";
} else {
    $natural_tier = "Elite Architect";
    $target_coins = "MAX"; 
    $progress_pct = 100;
    $natural_color = "#ffd700"; 
    $next_color = "#ffd700"; 
    $next_tier_name = "MAX LEVEL";
}

if ($tier_status === 'VIP') {
    $profile_display_tier = "Elite (VIP)";
    $icon = "fa-crown";
    $bar_color = "#ffd700"; 
    
    if ($coins < 1000) {
        $next_tier = "Permanent Elite";
        $target_coins = 1000; 
        $progress_pct = ($coins / 1000) * 100; 
        $next_color = "#ffd700"; 
        $benefits_text = "VIP Active! You enjoy Elite privileges. Reach {$target_coins} pts to lock in this status permanently.";
    } else {
        $next_tier = "MAX LEVEL";
        $target_coins = "MAX"; 
        $progress_pct = 100;
        $benefits_text = "Maximum prestige achieved! You are a Permanent Elite and VIP.";
    }
} else {
    $profile_display_tier = $natural_tier;
    $next_tier = $next_tier_name;
    $bar_color = $natural_color;
    $icon = ($coins >= 1000) ? "fa-crown" : (($coins >= 500) ? "fa-star" : "fa-user");
    
    if ($coins < 500) {
        $benefits_text = "Welcome! Earn reward coins through purchases to unlock Pro privileges and special badges.";
    } elseif ($coins < 1000) {
        $benefits_text = "You are a Pro member! Keep earning points to unlock Elite vouchers and maximum discounts.";
    } else {
        $benefits_text = "You have Permanent Elite status! Enjoy maximum community prestige.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; margin: 0; }
        .main-wrapper { flex: 1; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(168, 85, 247, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1200px; margin: 40px auto 80px; padding: 0 20px; position: relative; z-index: 1; }
        
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.65); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 16px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(0, 242, 254, 0.05);
            overflow: hidden;
        }
        
        .identity-banner { padding: 40px; margin-bottom: 30px; display: block; }
        .banner-top-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
        .user-info-large h1 { font-size: 2.2rem; font-weight: 900; margin: 5px 0 0 0; letter-spacing: -1px; }
        .user-info-large p { color: #00f2fe; margin: 0; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: bold; text-transform: uppercase;}
        .balance-badge { display: flex; gap: 30px; text-align: right; }
        .bal-item h4 { font-family: 'JetBrains Mono', monospace; font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;}
        .bal-item.credits h4 { color: #00f2fe; text-shadow: 0 0 20px rgba(0,242,254,0.4); }
        .bal-item.coins h4 { color: #ffd700; text-shadow: 0 0 20px rgba(255,215,0,0.4); }
        .bal-item span { font-size: 0.75rem; color: #64748b; font-weight: 800; letter-spacing: 1px; }

        .progress-section { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 25px; box-shadow: inset 0 0 20px rgba(0,0,0,0.5); }
        .progress-track { width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; position: relative; margin: 15px 0;}
        .progress-fill { height: 100%; border-radius: 4px; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
        @media(max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } .banner-top-row { flex-direction: column; align-items: flex-start; gap: 20px; } .balance-badge { text-align: left; } }
        
        .accordion-item { background: rgba(0,0,0,0.4); border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; margin-bottom: 20px; overflow: hidden; transition: 0.3s; }
        .accordion-item:hover { border-color: rgba(0, 242, 254, 0.4); box-shadow: 0 0 15px rgba(0, 242, 254, 0.1); }
        .accordion-header { padding: 20px 25px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: #00f2fe; font-weight: 800; font-size: 1.1rem; background: rgba(0,242,254,0.05); user-select: none; }
        .accordion-header i.chevron { transition: transform 0.4s ease; }
        .accordion-content { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease; padding: 0 25px; background: rgba(10,10,15,0.6); }
        
        .accordion-item.active { border-color: #00f2fe; box-shadow: 0 0 20px rgba(0, 242, 254, 0.15); }
        .accordion-item.active .accordion-header { background: rgba(0,242,254,0.1); }
        .accordion-item.active .accordion-header i.chevron { transform: rotate(180deg); color: #fff;}
        .accordion-item.active .accordion-content { max-height: 2500px; opacity: 1; padding: 25px; border-top: 1px solid rgba(0, 242, 254, 0.2); }

        .tech-input-group { margin-bottom: 20px; position: relative; }
        .tech-label { color: #94a3b8; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; }
        .tech-input { width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 14px 16px; border-radius: 8px; font-size: 0.95rem; transition: 0.3s; font-family: 'Inter', sans-serif; box-sizing: border-box;}
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .tech-btn { background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 12px 20px; border-radius: 8px; cursor: pointer; transition: 0.3s; display: inline-block; text-align: center; text-decoration: none; box-sizing: border-box; }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px); }

        .phone-input-group { display: flex; align-items: center; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; transition: 0.3s; overflow: hidden;}
        .phone-input-group:focus-within { border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .phone-prefix { padding: 14px 15px; background: rgba(255,255,255,0.05); color: #00f2fe; font-weight: bold; border-right: 1px solid rgba(255, 255, 255, 0.1); font-family: 'JetBrains Mono', monospace; }
        .phone-input-group .tech-input { border: none; background: transparent; box-shadow: none; border-radius: 0; flex: 1; padding: 14px 16px;}

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; opacity: 0.6; transition: 0.3s; }
        input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }

        .pwd-checklist { list-style: none; padding: 0; margin: 10px 0 0 0; font-size: 0.75rem; color: #64748b; font-family: 'JetBrains Mono', monospace; display: none; }
        .pwd-checklist li { margin-bottom: 5px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .pwd-checklist li.valid { color: #00e676; }

        .address-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        @media(max-width: 600px) { .address-grid { grid-template-columns: 1fr; } }
        .addr-card { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 12px; position: relative; }
        .addr-card.is-default { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); }
        .badge-default { position: absolute; top: 15px; right: 15px; background: #00f2fe; color: #000; font-size: 0.65rem; font-weight: bold; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }

        .side-panel { display: flex; flex-direction: column; gap: 25px; }
        .blueprints-scroll-container { max-height: 550px; overflow-y: auto; overflow-x: hidden; padding-right: 10px; display: flex; flex-direction: column; gap: 15px; }
        .blueprints-scroll-container::-webkit-scrollbar { width: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb { background: rgba(0, 242, 254, 0.3); border-radius: 4px; transition: 0.3s; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb:hover { background: #00f2fe; box-shadow: 0 0 10px #00f2fe; }

        .blueprint-card { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 20px; transition: all 0.3s; overflow: hidden; flex-shrink: 0; }
        .blueprint-card:hover { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: inset 0 0 20px rgba(0,242,254,0.05); }
        .bp-title { font-weight: 900; font-size: 1.05rem; margin: 0 0 5px 0; color: #fff;}
        .bp-price { font-family: 'JetBrains Mono', monospace; color: #00e676; font-size: 1.1rem; font-weight: 800; }
        
        .bp-details { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s; margin-top: 0; padding-top: 0; border-top: 1px dashed transparent; }
        .blueprint-card:hover .bp-details { max-height: 400px; opacity: 1; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0, 242, 254, 0.3); }
        .bp-part-item { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 8px; }
        .bp-part-cat { color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: bold; }
        .bp-part-name { color: #cbd5e1; text-align: right; width: 65%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* 🌟 极致美学的操作按钮 */
        .action-link { font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #cbd5e1; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 6px; transition: 0.3s; text-align: center; cursor: pointer; flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;}
        .btn-export-pdf { background: rgba(255, 0, 127, 0.05); color: #ff007f; border-color: rgba(255, 0, 127, 0.3); }
        .btn-export-pdf:hover { background: #ff007f; color: #fff; box-shadow: 0 0 15px rgba(255, 0, 127, 0.4); border-color: #ff007f; transform: translateY(-2px);}
        .btn-load { background: rgba(0, 242, 254, 0.05); color: #00f2fe; border-color: rgba(0, 242, 254, 0.3); }
        .btn-load:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4); border-color: #00f2fe; transform: translateY(-2px);}
        .btn-delete:hover { background: #ff4d4d; color: #fff; border-color: #ff4d4d; transform: translateY(-2px);}
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<div class="main-wrapper">
    <div class="dashboard-container">
        
        <div class="tech-auth-card identity-banner">
            <div class="banner-top-row">
                <div class="user-info-large">
                    <p><i class="fas fa-user-circle"></i> ACCOUNT OVERVIEW</p>
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <div style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="balance-badge">
                    <div class="bal-item coins">
                        <span>REWARD COINS</span>
                        <h4><?php echo number_format($user['reward_coins']); ?></h4>
                    </div>
                    <div class="bal-item credits">
                        <span>WALLET BALANCE</span>
                        <h4>RM <?php echo number_format($user['wallet_balance'], 2); ?></h4>
                    </div>
                </div>
            </div>

            <div class="progress-section">
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Current Rank</div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: <?php echo $bar_color; ?>; display: flex; align-items: center; gap: 10px; letter-spacing: -0.5px;">
    <i class="fas <?php echo $icon; ?>"></i> <?php echo $profile_display_tier; ?> 
</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Next Target: <span style="color: <?php echo $next_color; ?>;"><?php echo $next_tier; ?></span></div>
                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; color: #fff; font-weight: bold;">
                        <?php echo $coins; ?> <span style="color: #64748b;"><?php echo ($target_coins === 'MAX') ? ' PTS (MAX TIER)' : ' / ' . $target_coins . ' PTS'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?php echo $progress_pct; ?>%; background: <?php echo $bar_color; ?>; box-shadow: 0 0 15px <?php echo $bar_color; ?>;"></div>
                </div>
                
                <div style="font-size: 0.85rem; color: #cbd5e1; margin-top: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: <?php echo $bar_color; ?>;"></i> 
                    <span><?php echo $benefits_text; ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="main-column">
                
                <div class="accordion-item <?php echo $open_acc == 'account' ? 'active' : ''; ?>" id="acc-account">
                    <div class="accordion-header" onclick="toggleAccordion('acc-account')">
                        <span><i class="fas fa-user-shield" style="margin-right: 10px;"></i> Account Settings</span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                    <div class="accordion-content">
                        <?php if($update_msg) echo "<div style='font-size: 0.85rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 15px; border: 1px solid rgba(0,230,118,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-check-circle'></i> $update_msg</div>"; ?>
                        <?php if($update_err) echo "<div style='font-size: 0.85rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 15px; border: 1px solid rgba(255,77,77,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-exclamation-triangle'></i> $update_err</div>"; ?>

                        <form method="POST">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="tech-input-group">
                                    <label class="tech-label">Username</label>
                                    <input type="text" name="username" class="tech-input" value="<?php echo htmlspecialchars($user['username']); ?>" pattern="[a-zA-Z0-9_]{3,20}" title="3-20 letters, numbers, or underscores" required>
                                </div>
                                <div class="tech-input-group">
                                    <label class="tech-label">Email Address</label>
                                    <input type="email" name="email" class="tech-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="tech-input-group">
                                    <label class="tech-label">Phone Number</label>
                                    <div class="phone-input-group">
                                        <span class="phone-prefix">+60</span>
                                        <?php 
                                            $display_phone = preg_replace('/^\+?60/', '', $user['phone_number'] ?? ''); 
                                            $display_phone = ltrim($display_phone, '0');
                                        ?>
                                        <input type="tel" name="phone_number" class="tech-input" value="<?php echo htmlspecialchars($display_phone); ?>" pattern="[0-9]{8,10}" maxlength="10" title="Enter 8 to 10 digits (e.g., 123456789)" placeholder="123456789">
                                    </div>
                                </div>
                                <div class="tech-input-group">
                                    <label class="tech-label">Date of Birth</label>
                                    <?php 
                                        $max_bday = date('Y-m-d', strtotime('-13 years')); 
                                        $min_bday = '1900-01-01'; 
                                    ?>
                                    <input type="date" name="birthday" class="tech-input" value="<?php echo htmlspecialchars($user['birthday']); ?>" min="<?php echo $min_bday; ?>" max="<?php echo $max_bday; ?>">
                                </div>
                            </div>
                            
                            <h4 style="color: #fff; margin-top: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Change Password</h4>
                            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0;">Leave blank to keep your current password.</p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                                <div class="tech-input-group" style="grid-column: span 2;">
                                    <label class="tech-label" style="color: #facc15;"><i class="fas fa-lock"></i> Current Password (Required to save changes)</label>
                                    <input type="password" name="current_password" class="tech-input" placeholder="Enter your current password" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono'; border-color: rgba(250, 204, 21, 0.4);">
                                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                                </div>

                                <div class="tech-input-group">
                                    <label class="tech-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="tech-input" placeholder="Min 12 chars" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono';">
                                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                                    
                                    <ul class="pwd-checklist" id="pwd-checklist">
                                        <li id="req-len"><i class="fas fa-times-circle"></i> 12+ characters</li>
                                        <li id="req-up"><i class="fas fa-times-circle"></i> 1 Uppercase</li>
                                        <li id="req-num"><i class="fas fa-times-circle"></i> 1 Number</li>
                                        <li id="req-sym"><i class="fas fa-times-circle"></i> 1 Symbol</li>
                                    </ul>
                                </div>
                                <div class="tech-input-group">
                                    <label class="tech-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="tech-input" placeholder="Verify match" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono';">
                                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="tech-btn" style="width: 100%; margin-top: 10px; padding: 15px; font-size: 1rem;"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>

       
                <div class="accordion-item <?php echo $open_acc == 'vouchers' ? 'active' : ''; ?>" id="acc-vouchers">
                    <div class="accordion-header" onclick="toggleAccordion('acc-vouchers')">
                        <span><i class="fas fa-crown" style="margin-right: 10px; color: #ffd700;"></i> Membership & Vouchers</span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                    <div class="accordion-content">
                        <?php if ($is_elite): ?>
                            <div style="background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(10,10,15,0.9) 100%); border: 1px solid rgba(255,215,0,0.4); padding: 30px; border-radius: 12px; position: relative; overflow: hidden;">
                                <h4 style="color: #ffd700; margin: 0 0 12px 0; font-size: 1.5rem; font-weight: 900;"><i class="fa-solid fa-circle-check"></i> Elite Status Active</h4>
                                <p style="font-size: 0.95rem; color: #e2e8f0; margin: 0 0 8px 0;">Your Elite status is active. Enjoy premium discounts and exclusive vouchers.</p>
                                
                                <?php if ($tier_status === 'VIP'): ?>
                                    <p style="font-size: 0.85rem; color: #94a3b8; font-family: 'JetBrains Mono'; margin: 0 0 25px 0;">Valid until: <span style="color: #fff; font-weight: bold;"><?php echo date('d M Y', strtotime($user['vip_expiry_date'])); ?></span></p>
                                <?php else: ?>
                                    <p style="font-size: 0.85rem; color: #94a3b8; font-family: 'JetBrains Mono'; margin: 0 0 25px 0;">Valid until: <span style="color: #00e676; font-weight: bold;">LIFETIME (Permanent)</span></p>
                                <?php endif; ?>

                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <a href="vouchers.php" class="tech-btn" style="background: #ffd700; color: #000; border: none; box-shadow: 0 4px 15px rgba(255,215,0,0.3);"><i class="fa-solid fa-ticket"></i> View My Vouchers</a>
                                    <a href="membership.php" class="tech-btn" style="border: 1px solid rgba(255,215,0,0.5); color: #ffd700;">Manage Subscription</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(0, 242, 254, 0.5); padding: 30px; border-radius: 12px;">
                                <h4 style="color: #fff; margin: 0 0 12px 0; font-size: 1.3rem; font-weight: 800;">Standard Membership</h4>
                                <p style="font-size: 0.95rem; color: #94a3b8; margin: 0 0 25px 0; line-height: 1.6;">You currently have access to public vouchers. Upgrade to <strong style="color:#ffd700;">Elite</strong> to unlock 25% OFF codes & 500 immediate Coins.</p>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <a href="membership.php" class="tech-btn" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; box-shadow: 0 4px 15px rgba(0,242,254,0.4);"><i class="fa-solid fa-bolt"></i> Upgrade to Elite</a>
                                    <a href="vouchers.php" class="tech-btn" style="color: #cbd5e1; border-color: rgba(255,255,255,0.2);">Browse Vouchers</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="accordion-item <?php echo $open_acc == 'address' ? 'active' : ''; ?>" id="acc-address">
                    <div class="accordion-header" onclick="toggleAccordion('acc-address')">
                        <span><i class="fas fa-location-crosshairs" style="margin-right: 10px;"></i> Shipping Addresses</span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                    <div class="accordion-content">
                        
                        <?php if($addr_msg) echo "<div style='font-size: 0.85rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 15px; border: 1px solid rgba(0,230,118,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-check-circle'></i> $addr_msg</div>"; ?>
                        <?php if($addr_err) echo "<div style='font-size: 0.85rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 15px; border: 1px solid rgba(255,77,77,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-exclamation-triangle'></i> $addr_err</div>"; ?>

                        <button onclick="document.getElementById('add-addr-form').style.display='block'" class="tech-btn" style="width: auto; padding: 12px 20px; font-size: 0.85rem; margin-bottom: 20px;"><i class="fas fa-plus"></i> Add New Address</button>

                        <div id="add-addr-form" style="display: none; background: rgba(0,0,0,0.4); border: 1px dashed rgba(0,242,254,0.3); padding: 30px; border-radius: 12px; margin-bottom: 25px;">
                            <form method="POST" action="profile.php">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">Recipient Name</label><input type="text" name="recipient_name" class="tech-input" required></div>
                                    <div class="tech-input-group" style="margin-bottom:0;">
                                        <label class="tech-label">Phone Number</label>
                                        <div class="phone-input-group">
                                            <span class="phone-prefix">+60</span>
                                            <input type="tel" name="addr_phone" class="tech-input" pattern="[0-9]{8,10}" maxlength="10" placeholder="123456789" required>
                                        </div>
                                    </div>
                                    <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;"><label class="tech-label">Address Line 1</label><input type="text" name="address_line1" class="tech-input" required></div>
                                    <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;"><label class="tech-label">Address Line 2 (Optional)</label><input type="text" name="address_line2" class="tech-input"></div>
                                    <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">City</label><input type="text" name="city" class="tech-input" required></div>
                                    <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">State</label><input type="text" name="state" class="tech-input" required></div>
                                    <div class="tech-input-group" style="margin-bottom:0; grid-column: span 2;"><label class="tech-label">Postcode</label><input type="text" name="postcode" class="tech-input" pattern="[0-9]{5}" maxlength="5" required></div>
                                </div>
                                <div style="margin-top: 25px; display: flex; gap: 15px;">
                                    <button type="submit" name="add_address" class="tech-btn" style="width: auto; padding: 12px 30px;">Save Address</button>
                                    <button type="button" onclick="document.getElementById('add-addr-form').style.display='none'" class="action-link" style="border:none; flex: none; width: auto; padding: 12px 20px;">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div class="address-grid">
                            <?php if($addresses->num_rows > 0): while($addr = $addresses->fetch_assoc()): ?>
                                <div class="addr-card <?php echo $addr['is_default'] ? 'is-default' : ''; ?>">
                                    <?php if($addr['is_default']) echo '<span class="badge-default">PRIMARY</span>'; ?>
                                    
                                    <h4 style="margin: 0 0 8px 0; color: #fff; font-size: 1rem; font-weight: 800;"><?php echo htmlspecialchars($addr['recipient_name'] ?: $user['username']); ?></h4>
                                    <div style="color: #00f2fe; font-family: 'JetBrains Mono'; font-size: 0.85rem; margin-bottom: 15px; font-weight: bold;">
                                        <i class="fas fa-phone" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($addr['phone_number'] ?: 'N/A'); ?>
                                    </div>
                                    
                                    <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin: 0 0 25px 0; min-height: 65px;">
                                        <?php 
                                            if (!empty($addr['address_line1'])) {
                                                echo htmlspecialchars($addr['address_line1']) . "<br>";
                                                echo htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']) . ", " . htmlspecialchars($addr['state']);
                                            } else {
                                                echo nl2br(htmlspecialchars($addr['full_address'])); 
                                            }
                                        ?>
                                    </p>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <?php if(!$addr['is_default']): ?>
                                            <a href="profile.php?set_default=<?php echo $addr['address_id']; ?>" class="action-link">Set Primary</a>
                                        <?php endif; ?>
                                        <a href="profile.php?del_addr=<?php echo $addr['address_id']; ?>" onclick="return confirm('Delete this address?')" class="action-link btn-delete" style="color: #ff4d4d; border-color: rgba(255,77,77,0.2); flex: none; width: 40px;"><i class="fas fa-trash"></i></a>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <p style="grid-column: span 2; color: #64748b; font-style: italic; font-size: 0.9rem; text-align: center; padding: 20px;">You haven't saved any addresses yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="accordion-item <?php echo $open_acc == 'cards' ? 'active' : ''; ?>" id="acc-cards">
                    <div class="accordion-header" onclick="toggleAccordion('acc-cards')">
                        <span><i class="fas fa-credit-card" style="margin-right: 10px;"></i> Payment Methods</span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                    <div class="accordion-content">
                        
                       <?php if($card_msg) echo "<div class='dynamic-alert' style='font-size: 0.85rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 15px; border: 1px solid rgba(0,230,118,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-check-circle'></i> $card_msg</div>"; ?>
                        <?php if($card_err) echo "<div class='dynamic-alert' style='font-size: 0.85rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 15px; border: 1px solid rgba(255,77,77,0.3); border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='fas fa-exclamation-triangle'></i> $card_err</div>"; ?>

                        <button onclick="document.getElementById('add-card-form').style.display='block'" class="tech-btn" style="width: auto; padding: 12px 20px; font-size: 0.85rem; margin-bottom: 20px;"><i class="fas fa-plus"></i> Add New Card</button>

                        <div id="add-card-form" style="display: none; background: rgba(0,0,0,0.4); border: 1px dashed rgba(0,242,254,0.3); padding: 30px; border-radius: 12px; margin-bottom: 25px;">
                            <form method="POST" action="profile.php">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;">
                                        <label class="tech-label">Cardholder Name</label>
                                        <input type="text" name="cardholder_name" class="tech-input" placeholder="e.g. JOHN DOE" required>
                                    </div>
                                    <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;">
                                        <label class="tech-label">Card Number</label>
                                        <input type="text" name="card_number" class="tech-input" placeholder="0000 0000 0000 0000" pattern="[0-9 ]{16,19}" title="Enter 16 digit card number" required>
                                    </div>
                                    <div class="tech-input-group" style="margin-bottom:0;">
                                        <label class="tech-label">Expiry Date</label>
                                        <input type="text" name="expiry_date" class="tech-input" placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/?([0-9]{2})" title="Format: MM/YY" required>
                                    </div>
                                    <div class="tech-input-group" style="margin-bottom:0;">
                                        <label class="tech-label">CVV / CVC</label>
                                        <input type="password" name="cvc" class="tech-input" placeholder="123" pattern="[0-9]{3,4}" required>
                                    </div>
                                </div>
                                <div style="margin-top: 25px; display: flex; gap: 15px;">
                                    <button type="submit" name="add_card" class="tech-btn" style="width: auto; padding: 12px 30px;">Save Card</button>
                                    <button type="button" onclick="document.getElementById('add-card-form').style.display='none'" class="action-link btn-delete" style="color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); background: rgba(255,77,77,0.05); flex: none; width: auto; padding: 12px 20px;">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div class="address-grid"> 
                            <?php if($saved_cards->num_rows > 0): while($card = $saved_cards->fetch_assoc()): ?>
                                
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    
                                    <div class="addr-card <?php echo $card['is_default'] ? 'is-default' : ''; ?>" style="padding: 25px; border-radius: 16px; background: linear-gradient(135deg, rgba(20,20,30,0.8) 0%, rgba(5,5,10,0.9) 100%); position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.4); display: flex; flex-direction: column; justify-content: space-between; min-height: 180px;">
                                        
                                        <?php if($card['is_default']): ?>
                                            <div style="position: absolute; top: -50px; right: -50px; width: 120px; height: 120px; background: #00f2fe; filter: blur(70px); opacity: 0.25; pointer-events: none;"></div>
                                        <?php endif; ?>

                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; z-index: 1;">
                                            <?php 
                                            $brand_class = 'fa-solid fa-credit-card'; 
                                            $brand_lower = strtolower($card['card_brand']);
                                            if ($brand_lower == 'visa') $brand_class = 'fa-brands fa-cc-visa';
                                            elseif ($brand_lower == 'mastercard') $brand_class = 'fa-brands fa-cc-mastercard';
                                            elseif ($brand_lower == 'amex') $brand_class = 'fa-brands fa-cc-amex';
                                            ?>
                                            <i class="<?php echo $brand_class; ?>" style="font-size: 2.5rem; color: #00f2fe;"></i>
                                            
                                            <?php if($card['is_default']): ?>
                                                <span style="background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid rgba(0, 242, 254, 0.3); font-size: 0.65rem; font-weight: 800; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;">Primary</span>
                                            <?php endif; ?>
                                        </div>

                                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; color: #fff; letter-spacing: 4px; display: flex; gap: 15px; z-index: 1; text-shadow: 0 2px 10px rgba(0,0,0,0.5); margin-top: 15px; margin-bottom: 10px;">
                                            <span>••••</span>
                                            <span>••••</span>
                                            <span>••••</span>
                                            <span><?php echo htmlspecialchars($card['last_four_digits']); ?></span>
                                        </div>

                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; z-index: 1;">
                                            <div>
                                                <div style="color: #64748b; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Cardholder Name</div>
                                                <div style="color: #cbd5e1; font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;"><?php echo htmlspecialchars($card['cardholder_name']); ?></div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="color: #64748b; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Valid Thru</div>
                                                <div style="color: #cbd5e1; font-size: 1rem; font-weight: 700; font-family: 'JetBrains Mono';"><?php echo htmlspecialchars($card['expiry_date']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 10px;">
                                        <?php if(!$card['is_default']): ?>
                                            <a href="profile.php?set_default_card=<?php echo $card['card_id']; ?>" class="action-link" style="font-size: 0.75rem; padding: 8px;"><i class="fas fa-check-circle"></i> Set Primary</a>
                                        <?php endif; ?>
                                        <a href="profile.php?del_card=<?php echo $card['card_id']; ?>" onclick="return confirm('Remove this payment method?')" class="action-link btn-delete" style="color: #ff4d4d; border-color: rgba(255,77,77,0.2); flex: <?php echo $card['is_default'] ? '1' : 'none'; ?>; width: <?php echo $card['is_default'] ? '100%' : '40px'; ?>; padding: 8px;"><i class="fas fa-trash"></i> <?php echo $card['is_default'] ? 'Remove Card' : ''; ?></a>
                                    </div>

                                </div>

                            <?php endwhile; else: ?>
                                <p style="grid-column: span 2; color: #64748b; font-style: italic; font-size: 0.9rem; text-align: center; padding: 20px;">You haven't saved any payment methods yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="side-panel">
                
                <div class="tech-auth-card" style="padding: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
                        <h3 style="margin:0; font-weight: 900; font-size: 1.2rem; color: #fff;"><i class="fas fa-microchip" style="color: #00f2fe; margin-right: 8px;"></i> Saved Blueprints</h3>
                        <a href="builder.php" style="color: #00f2fe; font-size: 0.85rem; text-decoration: none; font-weight: bold;"><i class="fas fa-plus"></i> NEW</a>
                    </div>
                    
                    <div class="blueprints-scroll-container">
                        <?php
                        $builds = $conn->query("SELECT * FROM saved_builds WHERE customer_id = $customer_id ORDER BY created_at DESC");
                        if($builds->num_rows > 0): 
                            while($b = $builds->fetch_assoc()): 
                                $current_build_id = $b['pc_build'];
                        ?>
                            <div class="blueprint-card">
                                <h4 class="bp-title"><?php echo htmlspecialchars($b['build_name']); ?></h4>
                                <span class="bp-price">RM <?php echo number_format($b['total_price'], 2); ?></span>
                                
                                <div class="bp-details">
                                    <?php
                                    $items_sql = "SELECT c.category_name, p.product_name 
                                                  FROM build_items bi JOIN products p ON bi.product_id = p.product_id 
                                                  JOIN categories c ON p.category_id = c.category_id WHERE bi.pc_build = $current_build_id";
                                    $items_res = $conn->query($items_sql);
                                    if ($items_res->num_rows > 0) {
                                        while ($item = $items_res->fetch_assoc()) {
                                            echo '<div class="bp-part-item">';
                                            echo '<span class="bp-part-cat">' . htmlspecialchars($item['category_name']) . '</span>';
                                            echo '<span class="bp-part-name" title="' . htmlspecialchars($item['product_name']) . '">' . htmlspecialchars($item['product_name']) . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>

                                <div style="margin-top: 20px; display: flex; gap: 10px;">
                                    <a href="load_build.php?id=<?php echo $b['pc_build']; ?>" class="action-link btn-load"><i class="fas fa-wrench"></i> Load Into Builder</a>
                                    <a href="delete_build.php?id=<?php echo $b['pc_build']; ?>" onclick="return confirm('Delete this blueprint?')" class="action-link btn-delete" style="color: #ff4d4d; border-color: rgba(255,77,77,0.3); flex: none; width: 40px;"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                            <div style="text-align: center; padding: 40px 20px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px;">
                                <i class="fas fa-folder-open" style="font-size: 2rem; color: #475569; margin-bottom: 15px;"></i>
                                <p style="color: #64748b; font-size: 0.85rem; font-family: 'Inter', sans-serif; margin: 0;">You haven't saved any PC builds yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tech-auth-card" style="padding: 25px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: 0.3s;" onclick="window.location.href='my_orders.php'" onmouseover="this.style.borderColor='#00f2fe'; this.style.boxShadow='0 0 20px rgba(0,242,254,0.2)';" onmouseout="this.style.borderColor='rgba(0, 242, 254, 0.15)'; this.style.boxShadow='none';">
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; font-weight: 800; color: #fff;"><i class="fas fa-box-open" style="color: #00f2fe; margin-right: 8px;"></i> My Orders</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Track your purchases</p>
                    </div>
                    <i class="fas fa-arrow-right-long" style="color: #00f2fe; font-size: 1.2rem;"></i>
                </div>
                
                <a href="logout.php" class="tech-btn" style="color: #ff4d4d; border-color: rgba(255,77,77,0.3); width: 100%;"><i class="fas fa-power-off"></i> Log Out</a>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function toggleAccordion(id) {
    const items = document.querySelectorAll('.accordion-item');
    items.forEach(item => {
        if(item.id === id) {
            item.classList.toggle('active');
        } else {
            item.classList.remove('active');
        }
    });
}

document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            this.classList.replace('fa-eye', 'fa-eye-slash');
            this.style.color = '#00f2fe';
        } else {
            input.type = 'password';
            this.classList.replace('fa-eye-slash', 'fa-eye');
            this.style.color = '#64748b';
        }
    });
});

document.getElementById('new_password').addEventListener('input', function() {
    const val = this.value;
    const checklist = document.getElementById('pwd-checklist');
    
    if(val.length > 0) { checklist.style.display = 'block'; } 
    else { checklist.style.display = 'none'; return; }

    const reqLen = document.getElementById('req-len');
    const reqUp = document.getElementById('req-up');
    const reqNum = document.getElementById('req-num');
    const reqSym = document.getElementById('req-sym');

    const tick = '<i class="fas fa-check-circle"></i> ';
    const cross = '<i class="fas fa-times-circle"></i> ';

    if(val.length >= 12) { reqLen.className = 'valid'; reqLen.innerHTML = tick + '12+ characters'; }
    else { reqLen.className = ''; reqLen.innerHTML = cross + '12+ characters'; }

    if(/[A-Z]/.test(val)) { reqUp.className = 'valid'; reqUp.innerHTML = tick + '1 Uppercase'; }
    else { reqUp.className = ''; reqUp.innerHTML = cross + '1 Uppercase'; }

    if(/[0-9]/.test(val)) { reqNum.className = 'valid'; reqNum.innerHTML = tick + '1 Number'; }
    else { reqNum.className = ''; reqNum.innerHTML = cross + '1 Number'; }

    if(/[\W]/.test(val)) { reqSym.className = 'valid'; reqSym.innerHTML = tick + '1 Symbol'; }
    else { reqSym.className = ''; reqSym.innerHTML = cross + '1 Symbol'; }
});


document.addEventListener('DOMContentLoaded', function() {
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    const expiryInput = document.querySelector('input[name="expiry_date"]');
    const cvcInput = document.querySelector('input[name="cvc"]');

   
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); 
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formattedValue += ' ';
                formattedValue += value[i];
            }
  
            e.target.value = formattedValue.substring(0, 19);
        });
    }


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
          
            e.target.value = value.substring(0, 5);
        });
    }


    if (cvcInput) {
        cvcInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
    }
});
</script>

</body>
</html>