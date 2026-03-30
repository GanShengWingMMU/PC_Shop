<?php
session_start();
require_once 'config.php';

// 🚨 核心修复：安全拦截必须放在 include header 的前面！
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
$customer_id = $_SESSION['customer_id'];

// 安全确认过关后，才加载头部 UI
include 'includes/header.php';

// ==========================================
// 🧠 联合查询 (SQL JOIN): 把存档、零件明细、商品名字一次性查出来
// ==========================================
$sql = "
    SELECT 
        sb.build_id, sb.build_name, sb.total_price, sb.created_at,
        p.product_name, p.image_url, c.category_name
    FROM saved_builds sb
    LEFT JOIN build_items bi ON sb.build_id = bi.build_id
    LEFT JOIN products p ON bi.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE sb.customer_id = ?
    ORDER BY sb.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// 数据重组
$saved_builds = [];
while ($row = $result->fetch_assoc()) {
    $b_id = $row['build_id'];
    if (!isset($saved_builds[$b_id])) {
        $saved_builds[$b_id] = [
            'name' => $row['build_name'],
            'price' => $row['total_price'],
            'date' => date('F j, Y', strtotime($row['created_at'])),
            'parts' => []
        ];
    }
    if ($row['product_name']) {
        $saved_builds[$b_id]['parts'][] = [
            'category' => $row['category_name'],
            'product' => $row['product_name']
        ];
    }
}
$stmt->close();
?>

<style>
    :root { --accent: #00f2fe; --dark-bg: #0f172a; --card-bg: rgba(255,255,255,0.03); }
    .profile-container { max-width: 1000px; margin: 3rem auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    .build-card { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 25px; overflow: hidden; transition: 0.3s; }
    .build-card:hover { border-color: var(--accent); box-shadow: 0 10px 30px rgba(0,242,254,0.05); }
    
    .build-header { background: rgba(0,0,0,0.3); padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .build-title { font-size: 1.3rem; font-weight: 800; color: #fff; margin: 0; }
    .build-date { font-size: 0.85rem; color: #888; margin-top: 5px; }
    .build-price { font-size: 1.5rem; font-weight: 900; color: var(--accent); }
    
    .build-body { padding: 20px 25px; }
    .part-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed rgba(255,255,255,0.05); font-size: 0.9rem; }
    .part-row:last-child { border-bottom: none; }
    .part-cat { color: #888; font-weight: 600; width: 150px; }
    .part-name { color: #ddd; flex-grow: 1; }
    
    .btn-action { display: inline-block; padding: 8px 16px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 0.85rem; transition: 0.2s; margin-left: 10px; }
    .btn-primary { background: var(--accent); color: #000; }
    .btn-primary:hover { background: #fff; box-shadow: 0 0 10px var(--accent); }
</style>

<div class="profile-container">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0;">MY <span style="color:var(--accent)">ARMORY</span></h1>
        <p style="color: #888;">View and manage your saved custom builds.</p>
    </div>

    <?php if (empty($saved_builds)): ?>
        <div style="text-align: center; padding: 60px 20px; border: 1px dashed rgba(255,255,255,0.2); border-radius: 12px;">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #444; margin-bottom: 15px;"></i>
            <h3 style="color: #fff;">Your Armory is Empty</h3>
            <p style="color: #888;">You haven't saved any PC builds yet.</p>
            <a href="builder.php" class="btn-action btn-primary" style="margin-top: 15px;">Go Build One Now</a>
        </div>
    <?php else: ?>
        <?php foreach ($saved_builds as $id => $build): ?>
            <div class="build-card">
                <div class="build-header">
                    <div>
                        <h2 class="build-title"><i class="fas fa-desktop" style="color: var(--accent); margin-right: 10px;"></i><?php echo htmlspecialchars($build['name']); ?></h2>
                        <div class="build-date">Saved on: <?php echo $build['date']; ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div class="build-price">RM <?php echo number_format($build['price'], 2); ?></div>
                        <a href="#" class="btn-action btn-primary" style="margin-top: 10px;"><i class="fas fa-shopping-cart"></i> Checkout This Rig</a>
                    </div>
                </div>
                <div class="build-body">
                    <?php foreach ($build['parts'] as $part): ?>
                        <div class="part-row">
                            <div class="part-cat"><?php echo htmlspecialchars($part['category']); ?></div>
                            <div class="part-name"><?php echo htmlspecialchars($part['product']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>