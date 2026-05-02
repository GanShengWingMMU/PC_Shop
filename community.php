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

// 1. 处理点赞逻辑
if (isset($_GET['action']) && $_GET['action'] == 'like' && isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    $check_like = $conn->query("SELECT * FROM community_likes WHERE post_id = $post_id AND customer_id = $customer_id");
    if ($check_like->num_rows > 0) {
        $conn->query("DELETE FROM community_likes WHERE post_id = $post_id AND customer_id = $customer_id");
    } else {
        $conn->query("INSERT INTO community_likes (post_id, customer_id) VALUES ($post_id, $customer_id)");
    }
    // 保持当前的 filter 状态
    $filter_param = isset($_GET['filter']) ? "&filter=" . $_GET['filter'] : "";
    header("Location: community.php?$filter_param"); 
    exit();
}

// 2. 处理发布新帖
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'];
    $pc_build_id = ($post_type == 'Showcase' && !empty($_POST['pc_build_id'])) ? intval($_POST['pc_build_id']) : 'NULL';

    if (empty($title) || empty($content)) {
        $sys_err = "Data corrupted: Title and Content are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO community_posts (customer_id, pc_build_id, title, content, post_type) VALUES (?, $pc_build_id, ?, ?, ?)");
        $stmt->bind_param("isss", $customer_id, $title, $content, $post_type);
        if ($stmt->execute()) {
            $sys_msg = "Signal transmitted successfully!";
        } else {
            $sys_err = "System error: Failed to transmit.";
        }
    }
}

// 3. 抓取页面数据
$my_builds = $conn->query("SELECT pc_build, build_name, total_price FROM saved_builds WHERE customer_id = $customer_id ORDER BY created_at DESC");

// 过滤器
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$where_clause = "";
if ($filter_type == 'Showcase') $where_clause = "WHERE cp.post_type = 'Showcase'";
elseif ($filter_type == 'Discussion') $where_clause = "WHERE cp.post_type IN ('Discussion', 'Question')";

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
    ORDER BY cp.created_at DESC
";
$posts = $conn->query($query_posts);

$total_posts = $conn->query("SELECT COUNT(*) FROM community_posts")->fetch_row()[0];
$top_builders = $conn->query("SELECT c.username, COUNT(cp.post_id) as post_count FROM customers c JOIN community_posts cp ON c.customer_id = cp.customer_id GROUP BY c.customer_id ORDER BY post_count DESC LIMIT 3");

function getRankBadge($coins) {
    if ($coins >= 1000) return '<span class="rank-badge elite" title="Elite Architect"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 500) return '<span class="rank-badge pro" title="Pro Builder"><i class="fas fa-star"></i> Pro</span>';
    return '<span class="rank-badge novice" title="Tech Enthusiast">Enthusiast</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 🌟 开启移动端视口缩放 -->
    <title>Neural Network - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(168, 85, 247, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1300px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        /* 头部高清晰度优化 */
        .community-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 2px solid rgba(255,255,255,0.05); padding-bottom: 20px;}
        .community-header h1 { margin: 0; font-size: 2.8rem; font-weight: 900; color: #fff; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .community-header p { margin: 5px 0 0 0; color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;}
        
        .tech-btn { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 12px 25px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;}
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px);}
        .tech-btn-primary { background: #00f2fe; color: #000; }

        /* 发帖表单: 增加可读性 */
        .post-form-container { display: none; background: #0b0f16; border: 1px solid #00f2fe; padding: 30px; border-radius: 12px; margin-bottom: 30px; animation: slideDown 0.3s ease; box-shadow: 0 20px 40px rgba(0, 242, 254, 0.1);}
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .tech-input { width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 15px; border-radius: 8px; font-size: 1rem; font-family: 'Inter', sans-serif; margin-bottom: 20px; box-sizing: border-box; transition: 0.3s;}
        .tech-input:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.1); }
        .tech-label { color: #cbd5e1; font-size: 0.9rem; font-weight: 700; margin-bottom: 10px; display: block; }
        .helper-text { font-size: 0.8rem; color: #64748b; margin-top: -15px; margin-bottom: 20px; display: block; }

        /* 双栏布局 */
        .community-layout { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start;}

        /* 过滤器 (明确的视觉反馈) */
        .filter-bar { display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; overflow-x: auto; white-space: nowrap;}
        .filter-btn { padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; font-size: 0.95rem; font-weight: 700; text-decoration: none; transition: 0.3s; background: rgba(255,255,255,0.02);}
        .filter-btn:hover { background: rgba(255,255,255,0.05); color: #fff;}
        .filter-btn.active { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border-color: #00f2fe; }

        .post-feed { display: flex; flex-direction: column; gap: 25px; }
        
        /* 🌟 高清晰度帖子卡片 */
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
        
        /* 🌟 超强 PC Builder 联动展示 UI */
        .showcase-box { background: rgba(168, 85, 247, 0.05); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 10px; padding: 20px; margin-bottom: 25px; }
        .showcase-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed rgba(168, 85, 247, 0.3); padding-bottom: 15px;}
        .showcase-badge { color: #d8b4fe; font-size: 0.8rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;}
        .showcase-price { color: #fff; font-family: 'JetBrains Mono'; font-weight: 800; font-size: 1.4rem; }
        
        /* 联动预览硬件模块 */
        .specs-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .spec-tag { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; color: #e2e8f0; display: flex; align-items: center; gap: 8px;}
        .spec-tag i { color: #00f2fe; }

        .post-actions { display: flex; gap: 20px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; flex-wrap: wrap;}
        .action-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none;}
        .action-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .action-btn.liked { background: rgba(255, 0, 127, 0.1); border-color: rgba(255, 0, 127, 0.3); color: #ff007f; }

        /* --- 右侧挂件栏 --- */
        .sidebar { display: flex; flex-direction: column; gap: 25px; }
        
        /* 🌟 新增：用户引导/行动号召 (CTA) 挂件 */
        .widget-cta { background: linear-gradient(135deg, rgba(0,242,254,0.1), rgba(168,85,247,0.1)); border: 1px solid #00f2fe; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 10px 20px rgba(0,242,254,0.1);}
        .widget-cta h3 { color: #fff; margin: 0 0 10px 0; font-size: 1.2rem; font-weight: 800;}
        .widget-cta p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; line-height: 1.5;}
        
        .widget { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; }
        .widget h3 { font-size: 1.1rem; color: #fff; margin: 0 0 20px 0; font-weight: 800; display: flex; align-items: center; gap: 10px;}
        .widget h3 i { color: #00f2fe; }

        .stat-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; color: #94a3b8; }
        .stat-value { font-family: 'JetBrains Mono'; font-weight: bold; color: #fff; }
        
        .top-user-list { list-style: none; padding: 0; margin: 0; }
        .top-user-list li { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .top-user-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .top-avatar { width: 40px; height: 40px; border-radius: 10px; background: #1f2937; border: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: center; align-items: center; font-weight: 900; font-size: 1rem;}

        /* 📱 响应式设计 (Responsive Design) */
        @media (max-width: 992px) {
            .community-layout { grid-template-columns: 1fr; }
            .sidebar { order: -1; margin-bottom: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;}
            .community-header h1 { font-size: 2.2rem; }
        }
        @media (max-width: 768px) {
            .community-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .sidebar { grid-template-columns: 1fr; }
            .post-actions { flex-wrap: wrap; }
            .action-btn { flex-grow: 1; justify-content: center; }
        }
         /* 🌟 极客悬浮窗 (Tech Tooltip) 动画与样式 */
.spec-tag { 
    background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); padding: 6px 12px; 
    border-radius: 6px; font-size: 0.85rem; color: #e2e8f0; display: flex; align-items: center; gap: 8px;
    position: relative; cursor: crosshair; transition: 0.3s;
}
.spec-tag i { color: #00f2fe; }
.spec-tag:hover { border-color: #00f2fe; background: rgba(0, 242, 254, 0.1); }

/* Tooltip 本体：初始隐藏并往下沉 */
.tech-tooltip {
    position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%) translateY(10px);
    background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(10px);
    border: 1px solid #00f2fe; border-radius: 8px; padding: 15px; width: 260px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px rgba(0,242,254,0.2);
    opacity: 0; visibility: hidden; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    z-index: 100; pointer-events: none;
}
/* 小三角箭头 */
.tech-tooltip::after {
    content: ''; position: absolute; top: 100%; left: 50%; margin-left: -6px;
    border-width: 6px; border-style: solid; border-color: #00f2fe transparent transparent transparent;
}
/* 鼠标悬停：浮现并上浮 */
.spec-tag:hover .tech-tooltip { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }

/* Tooltip 内部排版 */
.tt-cat { color: #00f2fe; font-family: 'JetBrains Mono'; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
.tt-name { color: #fff; font-size: 0.95rem; font-weight: 800; line-height: 1.3; margin-bottom: 15px; }
.tt-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 10px; }
.tt-price { color: #a855f7; font-family: 'JetBrains Mono'; font-weight: 900; font-size: 1.1rem; }
.tt-stock { font-size: 0.75rem; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<div class="dashboard-container">
    
    <div class="community-header">
        <div>
            <p><i class="fas fa-globe-asia"></i> GridCitY Central Node</p>
            <h1>Neural Network</h1>
        </div>
        <button class="tech-btn tech-btn-primary" onclick="togglePostForm()"><i class="fas fa-pen-nib"></i> Compose Signal</button>
    </div>

    <?php if($sys_msg) echo "<div style='color:#00e676; padding:15px; border:1px solid #00e676; border-radius:8px; margin-bottom:20px; background:rgba(0,230,118,0.1);'><i class='fas fa-check'></i> $sys_msg</div>"; ?>
    <?php if($sys_err) echo "<div style='color:#ff4d4d; padding:15px; border:1px solid #ff4d4d; border-radius:8px; margin-bottom:20px; background:rgba(255,77,77,0.1);'><i class='fas fa-exclamation-triangle'></i> $sys_err</div>"; ?>

    <!-- 发帖控制台 (增加引导提示) -->
    <div class="post-form-container" id="post-form">
        <h3 style="margin:0 0 20px 0; color:#fff; font-size: 1.4rem;"><i class="fas fa-satellite-dish" style="color:#00f2fe; margin-right:10px;"></i> Initialize Transmission</h3>
        <form method="POST" action="community.php">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div>
                    <label class="tech-label">Transmission Type</label>
                    <select name="post_type" id="post_type" class="tech-input" onchange="toggleBuildSelect()">
                        <option value="Discussion">💬 General Discussion</option>
                        <option value="Question">❓ Ask for Help</option>
                        <option value="Showcase">🔥 Showcase My Rig</option>
                    </select>
                    <span class="helper-text">Select what kind of signal you are sending.</span>
                </div>
                <div id="build_select_container" style="display: none;">
                    <label class="tech-label">Select Your Blueprint</label>
                    <select name="pc_build_id" class="tech-input">
                        <option value="">-- Choose from your Saved Builds --</option>
                        <?php 
                        if ($my_builds->num_rows > 0) {
                            while($mb = $my_builds->fetch_assoc()) {
                                echo "<option value='{$mb['pc_build']}'>{$mb['build_name']} (RM " . number_format($mb['total_price'], 2) . ")</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No builds found! Please use PC Builder first.</option>";
                        }
                        ?>
                    </select>
                    <span class="helper-text">Only saved builds can be showcased to the network.</span>
                </div>
            </div>
            
            <label class="tech-label">Subject Title</label>
            <input type="text" name="title" class="tech-input" placeholder="Give your post a clear and catchy title..." required>
            
            <label class="tech-label">Content Body</label>
            <textarea name="content" class="tech-input" rows="6" placeholder="Describe your build, ask your question, or share your thoughts in detail here..." required></textarea>
            
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" name="submit_post" class="tech-btn tech-btn-primary" style="padding: 14px 40px; font-size:1rem;">Publish to Network</button>
                <button type="button" class="tech-btn" style="border-color:rgba(255,255,255,0.2); color:#fff;" onclick="togglePostForm()">Cancel</button>
            </div>
        </form>
    </div>

    <div class="community-layout">
        
        <!-- 左侧：主信息流 -->
        <div class="main-feed-column">
            
            <div class="filter-bar">
                <a href="community.php?filter=All" class="filter-btn <?php echo $filter_type == 'All' ? 'active' : ''; ?>">All Signals</a>
                <a href="community.php?filter=Showcase" class="filter-btn <?php echo $filter_type == 'Showcase' ? 'active' : ''; ?>"><i class="fas fa-fire"></i> Build Showcases</a>
                <a href="community.php?filter=Discussion" class="filter-btn <?php echo $filter_type == 'Discussion' ? 'active' : ''; ?>">Discussions & Q&A</a>
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
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="post-title"><?php echo htmlspecialchars($p['title']); ?></h2>
                        <div class="post-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>
                        
                        <!-- 🌟 极致 PC Builder 联动展示 -->
                        <?php if ($p['post_type'] == 'Showcase' && $p['pc_build_id']): ?>
                            <div class="showcase-box">
                                <div class="showcase-header">
                                    <div style="flex:1;">
                                        <span class="showcase-badge"><i class="fas fa-microchip"></i> Hardware Showcase</span>
                                        <h4 style="margin: 5px 0 0 0; color: #fff; font-size: 1.3rem; font-weight: 900;"><?php echo htmlspecialchars($p['build_name']); ?></h4>
                                    </div>
                                    <div class="showcase-price">RM <?php echo number_format($p['total_price'], 2); ?></div>
                                </div>
                                
                                <!-- 动态抓取这台机器的核心硬件 (CPU, GPU, MB) -->
<!-- 联动预览硬件模块 (带终极悬浮窗交互) -->
<div class="specs-preview">
    <?php
    $b_id = $p['pc_build_id'];
    // 🌟 升级：多抓取 price, stock_quantity 和 status
    $specs_sql = "SELECT c.category_name, p.product_name, p.price, p.stock_quantity, p.status 
                  FROM build_items bi 
                  JOIN products p ON bi.product_id = p.product_id 
                  JOIN categories c ON p.category_id = c.category_id 
                  WHERE bi.pc_build = $b_id 
                  AND (c.category_name LIKE '%CPU%' OR c.category_name LIKE '%GPU%' OR c.category_name LIKE '%Motherboard%')
                  LIMIT 3";
    $specs_res = $conn->query($specs_sql);
    
    if ($specs_res->num_rows > 0) {
        while ($spec = $specs_res->fetch_assoc()) {
            $icon = 'fa-microchip'; 
            if (stripos($spec['category_name'], 'GPU') !== false) $icon = 'fa-video';
            if (stripos($spec['category_name'], 'Motherboard') !== false) $icon = 'fa-chess-board';
            
            $short_name = strlen($spec['product_name']) > 20 ? substr($spec['product_name'],0,20)."..." : $spec['product_name'];
            
            // 库存逻辑判断
            $stock_color = $spec['stock_quantity'] > 0 ? '#00e676' : '#ff4d4d';
            $stock_text = $spec['stock_quantity'] > 0 ? $spec['stock_quantity'].' In Stock' : 'Out of Stock';
            
            echo "
            <div class='spec-tag has-tooltip'>
                <i class='fas $icon'></i> ".htmlspecialchars($short_name)."
                
                <!-- 🌟 隐藏的悬浮数据面板 (Tooltip) -->
                <div class='tech-tooltip'>
                    <div class='tt-cat'>".htmlspecialchars($spec['category_name'])."</div>
                    <div class='tt-name'>".htmlspecialchars($spec['product_name'])."</div>
                    <div class='tt-footer'>
                        <span class='tt-price'>RM ".number_format($spec['price'], 2)."</span>
                        <span class='tt-stock' style='color: $stock_color;'><i class='fas fa-box'></i> $stock_text</span>
                    </div>
                </div>
            </div>";
        }
    } else {
        echo "<span style='color:#64748b; font-size:0.85rem;'>Full specs available inside.</span>";
    }
    ?>
</div>
                                
                                <a href="load_build.php?id=<?php echo $p['pc_build_id']; ?>&action=cart" class="tech-btn" style="width: 100%; text-align: center; display: block; padding: 10px; font-size:0.9rem;">
                                <i class="fas fa-cart-plus"></i> Load This Build to Cart
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="post-actions">
                            <a href="community.php?action=like&post_id=<?php echo $p['post_id']; ?><?php echo $filter_type != 'All' ? '&filter='.$filter_type : ''; ?>" class="action-btn <?php echo $p['user_liked'] > 0 ? 'liked' : ''; ?>">
                                <i class="<?php echo $p['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $p['like_count']; ?> Likes
                            </a>
                            <a href="#" class="action-btn">
                                <i class="far fa-comment-dots"></i> <?php echo $p['comment_count']; ?> Replies
                            </a>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 60px 20px; background: #111827; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <i class="fas fa-satellite" style="font-size: 3.5rem; color: #475569; margin-bottom: 20px;"></i>
                        <h3 style="margin:0 0 10px 0; color:#fff;">It's quiet in this sector...</h3>
                        <p style="color: #94a3b8; margin-bottom: 25px;">Be the first to initiate a transmission.</p>
                        <button onclick="togglePostForm()" class="tech-btn tech-btn-primary">Compose Signal</button>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- 右侧：挂件栏 (在移动端会移到最上方) -->
        <div class="sidebar">
            
            <!-- 🌟 新增：用户交互引导 CTA -->
            <div class="widget-cta">
                <h3><i class="fas fa-bolt" style="color:#ffd700; margin-right:8px;"></i> Show Off Your Masterpiece</h3>
                <p>Designed a killer rig in our PC Builder? Publish it to the Neural Network and let others marvel at your creation (or load it to buy!).</p>
                <a href="builder.php" class="tech-btn" style="width:100%; justify-content:center; background: rgba(0,0,0,0.5); border-color: #00f2fe; color: #00f2fe;"><i class="fas fa-tools"></i> Launch PC Builder</a>
            </div>
            
            <div class="widget">
                <h3><i class="fas fa-chart-network"></i> Network Status</h3>
                <div class="stat-row">
                    <span>Total Signals</span>
                    <span class="stat-value"><?php echo $total_posts; ?></span>
                </div>
                <div class="stat-row">
                    <span>Active Nodes</span>
                    <span class="stat-value" style="color: #00e676;">Online</span>
                </div>
            </div>

            <div class="widget">
                <h3><i class="fas fa-trophy" style="color: #ffd700;"></i> Top Architects</h3>
                <ul class="top-user-list">
                    <?php 
                    $rank = 1;
                    if($top_builders->num_rows > 0): 
                        while($tb = $top_builders->fetch_assoc()): 
                    ?>
                    <li>
                        <div class="top-avatar"><?php echo strtoupper(substr($tb['username'], 0, 1)); ?></div>
                        <div style="flex: 1;">
                            <div style="color: #fff; font-weight: bold; font-size: 1rem;"><?php echo htmlspecialchars($tb['username']); ?></div>
                            <div style="color: #64748b; font-size: 0.8rem;"><?php echo $tb['post_count']; ?> Contributions</div>
                        </div>
                        <?php if($rank == 1) echo '<i class="fas fa-medal" style="color: #ffd700; font-size: 1.3rem;"></i>'; ?>
                    </li>
                    <?php $rank++; endwhile; else: ?>
                    <li style="color: #64748b; font-size: 0.85rem;">No data yet.</li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

    </div>
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

    function toggleBuildSelect() {
        const type = document.getElementById('post_type').value;
        const buildSelect = document.getElementById('build_select_container');
        if (type === 'Showcase') {
            buildSelect.style.display = 'block';
            buildSelect.querySelector('select').setAttribute('required', 'required');
        } else {
            buildSelect.style.display = 'none';
            buildSelect.querySelector('select').removeAttribute('required');
        }
    }
</script>

</body>
</html>