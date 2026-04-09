<?php
ob_start();
session_start();
require_once 'config.php';

// ==========================================
// 🧠 引擎一：余弦相似度算法 (Cosine Similarity)
// 负责 Zone 1: AI Top Match (后台静默推荐)
// ==========================================

// 1. 获取用户的“数字 DNA”(如果未登录，给一组平均默认值)
$user_dna = ['g' => 0, 'c' => 0, 's' => 0, 'e' => 0];
$is_logged_in = isset($_SESSION['customer_id']);

if ($is_logged_in) {
    $cid = $_SESSION['customer_id'];
    $stmt_dna = $conn->prepare("SELECT pref_gamer, pref_creator, pref_student, pref_enthusiast FROM customers WHERE customer_id = ?");
    // 如果你还没在数据库建这几个字段，请先加上，否则这里会报错。
    // 这里用 try-catch 稍微保护一下，防止数据库没建好直接崩溃
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
    } catch (Exception $e) {
        // 数据库还没建 DNA 字段时的降级处理
    }
}

// 2. 余弦相似度计算函数 (纯数学实现，FYP 杀手锏)
function cosine_similarity($vec1, $vec2) {
    $dot_product = ($vec1['g'] * $vec2['g']) + ($vec1['c'] * $vec2['c']) + ($vec1['s'] * $vec2['s']) + ($vec1['e'] * $vec2['e']);
    $mag1 = sqrt(pow($vec1['g'], 2) + pow($vec1['c'], 2) + pow($vec1['s'], 2) + pow($vec1['e'], 2));
    $mag2 = sqrt(pow($vec2['g'], 2) + pow($vec2['c'], 2) + pow($vec2['s'], 2) + pow($vec2['e'], 2));
    if ($mag1 == 0 || $mag2 == 0) return 0;
    return $dot_product / ($mag1 * $mag2);
}

// 3. 找出最匹配的套餐
$top_package = null;
$highest_score = -1;

// 只有当用户 DNA 不是全 0 时才跑推荐算法
if (array_sum($user_dna) > 0) {
    $ai_sql = "SELECT * FROM packages WHERE stock_status = 'Available'";
    $ai_res = mysqli_query($conn, $ai_sql);
    while ($pkg = mysqli_fetch_assoc($ai_res)) {
        $pkg_dna = [
            'g' => $pkg['score_gamer'],
            'c' => $pkg['score_creator'],
            's' => $pkg['score_student'],
            'e' => $pkg['score_enthusiast']
        ];
        $sim_score = cosine_similarity($user_dna, $pkg_dna);
        if ($sim_score > $highest_score) {
            $highest_score = $sim_score;
            $top_package = $pkg;
        }
    }
}


// ==========================================
// 🔍 模块二：动态多维筛选器 (Dynamic SQL Builder)
// 负责 Zone 2: 满足 Rubric 的 Searching 要求
// ==========================================
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$persona_filter = isset($_GET['persona']) ? $conn->real_escape_string($_GET['persona']) : '';
$price_range = isset($_GET['price']) ? $_GET['price'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : '';

$sql = "SELECT * FROM packages WHERE stock_status = 'Available'";
$filter_active = false;

if (!empty($search_query)) {
    $sql .= " AND (package_name LIKE '%$search_query%' OR description LIKE '%$search_query%')";
    $filter_active = true;
}
if (!empty($persona_filter)) {
    $sql .= " AND target_persona = '$persona_filter'";
    $filter_active = true;
}
if (!empty($price_range)) {
    if ($price_range == 'low') { $sql .= " AND price <= 3000"; }
    elseif ($price_range == 'mid') { $sql .= " AND price BETWEEN 3001 AND 5000"; }
    elseif ($price_range == 'high') { $sql .= " AND price > 5000"; }
    $filter_active = true;
}
if ($sort_by == 'price_asc') { $sql .= " ORDER BY price ASC"; } 
elseif ($sort_by == 'price_desc') { $sql .= " ORDER BY price DESC"; } 
else { $sql .= " ORDER BY package_id DESC"; }

$catalog_result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<style>
    :root { --accent: #00f2fe; --dark-bg: #0a0a0a; --card-bg: rgba(255,255,255,0.03); }
    .page-container { max-width: 1200px; margin: 2rem auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    /* Zone 1: AI Recommendation */
    .ai-hero { background: linear-gradient(135deg, rgba(0,242,254,0.1) 0%, rgba(10,10,10,0.8) 100%); border: 1px solid var(--accent); border-radius: 15px; padding: 30px; margin-bottom: 40px; display: flex; gap: 30px; align-items: center; box-shadow: 0 0 30px rgba(0,242,254,0.15); position: relative; overflow: hidden; }
    .ai-badge { position: absolute; top: 0; left: 0; background: var(--accent); color: #000; padding: 5px 15px; font-weight: 900; font-size: 0.8rem; border-bottom-right-radius: 10px; letter-spacing: 1px; }
    
    /* Zone 2: Filter Bar */
    .filter-bar { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; }
    .search-box { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 10px 15px; border-radius: 8px; outline: none; flex: 1; min-width: 200px; }
    .pill-group { display: flex; gap: 10px; }
    .pill { padding: 8px 15px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); color: #888; text-decoration: none; font-size: 0.85rem; font-weight: bold; transition: 0.3s; }
    .pill:hover, .pill.active { background: rgba(0,242,254,0.1); border-color: var(--accent); color: var(--accent); }
    .select-box { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 10px; border-radius: 8px; outline: none; }

    /* Zone 2: Catalog Grid */
    .pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
    .pkg-card { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; }
    .pkg-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 25px rgba(0,242,254,0.1); }
    .pkg-img { width: 100%; height: 200px; object-fit: contain; background: rgba(0,0,0,0.3); border-radius: 8px; margin-bottom: 15px; }
    .pkg-title { font-size: 1.3rem; font-weight: 900; color: #fff; margin-bottom: 8px; }
    .pkg-desc { font-size: 0.9rem; color: #888; flex-grow: 1; margin-bottom: 20px; line-height: 1.4; }
    .pkg-price { font-size: 1.5rem; font-weight: 900; color: #00e676; margin-bottom: 15px; }
    
    .btn-group { display: flex; gap: 10px; }
    .btn-buy { flex: 1; background: var(--accent); color: #000; text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; }
    .btn-buy:hover { background: #fff; box-shadow: 0 0 15px var(--accent); }
    .btn-cust { flex: 1; background: transparent; border: 1px solid var(--accent); color: var(--accent); text-align: center; padding: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.2s; }
    .btn-cust:hover { background: var(--accent); color: #000; }

    /* Zone 3: Auto Generator */
    .engine2-box { margin-top: 60px; padding: 40px; background: rgba(0,0,0,0.6); border: 1px dashed rgba(255,255,255,0.2); border-radius: 15px; text-align: center; }
</style>

<div class="page-container">
    
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 3rem; font-weight: 900; color: #fff; margin: 0; letter-spacing: -1px;">PRE-BUILT <span style="color: var(--accent);">PACKAGES</span></h1>
        <p style="color: #888; font-size: 1.1rem;">Expertly assembled. Ready to ship. Powered by AI recommendation.</p>
    </div>

    <?php if ($top_package && !$filter_active): ?>
        <div class="ai-hero">
            <div class="ai-badge"><i class="fas fa-brain"></i> AI TAILORED FOR YOU</div>
            <img src="<?php echo htmlspecialchars($top_package['image_url'] ?? 'https://via.placeholder.com/300x300'); ?>" style="width: 250px; height: 250px; object-fit: contain;">
            <div>
                <div style="color: var(--accent); font-weight: bold; font-size: 0.9rem; letter-spacing: 2px; margin-bottom: 5px; text-transform: uppercase;">
                    <i class="fas fa-crosshairs"></i> MATCHED PERSONA: <?php echo $top_package['target_persona']; ?>
                </div>
                <h2 style="font-size: 2.2rem; color: #fff; font-weight: 900; margin: 0 0 10px 0;"><?php echo htmlspecialchars($top_package['package_name']); ?></h2>
                <p style="color: #cbd5e1; font-size: 1rem; max-width: 600px; margin-bottom: 20px;">
                    Based on your historical component selections in the Builder, our vector-space AI calculates this rig as your ultimate setup.
                </p>
                <div style="font-size: 2rem; color: #00e676; font-weight: 900; margin-bottom: 20px;">RM <?php echo number_format($top_package['price'], 2); ?></div>
                <div class="btn-group" style="max-width: 400px;">
                    <a href="cart_add.php?pkg_id=<?php echo $top_package['package_id']; ?>" class="btn-buy"><i class="fas fa-shopping-cart"></i> Buy Now</a>
                    <a href="builder_load_package.php?pkg_id=<?php echo $top_package['package_id']; ?>" class="btn-cust"><i class="fas fa-tools"></i> Customize</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="packages.php" method="GET" class="filter-bar">
        <input type="text" name="search" class="search-box" placeholder="Search RTX 4090, Intel i9..." value="<?php echo htmlspecialchars($search_query); ?>">
        
        <div class="pill-group">
            <a href="packages.php?persona=Gamer" class="pill <?php echo $persona_filter == 'Gamer' ? 'active' : ''; ?>"><i class="fas fa-gamepad"></i> Gamers</a>
            <a href="packages.php?persona=Creator" class="pill <?php echo $persona_filter == 'Creator' ? 'active' : ''; ?>"><i class="fas fa-palette"></i> Creators</a>
            <a href="packages.php?persona=Student" class="pill <?php echo $persona_filter == 'Student' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i> Students</a>
            <?php if(!empty($persona_filter) || !empty($search_query) || !empty($price_range)): ?>
                <a href="packages.php" class="pill" style="color: #ef4444; border-color: #ef4444;"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 10px;">
            <select name="price" class="select-box" onchange="this.form.submit()">
                <option value="">Any Budget</option>
                <option value="low" <?php echo $price_range == 'low' ? 'selected' : ''; ?>>Under RM 3,000</option>
                <option value="mid" <?php echo $price_range == 'mid' ? 'selected' : ''; ?>>RM 3,000 - RM 5,000</option>
                <option value="high" <?php echo $price_range == 'high' ? 'selected' : ''; ?>>Above RM 5,000</option>
            </select>
            <select name="sort" class="select-box" onchange="this.form.submit()">
                <option value="new">Newest First</option>
                <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>
    </form>

    <div class="pkg-grid">
        <?php if (mysqli_num_rows($catalog_result) > 0): ?>
            <?php while ($pkg = mysqli_fetch_assoc($catalog_result)): ?>
                <div class="pkg-card">
                    <img src="<?php echo htmlspecialchars($pkg['image_url'] ?? 'https://via.placeholder.com/280x200'); ?>" class="pkg-img">
                    <div style="font-size: 0.75rem; color: var(--accent); font-weight: bold; letter-spacing: 1px; margin-bottom: 5px; text-transform: uppercase;">
                        <?php echo htmlspecialchars($pkg['target_persona']); ?> EDITION
                    </div>
                    <div class="pkg-title"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                    <div class="pkg-desc"><?php echo htmlspecialchars($pkg['description']); ?></div>
                    <div class="pkg-price">RM <?php echo number_format($pkg['price'], 2); ?></div>
                    
                    <div class="btn-group">
                        <a href="cart_add.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-buy">Buy Now</a>
                        <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" class="btn-cust"><i class="fas fa-wrench"></i> Customize</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 10px; color: #888;">
                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px; color: #444;"></i>
                <h2>No packages found matching your criteria.</h2>
                <a href="packages.php" style="color: var(--accent); text-decoration: none;">Clear all filters</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="engine2-box">
        <h2 style="color: #fff; font-size: 2rem; font-weight: 900; margin-bottom: 10px;">Still can't find the perfect rig?</h2>
        <p style="color: #aaa; margin-bottom: 25px; max-width: 600px; margin-left: auto; margin-right: auto;">
            Tell us your exact budget and persona. Our AI Auto-Generator (Heuristic Greedy Algorithm) will scan thousands of parts and assemble a custom blueprint from scratch in seconds.
        </p>
        
        <form action="auto_generate.php" method="POST" style="display: flex; gap: 15px; justify-content: center; max-width: 600px; margin: 0 auto;">
            <select name="target_persona" class="select-box" style="flex: 1;" required>
                <option value="Gamer">For Gaming</option>
                <option value="Creator">For Rendering & Art</option>
                <option value="Student">For Study & Office</option>
            </select>
            <div style="position: relative; flex: 1;">
                <span style="position: absolute; left: 15px; top: 12px; color: #888; font-weight: bold;">RM</span>
                <input type="number" name="budget" class="search-box" style="width: 100%; padding-left: 45px;" placeholder="Max Budget" required min="1500" max="20000">
            </div>
            <button type="submit" class="btn-buy" style="border: none; cursor: pointer; padding: 0 30px;"><i class="fas fa-magic"></i> GENERATE</button>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>