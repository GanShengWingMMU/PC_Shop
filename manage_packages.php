<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // ACID Transaction 刪除：先刪除關聯零件，再刪除套餐主體
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM package_items WHERE package_id = $delete_id");
        $conn->query("DELETE FROM packages WHERE package_id = $delete_id");
        $conn->commit();
        header("Location: manage_packages.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>⚠️ System Error: Failed to delete package matrix.</div>";
    }
}

if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Package purged from the registry.</div>";
if (isset($_GET['msg']) && $_GET['msg'] == 'added') $message = "<div style='color: #00f2fe; background: rgba(0,242,254,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ New Blueprint Forged! Dynamic pricing activated.</div>";
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') $message = "<div style='color: #a855f7; background: rgba(168,85,247,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Package Architecture Updated!</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Packages - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="manage_categories.php"><i class="fas fa-tags"></i> Manage Categories</a></li>
                <li><a href="manage_packages.php" class="active"><i class="fas fa-layer-group"></i> Manage Packages</a></li>
                <?php if (strtolower($current_role) === 'superadmin') : ?>
                    <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h2 style="color: #a855f7; margin:0;"><i class="fas fa-layer-group"></i> Master Blueprints</h2>
                    <p style="color: #64748b; margin-top:5px;">Prices are dynamically calculated in real-time based on selected components.</p>
                </div>
                <a href="add_package.php" class="btn-action" style="background: linear-gradient(135deg, #a855f7, #00f2fe); color:#fff; font-weight:bold; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-hammer"></i> Forge New Package</a>
            </header>

            <?php echo $message; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(168,85,247,0.2); text-align: left;">
                            <th style="padding:15px; color:#a855f7; width:80px;">Visual</th>
                            <th style="padding:15px; color:#a855f7;">Package Name & Persona</th>
                            <th style="padding:15px; color:#a855f7; text-align:center;">Parts Inside</th>
                            <th style="padding:15px; color:#a855f7;">Real-time Total (RM)</th>
                            <th style="padding:15px; color:#a855f7;">Status</th>
                            <th style="padding:15px; color:#a855f7; text-align:right;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 連動前台：動態抓取真實零件總價與零件數量
                        $sql = "SELECT pk.*, 
                                (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = pk.package_id) AS real_price,
                                (SELECT COUNT(*) FROM package_items WHERE package_id = pk.package_id) AS item_count
                                FROM packages pk ORDER BY pk.package_id DESC";
                        $res = $conn->query($sql);
                        
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $pkg_id = $row['package_id'];
                                $item_count = $row['item_count'];
                                $real_price = $row['real_price'];
                                $status = $row['stock_status'];
                                
                                $status_color = "#00e676";
                                if ($status == 'Out of Stock') $status_color = "#ff4d4d";
                                elseif ($status == 'Pre-order') $status_color = "#facc15";

                                $img = htmlspecialchars($row['image_url']);
                                if (empty($img)) $img = 'image/placeholder_pc.png';

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05); transition:0.2s;' onmouseover='this.style.background=\"rgba(255,255,255,0.02)\"' onmouseout='this.style.background=\"none\"'>";
                                
                                echo "<td style='padding:15px;'><img src='{$img}' style='width:60px; height:60px; object-fit:contain; border-radius:8px; background:rgba(0,0,0,0.5);'></td>";
                                echo "<td style='padding:15px;'>
                                        <span style='font-weight:bold; font-size:16px; color:#fff;'>".htmlspecialchars($row['package_name'])."</span><br>
                                        <span style='font-size:12px; color:#00f2fe; background:rgba(0,242,254,0.1); padding:2px 8px; border-radius:10px;'>Target: ".htmlspecialchars($row['target_persona'])."</span>
                                      </td>";
                                echo "<td style='padding:15px; text-align:center;'><span style='font-family:\"JetBrains Mono\"; font-weight:bold; color:#a855f7;'>{$item_count} Parts</span></td>";
                                echo "<td style='padding:15px; font-weight:bold; color:#00e676; font-family:\"JetBrains Mono\"; font-size:16px;'>RM " . number_format($real_price, 2) . "</td>";
                                echo "<td style='padding:15px; font-weight:bold; color:{$status_color}; font-size:13px;'>{$status}</td>";
                                
                                echo "<td style='padding:15px; text-align:right;'>
                                        <div style='display:flex; gap:8px; justify-content:flex-end;'>
                                            <a href='edit_package.php?package_id={$pkg_id}' class='btn-action' style='color:#00f2fe; border-color:#00f2fe; padding:6px 14px; font-size:13px; text-decoration:none;'>Configure</a>
                                            <a href='manage_packages.php?delete_id={$pkg_id}' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 14px; font-size:13px; text-decoration:none;' onclick='return confirm(\"Are you sure you want to dismantle this blueprint?\");'>Dismantle</a>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#64748b;'>No blueprints forged yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>