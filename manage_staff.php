<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$current_admin_id = $_SESSION['admin_id'] ?? 0;

if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_GET['toggle_id']) && isset($_GET['new_status'])) {
    if (strtolower($current_role) !== 'superadmin') {
        die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA CLEARANCE REQUIRED.</div>");
    }

    $toggle_id = intval($_GET['toggle_id']);
    $new_status = $_GET['new_status'] === 'Inactive' ? 'Inactive' : 'Active';
    
    if ($toggle_id != $current_admin_id) {
        $stmt_get = $conn->prepare("SELECT username FROM admins WHERE admin_id = ?");
        $stmt_get->bind_param("i", $toggle_id);
        $stmt_get->execute();
        $res = $stmt_get->get_result();
        
        if ($res->num_rows > 0) {
            $staff = $res->fetch_assoc();
            $target_username = $staff['username'];

            $stmt_update = $conn->prepare("UPDATE admins SET status = ? WHERE admin_id = ?");
            $stmt_update->bind_param("si", $new_status, $toggle_id);
            
            if ($stmt_update->execute()) {
                $log_username = $_SESSION['admin_username'] ?? 'UnknownAdmin';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $action_event = "Set Staff " . $target_username . " to " . $new_status;

                $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, ?, ?)");
                $log_stmt->bind_param("issss", $current_admin_id, $log_username, $current_role, $action_event, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            }
            $stmt_update->close();
        }
        $stmt_get->close();
    }
    
    header("Location: manage_staff.php?msg=status_updated");
    exit();
}

$msg = $_GET['msg'] ?? '';
$alert = '';
if($msg == 'updated') $alert = "<div style='background:rgba(0,242,254,0.1); color:#00f2fe; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'><i class='fas fa-check-circle'></i> Profile updated successfully.</div>";
if($msg == 'status_updated') $alert = "<div style='background:rgba(255,215,0,0.1); color:#ffd700; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,215,0,0.3);'><i class='fas fa-user-shield'></i> Personnel security status updated.</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
    
        html, body { height: 100vh; margin: 0; overflow: hidden; background-color: var(--bg-main); }
        .admin-container { display: flex; width: 100%; height: 100vh; }
        .admin-sidebar { width: 250px; height: 100vh; flex-shrink: 0; }
        
   
        .admin-content { 
            flex: 1; 
            padding: 30px; 
            height: 100vh; 
            overflow-y: auto; 
            box-sizing: border-box; 
        }
        
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 15px; color:#64748b; font-size: 12px; text-transform: uppercase; background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle; }
        
        .btn-recruit { background: linear-gradient(135deg, #ff4d4d, #f39c12); color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn-recruit:hover { box-shadow: 0 0 15px rgba(255,77,77,0.5); transform: translateY(-2px); }
        .action-buttons { display: flex; gap: 8px; justify-content: flex-end; align-items: center; }
    </style>
</head>
<body>
    <div class="admin-container">
      <?php include 'admin_sidebar.php'; ?>
        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #facc15; margin: 0;"><i class="fas fa-users-cog"></i> Security Personnel List</h2>
                <a href="add_staff.php" class="btn-recruit"><i class="fas fa-user-plus"></i> Recruit Personnel</a>
            </header>

            <?php echo $alert; ?>

            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Personnel</th>
                        <th>Email Contact</th>
                        <th>Clearance Level</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM admins ORDER BY role DESC, admin_id ASC");
                    while($row = $res->fetch_assoc()):
                        $is_self = ($row['admin_id'] == $current_admin_id);
                        $role_color = (strtolower($row['role']) === 'superadmin') ? '#a855f7' : '#00f2fe';
                        $role_bg = (strtolower($row['role']) === 'superadmin') ? 'rgba(168,85,247,0.1)' : 'rgba(0,242,254,0.1)';
                        
                        $account_status = isset($row['status']) ? $row['status'] : 'Active';
                        $row_opacity = (strtolower($account_status) === 'inactive') ? '0.5' : '1';
                    ?>
                    <tr style="opacity: <?php echo $row_opacity; ?>;">
                        <td style="color:#fff; font-family: 'JetBrains Mono';">#<?php echo str_pad($row['admin_id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td style="color:#fff; font-weight:bold;">
                            <?php echo htmlspecialchars($row['username']); ?>
                            <?php if(strtolower($account_status) === 'inactive'): ?>
                                <span style="background:rgba(255,77,77,0.1); color:#ff4d4d; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; border: 1px solid rgba(255,77,77,0.3); margin-left:8px;">INACTIVE</span>
                            <?php endif; ?>
                            <?php if($is_self): ?>
                                <span style="color:#00e676; font-size:10px; margin-left:5px;"><i class="fas fa-check-circle"></i> YOU</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#cbd5e1; font-size: 13px;"><?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?></td>
                        <td>
                            <span style="background:<?php echo $role_bg; ?>; color:<?php echo $role_color; ?>; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; border:1px solid <?php echo $role_color; ?>50;">
                                <i class="fas <?php echo (strtolower($row['role']) === 'superadmin') ? 'fa-star' : 'fa-user-shield'; ?>"></i> <?php echo strtoupper($row['role']); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <a href="edit_staff.php?id=<?php echo $row['admin_id']; ?>" style="background:rgba(0,242,254,0.1); color:#00f2fe; border:1px solid rgba(0,242,254,0.3); padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;"><i class="fas fa-edit"></i> Edit</a>
                                
                                <?php if (!$is_self): ?>
                                    <?php if (strtolower($account_status) === 'inactive'): ?>
                                        <a href="manage_staff.php?toggle_id=<?php echo $row['admin_id']; ?>&new_status=Active" onclick="return confirm('Reactivate this personnel account?');" style="background:transparent; color:#00e676; border:1px solid #00e676; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;" onmouseover="this.style.background='rgba(0,230,118,0.1)'" onmouseout="this.style.background='transparent'"><i class="fas fa-user-check"></i> Set Active</a>
                                    <?php else: ?>
                                        <a href="manage_staff.php?toggle_id=<?php echo $row['admin_id']; ?>&new_status=Inactive" onclick="return confirm('Suspend this personnel account?');" style="background:transparent; color:#ff4d4d; border:1px solid #ff4d4d; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;" onmouseover="this.style.background='rgba(255,77,77,0.1)'" onmouseout="this.style.background='transparent'"><i class="fas fa-user-slash"></i> Set Inactive</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>