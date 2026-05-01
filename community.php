<?php
ob_start();
session_start();
require_once 'config.php';

// 验证是否登录
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
$customer_id = $_SESSION['customer_id'];
$sys_msg = $sys_err = "";

// ==========================================
// 1. 处理点赞逻辑 (Like / Unlike)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'like' && isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    $check_like = $conn->query("SELECT * FROM community_likes WHERE post_id = $post_id AND customer_id = $customer_id");
    if ($check_like->num_rows > 0) {
        $conn->query("DELETE FROM community_likes WHERE post_id = $post_id AND customer_id = $customer_id");
    } else {
        $conn->query("INSERT INTO community_likes (post_id, customer_id) VALUES ($post_id, $customer_id)");
    }
    header("Location: community.php"); 
    exit();
}

// ==========================================
// 2. 处理发布新帖 (Publish Post)
// ==========================================
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
            $sys_msg = "Signal transmitted successfully.";
        } else {
            $sys_err = "System error: Failed to transmit.";
        }
    }
}

// ==========================================
// 3. 抓取页面所需数据
// ==========================================
$my_builds = $conn->query("SELECT pc_build, build_name, total_price FROM saved_builds WHERE customer_id = $customer_id ORDER BY created_at DESC");

// 过滤器逻辑
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$where_clause = "";
if ($filter_type == 'Showcase') $where_clause = "WHERE cp.post_type = 'Showcase'";
elseif ($filter_type == 'Discussion') $where_clause = "WHERE cp.post_type IN ('Discussion', 'Question')";

// 抓取帖子及用户信息
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

// 抓取侧边栏统计数据
$total_posts = $conn->query("SELECT COUNT(*) FROM community_posts")->fetch_row()[0];
$top_builders = $conn->query("SELECT c.username, COUNT(cp.post_id) as post_count FROM customers c JOIN community_posts cp ON c.customer_id = cp.customer_id GROUP BY c.customer_id ORDER BY post_count DESC LIMIT 3");

// 头衔计算函数
function getRankBadge($coins) {
    if ($coins >= 1000) return '<span class="rank-badge elite">Elite Architect</span>';
    if ($coins >= 500) return '<span class="rank-badge pro">Pro Builder</span>';
    return '<span class="rank-badge novice">Tech Enthusiast</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Neural Network - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(168, 85, 247, 0.05) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1250px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        /* 头部设计优化 */
        .community-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px;}
        .community-header h1 { margin: 0; font-size: 2.5rem; font-weight: 900; color: #fff; letter-spacing: -1px; }
        .community-header p { margin: 5px 0 0 0; color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;}
        
        .tech-btn { background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 12px 25px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }

        /* 发帖表单 Modal */
        .post-form-container { display: none; background: rgba(10,10,15,0.8); backdrop-filter: blur(20px); border: 1px dashed rgba(0,242,254,0.4); padding: 30px; border-radius: 12px; margin-bottom: 30px; animation: slideDown 0.3s ease; box-shadow: 0 20px 40px rgba(0,0,0,0.5);}
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .tech-input { width: 100%; background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 12px; border-radius: 6px; font-size: 0.95rem; font-family: 'Inter', sans-serif; margin-bottom: 20px; box-sizing: border-box; }
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.05); }
        .tech-label { color: #94a3b8; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; }

        /* 🌟 核心：双栏布局 */
        .community-layout { display: grid; grid-template-columns: 1fr 320px; gap: 30px; align-items: start;}

        /* --- 左侧主信息流 --- */
        
        /* 过滤器 (Filter Pills) */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; }
        .filter-btn { padding: 8px 18px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: 0.3s; background: rgba(255,255,255,0.02);}
        .filter-btn:hover { background: rgba(255,255,255,0.05); }
        .filter-btn.active { background: #00f2fe; color: #000; border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.3); }

        .post-feed { display: flex; flex-direction: column; gap: 20px; }
        
        /* 🌟 帖子卡片全面升级 */
        .post-card { background: rgba(15,15,20,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; transition: 0.3s; position: relative; overflow: hidden;}
        .post-card:hover { border-color: rgba(0,242,254,0.3); background: rgba(15,15,20,0.8); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .post-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #00f2fe, #4facfe); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem; color: #000; box-shadow: 0 0 15px rgba(0, 242, 254, 0.3); text-transform: uppercase;}
        
        .author-info { flex: 1; }
        .author-name { color: #fff; font-weight: 800; font-size: 1.05rem; display: flex; align-items: center; gap: 10px;}
        .post-time { font-size: 0.75rem; color: #64748b; font-family: 'JetBrains Mono', monospace; margin-top: 3px; }
        
        /* 头衔系统 Badge */
        .rank-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; font-family: 'Inter'; }
        .rank-badge.elite { background: rgba(255, 215, 0, 0.1); color: #ffd700; border: 1px solid #ffd700; }
        .rank-badge.pro { background: rgba(168, 85, 247, 0.1); color: #a855f7; border: 1px solid #a855f7; }
        .rank-badge.novice { background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.2); }

        .post-title { font-size: 1.3rem; font-weight: 800; margin: 0 0 10px 0; color: #fff; line-height: 1.4;}
        .post-content { color: #94a3b8; line-height: 1.6; font-size: 0.95rem; margin-bottom: 20px; white-space: pre-wrap; }
        
        /* 装机秀专属模块强化 */
        .showcase-box { background: linear-gradient(90deg, rgba(168,85,247,0.1), transparent); border: 1px dashed rgba(168,85,247,0.4); border-left: 4px solid #a855f7; padding: 20px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .showcase-badge { background: #a855f7; color: #000; font-size: 0.7rem; font-weight: 900; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-bottom: 8px; display: inline-block; box-shadow: 0 0 10px rgba(168,85,247,0.5);}
        .showcase-price { color: #fff; font-family: 'JetBrains Mono'; font-weight: 800; font-size: 1.3rem; text-shadow: 0 0 10px rgba(255,255,255,0.3);}
        
        /* 互动底栏 */
        .post-actions { display: flex; gap: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; }
        .action-btn { background: transparent; border: none; color: #64748b; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none;}
        .action-btn:hover { color: #00f2fe; }
        .action-btn.liked { color: #ff007f; }
        .action-btn.liked i { font-weight: 900; filter: drop-shadow(0 0 5px #ff007f); }

        /* --- 右侧挂件栏 (Widgets) --- */
        .sidebar { display: flex; flex-direction: column; gap: 25px; }
        .widget { background: rgba(15,15,20,0.5); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; }
        .widget h3 { font-size: 1rem; color: #fff; margin: 0 0 15px 0; font-weight: 800; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 10px;}
        .widget h3 i { color: #00f2fe; }

        .stat-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.85rem; color: #cbd5e1; }
        .stat-value { font-family: 'JetBrains Mono'; font-weight: bold; color: #fff; }
        
        .top-user-list { list-style: none; padding: 0; margin: 0; }
        .top-user-list li { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .top-user-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .top-avatar { width: 35px; height: 35px; border-radius: 50%; background: #1a1a24; border: 1px solid #333; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 0.9rem;}
        
        .tag-cloud { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-pill { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; color: #94a3b8; cursor: pointer; transition: 0.3s; }
        .tag-pill:hover { border-color: #00f2fe; color: #00f2fe; }

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
        <button class="tech-btn" onclick="togglePostForm()"><i class="fas fa-pen-nib"></i> Compose Signal</button>
    </div>

    <?php if($sys_msg) echo "<div style='color:#00e676; padding:15px; border:1px solid #00e676; border-radius:8px; margin-bottom:20px; background:rgba(0,230,118,0.1);'><i class='fas fa-check'></i> $sys_msg</div>"; ?>
    <?php if($sys_err) echo "<div style='color:#ff4d4d; padding:15px; border:1px solid #ff4d4d; border-radius:8px; margin-bottom:20px; background:rgba(255,77,77,0.1);'><i class='fas fa-exclamation-triangle'></i> $sys_err</div>"; ?>

    <!-- 发帖控制台 -->
    <div class="post-form-container" id="post-form">
        <h3 style="margin:0 0 20px 0; color:#fff; font-size: 1.2rem;">Create a New Post</h3>
        <form method="POST" action="community.php">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div>
                    <label class="tech-label">Category</label>
                    <select name="post_type" id="post_type" class="tech-input" onchange="toggleBuildSelect()">
                        <option value="Discussion">💬 General Discussion</option>
                        <option value="Question">❓ Technical Question</option>
                        <option value="Showcase">🔥 Rig Showcase</option>
                    </select>
                </div>
                <div id="build_select_container" style="display: none;">
                    <label class="tech-label">Select Blueprint to Showcase</label>
                    <select name="pc_build_id" class="tech-input">
                        <option value="">-- Choose from your Saved Builds --</option>
                        <?php 
                        if ($my_builds->num_rows > 0) {
                            while($mb = $my_builds->fetch_assoc()) {
                                echo "<option value='{$mb['pc_build']}'>{$mb['build_name']} (RM " . number_format($mb['total_price'], 2) . ")</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No builds found. Go build one first!</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <label class="tech-label">Subject Title</label>
            <input type="text" name="title" class="tech-input" placeholder="Enter an eye-catching title..." required>
            
            <label class="tech-label">Content Body</label>
            <textarea name="content" class="tech-input" rows="6" placeholder="Share your thoughts, ask a question, or describe your rig in detail..." required></textarea>
            
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" name="submit_post" class="tech-btn" style="background:#00f2fe; color:#000; border:none; padding: 12px 30px;">Publish to Network</button>
                <button type="button" class="tech-btn" style="border-color:rgba(255,255,255,0.2); color:#fff;" onclick="togglePostForm()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- 🌟 终极双栏布局开始 -->
    <div class="community-layout">
        
        <!-- 左侧：瀑布流 -->
        <div class="main-feed-column">
            
            <!-- 过滤器导航 -->
            <div class="filter-bar">
                <a href="community.php?filter=All" class="filter-btn <?php echo $filter_type == 'All' ? 'active' : ''; ?>">All Signals</a>
                <a href="community.php?filter=Showcase" class="filter-btn <?php echo $filter_type == 'Showcase' ? 'active' : ''; ?>"><i class="fas fa-fire"></i> Showcases</a>
                <a href="community.php?filter=Discussion" class="filter-btn <?php echo $filter_type == 'Discussion' ? 'active' : ''; ?>">Discussions & Q&A</a>
            </div>

            <!-- 帖子列表 -->
            <div class="post-feed">
                <?php if($posts->num_rows > 0): while($p = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        
                        <!-- 用户头部信息 -->
                        <div class="post-header">
                            <!-- 动态提取首字母做头像 -->
                            <div class="avatar"><?php echo strtoupper(substr($p['username'], 0, 1)); ?></div>
                            <div class="author-info">
                                <div class="author-name">
                                    <?php echo htmlspecialchars($p['username']); ?>
                                    <?php echo getRankBadge($p['reward_coins']); ?>
                                </div>
                                <div class="post-time"><?php echo date('M d, Y • H:i', strtotime($p['created_at'])); ?></div>
                            </div>
                            
                            <!-- 帖子类型小标签 -->
                            <?php if($p['post_type'] == 'Question'): ?>
                                <span style="background: rgba(255,152,0,0.1); color: #ff9800; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold;"><i class="fas fa-question-circle"></i> Question</span>
                            <?php elseif($p['post_type'] == 'Discussion'): ?>
                                <span style="background: rgba(0,188,212,0.1); color: #00bcd4; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold;"><i class="fas fa-comments"></i> Discussion</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 标题与内容 -->
                        <h2 class="post-title"><?php echo htmlspecialchars($p['title']); ?></h2>
                        <div class="post-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>
                        
                        <!-- 🌟 装机秀专属UI -->
                        <?php if ($p['post_type'] == 'Showcase' && $p['build_name']): ?>
                            <div class="showcase-box">
                                <div>
                                    <span class="showcase-badge"><i class="fas fa-fire"></i> Official Showcase</span>
                                    <h4 style="margin: 0; color: #fff; font-size: 1.15rem; font-weight: 800;"><?php echo htmlspecialchars($p['build_name']); ?></h4>
                                </div>
                                <div style="text-align: right;">
                                    <div class="showcase-price">RM <?php echo number_format($p['total_price'], 2); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 互动按钮 -->
                        <div class="post-actions">
                            <a href="community.php?action=like&post_id=<?php echo $p['post_id']; ?>" class="action-btn <?php echo $p['user_liked'] > 0 ? 'liked' : ''; ?>">
                                <i class="<?php echo $p['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $p['like_count']; ?>
                            </a>
                            <a href="#" class="action-btn">
                                <i class="far fa-comment-dots"></i> <?php echo $p['comment_count']; ?> Reply
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-share-nodes"></i> Share
                            </a>
                            
                            <?php if ($p['post_type'] == 'Showcase' && $p['pc_build_id']): ?>
                                <a href="load_build.php?id=<?php echo $p['pc_build_id']; ?>" class="action-btn" style="margin-left: auto; color: #00f2fe; border: 1px solid rgba(0,242,254,0.3); padding: 5px 12px; border-radius: 4px;">
                                    <i class="fas fa-download"></i> Load Specs
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 50px; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <i class="fas fa-ghost" style="font-size: 3rem; color: #64748b; margin-bottom: 15px;"></i>
                        <p style="color: #cbd5e1;">No signals found in this sector.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- 右侧：环境挂件监控区 (Widgets) -->
        <div class="sidebar">
            
            <!-- 挂件 1：系统状态 -->
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
                <div class="stat-row" style="margin-bottom: 0;">
                    <span>System Latency</span>
                    <span class="stat-value" style="color: #00f2fe;">12ms</span>
                </div>
            </div>

            <!-- 挂件 2：风云榜 -->
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
                            <div style="color: #fff; font-weight: bold; font-size: 0.9rem;"><?php echo htmlspecialchars($tb['username']); ?></div>
                            <div style="color: #64748b; font-size: 0.75rem;"><?php echo $tb['post_count']; ?> Contributions</div>
                        </div>
                        <?php if($rank == 1) echo '<i class="fas fa-medal" style="color: #ffd700; font-size: 1.2rem;"></i>'; ?>
                    </li>
                    <?php $rank++; endwhile; else: ?>
                    <li style="color: #64748b; font-size: 0.8rem;">No data yet.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 挂件 3：热门标签 -->
            <div class="widget">
                <h3><i class="fas fa-hashtag"></i> Trending Topics</h3>
                <div class="tag-cloud">
                    <span class="tag-pill">#RTX4090</span>
                    <span class="tag-pill">#WaterCooling</span>
                    <span class="tag-pill">#AM5</span>
                    <span class="tag-pill">#CableManagement</span>
                    <span class="tag-pill">#BudgetBuild</span>
                </div>
            </div>

        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- 交互逻辑 -->
<script>
    function togglePostForm() {
        const form = document.getElementById('post-form');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
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