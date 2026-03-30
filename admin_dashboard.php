<?php
session_start();
include 'db_connect.php'; 

// 安全检查：必须是 admin 才能进入
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
    <title>Dashboard - PC Shop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* ====== 全局样式 ====== */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f9;
            display: flex; /* 制作左右分栏 */
            height: 100vh;
        }

        /* ====== 侧边栏 (Sidebar) ====== */
        .sidebar {
            width: 250px;
            background-color: #2c2c2c;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            display: flex;               
            align-items: center;         
            justify-content: center;     
            gap: 10px;                   
            font-family: 'Inter', serif;
            color: #8a2be2;              
            padding: 20px 0;
            border-bottom: 1px solid #444;
            margin: 0;
        }

        .sidebar-logo {
            width: 50px;                 
            height: auto;
            background-color: transparent; 
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 15px 20px;
            color: #ddd;
            text-decoration: none;
            border-bottom: 1px solid #3a3a3a;
            transition: 0.3s;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: #8a2be2;
            color: white;
            font-weight: bold;
        }
        .logout-btn {
            margin-top: auto; 
            background-color: #1a1a1a !important;
        }

        /* ====== 主内容区 (Main Content) ====== */
        .main-content {
            flex: 1;
            padding: 20px 40px;
            overflow-y: auto;
        }
        .header h1 {
            font-family: 'Inter', serif;
            color: #333;
            margin-top: 0;
        }

        /* ====== 顶部数据卡片 ====== */
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            flex: 1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border-top: 4px solid #8a2be2;
        }
        .card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        .card .number {
            font-size: 24px;
            font-weight: bold;
            color: #2c2c2c;
        }

        /* ====== 图表占位区 ====== */
        .charts-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-box {
            background: white;
            flex: 1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            border: 1px dashed #ccc;
        }

        /* ====== 底部表格与区块 ====== */
        .bottom-sections {
            display: flex;
            gap: 20px;
        }
        .table-section {
            flex: 2;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .side-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .widget-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        table th {
            background-color: #f9f9f9;
            color: #333;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .status-pending { background-color: #f39c12; }
        .status-completed { background-color: #27ae60; }
        .btn-action {
            padding: 5px 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .quick-action-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f4f4f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .quick-action-btn:hover {
            background-color: #8a2be2;
            color: white;
            border-color: #8a2be2;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>
            <img src="photo/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>PC SHOP</span>
        </h2>
            <ul>
            <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li>
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="manage_users.php">Users</a></li>
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
        </div>

        <div class="dashboard-cards">
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
                <h3>Recent builds</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Orders ID</th>
                            <th>Customers Ordered Spec</th>
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
                                $status_badge = ($status == 'Pending') ? 'status-pending' : 'status-completed';

                                echo "<tr>";
                                echo "<td>#" . (isset($row['order_id']) ? $row['order_id'] : 'N/A') . "</td>";
                                
                                $specs = isset($row['specs']) ? $row['specs'] : "Custom PC Build"; 
                                echo "<td>" . $specs . "</td>";
                                
                                $amount = isset($row['total_amount']) ? $row['total_amount'] : 0;
                                echo "<td>RM " . number_format($amount, 2) . "</td>";
                                
                                echo "<td><span class='status-badge {$status_badge}'>" . $status . "</span></td>";
                                
                                $order_id_link = isset($row['order_id']) ? $row['order_id'] : '#';
                                echo "<td><a href='view_order.php?id=" . $order_id_link . "' class='btn-action' style='text-decoration: none; display: inline-block;'>View</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr>
                                    <td colspan='5' style='text-align: center; padding: 30px; color: #888; font-style: italic; font-weight: bold;'>
                                        (there is not any products included)
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="side-section">
                <div class="widget-box">
                    <h3 style="color: #8a2be2; margin-top:0;">Stock Alert</h3>
                    <p><strong>Remaining quantity:</strong></p>
                    <ul style="color: #666; font-size: 14px; padding-left: 20px;">
                        <li>ROG Strix B650-A (2 left)</li>
                        <li>Corsair 850W Gold (Out of stock)</li>
                    </ul>
                    <a href="inventory_status.php" style="font-size: 12px; color: #8a2be2; text-decoration: none; font-weight: bold;">Check the fully remaining quantity &rarr;</a>
                </div>

                <div class="widget-box">
                    <h3 style="margin-top:0;">Quick action</h3>
                    <a href="add_product.php" class="quick-action-btn">+ Add product</a>
                    <a href="manage_products.php" class="quick-action-btn">Edit products</a>
                    <a href="pending_orders.php" class="quick-action-btn">Pending Builds</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>