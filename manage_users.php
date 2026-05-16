<?php
session_start();
include 'db_connect.php'; 

// ✅ 聪明的超级保安：只有 superadmin 才能看顾客隐私！
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

$message = "";

// 处理删除顾客逻辑
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // 执行删除 SQL
    $sql_delete = "DELETE FROM customers WHERE customer_id = $delete_id";
    if (mysqli_query($conn, $sql_delete)) {
        // ✅ 跳回 manage_users.php
        header("Location: manage_users.php?deleted=1");
        exit();
    } else {
        $message = "<div class='error-msg'>⚠️ Failed to delete customer.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        .badge-vip { background: rgba(243, 156, 18, 0.1); color: var(--accent-warning); border: 1px solid rgba(243, 156, 18, 0.3); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; letter-spacing: 1px; }
        .badge-standard { background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid rgba(255, 255, 255, 0.1); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; letter-spacing: 1px; }
        .wallet-text { color: #00e676; font-weight: bold; font-size: 14px; }
        .coins-text { color: var(--accent-warning); font-size: 13px; margin-top: 3px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="GridCity PC Logo" class="sidebar-logo">
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
                <li><a href="manage_users.php" class="active" style="color: var(--accent-blue); border-left-color: var(--accent-blue);">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--accent-blue);">Customer Management</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Superadmin Access: View registered customers, VIP status, and wallet balances.</p>
            </div>
        </div>

        <?php 
        if(!empty($message)) echo $message;
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>🗑️ Customer account deleted permanently!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="8%">ID</th>
                        <th width="25%">Customer Info</th> 
                        <th width="15%">Membership</th>
                        <th width="20%">Wallet & Coins</th>
                        <th width="17%">Joined Date</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_customers = "SELECT * FROM customers ORDER BY customer_id DESC"; 
                    $res_customers = mysqli_query($conn, $sql_customers);

                    if ($res_customers && mysqli_num_rows($res_customers) > 0) {
                        while($row = mysqli_fetch_assoc($res_customers)) {
                            echo "<tr>";
                            
                            echo "<td><strong>#" . $row['customer_id'] . "</strong></td>";
                            
                            $username = !empty($row['username']) ? htmlspecialchars($row['username']) : 'No Name';
                            $email = htmlspecialchars($row['email']);
                            echo "<td>
                                    <div style='color: var(--text-main); font-weight: 600; font-size: 15px; margin-bottom: 3px;'>
                                        <i class='fas fa-user-circle' style='color: var(--text-muted); margin-right: 5px;'></i> {$username}
                                    </div>
                                    <div style='color: var(--text-muted); font-size: 12px;'>{$email}</div>
                                  </td>";
                            
                            $tier = isset($row['membership_tier']) ? $row['membership_tier'] : 'Standard';
                            $badge_class = (strtoupper($tier) == 'VIP') ? 'badge-vip' : 'badge-standard';
                            $vip_icon = (strtoupper($tier) == 'VIP') ? '<i class="fas fa-crown"></i> ' : '';
                            echo "<td><span class='{$badge_class}'>{$vip_icon}" . strtoupper($tier) . "</span></td>";
                            
                            $balance = number_format($row['wallet_balance'], 2);
                            $coins = number_format($row['reward_coins']);
                            echo "<td>
                                    <div class='wallet-text'>RM {$balance}</div>
                                    <div class='coins-text'><i class='fas fa-coins'></i> {$coins} Coins</div>
                                  </td>";
                            
                            $date = date('Y-m-d', strtotime($row['created_at']));
                            echo "<td style='color: var(--text-muted); font-size: 13px;'>{$date}</td>";
                            
                            // ✅ 这里的删除按钮也改成 manage_users.php
                            echo "<td>
                                    <a href='manage_users.php?delete_id=" . $row['customer_id'] . "' class='btn-action' style='color: var(--accent-danger); border-color: var(--accent-danger);' onclick='return confirm(\"⚠️ DANGER: Are you sure you want to delete this customer? They will lose all data, wallet balance, and order history!\");'>Delete</a>
                                  </td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 40px; color: var(--text-muted);'>No customers registered yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>