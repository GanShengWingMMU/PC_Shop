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
        /* Modal 訂單明細彈窗樣式 */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); }
        .modal-content { background: #0b0b12; margin: 5% auto; padding: 30px; border: 1px solid #00f2fe; width: 60%; max-width: 800px; border-radius: 12px; box-shadow: 0 0 30px rgba(0,242,254,0.2); color: #fff; max-height: 80vh; overflow-y: auto; }
        .close-btn { float: right; font-size: 28px; cursor: pointer; color: #888; transition: 0.3s; }
        .close-btn:hover { color: #ff4d4d; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-family: 'Inter', sans-serif; }
        .detail-table th, .detail-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .detail-table th { color: #00f2fe; font-size: 13px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
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
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-box-open"></i> Global Order Fulfillment Center</h2>
                <p style="color: #64748b; margin-top:5px;">Review financial totals, inspect package details, and process shipments.</p>
            </header>

            <?php if (isset($_GET['updated'])) echo "<div style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'>✅ Logistics status updated successfully.</div>"; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px; color:#00f2fe;">Order ID</th>
                            <th style="padding:15px; color:#00f2fe;">Customer</th>
                            <th style="padding:15px; color:#00f2fe;">Amount</th>
                            <th style="padding:15px; color:#00f2fe;">Shipping Address</th>
                            <th style="padding:15px; color:#00f2fe;">Date</th>
                            <th style="padding:15px; color:#00f2fe;">Status</th>
                            <th style="padding:15px; color:#00f2fe;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, c.username, c.email FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_date DESC";
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $order_id = $row['order_id'];
                                $status = $row['order_status'];
                                
                                $status_color = "#facc15";
                                if ($status == 'Processing') $status_color = "#00f2fe";
                                elseif ($status == 'Shipped') $status_color = "#a855f7";
                                elseif ($status == 'Completed') $status_color = "#00e676";
                                elseif ($status == 'Cancelled') $status_color = "#ff4d4d";

                                $address = $row['shipping_address'] ?? $row['delivery_address'] ?? 'Pickup / Digital';
                                
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.2s;' onmouseover='this.style.background=\"rgba(255,255,255,0.02)\"' onmouseout='this.style.background=\"none\"'>";
                                echo "<td style='padding:15px; font-family:\"JetBrains Mono\"; text-shadow:0 0 10px rgba(0,242,254,0.3);'>#$order_id</td>";
                                echo "<td style='padding:15px;'><strong>" . htmlspecialchars($row['username']) . "</strong><br><span style='font-size:12px; color:#64748b;'>" . htmlspecialchars($row['email']) . "</span></td>";
                                echo "<td style='padding:15px; color:#00e676; font-weight:bold; font-family:\"JetBrains Mono\";'>RM " . number_format($row['total_amount'], 2) . "</td>";
                                echo "<td style='padding:15px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;' title='".htmlspecialchars($address)."'>" . htmlspecialchars($address) . "</td>";
                                echo "<td style='padding:15px; color:#94a3b8; font-size:13px;'>" . date('d M Y, H:i', strtotime($row['order_date'])) . "</td>";
                                echo "<td style='padding:15px; font-weight:bold; color:$status_color'>$status</td>";
                                
                                echo "<td style='padding:15px;'>
                                        <div style='display:flex; gap:10px; align-items:center;'>
                                            <button type='button' class='btn-action' style='color:#00f2fe; border-color:#00f2fe; padding:6px 12px; font-size:12px; cursor:pointer;' onclick='showOrderDetails($order_id)'>Details</button>
                                            <a href='invoice.php?order_id=$order_id' target='_blank' class='btn-action' style='color:#ffd700; border-color:#ffd700; padding:6px 12px; font-size:12px; text-decoration:none;'>Invoice</a>
                                            <form method='POST' style='display:flex; gap:5px;'>
                                                <input type='hidden' name='order_id' value='$order_id'>
                                                <select name='new_status' style='background:#000; color:#fff; border:1px solid #333; padding:5px; border-radius:4px; font-size:12px;'>
                                                    <option value='Pending' ".($status=='Pending'?'selected':'').">Pending</option>
                                                    <option value='Processing' ".($status=='Processing'?'selected':'').">Processing</option>
                                                    <option value='Shipped' ".($status=='Shipped'?'selected':'').">Shipped</option>
                                                    <option value='Completed' ".($status=='Completed'?'selected':'').">Completed</option>
                                                    <option value='Cancelled' ".($status=='Cancelled'?'selected':'').">Cancelled</option>
                                                </select>
                                                <button type='submit' name='update_status' class='btn-action' style='padding:5px 10px; font-size:12px; background:#00f2fe; color:#000; cursor:pointer;'>Go</button>
                                            </form>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:40px; color:#64748b;'><i class='fas fa-box-open' style='font-size: 24px; display:block; margin-bottom:10px;'></i> No customer orders found in the database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 style="color:#00f2fe; margin-top:0; font-size:1.5rem;" id="modalTitle">Order Manifest</h3>
            <div id="modalBody" style="margin-top: 20px;">Loading manifest variables...</div>
        </div>
    </div>

    <script>
    function showOrderDetails(orderId) {
        document.getElementById('modalTitle').innerText = "Order Manifest Details #" + orderId;
        document.getElementById('modalBody').innerHTML = "<p style='color:#888; text-align:center; padding:20px;'><i class='fas fa-spinner fa-spin fa-2x'></i><br><br>Querying database columns...</p>";
        document.getElementById('detailsModal').style.display = "block";

        fetch('manage_orders.php?get_items=1&order_id=' + orderId)
            .then(response => response.text())
            .then(data => {
                document.getElementById('modalBody').innerHTML = data;
            });
    }
    function closeModal() { document.getElementById('detailsModal').style.display = "none"; }
    window.onclick = function(event) { if (event.target == document.getElementById('detailsModal')) closeModal(); }
    </script>
</body>
</html>
<?php
// 🌟 處理明細非同步請求 (這段必須留在檔案最底部)
if (isset($_GET['get_items']) && isset($_GET['order_id'])) {
    ob_clean();
    $oid = intval($_GET['order_id']);
    
    $sql_items = "SELECT oi.quantity, oi.price_at_purchase, p.product_name, p.image_url 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.product_id 
                  WHERE oi.order_id = $oid";
    $res_items = $conn->query($sql_items);
    
    echo "<table class='detail-table'>";
    echo "<thead><tr><th>Item Photo</th><th>Component Name</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>";
    
    if($res_items && $res_items->num_rows > 0) {
        while($item = $res_items->fetch_assoc()) {
            $img = htmlspecialchars($item['image_url']);
            if(empty($img)) $img = 'image/placeholder_pc.png';

            echo "<tr style='transition: 0.2s;' onmouseover='this.style.background=\"rgba(255,255,255,0.02)\"' onmouseout='this.style.background=\"none\"'>";
            echo "<td><img src='{$img}' style='height:50px; width:50px; object-fit:cover; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:#000;'></td>";
            echo "<td style='font-weight:600; color:#fff;'>".htmlspecialchars($item['product_name'])."</td>";
            echo "<td style='font-family:\"JetBrains Mono\"; color:#a855f7; font-weight:bold;'>x".$item['quantity']."</td>";
            echo "<td style='color:#00e676; font-weight:bold; font-family:\"JetBrains Mono\";'>RM ".number_format($item['price_at_purchase'] * $item['quantity'], 2)."</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4' style='color:#ff4d4d; text-align:center; padding:20px;'>No components mapped to this order matrix.</td></tr>";
    }
    echo "</tbody></table>";
    exit();
}
?>