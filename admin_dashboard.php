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

// 實時財務加總 (排除 Cancelled 訂單)
$res_sales = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'Cancelled'");
$total_sales = $res_sales->fetch_assoc()['total'] ?? 0;

$res_orders = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $res_orders->fetch_assoc()['total'] ?? 0;

$res_users = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_users = $res_users->fetch_assoc()['total'] ?? 0;

$res_pending = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'");
$total_pending = $res_pending->fetch_assoc()['total'] ?? 0;

// 🌟 ====== 卡片底部小波浪图动态数据 (过去 7 天) ======
$dates = [];
for ($i = 6; $i >= 0; $i--) { $dates[] = date('Y-m-d', strtotime("-$i days")); }

$rev_data = array_fill_keys($dates, 0);
$res = $conn->query("SELECT DATE(order_date) as dt, SUM(total_amount) as total FROM orders WHERE order_status != 'Cancelled' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(order_date)");
if($res) { while($r = $res->fetch_assoc()) { if(isset($rev_data[$r['dt']])) $rev_data[$r['dt']] = $r['total']; } }
$rev_chart_data = implode(',', array_values($rev_data));

$ord_data = array_fill_keys($dates, 0);
$res = $conn->query("SELECT DATE(order_date) as dt, COUNT(*) as cnt FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(order_date)");
if($res) { while($r = $res->fetch_assoc()) { if(isset($ord_data[$r['dt']])) $ord_data[$r['dt']] = $r['cnt']; } }
$ord_chart_data = implode(',', array_values($ord_data));

$usr_data = array_fill_keys($dates, 0);
$res = $conn->query("SELECT DATE(created_at) as dt, COUNT(*) as cnt FROM customers WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
if($res) { while($r = $res->fetch_assoc()) { if(isset($usr_data[$r['dt']])) $usr_data[$r['dt']] = $r['cnt']; } }
$usr_chart_data = implode(',', array_values($usr_data));

$pen_data = array_fill_keys($dates, 0);
$res = $conn->query("SELECT DATE(order_date) as dt, COUNT(*) as cnt FROM orders WHERE order_status = 'Pending' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(order_date)");
if($res) { while($r = $res->fetch_assoc()) { if(isset($pen_data[$r['dt']])) $pen_data[$r['dt']] = $r['cnt']; } }
$pen_chart_data = implode(',', array_values($pen_data));

// 🌟 ====== 弹窗大图表动态数据 (今年 1-12 月各维度数据) ======
$m_revenue = array_fill(1, 12, 0);
$m_orders = array_fill(1, 12, 0);
$m_users = array_fill(1, 12, 0);
$m_pending = array_fill(1, 12, 0);

// 1. 月度营收
$res = $conn->query("SELECT MONTH(order_date) as mth, SUM(total_amount) as total FROM orders WHERE order_status != 'Cancelled' AND YEAR(order_date) = YEAR(CURDATE()) GROUP BY MONTH(order_date)");
if($res) while($r = $res->fetch_assoc()) $m_revenue[$r['mth']] = $r['total'];

// 2. 月度订单数
$res = $conn->query("SELECT MONTH(order_date) as mth, COUNT(*) as total FROM orders WHERE YEAR(order_date) = YEAR(CURDATE()) GROUP BY MONTH(order_date)");
if($res) while($r = $res->fetch_assoc()) $m_orders[$r['mth']] = $r['total'];

// 3. 月度新增用户
$res = $conn->query("SELECT MONTH(created_at) as mth, COUNT(*) as total FROM customers WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)");
if($res) while($r = $res->fetch_assoc()) $m_users[$r['mth']] = $r['total'];

// 4. 卡在各月的 Pending 订单数
$res = $conn->query("SELECT MONTH(order_date) as mth, COUNT(*) as total FROM orders WHERE order_status = 'Pending' AND YEAR(order_date) = YEAR(CURDATE()) GROUP BY MONTH(order_date)");
if($res) while($r = $res->fetch_assoc()) $m_pending[$r['mth']] = $r['total'];

$js_m_rev = implode(',', array_values($m_revenue));
$js_m_ord = implode(',', array_values($m_orders));
$js_m_usr = implode(',', array_values($m_users));
$js_m_pen = implode(',', array_values($m_pending));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - GridCity PC Admin Explorer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { 
            background: rgba(11,11,18,0.6); border: 1px solid rgba(255,255,255,0.05); 
            padding: 25px; border-radius: 12px; box-shadow: inset 0 0 15px rgba(255,255,255,0.02); 
            backdrop-filter: blur(10px); position: relative; overflow: hidden;   
            display: flex; flex-direction: column; transition: all 0.3s ease; cursor: pointer;
        }
        /* 🌟 各卡片专属悬浮发光颜色 */
        .card-revenue:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 230, 118, 0.2); border-color: rgba(0, 230, 118, 0.5); }
        .card-orders:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 242, 254, 0.2); border-color: rgba(0, 242, 254, 0.5); }
        .card-users:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(255, 215, 0, 0.2); border-color: rgba(255, 215, 0, 0.5); }
        .card-pending:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(255, 77, 77, 0.2); border-color: rgba(255, 77, 77, 0.5); }

        .stat-card-header { display: flex; justify-content: space-between; align-items: center; z-index: 2; }
        .stat-card h4 { margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 800; z-index: 2; }
        .stat-card .value { font-size: 1.8rem; font-weight: 900; margin-top: 15px; font-family: 'Inter', sans-serif; z-index: 2; }
        .chart-sparkline { position: absolute; bottom: 0; left: 0; width: 100%; z-index: 1; pointer-events: none; }
        
        /* 🌟 动态模态框 (Modal) 样式 */
        .cyber-modal {
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px);
            align-items: center; justify-content: center;
        }
        .cyber-modal-content {
            background: rgba(11,11,18,0.95); border: 1px solid #333; border-radius: 12px;
            width: 90%; max-width: 850px; padding: 30px; position: relative;
            box-shadow: 0 0 40px rgba(0,0,0,1);
            animation: modalFadeIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .close-modal {
            position: absolute; right: 20px; top: 15px; color: #64748b; font-size: 24px; 
            font-weight: bold; cursor: pointer; transition: 0.3s; z-index: 10;
        }
        .close-modal:hover { color: #fff; transform: scale(1.1); }
        @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        
        .recent-order-items { font-size: 12px; color: #cbd5e1; max-height: 60px; overflow-y: auto; padding-right: 5px; }
        .recent-order-items::-webkit-scrollbar { width: 4px; }
        .recent-order-items::-webkit-scrollbar-thumb { background: rgba(0,242,254,0.3); border-radius: 4px; }
        .item-qty { color: #00f2fe; font-weight: bold; margin-right: 5px; background: rgba(0,242,254,0.1); padding: 2px 6px; border-radius: 4px; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
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
                <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php">Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 25px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-chart-pie"></i> Operations Control Center</h2>
                    <p style="color: #64748b; margin-top:5px;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Commander'); ?></strong>. Monitoring live telemetry.</p>
                </div>
                <div style="font-family:'JetBrains Mono'; color:#00f2fe; background:rgba(0,242,254,0.05); padding:10px 20px; border-radius:8px; border:1px solid rgba(0,242,254,0.2);">
                    <i class="fa-solid fa-satellite"></i> LIVE NODE
                </div>
            </header>

            <div class="grid-stats">
                <div class="stat-card card-revenue" style="border-left: 4px solid #00e676;" onclick="openDetailModal('revenue')" title="Click to view Monthly Trend">
                    <div class="stat-card-header">
                        <h4>Gross Revenue <i class="fas fa-external-link-alt" style="font-size: 10px; margin-left: 5px; color:#00e676;"></i></h4>
                        <span class="badge" style="background:rgba(0,230,118,0.1); color:#00e676;">TOTAL</span>
                    </div>
                    <div class="value" style="color: #00e676; font-family:'JetBrains Mono';">RM <?php echo number_format($total_sales, 2); ?></div>
                    <div id="spark1" class="chart-sparkline"></div>
                </div>

                <div class="stat-card card-orders" style="border-left: 4px solid #00f2fe;" onclick="openDetailModal('orders')" title="Click to view Monthly Trend">
                    <div class="stat-card-header">
                        <h4>Fulfillment Velocity <i class="fas fa-external-link-alt" style="font-size: 10px; margin-left: 5px; color:#00f2fe;"></i></h4>
                        <span class="badge" style="background:rgba(0,242,254,0.1); color:#00f2fe;">ALL TIME</span>
                    </div>
                    <div class="value" style="color: #00f2fe;"><?php echo $total_orders; ?> <span style="font-size:14px; color:#555;">Invoices</span></div>
                    <div id="spark2" class="chart-sparkline"></div>
                </div>

                <div class="stat-card card-users" style="border-left: 4px solid #ffd700;" onclick="openDetailModal('users')" title="Click to view Monthly Trend">
                    <div class="stat-card-header">
                        <h4>Active Citizens <i class="fas fa-external-link-alt" style="font-size: 10px; margin-left: 5px; color:#ffd700;"></i></h4>
                        <span class="badge" style="background:rgba(255,215,0,0.1); color:#ffd700;">REGISTERED</span>
                    </div>
                    <div class="value" style="color: #ffd700;"><?php echo $total_users; ?> <span style="font-size:14px; color:#555;">Profiles</span></div>
                    <div id="spark3" class="chart-sparkline"></div>
                </div>

                <div class="stat-card card-pending" style="border-left: 4px solid #ff4d4d;" onclick="openDetailModal('pending')" title="Click to view Monthly Trend">
                    <div class="stat-card-header">
                        <h4>Pending Telemetry <i class="fas fa-external-link-alt" style="font-size: 10px; margin-left: 5px; color:#ff4d4d;"></i></h4>
                        <span class="badge" style="background:rgba(255,77,77,0.1); color:#ff4d4d;">ACTION REQ</span>
                    </div>
                    <div class="value" style="color: #ff4d4d;"><?php echo $total_pending; ?> <span style="font-size:14px; color:#555;">Queued</span></div>
                    <div id="spark4" class="chart-sparkline"></div>
                </div>
            </div>

            <div style="background:rgba(11,11,18,0.4); padding:30px; border-radius:12px; border:1px solid rgba(255,255,255,0.05); margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="color:#00f2fe; margin:0; font-size:16px; text-transform:uppercase;"><i class="fas fa-stream"></i> Live Logistics Feed (Recent Orders)</h3>
                    <a href="manage_orders.php" style="color: #64748b; font-size: 13px; text-decoration: none; border-bottom: 1px dashed #64748b; padding-bottom: 2px;">Manage All Orders <i class="fas fa-arrow-right" style="font-size: 10px;"></i></a>
                </div>

                <table style="width:100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <th style="padding: 12px 15px; color:#64748b; font-size: 12px; text-transform: uppercase;">Order ID & Date</th>
                            <th style="padding: 12px 15px; color:#64748b; font-size: 12px; text-transform: uppercase;">Customer</th>
                            <th style="padding: 12px 15px; color:#64748b; font-size: 12px; text-transform: uppercase;">Items Purchased</th>
                            <th style="padding: 12px 15px; color:#64748b; font-size: 12px; text-transform: uppercase;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_recent = "SELECT o.order_id, c.username, o.order_status, o.total_amount, o.order_date 
                                       FROM orders o JOIN customers c ON o.customer_id = c.customer_id 
                                       ORDER BY o.order_id DESC LIMIT 5";
                        $res_recent = $conn->query($sql_recent);
                        if ($res_recent && $res_recent->num_rows > 0) {
                            while ($row = $res_recent->fetch_assoc()) {
                                $order_id = $row['order_id'];
                                $status = $row['order_status'];
                                
                                $status_bg = "rgba(250, 204, 21, 0.1)"; $status_color = "#facc15"; 
                                if ($status == 'Processing') { $status_bg = "rgba(0, 242, 254, 0.1)"; $status_color = "#00f2fe"; }
                                elseif ($status == 'Shipped') { $status_bg = "rgba(168, 85, 247, 0.1)"; $status_color = "#a855f7"; }
                                elseif ($status == 'Completed') { $status_bg = "rgba(0, 230, 118, 0.1)"; $status_color = "#00e676"; }
                                elseif ($status == 'Cancelled') { $status_bg = "rgba(255, 77, 77, 0.1)"; $status_color = "#ff4d4d"; }

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.03);'>";
                                echo "<td style='padding: 15px;'>
                                        <div style='color: #fff; font-family: JetBrains Mono; font-weight: bold;'>#" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "</div>
                                        <div style='color: #64748b; font-size: 11px; margin-top: 4px;'>" . date('d M, h:i A', strtotime($row['order_date'])) . "</div>
                                      </td>";
                                echo "<td style='padding: 15px; color: #fff; font-weight: 600;'>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td style='padding: 15px; width: 40%;'><div class='recent-order-items'>";
                                $sql_items = "SELECT od.quantity, p.product_name, pkg.package_name, sb.build_name FROM order_details od LEFT JOIN products p ON od.product_id = p.product_id LEFT JOIN packages pkg ON od.package_id = pkg.package_id LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build WHERE od.order_id = " . $order_id;
                                $res_items = $conn->query($sql_items);
                                while($item = $res_items->fetch_assoc()) {
                                    $item_name = $item['product_name'] ?: ($item['package_name'] ? "[Package] ".$item['package_name'] : "[Custom PC] ".$item['build_name']);
                                    echo "<div style='margin-bottom: 4px;'><span class='item-qty'>{$item['quantity']}x</span> " . htmlspecialchars($item_name) . "</div>";
                                }
                                echo "</div></td>";
                                echo "<td style='padding: 15px;'><span class='status-badge' style='background: {$status_bg}; color: {$status_color}; border: 1px solid {$status_color};'>{$status}</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='padding: 20px; text-align: center; color: #64748b;'>No recent orders found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <div style="background:rgba(11,11,18,0.4); padding:30px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                    <h3 style="color:#ff4d4d; margin-top:0; font-size:16px; text-transform:uppercase;"><i class="fas fa-radiation"></i> Hardware Stock Emergency Alert</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:20px;">The following nodes are running critically low on inventory.</p>
                    
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php
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

    <div id="detailModal" class="cyber-modal">
        <div class="cyber-modal-content" id="modalBox">
            <span class="close-modal" onclick="closeDetailModal()">&times;</span>
            <h3 id="modalTitle" style="margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 20px;">
                </h3>
            <p id="modalSubtitle" style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
                </p>
            
            <div id="modalChart" style="min-height: 350px;"></div>
        </div>
    </div>

    <script>
        // --- 1. 卡片底部的小动态 Sparkline 图表 ---
        var commonOptions = {
            chart: { type: 'area', height: 80, sparkline: { enabled: true } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.0, stops: [0, 90, 100] } },
            tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function (seriesName) { return '' } } }, marker: { show: false } }
        };

        new ApexCharts(document.querySelector("#spark1"), { ...commonOptions, series: [{ data: [<?php echo $rev_chart_data; ?>] }], colors: ['#00e676'] }).render();
        new ApexCharts(document.querySelector("#spark2"), { ...commonOptions, series: [{ data: [<?php echo $ord_chart_data; ?>] }], colors: ['#00f2fe'] }).render();
        new ApexCharts(document.querySelector("#spark3"), { ...commonOptions, series: [{ data: [<?php echo $usr_chart_data; ?>] }], colors: ['#ffd700'] }).render();
        new ApexCharts(document.querySelector("#spark4"), { ...commonOptions, series: [{ data: [<?php echo $pen_chart_data; ?>] }], colors: ['#ff4d4d'] }).render();

        // --- 2. 强大的动态弹窗 (Modal) 与大图表逻辑 ---
        var modal = document.getElementById("detailModal");
        var modalBox = document.getElementById("modalBox");
        var detailChart = null; // 全局变量存储图表实例

        function openDetailModal(type) {
            var title = document.getElementById("modalTitle");
            var subtitle = document.getElementById("modalSubtitle");
            
            var chartData = [];
            var chartColor = '';
            var chartName = '';
            var isCurrency = false;

            // 根据点击的卡片类型，注入不同的数据和文案
            if (type === 'revenue') {
                title.innerHTML = '<i class="fas fa-chart-bar"></i> Monthly Revenue Overview (<?php echo date('Y'); ?>)';
                title.style.color = '#00e676';
                subtitle.innerText = 'Breakdown of total gross revenue processed per month.';
                modalBox.style.borderColor = 'rgba(0, 230, 118, 0.5)';
                modalBox.style.boxShadow = '0 0 30px rgba(0, 230, 118, 0.2)';
                chartData = [<?php echo $js_m_rev; ?>];
                chartColor = '#00e676';
                chartName = 'Revenue';
                isCurrency = true;
            } else if (type === 'orders') {
                title.innerHTML = '<i class="fas fa-file-invoice"></i> Fulfillment Velocity (<?php echo date('Y'); ?>)';
                title.style.color = '#00f2fe';
                subtitle.innerText = 'Total number of valid invoices/orders placed per month.';
                modalBox.style.borderColor = 'rgba(0, 242, 254, 0.5)';
                modalBox.style.boxShadow = '0 0 30px rgba(0, 242, 254, 0.2)';
                chartData = [<?php echo $js_m_ord; ?>];
                chartColor = '#00f2fe';
                chartName = 'Invoices';
            } else if (type === 'users') {
                title.innerHTML = '<i class="fas fa-users"></i> Active Citizens Growth (<?php echo date('Y'); ?>)';
                title.style.color = '#ffd700';
                subtitle.innerText = 'Number of new users registering accounts each month.';
                modalBox.style.borderColor = 'rgba(255, 215, 0, 0.5)';
                modalBox.style.boxShadow = '0 0 30px rgba(255, 215, 0, 0.2)';
                chartData = [<?php echo $js_m_usr; ?>];
                chartColor = '#ffd700';
                chartName = 'New Users';
            } else if (type === 'pending') {
                title.innerHTML = '<i class="fas fa-clock"></i> Pending Telemetry (<?php echo date('Y'); ?>)';
                title.style.color = '#ff4d4d';
                subtitle.innerText = 'Orders placed in these months that are STILL in Pending status.';
                modalBox.style.borderColor = 'rgba(255, 77, 77, 0.5)';
                modalBox.style.boxShadow = '0 0 30px rgba(255, 77, 77, 0.2)';
                chartData = [<?php echo $js_m_pen; ?>];
                chartColor = '#ff4d4d';
                chartName = 'Pending Orders';
            }

            // 如果之前有图表，先销毁掉再画新的
            if(detailChart) {
                detailChart.destroy();
            }

            // 渲染动态大图表
            var options = {
                series: [{ name: chartName, data: chartData }],
                chart: { type: 'bar', height: 350, toolbar: { show: false }, foreColor: '#64748b' },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                stroke: { width: 0 },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    axisBorder: { show: false }, axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) { return isCurrency ? "RM " + val.toLocaleString() : val; }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: { shade: 'dark', type: "vertical", shadeIntensity: 0.5, gradientToColors: [chartColor], inverseColors: true, opacityFrom: 1, opacityTo: 1, stops: [0, 100] }
                },
                colors: [chartColor],
                tooltip: {
                    theme: 'dark',
                    y: { formatter: function (val) { return isCurrency ? "RM " + val.toLocaleString() : val; } }
                }
            };

            detailChart = new ApexCharts(document.querySelector("#modalChart"), options);
            detailChart.render();
            
            modal.style.display = "flex";
        }

        function closeDetailModal() {
            modal.style.display = "none";
        }

        // 点击黑色遮罩空白处自动关闭
        window.onclick = function(event) {
            if (event.target == modal) {
                closeDetailModal();
            }
        }
    </script>
</body>
</html>