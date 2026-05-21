<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) { header("Location: manage_users.php"); exit(); }

// 🌟 针对你的 customers 表结构的查询
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) { header("Location: manage_users.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Profile - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .info-box { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; }
        .info-label { color: #f39c12; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; letter-spacing: 1px; }
        .info-value { color: #fff; font-size: 16px; font-family: 'Inter', sans-serif; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="admin-container" style="display: flex; min-height: 100vh; width: 100%;">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="flex: 1; padding: 30px; box-sizing: border-box;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #f39c12; margin: 0;"><i class="fas fa-id-card"></i> Customer Intel Profile</h2>
                <a href="manage_users.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Return to Database</a>
            </header>

            <div class="form-card" style="max-width: 800px; margin: 0 auto; background: rgba(0,0,0,0.4); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                
                <div style="text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;">
                    <i class="fas fa-user-circle" style="font-size: 60px; color: #f39c12; margin-bottom: 15px;"></i>
                    <h2 style="margin: 0; color: #fff;"><?php echo htmlspecialchars($customer['username']); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #888; font-family: 'JetBrains Mono';">ID: USR-<?php echo $customer['customer_id']; ?></p>
                </div>

                <div class="profile-grid">
                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                    </div>
                    
                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                        <div class="info-value">
                            <?php echo !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : '<span style="color:#555; font-style:italic;">Not provided</span>'; ?>
                        </div>
                    </div>

                    <div class="info-box" style="grid-column: 1 / -1;">
                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>
                        <div class="info-value">
                            <?php echo !empty($customer['address']) ? nl2br(htmlspecialchars($customer['address'])) : '<span style="color:#555; font-style:italic;">No address on file</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>