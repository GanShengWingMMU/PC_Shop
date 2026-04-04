<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = isset($_SESSION['pc_build']) ? $_SESSION['pc_build'] : [];

if (empty($cart)) {
    header("Location: builder.php");
    exit();
}

$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'];
}

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_build'])) {
    $build_name = trim($conn->real_escape_string($_POST['build_name']));
    if (empty($build_name)) { $build_name = "My Custom PC (" . date('M d, Y') . ")"; }
    
    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $customer_id, $build_name, $total_price);
        $stmt->execute();
        
        $build_id = $conn->insert_id; 
        $stmt->close();

<<<<<<< HEAD
        // [步骤 B]：把购物车里的零件，一个个打上这个文件夹的 ID 烙印，存入 build_items 表
       
        $stmt_items = $conn->prepare("INSERT INTO build_items (pc_build, product_id, quantity) VALUES (?, ?, 1)");
=======
        $stmt_items = $conn->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");
>>>>>>> bef5aee379b8a16235e13ec7c3ceebec133498f9
        foreach ($cart as $cat_id => $item) {
            $pid = $item['product_id'];
            $stmt_items->bind_param("ii", $build_id, $pid);
            $stmt_items->execute();
        }
        $stmt_items->close();

        $conn->commit();
        
        $message = "Blueprint successfully saved to your Armory!";
        $msg_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "System Error: Failed to save blueprint. " . $e->getMessage();
        $msg_type = "error";
    }
}

include 'includes/header.php';
?>

<style>
    .save-container { max-width: 500px; margin: 5rem auto; background: rgba(255,255,255,0.03); padding: 40px; border-radius: 15px; border: 1px solid rgba(0,242,254,0.2); box-shadow: 0 15px 35px rgba(0,0,0,0.5); font-family: 'Inter', sans-serif; }
    .title { text-align: center; color: #fff; font-size: 2.2rem; font-weight: 900; margin-bottom: 10px; letter-spacing: -1px; }
    .subtitle { text-align: center; color: #888; margin-bottom: 30px; font-size: 0.95rem; }
    .form-group { margin-bottom: 20px; }
    .form-control { width: 100%; padding: 12px 15px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); color: #00f2fe; border-radius: 8px; outline: none; transition: 0.3s; font-size: 1.1rem; font-weight: bold; }
    .form-control:focus { border-color: #00f2fe; box-shadow: 0 0 10px rgba(0,242,254,0.2); }
    .btn-save { width: 100%; padding: 14px; background: #00f2fe; color: #000; border: none; font-weight: 900; font-size: 1.1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; }
    .btn-save:hover { background: #fff; box-shadow: 0 0 20px #00f2fe; transform: translateY(-2px); }
    .parts-preview { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 15px; margin-bottom: 25px; border: 1px dashed rgba(255,255,255,0.1); }
    .preview-item { font-size: 0.85rem; color: #cbd5e1; margin-bottom: 5px; display: flex; justify-content: space-between; }
</style>

<div class="save-container">
    <div class="title">Save <span style="color: #00f2fe;">Blueprint</span></div>
    <div class="subtitle">Secure your current configuration to your armory.</div>

    <?php if ($message): ?>
        <?php if ($msg_type == 'success'): ?>
            <div style="background: rgba(0, 230, 118, 0.1); color: #00e676; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center; font-weight: bold; border: 1px solid rgba(0, 230, 118, 0.3);">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; display: block; margin-bottom: 5px;"></i> <?php echo $message; ?>
            </div>
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <a href="profile.php" class="btn-save" style="text-align: center; text-decoration: none; background: transparent; border: 1px solid #00f2fe; color: #00f2fe;">View Armory</a>
                <a href="builder.php" class="btn-save" style="text-align: center; text-decoration: none;">Keep Building</a>
            </div>
        <?php else: ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3);">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $message; ?>
            </div>
            <a href="builder.php" style="color: #888; text-decoration: none; display: block; text-align: center;"><i class="fas fa-arrow-left"></i> Go back</a>
        <?php endif; ?>
    <?php else: ?>

        <div class="parts-preview">
            <div style="color: #888; font-size: 0.75rem; font-weight: bold; margin-bottom: 10px; letter-spacing: 1px;">CURRENT LOADOUT: <?php echo count($cart); ?> PARTS</div>
            <?php foreach ($cart as $item): ?>
                <div class="preview-item">
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%;">- <?php echo htmlspecialchars($item['name']); ?></span>
                    <span style="color: #00e676;">RM <?php echo number_format($item['price'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between; font-weight: bold; color: #fff;">
                <span>ESTIMATED TOTAL:</span>
                <span style="color: #00f2fe;">RM <?php echo number_format($total_price, 2); ?></span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label style="color: #888; font-size: 0.85rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">BLUEPRINT NAME</label>
                <input type="text" name="build_name" class="form-control" placeholder="e.g. Dream Gaming Rig 2026" required>
            </div>
            <button type="submit" name="save_build" class="btn-save"><i class="fas fa-lock" style="margin-right: 5px;"></i> SECURE LOADOUT</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="builder.php" style="color: #888; text-decoration: none; font-size: 0.9rem; transition: 0.2s;" onmouseover="this.style.color='#00f2fe'" onmouseout="this.style.color='#888'"><i class="fas fa-arrow-left"></i> Return to Builder</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>