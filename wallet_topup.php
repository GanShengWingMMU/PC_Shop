<?php
session_start();
require_once 'config.php';

// 1. 檢查登入狀態
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$success_msg = "";
$error_msg = "";

// ==========================================
// 2. 處理儲值請求 (POST Request)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = 0;

    // 🌟 核心修改：判斷顧客是填寫「自定義金額」還是點擊「預設選項」
    if (!empty($_POST['custom_amount']) && is_numeric($_POST['custom_amount'])) {
        $amount = (float) $_POST['custom_amount'];
    } elseif (!empty($_POST['topup_option']) && is_numeric($_POST['topup_option'])) {
        $amount = (float) $_POST['topup_option'];
    }

    // 安全檢查：確保儲值金額大於等於 RM 10 (設定一個最低門檻比較真實)
    if ($amount >= 10) {
        // 計算回饋金幣 (商業邏輯：每儲值 RM 10 送 1 個金幣)
        $coins_earned = floor($amount / 10);
        
        $conn->begin_transaction();

        try {
            // A. 更新顧客的錢包餘額與金幣
            $update_sql = "UPDATE customers SET wallet_balance = wallet_balance + ?, reward_coins = reward_coins + ? WHERE customer_id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("dii", $amount, $coins_earned, $customer_id);
            $stmt_update->execute();
            $stmt_update->close();

            // B. 寫入錢包交易紀錄
            $type = 'Top-up';
            $insert_sql = "INSERT INTO wallet_transactions (customer_id, type, amount, coins_earned) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("isdi", $customer_id, $type, $amount, $coins_earned);
            $stmt_insert->execute();
            $stmt_insert->close();

            $conn->commit();
            $success_msg = "Successfully topped up RM " . number_format($amount, 2) . "! You earned $coins_earned Coins. 🪙";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Top-up failed. Please try again. Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Minimum top-up amount is RM 10.";
    }
}

// ==========================================
// 3. 取得顧客最新的錢包餘額與金幣
// ==========================================
$query = "SELECT wallet_balance, reward_coins FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$current_balance = $user_data['wallet_balance'];
$current_coins = $user_data['reward_coins'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Digital Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-container" style="max-width: 600px;">
            
            <div class="auth-title">
                <h2><i class="fa-solid fa-wallet"></i> E-Wallet Top-up</h2>
                <p class="specs">Add funds to your GridCitY Wallet and earn loyalty coins.</p>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="cart-empty-state" style="margin-bottom: 20px; border-color: #4CAF50; padding: 15px;">
                    <p style="color: #4CAF50; margin: 0;"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <p class="text-danger" style="margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></p>
            <?php endif; ?>

            <div class="wallet-dashboard">
                <div class="stat-box">
                    <h4>Current Balance</h4>
                    <p>RM <?php echo number_format($current_balance, 2); ?></p>
                </div>
                <div class="stat-box" style="text-align: right;">
                    <h4>Reward Coins</h4>
                    <p style="color: #ffd700;"><i class="fa-solid fa-coins"></i> <?php echo number_format($current_coins); ?></p>
                </div>
            </div>

            <form action="wallet_topup.php" method="POST" class="form">
                <label class="form-label" style="margin-bottom: 15px; display: block;">Select Top-up Amount</label>
                
                <div class="topup-grid">
                    <label class="topup-option"><input type="radio" name="topup_option" value="50" required><div class="topup-card"><span class="amount">RM 50</span><span class="reward">+5 Coins</span></div></label>
                    <label class="topup-option"><input type="radio" name="topup_option" value="100"><div class="topup-card"><span class="amount">RM 100</span><span class="reward">+10 Coins</span></div></label>
                    <label class="topup-option"><input type="radio" name="topup_option" value="300"><div class="topup-card"><span class="amount">RM 300</span><span class="reward">+30 Coins</span></div></label>
                    <label class="topup-option"><input type="radio" name="topup_option" value="500"><div class="topup-card"><span class="amount">RM 500</span><span class="reward">+50 Coins</span></div></label>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="form-label" for="custom_amount">Or Enter Custom Amount (Min RM 10)</label>
                    <div class="custom-amount-wrapper">
                        <span>RM</span>
                        <input type="number" id="custom_amount" name="custom_amount" min="10" step="0.01" placeholder="0.00">
                    </div>
                    <p id="custom_reward_preview" style="color: #ffd700; margin-top: 8px; font-size: 0.9rem; display: none;"></p>
                </div>

                <div class="form-group input-group">
                    <label class="form-label" for="bank">Payment Method</label>
                    <select id="bank" class="form-control" style="background-color: var(--bg-darker); color: var(--text-main);">
                        <option>FPX Online Banking (Mock)</option>
                        <option>Credit/Debit Card (Mock)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-bolt"></i> Proceed to Pay
                </button>
            </form>

            <div class="specs" style="margin-top: 1.5rem; text-align: center;">
                <a href="profile.php" class="highlight-link"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
            </div>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioBtns = document.querySelectorAll('input[name="topup_option"]');
            const customInput = document.getElementById('custom_amount');
            const rewardPreview = document.getElementById('custom_reward_preview');

            // 1. 如果顧客點擊了「上面的卡片選項」，就把「自定義輸入框」清空
            radioBtns.forEach(btn => {
                btn.addEventListener('change', () => {
                    customInput.value = '';
                    customInput.required = false; // 取消必填
                    rewardPreview.style.display = 'none';
                });
            });

            // 2. 如果顧客在「自定義輸入框」打字，就取消勾選上面的卡片，並即時算金幣！
            customInput.addEventListener('input', () => {
                // 取消所有的 radio 勾選
                radioBtns.forEach(btn => {
                    btn.checked = false;
                    btn.required = false; // 取消必填，讓顧客可以送出自定義的
                });
                
                let amount = parseFloat(customInput.value);
                
                // 動態計算金幣並顯示
                if (!isNaN(amount) && amount >= 10) {
                    let coins = Math.floor(amount / 10);
                    rewardPreview.innerHTML = `<i class="fa-solid fa-coins"></i> +${coins} Coins earned!`;
                    rewardPreview.style.display = 'block';
                } else {
                    rewardPreview.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>