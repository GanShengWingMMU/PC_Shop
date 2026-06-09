<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: components.php");
    exit();
}

$product_id = intval($_GET['id']);

// 1. 抓取主商品
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

// 2. 抓取“一起购买”推荐算法
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

// 3. 抓取评论 (包含生态徽章系统)
$reviews = [];
$total_rating = 0;
$avg_rating = 0;

$review_query = "
    SELECT r.*, c.username, c.reward_coins, c.membership_tier 
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

// 生态等级徽章引擎
function getRankBadge($coins, $tier = 'Basic') {
    if ($tier === 'VIP') return '<span class="rank-badge elite" title="Elite Subscriber"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 1000) return '<span class="rank-badge elite" title="Elite Architect"><i class="fas fa-crown"></i> Elite</span>';
    if ($coins >= 500) return '<span class="rank-badge pro" title="Pro Builder"><i class="fas fa-star"></i> Pro</span>';
    return '<span class="rank-badge novice" title="Enthusiast">Enthusiast</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - GridCitY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 极简深色主题规范 */
        :root {
            --bg-base: #0b0f17;
            --bg-panel: #111827;
            --border-light: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #00f2fe;
            --accent-hover: #ffffff;
            --gold: #f59e0b;
        }

        body { background: var(--bg-base); font-family: 'Inter', sans-serif; color: var(--text-main); line-height: 1.6; }
        .page-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px 80px 20px; }

        /* 🌟 细节1：专业面包屑导航 */
        .breadcrumb { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 30px; font-weight: 500; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb span { color: var(--text-main); }
        .breadcrumb i { font-size: 0.7rem; margin: 0 8px; opacity: 0.5; }

        /* 布局：左图右文 */
        .product-hero { display: grid; grid-template-columns: 1fr 1.1fr; gap: 50px; margin-bottom: 60px; }

        /* 精简：去边框的图库 */
        .image-gallery { background: var(--bg-panel); border-radius: 12px; padding: 40px; display: flex; align-items: center; justify-content: center; position: relative; aspect-ratio: 1; cursor: zoom-in; }
        .main-image { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s ease; }
        .image-gallery:hover .main-image { transform: scale(1.05); }

        /* 产品信息区 */
        .product-info { display: flex; flex-direction: column; }
        
        .title-group { border-bottom: 1px solid var(--border-light); padding-bottom: 24px; margin-bottom: 24px; }
        .brand-cat { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 12px; display: block; }
        .prod-title { font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin: 0 0 16px 0; color: var(--text-main); letter-spacing: -0.5px; }
        
        .stock-status { display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-light); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-in { background: #10b981; box-shadow: 0 0 8px #10b981; }
        .dot-out { background: #ef4444; box-shadow: 0 0 8px #ef4444; }

        .price-block { margin-bottom: 30px; }
        .price-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; display: block; }
        .price-val { font-size: 2.5rem; font-weight: 800; color: var(--text-main); font-family: 'JetBrains Mono', monospace; letter-spacing: -1px; }
        .currency { font-size: 1.2rem; color: var(--text-muted); font-weight: 500; vertical-align: super; }

        /* 购物车操作组 */
        .action-group { display: flex; gap: 16px; margin-bottom: 30px; }
        .qty-box { display: flex; align-items: center; background: var(--bg-panel); border: 1px solid var(--border-light); border-radius: 8px; overflow: hidden; height: 54px; }
        .qty-btn { background: transparent; border: none; color: var(--text-main); width: 45px; height: 100%; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .qty-btn:hover { background: rgba(255,255,255,0.05); color: var(--accent); }
        .qty-input { width: 50px; text-align: center; background: transparent; border: none; color: var(--text-main); font-size: 1.1rem; font-weight: 700; -moz-appearance: textfield; }
        .qty-input::-webkit-outer-spin-button, .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        
        .btn-add { flex: 1; background: var(--text-main); color: var(--bg-base); border: none; border-radius: 8px; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px; height: 54px; }
        .btn-add:hover:not(:disabled) { background: var(--accent); transform: translateY(-2px); }
        .btn-add:disabled { background: #334155; color: #94a3b8; cursor: not-allowed; }

        /* 🌟 细节2：高定信任面板 */
        .trust-panel { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; border-top: 1px solid var(--border-light); padding-top: 24px; }
        .trust-item { display: flex; flex-direction: column; gap: 6px; }
        .trust-item i { font-size: 1.2rem; color: var(--accent); }
        .trust-item span { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; line-height: 1.3; }

        /* 🌟 细节3：高定斑马线规格表 */
        .specs-section { margin-bottom: 60px; }
        .section-header { font-size: 1.4rem; font-weight: 800; margin-bottom: 24px; color: var(--text-main); padding-bottom: 12px; border-bottom: 1px solid var(--border-light); }
        .specs-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-light); }
        .specs-table tr:nth-child(even) { background: rgba(255,255,255,0.02); }
        .specs-table td { padding: 16px 20px; color: var(--text-muted); border-bottom: 1px solid var(--border-light); line-height: 1.5; }
        .specs-table td:last-child { border-bottom: none; }
        .specs-table td.key { color: var(--text-main); font-weight: 600; width: 30%; background: var(--bg-panel); }

        /* 推荐商品区 (精简卡片) */
        .rec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-bottom: 60px; }
        .rec-card { background: transparent; border: 1px solid var(--border-light); border-radius: 12px; padding: 20px; transition: all 0.2s; display: flex; flex-direction: column; text-decoration: none; }
        .rec-card:hover { border-color: var(--text-muted); background: var(--bg-panel); }
        .rec-img-box { height: 140px; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; }
        .rec-img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .rec-title { color: var(--text-main); font-size: 0.95rem; margin-bottom: 8px; line-height: 1.4; font-weight: 600; }
        .rec-price { color: var(--text-main); font-weight: 700; font-family: 'JetBrains Mono', monospace; }
        .rec-meta { margin-top: auto; padding-top: 15px; font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

        /* 评论区排版优化 */
        .reviews-section { margin-bottom: 40px; }
        .rating-overview { display: flex; align-items: center; gap: 24px; margin-bottom: 30px; background: var(--bg-panel); padding: 24px 30px; border-radius: 12px; border: 1px solid var(--border-light); }
        .rating-score { font-size: 3rem; font-weight: 800; color: var(--text-main); line-height: 1; }
        .rating-stars { color: var(--gold); font-size: 1.1rem; margin-bottom: 4px; }
        .rating-count { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        .review-list { display: flex; flex-direction: column; gap: 20px; }
        .review-card { padding: 24px 0; border-bottom: 1px solid var(--border-light); display: flex; gap: 20px; align-items: flex-start; }
        .review-avatar { width: 42px; height: 42px; background: var(--border-light); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 700; color: var(--text-main); font-size: 1.1rem; flex-shrink: 0; }
        .review-body { flex: 1; }
        .review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap; }
        .reviewer-name { font-weight: 600; color: var(--text-main); font-size: 1rem; }
        .review-date { font-size: 0.8rem; color: var(--text-muted); margin-left: auto; }
        .review-content { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-top: 10px; }

        /* 等级徽章 */
        .rank-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; letter-spacing: 0.5px; text-transform: uppercase;}
        .rank-badge.elite { background: rgba(245, 158, 11, 0.1); color: var(--gold); border: 1px solid rgba(245, 158, 11, 0.3); }
        .rank-badge.pro { background: rgba(168, 85, 247, 0.1); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .rank-badge.novice { background: var(--border-light); color: var(--text-muted); }

        /* Lightbox */
        #lightbox { display: none; position: fixed; inset: 0; background: rgba(11, 15, 23, 0.95); z-index: 9999; justify-content: center; align-items: center; cursor: zoom-out;}
        #lightbox img { max-width: 90vw; max-height: 90vh; object-fit: contain; }
        .lb-close { position: absolute; top: 30px; right: 40px; font-size: 2rem; color: var(--text-muted); cursor: pointer; transition: 0.2s;}
        .lb-close:hover { color: #fff; }

        @media (max-width: 900px) {
            .product-hero { grid-template-columns: 1fr; gap: 30px; }
            .image-gallery { padding: 20px; }
            .trust-panel { grid-template-columns: 1fr; gap: 20px; }
            .specs-table td { display: block; width: 100%; border-bottom: none; padding: 10px 16px; }
            .specs-table td.key { padding-top: 16px; padding-bottom: 4px; background: transparent; }
            .specs-table tr { border-bottom: 1px solid var(--border-light); }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 16px; border-radius: 8px; margin: 20px auto; max-width: 1200px; text-align: center; font-weight: 600;">
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_msg'])): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 16px; border-radius: 8px; margin: 20px auto; max-width: 1200px; text-align: center; font-weight: 600;">
        <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
    </div>
<?php endif; ?>

<main class="page-wrapper">
    
    <div class="breadcrumb">
        <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i> 
        <a href="components.php">Components</a> <i class="fas fa-chevron-right"></i> 
        <span><?php echo htmlspecialchars($product['category_name'] ?? 'Hardware'); ?></span>
    </div>

    <div class="product-hero">
        <div class="image-gallery" onclick="openLightbox()">
            <?php 
            $raw_img = $product['image_url'] ?? '';
            if (empty($raw_img) || strpos($raw_img, 'placeholder') !== false) {
                $img_src = 'image/placeholder.jpg';
            } elseif (strpos($raw_img, 'data:image') === 0 || strpos($raw_img, 'http') === 0) {
                $img_src = $raw_img;
            } else {
                $img_src = (strpos($raw_img, 'image/') === 0) ? $raw_img : 'image/' . basename($raw_img);
            }
            ?>
            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="main-image" id="mainProductImage" onerror="this.src='image/placeholder.jpg';">
        </div>
        
        <div class="product-info">
            <div class="title-group">
                <span class="brand-cat"><?php echo htmlspecialchars($product['category_name'] ?? 'Hardware'); ?></span>
                <h1 class="prod-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="stock-status">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="status-dot dot-in"></div> In Stock (<?php echo $product['stock_quantity']; ?> units)
                    <?php else: ?>
                        <div class="status-dot dot-out"></div> Out of Stock
                    <?php endif; ?>
                </div>
            </div>

            <div class="price-block">
                <span class="price-label">Retail Price</span>
                <div><span class="currency">RM</span> <span class="price-val"><?php echo number_format($product['price'], 2, '.', ''); ?></span></div>
            </div>

            <form action="add_to_cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div class="action-group">
                    <div class="qty-box">
                        <button type="button" class="qty-btn" id="qtyDesc"><i class="fas fa-minus"></i></button>
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="qty-input" id="qtyInput" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <button type="button" class="qty-btn" id="qtyInsc"><i class="fas fa-plus"></i></button>
                    </div>
                    
                    <button type="submit" class="btn-add" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-shopping-cart"></i> 
                        <?php echo $product['stock_quantity'] > 0 ? 'Add to Cart' : 'Currently Unavailable'; ?>
                    </button>
                </div>
            </form>

            <div class="trust-panel">
                <div class="trust-item">
                    <i class="fas fa-shield-alt"></i>
                    <span><strong>1 Year Warranty</strong><br>Official manufacturer coverage</span>
                </div>
                <div class="trust-item">
                    <i class="fas fa-shipping-fast"></i>
                    <span><strong>Express Delivery</strong><br>Ships within 24 hours</span>
                </div>
                <div class="trust-item">
                    <i class="fas fa-undo"></i>
                    <span><strong>Easy Returns</strong><br>7-day hassle-free policy</span>
                </div>
            </div>
        </div>
    </div>

    <div class="specs-section">
        <h2 class="section-header">Technical Specifications</h2>
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
                    echo "<tr><td class='key'>Overview</td><td>".nl2br(htmlspecialchars($desc))."</td></tr>";
                }
            ?>
        </table>
    </div>

    <?php if (!empty($recommended_products)): ?>
    <div class="specs-section">
        <h2 class="section-header">Frequently Bought Together</h2>
        <div class="rec-grid">
            <?php foreach ($recommended_products as $rec): ?>
                <a href="product_detail.php?id=<?php echo $rec['product_id']; ?>" class="rec-card">
                    <div class="rec-img-box">
                        <?php 
                            $raw_rec_img = $rec['image_url'] ?? '';
                            if (empty($raw_rec_img) || strpos($raw_rec_img, 'placeholder') !== false) {
                                $rec_img_src = 'image/placeholder.jpg';
                            } elseif (strpos($raw_rec_img, 'data:image') === 0 || strpos($raw_rec_img, 'http') === 0) {
                                $rec_img_src = $raw_rec_img;
                            } else {
                                $rec_img_src = (strpos($raw_rec_img, 'image/') === 0) ? $raw_rec_img : 'image/' . basename($raw_rec_img);
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($rec_img_src); ?>" alt="<?php echo htmlspecialchars($rec['product_name']); ?>" class="rec-img" onerror="this.src='image/placeholder.jpg';">
                    </div>
                    <div class="rec-title"><?php echo htmlspecialchars($rec['product_name']); ?></div>
                    <div class="rec-price">RM <?php echo number_format($rec['price'], 2); ?></div>
                    <div class="rec-meta"><i class="fas fa-link"></i> Co-purchased <?php echo $rec['purchase_count']; ?> times</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="reviews-section">
        <h2 class="section-header">Customer Reviews</h2>
        
        <?php if (empty($reviews)): ?>
            <div style="padding: 40px 0; color: var(--text-muted); font-size: 0.95rem;">
                This product doesn't have any reviews yet.
            </div>
        <?php else: ?>
            <div class="rating-overview">
                <div class="rating-score"><?php echo number_format($avg_rating, 1); ?></div>
                <div>
                    <div class="rating-stars">
                        <?php 
                        for($i=1; $i<=5; $i++) {
                            echo $i <= round($avg_rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <div class="rating-count">Based on <?php echo count($reviews); ?> reviews</div>
                </div>
            </div>

            <div class="review-list">
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card">
                        <div class="review-avatar">
                            <?php echo strtoupper(substr($rev['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="review-body">
                            <div class="review-header">
                                <span class="reviewer-name"><?php echo htmlspecialchars($rev['username'] ?? 'Anonymous'); ?></span>
                                <?php echo getRankBadge($rev['reward_coins'] ?? 0, $rev['membership_tier'] ?? 'Basic'); ?>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($rev['review_date'] ?? 'now')); ?></span>
                            </div>
                            <div style="color: var(--gold); font-size: 0.8rem; margin-bottom: 8px;">
                                <?php 
                                for($i=1; $i<=5; $i++) {
                                    echo $i <= $rev['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <div class="review-content"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
</main>

<div id="lightbox" onclick="closeLightbox()">
    <span class="lb-close">&times;</span>
    <img id="lightbox-img" src="" alt="Zoomed Image">
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // 数量控制引擎
    document.addEventListener('DOMContentLoaded', function() {
        const qtyInput = document.getElementById('qtyInput');
        const btnDesc = document.getElementById('qtyDesc');
        const btnInsc = document.getElementById('qtyInsc');
        
        if(!qtyInput) return; 
        
        const maxStock = parseInt(qtyInput.getAttribute('max'));

        if(maxStock <= 0) return;

        btnDesc.addEventListener('click', function() {
            let currentQty = parseInt(qtyInput.value);
            if(currentQty > 1) { qtyInput.value = currentQty - 1; }
        });

        btnInsc.addEventListener('click', function() {
            let currentQty = parseInt(qtyInput.value);
            if(currentQty < maxStock) { qtyInput.value = currentQty + 1; }
        });

        qtyInput.addEventListener('change', function() {
            let currentQty = parseInt(this.value);
            if(isNaN(currentQty) || currentQty < 1) {
                this.value = 1;
            } else if (currentQty > maxStock) {
                this.value = maxStock;
            }
        });
    });

    // Lightbox 控制
    function openLightbox() {
        const mainImgSrc = document.getElementById('mainProductImage').src;
        document.getElementById('lightbox-img').src = mainImgSrc;
        document.getElementById('lightbox').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
</script>

</body>
</html>