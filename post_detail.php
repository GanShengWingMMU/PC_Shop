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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_comment'])) {
    $comment_text = htmlspecialchars(trim($_POST['comment_text']));
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO community_comments (post_id, customer_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $customer_id, $comment_text);
        if ($stmt->execute()) {
            $sys_msg = "Reply posted successfully."; // 🌟 简化
            header("Location: post_detail.php?id=$post_id");
            exit();
        } else {
            $sys_err = "System Error: Failed to post reply.";
        }
        $stmt->close();
    } else {
        $sys_err = "Error: Reply cannot be empty.";
    }
}

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
$comments_result = $stmt_comments->get_result(); 

function getRankBadge($coins) {
    if ($coins >= 1000) return '<span class="rank-badge elite"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 500) return '<span class="rank-badge pro"><i class="fas fa-star"></i> Pro</span>';
    return '<span class="rank-badge novice">Enthusiast</span>';
}

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

        .fb-image-grid { display: grid; gap: 4px; border-radius: 8px; overflow: hidden; margin-bottom: 30px; background: #000;}
        .grid-img { width: 100%; height: 100%; object-fit: cover; aspect-ratio: 1; cursor: pointer; transition: opacity 0.2s; }
        .grid-img:hover { opacity: 0.8; }
        .layout-1 { grid-template-columns: 1fr; }
        .layout-1 .grid-img { aspect-ratio: auto; max-height: 600px; }
        .layout-2 { grid-template-columns: 1fr 1fr; }
        .layout-3 { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
        .layout-3 .img-item:nth-child(1) { grid-row: 1 / span 2; aspect-ratio: auto; height: 100%; }
        .layout-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .more-overlay { position: relative; cursor: pointer; }
        .more-overlay::after { content: attr(data-count); position: absolute; inset: 0; background: rgba(0,0,0,0.6); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; backdrop-filter: blur(2px);}

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; }
        #lightbox-img { max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 4px; box-shadow: 0 0 30px rgba(0,242,254,0.2); }
        .lb-close { position: absolute; top: 20px; right: 30px; font-size: 2rem; color: #fff; cursor: pointer; }
        .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); font-size: 2.5rem; color: #00f2fe; cursor: pointer; padding: 20px; transition: 0.3s; }
        .lb-nav:hover { color: #fff; text-shadow: 0 0 15px #00f2fe; }
        .lb-prev { left: 20px; }
        .lb-next { right: 20px; }

        .comments-wrapper { position: relative; padding-left: 20px; border-left: 2px solid rgba(0, 242, 254, 0.15); margin-left: 10px; }
        .comments-header-title { font-size: 1.3rem; font-weight: 900; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; color: #fff; }
        .comment-item { position: relative; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 25px; margin-bottom: 20px; transition: 0.3s; }
        .comment-item:hover { background: rgba(0,242,254,0.02); border-color: rgba(0,242,254,0.3); transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .comment-item::before { content: ''; position: absolute; left: -22px; top: 40px; width: 20px; height: 2px; background: rgba(0, 242, 254, 0.15); }
        .c-header-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .c-avatar { width: 45px; height: 45px; border-radius: 10px; background: #1f2937; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.1rem; color: #00f2fe; border: 1px solid rgba(0,242,254,0.2); box-shadow: 0 0 10px rgba(0,242,254,0.1);}
        .c-author-info { display: flex; flex-direction: column; justify-content: center; }
        .c-author-name { font-weight: 800; color: #fff; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .c-op-badge { background: rgba(0,242,254,0.15); color: #00f2fe; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; font-weight: 900; letter-spacing: 1px; border: 1px solid rgba(0,242,254,0.3);}
        .c-time { color: #64748b; font-size: 0.8rem; font-family: 'JetBrains Mono', monospace; margin-top: 3px; }
        .c-text { color: #cbd5e1; font-size: 1rem; line-height: 1.7; padding-left: 60px; }

        .reply-panel { margin-top: 40px; background: #0b0f16; border: 1px solid #00f2fe; border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 242, 254, 0.05); position: relative; overflow: hidden; }
        .reply-panel::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #00f2fe; }
        .reply-flex { display: flex; gap: 20px; align-items: flex-start; }
        .current-user-avatar { width: 50px; height: 50px; border-radius: 10px; background: rgba(0,242,254,0.1); border: 1px dashed #00f2fe; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem; color: #00f2fe; flex-shrink: 0;}
        .tech-textarea { width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 18px; border-radius: 8px; font-size: 1rem; font-family: 'Inter', sans-serif; resize: vertical; min-height: 120px; transition: 0.3s; line-height: 1.5; }
        .tech-textarea:focus { outline: none; border-color: #00f2fe; background: rgba(0,242,254,0.02); box-shadow: inset 0 0 10px rgba(0,242,254,0.05); }
        .tech-textarea::placeholder { color: #475569; font-family: 'Inter', sans-serif; }
        .reply-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-left: 70px; }
        .reply-hint { color: #64748b; font-size: 0.85rem; }
        .btn-transmit { background: #00f2fe; color: #000; border: none; font-weight: 900; text-transform: uppercase; padding: 12px 30px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; letter-spacing: 1px; }
        .btn-transmit:hover { box-shadow: 0 0 20px rgba(0, 242, 254, 0.6); transform: translateY(-2px); background: #fff; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>

<div class="container-detail">
    <a href="community.php" class="back-link"><i class="fas fa-arrow-left"></i> Return to Community</a> <?php if($sys_msg) echo "<div style='color:#00e676; padding:15px; border:1px solid #00e676; border-radius:8px; margin-bottom:20px; background:rgba(0,230,118,0.1);'><i class='fas fa-check'></i> $sys_msg</div>"; ?>
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
                <div class="post-time">Posted on <?php echo date('F d, Y • H:i A', strtotime($post['created_at'])); ?></div> </div>
        </div>

        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

        <?php 
        $imgs = !empty($post['post_images']) ? json_decode($post['post_images'], true) : [];
        if (is_array($imgs) && count($imgs) > 0): 
            $count = count($imgs);
            $layout_class = 'layout-' . min($count, 4);
        ?>
            <div class="fb-image-grid <?php echo $layout_class; ?>">
                <?php for ($i = 0; $i < min($count, 4); $i++): ?>
                    <?php 
                        $src = htmlspecialchars($imgs[$i]); 
                        $imgs_js = htmlspecialchars(json_encode($imgs), ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php if ($i == 3 && $count > 4): ?>
                        <div class="more-overlay img-item" data-count="+<?php echo ($count - 4); ?>" onclick="openLightbox(<?php echo $imgs_js; ?>, <?php echo $i; ?>)">
                            <img src="<?php echo $src; ?>" class="grid-img">
                        </div>
                    <?php else: ?>
                        <div class="img-item" onclick="openLightbox(<?php echo $imgs_js; ?>, <?php echo $i; ?>)">
                            <img src="<?php echo $src; ?>" class="grid-img">
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

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
            <span style="color: #00f2fe; font-weight: 800;"><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> Likes</span> <span style="color: #cbd5e1; font-weight: 800;"><i class="fas fa-comments"></i> <?php echo $comments_result->num_rows; ?> Comments</span> </div>
    </div>

    <div class="comments-header-title">
        <i class="fas fa-comments" style="color: #00f2fe;"></i> Discussion </div>

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
                <i class="fas fa-comment-dots" style="font-size: 2rem; color: #475569; margin-bottom: 10px;"></i>
                <p style="color: #64748b; margin: 0;">No comments yet. Be the first to reply!</p> </div>
        <?php endif; ?>
    </div>

    <div class="reply-panel">
        <form method="POST">
            <div class="reply-flex">
                <div class="current-user-avatar"><?php echo strtoupper(substr($current_username, 0, 1)); ?></div>
                <div style="flex: 1;">
                    <textarea name="comment_text" class="tech-textarea" placeholder="Write your reply here..." required></textarea> </div>
            </div>
            <div class="reply-actions">
                <span class="reply-hint"><i class="fas fa-info-circle"></i> Be respectful and friendly.</span> <button type="submit" name="submit_comment" class="btn-transmit">
                    <i class="fas fa-paper-plane"></i> Post Reply </button>
            </div>
        </form>
    </div>
</div>

<div id="lightbox">
    <span class="lb-close" onclick="closeLightbox()">&times;</span>
    <span class="lb-nav lb-prev" onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></span>
    <img id="lightbox-img" src="" alt="Full Screen Preview">
    <span class="lb-nav lb-next" onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></span>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    let currentGallery = [];
    let currentIndex = 0;

    function openLightbox(imagesArray, index) {
        currentGallery = imagesArray;
        currentIndex = index;
        document.getElementById('lightbox').style.display = 'flex';
        updateLightboxImage();
        document.body.style.overflow = 'hidden'; 
    }

    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function changeImage(direction) {
        currentIndex += direction;
        if (currentIndex < 0) currentIndex = currentGallery.length - 1;
        if (currentIndex >= currentGallery.length) currentIndex = 0;
        updateLightboxImage();
    }

    function updateLightboxImage() {
        document.getElementById('lightbox-img').src = currentGallery[currentIndex];
    }
</script>

</body>
</html>