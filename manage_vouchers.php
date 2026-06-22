<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['admin_username'] ?? 'UnknownAdmin';

if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}


if (isset($_GET['delete_id']) && strtolower($current_role) === 'superadmin') {
    $del_id = intval($_GET['delete_id']);
    

    $conn->query("UPDATE promo_codes SET status = 'Inactive' WHERE promo_id = $del_id");
    

    $action_msg = "Deactivated Promo Code ID: $del_id";
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->bind_param("issss", $admin_id, $admin_username, $current_role, $action_msg, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();

    header("Location: manage_vouchers.php?msg=inactive");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promo Protocols - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 15px; color:#00f2fe; font-size: 12px; text-transform: uppercase; background: rgba(0,242,254,0.05); border-bottom: 2px solid rgba(0,242,254,0.2); }
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; }
        .btn-forge { background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn-forge:hover { box-shadow: 0 0 15px rgba(0,242,254,0.5); transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="admin-container">
       <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-ticket-alt"></i> Promo Protocols (Vouchers)</h2>
                <a href="add_voucher.php" class="btn-forge"><i class="fas fa-plus"></i> Forge Promo</a>
            </header>

            <?php 
            if (isset($_GET['msg']) && $_GET['msg'] == 'inactive') echo "<div style='color:#ffcc00; background:rgba(255,204,0,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,204,0,0.3);'><i class='fas fa-archive'></i> Protocol marked as Inactive. Log retained.</div>";
            if (isset($_GET['msg']) && $_GET['msg'] == 'forged') echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3);'><i class='fas fa-check-circle'></i> New Protocol Forged and Active!</div>";
            ?>

            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>Code Name</th>
                        <th>Discount Value</th>
                        <th>Target</th>
                        <th>Min Spend / Max Cap</th>
                        <th>Access</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = @$conn->query("SELECT * FROM promo_codes ORDER BY promo_id DESC");
                    if ($res && $res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $is_vip = $row['is_vip_only'] ? "<span style='color:#ffd700; background:rgba(255,215,0,0.1); padding:2px 6px; border-radius:4px; font-size:10px;'>ELITE</span>" : "<span style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:2px 6px; border-radius:4px; font-size:10px;'>PUBLIC</span>";
                            $status_color = ($row['status'] == 'Active') ? '#00e676' : '#ff4d4d';
                            $val_display = ($row['discount_type'] == 'Fixed') ? 'RM '.$row['discount_value'] : $row['discount_value'].'%';
                            $row_opacity = ($row['status'] == 'Active') ? '1' : '0.5';

                            echo "<tr style='opacity: {$row_opacity};'>";
                            echo "<td style='font-family: JetBrains Mono; font-weight: bold; font-size:15px;'>{$row['code_name']}</td>";
                            echo "<td style='color:#00f2fe; font-weight:bold;'>{$val_display}</td>";
                            echo "<td style='color:#cbd5e1; font-size:12px;'>{$row['target_category']}</td>";
                            echo "<td style='color:#888; font-size:12px;'>Min: RM{$row['min_spend']} <br> Cap: RM{$row['max_cap']}</td>";
                            echo "<td>{$is_vip}</td>";
                            echo "<td style='color:{$status_color}; font-weight:bold; font-size:12px;'><i class='fas fa-circle' style='font-size:8px;'></i> {$row['status']}</td>";
                            echo "<td style='text-align: right;'>";
                            
                            if (strtolower($current_role) === 'superadmin' && $row['status'] == 'Active') {
                                echo "<a href='manage_vouchers.php?delete_id={$row['promo_id']}' onclick=\"return confirm('Set this protocol to Inactive? Customers will no longer be able to use it.');\" style='color:#ff4d4d; border:1px solid #ff4d4d; padding:4px 10px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;' onmouseover=\"this.style.background='rgba(255,77,77,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-ban'></i> Terminate</a>";
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#64748b;'>No Promo Protocols Found. Forge one to begin.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>