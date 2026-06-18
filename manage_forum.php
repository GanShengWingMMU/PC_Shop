<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

// 🌟 极限删帖逻辑 (感谢你在资料库加了 ON DELETE CASCADE，删主帖会自动删点赞和评论！)
if (isset($_GET['delete_post'])) {
    $del_id = intval($_GET['delete_post']);
    $stmt = $conn->prepare("DELETE FROM community_posts WHERE post_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    
    header("Location: manage_forum.php?msg=deleted");
    exit();
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
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 15px; color:#a855f7; font-size: 12px; text-transform: uppercase; background: rgba(168,85,247,0.05); border-bottom: 2px solid rgba(168,85,247,0.2); letter-spacing: 1px; }
        .cyber-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; vertical-align: top; }
        .post-content { max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #888; font-size: 12px; margin-top: 5px; }
        .type-badge { background: rgba(168,85,247,0.1); color: #a855f7; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid rgba(168,85,247,0.3); text-transform: uppercase; }
        .stat-item { display: inline-flex; align-items: center; gap: 5px; margin-right: 15px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #a855f7; margin:0;"><i class="fas fa-satellite-dish"></i> Nexus Moderation (Forum)</h2>
                <p style="color:#888; font-size:13px; margin-top:5px;">Monitor civilian broadcasts, analyze engagement, and purge compromised data.</p>
            </header>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3);'><i class='fas fa-trash'></i> Signal Purged Successfully.</div>"; ?>

            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>Post ID</th>
                        <th>Author (Citizen)</th>
                        <th>Signal Title & Content</th>
                        <th>Engagement Matrix</th>
                        <th>Transmitted At</th>
                        <th style="text-align: right;">Command</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🌟 依据你的 SQL 数据库精准查询，连同喜欢和评论数量一起抓出
                    $sql = "SELECT p.post_id, p.title, p.content, p.post_type, p.views, p.created_at, c.username,
                            (SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.post_id) AS likes_count,
                            (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.post_id) AS comments_count
                            FROM community_posts p 
                            LEFT JOIN customers c ON p.customer_id = c.customer_id 
                            ORDER BY p.created_at DESC";
                            
                    $res = @$conn->query($sql);
                    
                    if ($res && $res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $pid = $row['post_id'];
                            $title = htmlspecialchars($row['title']);
                            $content = htmlspecialchars($row['content']);
                            $type = $row['post_type'] ?? 'Discussion';
                            $views = $row['views'] ?? 0;
                            $likes = $row['likes_count'] ?? 0;
                            $comments = $row['comments_count'] ?? 0;
                            $author = htmlspecialchars($row['username'] ?? 'Unknown');
                            
                            echo "<tr>";
                            echo "<td style='color:#64748b; font-family: JetBrains Mono; font-weight:bold;'>SIG-" . str_pad($pid, 4, '0', STR_PAD_LEFT) . "</td>";
                            echo "<td style='font-weight:bold; color:#fff;'><i class='fas fa-user-astronaut' style='color:#00f2fe; margin-right:5px;'></i> {$author}</td>";
                            
                            echo "<td>
                                    <div style='display:flex; align-items:center; gap:10px;'>
                                        <span class='type-badge'>{$type}</span>
                                        <span style='color:#a855f7; font-weight:bold;'>{$title}</span>
                                    </div>
                                    <div class='post-content'>{$content}</div>
                                  </td>";
                                  
                            echo "<td>
                                    <div class='stat-item' style='color:#00e676;' title='Upvotes'><i class='fas fa-arrow-up'></i> {$likes}</div>
                                    <div class='stat-item' style='color:#00f2fe;' title='Comments'><i class='fas fa-comment-dots'></i> {$comments}</div>
                                    <div class='stat-item' style='color:#888;' title='Views'><i class='fas fa-eye'></i> {$views}</div>
                                  </td>";
                                  
                            echo "<td style='color:#888; font-size:12px; font-family: Inter;'>" . date('d M Y, h:i A', strtotime($row['created_at'])) . "</td>";
                            
                            echo "<td style='text-align: right;'>
                                    <a href='manage_forum.php?delete_post={$pid}' onclick=\"return confirm('CRITICAL ACTION: Are you sure you want to permanently purge this post? (All likes and comments will be automatically destroyed by the database)');\" style='display:inline-block; color:#ff4d4d; border:1px solid #ff4d4d; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold; transition:0.3s;' onmouseover=\"this.style.background='rgba(255,77,77,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-ban'></i> Purge</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#64748b;'><i class='fas fa-radar' style='font-size:30px; margin-bottom:15px; display:block; opacity:0.5;'></i>No signals detected in the Nexus. The community is quiet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>