<?php 
ob_start(); 
session_start();
include 'includes/header.php'; 

// ==========================================
// [🚨 优化点 1]：监听用户的“移除”和“清空”指令
// ==========================================
// 1. 单个零件移除逻辑
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['cat_id'])) {
    $remove_id = $_GET['cat_id'];
    unset($_SESSION['pc_build'][$remove_id]); // 从 Session 中删掉这个零件
    header("Location: builder.php"); // 刷新页面
    exit();
}
// 2. 一键清空购物车逻辑
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    unset($_SESSION['pc_build']); // 砸碎整个 PC Builder 的 Session
    header("Location: builder.php"); 
    exit();
}

// 初始化
if (!isset($_SESSION['pc_build'])) {
    $_SESSION['pc_build'] = []; 
}

$total_price = 0.00;
$total_wattage = 0;

// 插槽数据
$pc_slots = [
    ['id' => 1, 'name' => 'Processor (CPU)', 'desc' => 'The brain of your computer.', 'icon' => 'fa-microchip'],
    ['id' => 2, 'name' => 'Motherboard', 'desc' => 'Connects everything together.', 'icon' => 'fa-chess-board'],
    ['id' => 3, 'name' => 'Memory (RAM)', 'desc' => 'Short-term memory for multitasking.', 'icon' => 'fa-memory'],
    ['id' => 4, 'name' => 'Graphics Card (GPU)', 'desc' => 'For gaming and heavy rendering.', 'icon' => 'fa-tv'],
    ['id' => 5, 'name' => 'Storage (SSD)', 'desc' => 'Where your OS and games live.', 'icon' => 'fa-hdd'],
    ['id' => 6, 'name' => 'Power Supply (PSU)', 'desc' => 'Provides juice to all components.', 'icon' => 'fa-plug'],
    ['id' => 7, 'name' => 'PC Case', 'desc' => 'The house for your parts.', 'icon' => 'fa-box'],
    ['id' => 8, 'name' => 'Cooling System', 'desc' => 'Keeps your temperatures low.', 'icon' => 'fa-fan']
];

// ==========================================
// [🚨 优化点 2]：计算组装进度百分比
// ==========================================
$total_slots_count = count($pc_slots);
$filled_slots_count = count($_SESSION['pc_build']);
// 防止除以 0 的错误
$progress_percent = ($total_slots_count > 0) ? round(($filled_slots_count / $total_slots_count) * 100) : 0;

?>

<link rel="stylesheet" href="css/builder.css">

<style>
    .slot-filled { border-color: #00f2fe !important; background: rgba(0, 242, 254, 0.05); }
    .selected-part-name { color: #fff; font-weight: 700; font-size: 1.1rem; margin-top: 5px; display: block; }
    .selected-price { color: #00e676; font-weight: bold; }
    .btn-replace { background: rgba(0, 242, 254, 0.1); border: 1px solid #00f2fe; color: #00f2fe; }
    .btn-replace:hover { background: #00f2fe; color: #000; }
    
    /* 移除按钮样式 */
    .btn-remove { background: transparent; border: 1px solid #ff4d4d; color: #ff4d4d; padding: 10px 15px; border-radius: 6px; cursor: pointer; transition: 0.3s; margin-left: 10px; }
    .btn-remove:hover { background: #ff4d4d; color: #fff; }

    /* 进度条样式 */
    .progress-container { background: rgba(255,255,255,0.05); border-radius: 10px; height: 10px; width: 100%; margin-top: 15px; overflow: hidden; position: relative; }
    .progress-bar { height: 100%; background: linear-gradient(90deg, #00f2fe, #4facfe); transition: width 0.5s ease-in-out; box-shadow: 0 0 10px #00f2fe; }
</style>

<div class="builder-container">
    
    <div style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; position: relative;">
        <h1 style="font-size: 2.8rem; font-weight: 800;">System <span style="color: #00f2fe;">Builder</span></h1>
        
        <?php if($filled_slots_count > 0): ?>
            <a href="builder.php?action=clear" onclick="return confirm('Are you sure you want to clear your entire build?');" 
               style="position: absolute; right: 0; top: 15px; color: #ff4d4d; text-decoration: none; font-weight: bold;">
               <i class="fas fa-trash-alt"></i> Clear Build
            </a>
        <?php endif; ?>

        <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Select your components. Our smart engine ensures 100% compatibility.</p>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px;">
            <span style="color: #00f2fe; font-weight: 600;"><i class="fas fa-tasks"></i> Build Progress: <?php echo $filled_slots_count; ?>/<?php echo $total_slots_count; ?> Parts</span>
            <span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo $progress_percent; ?>% Completed</span>
        </div>
        <div class="progress-container">
            <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%;"></div>
        </div>
    </div>

    <?php
    foreach ($pc_slots as $slot) {
        $cat_id = $slot['id'];
        $is_filled = isset($_SESSION['pc_build'][$cat_id]); 
        
        if ($is_filled) {
            $part = $_SESSION['pc_build'][$cat_id]; 
            $total_price += $part['price'];         
            $total_wattage += $part['wattage'];     
        }
    ?>
        <div class="slot-card <?php echo $is_filled ? 'slot-filled' : ''; ?>">
            <div class="slot-info">
                <div class="slot-icon">
                    <i class="fas <?php echo $slot['icon']; ?>"></i>
                </div>
                <div class="slot-details">
                    <h3><?php echo $slot['name']; ?></h3>
                    <?php if ($is_filled): ?>
                        <span class="selected-part-name"><?php echo htmlspecialchars($part['name']); ?></span>
                        <span class="selected-price">RM <?php echo number_format($part['price'], 2); ?></span>
                    <?php else: ?>
                        <p><?php echo $slot['desc']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="slot-action" style="display: flex; align-items: center;">
                <?php if ($is_filled): ?>
                    <a href="select_part.php?category_id=<?php echo $cat_id; ?>" class="btn-select btn-replace">
                        <i class="fas fa-exchange-alt"></i> Replace
                    </a>
                    <a href="builder.php?action=remove&cat_id=<?php echo $cat_id; ?>" class="btn-remove" title="Remove Component">
                        <i class="fas fa-times"></i>
                    </a>
                <?php else: ?>
                    <a href="select_part.php?category_id=<?php echo $cat_id; ?>" class="btn-select">
                        <i class="fas fa-plus"></i> Select
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php 
    } 
    ?>
</div>

<div class="sticky-footer">
    <div class="summary-stats">
        <div class="stat-box">
            <span class="stat-label">Estimated Wattage</span>
            <span class="stat-value"><i class="fas fa-bolt" style="color: #ffc107;"></i> <?php echo $total_wattage; ?>W</span>
        </div>
        <div class="stat-box">
            <span class="stat-label">Total Price</span>
            <span class="stat-value highlight">RM <?php echo number_format($total_price, 2); ?></span>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px;">
        <button class="btn btn-outline" style="border-color: var(--text-main); color: var(--text-main);">
            <i class="fas fa-save"></i> Save Build
        </button>
        <button class="btn btn-primary" <?php echo ($total_price == 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
            Checkout <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>