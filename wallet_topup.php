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

        $amount = round((float) $_POST['custom_amount'], 2);
    } elseif (!empty($_POST['topup_option']) && is_numeric($_POST['topup_option'])) {
        $amount = round((float) $_POST['topup_option'], 2);
    }

    $method = $_POST['payment_method'] ?? '';

    if ($amount >= 10 && $amount <= 10000) {
        $auth_success = false;
        $bank_id = null;

     
        if ($method === 'Online Banking (FPX)') {
            $fpx_user = trim($_POST['fpx_username'] ?? '');
            $fpx_pass = trim($_POST['fpx_password'] ?? '');
            $selected_bank = trim($_POST['selected_bank'] ?? '');

            $stmt_bank = $conn->prepare("SELECT id, balance FROM bank WHERE fpx_username = ? AND fpx_password = ? AND bank_name = ?");
            $stmt_bank->bind_param("sss", $fpx_user, $fpx_pass, $selected_bank);
            $stmt_bank->execute();
            $bank_res = $stmt_bank->get_result();
            
            if ($bank_row = $bank_res->fetch_assoc()) {
                if ($bank_row['balance'] >= $amount) {
                    $bank_id = $bank_row['id'];
                    $auth_success = true;
                } else {
                    $error_msg = "Bank Declined: Insufficient funds in your bank account.";
                }
            } else {
                $error_msg = "FPX Authentication Failed: Invalid Username or Password.";
            }
            $stmt_bank->close();
            
        } elseif ($method === 'Credit Card') {
            $selected_card = $_POST['selected_card'] ?? '';
            if ($selected_card === 'new') {
                $card_num = str_replace([' ', '-'], '', $_POST['dummy_card_number']);
                $card_cvc = trim($_POST['dummy_card_cvc']);
                $card_exp = trim($_POST['dummy_card_expiry']); 
                $stmt_bank = $conn->prepare("SELECT id, balance FROM bank WHERE card_number = ? AND cvc = ? AND expiry_date = ?");
                $stmt_bank->bind_param("sss", $card_num, $card_cvc, $card_exp);
            } else {
                $card_id = intval($selected_card);
                $stmt_bank = $conn->prepare("SELECT b.id, b.balance FROM bank b JOIN saved_cards s ON b.id = s.bank_id WHERE s.card_id = ? AND s.customer_id = ?");
                $stmt_bank->bind_param("ii", $card_id, $customer_id);
            }
            
            $stmt_bank->execute();
            $bank_res = $stmt_bank->get_result();
            if ($bank_row = $bank_res->fetch_assoc()) {
                if ($bank_row['balance'] >= $amount) {
                    $bank_id = $bank_row['id'];
                    $auth_success = true;
                } else {
                    $error_msg = "Bank Declined: Insufficient funds in your Credit Card account.";
                }
            } else {
                $error_msg = "Credit Card Authentication Failed: Invalid Card Details.";
            }
            $stmt_bank->close();
        } else {
            $error_msg = "Please select a payment method.";
        }


        if ($auth_success && $bank_id !== null) {
            $coins_earned = floor($amount / 10);
            $conn->begin_transaction();

            try {
              
                $deduct_stmt = $conn->prepare("UPDATE bank SET balance = balance - ? WHERE id = ? AND balance >= ?");
                $deduct_stmt->bind_param("did", $amount, $bank_id, $amount);
                $deduct_stmt->execute();
                if ($deduct_stmt->affected_rows === 0) {
                    throw new Exception("Bank Declined: Insufficient funds or account locked.");
                }
                $deduct_stmt->close();


                $update_sql = "UPDATE customers SET wallet_balance = wallet_balance + ?, reward_coins = reward_coins + ?, lifetime_coins = lifetime_coins + ? WHERE customer_id = ?";
                $stmt_update = $conn->prepare($update_sql);
          
                $stmt_update->bind_param("diii", $amount, $coins_earned, $coins_earned, $customer_id);
                $stmt_update->execute();
                $stmt_update->close();

     
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
                $error_msg = "Transaction interrupted safely. No funds were lost. Error: " . $e->getMessage();
            }
        }
    } else {
        if (empty($error_msg)) $error_msg = "Top-up amount must be between RM 10 and RM 10,000.";
    }
}

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
                    <label class="form-label" for="custom_amount">Or Enter Custom Amount (min=RM10 & max=RM10000)</label>
                    <div class="custom-amount-wrapper">
                        <span>RM</span>
                        <input type="number" id="custom_amount" name="custom_amount" min="10" max="10000" step="0.01" placeholder="0.00">
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
                                    <strong style="color: #fff; font-family: 'JetBrains Mono', monospace; letter-spacing: 2px;">
    <?php echo htmlspecialchars($card['card_brand']); ?> &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; <?php echo htmlspecialchars($card['last_four_digits']); ?>
</strong>
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
                            <input type="text" name="dummy_card_expiry" placeholder="MM/YY" class="form-control" style="flex: 1;" maxlength="5">
                            <input type="text" name="dummy_card_cvc" placeholder="CVC" class="form-control" style="flex: 1;" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <div id="fpx_section" style="display: none; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(0, 243, 255, 0.2); padding: 20px; border-radius: 12px; margin-top: 15px;">
                    <h4 style="color: #00f2fe; margin-top: 0; margin-bottom: 15px; font-size: 1rem;">
                        <i class="fa-solid fa-building-columns"></i> Select Your Bank
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Maybank" style="margin-right: 10px;" onchange="toggleFPXForm()">
                            <img src="image/maybank.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="CIMB" style="margin-right: 10px;" onchange="toggleFPXForm()">
                            <img src="image/cimb.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="Public Bank" style="margin-right: 10px;" onchange="toggleFPXForm()">
                            <img src="image/public.png" style="height: 30px; object-fit: contain;">
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                            <input type="radio" name="selected_bank" value="RHB" style="margin-right: 10px;" onchange="toggleFPXForm()">
                            <img src="image/rhb.png" style="height: 30px; object-fit: contain;">
                        </label>
                    </div>
                    
                    <div id="fpx_login_form" style="display: none; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                        <p style="font-size: 0.85rem; color: #ffcc00; margin-bottom: 15px;">
                            <i class="fa-solid fa-shield-halved"></i> <strong>Secure Bank Login:</strong>
                        </p>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="fpx_username" placeholder="Bank Username" class="form-control" style="flex: 1; background: #000; color: #fff; border: 1px solid #333; padding: 10px; border-radius: 5px;">
                            <input type="password" name="fpx_password" placeholder="Password" class="form-control" style="flex: 1; background: #000; color: #fff; border: 1px solid #333; padding: 10px; border-radius: 5px;">
                        </div>
                    </div>
                </div> 

                <button type="submit" style="width: 100%; margin-top: 25px; background: #00f2fe; color: #000; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 900; cursor: pointer; transition: 0.3s; box-shadow: 0 0 15px rgba(0, 242, 254, 0.3);" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 0 25px rgba(0, 242, 254, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 0 15px rgba(0, 242, 254, 0.3)';">
                    <i class="fa-solid fa-bolt"></i> Confirm & Top Up
                </button>

            </form> 
        </div> 
    </main>

    <?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radioBtns = document.querySelectorAll('input[name="topup_option"]');
        const customInput = document.getElementById('custom_amount');
        const rewardPreview = document.getElementById('custom_reward_preview');

        if (radioBtns.length > 0 && customInput) {
            radioBtns.forEach(btn => {
                btn.addEventListener('change', () => {
                    customInput.value = '';
                    customInput.required = false; 
                    if(rewardPreview) rewardPreview.style.display = 'none';
                });
            });

            customInput.addEventListener('input', () => {
                radioBtns.forEach(btn => {
                    btn.checked = false;
                    btn.required = false; 
                });
                
                let amount = parseFloat(customInput.value);
                if (!isNaN(amount) && amount >= 10 && amount <= 10000) {
                    let coins = Math.floor(amount / 10);
                    if(rewardPreview) {
                        rewardPreview.innerHTML = `<i class="fa-solid fa-coins"></i> +${coins} Coins earned!`;
                        rewardPreview.style.display = 'block';
                    }
                } else {
                    if(rewardPreview) rewardPreview.style.display = 'none';
                }
            });
        }
        togglePaymentSections();
    });

    function togglePaymentSections() {
        var method = document.getElementById('payment_method').value;
        var ccSection = document.getElementById('credit_card_section');
        var fpxSection = document.getElementById('fpx_section');
        var newCardForm = document.getElementById('new_card_form');
        var fpxForm = document.getElementById('fpx_login_form');
        
        if (ccSection) ccSection.style.display = 'none';
        if (fpxSection) fpxSection.style.display = 'none';
        if (newCardForm) newCardForm.style.display = 'none';
        if (fpxForm) fpxForm.style.display = 'none';

        if (method === 'Credit Card') {
            if (ccSection) ccSection.style.display = 'block';
            toggleNewCardForm(); 
        } else if (method === 'Online Banking (FPX)') {
            if (fpxSection) fpxSection.style.display = 'block';
            toggleFPXForm();
        }
    }

    function toggleNewCardForm() {
        var radios = document.getElementsByName('selected_card');
        var newCardForm = document.getElementById('new_card_form');
        if (!newCardForm) return; 

        let isNew = false;
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked && radios[i].value === 'new') {
                isNew = true;
                break;
            }
        }
        newCardForm.style.display = isNew ? 'block' : 'none';
    }

    function toggleFPXForm() {
        const radios = document.getElementsByName('selected_bank');
        const fpxForm = document.getElementById('fpx_login_form');
        if (!fpxForm) return; 

        let isSelected = false;
        for (let r of radios) {
            if (r.checked) {
                isSelected = true;
                break;
            }
        }
        fpxForm.style.display = isSelected ? 'block' : 'none';
    }
</script>
</body>
</html>