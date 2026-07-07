<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cid = intval($_POST['customer_id']);
    $amount = abs(intval($_POST['coin_amount'])); 
    $action = $_POST['action']; 

  
    $check_stmt = $conn->prepare("SELECT reward_coins, daily_coins_added, daily_coins_deducted, last_coin_update FROM customers WHERE customer_id = ?");
    $check_stmt->bind_param("i", $cid);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $today = date('Y-m-d');
        

        $daily_added = ($row['last_coin_update'] === $today) ? intval($row['daily_coins_added']) : 0;
        $daily_deducted = ($row['last_coin_update'] === $today) ? intval($row['daily_coins_deducted']) : 0;

        if ($action === 'add') {
       
            if ($daily_added + $amount > 500) {
                $rem = 500 - $daily_added;
                header("Location: manage_coins.php?msg=limit_reached&type=add&rem=" . $rem);
                exit();
            }
            $daily_added += $amount;
            $db_amount = $amount;
        } else {
          
            if ($daily_deducted + $amount > 500) {
                $rem = 500 - $daily_deducted;
                header("Location: manage_coins.php?msg=limit_reached&type=deduct&rem=" . $rem);
                exit();
            }
            $daily_deducted += $amount;
            $db_amount = -$amount; 
        }

   
        $stmt = $conn->prepare("UPDATE customers SET reward_coins = GREATEST(0, reward_coins + ?), daily_coins_added = ?, daily_coins_deducted = ?, last_coin_update = ? WHERE customer_id = ?");
        $stmt->bind_param("iiisi", $db_amount, $daily_added, $daily_deducted, $today, $cid);
        
        if($stmt->execute()) {
            header("Location: manage_coins.php?msg=success");
            exit();
        }
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coin Ledger - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 15px; color:#ffd700; font-size: 12px; text-transform: uppercase; background: rgba(255,215,0,0.05); border-bottom: 2px solid rgba(255,215,0,0.2); }
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; }
        .coin-input { background: #000; border: 1px solid #ffd700; color: #ffd700; padding: 6px; border-radius: 4px; width: 70px; text-align: center; font-family: 'JetBrains Mono'; outline: none; }
        .coin-input:focus { box-shadow: 0 0 10px rgba(255,215,0,0.3); }
        .btn-add { background: rgba(0,230,118,0.1); color: #00e676; border: 1px solid #00e676; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; font-size: 12px; }
        .btn-add:hover { background: #00e676; color: #000; box-shadow: 0 0 10px rgba(0,230,118,0.4); }
        .btn-deduct { background: rgba(255,77,77,0.1); color: #ff4d4d; border: 1px solid #ff4d4d; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; font-size: 12px; }
        .btn-deduct:hover { background: #ff4d4d; color: #000; box-shadow: 0 0 10px rgba(255,77,77,0.4); }
    </style>
</head>
<body>
    <div class="admin-container">
      <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #ffd700; margin:0;"><i class="fas fa-coins"></i> Universal Coin Ledger</h2>
                <p style="color:#888; font-size:13px; margin-top:5px;">Monitor and manually inject or deduct reward coins for citizens. (Max 500 per day)</p>
            </header>

      
            <?php 
            if (isset($_GET['msg'])) {
                if ($_GET['msg'] == 'success') {
                    echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3);'><i class='fas fa-check-circle'></i> Ledger Updated Successfully.</div>";
                } elseif ($_GET['msg'] == 'limit_reached') {
                    $rem = intval($_GET['rem'] ?? 0);
                    $type = ($_GET['type'] == 'add') ? 'Inject' : 'Deduct';
                    echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3);'><i class='fas fa-exclamation-triangle'></i> Daily Limit Reached! You can only <strong>{$type} {$rem}</strong> more coins for this citizen today.</div>";
                }
            }
            ?>

            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>Citizen ID</th>
                        <th>Username</th>
                        <th>Current Tier</th>
                        <th>Coin Balance</th>
                        <th style="text-align: right;">Manual Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT customer_id, username, membership_tier, reward_coins FROM customers ORDER BY reward_coins DESC");
                    while ($row = $res->fetch_assoc()) {
                        $tier_color = ($row['membership_tier'] == 'VIP') ? '#ffd700' : '#00f2fe';
                        echo "<tr>";
                        echo "<td style='color:#64748b; font-family: JetBrains Mono;'>USR-{$row['customer_id']}</td>";
                        echo "<td><strong>{$row['username']}</strong></td>";
                        echo "<td><span style='color:{$tier_color}; border:1px solid {$tier_color}; padding:2px 6px; border-radius:4px; font-size:10px;'>{$row['membership_tier']}</span></td>";
                        echo "<td style='color:#ffd700; font-family: JetBrains Mono; font-weight:bold; font-size:16px;'><i class='fas fa-coins'></i> {$row['reward_coins']}</td>";
                        echo "<td style='text-align: right;'>
                                <form method='POST' style='display:flex; justify-content:flex-end; gap:8px; align-items:center;'>
                                    <input type='hidden' name='customer_id' value='{$row['customer_id']}'>
                                    <input type='number' name='coin_amount' class='coin-input' placeholder='Qty' min='1' max='500' required>
                                    <button type='submit' name='action' value='add' class='btn-add' title='Inject Coins'><i class='fas fa-plus'></i></button>
                                    <button type='submit' name='action' value='deduct' class='btn-deduct' title='Deduct Coins'><i class='fas fa-minus'></i></button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>