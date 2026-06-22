<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['admin_username'] ?? 'UnknownAdmin';

if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

//Delete the post
if (isset($_GET['delete_post'])) {
    $del_id = intval($_GET['delete_post']);
    $stmt = $conn->prepare("DELETE FROM community_posts WHERE post_id = ?");
    $stmt->bind_param("i", $del_id);
    if($stmt->execute()){
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, ?, ?)");
        $action_msg = "Purged Forum Post ID: $del_id";
        $log_stmt->bind_param("issss", $admin_id, $admin_username, $current_role, $action_msg, $ip_address);
        $log_stmt->execute();
    }
    header("Location: manage_forum.php?msg=deleted"); exit();
}

//Move Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_post'])) {
    $pid = intval($_POST['post_id']);
    $new_type = $_POST['new_type'];
    $stmt = $conn->prepare("UPDATE community_posts SET post_type = ? WHERE post_id = ?");
    $stmt->bind_param("si", $new_type, $pid);
    $stmt->execute();
    
    $conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event) VALUES ('$admin_id', '$admin_username', '$current_role', 'Moved Post ID: $pid to $new_type')");
    header("Location: manage_forum.php?msg=moved"); exit();
}

// Censor Filter
if (isset($_GET['scan_post'])) {
    $pid = intval($_GET['scan_post']);
    $bad_words = array('fuck', 'shit', 'scam', 'bitch', 'asshole');
    
    $res = $conn->query("SELECT content FROM community_posts WHERE post_id = $pid");
    if ($row = $res->fetch_assoc()) {
        $content = $row['content'];
        $clean_content = str_ireplace($bad_words, '***', $content); 
        
        $stmt = $conn->prepare("UPDATE community_posts SET content = ?, is_flagged = 0 WHERE post_id = ?");
        $stmt->bind_param("si", $clean_content, $pid);
        $stmt->execute();
        
        $conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event) VALUES ('$admin_id', '$admin_username', '$current_role', 'Censored Post ID: $pid')");
    }
    header("Location: manage_forum.php?msg=censored"); exit();
}

// Mute User
if (isset($_GET['ban_user'])) {
    $uid = intval($_GET['ban_user']);
    
    $conn->query("UPDATE customers SET account_status = 'Muted' WHERE customer_id = $uid");
    
    $action_msg = "Muted User ID: $uid";
    $conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event) VALUES ('$admin_id', '$admin_username', '$current_role', '$action_msg')");
    header("Location: manage_forum.php?msg=muted"); exit();
}

// Unmute User
if (isset($_GET['unban_user'])) {
    $uid = intval($_GET['unban_user']);
    // Active
    $conn->query("UPDATE customers SET account_status = 'Active' WHERE customer_id = $uid");
    
    $action_msg = "Unmuted User ID: $uid";
    $conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event) VALUES ('$admin_id', '$admin_username', '$current_role', '$action_msg')");
    header("Location: manage_forum.php?msg=unmuted"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nexus Moderation - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .table-scroll-container {
            max-height: 65vh;
            overflow-y: auto;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            background: rgba(0,0,0,0.5);
            position: relative;
        }
        .table-scroll-container::-webkit-scrollbar { width: 8px; }
        .table-scroll-container::-webkit-scrollbar-track { background: rgba(0,0,0,0.3); border-radius: 8px; }
        .table-scroll-container::-webkit-scrollbar-thumb { background: rgba(168,85,247,0.4); border-radius: 8px; }
        .table-scroll-container::-webkit-scrollbar-thumb:hover { background: rgba(168,85,247,0.8); }

        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; }
        
        .cyber-table th { 
            position: sticky; top: 0; z-index: 20; 
            background: rgba(15, 10, 20, 0.95); backdrop-filter: blur(10px);
            padding: 15px; color:#a855f7; font-size: 12px; text-transform: uppercase; 
            border-bottom: 2px solid rgba(168,85,247,0.2); letter-spacing: 1px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); 
        }
        
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; vertical-align: top; }
        
        .type-badge { background: rgba(168,85,247,0.1); color: #a855f7; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid rgba(168,85,247,0.3); text-transform: uppercase; }
        
        .rank-title { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: bold; letter-spacing: 0.5px; margin-top: 5px; display: inline-block; }
        .rank-vip { color: #ffd700; background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255,215,0,0.4); }
        .rank-standard { color: #00f2fe; background: rgba(0, 242, 254, 0.1); border: 1px solid rgba(0,242,254,0.4); }
        
        /* Muted color */
        .banned-badge { color: #ff9800; background: rgba(255, 152, 0, 0.1); font-size: 10px; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(255, 152, 0, 0.4); }
        
        .badge-package { color: #10b981; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.4); font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: bold; letter-spacing: 0.5px; margin-top: 5px; display: inline-block; margin-left: 5px; }

        .action-group { display: flex; flex-direction: column; gap: 6px; min-width: 110px; }
        .action-btn { display:flex; align-items: center; justify-content: center; gap: 6px; padding:6px 10px; border-radius:4px; text-decoration:none; font-size:11px; font-weight:bold; transition:0.3s; cursor: pointer; border: 1px solid transparent; width: 100%; box-sizing: border-box; }
        .btn-purge { color:#ff4d4d; border-color:rgba(255,77,77,0.4); background: rgba(255,77,77,0.05); } .btn-purge:hover { background:rgba(255,77,77,0.2); }
        .btn-scan { color:#00e676; border-color:rgba(0,230,118,0.4); background: rgba(0,230,118,0.05); } .btn-scan:hover { background:rgba(0,230,118,0.2); }
        
        .btn-ban { color:#ff9800; border-color:rgba(255,152,0,0.4); background: rgba(255,152,0,0.05); } .btn-ban:hover { background:rgba(255,152,0,0.2); }
        .btn-unban { color:#00f2fe; border-color:rgba(0,242,254,0.4); background: rgba(0,242,254,0.05); } .btn-unban:hover { background:rgba(0,242,254,0.2); }
        
        .report-warning { color: #ff9800; font-weight: bold; font-size: 11px; margin-top: 5px; display: flex; align-items: center; gap: 5px;}
        .pinned-row { background: rgba(250, 204, 21, 0.05); }

        .tooltip-container { position: relative; display: inline-block; max-width: 300px; cursor: pointer; }
        .post-content-preview { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #888; font-size: 12px; margin-top: 5px; display: block; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 2px; }
        .tooltip-text { visibility: hidden; opacity: 0; width: max-content; max-width: 350px; background-color: rgba(15, 15, 20, 0.95); color: #fff; text-align: left; border-radius: 8px; padding: 15px; position: absolute; z-index: 10; top: 100%; left: 0; margin-top: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.8); border: 1px solid rgba(168,85,247,0.3); font-size: 12px; line-height: 1.5; white-space: pre-wrap; transition: opacity 0.3s, visibility 0.3s, transform 0.3s; transform: translateY(-5px); }
        .tooltip-text::after { content: ""; position: absolute; bottom: 100%; left: 20px; margin-left: -5px; border-width: 5px; border-style: solid; border-color: transparent transparent rgba(168,85,247,0.3) transparent; }
        .tooltip-container:hover .tooltip-text { visibility: visible; opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #a855f7; margin:0;"><i class="fas fa-satellite-dish"></i> Nexus Moderation (Forum)</h2>
                <p style="color:#888; font-size:13px; margin-top:5px;">Monitor signals, scan for anomalies, and manage citizen access.</p>
            </header>

            <?php 
            if (isset($_GET['msg'])) {
                $msg = $_GET['msg'];
                $text = "";
                if ($msg == 'deleted') $text = "Signal Purged Successfully.";
                if ($msg == 'moved') $text = "Post successfully migrated to new section.";
                if ($msg == 'censored') $text = "Anomalies cleansed from post content.";
                
                if ($msg == 'muted') $text = "Citizen silenced. (They can still log in and buy PCs, but cannot post).";
                if ($msg == 'unmuted') $text = "Citizen speaking rights restored.";
                
                if ($text != "") echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3);'><i class='fas fa-check-circle'></i> $text</div>";
            }
            ?>
            
            <div class="table-scroll-container">
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Citizen & Title</th>
                            <th style="width: 35%;">Signal Status & Content</th>
                            <th style="width: 25%;">Section Routing</th>
                            <th style="text-align: center; width: 20%;">Moderation Commands</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.post_id, p.title, p.content, p.post_type, p.views, p.created_at, p.customer_id, 
                                       COALESCE(p.is_pinned, 0) as is_pinned, COALESCE(p.report_count, 0) as report_count, COALESCE(p.is_flagged, 0) as is_flagged,
                                       c.username, c.membership_tier, c.account_status,
                            (SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.post_id) AS likes_count,
                            (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.post_id) AS comments_count,
                            (SELECT COUNT(*) FROM orders o JOIN order_details od ON o.order_id = od.order_id WHERE o.customer_id = p.customer_id AND od.package_id IS NOT NULL AND o.order_status != 'Cancelled') AS package_count
                            FROM community_posts p 
                            LEFT JOIN customers c ON p.customer_id = c.customer_id 
                            ORDER BY p.is_pinned DESC, p.created_at DESC";
                            
                        $res = $conn->query($sql);
                        
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $pid = $row['post_id'];
                                $cid = $row['customer_id'];
                                $title = htmlspecialchars($row['title'] ?? 'Untitled');
                                $content = htmlspecialchars($row['content'] ?? 'No content');
                                $type = $row['post_type'] ?? 'Discussion';
                                
                                $tier = $row['membership_tier'] ?? 'Standard';
                                $title_class = ($tier === 'VIP') ? 'rank-vip' : 'rank-standard';
                                $title_icon = ($tier === 'VIP') ? '<i class="fas fa-crown"></i> Elite Citizen' : '<i class="fas fa-user"></i> Standard';
                                
                                $package_badge = ($row['package_count'] > 0) ? "<div class='badge-package' title='This user purchased a PC Package'><i class='fas fa-cube'></i> Pack Owner</div>" : "";

                                $is_banned = ($row['account_status'] === 'Banned' || $row['account_status'] === 'Muted');
                                $row_bg = ($row['is_pinned'] == 1) ? 'pinned-row' : '';

                                echo "<tr class='$row_bg'>";
                                
                                echo "<td>
                                        <div style='font-weight:bold; color:#fff; font-size: 14px;'>{$row['username']}</div>
                                        <div class='rank-title $title_class'>$title_icon</div>{$package_badge}<br>";
                                        if ($is_banned) {
                                            echo "<span class='banned-badge' style='margin-top:5px; display:inline-block;'><i class='fas fa-comment-slash'></i> MUTED / SILENCED</span>";
                                        }
                                echo "</td>";
                                
                                echo "<td>
                                        <div style='display:flex; align-items:center; gap:10px;'>";
                                            if ($row['is_pinned'] == 1) echo "<i class='fas fa-thumbtack' style='color:#facc15;' title='Pinned Post'></i>";
                                            if ($row['is_flagged'] == 1) echo "<i class='fas fa-radiation' style='color:#ff4d4d;' title='Sensitive Content Detected'></i>";
                                            echo "<span style='color:#a855f7; font-weight:bold; font-size:14px;'>{$title}</span>
                                        </div>
                                        
                                        <div class='tooltip-container'>
                                            <span class='post-content-preview'><i class='fas fa-eye' style='font-size:10px; color:#555;'></i> {$content}</span>
                                            <div class='tooltip-text'><strong style='color:#a855f7;'><i class='fas fa-align-left'></i> Full Signal Content:</strong><br><br>{$content}</div>
                                        </div>";
                                        
                                        if ($row['report_count'] > 0) {
                                            echo "<div class='report-warning'><i class='fas fa-flag'></i> Flagged by {$row['report_count']} citizens for review.</div>";
                                        }
                                        
                                        echo "<div style='color:#888; font-size:10px; margin-top:8px;'>
                                            <i class='fas fa-arrow-up'></i> {$row['likes_count']} &nbsp;|&nbsp; <i class='fas fa-comment-dots'></i> {$row['comments_count']} &nbsp;|&nbsp; " . date('d M Y, h:i A', strtotime($row['created_at'])) . "
                                        </div>
                                      </td>";
                                      
                                echo "<td>
                                        <form method='POST' style='display:flex; flex-direction:column; gap:5px; max-width: 140px;'>
                                            <input type='hidden' name='post_id' value='{$pid}'>
                                            <select name='new_type' style='background:#000; color:#fff; border:1px solid #333; padding:6px; border-radius:4px; font-size:11px; width: 100%; outline:none;'>
                                                <option value='Discussion' ".($type=='Discussion'?'selected':'').">Discussion</option>
                                                <option value='Question' ".($type=='Question'?'selected':'').">Question</option>
                                                <option value='Showcase' ".($type=='Showcase'?'selected':'').">Showcase</option>
                                            </select>
                                            <button type='submit' name='move_post' style='background:rgba(168,85,247,0.1); color:#a855f7; border:1px solid rgba(168,85,247,0.4); padding:6px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:bold; width: 100%; transition:0.3s;' onmouseover=\"this.style.background='rgba(168,85,247,0.2)'\" onmouseout=\"this.style.background='rgba(168,85,247,0.1)'\">Move</button>
                                        </form>
                                      </td>";
                                
                                echo "<td style='text-align: center; vertical-align: middle;'>
                                        <div class='action-group'>";
                                    
                                    // 🌟 Button Mute & Unmute
                                    if ($is_banned) {
                                        echo "<a href='manage_forum.php?unban_user={$cid}' class='action-btn btn-unban' onclick=\"return confirm('Restore this citizen\'s speaking rights?');\"><i class='fas fa-comment'></i> Unmute User</a>";
                                    } else {
                                        echo "<a href='manage_forum.php?ban_user={$cid}' class='action-btn btn-ban' onclick=\"return confirm('Mute this citizen? They can still buy PC but cannot post.');\"><i class='fas fa-comment-slash'></i> Mute User</a>";
                                    }

                                    echo "<a href='manage_forum.php?scan_post={$pid}' class='action-btn btn-scan' onclick=\"return confirm('Scan and replace sensitive words?');\"><i class='fas fa-search'></i> Filter Scan</a>";
                                    echo "<a href='manage_forum.php?delete_post={$pid}' onclick=\"return confirm('CRITICAL ACTION: Purge this post permanently?');\" class='action-btn btn-purge'><i class='fas fa-trash'></i> Purge</a>";
                                echo "    </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding:40px; color:#64748b;'>No signals detected in the Nexus.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>