<?php
// 1. 逻辑前置：开启缓冲和 Session
ob_start();
session_start();
require_once 'config.php';

// 2. 防御机制：如果没有传 category_id 过来，直接踢回 builder 页面
if (!isset($_GET['category_id']) || empty($_GET['category_id'])) {
    header("Location: builder.php");
    exit();
}
$category_id = intval($_GET['category_id']); // intval() 强转数字，防止黑客 SQL 注入

// ==========================================
// 3. 处理用户点击 "Add to Build" 的动作
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $product_id = intval($_POST['product_id']);
    
    // 去数据库查出这个商品的名字、价格和功耗
    $stmt = $conn->prepare("SELECT name, price, tdp_wattage FROM products WHERE product_id = ? AND status = 'Available'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        
        // 【核心魔法】把选中的商品，存进我们之前在 builder.php 设计的 Session 购物车里！
        $_SESSION['pc_build'][$category_id] = [
            'product_id' => $product_id,
            'name'       => $product['name'],
            'price'      => $product['price'],
            'wattage'    => $product['tdp_wattage']
        ];
        
        // 选好之后，带着记忆瞬间传送回 builder.php
        header("Location: builder.php");
        exit();
    }
}

// ==========================================
// 4. 获取要在网页上显示的商品数据
// ==========================================
// 获取当前分类的名称 (比如把 1 翻译成 "Processor (CPU)")
$cat_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$cat_stmt->bind_param("i", $category_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$category_name = ($cat_result->num_rows > 0) ? $cat_result->fetch_assoc()['category_name'] : "Components";
$cat_stmt->close();

// 获取该分类下所有“上架中”且“不是整机套餐”的配件
$prod_stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'Available' AND is_package = 0");
$prod_stmt->bind_param("i", $category_id);
$prod_stmt->execute();
$products = $prod_stmt->get_result();

// 开始渲染 HTML
include 'includes/header.php';
?>

<style>
    .part-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    .part-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        transition: var(--transition-smooth);
        display: flex;
        flex-direction: column;
    }
    .part-card:hover {
        border-color: #00f2fe;
        box-shadow: 0 5px 20px rgba(0, 242, 254, 0.1);
        transform: translateY(-5px);
    }
    .part-price {
        font-size: 1.4rem;
        color: #00e676; /* 科技绿 */
        font-weight: 800;
        margin: 10px 0;
    }
    .part-specs {
        font-size: 0.85rem;
        color: var(--text-muted);
        background: rgba(255,255,255,0.05);
        padding: 8px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
</style>

<div class="main-container" style="max-width: 1000px; margin: 2rem auto;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <div>
            <a href="builder.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-arrow-left"></i> Back to Builder
            </a>
            <h1 style="font-size: 2.2rem; font-weight: 800; margin-top: 5px;">Select <span style="color: #00f2fe;"><?php echo htmlspecialchars($category_name); ?></span></h1>
        </div>
    </div>

    <div class="part-grid">
        <?php if ($products->num_rows > 0): ?>
            <?php while($row = $products->fetch_assoc()): ?>
                <div class="part-card">
                    <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 8px;"><?php echo htmlspecialchars($row['name']); ?></h3>
                    
                    <p style="font-size: 0.9rem; color: var(--text-muted); flex-grow: 1;"><?php echo htmlspecialchars($row['description']); ?></p>
                    
                    <div class="part-specs">
                        <i class="fas fa-bolt" style="color: #ffc107;"></i> TDP: <?php echo $row['tdp_wattage']; ?>W &nbsp;|&nbsp; 
                        <i class="fas fa-box" style="color: var(--accent-purple);"></i> Stock: <?php echo $row['stock_quantity']; ?>
                    </div>
                    
                    <div class="part-price">RM <?php echo number_format($row['price'], 2); ?></div>

                    <form action="select_part.php?category_id=<?php echo $category_id; ?>" method="POST" style="margin-top: auto;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-plus-circle"></i> Add to Build
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: rgba(255,255,255,0.02); border-radius: 8px;">
                <i class="fas fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-main);">No components available yet</h3>
                <p style="color: var(--text-muted);">Please check back later or contact admin.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>