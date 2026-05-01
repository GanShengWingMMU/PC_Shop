<?php
session_start();
require_once 'config.php';

// =========================================================
// 🚀 [方案 A] 核心逻辑：获取用户数字 DNA 并判定 Persona
// =========================================================
$user_persona = 'Standard';
$dna = ['g' => 0, 'c' => 0, 's' => 0, 'e' => 0];

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    $dna_sql = "SELECT pref_gamer, pref_creator, pref_student, pref_enthusiast FROM customers WHERE customer_id = $cid";
    $dna_res = mysqli_query($conn, $dna_sql);
    if ($row = mysqli_fetch_assoc($dna_res)) {
        $dna = ['g' => $row['pref_gamer'], 'c' => $row['pref_creator'], 's' => $row['pref_student'], 'e' => $row['pref_enthusiast']];
        // 判定权重最高的身份
        arsort($dna);
        $top_key = key($dna);
        if ($dna[$top_key] > 0) {
            $user_persona = ($top_key == 'g') ? 'Gamer' : (($top_key == 'c') ? 'Creator' : (($top_key == 's') ? 'Student' : 'Enthusiast'));
        }
    }
}

// 根据 Persona 定义 Hero 视觉文案与氛围色
$hero_config = [
    'Gamer'   => ['title' => 'DOMINATE THE <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); -webkit-background-clip: text;">MATRIX.</span>', 'desc' => 'Your DNA suggests high-performance demands. We’ve prioritized GPU-heavy loadouts for your next conquest.', 'bg' => 'rgba(0,242,254,0.15)'],
    'Creator' => ['title' => 'RENDER YOUR <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #a855f7 0%, #ff007f 100%); -webkit-background-clip: text;">IMAGINATION.</span>', 'desc' => 'Optimized for multi-threaded excellence. Your profile is set for maximum creative throughput.', 'bg' => 'rgba(168,85,247,0.15)'],
    'Student' => ['title' => 'CODE THE <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #facc15 0%, #f59e0b 100%); -webkit-background-clip: text;">FUTURE.</span>', 'desc' => 'Efficiency meets reliability. We’ve tailored a balance of compilation speed and multitasking for your workflow.', 'bg' => 'rgba(250,204,21,0.15)'],
    'Standard' => ['title' => 'ENGINEERED FOR <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #00f2fe 0%, #a855f7 100%); -webkit-background-clip: text;">ABSOLUTE REALITY.</span>', 'desc' => 'Step beyond standard eCommerce. Our Multi-Scenario Heuristic AI analyzes bottlenecks and predicts FPS in milliseconds.', 'bg' => 'rgba(0,242,254,0.1)']
];
$current_hero = $hero_config[$user_persona] ?? $hero_config['Standard'];

// =========================================================
// 🚀 [方案 B] 核心逻辑：获取最近的社交装机动态 (Live Builds)
// =========================================================
$recent_builds = [];
// 尝试查询存档记录，使用 try-catch 防止表不存在导致崩溃
try {
    $recent_builds_sql = "SELECT sb.build_name, sb.total_price, c.username FROM saved_builds sb 
                          JOIN customers c ON sb.customer_id = c.customer_id 
                          ORDER BY sb.save_date DESC LIMIT 3";
    $recent_builds_res = @mysqli_query($conn, $recent_builds_sql);
    if ($recent_builds_res) {
        while($row = mysqli_fetch_assoc($recent_builds_res)) { $recent_builds[] = $row; }
    }
} catch (Exception $e) {
    // 静默失败：如果 saved_builds 不存在，动态栏将隐藏
}

// =========================================================
// 🚀 前序功能：拉取跑马灯库存数据
// =========================================================
$ticker_sql = "SELECT product_name, stock_quantity FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 5";
$ticker_res = mysqli_query($conn, $ticker_sql);
$ticker_items = [];
if ($ticker_res) {
    while($row = mysqli_fetch_assoc($ticker_res)) { $ticker_items[] = $row; }
}

// =========================================================
// 🚀 前序功能：拉取高端套餐并计算 AI 性能指标
// =========================================================
$feat_sql = "SELECT * FROM (
                SELECT pk.*, 
                (SELECT COALESCE(SUM(p.price), pk.price) 
                 FROM package_items pi JOIN products p ON pi.product_id = p.product_id 
                 WHERE pi.package_id = pk.package_id) AS real_price
                FROM packages pk WHERE pk.stock_status = 'Available'
            ) AS final_packages ORDER BY real_price DESC LIMIT 4";
$feat_res = mysqli_query($conn, $feat_sql);
$featured_packages = [];

if ($feat_res && mysqli_num_rows($feat_res) > 0) {
    while($pkg = mysqli_fetch_assoc($feat_res)) {
        $pkg_id = $pkg['package_id'];
        
        $cpu_sql = "SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = $pkg_id AND p.category_id = 1 LIMIT 1";
        $cpu_res = mysqli_query($conn, $cpu_sql);
        $cpu_p = ($cpu_res && $cpu_row = mysqli_fetch_assoc($cpu_res)) ? $cpu_row['price'] : 1500;

        $gpu_sql = "SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = $pkg_id AND p.category_id = 4 LIMIT 1";
        $gpu_res = mysqli_query($conn, $gpu_sql);
        $gpu_p = ($gpu_res && $gpu_row = mysqli_fetch_assoc($gpu_res)) ? $gpu_row['price'] : 3000;

        // 启发式 AI 引擎推演
        $cpu_index = pow($cpu_p / 3000, 0.6) * 100; 
        $gpu_index = pow($gpu_p / 6000, 0.6) * 100; 
        
        $pkg['fps_cyberpunk'] = round(30 + ($gpu_index * 0.85) + ($cpu_index * 0.15));
        $pkg['score_pr'] = round(($cpu_index * 0.65 + $gpu_index * 0.35) * 10);
        
        $featured_packages[] = $pkg;
    }
}

include 'includes/header.php';
?>

<style>
    :root { 
        --accent-cyan: #00f2fe; 
        --accent-purple: #a855f7;
        --accent-green: #34d399;
        --bg-dark: #050505; 
        --glass-bg: rgba(255, 255, 255, 0.02);
        --glass-border: rgba(255, 255, 255, 0.05);
    }
    
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap');
    
    body { background-color: var(--bg-dark); color: #fff; font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
    
    /* --- 📈 实时跑马灯 (Ticker) --- */
    .market-ticker { 
        width: 100vw; 
        margin-left: calc(-50vw + 50%); 
        background: #000; 
        border-bottom: 1px solid rgba(255,255,255,0.05); 
        color: #888; 
        font-size: 0.8rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        padding: 8px 0; 
        overflow: hidden; 
        white-space: nowrap; 
        display: flex; 
    }
    .ticker-content { display: inline-block; padding-left: 100%; animation: ticker-scroll 25s linear infinite; }
    @keyframes ticker-scroll { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }
    .ticker-item { margin-right: 50px; display: inline-flex; align-items: center; gap: 8px; }
    .ticker-alert { color: #ef4444; border: 1px solid #ef4444; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; }

    /* --- 🌌 沉浸式英雄区 (动态 Persona 背景) --- */
    .hero { 
        width: 100vw; 
        margin-left: calc(-50vw + 50%); 
        position: relative; 
        min-height: 85vh; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        overflow: hidden; 
        background: radial-gradient(ellipse at 50% 0%, <?php echo $current_hero['bg']; ?> 0%, var(--bg-dark) 80%); 
        transition: background 0.5s ease; 
    }
    .hero::before { 
        content: ''; 
        position: absolute; 
        width: 150vw; 
        height: 600px; 
        background: radial-gradient(ellipse at 50% 0%, <?php echo str_replace('0.15', '0.1', $current_hero['bg']); ?> 0%, transparent 70%); 
        top: -200px; 
        left: -25vw; 
        border-radius: 50%; 
        filter: blur(80px); 
        z-index: 0; 
    }
    .hero-content { position: relative; z-index: 2; text-align: center; max-width: 900px; padding: 0 20px; transform: translateY(-30px); }
    .hero-badge { display: inline-block; padding: 8px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; border-radius: 30px; font-size: 0.8rem; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 30px; }
    .hero-badge span { color: var(--accent-cyan); }
    .hero h1 { font-size: 5.5rem; font-weight: 900; line-height: 1; margin: 0 0 20px 0; letter-spacing: -2px; }
    .text-gradient { background: linear-gradient(135deg, #fff 0%, #888 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero p { font-size: 1.25rem; color: #888; max-width: 600px; margin: 0 auto 40px; line-height: 1.6; }
    
    .hero-btns { display: flex; gap: 20px; justify-content: center; }
    .btn-premium { padding: 16px 40px; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; }
    .btn-primary { background: #fff; color: #000; box-shadow: 0 10px 30px rgba(255,255,255,0.1); }
    .btn-primary:hover { background: var(--accent-cyan); box-shadow: 0 10px 40px rgba(0, 242, 254, 0.4); transform: translateY(-3px); }
    .btn-secondary { background: var(--glass-bg); color: #fff; border: 1px solid var(--glass-border); backdrop-filter: blur(10px); }
    .btn-secondary:hover { border-color: #fff; background: rgba(255,255,255,0.05); transform: translateY(-3px); }

    /* --- 📡 社交动态条 --- */
    .live-activity-bar { max-width: 1200px; margin: 0 auto 60px; padding: 0 20px; position: relative; z-index: 10; margin-top: -30px; }
    .activity-container { background: rgba(10,10,10,0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px 25px; display: flex; align-items: center; gap: 20px; font-size: 0.85rem; color: #888; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow-x: auto; white-space: nowrap; scrollbar-width: none; }
    .activity-container::-webkit-scrollbar { display: none; }
    .pulse-icon { width: 8px; height: 8px; min-width: 8px; background: #34d399; border-radius: 50%; box-shadow: 0 0 10px #34d399; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

    /* --- 🍱 Bento Box --- */
    .bento-section { max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; position: relative; z-index: 10; }
    .bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: minmax(280px, auto); gap: 20px; }
    .bento-card { background: rgba(20, 20, 20, 0.6); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 40px; display: flex; flex-direction: column; justify-content: space-between; transition: 0.4s; overflow: hidden; position: relative; }
    .bento-card:hover { border-color: rgba(255,255,255,0.2); transform: translateY(-5px); }
    .bento-large { grid-column: span 2; }
    .bento-icon { font-size: 2.5rem; margin-bottom: 20px; color: #fff; }
    .bento-card h3 { font-size: 1.8rem; font-weight: 800; margin: 0 0 10px 0; letter-spacing: -1px; }
    .bento-card p { color: #888; font-size: 1rem; line-height: 1.5; margin: 0; }
    .glow-cyan { position: absolute; bottom: -50%; right: -20%; width: 200px; height: 200px; background: var(--accent-cyan); filter: blur(80px); opacity: 0.15; }
    .glow-purple { position: absolute; top: -20%; left: -20%; width: 200px; height: 200px; background: var(--accent-purple); filter: blur(80px); opacity: 0.15; }

    /* --- 🛒 透视矩阵 (Hover Telemetry) --- */
    .matrix-section { max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; }
    .section-header { margin-bottom: 50px; display: flex; justify-content: space-between; align-items: flex-end; }
    .section-header h2 { font-size: 3rem; font-weight: 900; margin: 0; letter-spacing: -1.5px; }
    .view-all { color: #888; font-weight: bold; text-decoration: none; transition: 0.3s; }
    .view-all:hover { color: #fff; }

    .matrix-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; }
    .matrix-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 20px; transition: 0.3s; position: relative; }
    .matrix-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.2); }
    
    .mc-img-box { width: 100%; height: 220px; background: #000; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative; }
    .mc-img-box img { width: 90%; height: 90%; object-fit: contain; transition: 0.5s; }
    .matrix-card:hover .mc-img-box img { transform: scale(1.05); filter: brightness(0.4); }
    
    .telemetry-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); padding: 15px; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-top: 1px solid rgba(0,242,254,0.3); }
    .matrix-card:hover .telemetry-overlay { transform: translateY(0); }
    .tele-row { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 6px; font-weight: 800; font-family: 'JetBrains Mono', monospace; }
    .tele-val { color: #fff; }
    .tele-label { color: #888; display: flex; align-items: center; gap: 5px; font-family: 'Inter', sans-serif;}
    .tele-bar-bg { width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-bottom: 12px; overflow: hidden; }
    .tele-bar-fill { height: 100%; border-radius: 2px; }

    /* 🌟 完全对齐 packages.php 的文本和按钮体系 */
    .mc-tag { font-size: 0.7rem; color: var(--accent-cyan); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace;}
    .mc-title { font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .mc-price { font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 900; color: #00e676; margin-bottom: 15px; display: block; }
    
    .btn-group { display: flex; gap: 10px; margin-top: 15px; }
    .btn-buy { flex: 1; background: var(--accent-cyan); color: #000; text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif;}
    .btn-buy:hover { background: #fff; box-shadow: 0 0 15px var(--accent-cyan); }
    .btn-cust { flex: 1; background: transparent; border: 1px solid var(--accent-cyan); color: var(--accent-cyan); text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif;}
    .btn-cust:hover { background: var(--accent-cyan); color: #000; }

    @media (max-width: 900px) { .hero h1 { font-size: 3.5rem; } .bento-large { grid-column: span 1; } .bento-grid { grid-template-columns: 1fr; } }
</style>

<?php if (!empty($ticker_items)): ?>
<div class="market-ticker">
    <div class="ticker-content">
        <?php foreach($ticker_items as $item): ?>
            <span class="ticker-item">
                <span class="ticker-alert">LOW STOCK</span> 
                <?php echo htmlspecialchars($item['product_name']); ?> 
                <span style="color: #fff;">(Only <?php echo $item['stock_quantity']; ?> left)</span>
                <span style="color: #444;">&nbsp;&nbsp;•&nbsp;&nbsp;</span>
            </span>
        <?php endforeach; ?>
        <?php foreach($ticker_items as $item): ?>
            <span class="ticker-item">
                <span class="ticker-alert">LOW STOCK</span> 
                <?php echo htmlspecialchars($item['product_name']); ?> 
                <span style="color: #fff;">(Only <?php echo $item['stock_quantity']; ?> left)</span>
                <span style="color: #444;">&nbsp;&nbsp;•&nbsp;&nbsp;</span>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">DIGITAL DNA: <span><?php echo strtoupper($user_persona); ?> OPTIMIZED</span></div>
        <h1 class="text-gradient"><?php echo $current_hero['title']; ?></h1>
        <p><?php echo $current_hero['desc']; ?></p>
        <div class="hero-btns">
            <a href="builder.php" class="btn-premium btn-primary">Enter Builder</a>
            <a href="packages.php" class="btn-premium btn-secondary">Explore AI</a>
        </div>
    </div>
</section>

<?php if (!empty($recent_builds)): ?>
<div class="live-activity-bar">
    <div class="activity-container">
        <div class="pulse-icon"></div>
        <span style="font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px;">Live Community:</span>
        <?php foreach($recent_builds as $build): ?>
            <div class="activity-item">
                <span style="color: var(--accent-cyan);">@<?php echo htmlspecialchars($build['username']); ?></span> 
                just saved <span style="color: #fff;"><?php echo htmlspecialchars($build['build_name']); ?></span> 
                (RM <?php echo number_format($build['total_price'], 2); ?>)
            </div>
            <span style="opacity: 0.2;">|</span>
        <?php endforeach; ?>
        <span class="activity-item" style="color: #555;">...Join them in the Builder</span>
    </div>
</div>
<?php endif; ?>

<section class="bento-section">
    <div class="bento-grid">
        <div class="bento-card bento-large">
            <div class="glow-cyan"></div>
            <div style="position: relative; z-index: 1;">
                <div class="bento-icon"><i class="fas fa-brain"></i></div>
                <h3>Multi-Scenario AI Predictor</h3>
                <p>Not just for gamers. Our power-weighted heuristic algorithm actively predicts performance across 5 distinct workloads: 1440p Gaming, Content Creation, Local LLM Inference, OBS Streaming, and Code Compilation.</p>
            </div>
        </div>
        <div class="bento-card">
            <div class="glow-purple"></div>
            <div style="position: relative; z-index: 1;">
                <div class="bento-icon"><i class="fas fa-project-diagram"></i></div>
                <h3>DAG Dependency</h3>
                <p>Hardware conflicts are mathematically eliminated. Our engine locks out incompatible parts in real-time.</p>
            </div>
        </div>
        <div class="bento-card">
            <div style="position: relative; z-index: 1;">
                <div class="bento-icon"><i class="fas fa-balance-scale-right"></i></div>
                <h3>Dynamic Bottlenecking</h3>
                <p>Utilizing non-linear price-to-performance scaling to detect CPU/GPU throttling before checkout.</p>
            </div>
        </div>
        <div class="bento-card bento-large">
            <div style="position: relative; z-index: 1;">
                <div class="bento-icon"><i class="fas fa-fingerprint"></i></div>
                <h3>Digital DNA Checkout</h3>
                <p>A unified pricing model handling tier-based volume discounts while silently updating your hardware preference DNA vector.</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($featured_packages)): ?>
<section class="matrix-section">
    <div class="section-header">
        <div>
            <h2 class="text-gradient">TOP TIER LOADOUTS</h2>
            <div style="color: #888; margin-top: 10px;">Hover over any rig to reveal AI-calculated telemetry.</div>
        </div>
        <a href="packages.php" class="view-all">View Database <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="matrix-grid">
        <?php foreach ($featured_packages as $pkg): ?>
            <div class="matrix-card">
                <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" style="text-decoration: none;">
                    <div class="mc-img-box">
                        <img src="<?php echo htmlspecialchars($pkg['image_url'] ?? 'https://via.placeholder.com/300x300'); ?>" alt="PC Package">
                        
                        <div class="telemetry-overlay">
                            <div class="tele-row">
                                <span class="tele-label"><i class="fas fa-gamepad" style="color:var(--accent-cyan);"></i> Cyberpunk FPS</span>
                                <span class="tele-val"><?php echo $pkg['fps_cyberpunk']; ?></span>
                            </div>
                            <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['fps_cyberpunk']/144)*100, 100); ?>%; background: var(--accent-cyan);"></div></div>
                            
                            <div class="tele-row">
                                <span class="tele-label"><i class="fas fa-palette" style="color:var(--accent-purple);"></i> Premiere Score</span>
                                <span class="tele-val"><?php echo $pkg['score_pr']; ?></span>
                            </div>
                            <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['score_pr']/2000)*100, 100); ?>%; background: var(--accent-purple);"></div></div>
                        </div>
                    </div>
                    
                    <!-- 🌟 结构与类名与 packages.php 严格一致 -->
                    <div class="mc-tag">CLASS_<?php echo htmlspecialchars($pkg['target_persona']); ?></div>
                    <div class="mc-title"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                    <div class="mc-price">RM <?php echo number_format($pkg['real_price'], 2); ?></div>
                </a>

                <div class="btn-group">
                    <a href="add_to_cart.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-buy"><i class="fas fa-shopping-cart" style="margin-right: 6px;"></i> Buy Now</a>
                    <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-cust"><i class="fas fa-wrench" style="margin-right: 6px;"></i> Customize</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>