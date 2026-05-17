<?php
session_start();
require_once 'config.php';

// 1. 檢查登入狀態
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_msg = "";

// ==========================================
// 2. 處理新增卡片請求 (POST Request)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🌟 A+ 级安全修复：防御持卡人姓名的 XSS 注入
    $cardholder_name = htmlspecialchars(trim($_POST['cardholder_name']));
    $expiry_date = htmlspecialchars(trim($_POST['expiry_date']));
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // 取得卡號並移除所有空白
    $raw_card_number = str_replace(' ', '', $_POST['card_number']);
    
    // 🌟 資安防禦機制：絕對不存完整卡號，只存最後四碼！
    $last_four_digits = substr($raw_card_number, -4);
    
    // 🌟 智能辨識：透過卡號第一個數字判斷發卡組織
    $card_brand = 'Unknown';
    $first_digit = substr($raw_card_number, 0, 1);
    if ($first_digit === '4') {
        $card_brand = 'Visa';
    } elseif ($first_digit === '5') {
        $card_brand = 'Mastercard';
    } elseif ($first_digit === '3') {
        $card_brand = 'American Express';
    } else {
        $card_brand = 'Credit Card'; // 預設值
    }

    if (strlen($raw_card_number) >= 15 && strlen($last_four_digits) === 4) {
        
        $conn->begin_transaction();

        try {
            // 邏輯 A：檢查是否為該顧客的第一張卡
            $check_first = $conn->query("SELECT COUNT(*) as count FROM saved_cards WHERE customer_id = $customer_id");
            $row = $check_first->fetch_assoc();
            if ($row['count'] == 0) {
                $is_default = 1; // 第一張卡強制設為預設
            }

            // 邏輯 B：如果勾選了設為預設，先把其他卡片降級
            if ($is_default == 1) {
                $remove_default = "UPDATE saved_cards SET is_default = 0 WHERE customer_id = ?";
                $stmt_remove = $conn->prepare($remove_default);
                $stmt_remove->bind_param("i", $customer_id);
                $stmt_remove->execute();
                $stmt_remove->close();
            }

            // 邏輯 C：寫入資料庫 (注意：我們沒有把 CVV 傳給後端，這非常安全！)
            $insert_query = "INSERT INTO saved_cards (customer_id, cardholder_name, last_four_digits, expiry_date, card_brand, is_default) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bind_param("issssi", $customer_id, $cardholder_name, $last_four_digits, $expiry_date, $card_brand, $is_default);
            $stmt_insert->execute();
            $stmt_insert->close();

            $conn->commit();
            $_SESSION['success_msg'] = "Card added securely!";
            header("Location: profile.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error securely saving card. Please try again.";
        }
    } else {
        $error_msg = "Invalid card number format.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Add New Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-container" style="max-width: 500px;">
            
            <div class="auth-title">
                <h2><i class="fa-solid fa-shield-halved"></i> Add Payment Method</h2>
                <p class="specs">Your card details are protected by 256-bit encryption.</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <p class="text-danger" style="margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></p>
            <?php endif; ?>

            <form action="add_card.php" method="POST" class="form">
                
                <div class="form-group input-group">
                    <label class="form-label" for="cardholder_name">Name on Card</label>
                    <input type="text" id="cardholder_name" name="cardholder_name" class="form-control" required placeholder="e.g. GAN SHENG WING" style="text-transform: uppercase;">
                </div>

                <div class="form-group input-group">
                    <label class="form-label" for="card_number">Card Number</label>
                    <div style="position: relative;">
                        <i id="card-icon" class="fa-solid fa-credit-card" style="position: absolute; right: 15px; top: 15px; color: var(--text-muted); font-size: 1.2rem;"></i>
                        <input type="text" id="card_number" name="card_number" class="form-control" required placeholder="0000 0000 0000 0000" maxlength="19">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="expiry_date">Expiry Date</label>
                        <input type="text" id="expiry_date" name="expiry_date" class="form-control" required placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="cvv">CVV</label>
                        <input type="password" id="cvv" class="form-control" required placeholder="***" maxlength="4">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" name="is_default" value="1" style="margin-right: 10px; width: 16px; height: 16px;">
                        Set as Default Payment Method
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%;">
                    <i class="fa-solid fa-lock"></i> Save Card Securely
                </button>
            </form>

            <div class="specs" style="margin-top: 1.5rem; text-align: center;">
                <a href="profile.php" class="highlight-link"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            </div>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardInput = document.getElementById('card_number');
            const expiryInput = document.getElementById('expiry_date');
            const cardIcon = document.getElementById('card-icon');

            // 自動加上空白與辨識卡片品牌
            cardInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // 移除非數字
                
                // 判斷品牌並更換 Icon
                if (value.startsWith('4')) {
                    cardIcon.className = 'fa-brands fa-cc-visa';
                    cardIcon.style.color = '#1a1f71';
                } else if (value.startsWith('5')) {
                    cardIcon.className = 'fa-brands fa-cc-mastercard';
                    cardIcon.style.color = '#eb001b';
                } else {
                    cardIcon.className = 'fa-solid fa-credit-card';
                    cardIcon.style.color = 'var(--text-muted)';
                }

                // 每 4 個數字加一個空白
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formattedValue += ' ';
                    formattedValue += value[i];
                }
                e.target.value = formattedValue;
            });

            // 自動幫 MM/YY 加上斜線
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        });
    </script>
</body>
</html>