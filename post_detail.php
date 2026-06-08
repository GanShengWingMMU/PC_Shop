<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
$customer_id = $_SESSION['customer_id'];
$sys_msg = $sys_err = "";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: community.php");
    exit();
}
$post_id = intval($_GET['id']);

// ==========================================
// 🚀 处理提交评论 (防 XSS & 预处理)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_comment'])) {
    $comment_text = htmlspecialchars(trim($_POST['comment_text']));
    
    if (!empty($comment_text)) {
        // 假设你的评论表列名是 comment，如果报错请检查 pcshop.sql
        $stmt = $conn->prepare("INSERT INTO community_comments (post_id, customer_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $customer_id, $comment_text);
        if ($stmt->execute()) {
            $sys_msg = "Signal reply transmitted successfully.";
            header("Location: post_detail.php?id=$post_id");
            exit();
        } else {
            $sys_err = "System Error: Transmission failed.";
        }
        $stmt->close();
    } else {
        $sys_err = "Data corrupted: Reply cannot be empty.";
    }
}

// 抓取帖子本体详情
$query_post = "
    SELECT cp.*, c.username, c.reward_coins,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = cp.post_id) AS like_count,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = cp.post_id AND customer_id = ?) AS user_liked,
           sb.build_name, sb.total_price
    FROM community_posts cp
    JOIN customers c ON cp.customer_id = c.customer_id
    LEFT JOIN saved_builds sb ON cp.pc_build_id = sb.pc_build
    WHERE cp.post_id = ?
";
$stmt_post = $conn->prepare($query_post);
$stmt_post->bind_param("ii", $customer_id, $post_id);
$stmt_post->execute();
$post_result = $stmt_post->get_result();

if ($post_result->num_rows === 0) {
    header("Location: community.php");
    exit();
}
$post = $post_result->fetch_assoc();

// 抓取所有评论
$query_comments = "
    SELECT cc.*, c.username, c.reward_coins 
    FROM community_comments cc
    JOIN customers c ON cc.customer_id = c.customer_id
    WHERE cc.post_id = ?
    ORDER BY cc.created_at ASC
";
$stmt_comments = $conn->prepare($query_comments);
$stmt_comments->bind_param("i", $post_id);
$stmt_comments->execute();
// 🌟 CTO 修复：绝对不能写成 result()，必须是 get_result()！
$comments_result = $stmt_comments->get_result(); 

function getRankBadge($coins) {
    if ($coins >= 1000) return '<span class="rank-badge elite"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 500) return '<span class="rank-badge pro"><i class="fas fa-star"></i> Pro</span>';
    return '<span class="rank-badge novice">Enthusiast</span>';
}

// 获取当前登录用户名(用于评论框头像)
$current_user_query = $conn->query("SELECT username FROM customers WHERE customer_id = $customer_id");
$current_username = $current_user_query->fetch_assoc()['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .container-detail { max-width: 900px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        .back-link { color: #00f2fe; text-decoration: none; font-weight: 800; text-transform: uppercase; font-size: 0.9rem; margin-bottom: 20px; display: inline-block; transition: 0.3s; }
        .back-link:hover { text-shadow: 0 0 10px rgba(0,242,254,0.5); transform: translateX(-5px); }

        .post-card { background: #111827; border: 1px solid rgba(0, 242, 254, 0.2); border-radius: 12px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.5), inset 0 0 20px rgba(0,242,254,0.05); margin-bottom: 40px;}
        .avatar { width: 55px; height: 55px; border-radius: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.5rem; color: #000; }
        .post-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px;}
        .author-name { color: #fff; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;}
        .post-time { font-size: 0.85rem; color: #64748b; margin-top: 5px; }
        .post-title { font-size: 2rem; font-weight: 900; margin: 0 0 20px 0; color: #fff; line-height: 1.2;}
        .post-content { color: #cbd5e1; line-height: 1.8; font-size: 1.05rem; white-space: pre-wrap; margin-bottom: 30px;}

        .rank-badge { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .rank-badge.elite { background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255,215,0,0.5); }
        .rank-badge.pro { background: rgba(168, 85, 247, 0.15); color: #d8b4fe; border: 1px solid rgba(168,85,247,0.5); }
        .rank-badge.novice { background: rgba(255, 255, 255, 0.05); color: #94a3b8; border: 1px solid rgba(255, 255, 255, 0.1); }

        /* 🌟 高级评论区 UI */
        .comments-wrapper { position: relative; padding-left: 20px; border-left: 2px solid rgba(0, 242, 254, 0.15); margin-left: 10px; }
        .comments-header-title { font-size: 1.3rem; font-weight: 900; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; color: #fff; }
        
        .comment-item { position: relative; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 25px; margin-bottom: 20px; transition: 0.3s; }
        .comment-item:hover { background: rgba(0,242,254,0.02); border-color: rgba(0,242,254,0.3); transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        
        /* 评论气泡连接线 */
        .comment-item::before { content: ''; position: absolute; left: -22px; top: 40px; width: 20px; height: 2px; background: rgba(0, 242, 254, 0.15); }

        .c-header-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .c-avatar { width: 45px; height: 45px; border-radius: 10px; background: #1f2937; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.1rem; color: #00f2fe; border: 1px solid rgba(0,242,254,0.2); box-shadow: 0 0 10px rgba(0,242,254,0.1);}
        
        .c-author-info { display: flex; flex-direction: column; justify-content: center; }
        .c-author-name { font-weight: 800; color: #fff; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .c-op-badge { background: rgba(0,242,254,0.15); color: #00f2fe; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; font-weight: 900; letter-spacing: 1px; border: 1px solid rgba(0,242,254,0.3);}
        .c-time { color: #64748b; font-size: 0.8rem; font-family: 'JetBrains Mono', monospace; margin-top: 3px; }
        
        .c-text { color: #cbd5e1; font-size: 1rem; line-height: 1.7; padding-left: 60px; }

        /* 🌟 极客级回复输入面板 */
        .reply-panel { margin-top: 40px; background: #0b0f16; border: 1px solid #00f2fe; border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 242, 254, 0.05); position: relative; overflow: hidden; }
        .reply-panel::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #00f2fe; }
        
        .reply-flex { display: flex; gap: 20px; align-items: flex-start; }
        .current-user-avatar { width: 50px; height: 50px; border-radius: 10px; background: rgba(0,242,254,0.1); border: 1px dashed #00f2fe; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem; color: #00f2fe; flex-shrink: 0;}
        
        .tech-textarea { width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 18px; border-radius: 8px; font-size: 1rem; font-family: 'Inter', sans-serif; resize: vertical; min-height: 120px; transition: 0.3s; line-height: 1.5; }
        .tech-textarea:focus { outline: none; border-color: #00f2fe; background: rgba(0,242,254,0.02); box-shadow: inset 0 0 10px rgba(0,242,254,0.05); }
        .tech-textarea::placeholder { color: #475569; font-family: 'JetBrains Mono', monospace; }
        
        .reply-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-left: 70px; }
        .reply-hint { color: #64748b; font-size: 0.85rem; font-family: 'JetBrains Mono', monospace; }
        
        .btn-transmit { background: #00f2fe; color: #000; border: none; font-weight: 900; text-transform: uppercase; padding: 12px 30px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; letter-spacing: 1px; }
        .btn-transmit:hover { box-shadow: 0 0 20px rgba(0, 242, 254, 0.6); transform: translateY(-2px); background: #fff; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>

<div class="container-detail">
    <a href="community.php" class="back-link"><i class="fas fa-arrow-left"></i> Return to Neural Network</a>

    <?php if($sys_msg) echo "<div style='color:#00e676; padding:15px; border:1px solid #00e676; border-radius:8px; margin-bottom:20px; background:rgba(0,230,118,0.1);'><i class='fas fa-check'></i> $sys_msg</div>"; ?>
    <?php if($sys_err) echo "<div style='color:#ff4d4d; padding:15px; border:1px solid #ff4d4d; border-radius:8px; margin-bottom:20px; background:rgba(255,77,77,0.1);'><i class='fas fa-exclamation-triangle'></i> $sys_err</div>"; ?>

    <div class="post-card">
        <div class="post-header">
            <div class="avatar"><?php echo strtoupper(substr($post['username'], 0, 1)); ?></div>
            <div>
                <div class="author-name">
                    <?php echo htmlspecialchars($post['username']); ?>
                    <?php echo getRankBadge($post['reward_coins']); ?>
                    
                    <?php if($post['post_type'] == 'Showcase'): ?>
                        <span style="background: rgba(168, 85, 247, 0.15); color: #d8b4fe; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;"><i class="fas fa-desktop"></i> Showcase</span>
                    <?php endif; ?>
                </div>
                <div class="post-time">Transmitted on <?php echo date('F d, Y • H:i A', strtotime($post['created_at'])); ?></div>
            </div>
        </div>

        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

        <?php if ($post['post_type'] == 'Showcase' && $post['pc_build_id']): ?>
            <div style="background: rgba(0,0,0,0.4); border: 1px dashed rgba(168,85,247,0.4); padding: 25px; border-radius: 12px; margin-bottom: 25px;">
                <h4 style="color: #d8b4fe; margin:0 0 15px 0; display:flex; align-items:center; gap:10px;"><i class="fas fa-microchip"></i> Hardware Blueprint Attached</h4>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong style="font-size:1.3rem; color:#fff;"><?php echo htmlspecialchars($post['build_name']); ?></strong>
                    <a href="load_build.php?id=<?php echo $post['pc_build_id']; ?>&action=cart" class="btn-transmit" style="padding: 10px 20px; font-size: 0.85rem;"><i class="fas fa-cart-arrow-down"></i> Load Blueprint</a>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 25px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 25px;">
            <span style="color: #00f2fe; font-weight: 800;"><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> Protocol Approvals</span>
            <span style="color: #cbd5e1; font-weight: 800;"><i class="fas fa-comments"></i> <?php echo $comments_result->num_rows; ?> Network Replies</span>
        </div>
    </div>

    <div class="comments-header-title">
        <i class="fas fa-stream" style="color: #00f2fe;"></i> Signal Data Log
    </div>

    <div class="comments-wrapper">
        <?php if($comments_result->num_rows > 0): while($c = $comments_result->fetch_assoc()): ?>
            <div class="comment-item">
                <div class="c-header-row">
                    <div class="c-avatar"><?php echo strtoupper(substr($c['username'], 0, 1)); ?></div>
                    <div class="c-author-info">
                        <div class="c-author-name">
                            <?php echo htmlspecialchars($c['username']); ?>
                            <?php if($c['customer_id'] == $post['customer_id']) echo '<span class="c-op-badge"><i class="fas fa-check-circle"></i> OP</span>'; ?>
                        </div>
                        <div class="c-time"><?php echo date('Y-m-d // H:i:s', strtotime($c['created_at'])); ?></div>
                    </div>
                </div>
                <div class="c-text"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>
            </div>
        <?php endwhile; else: ?>
            <div style="padding: 30px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-ghost" style="font-size: 2rem; color: #475569; margin-bottom: 10px;"></i>
                <p style="color: #64748b; margin: 0;">No logs found. Initialize the first response protocol.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="reply-panel">
        <form method="POST">
            <div class="reply-flex">
                <div class="current-user-avatar"><?php echo strtoupper(substr($current_username, 0, 1)); ?></div>
                <div style="flex: 1;">
                    <textarea name="comment_text" class="tech-textarea" placeholder="> Type your response here to transmit to the network..." required></textarea>
                </div>
            </div>
            <div class="reply-actions">
                <span class="reply-hint"><i class="fas fa-shield-alt"></i> Connection Secure. Markdown disabled.</span>
                <button type="submit" name="submit_comment" class="btn-transmit">
                    <i class="fas fa-paper-plane"></i> Transmit Log
                </button>
            </div>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>