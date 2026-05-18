<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 統一安全准入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 實時財務加總 (排除 Cancelled 訂單，完全連動前台結帳資料)
$res_sales = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'Cancelled'");
$total_sales = $res_sales->fetch_assoc()['total'] ?? 0;

$res_orders = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $res_orders->fetch_assoc()['total'] ?? 0;

$res_users = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_users = $res_users->fetch_assoc()['total'] ?? 0;

$res_pending = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'");
$total_pending = $res_pending->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - GridCity Admin Explorer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: rgba(11,11,18,0.6); border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; box-shadow: inset 0 0 15px rgba(255,255,255,0.02); backdrop-filter: blur(10px); }
        .stat-card h4 { margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 900; margin-top: 10px; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="manage_categories.php"><i class="fas fa-tags"></i> Manage Categories</a></li>
                <li><a href="manage_packages.php"><i class="fas fa-layer-group"></i> Manage Packages</a></li>
                <?php if (strtolower($current_role) === 'superadmin') : ?>
                    <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 40px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-chart-pie"></i> Operations Control Center</h2>
                    <p style="color: #64748b; margin-top:5px;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Commander'); ?></strong>. Monitoring live telemetry.</p>
                </div>
                <div style="font-family:'JetBrains Mono'; color:#00f2fe; background:rgba(0,242,254,0.05); padding:10px 20px; border-radius:8px; border:1px solid rgba(0,242,254,0.2);">
                    <i class="fa-solid fa-satellite"></i> LIVE NODE
                </div>
            </header>

            <div class="grid-stats">
                <div class="stat-card" style="border-left: 4px solid #00e676;">
                    <h4>Gross Revenue</h4>
                    <div class="value" style="color: #00e676; font-family:'JetBrains Mono';">RM <?php echo number_format($total_sales, 2); ?></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #00f2fe;">
                    <h4>Fulfillment Velocity</h4>
                    <div class="value" style="color: #00f2fe;"><?php echo $total_orders; ?> <span style="font-size:14px; color:#555;">Invoices</span></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #ffd700;">
                    <h4>Active Citizens</h4>
                    <div class="value" style="color: #ffd700;"><?php echo $total_users; ?> <span style="font-size:14px; color:#555;">Profiles</span></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #ff4d4d;">
                    <h4>Pending Telemetry</h4>
                    <div class="value" style="color: #ff4d4d;"><?php echo $total_pending; ?> <span style="font-size:14px; color:#555;">Queued</span></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <div style="background:rgba(11,11,18,0.4); padding:30px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                    <h3 style="color:#ff4d4d; margin-top:0; font-size:16px; text-transform:uppercase;"><i class="fas fa-radiation"></i> Hardware Stock Emergency Alert</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:20px;">The following nodes are running critically low on inventory. AI Builder compatibility mapping might degrade if these items completely drain.</p>
                    
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php
                        // 🌟 連動 products 庫存，低於 3 件自動抓取
                        $stock_res = $conn->query("SELECT product_id, product_name, stock_quantity FROM products WHERE stock_quantity <= 3 ORDER BY stock_quantity ASC LIMIT 5");
                        if($stock_res && $stock_res->num_rows > 0) {
                            while($item = $stock_res->fetch_assoc()) {
                                $qty = $item['stock_quantity'];
                                $lbl = ($qty == 0) ? "DEPLETED (Out of Stock)" : "$qty UNITS REMAINING";
                                $c = ($qty == 0) ? "#ff4d4d" : "#facc15";
                                
                                echo "<li style='display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid rgba(255,255,255,0.03); font-size:14px;'>
                                        <span><i class='fa-solid fa-microchip' style='color:#8a2be2; margin-right:10px;'></i> " . htmlspecialchars($item['product_name']) . "</span>
                                        <span style='color:$c; font-weight:bold; font-family:\"JetBrains Mono\"; font-size:12px;'>$lbl</span>
                                      </li>";
                            }
                        } else {
                            echo "<li style='color:#00e676; padding:15px; text-align:center; background:rgba(0,230,118,0.05); border-radius:6px; border:1px solid rgba(0,230,118,0.1);'><i class='fas fa-check-shield'></i> All core component supply chains stable. Quantum grids optimal.</li>";
                        }
                        ?>
                    </ul>
                </div>

                <div style="background:rgba(11,11,18,0.4); padding:30px; border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; gap:12px;">
                    <h3 style="color:#00f2fe; margin-top:0; font-size:16px; text-transform:uppercase;"><i class="fas fa-terminal"></i> Command Matrix</h3>
                    <a href="add_product.php" class="quick-action-btn" style="text-align:center; display:block; text-decoration:none;"><i class="fas fa-plus"></i> Launch New Hardware</a>
                    <a href="add_package.php" class="quick-action-btn" style="text-align:center; display:block; text-decoration:none; background:linear-gradient(135deg, #8a2be2, #4facfe); border:none; color:#fff;"><i class="fas fa-hammer"></i> Forge Pre-built Package</a>
                    <a href="manage_orders.php" class="quick-action-btn" style="text-align:center; display:block; text-decoration:none; background:transparent; border:1px solid #333; color:#aaa;"><i class="fas fa-truck"></i> Access Logistics Hub</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>