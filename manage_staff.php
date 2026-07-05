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


if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (strtolower($current_role) !== 'superadmin') {
        die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA CLEARANCE REQUIRED TO TERMINATE PERSONNEL.</div>");
    }

    $del_id = intval($_GET['id']);
    

    if ($del_id != $current_admin_id) {
        $stmt_get = $conn->prepare("SELECT username FROM admins WHERE admin_id = ?");
        $stmt_get->bind_param("i", $del_id);
        $stmt_get->execute();
        $res = $stmt_get->get_result();
        
        if ($res->num_rows > 0) {
            $staff = $res->fetch_assoc();
            $deleted_username = $staff['username'];

   
            $stmt_del = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
            $stmt_del->bind_param("i", $del_id);
            
            if ($stmt_del->execute()) {
                $log_username = $_SESSION['admin_username'] ?? 'UnknownAdmin';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $action_event = "Terminated Staff Personnel: " . $deleted_username;

                $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, ?, ?)");
                $log_stmt->bind_param("issss", $current_admin_id, $log_username, $current_role, $action_event, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            }
            $stmt_del->close();
        }
        $stmt_get->close();
    }
    

    header("Location: manage_staff.php?msg=deleted");
    exit();
}

$msg = $_GET['msg'] ?? '';
$alert = '';
if($msg == 'updated') $alert = "<div style='background:rgba(0,242,254,0.1); color:#00f2fe; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'><i class='fas fa-check-circle'></i> Profile updated successfully.</div>";
if($msg == 'deleted') $alert = "<div style='background:rgba(255,77,77,0.1); color:#ff4d4d; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3);'><i class='fas fa-trash'></i> Personnel access terminated.</div>";
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
        html, body { height: auto; min-height: 100vh; margin: 0; overflow-y: auto; background-color: var(--bg-main); }
        .admin-container { display: flex; min-height: 100vh; width: 100%; }
        .admin-sidebar { position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
        .admin-content { margin-left: 250px; flex: 1; padding: 30px !important; padding-bottom: 120px !important; min-height: 100vh; box-sizing: border-box; }
        
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 15px; color:#64748b; font-size: 12px; text-transform: uppercase; background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        
        .btn-recruit { background: linear-gradient(135deg, #ff4d4d, #f39c12); color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn-recruit:hover { box-shadow: 0 0 15px rgba(255,77,77,0.5); transform: translateY(-2px); }
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
                    ?>
                    <tr>
                        <td style="color:#fff; font-family: 'JetBrains Mono';">#<?php echo str_pad($row['admin_id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td style="color:#fff; font-weight:bold;">
                            <?php echo htmlspecialchars($row['username']); ?>
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
                            <a href="edit_staff.php?id=<?php echo $row['admin_id']; ?>" style="background:rgba(0,242,254,0.1); color:#00f2fe; border:1px solid rgba(0,242,254,0.3); padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; margin-right:5px; transition:0.3s;"><i class="fas fa-edit"></i> Edit</a>
                            <?php if (!$is_self): ?>
                              
                                <a href="manage_staff.php?action=delete&id=<?php echo $row['admin_id']; ?>" onclick="return confirm('Terminate this personnel?');" style="background:rgba(255,77,77,0.1); color:#ff4d4d; border:1px solid rgba(255,77,77,0.3); padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>