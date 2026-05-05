<?php
session_start();
require_once 'config.php';


$categories = [];
$cat_query = "SELECT * FROM categories ORDER BY category_name ASC";
$cat_result = $conn->query($cat_query);
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}


$active_category_id = isset($_GET['category']) ? intval($_GET['category']) : 0; 
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : 0;


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


$where_clause = implode(" AND ", $conditions);
$prod_query = "SELECT * FROM products WHERE $where_clause ORDER BY product_name ASC";


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
    <title>Components Shop - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .shop-layout {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            align-items: flex-start;
        }

        .shop-sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            position: sticky;
            top: 90px; 
        }
        .shop-sidebar h3 {
            color: var(--text-main);
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1rem;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        .cat-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: 0.3s;
        }
        .cat-link:hover {
            background: rgba(0, 243, 255, 0.05);
            color: var(--accent-blue);
        }
        .cat-link.active {
            background: var(--accent-blue);
            color: #000;
            font-weight: bold;
        }

        .shop-main {
            flex: 1;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: left; 
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
            border-color: rgba(0, 243, 255, 0.3);
        }
        
        /* 圖片專屬容器 */
        .product-img-box {
            width: 100%;
            height: 180px;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .product-img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        .product-card:hover .product-img {
            transform: scale(1.05); 
        }

        .product-cat-tag {
            color: var(--accent-blue);
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .product-title {
            color: var(--text-main);
            font-size: 1.1rem;
            margin-bottom: 10px;
            line-height: 1.4;
            font-weight: bold;
            flex-grow: 1; 
        }
        
        .product-price {
            color: #00ff66; 
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }
        
        .btn-add-cart {
            width: 100%;
            padding: 10px;
            background: var(--accent-blue);
            color: #000;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-add-cart:hover {
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.5);
        }
        .price-filter-widget {
            background: transparent;
            padding: 10px 0;
        }
        .price-input-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        .price-field {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .price-field label {
            font-size: 0.75rem;
            color: #888;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .price-field input {
            width: 100%;
            padding: 8px 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: 0.3s;
            text-align: center;
        }
        .price-field input:focus {
            border-color: #34d399; 
        }
        .price-field input[type="number"]::-webkit-inner-spin-button,
        .price-field input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .slider-container {
            position: relative;
            height: 5px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .slider-container .slider-track-fill {
            position: absolute;
            height: 100%;
            left: 0%; 
            right: 0%;
            background: #34d399; 
            border-radius: 5px;
            pointer-events: none;
        }
        .range-inputs {
            position: relative;
        }
        .range-inputs input {
            position: absolute;
            top: -10px;
            height: 5px;
            width: 100%;
            background: none;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
        }
        .range-inputs input::-webkit-slider-thumb {
            height: 18px;
            width: 18px;
            border-radius: 50%;
            background: #fff;
            pointer-events: auto;
            -webkit-appearance: none;
            box-shadow: 0 0 6px rgba(0,0,0,0.5);
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid rgba(0, 255, 0, 0.3); color: #00ff00; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
        <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid rgba(255, 0, 0, 0.3); color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
    </div>
<?php endif; ?>

<main class="main-container">
    <div class="auth-title" style="text-align: left; margin-bottom: 10px;">
        <h2><i class="fa-solid fa-microchip"></i> Components Shop</h2>
        <p class="specs">Upgrade your rig with top-tier hardware.</p>
    </div>

    <div class="shop-layout">
        <aside class="shop-sidebar">
            <h3>Categories</h3>
            
            <?php
            $price_params = "";
            if ($min_price > 0) $price_params .= "&min_price=" . $min_price;
            if ($max_price > 0) $price_params .= "&max_price=" . $max_price;
            ?>

            <a href="components.php?category=0<?php echo $price_params; ?>" class="cat-link <?php echo ($active_category_id == 0) ? 'active' : ''; ?>">
                <span><i class="fa-solid fa-border-all" style="margin-right: 8px;"></i> All Components</span>
            </a>
            
            <?php foreach ($categories as $cat): ?>
                <a href="components.php?category=<?php echo $cat['category_id'] . $price_params; ?>" 
                   class="cat-link <?php echo ($active_category_id == $cat['category_id']) ? 'active' : ''; ?>">
                    <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                </a>
            <?php endforeach; ?>

            <h3 style="margin-top: 30px;"><i class="fa-solid fa-filter" style="font-size: 0.9rem;"></i> Filter by Price</h3>
            <form action="components.php" method="GET" style="padding-top: 10px;">
                
                <?php if ($active_category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $active_category_id; ?>">
                <?php endif; ?>

                <div class="price-filter-widget">
                    <div class="price-input-group">
                        <div class="price-field">
                            <label>Min (RM)</label>
                            <input type="number" class="input-min" name="min_price" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>">
                        </div>
                        <div style="color: #888;">—</div>
                        <div class="price-field">
                            <label>Max (RM)</label>
                            <input type="number" class="input-max" name="max_price" value="<?php echo $max_price > 0 ? $max_price : '10000'; ?>">
                        </div>
                    </div>

                    <div class="slider-container">
                        <div class="slider-track-fill"></div>
                    </div>
                    <div class="range-inputs">
                        <input type="range" class="range-min" min="0" max="10000" value="<?php echo $min_price > 0 ? $min_price : '0'; ?>" step="10">
                        <input type="range" class="range-max" min="0" max="10000" value="<?php echo $max_price > 0 ? $max_price : '10000'; ?>" step="10">
                    </div>
                </div>

                <button type="submit" style="width: 100%; padding: 10px; background: var(--accent-blue); color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 15px;" onmouseover="this.style.boxShadow='0 0 15px rgba(0,243,255,0.4)'" onmouseout="this.style.boxShadow='none'">
                    Apply Filter
                </button>

                <?php if ($min_price > 0 || $max_price > 0): ?>
                    <a href="components.php<?php echo $active_category_id > 0 ? '?category='.$active_category_id : ''; ?>" style="display: block; text-align: center; margin-top: 15px; color: #ff4d4d; font-size: 0.85rem; text-decoration: none; transition: 0.3s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        <i class="fa-solid fa-xmark"></i> Clear Price Filter
                    </a>
                <?php endif; ?>
            </form>
        </aside>

        <main class="shop-main">
            <?php if (empty($products)): ?>
                <div style="padding: 50px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px;">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                    <h3 style="color: var(--text-main);">No products found</h3>
                    <p style="color: var(--text-muted);">We are restocking soon. Please check another category.</p>
                </div>
            <?php else: ?>
<div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="product-card">
                            
                            <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" style="text-decoration: none; display: flex; flex-direction: column; flex-grow: 1;">
                                
                                <div class="product-img-box">
                                    <img src="<?php echo htmlspecialchars($p['image_url'] ? $p['image_url'] : 'image/placeholder.png'); ?>" alt="Product" class="product-img">
                                </div>
                                
                                <div class="product-cat-tag">COMPONENT HARDWARE</div>
                                
                                <h4 class="product-title"><?php echo htmlspecialchars($p['product_name']); ?></h4>
                            </a>
                            
                            <div>
                                <div class="product-price">RM <?php echo number_format($p['price'], 2); ?></div>
                                
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-add-cart">
                                        Buy Now
                                    </button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

    </div>
</main>


<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
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