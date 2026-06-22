<?php
ob_start();
session_start();
require_once 'config.php';


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


$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$persona_filter = isset($_GET['persona']) ? trim($_GET['persona']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'new';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : 0;

$filter_active = false;
$conditions = [];
$params = [];
$types = "";

if (!empty($search_query)) {
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

$packages_data = [];
if ($catalog_result->num_rows > 0) {
    $cpu_stmt = $conn->prepare("SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ? AND p.category_id = 1 LIMIT 1");
    $gpu_stmt = $conn->prepare("SELECT p.price FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ? AND p.category_id = 4 LIMIT 1");

    while ($pkg = $catalog_result->fetch_assoc()) {
        $cpu_stmt->bind_param("i", $pkg['package_id']);
        $cpu_stmt->execute();
        $cpu_p = ($cpu_row = $cpu_stmt->get_result()->fetch_assoc()) ? $cpu_row['price'] : 1500;

        $gpu_stmt->bind_param("i", $pkg['package_id']);
        $gpu_stmt->execute();
        $gpu_p = ($gpu_row = $gpu_stmt->get_result()->fetch_assoc()) ? $gpu_row['price'] : 3000;

        $cpu_index = pow($cpu_p / 3000, 0.6) * 100; 
        $gpu_index = pow($gpu_p / 6000, 0.6) * 100; 
        
        $pkg['fps_cyberpunk'] = round(30 + ($gpu_index * 0.85) + ($cpu_index * 0.15));
        $pkg['score_pr'] = round(($cpu_index * 0.65 + $gpu_index * 0.35) * 10);
        
        $packages_data[] = $pkg;
    }
    $cpu_stmt->close();
    $gpu_stmt->close();
}

include 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root { --accent: #00f2fe; --dark-bg: #030305; --card-bg: rgba(255,255,255,0.02); --card-border: rgba(255,255,255,0.08); }
    
    body { background-color: var(--dark-bg); color: #fff; font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
    .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; pointer-events: none;}
    
    .page-container { max-width: 1300px; margin: 2rem auto; padding: 0 20px; position: relative; z-index: 1;}


    .ai-hero { background: linear-gradient(135deg, rgba(0,242,254,0.1) 0%, rgba(10,10,10,0.8) 100%); border: 1px solid var(--accent); border-radius: 12px; padding: 30px; margin-bottom: 40px; display: flex; gap: 30px; align-items: center; box-shadow: 0 0 30px rgba(0,242,254,0.15); position: relative; overflow: hidden; }
    .ai-badge { position: absolute; top: 0; left: 0; background: var(--accent); color: #000; padding: 5px 15px; font-weight: 900; font-size: 0.8rem; border-bottom-right-radius: 10px; letter-spacing: 1px; font-family: 'JetBrains Mono', monospace;}
    

    .filter-panel { background: rgba(10, 10, 15, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(0,242,254,0.2); padding: 25px; border-radius: 12px; margin-bottom: 30px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .filter-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
    
    .search-wrapper { position: relative; flex: 1; min-width: 250px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }
    .search-box { width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 14px 15px 14px 45px; border-radius: 8px; outline: none; font-family: 'Inter', sans-serif; font-size: 0.95rem; transition: 0.3s; box-sizing: border-box; }
    .search-box:focus { border-color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.15); }
    
    .custom-select-wrapper { position: relative; min-width: 250px; user-select: none; z-index: 50; }
    .custom-select { background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 14px 15px; border-radius: 8px; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
    .custom-select:hover { border-color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.15); }
    .custom-options { position: absolute; top: 110%; left: 0; right: 0; background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(10px); border: 1px solid var(--accent); border-radius: 8px; overflow: hidden; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.8); animation: slideDown 0.2s ease forwards; opacity: 0; transform: translateY(-10px); }
    .custom-options.open { display: block; }
    .custom-option { padding: 12px 15px; color: #cbd5e1; cursor: pointer; transition: 0.2s; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;}
    .custom-option:last-child { border-bottom: none; }
    .custom-option:hover, .custom-option.selected { background: rgba(0, 242, 254, 0.1); color: var(--accent); padding-left: 20px; font-weight: bold;}

    .pill-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .pill { padding: 10px 20px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); color: #888; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; background: rgba(255,255,255,0.02); }
    .pill:hover, .pill.active { background: rgba(0,242,254,0.05); border-color: var(--accent); color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.1); }

    .price-widget { flex: 1; max-width: 400px; display: flex; flex-direction: column; gap: 10px; }
    .price-inputs { display: flex; align-items: center; gap: 10px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #64748b; }
    .price-inputs input { width: 90px; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px; border-radius: 6px; text-align: center; outline: none; font-family: 'JetBrains Mono', monospace; transition: 0.2s; }
    .price-inputs input:focus { border-color: var(--accent); }
    .price-inputs input[type="number"]::-webkit-inner-spin-button, .price-inputs input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    
    .slider-container { position: relative; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; }
    .slider-track-fill { position: absolute; height: 100%; left: 0%; right: 0%; background: var(--accent); border-radius: 2px; pointer-events: none; }
    .range-inputs { position: relative; }
    .range-inputs input { position: absolute; top: -10px; height: 4px; width: 100%; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    .range-inputs input::-webkit-slider-thumb { height: 18px; width: 18px; border-radius: 50%; background: #fff; pointer-events: auto; -webkit-appearance: none; cursor: pointer; transition: 0.2s; box-shadow: 0 0 10px rgba(0,0,0,0.5); border: 2px solid var(--accent);}
    .range-inputs input::-webkit-slider-thumb:hover { transform: scale(1.2); }

    .btn-exec { background: rgba(0, 242, 254, 0.1); color: var(--accent); border: 1px solid var(--accent); padding: 12px 20px; border-radius: 6px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: 0.3s; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; gap: 8px; box-sizing: border-box; font-family: 'Inter', sans-serif;}
    .btn-exec:hover { background: var(--accent); color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px);}

    .system-note { margin-bottom: 30px; background: rgba(0, 242, 254, 0.05); border: 1px solid rgba(0, 242, 254, 0.2); border-left: 4px solid #00f2fe; padding: 15px 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; }
    .pulse-icon { color: #00f2fe; font-size: 1.5rem; animation: pulse-alert 2s infinite; }
    @keyframes pulse-alert { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.1); } 100% { opacity: 1; transform: scale(1); } }


    .pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
    .pkg-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; position: relative;}
    .pkg-card:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 15px 30px rgba(0,0,0,0.5), inset 0 0 15px rgba(0,242,254,0.05); }
    
    .mc-img-box { width: 100%; height: 220px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative; }
    .mc-img-box img { width: 85%; height: 85%; object-fit: contain; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .pkg-card:hover .mc-img-box img { transform: scale(1.15); filter: brightness(0.4); }
    
    .telemetry-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); padding: 15px; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-top: 1px solid rgba(0,242,254,0.3); z-index: 10;}
    .pkg-card:hover .telemetry-overlay { transform: translateY(0); }
    .tele-row { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 6px; font-weight: 800; font-family: 'JetBrains Mono', monospace; }
    .tele-val { color: #fff; }
    .tele-label { color: #888; display: flex; align-items: center; gap: 5px; font-family: 'Inter', sans-serif;}
    .tele-bar-bg { width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-bottom: 12px; overflow: hidden; }
    .tele-bar-fill { height: 100%; border-radius: 2px; }

    .pkg-tag { font-size: 0.7rem; color: var(--accent); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace; }
    .pkg-title { font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 10px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3.2rem;}
    .pkg-price { font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 900; color: #00e676; margin-bottom: 0; }
    

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
    .btn-action { flex: 1; padding: 12px 10px; border-radius: 6px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: 0.3s; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; gap: 6px; box-sizing: border-box; border: none; font-family: 'Inter', sans-serif; width: 100%;}
    .btn-buy { background: rgba(0, 242, 254, 0.1); color: var(--accent); border: 1px solid var(--accent); }
    .btn-buy:hover { background: var(--accent); color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px);}
    .btn-cust { background: rgba(255,255,255,0.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); }
    .btn-cust:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.3); }


    .ai-command-center { margin: 60px auto 40px; max-width: 900px; background: rgba(10, 10, 10, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(0, 242, 254, 0.2); border-radius: 16px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
    .ai-cc-header { text-align: center; margin-bottom: 40px; }
    .ai-cc-badge-top { display: inline-block; background: rgba(0,242,254,0.1); color: var(--accent); padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; font-weight: 900; border: 1px solid var(--accent); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px; }
    .budget-display-area { text-align: center; margin-bottom: 30px; }
    .budget-amount { font-size: 4rem; font-weight: 900; color: #fff; letter-spacing: -2px; margin: 10px 0; text-shadow: 0 0 20px rgba(0, 242, 254, 0.3); display: flex; justify-content: center; align-items: baseline; gap: 10px; }
    .budget-currency { font-size: 1.5rem; color: #888; }
    .ai-custom-range { -webkit-appearance: none; width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 5px; outline: none; margin-bottom: 15px; }
    .ai-custom-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 24px; height: 24px; border-radius: 50%; background: var(--accent); cursor: pointer; box-shadow: 0 0 15px var(--accent); transition: transform 0.1s; }
    .ai-custom-range::-webkit-slider-thumb:hover { transform: scale(1.2); }
    .tier-feedback { font-size: 0.9rem; color: #a855f7; font-weight: bold; letter-spacing: 1px; transition: color 0.3s; }
    
    .persona-selector { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 40px; }
    .persona-option { position: relative; cursor: pointer; }
    .persona-option input[type="radio"] { display: none; }
    .persona-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px 10px; text-align: center; color: #888; transition: all 0.3s ease; height: 100%; box-sizing: border-box;}
    .persona-card i { font-size: 1.8rem; margin-bottom: 10px; display: block; }
    .persona-card span { font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; display: block; line-height: 1.3;}
    
    .persona-option input:checked + .persona-card[data-theme="gamer"] { background: rgba(0, 242, 254, 0.1); border-color: #00f2fe; color: #00f2fe; box-shadow: 0 0 20px rgba(0,242,254,0.2); }
    .persona-option input:checked + .persona-card[data-theme="creator"] { background: rgba(168, 85, 247, 0.1); border-color: #a855f7; color: #a855f7; box-shadow: 0 0 20px rgba(168,85,247,0.2); }
    .persona-option input:checked + .persona-card[data-theme="student"] { background: rgba(250, 204, 21, 0.1); border-color: #facc15; color: #facc15; box-shadow: 0 0 20px rgba(250,204,21,0.2); }
    .persona-option input:checked + .persona-card[data-theme="enthusiast"] { background: rgba(255, 0, 127, 0.1); border-color: #ff007f; color: #ff007f; box-shadow: 0 0 20px rgba(255,0,127,0.2); }
    
    .btn-generate { display: block; width: 100%; background: linear-gradient(90deg, #00f2fe, #4facfe); color: #000; border: none; padding: 20px; border-radius: 10px; font-size: 1.2rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,242,254,0.3); font-family: 'Inter', sans-serif;}
    .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(0,242,254,0.5); }
    
    @keyframes slideDown { to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) { 
        .filter-row { flex-direction: column; align-items: stretch; } 
        .persona-selector { grid-template-columns: repeat(2, 1fr); } 
        .budget-amount { font-size: 3rem; } 
        .custom-select-wrapper { min-width: 100%; }
    }
</style>

<div class="cyber-grid-bg"></div>

<div class="page-container">
    
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 3rem; font-weight: 900; color: #fff; margin: 0; letter-spacing: -1px;">PRE-BUILT <span style="color: var(--accent); text-shadow: 0 0 20px rgba(0,242,254,0.4);">PACKAGES</span></h1>
        <p style="color: #888; font-size: 1.1rem;">Expertly assembled. Ready to ship. Powered by AI recommendation.</p>
    </div>

    <?php if ($top_package && !$filter_active): ?>
        <div class="ai-hero">
            <div class="ai-badge">SYS.DNA TARGET MATCH</div>
            <?php 
                $raw_img = !empty($top_package['image_url']) ? trim($top_package['image_url']) : '';
                $img_src = (strpos($raw_img, 'http') === 0 || strpos($raw_img, 'data:image') === 0) ? $raw_img : (empty($raw_img) ? 'image/placeholder.jpg' : 'image/' . basename($raw_img));
            ?>
            <img src="<?php echo htmlspecialchars($img_src); ?>" style="width: 250px; height: 250px; object-fit: contain;">
            <div>
                <div style="color: var(--accent); font-weight: bold; font-size: 0.9rem; letter-spacing: 2px; margin-bottom: 5px; text-transform: uppercase;">
                    <i class="fas fa-crosshairs"></i> OPTIMIZED FOR: <?php echo $top_package['target_persona']; ?>
                </div>
                <h2 style="font-size: 2.2rem; color: #fff; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px;"><?php echo htmlspecialchars($top_package['package_name']); ?></h2>
                <p style="color: #cbd5e1; font-size: 1rem; max-width: 600px; margin-bottom: 20px;">
                    Based on your historical component selections in the Builder, our vector-space AI calculates this rig as your ultimate setup.
                </p>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 2rem; color: #00e676; font-weight: 900; margin-bottom: 20px;">RM <?php echo number_format($top_package['real_price'], 2); ?></div>
                
                <div class="btn-group" style="max-width: 400px; margin-top: 0;">
                    <form action="add_to_cart.php" method="POST" style="flex:1;">
                        <input type="hidden" name="package_id" value="<?php echo $top_package['package_id']; ?>">
                        <input type="hidden" name="action" value="buy_now">
                        <button type="submit" class="btn-action btn-buy" style="width:100%;"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </form>
                    <a href="builder_load_package.php?pkg_id=<?php echo $top_package['package_id']; ?>" class="btn-action btn-cust"><i class="fas fa-wrench"></i> Customize</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="packages.php" method="GET" class="filter-panel" id="filterForm">
        <div class="filter-row">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-box" placeholder="Search parameters (e.g. RTX 4090, AMD)..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            
            <div class="custom-select-wrapper" id="customSelectWrapper">
                <div class="custom-select" id="customSelect">
                    <?php 
                        $sort_text = "Sort By: Newest First";
                        if($sort_by == 'price_asc') $sort_text = "Sort By: Price (Low to High)";
                        if($sort_by == 'price_desc') $sort_text = "Sort By: Price (High to Low)";
                    ?>
                    <span id="customSelectText"><?php echo $sort_text; ?></span>
                    <i class="fas fa-chevron-down" id="customSelectIcon" style="transition: transform 0.3s;"></i>
                </div>
                <div class="custom-options" id="customOptions">
                    <div class="custom-option" data-value="new">Sort By: Newest First</div>
                    <div class="custom-option" data-value="price_asc">Sort By: Price (Low to High)</div>
                    <div class="custom-option" data-value="price_desc">Sort By: Price (High to Low)</div>
                </div>
                <input type="hidden" name="sort" id="sortInput" value="<?php echo htmlspecialchars($sort_by); ?>">
            </div>
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
                    <i class="fas fa-code"></i> Students
                </label>
                <label class="pill <?php echo $persona_filter == 'Enthusiast' ? 'active' : ''; ?>">
                    <input type="radio" name="persona" value="Enthusiast" style="display: none;" <?php echo $persona_filter == 'Enthusiast' ? 'checked' : ''; ?>>
                    <i class="fas fa-rocket"></i> Enthusiast
                </label>
            </div>

            <div class="price-widget">
                <div class="price-inputs">
                    <span>BUDGET: RM</span>
                    <input type="number" class="input-min" name="min_price" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>">
                    <span>-</span>
                    <input type="number" class="input-max" name="max_price" value="<?php echo $max_price > 0 ? $max_price : '50000'; ?>">
                </div>
                
                <div style="position: relative; margin-top: 5px;">
                    <div class="slider-container"><div class="slider-track-fill"></div></div>
                    <div class="range-inputs">
                        <input type="range" class="range-min" min="0" max="50000" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>" step="500">
                        <input type="range" class="range-max" min="0" max="50000" value="<?php echo $max_price > 0 ? $max_price : '50000'; ?>" step="500">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-exec"><i class="fas fa-server" style="margin-right:8px;"></i> EXECUTE</button>
                <?php if($filter_active): ?>
                    <a href="packages.php" class="btn-exec" style="color: #ef4444; border-color: rgba(239,68,68,0.3); padding: 12px 15px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="system-note">
        <i class="fas fa-info-circle pulse-icon"></i>
        <div>
            <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 1rem; letter-spacing: 0.5px;">SYSTEM NOTE: Core Components Only</h4>
            <p style="margin: 0; color: #cbd5e1; font-size: 0.85rem; line-height: 1.5;">Packages include Tower components (CPU, GPU, etc.). Peripherals like Monitors are left blank for your personalization. Builder load completion at ~80% is expected.</p>
        </div>
    </div>

    <div class="pkg-grid">
        <?php if (!empty($packages_data)): ?>
            <?php foreach ($packages_data as $pkg): ?>
                <div class="pkg-card">
                    <a href="builder_load_package.php?pkg_id=<?php echo $pkg['package_id']; ?>" style="text-decoration: none; display: block; color: inherit;">
                        <div class="mc-img-box">
                            <?php 
                                $raw_img = !empty($pkg['image_url']) ? trim($pkg['image_url']) : '';
                                $img_src = (strpos($raw_img, 'http') === 0 || strpos($raw_img, 'data:image') === 0) ? $raw_img : (empty($raw_img) ? 'image/placeholder.jpg' : 'image/' . basename($raw_img));
                            ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" onerror="this.src='image/placeholder.jpg';">
                            
                            <div class="telemetry-overlay">
                                <div class="tele-row">
                                    <span class="tele-label"><i class="fas fa-gamepad" style="color:var(--accent);"></i> Cyberpunk FPS</span>
                                    <span class="tele-val"><?php echo $pkg['fps_cyberpunk']; ?></span>
                                </div>
                                <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['fps_cyberpunk']/144)*100, 100); ?>%; background: var(--accent);"></div></div>
                                
                                <div class="tele-row">
                                    <span class="tele-label"><i class="fas fa-palette" style="color:#a855f7;"></i> Premiere Score</span>
                                    <span class="tele-val"><?php echo $pkg['score_pr']; ?></span>
                                </div>
                                <div class="tele-bar-bg"><div class="tele-bar-fill" style="width: <?php echo min(($pkg['score_pr']/2000)*100, 100); ?>%; background: #a855f7;"></div></div>
                            </div>
                        </div>
                        
                        <div class="pkg-tag">CLASS_<?php echo htmlspecialchars($pkg['target_persona']); ?></div>
                        <div class="pkg-title"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                    </a>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div class="pkg-price" style="margin-bottom: 0;">RM <?php echo number_format($pkg['real_price'], 2); ?></div>
                        <button type="button" class="btn-view-specs" onclick="openSpecsModal('<?php echo htmlspecialchars(addslashes($pkg['package_name'])); ?>', <?php echo $pkg['package_id']; ?>)">
                            <i class="fas fa-list-ul"></i> Specs
                        </button>
                    </div>

                    <div id="hidden_specs_<?php echo $pkg['package_id']; ?>" style="display: none;">
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 20px 25px; background: rgba(0,0,0,0.4); border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div style="text-align: center;">
            <i class="fas fa-gamepad" style="color: #00f2fe; font-size: 1.2rem; margin-bottom: 5px;"></i>
            <div style="font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: 800;">Gamer</div>
            <div style="font-family: 'JetBrains Mono', monospace; font-weight: 900; color: #fff;"><?php echo $pkg['score_gamer']; ?><span style="color:#555; font-size:0.7rem;">/10</span></div>
        </div>
        <div style="text-align: center;">
            <i class="fas fa-palette" style="color: #a855f7; font-size: 1.2rem; margin-bottom: 5px;"></i>
            <div style="font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: 800;">Creator</div>
            <div style="font-family: 'JetBrains Mono', monospace; font-weight: 900; color: #fff;"><?php echo $pkg['score_creator']; ?><span style="color:#555; font-size:0.7rem;">/10</span></div>
        </div>
        <div style="text-align: center;">
            <i class="fas fa-code" style="color: #facc15; font-size: 1.2rem; margin-bottom: 5px;"></i>
            <div style="font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: 800;">Student</div>
            <div style="font-family: 'JetBrains Mono', monospace; font-weight: 900; color: #fff;"><?php echo $pkg['score_student']; ?><span style="color:#555; font-size:0.7rem;">/10</span></div>
        </div>
        <div style="text-align: center;">
            <i class="fas fa-rocket" style="color: #ff007f; font-size: 1.2rem; margin-bottom: 5px;"></i>
            <div style="font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: 800;">Enthusiast</div>
            <div style="font-family: 'JetBrains Mono', monospace; font-weight: 900; color: #fff;"><?php echo $pkg['score_enthusiast']; ?><span style="color:#555; font-size:0.7rem;">/10</span></div>
        </div>
    </div>
    
    <div style="padding-top: 10px;">
        <?php
            $list_sql = "SELECT p.product_name, c.category_name FROM package_items pi JOIN products p ON pi.product_id = p.product_id JOIN categories c ON p.category_id = c.category_id WHERE pi.package_id = " . $pkg['package_id'];
            $list_res = $conn->query($list_sql);
            if ($list_res->num_rows > 0) {
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
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: rgba(0,0,0,0.4); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                <i class="fas fa-satellite-dish" style="font-size: 4rem; margin-bottom: 20px; color: #475569;"></i>
                <h2 style="color: #fff; margin:0 0 10px 0;">NO SIGNAL DETECTED</h2>
                <p style="color: #64748b; margin-bottom: 25px;">Query returned 0 results. Adjust your parameters.</p>
                <a href="packages.php" class="btn-action btn-cust" style="width: auto;"><i class="fas fa-sync-alt"></i> Reset Parameters</a>
            </div>
        <?php endif; ?>
    </div>

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
                <input type="range" class="ai-custom-range" id="ai-budget-slider" min="1500" max="50000" step="500" value="4000">
                
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
                        <span>Gamer</span>
                    </div>
                </label>
                
                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Creator">
                    <div class="persona-card" data-theme="creator">
                        <i class="fas fa-palette"></i>
                        <span>Creator</span>
                    </div>
                </label>
                
                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Student">
                    <div class="persona-card" data-theme="student">
                        <i class="fas fa-code"></i>
                        <span>Student / Dev</span>
                    </div>
                </label>

                <label class="persona-option">
                    <input type="radio" name="target_persona" value="Enthusiast">
                    <div class="persona-card" data-theme="enthusiast">
                        <i class="fas fa-rocket"></i>
                        <span>Enthusiast</span>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn-generate">
                <i class="fas fa-bolt"></i> Generate Blueprint
            </button>
        </form>
    </div>

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

<?php include 'includes/footer.php'; ?>

<script>

function openSpecsModal(pkgName, pkgId) {
    document.getElementById('modalPkgName').innerText = pkgName;
    const specsHtml = document.getElementById('hidden_specs_' + pkgId).innerHTML;
    document.getElementById('modalSpecsBody').innerHTML = specsHtml;
    
    const modal = document.getElementById('specsModal');
    modal.style.display = 'flex';
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

document.addEventListener("DOMContentLoaded", function() {

    // === 1. Custom Select Matrix Logic ===
    const selectWrapper = document.getElementById('customSelectWrapper');
    const selectBtn = document.getElementById('customSelect');
    const optionsPanel = document.getElementById('customOptions');
    const options = document.querySelectorAll('.custom-option');
    const sortInput = document.getElementById('sortInput');
    const selectText = document.getElementById('customSelectText');
    const selectIcon = document.getElementById('customSelectIcon');

    if(selectBtn) {
        selectBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            optionsPanel.classList.toggle('open');
            selectIcon.style.transform = optionsPanel.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0)';
        });

        options.forEach(option => {
            option.addEventListener('click', function() {
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectText.innerText = this.innerText;
                sortInput.value = this.getAttribute('data-value');
                optionsPanel.classList.remove('open');
                selectIcon.style.transform = 'rotate(0)';
                document.getElementById('filterForm').submit();
            });
            if(option.getAttribute('data-value') === sortInput.value) {
                option.classList.add('selected');
            }
        });

        document.addEventListener('click', function(e) {
            if (!selectWrapper.contains(e.target)) {
                optionsPanel.classList.remove('open');
                selectIcon.style.transform = 'rotate(0)';
            }
        });
    }


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
            else if (value < 15000) { text = "Estimated: Enthusiast God-Tier (Flawless 4K)"; color = "#ff007f"; }
            else { text = "Estimated: NASA Supercomputer Level"; color = "#ff0000"; }
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


    const rangeInputs = document.querySelectorAll(".range-inputs input");
    const priceInputs = document.querySelectorAll(".price-inputs input");
    const trackFill = document.querySelector(".slider-track-fill");
    const maxRange = 50000; const priceGap = 1000;

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
</body>
</html>