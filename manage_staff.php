<?php
session_start();
include 'db_connect.php'; 

// ✅ 聪明的超级保安：只有 superadmin (不管大小写) 才能进！
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

$message = "";

// 处理删除员工逻辑
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // 保护机制：防止老板不小心把自己（ID为1的初始账号）给删了！
    if ($delete_id == 1) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ Action Denied: You cannot delete the primary SuperAdmin account!</div>";
    } else {
        // ✅ 改成了 admin_id
        $sql_delete = "DELETE FROM admins WHERE admin_id = $delete_id";
        if (mysqli_query($conn, $sql_delete)) {
            header("Location: manage_staff.php?deleted=1");
            exit();
        } else {
            $message = "<div class='error-msg'>⚠️ Failed to delete staff.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
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
            <a href="add_staff.php" class="quick-action-btn" style="background: linear-gradient(135deg, #f39c12, #e67e22); font-size: 15px; border: none; padding: 12px 20px;">
                <i class="fas fa-user-plus"></i> Add New Staff
            </a>
        </div>

        <?php 
        if(!empty($message)) echo $message;
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>🗑️ Staff account removed successfully!</div>";
        }
        if(isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ New staff account created successfully!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="10%">Admin ID</th>
                        <th width="25%">Username</th> 
                        <th width="30%">Email</th>
                        <th width="15%">Role</th>
                        <th width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ✅ 核心修复：用 admin_id 来排序！
                    $sql_staff = "SELECT * FROM admins ORDER BY admin_id ASC"; 
                    $res_staff = mysqli_query($conn, $sql_staff);

                    if ($res_staff && mysqli_num_rows($res_staff) > 0) {
                        while($row = mysqli_fetch_assoc($res_staff)) {
                            echo "<tr>";
                            // ✅ 核心修复：输出 admin_id
                            echo "<td><strong>#" . $row['admin_id'] . "</strong></td>";
                            
                            echo "<td><i class='fas fa-user-shield' style='color: var(--text-muted); margin-right: 8px;'></i> " . htmlspecialchars($row['username']) . "</td>";
                            
                            $email = !empty($row['email']) ? htmlspecialchars($row['email']) : '<span style="color:#666; font-style:italic;">No email</span>';
                            echo "<td>" . $email . "</td>";
                            
                            $role = $row['role'];
                            $role_color = (strtolower($role) == 'superadmin') ? 'color: var(--accent-warning); font-weight: bold;' : 'color: var(--accent-blue);';
                            echo "<td style='$role_color'>" . strtoupper($role) . "</td>";
                            
                            echo "<td>
                                    <div style='display:flex; gap:8px;'>";
                            
                            // 保护机制：如果是老板本人（ID=1），不允许删除按钮
                            if ($row['admin_id'] == 1) {
                                echo "<span style='color: var(--text-muted); font-size: 12px; font-style: italic;'>Primary Owner</span>";
                            } else {
                                echo "<a href='manage_staff.php?delete_id=" . $row['admin_id'] . "' class='btn-action' style='color: var(--accent-danger); border-color: var(--accent-danger);' onclick='return confirm(\"⚠️ DANGER: Are you sure you want to delete this staff member? They will lose all access!\");'>Remove</a>";
                            }
                                        
                            echo "  </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color: var(--text-muted);'>No staff found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>