<?php
ob_start();
session_start();
require_once 'config.php';

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id == 0) {
    header("Location: builder.php");
    exit();
}

// ==========================================
// 1. 处理表单提交：抓取真实数据存入 Session
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_build'])) {
    $product_id = intval($_POST['product_id']);
    
    // 亲自去数据库查真实价格和功耗，绝对不信任前端数据！
    $stmt = $conn->prepare("SELECT product_name, price, tdp_wattage FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['pc_build'][$category_id] = [
            'product_id' => $product_id,
            'name'       => $row['product_name'], 
            'price'      => $row['price'],
            'wattage'    => $row['tdp_wattage'] ?? 0 
        ];
    }
    $stmt->close();
    
    header("Location: builder.php");
    exit();
}

// ==========================================
// 2. 接收来自 Builder 的“智能算法参数”
// ==========================================
$socket_filter = isset($_GET['socket']) ? $conn->real_escape_string($_GET['socket']) : '';
$min_wattage = isset($_GET['min_w']) ? intval($_GET['min_w']) : 0;
$ram_type_req = isset($_GET['ram_type']) ? $conn->real_escape_string($_GET['ram_type']) : ''; 

$cat_name = "Component";
$stmt_cat = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$stmt_cat->bind_param("i", $category_id);
$stmt_cat->execute();
$res_cat = $stmt_cat->get_result();
if ($row_cat = $res_cat->fetch_assoc()) {
    $cat_name = $row_cat['category_name']; 
}
$stmt_cat->close();

// ==========================================
// 3. 动态 SQL 构建器 (Dynamic SQL Builder)
// ==========================================
$sql = "SELECT * FROM products WHERE category_id = $category_id AND status = 'Available'";
$filter_messages = []; 

// 规则 A：插槽过滤 (CPU -> 主板)
if (!empty($socket_filter)) {
    $sql .= " AND (product_name LIKE '%$socket_filter%' OR description LIKE '%$socket_filter%')";
    $filter_messages[] = "Socket locked to: <strong>$socket_filter</strong>";
}

// 规则 B：内存世代过滤 (主板 -> RAM)
if (!empty($ram_type_req) && $category_id == 3) { 
    $sql .= " AND (product_name LIKE '%$ram_type_req%' OR description LIKE '%$ram_type_req%')";
    $filter_messages[] = "Memory standard locked to: <strong>$ram_type_req</strong>";
}

// 规则 C：功耗下限过滤 (总功耗 -> 电源)
if ($min_wattage > 0 && $category_id == 6) { 
    $sql .= " AND tdp_wattage >= $min_wattage";
    $filter_messages[] = "Minimum Power Required: <strong>{$min_wattage}W</strong>";
}

$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<style>
    :root { --accent: #00f2fe; --dark-bg: #0f172a; --card-bg: rgba(255,255,255,0.03); }
    .catalog-container { max-width: 1200px; margin: 2rem auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    .header-section { margin-bottom: 2rem; border-bottom: 1px solid rgba(0,242,254,0.2); padding-bottom: 1.5rem; }
    .filter-badge { display: inline-block; background: rgba(0,242,254,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; margin-right: 10px; margin-top: 10px; }

    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    
    .product-card { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; height: 100%; }
    .product-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 20px rgba(0,242,254,0.1); }
    
    .prod-img { width: 100%; height: 180px; object-fit: contain; margin-bottom: 15px; border-radius: 8px; background: rgba(0,0,0,0.2); padding: 10px; }
    .prod-name { font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
    .prod-desc { font-size: 0.85rem; color: #888; flex-grow: 1; margin-bottom: 15px; }
    
    .prod-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; }
    .prod-price { font-size: 1.3rem; color: #00e676; font-weight: 900; }
    
    .btn-add { background: var(--accent); color: #000; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-add:hover { background: #fff; box-shadow: 0 0 10px var(--accent); }
    
    .empty-state { text-align: center; padding: 50px 20px; background: var(--card-bg); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.2); }
</style>

<div class="catalog-container">
    <div class="header-section">
        <a href="builder.php" style="color: #888; text-decoration: none; font-size: 0.9rem; margin-bottom: 10px; display: inline-block;"><i class="fas fa-arrow-left"></i> Back to Builder</a>
        <h1 style="font-size: 2.5rem; font-weight: 900; margin: 0; color: #fff;">Select <span style="color: var(--accent);"><?php echo htmlspecialchars($cat_name); ?></span></h1>
        
        <?php if (!empty($filter_messages)): ?>
            <div style="margin-top: 10px;">
                <span style="color: #888; font-size: 0.85rem;"><i class="fas fa-filter"></i> Active Engine Filters:</span><br>
                <?php foreach ($filter_messages as $msg): ?>
                    <span class="filter-badge"><?php echo $msg; ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="product-card">
                    <img src="<?php echo !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'https://via.placeholder.com/280x180/111/333?text=PC+Part'; ?>" alt="Part" class="prod-img">
                    
                    <div class="prod-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    <div class="prod-desc">
                        <?php 
                            $desc = strip_tags($row['description']);
                            echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc; 
                        ?>
                    </div>
                    
                    <div style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <?php if(isset($row['tdp_wattage']) && $row['tdp_wattage'] > 0): ?>
                            <span style="font-size: 0.75rem; background: rgba(255,193,7,0.1); color: #ffc107; padding: 2px 6px; border-radius: 4px;"><i class="fas fa-bolt"></i> <?php echo $row['tdp_wattage']; ?>W</span>
                        <?php endif; ?>
                    </div>

                    <form action="select_part.php?category_id=<?php echo $category_id; ?>" method="POST" class="prod-footer">
                        <div class="prod-price">RM <?php echo number_format($row['price'], 2); ?></div>
                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>"> 
                        <button type="submit" name="add_to_build" class="btn-add">
                            <i class="fas fa-plus"></i> Select
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-search" style="font-size: 3rem; color: #444; margin-bottom: 15px;"></i>
                <h3 style="color: #fff; margin-bottom: 5px;">No Compatible Components Found</h3>
                <p style="color: #888; font-size: 0.9rem;">Our engine filtered out incompatible parts, but we couldn't find any matching products in the database.</p>
                <a href="builder.php" class="btn-add" style="display: inline-block; margin-top: 15px; text-decoration: none;">Return to Builder</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>