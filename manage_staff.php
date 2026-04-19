<?php
session_start();
include 'db_connect.php'; 

// 🌟 终极防线：如果不是 superadmin，直接无情踢回控制台！
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

// 处理删除 Admin 的逻辑
$message = "";
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // 防止老板不小心把自己给删了
    if ($delete_id == $_SESSION['user_id']) {
        $message = "<div style='color: #f39c12; background: rgba(243,156,18,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>⚠️ You cannot delete your own superadmin account!</div>";
    } else {
        $sql_delete = "DELETE FROM users WHERE user_id = $delete_id AND role = 'admin'";
        if (mysqli_query($conn, $sql_delete)) {
            header("Location: manage_staff.php?deleted=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>PC SHOP</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php">Build System</a></li>
            
            <li><a href="manage_staff.php" class="active" style="color: var(--accent-warning); border-left-color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
            <li><a href="manage_users.php">Manage Customers</a></li>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--accent-warning);">Staff Management</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Superadmin Access: Manage store admins and staff accounts.</p>
            </div>
           <a href="add_staff.php" class="btn-primary" style="background: linear-gradient(135deg, #f39c12, #e67e22); padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">
    <i class="fas fa-user-plus"></i> Add New Staff
</a>
        </div>

       <?php 
        if(!empty($message)) echo $message;
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ Staff account deleted successfully!</div>";
        }
       
        if(isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div style='color: #00f2fe; background: rgba(0,242,254,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,242,254,0.3);'>🎉 New staff account created successfully!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="10%">User ID</th>
                        <th width="30%">Username</th> 
                        <th width="30%">Email</th>
                        <th width="15%">Role</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🌟 只抓出 admin 和 superadmin
                    $sql_staff = "SELECT * FROM users WHERE role IN ('admin', 'superadmin') ORDER BY role DESC, user_id ASC"; 
                    $res_staff = mysqli_query($conn, $sql_staff);

                    if ($res_staff && mysqli_num_rows($res_staff) > 0) {
                        while($row = mysqli_fetch_assoc($res_staff)) {
                            echo "<tr>";
                            echo "<td>#" . $row['user_id'] . "</td>";
                            
                            // 给老板加上皇冠图标 👑
                            $crown = ($row['role'] == 'superadmin') ? " <i class='fas fa-crown' style='color: var(--accent-warning);'></i>" : "";
                            echo "<td><strong style='color: var(--text-main);'>" . htmlspecialchars($row['username']) . $crown . "</strong></td>";
                            
                            $email = !empty($row['email']) ? htmlspecialchars($row['email']) : '<span style="color: var(--text-muted);">No email</span>';
                            echo "<td>{$email}</td>";
                            
                            $role_badge = ($row['role'] == 'superadmin') ? "background: rgba(243,156,18,0.2); color: var(--accent-warning);" : "background: rgba(0,242,254,0.1); color: var(--accent-blue);";
                            echo "<td><span style='padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; {$role_badge}'>" . $row['role'] . "</span></td>";
                            
                            echo "<td>";
                            // 老板不能删老板，只能删普通的 admin
                            if ($row['role'] !== 'superadmin') {
                                echo "<div style='display:flex; gap:8px;'>
                                        <a href='#' class='btn-action'>Edit</a>
                                        <a href='manage_staff.php?delete_id=" . $row['user_id'] . "' class='btn-action' style='color: var(--accent-danger); border-color: var(--accent-danger);' onclick='return confirm(\"⚠️ Are you SURE you want to fire this admin?\");'>Fire</a>
                                      </div>";
                            } else {
                                echo "<span style='color: var(--text-muted); font-size: 0.85rem;'>Owner</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color: var(--text-muted);'>No staff accounts found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>