<?php
ob_start();
session_start();
require_once 'config.php';

// ==========================================
// 1. 门卫拦截 (Auth Guard)
// ==========================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// ==========================================
// 2. 抓取玩家基础资料 (Fetch User Info)
// ==========================================
$stmt_user = $conn->prepare("SELECT first_name, last_name, email FROM customers WHERE customer_id = ?");
$stmt_user->bind_param("i", $customer_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// ==========================================
// 3. 抓取草稿箱主单 (Fetch Saved Builds)
// ==========================================
$stmt_builds = $conn->prepare("SELECT pc_build, build_name, total_price, created_at FROM saved_builds WHERE customer_id = ? ORDER BY created_at DESC");
$stmt_builds->bind_param("i", $customer_id);
$stmt_builds->execute();
$builds_result = $stmt_builds->get_result();
$saved_builds = [];
while ($row = $builds_result->fetch_assoc()) {
    $saved_builds[] = $row;
}
$stmt_builds->close();

// ==========================================
// 模組：撈取地址簿與信用卡 (請加在 profile.php 最上方)
// ==========================================

// 抓取所有地址 (預設地址排前面)
$addresses = [];
$addr_query = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt_addr = $conn->prepare($addr_query);
$stmt_addr->bind_param("i", $customer_id);
$stmt_addr->execute();
$addr_result = $stmt_addr->get_result();
while ($row = $addr_result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt_addr->close();

// 抓取所有綁定的信用卡 (預設卡片排前面)
$cards = [];
$card_query = "SELECT * FROM saved_cards WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt_card = $conn->prepare($card_query);
$stmt_card->bind_param("i", $customer_id);
$stmt_card->execute();
$card_result = $stmt_card->get_result();
while ($row = $card_result->fetch_assoc()) {
    $cards[] = $row;
}
$stmt_card->close();

include 'includes/header.php';
?>

<style>
    .armory-container { max-width: 1100px; margin: 4rem auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .page-title { font-size: 2.5rem; font-weight: 900; color: #fff; margin-bottom: 5px; letter-spacing: -1px; text-transform: uppercase; }
    .page-subtitle { color: #888; font-size: 1rem; margin-bottom: 40px; }
    
    .profile-header { background: rgba(0,242,254,0.05); border: 1px solid rgba(0,242,254,0.2); border-radius: 12px; padding: 30px; margin-bottom: 40px; display: flex; align-items: center; gap: 20px; }
    .avatar-circle { width: 80px; height: 80px; background: linear-gradient(135deg, #00f2fe, #4facfe); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 2.5rem; color: #000; font-weight: 900; box-shadow: 0 0 20px rgba(0,242,254,0.4); }
    
    .build-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; margin-bottom: 25px; overflow: hidden; transition: 0.3s; }
    .build-card:hover { border-color: rgba(0,242,254,0.5); box-shadow: 0 10px 30px rgba(0,242,254,0.05); }
    .build-header { background: rgba(0,0,0,0.4); padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .build-title { font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 5px; }
    .build-date { font-size: 0.85rem; color: #64748b; }
    .build-price { font-size: 1.5rem; font-weight: 900; color: #00e676; }
    
    .parts-list { padding: 20px 25px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
    .part-item { background: rgba(255,255,255,0.03); padding: 12px 15px; border-radius: 8px; border-left: 3px solid #00f2fe; display: flex; flex-direction: column; }
    .part-cat { font-size: 0.7rem; color: #00f2fe; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }
    .part-name { font-size: 0.95rem; color: #cbd5e1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .btn-action { display: inline-block; padding: 10px 20px; background: transparent; border: 1px solid #00f2fe; color: #00f2fe; font-weight: bold; border-radius: 6px; text-decoration: none; transition: 0.3s; margin-top: 15px; cursor: pointer; }
    .btn-action:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px rgba(0,242,254,0.4); }
    .empty-state { text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1); }
</style>

<div class="armory-container">
    <div class="page-title">Commander <span style="color: #00f2fe;">Armory</span></div>
    <div class="page-subtitle">Manage your saved blueprints and order history.</div>

    <div class="profile-header">
        <div class="avatar-circle">
            <?php echo strtoupper(substr($user_info['first_name'], 0, 1)); ?>
        </div>
        <div>
            <div style="font-size: 1.8rem; font-weight: 900; color: #fff;"><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></div>
            <div style="color: #00f2fe; font-size: 0.95rem;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_info['email']); ?></div>
            <div style="margin-top: 8px; display: inline-block; background: rgba(255,255,255,0.1); color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; letter-spacing: 1px;">CLEARANCE LEVEL: CUSTOMER</div>
        </div>
    </div>

    <div class="auth-container" style="margin-bottom: 20px;">
    <h3 style="color: var(--text-main); margin-bottom: 20px;"><i class="fa-solid fa-map-location-dot"></i> Address Book</h3>
    
    <?php if (empty($addresses)): ?>
        <p class="specs"><i class="fa-solid fa-circle-info"></i> No addresses saved yet.</p>
    <?php else: ?>
        <?php foreach ($addresses as $addr): ?>
            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: <?php echo $addr['is_default'] ? '3px solid var(--accent-blue)' : '3px solid transparent'; ?>;">
                <p style="color: var(--text-main); margin: 0; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($addr['full_address'])); ?>
                    <?php if ($addr['is_default']) echo '<span style="background: var(--accent-blue); color: #000; font-size: 0.7rem; font-weight: bold; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">Default</span>'; ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<a href="add_address.php" class="btn" style="display: block; text-align: center; width: 100%; margin-top: 15px; background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue); transition: 0.3s;">
    <i class="fa-solid fa-plus"></i> Add New Address
</a>
</div>

<div class="auth-container">
    <h3 style="color: var(--text-main); margin-bottom: 20px;"><i class="fa-solid fa-credit-card"></i> Payment Methods</h3>
    
    <?php if (empty($cards)): ?>
        <p class="specs"><i class="fa-solid fa-circle-info"></i> No payment methods saved yet.</p>
    <?php else: ?>
        <?php foreach ($cards as $card): ?>
            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: <?php echo $card['is_default'] ? '3px solid var(--accent-blue)' : '3px solid transparent'; ?>;">
                <p style="color: var(--text-main); font-weight: bold; margin: 0;">
                    <i class="fa-solid fa-credit-card" style="color: var(--text-muted); margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($card['card_brand']); ?> 
                    <span class="specs" style="margin-left: 10px;">**** <?php echo htmlspecialchars($card['last_four_digits']); ?></span>
                    <?php if ($card['is_default']) echo '<span style="background: var(--accent-blue); color: #000; font-size: 0.7rem; font-weight: bold; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">Default</span>'; ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<a href="add_card.php" class="btn" style="display: block; text-align: center; width: 100%; margin-top: 15px; background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue); transition: 0.3s;">
    <i class="fa-solid fa-plus"></i> Add New Card
</a>

</div>

    <h2 style="color: #fff; font-size: 1.5rem; margin-bottom: 20px; border-left: 4px solid #00f2fe; padding-left: 15px;"><i class="fas fa-save"></i> Saved Blueprints</h2>

    <?php if (count($saved_builds) > 0): ?>
        <?php foreach ($saved_builds as $build): ?>
            <div class="build-card">
                <div class="build-header">
                    <div>
                        <div class="build-title"><?php echo htmlspecialchars($build['build_name']); ?></div>
                        <div class="build-date"><i class="far fa-clock"></i> Secured on <?php echo date('M d, Y - H:i', strtotime($build['created_at'])); ?></div>
                    </div>
                    <div style="text-align: right;">
                    <div class="build-price">RM <?php echo number_format($build['total_price'], 2); ?></div>
                    <a href="load_build.php?id=<?php echo $build['pc_build']; ?>" class="btn-action" style="padding: 6px 15px; font-size: 0.85rem; text-decoration: none;">
                    <i class="fas fa-upload"></i> Load to Builder
                    </a>
                    </div>
                </div>
                
                <div class="parts-list">
                    <?php
                        // ==========================================
                        // 🧠 架构师点睛之笔：多表 JOIN 查询零件清单
                        // ==========================================
                        $stmt_items = $conn->prepare("
                            SELECT p.product_name, c.category_name 
                            FROM build_items bi 
                            JOIN products p ON bi.product_id = p.product_id 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE bi.pc_build = ?
                        ");
                        $stmt_items->bind_param("i", $build['pc_build']);
                        $stmt_items->execute();
                        $items_res = $stmt_items->get_result();
                        
                        while ($item = $items_res->fetch_assoc()):
                    ?>
                        <div class="part-item">
                            <div class="part-cat"><?php echo htmlspecialchars($item['category_name']); ?></div>
                            <div class="part-name" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </div>
                        </div>
                    <?php 
                        endwhile; 
                        $stmt_items->close();
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #444; margin-bottom: 15px;"></i>
            <h3 style="color: #fff; margin-bottom: 5px;">Armory is Empty</h3>
            <p style="color: #888; font-size: 0.95rem;">You haven't saved any custom PC blueprints yet.</p>
            <a href="builder.php" class="btn-action" style="margin-top: 20px;"><i class="fas fa-tools"></i> Start Building</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>