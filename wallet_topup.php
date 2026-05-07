<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = 0;

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

 <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="color: #00f2fe; font-size: 0.9rem; font-weight: bold; margin-bottom: 8px; display: block;"><i class="fa-solid fa-money-check-dollar"></i> Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control" required onchange="togglePaymentSections()" style="background-color: #000; color: #fff; border: 1px solid rgba(0, 243, 255, 0.4); font-size: 1.05rem; padding: 12px; border-radius: 8px; width: 100%;">
                        <option value="">-- Select Payment Method --</option>
                        <option value="Credit Card">💳 Credit / Debit Card</option>
                        <option value="Online Banking (FPX)">🏦 Online Banking (FPX)</option>
                    </select>
                </div>

                <div id="credit_card_section" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 243, 255, 0.2); padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: left;">
                    <h4 style="color: #00f2fe; margin-top: 0; margin-bottom: 15px; font-size: 1rem;"><i class="fa-regular fa-credit-card"></i> Select or Enter Card Details</h4>
                    
                    <?php if(!empty($saved_cards)): ?>
                        <?php foreach ($saved_cards as $index => $card): ?>
                            <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 10px; color: #ccc; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                                <input type="radio" name="selected_card" value="<?php echo htmlspecialchars($card['card_id']); ?>" style="margin-right: 15px;" onchange="toggleNewCardForm()" <?php echo $card['is_default'] ? 'checked' : ''; ?>>
                                <div style="flex: 1;">
                                    <strong style="color: #fff;"><?php echo htmlspecialchars($card['card_brand']); ?> ending in <?php echo htmlspecialchars($card['last_four_digits']); ?></strong>
                                    <?php echo $card['is_default'] ? '<span style="margin-left: 8px; background: #00f2fe; color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">Default</span>' : ''; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <label style="display: flex; align-items: center; cursor: pointer; color: #fff; padding: 12px; background: rgba(0, 243, 255, 0.05); border-radius: 6px; border: 1px dashed rgba(0, 243, 255, 0.5);">
                        <input type="radio" name="selected_card" value="new" style="margin-right: 15px;" onchange="toggleNewCardForm()">
                        <strong>➕ Pay with a New Card</strong>
                    </label>

                    <div id="new_card_form" style="display: none; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <input type="text" name="dummy_card_name" placeholder="Name on Card (e.g., Ali Bin Abu)" class="form-control" style="width: 100%;">
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <input type="text" name="dummy_card_number" placeholder="Card Number (16 digits)" class="form-control" style="flex: 2;">
                            <input type="text" name="dummy_card_cvc" placeholder="CVC" class="form-control" style="flex: 1;" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <div id="fpx_section" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 243, 255, 0.2); padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: left;">
                    <h4 style="color: #00f2fe; margin-top: 0; margin-bottom: 15px; font-size: 1rem;"><i class="fa-solid fa-building-columns"></i> Select Your Bank</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Maybank2U" style="margin-right: 10px;">
                            <img src="image/maybank.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="CIMB Clicks" style="margin-right: 10px;">
                            <img src="image/cimb.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Public Bank" style="margin-right: 10px;">
                            <img src="image/public.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="RHB Now" style="margin-right: 10px;">
                            <img src="image/rhb.png" style="height: 30px; object-fit: contain;">
                        </label>
                    </div>
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

        // 🌟 升級 3：切換付款方式的邏輯
        function togglePaymentSections() {
            var method = document.getElementById('payment_method').value;
            var ccSection = document.getElementById('credit_card_section');
            var fpxSection = document.getElementById('fpx_section');
            var newCardForm = document.getElementById('new_card_form');
            
            // 隱藏所有區塊
            ccSection.style.display = 'none';
            fpxSection.style.display = 'none';
            newCardForm.style.display = 'none';

            // 根據選擇打開對應區塊
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
</body>
</html>