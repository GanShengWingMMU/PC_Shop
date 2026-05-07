<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: components.php");
    exit();
}

$product_id = intval($_GET['id']);

$prod_query = "SELECT p.*, c.category_name 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.category_id 
               WHERE p.product_id = ?";
$stmt = $conn->prepare($prod_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: components.php");
    exit();
}
$product = $result->fetch_assoc();
$stmt->close();


$recommended_products = [];
$algo_query = "
    SELECT 
        od2.product_id, 
        p.product_name, 
        p.price, 
        p.image_url,
        COUNT(od2.product_id) AS purchase_count
    FROM order_details od1
    JOIN order_details od2 ON od1.order_id = od2.order_id AND od1.product_id != od2.product_id
    JOIN products p ON od2.product_id = p.product_id
    WHERE od1.product_id = ? AND p.stock_quantity > 0
    GROUP BY od2.product_id
    ORDER BY purchase_count DESC
    LIMIT 4
";
if ($stmt_algo = $conn->prepare($algo_query)) {
    $stmt_algo->bind_param("i", $product_id);
    $stmt_algo->execute();
    $result_algo = $stmt_algo->get_result();
    while ($row = $result_algo->fetch_assoc()) {
        $recommended_products[] = $row;
    }
    $stmt_algo->close();
}

$reviews = [];
$total_rating = 0;
$avg_rating = 0;

$review_query = "
    SELECT r.*, c.username 
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.customer_id 
    WHERE r.product_id = ? 
    ORDER BY r.review_id DESC
";
if ($stmt_rev = $conn->prepare($review_query)) {
    $stmt_rev->bind_param("i", $product_id);
    $stmt_rev->execute();
    $res_rev = $stmt_rev->get_result();
    while ($rev = $res_rev->fetch_assoc()) {
        $reviews[] = $rev;
        $total_rating += $rev['rating'];
    }
    $stmt_rev->close();
}

if (count($reviews) > 0) {
    $avg_rating = round($total_rating / count($reviews), 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --panel-bg: linear-gradient(145deg, #1a202c, #151a25);
            --neon-blue-glow: 0 0 15px rgba(0, 243, 255, 0.3);
            --gold-accent: #ffd700;
        }

        .main-content-wrapper {
            padding: 40px 0;
        }

        .detail-card {
            display: flex;
            gap: 50px;
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            margin-bottom: 60px;
            position: relative;
            overflow: hidden;
        }
        
     
        .detail-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-blue), transparent);
        }

    
        .image-gallery {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .main-image-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid rgba(0, 243, 255, 0.2);
            box-shadow: var(--neon-blue-glow);
            height: 450px;
        }
        .main-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }
        .main-image-box:hover .main-image {
            transform: scale(1.05);
        }

      
        .info-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .category-tag {
            color: var(--accent-blue);
            background: rgba(0, 243, 255, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .stock-badge {
            font-size: 0.85rem;
            font-weight: bold;
        }
        .stock-badge.in { color: #00ff00; }
        .stock-badge.out { color: #ff4d4d; }

        .product-title {
            color: var(--text-main);
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.1;
            font-weight: 800;
        }
        
        .price-row {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }
        .current-price {
            font-size: 3rem;
            color: var(--gold-accent);
            font-weight: 900;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        .currency { font-size: 1.5rem; margin-right: 5px; }

                .specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .specs-table tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .specs-table td { padding: 12px 0; }
        .specs-table td.key { color: var(--text-main); font-weight: bold; width: 40%; }

                .purchase-zone {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
        }
        .qty-selector {
            display: flex;
            align-items: center;
            gap: 0;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            width: 140px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .qty-btn {
            background: none; border: none;
            color: var(--text-main);
            padding: 12px; cursor: pointer;
            font-size: 1.2rem; transition: 0.3s;
        }
        .qty-btn:hover { background: rgba(0, 243, 255, 0.1); color: var(--accent-blue); }
        .qty-input {
            width: 50px; text-align: center;
            background: none; border: none;
            color: var(--text-main); font-size: 1.2rem; font-weight: bold;
            -moz-appearance: textfield;
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .action-buttons {
            display: flex; gap: 15px; margin-top: 20px;
        }
        .btn-add-to-cart {
            flex: 1; padding: 15px;
            background: var(--accent-blue);
            color: #000; border: none; border-radius: 6px;
            font-size: 1.2rem; font-weight: bold;
            cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-add-to-cart:hover:not(:disabled) {
            box-shadow: 0 0 30px rgba(0, 243, 255, 0.6);
            transform: translateY(-3px);
        }
        .btn-add-to-cart:disabled {
            background: #444; color: #888; cursor: not-allowed;
        }

        .btn-secondary-action {
            background: rgba(255,255,255,0.05);
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 15px; border-radius: 6px;
            cursor: pointer; transition: 0.3s;
        }
        .btn-secondary-action:hover {
            background: rgba(255,255,255,0.1); color: var(--text-main);
        }

        .trust-badges {
            display: flex; justify-content: space-between;
            margin-top: 25px; padding: 15px;
            background: rgba(0,0,0,0.2); border-radius: 8px;
            font-size: 0.8rem; color: var(--text-muted);
        }
        .badge-item { display: flex; align-items: center; gap: 8px; }
        .badge-item i { color: var(--accent-blue); }

.recommendation-section h3 {
            font-size: 1.8rem; color: var(--text-main); margin-bottom: 25px;
        }
        .rec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .rec-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: 0.3s;
            display: flex; flex-direction: column; height: 100%;
            text-decoration: none;
        }
        .rec-card:hover {
            border-color: rgba(0, 243, 255, 0.4);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .rec-img-box {
            width: 100%; height: 160px;
            display: flex; justify-content: center; align-items: center;
            margin-bottom: 15px;
            background: rgba(0,0,0,0.2); border-radius: 8px;
        }
        .rec-img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .rec-img[src=""], .rec-img:not([src]) {
            opacity: 0;
        }
        .rec-img-box:has(.rec-img[src=""]), .rec-img-box:has(.rec-img:not([src])) {
            position: relative;
        }
        .rec-img-box:has(.rec-img[src=""])::after, .rec-img-box:has(.rec-img:not([src]))::after {
            content: '\f2db'; 
            font-family: 'Font Awesome 6 Free'; font-weight: 900;
            font-size: 3rem; color: #333;
            position: absolute;
        }

        .rec-title {
            color: var(--text-main); font-size: 1.1rem;
            margin-bottom: 10px; line-height: 1.3; flex-grow: 1;
        }
        .rec-price {
            color: var(--gold-accent); font-weight: bold;
            font-size: 1.2rem; margin-bottom: 15px;
        }
        .algo-tag {
            background: rgba(255, 204, 0, 0.1);
            color: #ffcc00; border: 1px solid rgba(255, 204, 0, 0.3);
            padding: 5px; border-radius: 4px; font-size: 0.75rem;
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

<main class="main-container main-content-wrapper">
    
    <a href="components.php" style="color: var(--text-muted); text-decoration: none; margin-bottom: 30px; display: inline-block; font-size: 0.9rem;" onmouseover="this.style.color='var(--accent-blue)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-chevron-left" style="font-size: 0.8rem;"></i> Back to All Components
    </a>

    <div class="detail-card">
        
        <div class="image-gallery">
            <div class="main-image-box">
                <img src="<?php echo htmlspecialchars($product['image_url'] ? $product['image_url'] : 'image/placeholder_pc.png'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="main-image" id="mainProductImage">
            </div>
            </div>
        
        <div class="info-panel">
            <div class="product-meta">
                <span class="category-tag"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($product['category_name'] ?? 'Component'); ?></span>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="stock-badge in"><i class="fa-solid fa-circle" style="font-size: 0.6rem;"></i> In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                <?php else: ?>
                    <span class="stock-badge out"><i class="fa-solid fa-circle" style="font-size: 0.6rem;"></i> Out of Stock</span>
                <?php endif; ?>
            </div>

            <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
            
            <div class="price-row">
                <span class="currency">RM</span>
                <span class="current-price" id="unitPrice"><?php echo number_format($product['price'], 2, '.', ''); ?></span>
            </div>
            
            <table class="specs-table">
                <?php 
                    $desc = $product['description'];
                    if (empty($desc)) {
                        echo "<tr><td colspan='2'>No detailed specifications available.</td></tr>";
                    } elseif (strpos($desc, '|') !== false) {
                        $specs = explode('|', $desc);
                        foreach ($specs as $spec) {
                            if (strpos($spec, ':') !== false) {
                                list($key, $value) = explode(':', $spec);
                                echo "<tr><td class='key'>".htmlspecialchars(trim($key))."</td><td>".htmlspecialchars(trim($value))."</td></tr>";
                            }
                        }
                    } else {
                        echo "<tr><td class='key'>Description</td><td>".nl2br(htmlspecialchars($desc))."</td></tr>";
                    }
                ?>
            </table>

            <form action="add_to_cart.php" method="POST" class="purchase-zone">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <label style="color: var(--text-main); font-weight: bold;">Quantity:</label>
                    
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" id="qtyDesc"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="qty-input" id="qtyInput" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <button type="button" class="qty-btn" id="qtyInsc"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn-add-to-cart" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-cart-shopping"></i> 
                        <?php echo $product['stock_quantity'] > 0 ? 'Add to Cart' : 'Currently Unavailable'; ?>
                    </button>
                    
                    <button type="button" class="btn-secondary-action" title="Add to Wishlist">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    
                    <button type="button" class="btn-secondary-action" title="Share">
                        <i class="fa-solid fa-share-nodes"></i>
                    </button>
                </div>

                <div class="trust-badges">
                    <div class="badge-item"><i class="fa-solid fa-shield-halved"></i> 1 Year Warranty</div>
                    <div class="badge-item"><i class="fa-solid fa-truck"></i> Free Shipping</div>
                    <div class="badge-item"><i class="fa-solid fa-lock"></i> Secure Payment</div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($recommended_products)): ?>
    <div class="recommendation-section">
        <h3><i class="fa-solid fa-layer-group" style="color: var(--accent-blue);"></i> Frequently Bought Together</h3>
        
        <div class="rec-grid">
            <?php foreach ($recommended_products as $rec): ?>
                <a href="product_detail.php?id=<?php echo $rec['product_id']; ?>" class="rec-card">
                    <div class="rec-img-box">
                        <img src="<?php echo htmlspecialchars($rec['image_url']); ?>" alt="Recommended" class="rec-img">
                    </div>
                    
                    <h4 class="rec-title"><?php echo htmlspecialchars($rec['product_name']); ?></h4>
                    
                    <div class="rec-price">RM <?php echo number_format($rec['price'], 2); ?></div>
                    
                    <div style="margin-top: auto;">
                        <span class="algo-tag"><i class="fa-solid fa-chart-line"></i> Bought together <?php echo $rec['purchase_count']; ?> times</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="reviews-section" style="background: var(--panel-bg); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 40px; margin-bottom: 60px; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
        <h3 style="font-size: 1.8rem; color: var(--text-main); margin-bottom: 25px;"><i class="fa-solid fa-star" style="color: #ffd700;"></i> Product Ratings</h3>
        
        <?php if (empty($reviews)): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 30px 0; font-size: 1.1rem;">
                <i class="fa-regular fa-comment-dots" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                No reviews yet. Be the first to review this product!
            </p>
        <?php else: ?>
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; background: rgba(0,0,0,0.2); padding: 25px; border-radius: 8px; border: 1px solid rgba(255,215,0,0.2);">
                <div style="color: #ffd700; font-size: 3.5rem; font-weight: bold; line-height: 1; text-shadow: 0 0 10px rgba(255,215,0,0.3);">
                    <?php echo number_format($avg_rating, 1); ?> <span style="font-size: 1.2rem; color: #888; font-weight: normal;">out of 5</span>
                </div>
                <div>
                    <?php 
                    for($i=1; $i<=5; $i++) {
                        echo $i <= round($avg_rating) ? '<i class="fa-solid fa-star" style="color: #ffd700; font-size: 1.5rem; margin-right: 3px;"></i>' : '<i class="fa-regular fa-star" style="color: #ffd700; font-size: 1.5rem; margin-right: 3px;"></i>';
                    }
                    ?>
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;"><?php echo count($reviews); ?> Ratings</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px 0;">
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <div style="width: 40px; height: 40px; background: rgba(0, 243, 255, 0.1); color: var(--accent-blue); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 1.2rem;">
                                <?php echo strtoupper(substr($rev['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($rev['username'] ?? 'Anonymous User'); ?></strong>
                                </div>
                                <div style="margin-bottom: 8px;">
                                    <?php 
                                    for($i=1; $i<=5; $i++) {
                                        echo $i <= $rev['rating'] ? '<i class="fa-solid fa-star" style="color: #ffd700; font-size: 0.8rem;"></i>' : '<i class="fa-regular fa-star" style="color: #ffd700; font-size: 0.8rem;"></i>';
                                    }
                                    ?>
                                </div>
                                <p style="color: #ccc; line-height: 1.6; margin: 0; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qtyInput = document.getElementById('qtyInput');
        const btnDesc = document.getElementById('qtyDesc');
        const btnInsc = document.getElementById('qtyInsc');
        
        const maxStock = parseInt(qtyInput.getAttribute('max'));
        const unitPrice = parseFloat(document.getElementById('unitPrice').innerText);

        if(maxStock <= 0) return;

        btnDesc.addEventListener('click', function() {
            let currentQty = parseInt(qtyInput.value);
            if(currentQty > 1) {
                qtyInput.value = currentQty - 1;
            }
        });

        btnInsc.addEventListener('click', function() {
            let currentQty = parseInt(qtyInput.value);
            if(currentQty < maxStock) {
                qtyInput.value = currentQty + 1;
            }
        });

        qtyInput.addEventListener('input', function() {
            let currentQty = parseInt(this.value);
            if(isNaN(currentQty) || currentQty < 1) {
                this.value = 1;
            } else if (currentQty > maxStock) {
                this.value = maxStock;
            }
        });
    });
</script>

</body>
</html>