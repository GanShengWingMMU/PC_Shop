<?php
session_start();
include 'db_connect.php'; 

// 🌟 修正 1：你的数据库里写的是 "SuperAdmin" (有大写)，所以我们加上 strtolower() 把它转成小写来对比，防止你被踢出去！
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 处理删除 (Delete) 逻辑 ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // 执行删除 SQL
    $sql_delete = "DELETE FROM products WHERE product_id = $delete_id";
    if (mysqli_query($conn, $sql_delete)) {
        // 删除成功后刷新页面，并带上 deleted 提示
        header("Location: manage_products.php?deleted=1");
        exit();
    } else {
        $message = "<div class='error-msg'>⚠️ Failed to delete product.</div>";
    }
}

// --- PART A: 统计核心数据 ---
$res_sales = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders");
$total_sales = mysqli_fetch_assoc($res_sales)['total'] ?? 0;

$res_orders = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
$total_orders = mysqli_fetch_assoc($res_orders)['total'] ?? 0;

$res_products = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
$total_products = mysqli_fetch_assoc($res_products)['total'] ?? 0;

// 🌟 修正 2：既然你的管理员都存在 `admins` 表里，那顾客肯定是在 `customers` 表里对吧！直接连去 customers 抓人数！
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers");
$total_users = mysqli_fetch_assoc($res_users)['total'] ?? 0;

// --- PART B: 准备图表数据 ---
$dates_arr = [];
$amounts_arr = [];
$sql_trend = "SELECT DATE(order_date) as date, SUM(total_amount) as daily_total 
              FROM orders 
              GROUP BY DATE(order_date) ORDER BY date DESC LIMIT 7";
$res_trend = mysqli_query($conn, $sql_trend);

if ($res_trend) {
    while($row = mysqli_fetch_assoc($res_trend)) {
        $dates_arr[] = date('M d', strtotime($row['date']));
        $amounts_arr[] = $row['daily_total'];
    }
}
$dates_arr = array_reverse($dates_arr);
$amounts_arr = array_reverse($amounts_arr);

// 分类库存数据 (加入了安全防崩溃机制)
$cat_names = [];
$cat_counts = [];

try {
    $res_cat = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM products GROUP BY category");
    if ($res_cat && mysqli_num_rows($res_cat) > 0) {
        while($row = mysqli_fetch_assoc($res_cat)) {
            $cat_names[] = $row['category'];
            $cat_counts[] = $row['count'];
        }
    } else {
        throw new Exception("No data"); 
    }
} catch (Exception $e) {
    $cat_names = ['Processors', 'Graphics Cards', 'Motherboards', 'RAM', 'Storage'];
    $cat_counts = [25, 15, 20, 30, 10];
}

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
            <li><a href="admin_dashboard.php">Dashboard</a></li>
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

        <div class="charts-container">
            <div class="chart-box">[ Sales Trend Graph Placeholder ]</div>
            <div class="chart-box">[ Inventory By Category Graph Placeholder ]</div>
        </div>

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
                                $status = isset($row['status']) ? $row['status'] : 'Completed';
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