<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}


$auto_suspend_sql = "UPDATE customers SET status = 'Inactive' WHERE (status = 'Active' OR account_status = 'Active') AND last_login < DATE_SUB(NOW(), INTERVAL 60 DAY)";
$conn->query($auto_suspend_sql);



if (isset($_GET['toggle_id']) && isset($_GET['new_status'])) {
    $toggle_id = intval($_GET['toggle_id']);
    $new_status = $_GET['new_status'] === 'Inactive' ? 'Inactive' : 'Active';
    

    $stmt = $conn->prepare("UPDATE customers SET status = ?, last_login = NOW() WHERE customer_id = ?");
    $stmt->bind_param("si", $new_status, $toggle_id);
    if ($stmt->execute()) {

        $search_redirect = isset($_GET['search']) ? "&search=" . urlencode($_GET['search']) : "";
        header("Location: manage_users.php?msg=status_updated" . $search_redirect);
        exit();
    }
    $stmt->close();
}


$search_query = "";
$search_param = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_param = trim($_GET['search']);
   
    $search_escaped = $conn->real_escape_string($search_param);
    // can search ID, Username, Email
    $search_query = " WHERE c.customer_id LIKE '%$search_escaped%' 
                      OR c.username LIKE '%$search_escaped%' 
                      OR c.email LIKE '%$search_escaped%' ";
}

$message = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Information - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 18px 20px; color:#ffd700; font-size: 12px; text-transform: uppercase; background: rgba(255,215,0,0.05); border-bottom: 2px solid rgba(255,215,0,0.2); letter-spacing: 1px; }
        .cyber-table td { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; vertical-align: middle; }
        
        .status-badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border: 1px solid; line-height: 1; }

        .admin-sidebar ul.sidebar-menu li a:hover {
            background-color: rgba(0, 242, 254, 0.05) !important;
            color: #00f2fe !important;
            border-left: 4px solid #00f2fe !important;
            text-shadow: 0 0 10px rgba(0, 242, 254, 0.4) !important;
        }

        .action-buttons { display: flex; gap: 10px; justify-content: flex-end; align-items: center; }
        

        .search-bar-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: rgba(0,0,0,0.3); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .search-input { flex: 1; padding: 12px 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.5); color: #fff; outline: none; font-family: 'Inter', sans-serif; transition: 0.3s; }
        .search-input:focus { border-color: #00f2fe; box-shadow: 0 0 10px rgba(0,242,254,0.2); }
        .search-btn { padding: 12px 20px; background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .search-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px rgba(0,242,254,0.4); }
        .clear-btn { padding: 12px 15px; background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid #ff4d4d; border-radius: 6px; cursor: pointer; text-decoration: none; transition: 0.3s; display: flex; align-items: center; justify-content: center;}
        .clear-btn:hover { background: #ff4d4d; color: #000; box-shadow: 0 0 15px rgba(255,77,77,0.4); }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #ffd700; margin:0;"><i class="fas fa-users"></i> Customer Information</h2>
            </header>
            
            <?php 
            if (isset($_GET['msg']) && $_GET['msg'] == 'status_updated') {
                echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3);'><i class='fas fa-check-circle'></i> Customer account status updated successfully.</div>";
            }
            ?>

            <div class="search-bar-container">
                <form method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 500px;">
                    <input type="text" name="search" class="search-input" placeholder="Search by ID, Name, or Email..." value="<?php echo htmlspecialchars($search_param); ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search_param)): ?>
                        <a href="manage_users.php" class="clear-btn" title="Clear Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
                
                <?php if(!empty($search_param)): ?>
                    <div style="color: #888; font-size: 13px;">
                        Showing results for: <strong style="color: #00f2fe;"><?php echo htmlspecialchars($search_param); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name / Email</th>
                        <th>Order Activity</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $sql = "SELECT c.*, 
                            (SELECT COUNT(*) FROM orders WHERE customer_id = c.customer_id) as total_orders,
                            (SELECT order_status FROM orders WHERE customer_id = c.customer_id ORDER BY order_date DESC LIMIT 1) as latest_status
                            FROM customers c 
                            $search_query 
                            ORDER BY c.customer_id DESC";
                    $res = $conn->query($sql);
                    
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $uid = $row['customer_id'];
                            $total = $row['total_orders'];
                            $status = $row['latest_status'];
                            
                            $account_status = isset($row['status']) ? $row['status'] : (isset($row['account_status']) ? $row['account_status'] : 'Active'); 

                            $bg = "rgba(255,255,255,0.05)"; $col = "#888";
                            if ($status == 'Pending') { $bg = "rgba(250, 204, 21, 0.1)"; $col = "#facc15"; }
                            elseif ($status == 'Processing') { $bg = "rgba(0, 242, 254, 0.1)"; $col = "#00f2fe"; }
                            elseif ($status == 'Shipped') { $bg = "rgba(168, 85, 247, 0.1)"; $col = "#a855f7"; }
                            elseif ($status == 'Completed') { $bg = "rgba(0, 230, 118, 0.1)"; $col = "#00e676"; }
                            elseif ($status == 'Cancelled') { $bg = "rgba(255, 77, 77, 0.1)"; $col = "#ff4d4d"; }

                            $row_opacity = (strtolower($account_status) === 'inactive') ? '0.6' : '1';

                            echo "<tr style='opacity: {$row_opacity};'>";
                            echo "<td style='color:#64748b; font-family:JetBrains Mono;'>USR-".$uid."</td>";
                            
                            echo "<td>
                                    <strong>".htmlspecialchars($row['username'])."</strong>";
                            
                            if (strtolower($account_status) === 'inactive') {
                                echo " <span style='background:rgba(255,77,77,0.1); color:#ff4d4d; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; border: 1px solid rgba(255,77,77,0.3);'>INACTIVE</span>";
                            } else {
                                echo " <span style='background:rgba(0,230,118,0.1); color:#00e676; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; border: 1px solid rgba(0,230,118,0.3);'>ACTIVE</span>";
                            }
                            
                          
                            $last_login_display = isset($row['last_login']) ? date('d M Y', strtotime($row['last_login'])) : 'N/A';
                            echo "<br><span style='color:#64748b; font-size:12px;'>".htmlspecialchars($row['email'])."</span>
                                  <br><span style='color:#888; font-size:11px;'><i class='fas fa-clock'></i> Last Login: {$last_login_display}</span>
                                  </td>";
                            
                            echo "<td>
                                    <div style='display: flex; flex-direction: row; align-items: center; gap: 15px;'>
                                        <span style='background:rgba(255,215,0,0.1); color:#ffd700; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; white-space: nowrap;'>$total Orders</span>";
                            if($total > 0) {
                                echo "  <div style='display: flex; align-items: center; gap: 6px;'>
                                            <span style='font-size:11px; color:#64748b; font-weight:bold;'>Latest:</span>
                                            <span class='status-badge' style='background:$bg; color:$col; border-color:$col;'>".($status ?: 'N/A')."</span>
                                        </div>";
                            } else {
                                echo "  <span style='font-size:11px; color:#64748b; font-style:italic;'>No activity</span>";
                            }
                            echo "  </div>
                                  </td>";

                            echo "<td style='text-align:right;'>
                                    <div class='action-buttons'>";
                            
                        
                            $search_append = !empty($search_param) ? "&search=" . urlencode($search_param) : "";

                            if (strtolower($account_status) === 'inactive') {
                                echo "<a href='manage_users.php?toggle_id=$uid&new_status=Active{$search_append}' onclick=\"return confirm('Reactivate this customer account? This will also reset their 60-day timer.');\" style='background:transparent; color:#00e676; border:1px solid #00e676; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;' onmouseover=\"this.style.background='rgba(0,230,118,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-user-check'></i> Set Active</a>";
                            } else {
                                echo "<a href='manage_users.php?toggle_id=$uid&new_status=Inactive{$search_append}' onclick=\"return confirm('Suspend this customer account? They will not be able to log in.');\" style='background:transparent; color:#ff4d4d; border:1px solid #ff4d4d; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;' onmouseover=\"this.style.background='rgba(255,77,77,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-user-slash'></i> Set Inactive</a>";
                            }

                            echo "      <a href='view_customer.php?id=$uid' style='background:transparent; color:#00f2fe; border:1px solid #00f2fe; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;' onmouseover=\"this.style.background='rgba(0,242,254,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-eye'></i> Details</a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                    
                        echo "<tr><td colspan='4' style='text-align:center; padding: 40px; color:#888;'><i class='fas fa-search' style='font-size:2rem; margin-bottom:10px; display:block; color:#444;'></i> No customers found matching your search.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>