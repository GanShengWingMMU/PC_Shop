<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 🌟 核心自动逻辑：实时检测 Processing 订单，超过 4 分钟自动改为 Shipped
$conn->query("UPDATE orders SET order_status = 'Shipped' 
              WHERE order_status = 'Processing' 
              AND order_date < NOW() - INTERVAL 4 MINUTE");

// 🌟 1. 捕捉排序参数
$current_sort = $_GET['sort'] ?? 'desc';
// 确定 SQL 的 ORDER BY 部分
$order_by = 'o.order_id DESC'; // 默认：最新订单
if ($current_sort === 'asc') $order_by = 'o.order_id ASC';
elseif ($current_sort === 'price_desc') $order_by = 'o.total_amount DESC';
elseif ($current_sort === 'price_asc') $order_by = 'o.total_amount ASC';

// 🌟 更新訂單狀態
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    
    // 🌟 在允许的列表中加入 'Delivered'
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];
    
    $saved_sort = isset($_POST['current_sort']) ? $_POST['current_sort'] : 'desc';
    
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            header("Location: manage_orders.php?updated=1&sort=" . urlencode($saved_sort));
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .admin-sort-dropdown { background: rgba(0,0,0,0.6); color: #00f2fe; border: 1px solid rgba(0,242,254,0.3); padding: 8px 15px; border-radius: 6px; cursor: pointer; }
        
        /* 🌟 把你原本很酷的 Items CSS 加回来 */
        .qty-badge { background: rgba(0,242,254,0.1); padding: 2px 6px; border-radius: 4px; color: #00f2fe; font-weight: 900; margin-right: 8px; font-size: 11px; border: 1px solid rgba(0,242,254,0.3); }
        .item-row { margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px dashed rgba(255,255,255,0.05); font-size: 12px; color: #cbd5e1; }
        .item-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    </style>
</head>
<body>
    <div class="admin-container">
         <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-box-open"></i> Global Order Fulfillment Center</h2>
                </div>
                <form method="GET" action="manage_orders.php" id="sortForm">
                    <select name="sort" class="admin-sort-dropdown" onchange="document.getElementById('sortForm').submit();">
                        <option value="desc" <?php echo $current_sort == 'desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $current_sort == 'asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_desc" <?php echo $current_sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_asc" <?php echo $current_sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    </select>
                </form>
            </header>

            <?php if (isset($_GET['updated'])) echo "<div style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'>✅ Logistics status updated successfully.</div>"; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px; color:#64748b; font-size:12px;">ID</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Customer</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Date</th>
                            <th style="padding:15px; color:#64748b; font-size:12px; width:30%;">Items Purchased</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Total</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Status</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, c.username FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY $order_by";
                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()) {
                            $status = $row['order_status'];
                            $status_color = "#facc15"; // Pending Default
                            
                            // 🌟 为 Delivered 添加专属的高亮蓝色
                            if ($status == 'Processing') $status_color = "#00f2fe";
                            elseif ($status == 'Shipped') $status_color = "#a855f7";
                            elseif ($status == 'Delivered') $status_color = "#3b82f6"; // Royal Blue
                            elseif ($status == 'Completed') $status_color = "#00e676";
                            elseif ($status == 'Cancelled') $status_color = "#ff4d4d";

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding:15px; font-family: JetBrains Mono;'>#{$row['order_id']}</td>";
                            echo "<td style='padding:15px; font-weight:bold;'>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td style='padding:15px; color:#888; font-size:12px;'>" . date('d M, Y', strtotime($row['order_date'])) . "</td>";
                            
                            // 🌟 完美修复：抓取订单内部商品的完整逻辑
                            echo "<td style='padding:15px;'>";
                            $order_id_val = $row['order_id'];
                            $sql_items = "SELECT od.quantity, p.product_name, pkg.package_name, sb.build_name 
                                          FROM order_details od 
                                          LEFT JOIN products p ON od.product_id = p.product_id 
                                          LEFT JOIN packages pkg ON od.package_id = pkg.package_id 
                                          LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build 
                                          WHERE od.order_id = $order_id_val";
                            $res_items = $conn->query($sql_items);
                            while($item = $res_items->fetch_assoc()) {
                                $name = $item['product_name'] ?: ($item['package_name'] ? "[Package] ".$item['package_name'] : "[Custom PC] ".$item['build_name']);
                                echo "<div class='item-row'><span class='qty-badge'>{$item['quantity']}x</span> ".htmlspecialchars($name)."</div>";
                            }
                            echo "</td>";
                            
                            echo "<td style='padding:15px; color:#00e676; font-weight:bold; font-family: JetBrains Mono;'>RM " . number_format($row['total_amount'], 2) . "</td>";
                            echo "<td style='padding:15px; font-weight:bold; color:$status_color'>$status</td>";
                            echo "<td style='padding:15px;'>
                                    <form method='POST'>
                                        <input type='hidden' name='order_id' value='{$row['order_id']}'>
                                        <input type='hidden' name='current_sort' value='$current_sort'>
                                        <select name='new_status' style='background:#000; color:#fff; border:1px solid #333; padding:6px; border-radius:4px; margin-bottom:5px; width:100px;'>
                                            <option value='Pending' ".($status=='Pending'?'selected':'').">Pending</option>
                                            <option value='Processing' ".($status=='Processing'?'selected':'').">Processing</option>
                                            <option value='Shipped' ".($status=='Shipped'?'selected':'').">Shipped</option>
                                            <option value='Delivered' ".($status=='Delivered'?'selected':'').">Delivered</option> <option value='Completed' ".($status=='Completed'?'selected':'').">Completed</option>
                                            <option value='Cancelled' ".($status=='Cancelled'?'selected':'').">Cancelled</option>
                                        </select><br>
                                        <button type='submit' name='update_status' style='background:#00f2fe; color:#000; border:none; padding:5px 10px; border-radius:4px; font-weight:bold; cursor:pointer; width:100px;'>Update</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>