<?php
session_start();
// 🌟 核心规范：统一使用 config.php，共享前后台数据库实例
require_once 'config.php'; 

// 🌟 统一准入防御：严格使用 admin_role，防止普通顾客输入网址越权进入
if (!isset($_SESSION['admin_role']) || (strtolower($_SESSION['admin_role']) !== 'admin' && strtolower($_SESSION['admin_role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 处理误放在这里的删除逻辑 (使用安全预处理)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt_del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt_del->bind_param("i", $delete_id);
    if ($stmt_del->execute()) {
        header("Location: manage_products.php?deleted=1");
        exit();
    }
    $stmt_del->close();
}

// --- PART A: 统计核心数据 (统一改为防注入的面向对象写法) ---
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
    <title>Dashboard - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>GridCity PC</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            
            <li><a href="manage_packages.php">Packages</a></li>
            
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php">Build System</a></li>
            
            <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
                <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">Dashboard Overview</h1>
        </div>

        <div class="dashboard-cards" style="margin-top: 20px;">
            <div class="card">
                <h3>Total Revenue</h3>
                <div class="number">RM <?php echo number_format($total_sales, 2); ?></div>
            </div>
            <div class="card">
                <h3>Orders Placed</h3>
                <div class="number"><?php echo $total_orders; ?></div>
            </div>
            <div class="card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $total_products; ?></div>
            </div>
            <div class="card">
                <h3>REG. Customers</h3>
                <div class="number"><?php echo $total_users; ?></div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <div class="charts-container" style="display: flex; gap: 20px; margin-top: 20px;">
            <div class="chart-box" style="flex: 1; background: var(--bg-surface); padding: 20px; border-radius: 10px; border: 1px solid var(--border-color);">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-box" style="flex: 1; background: var(--bg-surface); padding: 20px; border-radius: 10px; border: 1px solid var(--border-color);">
                <canvas id="inventoryChart"></canvas>
            </div>
        </div>

        <script>
        // 1. 绘制左侧：销售趋势折线图
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates_arr); ?>, // PHP传过来的日期
                datasets: [{
                    label: 'Daily Revenue (RM)',
                    data: <?php echo json_encode($amounts_arr); ?>, // PHP传过来的金额
                    borderColor: '#00f2fe',
                    backgroundColor: 'rgba(0, 242, 254, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4 // 让线条变得平滑弯曲
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#a0aec0' } },
                    title: { display: true, text: 'Last 7 Days Sales Trend', color: '#ffffff', font: { size: 16 } }
                },
                scales: {
                    x: { ticks: { color: '#a0aec0' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: '#a0aec0' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                }
            }
        });

        // 2. 绘制右侧：分类库存甜甜圈图
        const invCtx = document.getElementById('inventoryChart').getContext('2d');
        new Chart(invCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_names); ?>, // PHP传过来的分类名
                datasets: [{
                    label: 'Products in Stock',
                    data: <?php echo json_encode($cat_counts); ?>, // PHP传过来的库存数
                    backgroundColor: ['#8a2be2', '#00f2fe', '#00e676', '#f39c12', '#ff4d4d', '#9b59b6', '#34495e', '#e84393'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: '#a0aec0', padding: 20 } },
                    title: { display: true, text: 'Inventory By Category', color: '#ffffff', font: { size: 16 } }
                }
            }
        });
        </script>
        <div class="bottom-sections">
            <div class="table-section">
                <h3>Recent orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_recent = "SELECT * FROM orders ORDER BY order_date DESC LIMIT 5";
                        $res_recent = mysqli_query($conn, $sql_recent);

                        if ($res_recent && mysqli_num_rows($res_recent) > 0) {
                            while($row = mysqli_fetch_assoc($res_recent)) {
                                $status = isset($row['order_status']) ? $row['order_status'] : (isset($row['status']) ? $row['status'] : 'Completed');
                                $status_badge = 'status-pending'; 
                                if ($status == 'Completed' || $status == 'Shipped') $status_badge = 'status-completed';

                                echo "<tr>";
                                echo "<td><strong>#" . $row['order_id'] . "</strong></td>";
                                echo "<td>Custom PC Build / Order items</td>";
                                $amount = isset($row['total_amount']) ? $row['total_amount'] : 0;
                                echo "<td><strong style='color: var(--accent-blue);'>RM " . number_format($amount, 2) . "</strong></td>";
                                echo "<td><span class='status-badge {$status_badge}'>" . $status . "</span></td>";
                                echo "<td><a href='manage_orders.php' class='btn-action' style='text-decoration: none; display: inline-block;'>View</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center; padding: 30px; color: var(--text-muted); font-style: italic;'>(No recent orders found)</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="side-section">
                <div class="widget-box">
                    <h3 style="color: var(--accent-purple); margin-top:0;">Stock Alert</h3>
                    <p style="color: var(--text-muted);"><strong>Remaining quantity:</strong></p>
                    <ul style="color: var(--text-main); font-size: 14px; padding-left: 20px;">
                        <li>ROG Strix B650-A <span style="color: var(--accent-danger);">(2 left)</span></li>
                        <li>Corsair 850W Gold <span style="color: var(--accent-danger);">(Out of stock)</span></li>
                    </ul>
                    <a href="manage_products.php" style="font-size: 12px; color: var(--accent-purple); text-decoration: none; font-weight: bold;">Check all inventory &rarr;</a>
                </div>

                <div class="widget-box">
                    <h3 style="margin-top:0; color: var(--text-main);">Quick action</h3>
                    <a href="add_product.php" class="quick-action-btn"><i class="fas fa-plus"></i> Add product</a>
                    <a href="manage_products.php" class="quick-action-btn" style="background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue);"><i class="fas fa-edit"></i> Edit products</a>
                    <a href="manage_orders.php" class="quick-action-btn" style="background: transparent; border: 1px solid var(--accent-purple); color: var(--accent-purple);"><i class="fas fa-box"></i> Manage Orders</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>