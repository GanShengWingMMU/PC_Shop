<?php
ob_start();
session_start();
require_once 'config.php';

// ==========================================
// 🧠 引擎一：余弦相似度算法 (Cosine Similarity)
// ==========================================
$user_dna = ['g' => 0, 'c' => 0, 's' => 0, 'e' => 0];
$is_logged_in = isset($_SESSION['customer_id']);

if ($is_logged_in) {
    $cid = $_SESSION['customer_id'];
    $stmt_dna = $conn->prepare("SELECT pref_gamer, pref_creator, pref_student, pref_enthusiast FROM customers WHERE customer_id = ?");
    try {
        $stmt_dna->bind_param("i", $cid);
        $stmt_dna->execute();
        $res_dna = $stmt_dna->get_result();
        if ($row_dna = $res_dna->fetch_assoc()) {
            $user_dna = [
                'g' => $row_dna['pref_gamer'] ?? 0,
                'c' => $row_dna['pref_creator'] ?? 0,
                's' => $row_dna['pref_student'] ?? 0,
                'e' => $row_dna['pref_enthusiast'] ?? 0
            ];
        }
        $stmt_dna->close();
    } catch (Exception $e) {}
}

function cosine_similarity($vec1, $vec2) {
    $dot_product = ($vec1['g'] * $vec2['g']) + ($vec1['c'] * $vec2['c']) + ($vec1['s'] * $vec2['s']) + ($vec1['e'] * $vec2['e']);
    $mag1 = sqrt(pow($vec1['g'], 2) + pow($vec1['c'], 2) + pow($vec1['s'], 2) + pow($vec1['e'], 2));
    $mag2 = sqrt(pow($vec2['g'], 2) + pow($vec2['c'], 2) + pow($vec2['s'], 2) + pow($vec2['e'], 2));
    if ($mag1 == 0 || $mag2 == 0) return 0;
    return $dot_product / ($mag1 * $mag2);
}

// 🌟 动态提取真实零件总价作为 AI 推荐的价格
$top_package = null;
$highest_score = -1;

if (array_sum($user_dna) > 0) {
    $ai_sql = "SELECT pk.*, 
            (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) 
             FROM package_items pi JOIN products p ON pi.product_id = p.product_id 
             WHERE pi.package_id = pk.package_id) AS real_price
            FROM packages pk WHERE pk.stock_status = 'Available'";
    $ai_res = mysqli_query($conn, $ai_sql);
    while ($pkg = mysqli_fetch_assoc($ai_res)) {
        $pkg_dna = ['g' => $pkg['score_gamer'], 'c' => $pkg['score_creator'], 's' => $pkg['score_student'], 'e' => $pkg['score_enthusiast']];
        $sim_score = cosine_similarity($user_dna, $pkg_dna);
        if ($sim_score > $highest_score) {
            $highest_score = $sim_score;
            $top_package = $pkg;
        }
    }
}

// ==========================================
// 🔍 模块二：动态多维筛选器 (🌟 升级为动态 Prepared Statement)
// ==========================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$persona_filter = isset($_GET['persona']) ? trim($_GET['persona']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : 0;

$filter_active = false;
$conditions = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    // 🌟 使用 ? 占位符，防止任何形式的 SQL 注入
    $conditions[] = "(package_name LIKE ? OR description LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
    $filter_active = true;
}
if (!empty($persona_filter)) {
    $conditions[] = "target_persona = ?";
    $params[] = $persona_filter;
    $types .= "s";
    $filter_active = true;
}
if ($min_price > 0) {
    $conditions[] = "real_price >= ?";
    $params[] = $min_price;
    $types .= "d";
    $filter_active = true;
}
if ($max_price > 0) {
    $conditions[] = "real_price <= ?";
    $params[] = $max_price;
    $types .= "d";
    $filter_active = true;
}

$where_clause = "1=1";
if (!empty($conditions)) {
    $where_clause .= " AND " . implode(" AND ", $conditions);
}

$order_clause = "ORDER BY package_id DESC";
if ($sort_by == 'price_asc') { $order_clause = "ORDER BY real_price ASC"; } 
elseif ($sort_by == 'price_desc') { $order_clause = "ORDER BY real_price DESC"; }

$sql = "SELECT * FROM (
            SELECT pk.*, 
            (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) 
             FROM package_items pi JOIN products p ON pi.product_id = p.product_id 
             WHERE pi.package_id = pk.package_id) AS real_price
            FROM packages pk WHERE pk.stock_status = 'Available'
        ) AS final_packages WHERE $where_clause $order_clause";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$catalog_result = $stmt->get_result();
$stmt->close();

include 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root { --accent: #00f2fe; --dark-bg: #030305; --card-bg: rgba(255,255,255,0.02); --card-border: rgba(255,255,255,0.08); }
    
    body { background-color: var(--dark-bg); color: #fff; font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
    
    .page-container { max-width: 1200px; margin: 2rem auto; padding: 0 20px; }

    /* 模块一：AI 英雄区 */
    .ai-hero { background: linear-gradient(135deg, rgba(0,242,254,0.1) 0%, rgba(10,10,10,0.8) 100%); border: 1px solid var(--accent); border-radius: 12px; padding: 30px; margin-bottom: 40px; display: flex; gap: 30px; align-items: center; box-shadow: 0 0 30px rgba(0,242,254,0.15); position: relative; overflow: hidden; }
    .ai-badge { position: absolute; top: 0; left: 0; background: var(--accent); color: #000; padding: 5px 15px; font-weight: 900; font-size: 0.8rem; border-bottom-right-radius: 10px; letter-spacing: 1px; font-family: 'JetBrains Mono', monospace;}
    
    /* 高级过滤控制台 (保留优化的功能面板) */
    .filter-panel { background: rgba(10, 10, 15, 0.6); backdrop-filter: blur(20px); border: 1px solid var(--card-border); padding: 25px; border-radius: 12px; margin-bottom: 40px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .filter-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
    
    .search-wrapper { position: relative; flex: 1; min-width: 250px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }
    .search-box { width: 100%; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px 12px 45px; border-radius: 6px; outline: none; font-family: 'Inter', sans-serif; font-size: 0.95rem; transition: 0.3s; box-sizing: border-box; }
    .search-box:focus { border-color: var(--accent); box-shadow: inset 0 0 10px rgba(0,242,254,0.1); }
    
    .select-box { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; padding: 12px 15px; border-radius: 6px; outline: none; font-family: 'Inter', sans-serif; cursor: pointer; transition: 0.3s; min-width: 200px; }
    .select-box:focus { border-color: var(--accent); }

    .pill-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .pill { padding: 8px 18px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); color: #888; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .pill:hover, .pill.active { background: rgba(0,242,254,0.05); border-color: var(--accent); color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.1); }

    .price-widget { flex: 1; max-width: 400px; display: flex; flex-direction: column; gap: 10px; }
    .price-inputs { display: flex; align-items: center; gap: 10px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #64748b; }
    .price-inputs input { width: 80px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 6px; border-radius: 4px; text-align: center; outline: none; font-family: 'JetBrains Mono', monospace; transition: 0.2s; }
    .price-inputs input:focus { border-color: var(--accent); }
    .price-inputs input[type="number"]::-webkit-inner-spin-button, .price-inputs input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    
    .slider-container { position: relative; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; }
    .slider-track-fill { position: absolute; height: 100%; left: 0%; right: 0%; background: var(--accent); border-radius: 2px; pointer-events: none; }
    .range-inputs { position: relative; }
    .range-inputs input { position: absolute; top: -10px; height: 4px; width: 100%; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    .range-inputs input::-webkit-slider-thumb { height: 16px; width: 16px; border-radius: 50%; background: #fff; pointer-events: auto; -webkit-appearance: none; cursor: pointer; transition: 0.2s; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
    .range-inputs input::-webkit-slider-thumb:hover { transform: scale(1.2); }

    .btn-exec { background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-family: 'JetBrains Mono', monospace; font-weight: 700; padding: 12px 20px; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; display: inline-flex; justify-content: center; align-items: center; box-sizing: border-box; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; }
    .btn-exec:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4); transform: translateY(-2px); }

    /* 🌟 复原！卡片商品按钮的样式 (完全等同于你最初的设定) */
    .btn-group { display: flex; gap: 10px; margin-top: 15px; }
    .btn-buy { flex: 1; background: var(--accent); color: #000; text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; }
    .btn-buy:hover { background: #fff; box-shadow: 0 0 15px var(--accent); }
    .btn-cust { flex: 1; background: transparent; border: 1px solid var(--accent); color: var(--accent); text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; }
    .btn-cust:hover { background: var(--accent); color: #000; }

    /* Package Cards */
    .pkg-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
    .pkg-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 8px; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
    .pkg-card:hover { transform: translateY(-5px); border-color: rgba(0, 242, 254, 0.4); box-shadow: inset 0 0 15px rgba(0, 242, 254, 0.05); }
    .pkg-img { width: 100%; height: 200px; object-fit: contain; background: rgba(0,0,0,0.5); border-radius: 4px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
    .pkg-card:hover .pkg-img { transform: scale(1.05); filter: brightness(0.8); }
    
    .pkg-tag { font-size: 0.7rem; color: var(--accent); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace; }
    .pkg-title { font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .pkg-desc { font-size: 0.9rem; color: #888; flex-grow: 1; margin-bottom: 20px; line-height: 1.5; }
    .pkg-price { font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 900; color: #00e676; margin-bottom: 15px; }
    
    /* 🌟 复原！AI 控制台区域 (完全等同于你最初的设定) */
    .ai-command-center { margin: 60px auto 40px; max-width: 800px; background: rgba(10, 10, 10, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 16px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.02); }
    .ai-cc-header { text-align: center; margin-bottom: 40px; }
    .ai-cc-badge-top { display: inline-block; background: rgba(0,242,254,0.1); color: var(--accent); padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; font-weight: 900; border: 1px solid var(--accent); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px; }
    .budget-display-area { text-align: center; margin-bottom: 30px; }
    .budget-amount { font-size: 4rem; font-weight: 900; color: #fff; letter-spacing: -2px; margin: 10px 0; text-shadow: 0 0 20px rgba(0, 242, 254, 0.3); display: flex; justify-content: center; align-items: baseline; gap: 10px; }
    .budget-currency { font-size: 1.5rem; color: #888; }
    .ai-custom-range { -webkit-appearance: none; width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 5px; outline: none; margin-bottom: 15px; }
    .ai-custom-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 24px; height: 24px; border-radius: 50%; background: var(--accent); cursor: pointer; box-shadow: 0 0 15px var(--accent); transition: transform 0.1s; }
    .ai-custom-range::-webkit-slider-thumb:hover { transform: scale(1.2); }
    .tier-feedback { font-size: 0.9rem; color: #a855f7; font-weight: bold; letter-spacing: 1px; transition: color 0.3s; }
    
    .persona-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 40px; }
    .persona-option { position: relative; cursor: pointer; }
    .persona-option input[type="radio"] { display: none; }
    .persona-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px 15px; text-align: center; color: #888; transition: all 0.3s ease; }
    .persona-card i { font-size: 1.8rem; margin-bottom: 10px; display: block; }
    .persona-card span { font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
    .persona-option input:checked + .persona-card[data-theme="gamer"] { background: rgba(0, 242, 254, 0.1); border-color: #00f2fe; color: #00f2fe; box-shadow: 0 0 20px rgba(0,242,254,0.2); }
    .persona-option input:checked + .persona-card[data-theme="creator"] { background: rgba(168, 85, 247, 0.1); border-color: #a855f7; color: #a855f7; box-shadow: 0 0 20px rgba(168,85,247,0.2); }
    .persona-option input:checked + .persona-card[data-theme="student"] { background: rgba(250, 204, 21, 0.1); border-color: #facc15; color: #facc15; box-shadow: 0 0 20px rgba(250,204,21,0.2); }
    
    /* 🌟 复原！AI 生成按钮的大渐变样式 */
    .btn-generate { display: block; width: 100%; background: linear-gradient(90deg, #00f2fe, #4facfe); color: #000; border: none; padding: 20px; border-radius: 10px; font-size: 1.2rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,242,254,0.3); font-family: 'Inter', sans-serif;}
    .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(0,242,254,0.5); }
    
    @media (max-width: 768px) { .filter-row { flex-direction: column; align-items: stretch; } .persona-selector { grid-template-columns: 1fr; } .budget-amount { font-size: 3rem; } }
</style>

<div class="page-container">
    
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 3rem; font-weight: 900; color: #fff; margin: 0; letter-spacing: -1px;">PRE-BUILT <span style="color: var(--accent);">PACKAGES</span></h1>
        <p style="color: #888; font-size: 1.1rem;">Expertly assembled. Ready to ship. Powered by AI recommendation.</p>
    </div>

    <?php if ($top_package && !$filter_active): ?>
        <div class="ai-hero">
            <div class="ai-badge">SYS.DNA TARGET MATCH</div>
            <img src="<?php echo htmlspecialchars($top_package['image_url'] ?? 'https://via.placeholder.com/300x300'); ?>" style="width: 250px; height: 250px; object-fit: contain;">
            <div>
                <div style="color: var(--accent); font-weight: bold; font-size: 0.9rem; letter-spacing: 2px; margin-bottom: 5px; text-transform: uppercase;">
                    <i class="fas fa-crosshairs"></i> OPTIMIZED FOR: <?php echo $top_package['target_persona']; ?>
                </div>
                <h2 style="font-size: 2.2rem; color: #fff; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px;"><?php echo htmlspecialchars($top_package['package_name']); ?></h2>
                <p style="color: #cbd5e1; font-size: 1rem; max-width: 600px; margin-bottom: 20px;">
                    Based on your historical component selections in the Builder, our vector-space AI calculates this rig as your ultimate setup.
                </p>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 2rem; color: #00e676; font-weight: 900; margin-bottom: 20px;">RM <?php echo number_format($top_package['real_price'], 2); ?></div>
                
                <!-- 🌟 复原的按钮类名 btn-buy 和 btn-cust -->
                <div class="btn-group" style="max-width: 400px; margin-top: 0;">
                    <a href="add_to_cart.php?pkg_id=<?php echo $top_package['package_id']; ?>" class="btn-buy"><i class="fas fa-shopping-cart" style="margin-right: 6px;"></i> Buy Now</a>
                    <a href="builder_load_package.php?pkg_id=<?php echo $top_package['package_id']; ?>" class="btn-cust"><i class="fas fa-wrench" style="margin-right: 6px;"></i> Customize</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="packages.php" method="GET" class="filter-panel">
        <div class="filter-row">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-box" placeholder="Search parameters (e.g. RTX 4090, AMD)..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            
            <select name="sort" class="select-box">
                <option value="new">Sort By: Newest First</option>
                <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Sort By: Price (Low to High)</option>
                <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Sort By: Price (High to Low)</option>
            </select>
        </div>

        <div class="filter-row" style="border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 20px;">
            <div class="pill-group">
                <label class="pill <?php echo $persona_filter == 'Gamer' ? 'active' : ''; ?>">
                    <input type="radio" name="persona" value="Gamer" style="display: none;" <?php echo $persona_filter == 'Gamer' ? 'checked' : ''; ?>>
                    <i class="fas fa-gamepad"></i> Gamers
                </label>
                <label class="pill <?php echo $persona_filter == 'Creator' ? 'active' : ''; ?>">
                    <input type="radio" name="persona" value="Creator" style="display: none;" <?php echo $persona_filter == 'Creator' ? 'checked' : ''; ?>>
                    <i class="fas fa-palette"></i> Creators
                </label>
                <label class="pill <?php echo $persona_filter == 'Student' ? 'active' : ''; ?>">
                    <input type="radio" name="persona" value="Student" style="display: none;" <?php echo $persona_filter == 'Student' ? 'checked' : ''; ?>>
                    <i class="fas fa-graduation-cap"></i> Students
                </label>
            </div>

            <div class="price-widget">
                <div class="price-inputs">
                    <span>BUDGET: RM</span>
                    <input type="number" class="input-min" name="min_price" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>">
                    <span>-</span>
                    <input type="number" class="input-max" name="max_price" value="<?php echo $max_price > 0 ? $max_price : '20000'; ?>">
                </div>
                
                <div style="position: relative; margin-top: 5px;">
                    <div class="slider-container">
                        <div class="slider-track-fill"></div>
                    </div>
                    <div class="range-inputs">
                        <input type="range" class="range-min" min="0" max="20000" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>" step="100">
                        <input type="range" class="range-max" min="0" max="20000" value="<?php echo $max_price > 0 ? $max_price : '20000'; ?>" step="100">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-exec"><i class="fas fa-server" style="margin-right: 8px;"></i> EXECUTE_QUERY</button>
                <?php if($filter_active): ?>
                    <a href="packages.php" class="btn-exec" style="color: #ef4444; border-color: rgba(239,68,68,0.3);"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="pkg-grid">
        <?php if (mysqli_num_rows($catalog_result) > 0): ?>
            <?php while ($pkg = mysqli_fetch_assoc($catalog_result)): ?>
                <div class="pkg-card">
                    <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" style="text-decoration: none;">
                        <div class="pkg-img">
                            <img src="<?php echo htmlspecialchars($pkg['image_url'] ?? 'https://via.placeholder.com/280x200'); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div class="pkg-tag">CLASS_<?php echo htmlspecialchars($pkg['target_persona']); ?></div>
                        <div class="pkg-title"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                        <div class="pkg-desc"><?php echo htmlspecialchars($pkg['description']); ?></div>
                        <div class="pkg-price">RM <?php echo number_format($pkg['real_price'], 2); ?></div>
                    </a>
                    
                    <!-- 🌟 复原的按钮类名 btn-buy 和 btn-cust -->
                    <div class="btn-group">
                    <form action="add_to_cart.php" method="POST" style="display:inline-block;">
    <input type="hidden" name="package_id" value="<?php echo $pkg['package_id']; ?>">
    <input type="hidden" name="action" value="buy_now">
    
    <button type="submit" class="btn btn-primary" style="background: #00f2fe; color: #000; font-weight: bold; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.boxShadow='0 0 15px rgba(0, 242, 254, 0.5)'" onmouseout="this.style.boxShadow='none'">
        Buy Now
    </button>
</form>

                        <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-cust"><i class="fas fa-wrench"></i> Customize</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 10px; color: #888; border: 1px dashed rgba(255,255,255,0.1);">
                <i class="fas fa-satellite-dish" style="font-size: 3rem; margin-bottom: 15px; color: #444;"></i>
                <h2>Query returned 0 results.</h2>
                <a href="packages.php" style="color: var(--accent); text-decoration: none; font-family: 'JetBrains Mono', monospace;">> Reset_Parameters</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 🌟 原汁原味的 AI 控制台 -->
    <div class="ai-command-center">
        <div class="ai-cc-header">
            <div class="ai-cc-badge-top"><i class="fas fa-microchip"></i> Heuristic Blueprint Engine</div>
            <h2 style="font-size: 2.2rem; color: #fff; margin: 0 0 10px 0; font-weight: 900;">Auto-Generate <span style="color: var(--accent);">Your Rig</span></h2>
            <p style="color: #888; font-size: 1rem; margin: 0;">Set your parameters. Let the algorithm handle the bottlenecks.</p>
        </div>
        
        <form action="auto_generate.php" method="POST">
            <div class="budget-display-area">
                <div style="color: #888; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Target Budget</div>
                <div class="budget-amount">
                    <span class="budget-currency">RM</span>
                    <span id="ai-budget-value">4000</span>
                </div>
                
                <input type="hidden" name="budget" id="ai-hidden-budget" value="4000">
                <input type="range" class="ai-custom-range" id="ai-budget-slider" min="1500" max="15000" step="100" value="4000">
                
                <div class="tier-feedback" id="ai-tier-feedback">
                    <i class="fas fa-radar"></i> Estimated: Solid 1080p Performance
                </div>
            </div>

            <div style="color: #888; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; text-align: center;">Primary Workload</div>
            <div class="persona-selector">
                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Gamer" checked>
                    <div class="persona-card" data-theme="gamer">
                        <i class="fas fa-gamepad"></i>
                        <span>Extreme Gamer</span>
                    </div>
                </label>
                
                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Creator">
                    <div class="persona-card" data-theme="creator">
                        <i class="fas fa-palette"></i>
                        <span>Creator / Editor</span>
                    </div>
                </label>
                
                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Student">
                    <div class="persona-card" data-theme="student">
                        <i class="fas fa-code"></i>
                        <span>Student / Dev</span>
                    </div>
                </label>
            </div>

            <!-- 🌟 绝对还原的 渐变 大按钮 -->
            <button type="submit" class="btn-generate">
                <i class="fas fa-bolt"></i> Generate Blueprint
            </button>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 🌟 AI 预算滑块
    const aiSlider = document.getElementById("ai-budget-slider");
    const aiDisplay = document.getElementById("ai-budget-value");
    const aiHiddenInput = document.getElementById("ai-hidden-budget");
    const aiFeedback = document.getElementById("ai-tier-feedback");

    if (aiSlider) {
        function updateAIFeedback(value) {
            let text = ""; let color = "";
            if (value < 2500) { text = "Estimated: Entry-level 1080p & Office tasks"; color = "#facc15"; } 
            else if (value < 4500) { text = "Estimated: Solid 1080p / Entry 1440p Gaming"; color = "#00f2fe"; } 
            else if (value < 8000) { text = "Estimated: High-End 1440p / Entry 4K Powerhouse"; color = "#a855f7"; } 
            else { text = "Estimated: Enthusiast God-Tier (Flawless 4K)"; color = "#ff007f"; }
            aiFeedback.innerHTML = `<i class="fas fa-satellite-dish"></i> ${text}`;
            aiFeedback.style.color = color;
        }

        aiSlider.addEventListener("input", function() {
            const val = this.value;
            aiDisplay.textContent = Number(val).toLocaleString(); 
            aiHiddenInput.value = val; 
            updateAIFeedback(val);
        });
        updateAIFeedback(aiSlider.value);
    }

    // 🌟 过滤器双向价格滑块逻辑
    const rangeInputs = document.querySelectorAll(".range-inputs input");
    const priceInputs = document.querySelectorAll(".price-inputs input");
    const trackFill = document.querySelector(".slider-track-fill");
    const maxRange = 20000; const priceGap = 500;

    if (rangeInputs.length > 0 && priceInputs.length > 0 && trackFill) {
        function updateTrackFill(minVal, maxVal) {
            trackFill.style.left = (minVal / maxRange) * 100 + "%";
            trackFill.style.right = 100 - (maxVal / maxRange) * 100 + "%";
        }
        updateTrackFill(parseInt(rangeInputs[0].value), parseInt(rangeInputs[1].value));

        const pills = document.querySelectorAll('.pill input[type="radio"]');
        pills.forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
                if(this.checked) this.parentElement.classList.add('active');
            });
        });

        rangeInputs.forEach(input => {
            input.addEventListener("input", e => {
                let minVal = parseInt(rangeInputs[0].value); let maxVal = parseInt(rangeInputs[1].value);
                if ((maxVal - minVal) < priceGap) {
                    if (e.target.className === "range-min") { rangeInputs[0].value = maxVal - priceGap; } 
                    else { rangeInputs[1].value = minVal + priceGap; }
                } else {
                    priceInputs[0].value = minVal; priceInputs[1].value = maxVal;
                    updateTrackFill(minVal, maxVal);
                }
            });
        });

        priceInputs.forEach(input => {
            input.addEventListener("input", e => {
                if(priceInputs[0].value === "" || priceInputs[1].value === "") return;
                let minPrice = parseInt(priceInputs[0].value); let maxPrice = parseInt(priceInputs[1].value);
                if ((maxPrice - minPrice >= priceGap) && maxPrice <= maxRange && minPrice >= 0) {
                    if (e.target.className === "input-min") { rangeInputs[0].value = minPrice; } 
                    else { rangeInputs[1].value = maxPrice; }
                    updateTrackFill(minPrice, maxPrice);
                }
            });
            input.addEventListener("blur", e => {
                let minPrice = parseInt(priceInputs[0].value) || 0; let maxPrice = parseInt(priceInputs[1].value) || maxRange;
                if (minPrice < 0) minPrice = 0; if (maxPrice > maxRange) maxPrice = maxRange; if (minPrice > maxPrice - priceGap) minPrice = maxPrice - priceGap;
                priceInputs[0].value = minPrice; priceInputs[1].value = maxPrice;
                rangeInputs[0].value = minPrice; rangeInputs[1].value = maxPrice;
                updateTrackFill(minPrice, maxPrice);
            });
        });
    }
});
</script>