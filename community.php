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

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['post_id'])) {
    $del_post_id = intval($_GET['post_id']);
    $verify_del = $conn->prepare("SELECT post_id FROM community_posts WHERE post_id = ? AND customer_id = ?");
    $verify_del->bind_param("ii", $del_post_id, $customer_id);
    $verify_del->execute();
    if ($verify_del->get_result()->num_rows > 0) {
        $do_del = $conn->prepare("DELETE FROM community_posts WHERE post_id = ?");
        $do_del->bind_param("i", $del_post_id);
        if ($do_del->execute()) {
            $sys_msg = "Post permanently deleted."; // 🌟 改为直白的删除提示
        } else {
            $sys_err = "System Error: Failed to delete post.";
        }
        $do_del->close();
    }
    $verify_del->close();
}

if (isset($_GET['action']) && $_GET['action'] == 'like' && isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    $check_like = $conn->prepare("SELECT like_id FROM community_likes WHERE post_id = ? AND customer_id = ?");
    $check_like->bind_param("ii", $post_id, $customer_id);
    $check_like->execute();
    if ($check_like->get_result()->num_rows > 0) {
        $del_stmt = $conn->prepare("DELETE FROM community_likes WHERE post_id = ? AND customer_id = ?");
        $del_stmt->bind_param("ii", $post_id, $customer_id);
        $del_stmt->execute();
    } else {
        $ins_stmt = $conn->prepare("INSERT INTO community_likes (post_id, customer_id) VALUES (?, ?)");
        $ins_stmt->bind_param("ii", $post_id, $customer_id);
        $ins_stmt->execute();
    }
    $filter_param = isset($_GET['filter']) ? "&filter=" . htmlspecialchars($_GET['filter']) : "";
    header("Location: community.php?$filter_param"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    $title = htmlspecialchars(trim($_POST['title'])); 
    $content = htmlspecialchars(trim($_POST['content'])); 
    $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : 'Discussion';
    $pc_build_id = null;

    if ($post_type == 'Showcase' && !empty($_POST['pc_build_id'])) {
        $submitted_build_id = intval($_POST['pc_build_id']);
        $verify_owner = $conn->prepare("SELECT pc_build FROM saved_builds WHERE pc_build = ? AND customer_id = ?");
        $verify_owner->bind_param("ii", $submitted_build_id, $customer_id);
        $verify_owner->execute();
        if ($verify_owner->get_result()->num_rows > 0) {
            $pc_build_id = $submitted_build_id;
        } else {
            $sys_err = "Error: You can only showcase your own blueprints.";
        }
        $verify_owner->close();
    }

    $uploaded_images = [];
    if (!empty($_FILES['post_images']['name'][0])) {
        $file_count = count($_FILES['post_images']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['post_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['post_images']['tmp_name'][$i];
                $name = basename($_FILES['post_images']['name'][$i]);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $new_name = 'cmty_' . uniqid() . '_' . rand(1000, 9999) . '.' . $ext;
                    $dest_path = 'image/' . $new_name;
                    if (move_uploaded_file($tmp_name, $dest_path)) {
                        $uploaded_images[] = $dest_path; 
                    }
                }
            }
        }
    }
    $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

    if (empty($title) || empty($content)) {
        $sys_err = $sys_err ?: "Title and Content are required.";
    } elseif (empty($sys_err)) {
        $stmt = $conn->prepare("INSERT INTO community_posts (customer_id, pc_build_id, title, content, post_type, post_images) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $customer_id, $pc_build_id, $title, $content, $post_type, $images_json);
        if ($stmt->execute()) {
            $sys_msg = "Post published successfully!"; // 🌟 改为直白提示
        } else {
            $sys_err = "System error: Failed to publish post.";
        }
        $stmt->close();
    }
}

$my_builds = $conn->query("SELECT pc_build, build_name, total_price FROM saved_builds WHERE customer_id = $customer_id ORDER BY created_at DESC");

$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$where_clause = "";
$order_clause = "ORDER BY cp.created_at DESC";

if ($filter_type == 'Showcase') { $where_clause = "WHERE cp.post_type = 'Showcase'"; } 
elseif ($filter_type == 'Discussion') { $where_clause = "WHERE cp.post_type IN ('Discussion', 'Question')"; } 
elseif ($filter_type == 'Trending') { $order_clause = "ORDER BY like_count DESC, cp.created_at DESC"; }

$query_posts = "
    SELECT cp.*, c.username, c.reward_coins,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = cp.post_id) AS like_count,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = cp.post_id AND customer_id = $customer_id) AS user_liked,
           (SELECT COUNT(*) FROM community_comments WHERE post_id = cp.post_id) AS comment_count,
           sb.build_name, sb.total_price
    FROM community_posts cp
    JOIN customers c ON cp.customer_id = c.customer_id
    LEFT JOIN saved_builds sb ON cp.pc_build_id = sb.pc_build
    $where_clause
    $order_clause
";
$posts = $conn->query($query_posts);
$total_posts = $conn->query("SELECT COUNT(*) FROM community_posts")->fetch_row()[0];
$top_builders = $conn->query("SELECT c.username, c.reward_coins, COUNT(cp.post_id) as post_count FROM customers c JOIN community_posts cp ON c.customer_id = cp.customer_id GROUP BY c.customer_id ORDER BY post_count DESC LIMIT 3");

function getRankBadge($coins) {
    if ($coins >= 1000) return '<span class="rank-badge elite" title="Elite Architect"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 500) return '<span class="rank-badge pro" title="Pro Builder"><i class="fas fa-star"></i> Pro</span>';
    return '<span class="rank-badge novice" title="Enthusiast">Enthusiast</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(168, 85, 247, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1300px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        .community-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 2px solid rgba(255,255,255,0.05); padding-bottom: 20px;}
        .community-header h1 { margin: 0; font-size: 2.8rem; font-weight: 900; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .community-header p { margin: 5px 0 0 0; color: #00f2fe; font-weight: 600;}
        
        .tech-btn { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; font-weight: 800; padding: 12px 25px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;}
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px);}
        .tech-btn-primary { background: #00f2fe; color: #000; }

        .post-form-container { display: none; background: #0b0f16; border: 1px solid #00f2fe; padding: 30px; border-radius: 12px; margin-bottom: 30px; animation: slideDown 0.3s ease; box-shadow: 0 20px 40px rgba(0, 242, 254, 0.1);}
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .type-selector-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .type-card { position: relative; background: #111827; border: 1px solid rgba(255,255,255,0.15); padding: 16px; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; text-align: left; }
        .type-card:hover { border-color: rgba(0,242,254,0.5); background: rgba(0,242,254,0.02); }
        .type-card input[type="radio"] { position: absolute; opacity: 0; }
        .type-title { font-weight: 800; font-size: 1rem; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;}
        .type-desc { font-size: 0.8rem; color: #94a3b8; line-height: 1.4; display: block; }
        .type-card.selected { border-color: #00f2fe; background: rgba(0, 242, 254, 0.05); box-shadow: 0 0 15px rgba(0,242,254,0.1); }

        .tech-input { width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 15px; border-radius: 8px; font-size: 1rem; font-family: 'Inter', sans-serif; margin-bottom: 20px; box-sizing: border-box; transition: 0.3s;}
        .tech-input:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.1); }
        .tech-label { color: #cbd5e1; font-size: 0.9rem; font-weight: 700; margin-bottom: 10px; display: block; }

        .image-upload-wrapper { border: 2px dashed rgba(0,242,254,0.3); border-radius: 8px; padding: 25px; text-align: center; cursor: pointer; transition: 0.3s; background: rgba(0,0,0,0.3); margin-bottom: 20px;}
        .image-upload-wrapper:hover { border-color: #00f2fe; background: rgba(0,242,254,0.05); }
        .image-preview-grid { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .preview-img-box { position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #00f2fe; }
        .preview-img-box img { width: 100%; height: 100%; object-fit: cover; }

        .community-layout { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start;}

        .filter-bar { display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; overflow-x: auto; white-space: nowrap;}
        .filter-btn { padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; font-size: 0.95rem; font-weight: 700; text-decoration: none; transition: 0.3s; background: rgba(255,255,255,0.02);}
        .filter-btn:hover { background: rgba(255,255,255,0.05); color: #fff;}
        .filter-btn.active { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border-color: #00f2fe; }

        .post-feed { display: flex; flex-direction: column; gap: 25px; }
        .post-card { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 30px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); }
        .post-card:hover { border-color: rgba(0,242,254,0.3); transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.6); }
        
        .post-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .avatar { width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.4rem; color: #000; text-transform: uppercase;}
        
        .author-info { flex: 1; }
        .author-name { color: #fff; font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;}
        .post-time { font-size: 0.8rem; color: #64748b; margin-top: 4px; }
        
        .rank-badge { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .rank-badge.elite { background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255,215,0,0.5); }
        .rank-badge.pro { background: rgba(168, 85, 247, 0.15); color: #d8b4fe; border: 1px solid rgba(168,85,247,0.5); }
        .rank-badge.novice { background: rgba(255, 255, 255, 0.05); color: #94a3b8; border: 1px solid rgba(255, 255, 255, 0.1); }

        .post-title { font-size: 1.5rem; font-weight: 900; margin: 0 0 15px 0; color: #fff; line-height: 1.3;}
        .post-content { color: #cbd5e1; line-height: 1.7; font-size: 1rem; margin-bottom: 25px; white-space: pre-wrap; }

        .fb-image-grid { display: grid; gap: 4px; border-radius: 8px; overflow: hidden; margin-bottom: 20px; background: #000;}
        .grid-img { width: 100%; height: 100%; object-fit: cover; aspect-ratio: 1; cursor: pointer; transition: opacity 0.2s; }
        .grid-img:hover { opacity: 0.8; }
        .layout-1 { grid-template-columns: 1fr; }
        .layout-1 .grid-img { aspect-ratio: auto; max-height: 500px; }
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

        .post-actions { display: flex; gap: 20px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; flex-wrap: wrap;}
        .action-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none;}
        .action-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .action-btn.liked { background: rgba(255, 0, 127, 0.1); border-color: rgba(255, 0, 127, 0.3); color: #ff007f; }

        .sidebar { display: flex; flex-direction: column; gap: 25px; }
        .widget-cta { background: linear-gradient(135deg, rgba(0,242,254,0.1), rgba(168,85,247,0.1)); border: 1px solid #00f2fe; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 10px 20px rgba(0,242,254,0.1);}
        .widget-cta h3 { color: #fff; margin: 0 0 10px 0; font-size: 1.2rem; font-weight: 800;}
        .widget-cta p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; line-height: 1.5;}
        
        .widget { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; }
        .widget h3 { font-size: 1.1rem; color: #fff; margin: 0 0 20px 0; font-weight: 800; display: flex; align-items: center; gap: 10px;}
        .widget h3 i { color: #00f2fe; }

        .stat-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; color: #94a3b8; }
        .stat-value { font-weight: bold; color: #fff; }
        
        .top-user-list { list-style: none; padding: 0; margin: 0; }
        .top-user-list li { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .top-user-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .top-avatar { width: 40px; height: 40px; border-radius: 10px; background: #1f2937; border: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: center; align-items: center; font-weight: 900; font-size: 1rem;}
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<div class="dashboard-container">
    
    <div class="community-header">
        <div>
            <p><i class="fas fa-users"></i> GridCitY Community</p> <h1>Forum & Showcase</h1> </div>
        <button class="tech-btn tech-btn-primary" onclick="togglePostForm()"><i class="fas fa-pen"></i> Create Post</button> </div>

    <?php if($sys_msg) echo "<div style='color:#00e676; padding:15px; border:1px solid #00e676; border-radius:8px; margin-bottom:20px; background:rgba(0,230,118,0.1);'><i class='fas fa-check'></i> $sys_msg</div>"; ?>
    <?php if($sys_err) echo "<div style='color:#ff4d4d; padding:15px; border:1px solid #ff4d4d; border-radius:8px; margin-bottom:20px; background:rgba(255,77,77,0.1);'><i class='fas fa-exclamation-triangle'></i> $sys_err</div>"; ?>

    <div class="post-form-container" id="post-form">
        <h3 style="margin:0 0 20px 0; color:#fff; font-size: 1.4rem;"><i class="fas fa-edit" style="color:#00f2fe; margin-right:10px;"></i> Create a New Post</h3> <form method="POST" action="community.php" enctype="multipart/form-data">
            
            <label class="tech-label">Select Category</label> <div class="type-selector-grid">
                <div class="type-card selected" onclick="selectPostType(this, 'Discussion')">
                    <input type="radio" name="post_type" value="Discussion" checked>
                    <span class="type-title"><i class="fas fa-comments" style="color:#00f2fe;"></i> Discussion</span>
                    <span class="type-desc">General talk, opinions, or debates.</span>
                </div>
                <div class="type-card" onclick="selectPostType(this, 'Question')">
                    <input type="radio" name="post_type" value="Question">
                    <span class="type-title"><i class="fas fa-question-circle" style="color:#ffb74d;"></i> Question</span>
                    <span class="type-desc">Troubleshooting or part advice.</span>
                </div>
                <div class="type-card" onclick="selectPostType(this, 'Showcase')">
                    <input type="radio" name="post_type" value="Showcase">
                    <span class="type-title"><i class="fas fa-desktop" style="color:#d8b4fe;"></i> Showcase</span>
                    <span class="type-desc">Attach a blueprint to show off your build.</span>
                </div>
            </div>

            <div id="build_select_container" style="display: none;">
                <label class="tech-label">Select Your Saved Build</label>
                <select name="pc_build_id" class="tech-input">
                    <option value="">-- Choose a Build --</option>
                    <?php 
                    if ($my_builds->num_rows > 0) {
                        $my_builds->data_seek(0);
                        while($mb = $my_builds->fetch_assoc()) {
                            echo "<option value='{$mb['pc_build']}'>".htmlspecialchars($mb['build_name'])." (RM " . number_format($mb['total_price'], 2) . ")</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No builds found. Use PC Builder first.</option>";
                    }
                    ?>
                </select>
            </div>
            
            <label class="tech-label">Post Title</label>
            <input type="text" name="title" class="tech-input" placeholder="What is this discussion about?" required>
            
            <label class="tech-label">Post Content</label>
            <textarea name="content" class="tech-input" rows="6" placeholder="Write your thoughts or questions here..." required></textarea>
            
            <label class="tech-label">Upload Images (Optional)</label> <div class="image-upload-wrapper" onclick="document.getElementById('post_images').click();">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #00f2fe; margin-bottom: 10px;"></i>
                <div style="color: #fff; font-weight: 800; font-size: 1.1rem;">Click to upload images</div>
                <div style="color: #64748b; font-size: 0.85rem; margin-top: 5px;">Supports JPG, PNG, WEBP (Max 10 files)</div>
                <input type="file" id="post_images" name="post_images[]" accept="image/*" multiple style="display: none;" onchange="previewImages(event)">
            </div>
            <div class="image-preview-grid" id="image-preview-container"></div>
            
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" name="submit_post" class="tech-btn tech-btn-primary" style="padding: 14px 40px; font-size:1rem;">Publish Post</button> <button type="button" class="tech-btn" style="border-color:rgba(255,255,255,0.2); color:#fff;" onclick="togglePostForm()">Cancel</button>
            </div>
        </form>
    </div>

    <div class="community-layout">
        
        <div class="main-feed-column">
            <div class="filter-bar">
                <a href="community.php?filter=All" class="filter-btn <?php echo $filter_type == 'All' ? 'active' : ''; ?>">All Posts</a>
                <a href="community.php?filter=Trending" class="filter-btn <?php echo $filter_type == 'Trending' ? 'active' : ''; ?>"><i class="fas fa-fire" style="color: #ff4d4d; margin-right: 5px;"></i> Trending</a>
                <a href="community.php?filter=Showcase" class="filter-btn <?php echo $filter_type == 'Showcase' ? 'active' : ''; ?>"><i class="fas fa-desktop"></i> Showcases</a>
                <a href="community.php?filter=Discussion" class="filter-btn <?php echo $filter_type == 'Discussion' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Discussions</a>
            </div>

            <div class="post-feed">
                <?php if($posts->num_rows > 0): while($p = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        
                        <div class="post-header">
                            <div class="avatar"><?php echo strtoupper(substr($p['username'], 0, 1)); ?></div>
                            <div class="author-info">
                                <div class="author-name">
                                    <?php echo htmlspecialchars($p['username']); ?>
                                    <?php echo getRankBadge($p['reward_coins']); ?>
                                </div>
                                <div class="post-time"><?php echo date('M d, Y • H:i', strtotime($p['created_at'])); ?></div>
                            </div>
                            
                            <?php if($p['post_type'] == 'Question'): ?>
                                <span style="background: rgba(255,152,0,0.15); color: #ffb74d; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 800;"><i class="fas fa-question-circle"></i> Question</span>
                            <?php elseif($p['post_type'] == 'Discussion'): ?>
                                <span style="background: rgba(0,188,212,0.15); color: #4dd0e1; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 800;"><i class="fas fa-comments"></i> Discussion</span>
                            <?php elseif($p['post_type'] == 'Showcase'): ?>
                                <span style="background: rgba(168, 85, 247, 0.15); color: #d8b4fe; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 800;"><i class="fas fa-desktop"></i> Showcase</span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="post_detail.php?id=<?php echo $p['post_id']; ?>" style="text-decoration:none;">
                            <h2 class="post-title"><?php echo htmlspecialchars($p['title']); ?></h2>
                        </a>
                        <div class="post-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>
                        
                        <?php 
                        $imgs = !empty($p['post_images']) ? json_decode($p['post_images'], true) : [];
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

                        <div class="post-actions">
                            <a href="community.php?action=like&post_id=<?php echo $p['post_id']; ?><?php echo htmlspecialchars($filter_type) != 'All' ? '&filter='.htmlspecialchars($filter_type) : ''; ?>" class="action-btn <?php echo $p['user_liked'] > 0 ? 'liked' : ''; ?>">
                                <i class="<?php echo $p['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $p['like_count']; ?> Likes </a>
                            
                            <a href="post_detail.php?id=<?php echo $p['post_id']; ?>" class="action-btn">
                                <i class="far fa-comment-dots"></i> <?php echo $p['comment_count']; ?> Comments </a>

                            <?php if ($p['customer_id'] == $customer_id): ?>
                                <a href="community.php?action=delete&post_id=<?php echo $p['post_id']; ?>" class="action-btn" style="color: #ff4d4d; border-color: rgba(255, 77, 77, 0.3); margin-left:auto;" onclick="return confirm('Delete this post?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 60px 20px; background: #111827; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <i class="fas fa-inbox" style="font-size: 3.5rem; color: #475569; margin-bottom: 20px;"></i>
                        <h3 style="margin:0 0 10px 0; color:#fff;">It's quiet here...</h3>
                        <p style="color: #94a3b8; margin-bottom: 25px;">Be the first to start a discussion.</p> <button onclick="togglePostForm()" class="tech-btn tech-btn-primary">Create Post</button>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="sidebar">
            <div class="widget-cta">
                <h3><i class="fas fa-bolt" style="color:#ffd700; margin-right:8px;"></i> Show Off Your Build</h3>
                <p>Designed a killer rig in our PC Builder? Publish it to the community and let others see your creation.</p>
                <a href="builder.php" class="tech-btn" style="width:100%; justify-content:center; background: rgba(0,0,0,0.5); border-color: #00f2fe; color: #00f2fe;"><i class="fas fa-tools"></i> Launch PC Builder</a>
            </div>
            
            <div class="widget">
                <h3><i class="fas fa-chart-bar"></i> Community Stats</h3> <div class="stat-row">
                    <span>Total Posts</span>
                    <span class="stat-value"><?php echo $total_posts; ?></span>
                </div>
                <div class="stat-row">
                    <span>Status</span>
                    <span class="stat-value" style="color: #00e676;">Online</span>
                </div>
            </div>

            <div class="widget">
                <h3><i class="fas fa-trophy" style="color: #ffd700;"></i> Top Contributors</h3> <ul class="top-user-list">
                    <?php 
                    $rank = 1;
                    if($top_builders->num_rows > 0): 
                        while($tb = $top_builders->fetch_assoc()): 
                    ?>
                    <li>
                        <div class="top-avatar"><?php echo strtoupper(substr($tb['username'], 0, 1)); ?></div>
                        <div style="flex: 1;">
                            <div style="color: #fff; font-weight: bold; font-size: 1rem;">
                                <?php echo htmlspecialchars($tb['username']); ?>
                            </div>
                            <div style="margin-top: 4px;">
                                <?php echo getRankBadge($tb['reward_coins']); ?>
                            </div>
                        </div>
                        <?php if($rank == 1) echo '<i class="fas fa-medal" style="color: #ffd700; font-size: 1.5rem;"></i>'; ?>
                    </li>
                    <?php $rank++; endwhile; else: ?>
                    <li style="color: #64748b; font-size: 0.85rem;">No data yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

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
    function togglePostForm() {
        const form = document.getElementById('post-form');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
        if (form.style.display === 'block') {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function selectPostType(cardElement, typeValue) {
        document.querySelectorAll('.type-card').forEach(card => {
            card.classList.remove('selected');
            card.querySelector('input[type="radio"]').checked = false;
        });
        cardElement.classList.add('selected');
        cardElement.querySelector('input[type="radio"]').checked = true;

        const buildSelect = document.getElementById('build_select_container');
        if (typeValue === 'Showcase') {
            buildSelect.style.display = 'block';
            buildSelect.querySelector('select').setAttribute('required', 'required');
        } else {
            buildSelect.style.display = 'none';
            buildSelect.querySelector('select').removeAttribute('required');
        }
    }

    function previewImages(event) {
        const container = document.getElementById('image-preview-container');
        container.innerHTML = ''; 
        const files = event.target.files;
        if (files) {
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-img-box';
                    div.innerHTML = `<img src="${e.target.result}">`;
                    container.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    }

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