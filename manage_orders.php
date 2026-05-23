<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 🌟 更新訂單狀態
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    $allowed = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
    
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            header("Location: manage_orders.php?updated=1");
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
        /* 🌟 订单专属悬浮黑科技面板 */
        .order-tooltip-container { position: relative; cursor: help; }
        .order-tooltip {
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%) translateY(10px);
            background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(10px);
            border: 1px solid #00f2fe; border-radius: 8px; padding: 15px;
            width: max-content; max-width: 320px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px rgba(0,242,254,0.2);
            opacity: 0; visibility: hidden; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 100; pointer-events: none; text-align: left;
        }
        .order-tooltip::after {
            content: ''; position: absolute; top: 100%; left: 50%; margin-left: -6px;
            border-width: 6px; border-style: solid; border-color: #00f2fe transparent transparent transparent;
        }
        .order-tooltip-container:hover .order-tooltip { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(-10px); }
        .tt-title { color: #00f2fe; font-size: 11px; text-transform: uppercase; font-weight: 800; margin-bottom: 5px; border-bottom: 1px dashed rgba(255,255,255,0.2); padding-bottom: 5px; letter-spacing: 1px;}
        .tt-info { color: #fff; font-size: 13px; margin-bottom: 4px; line-height: 1.5; font-family: 'JetBrains Mono', monospace; }
        .qty-badge { background: rgba(255,255,255,0.1); padding: 2px 5px; border-radius: 3px; color: var(--accent-blue); font-weight: bold; margin-right: 5px; }
        .item-row { margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px dashed rgba(255,255,255,0.1); }
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
                <li><a href="admin_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_staff.php') echo 'class="active"'; ?>><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_users.php') echo 'class="active"'; ?>>Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_categories.php') echo 'class="active"'; ?>>Categories</a></li>
                <li><a href="manage_products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_products.php') echo 'class="active"'; ?>>Products</a></li> 
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-box-open"></i> Global Order Fulfillment Center</h2>
                <p style="color: #64748b; margin-top:5px;">Hover over Date to see precise timestamps and shipping coordinates.</p>
            </header>

            <?php if (isset($_GET['updated'])) echo "<div style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'>✅ Logistics status updated successfully.</div>"; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px;">ID</th>
                            <th style="padding:15px;">Customer</th>
                            <th style="padding:15px;">Date</th>
                            <th style="padding:15px;">Items Purchased</th>
                            <th style="padding:15px;">Total</th>
                            <th style="padding:15px;">Status</th>
                            <th style="padding:15px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, c.username FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_id DESC";
                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()) {
                            $order_id = $row['order_id'];
                            $status = $row['order_status'];
                            $status_color = "#facc15";
                            if ($status == 'Processing') $status_color = "#00f2fe";
                            elseif ($status == 'Shipped') $status_color = "#a855f7";
                            elseif ($status == 'Completed') $status_color = "#00e676";
                            elseif ($status == 'Cancelled') $status_color = "#ff4d4d";

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding:15px;'>#$order_id</td>";
                            echo "<td style='padding:15px;'><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
                            
                            // 🌟 核心：Hover 日期列
                            echo "<td style='padding:15px;' class='order-tooltip-container'>
                                    <span style='border-bottom: 1px dotted #00f2fe; color:#cbd5e1;'>" . date('d M, Y', strtotime($row['order_date'])) . "</span>
                                    <div class='order-tooltip'>
                                        <div class='tt-title'><i class='fas fa-clock'></i> Exact Order Time</div>
                                        <div class='tt-info' style='color: #00e676;'>" . date('d M Y, h:i:s A', strtotime($row['order_date'])) . "</div>
                                        <div class='tt-title' style='margin-top: 10px;'><i class='fas fa-map-marker-alt'></i> Shipping Address</div>
                                        <div class='tt-info' style='color:#aaa; font-size:12px; font-family:Inter; white-space:pre-wrap;'>" . htmlspecialchars($row['shipping_address']) . "</div>
                                    </div>
                                  </td>";
                            
                            echo "<td><div style='font-size: 12px; color: #fff;'>";
                            $sql_items = "SELECT od.quantity, p.product_name, pkg.package_name, sb.build_name FROM order_details od LEFT JOIN products p ON od.product_id = p.product_id LEFT JOIN packages pkg ON od.package_id = pkg.package_id LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build WHERE od.order_id = $order_id";
                            $res_items = $conn->query($sql_items);
                            while($item = $res_items->fetch_assoc()) {
                                $name = $item['product_name'] ?: ($item['package_name'] ? "[PKG] ".$item['package_name'] : "[DIY] ".$item['build_name']);
                                echo "<div class='item-row'><span class='qty-badge'>{$item['quantity']}x</span> ".htmlspecialchars($name)."</div>";
                            }
                            echo "</div></td>";
                            echo "<td style='padding:15px; color:#00e676; font-weight:bold;'>RM ".number_format($row['total_amount'], 2)."</td>";
                            echo "<td style='padding:15px; font-weight:bold; color:$status_color'>$status</td>";
                            echo "<td style='padding:15px;'>
                                    <form method='POST' style='display:flex; gap:5px;'>
                                        <input type='hidden' name='order_id' value='$order_id'>
                                        <select name='new_status' style='background:#000; color:#fff; border:1px solid #333; padding:5px; border-radius:4px; font-size:12px;'>
                                            <option value='Pending' ".($status=='Pending'?'selected':'').">Pending</option>
                                            <option value='Processing' ".($status=='Processing'?'selected':'').">Processing</option>
                                            <option value='Shipped' ".($status=='Shipped'?'selected':'').">Shipped</option>
                                            <option value='Completed' ".($status=='Completed'?'selected':'').">Completed</option>
                                            <option value='Cancelled' ".($status=='Cancelled'?'selected':'').">Cancelled</option>
                                        </select>
                                        <button type='submit' name='update_status' class='btn-action' style='padding:5px 10px; font-size:12px; background:#00f2fe; color:#000;'>Go</button>
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