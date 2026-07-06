<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// Auto change from processing to shipped after 4 min
$conn->query("UPDATE orders SET order_status = 'Shipped' 
              WHERE order_status = 'Processing' 
              AND order_date < NOW() - INTERVAL 4 MINUTE");

// sorting & searching parameter
$search = $_GET['search'] ?? '';
$current_sort = $_GET['sort'] ?? 'desc';
$order_by = 'o.order_id DESC'; 
if ($current_sort === 'asc') $order_by = 'o.order_id ASC';
elseif ($current_sort === 'price_desc') $order_by = 'o.total_amount DESC';
elseif ($current_sort === 'price_asc') $order_by = 'o.total_amount ASC';

// Update Status Logic
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];
    $saved_sort = isset($_POST['current_sort']) ? $_POST['current_sort'] : 'desc';
    $saved_search = isset($_POST['current_search']) ? $_POST['current_search'] : '';
    
    if (in_array($new_status, $allowed)) {
        
        $check_stmt = $conn->prepare("SELECT order_status, customer_id, total_amount, coins_used FROM orders WHERE order_id = ?");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $order_info = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($order_info) {
            $current_status = $order_info['order_status'];
            $cust_id = $order_info['customer_id'];
            $refund_amount = $order_info['total_amount'];
            $refund_coins = $order_info['coins_used'];

            if ($new_status === 'Cancelled' && $current_status !== 'Cancelled') {
                
                $conn->begin_transaction();
                
                try {
                    $pay_stmt = $conn->prepare("SELECT payment_status FROM payments WHERE order_id = ?");
                    $pay_stmt->bind_param("i", $order_id);
                    $pay_stmt->execute();
                    $pay_res = $pay_stmt->get_result()->fetch_assoc();
                    $pay_stmt->close();

                    $actual_refund_amount = ($pay_res && $pay_res['payment_status'] === 'Paid') ? $refund_amount : 0;

                    if ($actual_refund_amount > 0 || $refund_coins > 0) {
                        $update_cust = $conn->prepare("UPDATE customers SET wallet_balance = wallet_balance + ?, reward_coins = reward_coins + ? WHERE customer_id = ?");
                        $update_cust->bind_param("dii", $actual_refund_amount, $refund_coins, $cust_id);
                        $update_cust->execute();
                        $update_cust->close();
                    }

                    if ($actual_refund_amount > 0) {
                        $log_stmt = $conn->prepare("INSERT INTO wallet_transactions (customer_id, type, amount, coins_earned) VALUES (?, 'Refund', ?, 0)");
                        $log_stmt->bind_param("id", $cust_id, $actual_refund_amount);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }

                    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
                    $stmt->bind_param("si", $new_status, $order_id);
                    $stmt->execute();
                    $stmt->close();

                    $items_stmt = $conn->prepare("SELECT product_id, pc_build, package_id, quantity FROM order_details WHERE order_id = ?");
                    $items_stmt->bind_param("i", $order_id);
                    $items_stmt->execute();
                    $order_items = $items_stmt->get_result();

                    while ($item = $order_items->fetch_assoc()) {
                        $ordered_qty = $item['quantity'];

                        if (!empty($item['product_id'])) {
                            $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$ordered_qty} WHERE product_id = {$item['product_id']}");
                        
                        } elseif (!empty($item['package_id'])) {
                            $pkg_id = intval($item['package_id']);
                            $pkg_items = $conn->query("SELECT product_id, quantity FROM package_items WHERE package_id = {$pkg_id}");
                            while ($p_item = $pkg_items->fetch_assoc()) {
                                $restore_qty = $p_item['quantity'] * $ordered_qty;
                                $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$restore_qty} WHERE product_id = {$p_item['product_id']}");
                            }
                        
                        } elseif (!empty($item['pc_build'])) {
                            $build_id = intval($item['pc_build']);
                            $build_items = $conn->query("SELECT product_id, quantity FROM build_items WHERE pc_build = {$build_id}");
                            while ($b_item = $build_items->fetch_assoc()) {
                                $restore_qty = $b_item['quantity'] * $ordered_qty;
                                $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$restore_qty} WHERE product_id = {$b_item['product_id']}");
                            }
                        }
                    }
                    $items_stmt->close();

                    $conn->commit();
                    header("Location: manage_orders.php?updated=1&sort=" . urlencode($saved_sort) . "&search=" . urlencode($saved_search));
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                }
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
                $stmt->bind_param("si", $new_status, $order_id);
                if ($stmt->execute()) {
                    header("Location: manage_orders.php?updated=1&sort=" . urlencode($saved_sort) . "&search=" . urlencode($saved_search));
                    exit();
                }
                $stmt->close();
            }
        }
    }
}

if (isset($_POST['process_return'])) {
    $detail_id = intval($_POST['order_detail_id']);
    $return_action = $_POST['return_action']; 
    $saved_sort = isset($_POST['current_sort']) ? $_POST['current_sort'] : 'desc';
    $saved_search = isset($_POST['current_search']) ? $_POST['current_search'] : '';

    if ($return_action === 'approve') {
        $info_stmt = $conn->prepare("
            SELECT od.unit_price, od.quantity, o.customer_id 
            FROM order_details od 
            JOIN orders o ON od.order_id = o.order_id 
            WHERE od.order_detail_id = ?
        ");
        $info_stmt->bind_param("i", $detail_id);
        $info_stmt->execute();
        $result = $info_stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $refund_amount = $row['unit_price'] * $row['quantity']; 
            $cust_id = $row['customer_id'];
            
            $conn->query("UPDATE order_details SET return_status = 'Refunded' WHERE order_detail_id = $detail_id");
            $conn->query("UPDATE customers SET wallet_balance = wallet_balance + $refund_amount WHERE customer_id = $cust_id");
            
            header("Location: manage_orders.php?updated=refund_success&sort=" . urlencode($saved_sort) . "&search=" . urlencode($saved_search));
            exit();
        }
    } elseif ($return_action === 'reject') {
        $conn->query("UPDATE order_details SET return_status = 'Rejected' WHERE order_detail_id = $detail_id");
        header("Location: manage_orders.php?updated=refund_rejected&sort=" . urlencode($saved_sort) . "&search=" . urlencode($saved_search));
        exit();
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
        .qty-badge { background: rgba(0,242,254,0.1); padding: 2px 6px; border-radius: 4px; color: #00f2fe; font-weight: 900; margin-right: 8px; font-size: 11px; border: 1px solid rgba(0,242,254,0.3); }
        .item-row { margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px dashed rgba(255,255,255,0.05); font-size: 12px; color: #cbd5e1; }
        .item-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        /* Hover Tooltip CSS */
        .tooltip-container { position: relative; display: inline-block; cursor: help; border-bottom: 1px dotted #888; padding-bottom: 2px; }
        .tooltip-text { 
            visibility: hidden; opacity: 0; width: 260px; 
            background: rgba(15, 15, 20, 0.95); color: #fff; 
            text-align: left; border-radius: 8px; padding: 15px; 
            position: absolute; z-index: 100; bottom: 125%; left: 50%; 
            transform: translateX(-50%) translateY(10px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.8); 
            border: 1px solid rgba(0,242,254,0.3); font-size: 12px; line-height: 1.5; 
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); 
            pointer-events: none;
        }
        .tooltip-text::after { 
            content: ""; position: absolute; top: 100%; left: 50%; margin-left: -6px; 
            border-width: 6px; border-style: solid; 
            border-color: rgba(0,242,254,0.3) transparent transparent transparent; 
        }
        .tooltip-container:hover .tooltip-text { 
            visibility: visible; opacity: 1; transform: translateX(-50%) translateY(0); 
        }

        /* Search Bar Clean CSS */
        .search-form-clean {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background: rgba(15, 15, 20, 0.6);
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            align-items: center;
            margin-bottom: 25px;
        }
        .search-form-clean input,
        .search-form-clean select,
        .search-form-clean button {
            height: 42px !important; 
            padding: 0 15px !important;
            font-size: 14px !important;
            line-height: normal !important;
            font-family: 'Inter', sans-serif !important;
            border-radius: 6px !important;
            outline: none !important;
            box-sizing: border-box !important;
            margin: 0 !important;
        }
        .search-form-clean input {
            flex: 1;
            min-width: 200px;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important; 
            color: #fff !important;
        }
        .search-form-clean input:focus {
            border-color: #00f2fe !important;
            box-shadow: 0 0 8px rgba(0, 242, 254, 0.2) !important;
        }
        .search-form-clean select {
            width: 180px;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important; 
            color: #fff !important;
            cursor: pointer;
        }
        .search-form-clean select option {
            background: #0a0a0a !important;
            color: #fff !important;
        }
        .search-form-clean button {
            background: linear-gradient(135deg, #00f2fe, #4facfe) !important;
            color: #000 !important;
            font-weight: bold !important;
            border: none !important;
            cursor: pointer;
            padding: 0 25px !important;
            transition: 0.2s !important;
        }
        .search-form-clean button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 242, 254, 0.4) !important;
        }
    </style>
</head>
<body>
    <div class="admin-container">
         <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-box-open"></i> Global Order Fulfillment Center</h2>
                </div>
            </header>

            <?php if (isset($_GET['updated'])) echo "<div style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'>✅ Logistics status updated successfully.</div>"; ?>

            <div class="search-wrapper">
                <form method="GET" action="manage_orders.php" class="search-form-clean">
                    <input type="text" name="search" placeholder="Search by Customer Name or Order ID..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="desc" <?php echo $current_sort == 'desc' ? 'selected' : ''; ?>>Sort: Newest First</option>
                        <option value="asc" <?php echo $current_sort == 'asc' ? 'selected' : ''; ?>>Sort: Oldest First</option>
                        <option value="price_desc" <?php echo $current_sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_asc" <?php echo $current_sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    </select>

                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    
                    <?php if(!empty($search) || $current_sort !== 'desc'): ?>
                        <a href="manage_orders.php" style="color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); text-decoration: none; padding: 0 15px; border-radius: 6px; font-weight: bold; display: flex; align-items: center; height: 42px; transition: 0.3s; background: rgba(255,77,77,0.1);" onmouseover="this.style.background='rgba(255,77,77,0.2)'" onmouseout="this.style.background='rgba(255,77,77,0.1)'">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px; color:#64748b; font-size:12px;">ID</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Customer</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Date</th>
                            <th style="padding:15px; color:#64748b; font-size:12px; width:30%;">Items Purchased</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Total</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Payment</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Status</th>
                            <th style="padding:15px; color:#64748b; font-size:12px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($search !== '') {
                            $keywords = explode(' ', trim($search));
                            $conditions = [];
                            $params = [];
                            $types = "";
                            
                            foreach ($keywords as $kw) {
                                if (trim($kw) !== '') {
                                    $conditions[] = "(c.username LIKE ? OR o.order_id LIKE ?)";
                                    $params[] = "%" . trim($kw) . "%";
                                    $params[] = "%" . trim($kw) . "%";
                                    $types .= "ss";
                                }
                            }
                            
                            $where_clause = implode(" AND ", $conditions);
                            // Join payments table
                            $sql = "SELECT o.*, c.username, p.payment_status 
                                    FROM orders o 
                                    JOIN customers c ON o.customer_id = c.customer_id 
                                    LEFT JOIN payments p ON o.order_id = p.order_id 
                                    WHERE $where_clause ORDER BY $order_by";
                            
                            $stmt = $conn->prepare($sql);
                            if (!empty($params)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                        } else {
                            // Join payments table
                            $sql = "SELECT o.*, c.username, p.payment_status 
                                    FROM orders o 
                                    JOIN customers c ON o.customer_id = c.customer_id 
                                    LEFT JOIN payments p ON o.order_id = p.order_id 
                                    ORDER BY $order_by";
                            $res = $conn->query($sql);
                        }

                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $status = $row['order_status'];
                                $status_color = "#facc15"; 
                                
                                if ($status == 'Processing') $status_color = "#00f2fe";
                                elseif ($status == 'Shipped') $status_color = "#a855f7";
                                elseif ($status == 'Delivered') $status_color = "#3b82f6";
                                elseif ($status == 'Completed') $status_color = "#00e676";
                                elseif ($status == 'Cancelled') $status_color = "#ff4d4d";

                                // Payment status handling
                                $pay_status = empty($row['payment_status']) ? 'Unpaid' : $row['payment_status'];
                                $pay_color = (strtolower($pay_status) === 'paid') ? '#00e676' : '#ff4d4d';

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px; font-family: JetBrains Mono;'>#{$row['order_id']}</td>";
                                echo "<td style='padding:15px; font-weight:bold;'>" . htmlspecialchars($row['username']) . "</td>";
                                
                                // calculate date, realtime & pass time
                                $order_time = strtotime($row['order_date']);
                                $time_diff = time() - $order_time;
                                $days = floor($time_diff / 86400);
                                $hours = floor(($time_diff % 86400) / 3600);
                                $mins = floor(($time_diff % 3600) / 60);
                                
                                $time_elapsed = "";
                                if ($days > 0) $time_elapsed = "{$days} Days, {$hours} Hrs ago";
                                elseif ($hours > 0) $time_elapsed = "{$hours} Hrs, {$mins} Mins ago";
                                else $time_elapsed = "{$mins} Mins ago";

                                $exact_time = date('h:i:s A', $order_time);
                                $date_display = date('d M, Y', $order_time);
                                
                                // showing address
                                $address = $row['shipping_address'] ?? 'Check customer profile for full address details.';
                                
                                // Tooltip HTML
                                echo "<td style='padding:15px; color:#888; font-size:12px;'>
                                        <div class='tooltip-container'>
                                            <i class='far fa-clock'></i> {$date_display}
                                            <div class='tooltip-text'>
                                                <div style='margin-bottom:8px;'>
                                                    <strong style='color:#00f2fe;'><i class='fas fa-map-marker-alt'></i> Shipping Address:</strong><br>
                                                    <span style='color:#cbd5e1;'>" . htmlspecialchars($address) . "</span>
                                                </div>
                                                <div style='margin-bottom:8px;'>
                                                    <strong style='color:#00f2fe;'><i class='fas fa-calendar-check'></i> Exact Time:</strong><br>
                                                    <span style='color:#cbd5e1;'>{$date_display} at {$exact_time}</span>
                                                </div>
                                                <div>
                                                    <strong style='color:#00f2fe;'><i class='fas fa-stopwatch'></i> Time Elapsed:</strong><br>
                                                    <span style='color:#ff4d4d; font-weight:bold;'>{$time_elapsed}</span>
                                                </div>
                                            </div>
                                        </div>
                                      </td>";
                                
                                echo "<td style='padding:15px;'>";
                                $order_id_val = $row['order_id'];
                                $sql_items = "SELECT od.order_detail_id, od.quantity, od.unit_price, od.return_status, od.return_reason, od.return_image, p.product_name, pkg.package_name, sb.build_name 
                                              FROM order_details od 
                                              LEFT JOIN products p ON od.product_id = p.product_id 
                                              LEFT JOIN packages pkg ON od.package_id = pkg.package_id 
                                              LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build 
                                              WHERE od.order_id = $order_id_val";
                                $res_items = $conn->query($sql_items);
                                
                                while($item = $res_items->fetch_assoc()) {
                                    $name = $item['product_name'] ?: ($item['package_name'] ? "[Package] ".$item['package_name'] : "[Custom PC] ".$item['build_name']);
                                    echo "<div class='item-row'><span class='qty-badge'>{$item['quantity']}x</span> ".htmlspecialchars($name);
                                    
                                    if (!empty($item['return_status'])) {
                                        $bg_color = $item['return_status'] == 'Refunded' ? 'rgba(0, 230, 118, 0.05)' : ($item['return_status'] == 'Rejected' ? 'rgba(255, 77, 77, 0.05)' : 'rgba(235, 94, 40, 0.05)');
                                        $border_color = $item['return_status'] == 'Refunded' ? '#00e676' : ($item['return_status'] == 'Rejected' ? '#ff4d4d' : '#eb5e28');
                                        
                                        echo "<div style='margin-top:6px; margin-bottom:12px; margin-left:32px; padding:8px 12px; background:{$bg_color}; border-left:2px solid {$border_color}; border-radius:0 4px 4px 0;'>";
                                        echo "<div style='display:flex; justify-content:space-between; align-items:flex-start;'>";
                                        echo "<div>";
                                        echo "<strong style='color:{$border_color}; font-size:11px;'><i class='fa-solid fa-triangle-exclamation'></i> Return: " . htmlspecialchars($item['return_status']) . "</strong><br>";
                                        echo "<span style='color:#888; font-size:11px;'>Reason: <span style='color:#bbb;'>" . htmlspecialchars($item['return_reason']) . "</span></span>";
                                        echo "</div>";
                                        
                                        if (!empty($item['return_image'])) {
                                            echo "<a href='{$item['return_image']}' target='_blank' style='color:#00f2fe; text-decoration:none; font-size:11px; padding:4px 8px; border:1px solid rgba(0,242,254,0.3); border-radius:4px; transition:0.3s;' onmouseover='this.style.background=\"rgba(0,242,254,0.1)\"' onmouseout='this.style.background=\"transparent\"'><i class='fa-solid fa-image'></i> Evidence</a>";
                                        }
                                        echo "</div>";
                                        
                                        if ($item['return_status'] === 'Pending') {
                                            $refund_val = number_format($item['unit_price'] * $item['quantity'], 2);
                                            echo "<div style='margin-top:10px; display:flex; gap:8px;'>";
                                            echo "<form method='POST' style='margin:0;' onsubmit=\"return confirm('Approve this return and refund RM {$refund_val} to the customer wallet?');\">
                                                    <input type='hidden' name='order_detail_id' value='{$item['order_detail_id']}'>
                                                    <input type='hidden' name='return_action' value='approve'>
                                                    <input type='hidden' name='current_sort' value='{$current_sort}'>
                                                    <input type='hidden' name='current_search' value='{$search}'>
                                                    <button type='submit' name='process_return' style='background:transparent; color:#00e676; border:1px solid #00e676; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:11px; font-weight:bold; transition:0.3s;' onmouseover='this.style.background=\"rgba(0,230,118,0.1)\"' onmouseout='this.style.background=\"transparent\"'><i class='fa-solid fa-check'></i> Approve (Refund RM {$refund_val})</button>
                                                  </form>";
                                                  
                                            echo "<form method='POST' style='margin:0;' onsubmit=\"return confirm('Reject this return request?');\">
                                                    <input type='hidden' name='order_detail_id' value='{$item['order_detail_id']}'>
                                                    <input type='hidden' name='return_action' value='reject'>
                                                    <input type='hidden' name='current_sort' value='{$current_sort}'>
                                                    <input type='hidden' name='current_search' value='{$search}'>
                                                    <button type='submit' name='process_return' style='background:transparent; color:#ff4d4d; border:1px solid #ff4d4d; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:11px; font-weight:bold; transition:0.3s;' onmouseover='this.style.background=\"rgba(255,77,77,0.1)\"' onmouseout='this.style.background=\"transparent\"'><i class='fa-solid fa-xmark'></i> Reject</button>
                                                  </form>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                    }
                                    echo "</div>";
                                }
                                echo "</td>";
                                
                                echo "<td style='padding:15px; color:#00e676; font-weight:bold; font-family: JetBrains Mono;'>RM " . number_format($row['total_amount'], 2) . "</td>";
                                echo "<td style='padding:15px; font-weight:bold; color:{$pay_color}'>" . htmlspecialchars($pay_status) . "</td>";
                                echo "<td style='padding:15px; font-weight:bold; color:$status_color'>$status</td>";
                                echo "<td style='padding:15px;'>
                                        <form method='POST'>
                                            <input type='hidden' name='order_id' value='{$row['order_id']}'>
                                            <input type='hidden' name='current_sort' value='$current_sort'>
                                            <input type='hidden' name='current_search' value='$search'>
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
                        } else {
                            echo "<tr><td colspan='8' style='padding:30px; text-align:center; color:#888; font-size: 16px;'>No orders match your search.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>