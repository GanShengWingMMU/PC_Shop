<?php
session_start();
require_once 'config.php';


$categories = [];
$cat_query = "SELECT * FROM categories ORDER BY category_name ASC";
$cat_result = $conn->query($cat_query);
$active_category_name = "All Components"; 
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
    if (isset($_GET['category']) && $_GET['category'] == $row['category_id']) {
        $active_category_name = $row['category_name'];
    }
}


$active_category_id = isset($_GET['category']) ? intval($_GET['category']) : 0; 
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? max(0, floatval($_GET['min_price'])) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'default';


$conditions = ["stock_quantity > 0"]; 
$params = [];
$types = "";

if ($active_category_id > 0) {
    $conditions[] = "category_id = ?";
    $params[] = $active_category_id;
    $types .= "i";
}
if ($min_price > 0) {
    $conditions[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $conditions[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}
if (!empty($search_query)) {
    $conditions[] = "product_name LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= "s";
}

$where_clause = implode(" AND ", $conditions);


$order_by = "ORDER BY product_name ASC";
if ($sort_option === 'price_asc') {
    $order_by = "ORDER BY price ASC";
} elseif ($sort_option === 'price_desc') {
    $order_by = "ORDER BY price DESC";
} elseif ($sort_option === 'newest') {
    $order_by = "ORDER BY product_id DESC";
}

$prod_query = "SELECT * FROM products WHERE $where_clause $order_by";


$stmt = $conn->prepare($prod_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params); 
}
$stmt->execute();
$prod_result = $stmt->get_result();

$products = [];
while ($row = $prod_result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Vault - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        :root { --accent: #00f2fe; --dark-card: rgba(255,255,255,0.03); }
        body { background-color: #030305; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; pointer-events: none;}
        
      
        .builder-dashboard { max-width: 95%; margin: 40px auto 80px; display: grid; grid-template-columns: 320px 1fr; gap: 50px; align-items: start; }
        
        
        .builder-sidebar-column { 
            position: sticky; top: 100px; 
            max-height: calc(100vh - 120px); 
            overflow-y: auto; 
            background: rgba(10, 10, 15, 0.85); backdrop-filter: blur(20px); 
            border: 1px solid rgba(0, 242, 254, 0.2); border-radius: 16px; 
            padding: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
        }
        
        .builder-sidebar-column::-webkit-scrollbar { width: 6px; }
        .builder-sidebar-column::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 10px; }
        .builder-sidebar-column::-webkit-scrollbar-thumb { background: rgba(0, 242, 254, 0.3); border-radius: 10px; }
        .builder-sidebar-column::-webkit-scrollbar-thumb:hover { background: var(--accent); }
        
        @media (max-width: 1024px) {
            .builder-dashboard { grid-template-columns: 1fr; max-width: 100%; padding: 0 20px;}
            .builder-sidebar-column { position: static; max-height: none; overflow-y: visible; margin-bottom: 30px; }
        }

        .breadcrumbs { font-size: 0.85rem; color: #64748b; margin-bottom: 20px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .breadcrumbs a { color: var(--accent); text-decoration: none; transition: 0.3s; }
        .breadcrumbs a:hover { color: #fff; text-shadow: 0 0 10px var(--accent); }

        .tech-input { width: 100%; background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 12px 15px; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; transition: 0.3s; font-family: 'Inter', sans-serif;}
        .tech-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.15); }

        /* Custom Sort Matrix */
        .custom-select-wrapper { position: relative; width: 100%; user-select: none; }
        .custom-select { background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 12px 15px; border-radius: 8px; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .custom-select:hover { border-color: var(--accent); box-shadow: 0 0 15px rgba(0,242,254,0.15); }
        .custom-options { position: absolute; top: 110%; left: 0; right: 0; background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(10px); border: 1px solid var(--accent); border-radius: 8px; overflow: hidden; display: none; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.8); animation: slideDown 0.2s ease forwards; opacity: 0; transform: translateY(-10px); }
        .custom-options.open { display: block; }
        .custom-option { padding: 12px 15px; color: #cbd5e1; cursor: pointer; transition: 0.2s; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;}
        .custom-option:last-child { border-bottom: none; }
        .custom-option:hover, .custom-option.selected { background: rgba(0, 242, 254, 0.1); color: var(--accent); padding-left: 20px; font-weight: bold;}
        
        @keyframes slideDown { to { opacity: 1; transform: translateY(0); } }

        .cat-link { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; color: #cbd5e1; text-decoration: none; border-radius: 8px; margin-bottom: 6px; transition: 0.3s; border: 1px solid transparent; font-size: 0.95rem; }
        .cat-link:hover { background: rgba(0, 242, 254, 0.05); color: #fff; border-color: rgba(0, 242, 254, 0.2); }
        .cat-link.active { background: rgba(0, 242, 254, 0.1); color: var(--accent); border-color: var(--accent); font-weight: 800; box-shadow: inset 0 0 10px rgba(0, 242, 254, 0.1); }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .slot-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; }
        .slot-card:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 15px 30px rgba(0,0,0,0.5), inset 0 0 15px rgba(0,242,254,0.05); }
        
        .product-img-box { width: 100%; height: 220px; margin-bottom: 20px; display: flex; justify-content: center; align-items: center; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; position: relative; }
        .product-img { max-width: 85%; max-height: 85%; object-fit: contain; transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .slot-card:hover .product-img { transform: scale(1.15); }

        .product-title { color: #fff; font-size: 1.15rem; margin-bottom: 10px; line-height: 1.4; font-weight: 800; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3.2rem; }
        .product-price { color: #00e676; font-size: 1.5rem; font-weight: 900; margin-bottom: 20px; font-family: 'JetBrains Mono', monospace; letter-spacing: -0.5px; }

        .btn-action { padding: 10px 15px; border-radius: 6px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: 0.3s; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; gap: 8px; box-sizing: border-box; width: 100%; border: none; font-family: 'Inter', sans-serif;}
        .btn-select { background: rgba(0, 242, 254, 0.1); color: var(--accent); border: 1px solid var(--accent); }
        .btn-select:hover { background: var(--accent); color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); transform: translateY(-2px);}
        .btn-change { background: rgba(255,255,255,0.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); }
        .btn-change:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.3); }

        .price-filter-widget { padding: 10px 0 5px; }
        .price-input-group { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 15px; }
        .price-field { width: 100%; }
        .slider-container { position: relative; height: 4px; background: rgba(255,255,255,0.1); border-radius: 4px; margin-bottom: 10px; }
        .slider-track-fill { position: absolute; height: 100%; background: var(--accent); border-radius: 4px; pointer-events: none; box-shadow: 0 0 10px var(--accent); }
        .range-inputs { position: relative; }
        .range-inputs input { position: absolute; top: -10px; height: 4px; width: 100%; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
        .range-inputs input::-webkit-slider-thumb { height: 16px; width: 16px; border-radius: 50%; background: #fff; pointer-events: auto; -webkit-appearance: none; box-shadow: 0 0 10px rgba(0,0,0,0.8); cursor: pointer; border: 2px solid var(--accent); }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>

<div class="builder-dashboard">
    <div class="builder-sidebar-column">
        <h3 style="margin: 0; color: #fff; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px;">
            <i class="fas fa-terminal" style="color: var(--accent);"></i> TERMINAL CONTROLS
        </h3>

        <form action="components.php" method="GET" id="filterForm">
            <?php if ($active_category_id > 0): ?>
                <input type="hidden" name="category" value="<?php echo $active_category_id; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 25px;">
                <label style="font-size: 0.75rem; color: #888; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; display: block;">Database Query</label>
                <div style="position: relative;">
                    <input type="text" name="search" class="tech-input" placeholder="Search components..." value="<?php echo htmlspecialchars($search_query); ?>" style="margin-bottom: 0;">
                    <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="font-size: 0.75rem; color: #888; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; display: block;">Sort Matrix</label>
                <div class="custom-select-wrapper" id="customSelectWrapper">
                    <div class="custom-select" id="customSelect">
                        <?php 
                            $sort_text = "Default Order";
                            if($sort_option == 'newest') $sort_text = "Newest Arrivals";
                            if($sort_option == 'price_asc') $sort_text = "Price: Low to High";
                            if($sort_option == 'price_desc') $sort_text = "Price: High to Low";
                        ?>
                        <span id="customSelectText"><?php echo $sort_text; ?></span>
                        <i class="fas fa-chevron-down" id="customSelectIcon" style="transition: transform 0.3s;"></i>
                    </div>
                    <div class="custom-options" id="customOptions">
                        <div class="custom-option" data-value="default">Default Order</div>
                        <div class="custom-option" data-value="newest">Newest Arrivals</div>
                        <div class="custom-option" data-value="price_asc">Price: Low to High</div>
                        <div class="custom-option" data-value="price_desc">Price: High to Low</div>
                    </div>
                    <input type="hidden" name="sort" id="sortInput" value="<?php echo htmlspecialchars($sort_option); ?>">
                </div>
            </div>

            <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <label style="font-size: 0.75rem; color: #888; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: block;">Budget Range</label>
                <div class="price-filter-widget">
                    <div class="price-input-group">
                        <div class="price-field"><input type="number" class="tech-input input-min" name="min_price" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>" style="margin:0; padding:10px; text-align:center;"></div>
                        <div style="color: #64748b; font-weight: bold;">-</div>
                        <div class="price-field"><input type="number" class="tech-input input-max" name="max_price" value="<?php echo $max_price > 0 ? $max_price : '10000'; ?>" style="margin:0; padding:10px; text-align:center;"></div>
                    </div>
                    <div class="slider-container"><div class="slider-track-fill"></div></div>
                    <div class="range-inputs">
                        <input type="range" class="range-min" min="0" max="10000" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>" step="10">
                        <input type="range" class="range-max" min="0" max="10000" value="<?php echo $max_price > 0 ? $max_price : '10000'; ?>" step="10">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="font-size: 0.75rem; color: #888; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; display: block;">Hardware Categories</label>
                
                <?php
                $url_params = "";
                if ($min_price > 0) $url_params .= "&min_price=$min_price";
                if ($max_price > 0) $url_params .= "&max_price=$max_price";
                if (!empty($search_query)) $url_params .= "&search=" . urlencode($search_query);
                if ($sort_option != 'default') $url_params .= "&sort=$sort_option";
                ?>

                <a href="components.php?category=0<?php echo $url_params; ?>" class="cat-link <?php echo ($active_category_id == 0) ? 'active' : ''; ?>">
                    <span><i class="fa-solid fa-server" style="margin-right: 12px; width:15px; text-align:center;"></i> All Parts</span>
                </a>
                
                <?php foreach ($categories as $cat): 
                    $icon = 'fa-microchip'; 
                    if (stripos($cat['category_name'], 'GPU') !== false || stripos($cat['category_name'], 'Graphic') !== false) $icon = 'fa-tv';
                    elseif (stripos($cat['category_name'], 'Motherboard') !== false) $icon = 'fa-chess-board';
                    elseif (stripos($cat['category_name'], 'RAM') !== false || stripos($cat['category_name'], 'Memory') !== false) $icon = 'fa-memory';
                    elseif (stripos($cat['category_name'], 'Storage') !== false || stripos($cat['category_name'], 'SSD') !== false) $icon = 'fa-hdd';
                    elseif (stripos($cat['category_name'], 'Power') !== false || stripos($cat['category_name'], 'PSU') !== false) $icon = 'fa-plug';
                    elseif (stripos($cat['category_name'], 'Case') !== false) $icon = 'fa-box';
                    elseif (stripos($cat['category_name'], 'Cool') !== false || stripos($cat['category_name'], 'Fan') !== false) $icon = 'fa-fan';
                ?>
                    <a href="components.php?category=<?php echo $cat['category_id'] . $url_params; ?>" 
                       class="cat-link <?php echo ($active_category_id == $cat['category_id']) ? 'active' : ''; ?>">
                        <span><i class="fas <?php echo $icon; ?>" style="margin-right: 12px; width:15px; text-align:center;"></i> <?php echo htmlspecialchars($cat['category_name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-action btn-select" style="margin-top: 15px; padding: 14px; font-size: 1rem;">
                <i class="fas fa-satellite-dish"></i> EXECUTE FILTER
            </button>

            <?php if ($min_price > 0 || $max_price > 0 || !empty($search_query) || $sort_option != 'default'): ?>
                <a href="components.php<?php echo $active_category_id > 0 ? '?category='.$active_category_id : ''; ?>" style="display: block; text-align: center; margin-top: 20px; color: #ef4444; font-size: 0.85rem; text-decoration: none; font-weight: bold; transition: 0.3s;" onmouseover="this.style.textShadow='0 0 10px #ef4444'" onmouseout="this.style.textShadow='none'">
                    <i class="fa-solid fa-xmark"></i> RESET PARAMETERS
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div>
        <div class="breadcrumbs">
            <a href="index.php"><i class="fas fa-home"></i> Home</a> 
            <span style="margin: 0 8px; color: #333;">/</span> 
            <a href="components.php">Components Shop</a> 
            <span style="margin: 0 8px; color: #333;">/</span> 
            <span style="color: #fff; font-weight: 800;"><?php echo htmlspecialchars($active_category_name); ?></span>
        </div>

        <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0; letter-spacing: -1px; color: #fff;">HARDWARE <span style="color:var(--accent); text-shadow: 0 0 20px rgba(0,242,254,0.4);">VAULT</span></h1>
        <p style="color: #888; font-size: 1.1rem; margin-top: 5px; margin-bottom: 30px;">Upgrade your rig with top-tier components.</p>
        
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid #00e676; color: #00e676; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; font-family: 'Inter', sans-serif;">
                <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div style="padding: 80px 20px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; background: rgba(0,0,0,0.3);">
                <i class="fa-solid fa-satellite-dish" style="font-size: 4rem; color: #475569; margin-bottom: 20px;"></i>
                <h3 style="color: #fff; font-size: 1.5rem; margin-bottom: 10px;">NO SIGNAL DETECTED</h3>
                <p style="color: #64748b;">We couldn't find any components matching your parameters. Adjust your filters or search query.</p>
                <a href="components.php" class="btn-action btn-change" style="width: auto; margin-top: 25px;"><i class="fas fa-sync-alt"></i> Clear All Filters</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $item): ?> 
                <div class="slot-card">
                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" style="text-decoration: none; display: block; flex: 1;">
                        
                        <div class="product-img-box">
                            <?php 
                                $raw_img = !empty($item['image_url']) ? trim($item['image_url']) : '';
                                if (strpos($raw_img, 'http') === 0 || strpos($raw_img, 'data:image') === 0) {
                                    $img_src = $raw_img;
                                } elseif (empty($raw_img) || $raw_img == 'image/placeholder_pc.png') {
                                    $img_src = 'image/placeholder.jpg';
                                } else {
                                    $img_src = 'image/' . basename($raw_img); 
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 onerror="this.src='image/placeholder.jpg';" class="product-img">
                        </div>
                        
                        <h3 class="product-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                    </a>

                    <div style="display: flex; gap: 10px; margin-top: auto;">
                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="btn-action btn-change" style="flex: 1; padding: 12px 10px;">
                            <i class="fa-solid fa-circle-info"></i> Details
                        </a>
                        
                        <form method="POST" action="add_to_cart.php" style="flex: 1.2; margin: 0;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="quantity" value="1"> 
                            <button type="submit" class="btn-action btn-select" style="width: 100%; padding: 12px 10px;">
                                <i class="fa-solid fa-cart-plus"></i> Cart
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // === 1. Custom Select Matrix Logic ===
    const selectWrapper = document.getElementById('customSelectWrapper');
    const selectBtn = document.getElementById('customSelect');
    const optionsPanel = document.getElementById('customOptions');
    const options = document.querySelectorAll('.custom-option');
    const sortInput = document.getElementById('sortInput');
    const selectText = document.getElementById('customSelectText');
    const selectIcon = document.getElementById('customSelectIcon');

    // Toggle dropdown
    selectBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        optionsPanel.classList.toggle('open');
        selectIcon.style.transform = optionsPanel.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0)';
    });

    // Select option
    options.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all
            options.forEach(opt => opt.classList.remove('selected'));
            // Add to clicked
            this.classList.add('selected');
            
            // Update UI and Input
            selectText.innerText = this.innerText;
            sortInput.value = this.getAttribute('data-value');
            
            // Close dropdown
            optionsPanel.classList.remove('open');
            selectIcon.style.transform = 'rotate(0)';
            
            // Auto submit form
            document.getElementById('filterForm').submit();
        });
        
        // Init state
        if(option.getAttribute('data-value') === sortInput.value) {
            option.classList.add('selected');
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!selectWrapper.contains(e.target)) {
            optionsPanel.classList.remove('open');
            selectIcon.style.transform = 'rotate(0)';
        }
    });

    // === 2. Price Slider Logic ===
    const rangeInputs = document.querySelectorAll(".range-inputs input");
    const priceInputs = document.querySelectorAll(".price-input-group input");
    const trackFill = document.querySelector(".slider-track-fill");
    
    const maxRange = 10000; 
    const priceGap = 100;

    function updateTrackFill(minVal, maxVal) {
        trackFill.style.left = (minVal / maxRange) * 100 + "%";
        trackFill.style.right = 100 - (maxVal / maxRange) * 100 + "%";
    }

    updateTrackFill(parseInt(rangeInputs[0].value), parseInt(rangeInputs[1].value));

    rangeInputs.forEach(input => {
        input.addEventListener("input", e => {
            let minVal = parseInt(rangeInputs[0].value);
            let maxVal = parseInt(rangeInputs[1].value);

            if ((maxVal - minVal) < priceGap) {
                if (e.target.className === "range-min") {
                    rangeInputs[0].value = maxVal - priceGap;
                } else {
                    rangeInputs[1].value = minVal + priceGap;
                }
            } else {
                priceInputs[0].value = minVal;
                priceInputs[1].value = maxVal;
                updateTrackFill(minVal, maxVal);
            }
        });
    });

    priceInputs.forEach(input => {
        input.addEventListener("input", e => {
            if(priceInputs[0].value === "" || priceInputs[1].value === "") return;

            let minPrice = parseInt(priceInputs[0].value);
            let maxPrice = parseInt(priceInputs[1].value);

            if ((maxPrice - minPrice >= priceGap) && maxPrice <= maxRange && minPrice >= 0) {
                if (e.target.className === "input-min") {
                    rangeInputs[0].value = minPrice;
                } else {
                    rangeInputs[1].value = maxPrice;
                }
                updateTrackFill(minPrice, maxPrice);
            }
        });
        
        input.addEventListener("blur", e => {
            let minPrice = parseInt(priceInputs[0].value) || 0;
            let maxPrice = parseInt(priceInputs[1].value) || maxRange;
            
            if (minPrice < 0) minPrice = 0;
            if (maxPrice > maxRange) maxPrice = maxRange;
            if (minPrice > maxPrice - priceGap) minPrice = maxPrice - priceGap;

            priceInputs[0].value = minPrice;
            priceInputs[1].value = maxPrice;
            rangeInputs[0].value = minPrice;
            rangeInputs[1].value = maxPrice;
            updateTrackFill(minPrice, maxPrice);
        });
    });
});
</script>
</body>
</html>