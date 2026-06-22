<?php
session_start();
require_once 'config.php';


$user_persona = 'Standard';
$dna = ['g' => 0, 'c' => 0, 's' => 0, 'e' => 0];

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    
    $stmt = $conn->prepare("SELECT pref_gamer, pref_creator, pref_student, pref_enthusiast FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $dna_res = $stmt->get_result();
    
    if ($row = $dna_res->fetch_assoc()) {
        $dna = ['g' => $row['pref_gamer'], 'c' => $row['pref_creator'], 's' => $row['pref_student'], 'e' => $row['pref_enthusiast']];
        arsort($dna);
        $top_key = key($dna);
        if ($dna[$top_key] > 0) {
            $user_persona = ($top_key == 'g') ? 'Gamer' : (($top_key == 'c') ? 'Creator' : (($top_key == 's') ? 'Student' : 'Enthusiast'));
        }
    }
    $stmt->close();
}

$hero_config = [
    'Gamer'   => ['title' => 'DOMINATE THE <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); -webkit-background-clip: text;">MATRIX.</span>', 'desc' => 'Your profile is tuned for maximum FPS. We have optimized your path for elite graphical output.', 'bg' => 'rgba(0,242,254,0.15)'],
    'Creator' => ['title' => 'RENDER YOUR <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #a855f7 0%, #ff007f 100%); -webkit-background-clip: text;">IMAGINATION.</span>', 'desc' => 'Built for multi-threaded dominance. Optimized for video rendering, 3D modeling, and computing power.', 'bg' => 'rgba(168,85,247,0.15)'],
    'Student' => ['title' => 'CODE THE <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #facc15 0%, #f59e0b 100%); -webkit-background-clip: text;">FUTURE.</span>', 'desc' => 'Efficiency meets absolute stability. Balanced for fast compilation speeds and seamless multitasking.', 'bg' => 'rgba(250,204,21,0.15)'],
    'Standard' => ['title' => 'ENGINEERED FOR <br><span class="text-gradient-accent" style="background: linear-gradient(135deg, #00f2fe 0%, #a855f7 100%); -webkit-background-clip: text;">ABSOLUTE PERFORMANCE.</span>', 'desc' => 'Smart custom PC ecosystem. Our real-time AI logic calculates system bottlenecks and simulates performance instantly.', 'bg' => 'rgba(0,242,254,0.1)']
];
$current_hero = $hero_config[$user_persona] ?? $hero_config['Standard'];


$recent_builds = [];
try {
    $recent_builds_sql = "SELECT sb.build_name, sb.total_price, c.username FROM saved_builds sb 
                          JOIN customers c ON sb.customer_id = c.customer_id 
                          ORDER BY sb.save_date DESC LIMIT 3";
    $recent_builds_res = $conn->query($recent_builds_sql);
    if ($recent_builds_res) {
        while($row = $recent_builds_res->fetch_assoc()) { $recent_builds[] = $row; }
    }
} catch (Exception $e) {}


$ticker_sql = "SELECT product_name, stock_quantity FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 5";
$ticker_res = $conn->query($ticker_sql);
$ticker_items = [];
if ($ticker_res) {
    while($row = $ticker_res->fetch_assoc()) { $ticker_items[] = $row; }
}


$feat_sql = "SELECT * FROM (
                SELECT pk.*, 
                (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) 
                 FROM package_items pi JOIN products p ON pi.product_id = p.product_id 
                 WHERE pi.package_id = pk.package_id) AS real_price
                FROM packages pk WHERE pk.stock_status = 'Available'
            ) AS final_packages ORDER BY real_price DESC LIMIT 4";
$feat_res = $conn->query($feat_sql);
$featured_packages = [];

if ($feat_res && $feat_res->num_rows > 0) {
    $cpu_stmt = $conn->prepare("SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ? AND p.category_id = 1 LIMIT 1");
    $gpu_stmt = $conn->prepare("SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ? AND p.category_id = 4 LIMIT 1");

    while($pkg = $feat_res->fetch_assoc()) {
        $pkg_id = $pkg['package_id'];
        
        $cpu_stmt->bind_param("i", $pkg_id);
        $cpu_stmt->execute();
        $cpu_res = $cpu_stmt->get_result();
        $cpu_p = ($cpu_row = $cpu_res->fetch_assoc()) ? $cpu_row['price'] : 1500;

        $gpu_stmt->bind_param("i", $pkg_id);
        $gpu_stmt->execute();
        $gpu_res = $gpu_stmt->get_result();
        $gpu_p = ($gpu_row = $gpu_res->fetch_assoc()) ? $gpu_row['price'] : 3000;

        $cpu_index = pow($cpu_p / 3000, 0.6) * 100; 
        $gpu_index = pow($gpu_p / 6000, 0.6) * 100; 
        
        $pkg['fps_cyberpunk'] = round(30 + ($gpu_index * 0.85) + ($cpu_index * 0.15));
        $pkg['score_pr'] = round(($cpu_index * 0.65 + $gpu_index * 0.35) * 10);
        
        $featured_packages[] = $pkg;
    }
    $cpu_stmt->close();
    $gpu_stmt->close();
}

include 'includes/header.php';
?>

<style>
    :root { 
        --accent-cyan: #00f2fe; 
        --accent-purple: #a855f7;
        --accent-green: #34d399;
        --bg-dark: #030305; 
        --glass-bg: rgba(255, 255, 255, 0.02);
        --glass-border: rgba(255, 255, 255, 0.08);
    }
    
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap');
    
    body { display: flex; flex-direction: column; min-height: 100vh; background-color: var(--bg-dark); color: #fff; font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
    .main-wrapper { flex: 1; }
    
  
    .market-ticker { width: 100vw; margin-left: calc(-50vw + 50%); background: #000; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 8px 0; overflow: hidden; white-space: nowrap; display: flex; }
    .ticker-content { display: inline-block; padding-left: 100%; animation: ticker-scroll 25s linear infinite; }
    @keyframes ticker-scroll { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }
    .ticker-item { margin-right: 50px; display: inline-flex; align-items: center; gap: 8px; }
    .ticker-alert { color: #ef4444; border: 1px solid #ef4444; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; }

    
    .hero { width: 100vw; margin-left: calc(-50vw + 50%); position: relative; min-height: 85vh; display: flex; align-items: center; justify-content: center; overflow: hidden; background: radial-gradient(ellipse at 50% 0%, <?php echo $current_hero['bg']; ?> 0%, var(--bg-dark) 80%); transition: background 0.5s ease; }
    .hero::before { content: ''; position: absolute; width: 150vw; height: 600px; background: radial-gradient(ellipse at 50% 0%, <?php echo str_replace('0.15', '0.1', $current_hero['bg']); ?> 0%, transparent 70%); top: -200px; left: -25vw; border-radius: 50%; filter: blur(80px); z-index: 0; }
    .hero-content { position: relative; z-index: 2; text-align: center; max-width: 900px; padding: 0 20px; transform: translateY(-30px); }
    .hero-badge { display: inline-block; padding: 8px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; border-radius: 30px; font-size: 0.8rem; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 30px; }
    .hero-badge span { color: var(--accent-cyan); }
    .hero h1 { font-size: 5.5rem; font-weight: 900; line-height: 1; margin: 0 0 20px 0; letter-spacing: -2px; }
    .text-gradient { background: linear-gradient(135deg, #fff 0%, #888 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero p { font-size: 1.2rem; color: #94a3b8; max-width: 600px; margin: 0 auto 40px; line-height: 1.6; }
    
    .hero-btns { display: flex; gap: 20px; justify-content: center; }
    .btn-premium { padding: 16px 40px; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; }
    .btn-primary { background: #fff; color: #000; box-shadow: 0 10px 30px rgba(255,255,255,0.1); }
    .btn-primary:hover { background: var(--accent-cyan); box-shadow: 0 10px 40px rgba(0, 242, 254, 0.4); transform: translateY(-3px); }
    .btn-secondary { background: var(--glass-bg); color: #fff; border: 1px solid var(--glass-border); backdrop-filter: blur(10px); }
    .btn-secondary:hover { border-color: #fff; background: rgba(255,255,255,0.05); transform: translateY(-3px); }

   
    .live-activity-bar { max-width: 1200px; margin: 0 auto 60px; padding: 0 20px; position: relative; z-index: 10; margin-top: -30px; }
    .activity-container { background: rgba(10,10,10,0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px 25px; display: flex; align-items: center; gap: 20px; font-size: 0.85rem; color: #888; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow-x: auto; white-space: nowrap; scrollbar-width: none; }
    .activity-container::-webkit-scrollbar { display: none; }
    .pulse-icon { width: 8px; height: 8px; min-width: 8px; background: #34d399; border-radius: 50%; box-shadow: 0 0 10px #34d399; animation: pulse 2s infinite; }

    
    .bento-section { max-width: 1200px; margin: 0 auto 80px; padding: 0 20px; position: relative; z-index: 10; }
    .bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: minmax(240px, auto); gap: 20px; }
    .bento-card { background: rgba(20, 20, 20, 0.4); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 16px; padding: 35px; display: flex; flex-direction: column; justify-content: center; transition: 0.3s; overflow: hidden; position: relative; }
    .bento-card:hover { border-color: rgba(0, 242, 254, 0.3); transform: translateY(-3px); }
    .bento-large { grid-column: span 2; justify-content: center; }
    .bento-icon { font-size: 2rem; margin-bottom: 15px; color: var(--accent-cyan); }
    .bento-card h3 { font-size: 1.5rem; font-weight: 800; margin: 0 0 10px 0; color: #fff; }
    .bento-card p { color: #94a3b8; font-size: 0.95rem; line-height: 1.5; margin: 0; }
    .glow-cyan { position: absolute; bottom: -50%; right: -20%; width: 200px; height: 200px; background: var(--accent-cyan); filter: blur(80px); opacity: 0.1; }
    .glow-purple { position: absolute; top: -20%; left: -20%; width: 200px; height: 200px; background: var(--accent-purple); filter: blur(80px); opacity: 0.1; }

    
    .matrix-section { max-width: 1200px; margin: 0 auto 80px; padding: 0 20px; }
    .section-header { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
    .section-header h2 { font-size: 2.5rem; font-weight: 900; margin: 0; letter-spacing: -1px; }
    .view-all { color: #64748b; font-weight: bold; text-decoration: none; transition: 0.2s; font-size: 0.95rem; }
    .view-all:hover { color: #fff; }

    .matrix-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
    .matrix-card { background: rgba(255,255,255,0.01); border: 1px solid var(--glass-border); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; position: relative; }
    .matrix-card:hover { transform: translateY(-5px); border-color: var(--accent-cyan); box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
    
    .mc-img-box { width: 100%; height: 220px; background: rgba(0,0,0,0.3); border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative; }
    .mc-img-box img { width: 85%; height: 85%; object-fit: contain; transition: 0.4s; }
    .matrix-card:hover .mc-img-box img { transform: scale(1.1); filter: brightness(0.4); }
    
    .telemetry-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); padding: 15px; transform: translateY(100%); transition: transform 0.3s ease; border-top: 1px solid rgba(0,242,254,0.2); z-index: 10;}
    .matrix-card:hover .telemetry-overlay { transform: translateY(0); }
    .tele-row { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 6px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
    .tele-val { color: #fff; }
    .tele-label { color: #888; display: flex; align-items: center; gap: 5px; }
    .tele-bar-bg { width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-bottom: 12px; overflow: hidden; }
    .tele-bar-fill { height: 100%; border-radius: 2px; }

    .mc-tag { font-size: 0.7rem; color: var(--accent-cyan); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace;}
    .mc-title { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 10px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3rem;}
    .mc-price { font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 800; color: #00e676; margin-bottom: 0; }
    
   
    .btn-view-specs { background: transparent; border: 1px solid rgba(0, 242, 254, 0.3); color: #00f2fe; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 6px; font-family: 'JetBrains Mono', monospace;}
    .btn-view-specs:hover { background: rgba(0, 242, 254, 0.1); border-color: #00f2fe; box-shadow: 0 0 10px rgba(0,242,254,0.2); }

    #specsModal { display: none; position: fixed; inset: 0; background: rgba(3, 3, 5, 0.85); backdrop-filter: blur(15px); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease;}
    #specsModal.show { display: flex; opacity: 1; }
    .specs-modal-content { background: #0b0f16; border: 1px solid #00f2fe; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.5), inset 0 0 20px rgba(0,242,254,0.05); overflow: hidden; transform: translateY(20px); transition: transform 0.3s ease;}
    #specsModal.show .specs-modal-content { transform: translateY(0); }
    .specs-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,242,254,0.05);}
    .specs-modal-title { font-size: 1.2rem; font-weight: 900; color: #fff; margin: 0; }
    .specs-modal-close { color: #64748b; font-size: 1.5rem; cursor: pointer; transition: 0.2s; }
    .specs-modal-close:hover { color: #ef4444; }
    .specs-modal-body { padding: 0; max-height: 60vh; overflow-y: auto; }
    .spec-modal-item { display: flex; flex-direction: column; padding: 15px 25px; border-bottom: 1px dashed rgba(255,255,255,0.05); }
    .spec-modal-item:last-child { border-bottom: none; }
    .spec-cat { font-size: 0.7rem; color: #00f2fe; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; font-family: 'JetBrains Mono', monospace;}
    .spec-name { font-size: 0.95rem; color: #e2e8f0; font-weight: 600; }

    .btn-group { display: flex; gap: 10px; margin-top: auto; }
    .btn-action { flex: 1; padding: 12px 10px; border-radius: 6px; font-weight: 800; font-size: 0.9rem; text-decoration: none; transition: 0.3s; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; gap: 6px; box-sizing: border-box; border: none; font-family: 'Inter', sans-serif; width: 100%;}
    .btn-buy { background: rgba(0, 242, 254, 0.1); color: var(--accent-cyan); border: 1px solid var(--accent-cyan); }
    .btn-buy:hover { background: var(--accent-cyan); color: #000; box-shadow: 0 0 15px rgba(0, 242, 254, 0.3); }
    .btn-cust { background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); }
    .btn-cust:hover { background: rgba(255,255,255,0.1); color: #fff; }

   
    .algo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .algo-mini-card { background: rgba(20,20,25,0.6); border: 1px solid var(--glass-border); border-radius: 12px; padding: 25px; transition: 0.3s; position: relative; }
    .algo-mini-card::before { content: ''; position: absolute; top: 0; left: 0; width: 3px; height: 100%; background: var(--accent-cyan); border-radius: 12px 0 0 12px; }
    .algo-mini-card:nth-child(2)::before { background: var(--accent-purple); }
    .algo-mini-card:nth-child(3)::before { background: var(--accent-green); }
    .algo-mini-heading { font-size: 1.1rem; font-weight: 700; margin: 0 0 10px 0; color: #fff; display: flex; align-items: center; gap: 8px; }
    .algo-mini-desc { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 15px; }

    @media (max-width: 900px) { .hero h1 { font-size: 3.5rem; } .bento-large { grid-column: span 1; } .bento-grid, .algo-grid { grid-template-columns: 1fr; } }
</style>

<div class="main-wrapper">

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
                    <h3>Performance Simulator</h3>
                    <p>Calculates and predicts system framerates across distinct real-world processing workloads: Gaming, Streaming, Coding, Rendering, and Studio workflows.</p>
                </div>
            </div>
            <div class="bento-card">
                <div class="glow-purple"></div>
                <div style="position: relative; z-index: 1;">
                    <div class="bento-icon"><i class="fas fa-project-diagram"></i></div>
                    <h3>Topological Dependency</h3>
                    <p>Hardware conflicts are mathematically eliminated. Our dynamic pipeline safely rules out mismatched component connections instantly.</p>
                </div>
            </div>
            <div class="bento-card">
                <div style="position: relative; z-index: 1;">
                    <div class="bento-icon"><i class="fas fa-balance-scale-right"></i></div>
                    <h3>Heuristic Bottleneck Radar</h3>
                    <p>Utilizes custom hardware index profiling to pinpoint processor and graphic throttling parameters before checking out.</p>
                </div>
            </div>
            <div class="bento-card bento-large">
                <div style="position: relative; z-index: 1;">
                    <div class="bento-icon"><i class="fas fa-fingerprint"></i></div>
                    <h3>Preference Alignment Matrix</h3>
                    <p>Learns from your custom modular selections to build an architectural vector map, quietly curating your store preferences.</p>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($featured_packages)): ?>
    <section class="matrix-section">
        <div class="section-header">
            <div>
                <h2 class="text-gradient">TOP TIER LOADOUTS</h2>
                <div style="color: #888; margin-top: 10px; font-size: 0.95rem;">Hover over any rig to reveal AI-calculated telemetry.</div>
            </div>
            <a href="packages.php" class="view-all">View All Packages <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="matrix-grid">
            <?php foreach ($featured_packages as $pkg): ?>
                <div class="matrix-card">
                    <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" style="text-decoration: none; display: block; color: inherit;">
                        <div class="mc-img-box">
                            <?php 
                                $raw_pkg_img = $pkg['image_url'] ?? '';
                                if (empty($raw_pkg_img) || strpos($raw_pkg_img, 'placeholder') !== false) {
                                    $pkg_img_src = 'image/placeholder.jpg';
                                } elseif (strpos($raw_pkg_img, 'data:image') === 0 || strpos($raw_pkg_img, 'http') === 0) {
                                    $pkg_img_src = $raw_pkg_img;
                                } else {
                                    $pkg_img_src = (strpos($raw_pkg_img, 'image/') === 0) ? $raw_pkg_img : 'image/' . basename($raw_pkg_img);
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($pkg_img_src); ?>" alt="PC Package" onerror="this.src='image/placeholder.jpg';">
                            
                            <div class="telemetry-overlay">
                                <div class="tele-row">
                                    <span class="tele-label"><i class="fas fa-gamepad" style="color:var(--accent-cyan); margin-right:4px;"></i> Cyberpunk FPS</span>
                                    <span class="tele-val"><?php echo $pkg['fps_cyberpunk']; ?></span>
                                </div>
                                <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['fps_cyberpunk']/144)*100, 100); ?>%; background: var(--accent-cyan);"></div></div>
                                
                                <div class="tele-row">
                                    <span class="tele-label"><i class="fas fa-palette" style="color:var(--accent-purple); margin-right:4px;"></i> Premiere Score</span>
                                    <span class="tele-val"><?php echo $pkg['score_pr']; ?></span>
                                </div>
                                <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['score_pr']/2000)*100, 100); ?>%; background: var(--accent-purple);"></div></div>
                            </div>
                        </div>
                        
                        <div class="mc-tag">CLASS_<?php echo htmlspecialchars($pkg['target_persona']); ?></div>
                        <div class="mc-title"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                    </a>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div class="mc-price" style="margin-bottom: 0;">RM <?php echo number_format($pkg['real_price'], 2); ?></div>
                        <button type="button" class="btn-view-specs" onclick="openSpecsModal('<?php echo htmlspecialchars(addslashes($pkg['package_name'])); ?>', <?php echo $pkg['package_id']; ?>)">
                            <i class="fas fa-list-ul"></i> Specs
                        </button>
                    </div>

                    <div id="hidden_specs_<?php echo $pkg['package_id']; ?>" style="display: none;">
                        <?php
                            $list_sql = "SELECT p.product_name, c.category_name FROM package_items pi JOIN products p ON pi.product_id = p.product_id JOIN categories c ON p.category_id = c.category_id WHERE pi.package_id = " . $pkg['package_id'];
                            $list_res = $conn->query($list_sql);
                            if ($list_res && $list_res->num_rows > 0) {
                                while($item = $list_res->fetch_assoc()) {
                                    echo "<div class='spec-modal-item'>";
                                    echo "<span class='spec-cat'>" . htmlspecialchars($item['category_name']) . "</span>";
                                    echo "<span class='spec-name'>" . htmlspecialchars($item['product_name']) . "</span>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<div style='color:#64748b; text-align:center; padding: 20px;'>No specific parts listed.</div>";
                            }
                        ?>
                    </div>

                    <div class="btn-group">
                        <form action="add_to_cart.php" method="POST" style="flex:1.2; margin:0;">
                            <input type="hidden" name="package_id" value="<?php echo $pkg['package_id']; ?>">
                            <input type="hidden" name="action" value="buy_now">
                            <button type="submit" class="btn-action btn-buy"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                        </form>
                        <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-action btn-cust"><i class="fas fa-wrench"></i> Customize</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="matrix-section" style="border-top: 1px solid var(--glass-border); padding-top: 60px;">
        <div class="section-header" style="margin-bottom: 35px;">
            <div>
                <h2 class="text-gradient" style="font-size: 2.2rem;">ENGINE ARCHITECTURE</h2>
                <div style="color: #888; margin-top: 10px; font-size: 0.95rem;">The underlying mathematical models steering our heuristic computer processing nodes.</div>
            </div>
        </div>

        <div class="algo-grid">
            <div class="algo-mini-card">
                <h4 class="algo-mini-heading"><i class="fas fa-fingerprint" style="color: var(--accent-cyan);"></i> Vector DNA Alignment</h4>
                <p class="algo-mini-desc">Maps consumer hardware profiles across a 4-dimensional vector: V = [g, c, s, e] using Cosine Similarity metrics.</p>
                <div class="math-formula-box" style="font-family: 'JetBrains Mono', monospace; color: var(--accent-cyan); font-weight: bold; font-size: 0.95rem; letter-spacing: 0.5px;">
                    cos(&theta;) = (A &middot; B) / (||A|| &middot; ||B||)
                </div>
            </div>

            <div class="algo-mini-card">
                <h4 class="algo-mini-heading"><i class="fas fa-project-diagram" style="color: var(--accent-purple);"></i> Topological DAG Logic</h4>
                <p class="algo-mini-desc">Models physical socket dependency pathways as a strict Directed Acyclic Graph to rule out conflict connections.</p>
                <div class="math-formula-box" style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #a855f7; font-weight: bold;">
                    CPU ➔ Motherboard ➔ RAM / GPU
                </div>
            </div>

            <div class="algo-mini-card">
                <h4 class="algo-mini-heading"><i class="fas fa-chart-line" style="color: var(--accent-green);"></i> Bottleneck Calculus</h4>
                <p class="algo-mini-desc">Employs fractional exponent limits to replicate hardware diminishing returns and benchmark evaluations.</p>
                <div class="math-formula-box" style="font-family: 'JetBrains Mono', monospace; color: #34d399; font-weight: bold; font-size: 0.95rem;">
                    Idx = (Price / Base)<sup>0.6</sup> &times; 100
                </div>
            </div>
        </div>
    </section>

</div> 

<div id="specsModal" onclick="closeSpecsModal(event)">
    <div class="specs-modal-content" onclick="event.stopPropagation()">
        <div class="specs-modal-header">
            <h3 class="specs-modal-title"><i class="fas fa-microchip" style="color:#00f2fe; margin-right:8px;"></i> <span id="modalPkgName">System Specs</span></h3>
            <i class="fas fa-times specs-modal-close" onclick="closeSpecsModal()"></i>
        </div>
        <div class="specs-modal-body" id="modalSpecsBody">
            </div>
    </div>
</div>

<script>
    
    function openSpecsModal(pkgName, pkgId) {
        document.getElementById('modalPkgName').innerText = pkgName;
        // 把隐藏的内容复制到弹窗里
        const specsHtml = document.getElementById('hidden_specs_' + pkgId).innerHTML;
        document.getElementById('modalSpecsBody').innerHTML = specsHtml;
        
        const modal = document.getElementById('specsModal');
        modal.style.display = 'flex';
        // 强制回流以触发 CSS 动画
        modal.offsetHeight; 
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSpecsModal(e) {
        const modal = document.getElementById('specsModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300); 
    }
</script>

<?php include 'includes/footer.php'; ?>