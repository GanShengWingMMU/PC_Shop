<?php
session_start();

// 🌟 智慧相容：優先載入 config.php，若不存在則降級載入 db_connect.php
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    include 'db_connect.php';
}

// 🌟 統一安全准入：雙重相容 role 與 admin_role，防止未授權用戶或駭客直接盲點闖入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = ""; 

// 🌟 核心升級：安全防 SQL 注入刪除引擎 (Prepared Statement)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // 檢查防呆：先看看這件商品有沒有被鎖定在顧客的訂單 (order_items) 裡
    $check_stmt = $conn->prepare("SELECT item_id FROM order_items WHERE product_id = ? LIMIT 1");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if ($check_res->num_rows > 0) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'><i class='fas fa-exclamation-circle'></i> Cannot delete product! This component is linked to active customer orders (financial logs protection).</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) {
            header("Location: manage_products.php?deleted=1");
            exit();
        } else {
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>⚠️ System Error: Failed to execute deletion node.</div>";
        }
        $stmt_del->close();
    }
    $check_stmt->close();
}

if (isset($_GET['deleted'])) {
    $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'><i class='fas fa-check-circle'></i> Node purged! Product successfully removed from quantum grids.</div>";
}
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $message = "<div style='color: #00f2fe; background: rgba(0,242,254,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,242,254,0.3);'><i class='fas fa-sync'></i> Matrix Re-aligned! Product variables updated successfully.</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hardware Catalog - GridCity Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_products.php" class="active"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="manage_categories.php"><i class="fas fa-tags"></i> Manage Categories</a></li>
                <li><a href="manage_packages.php"><i class="fas fa-layer-group"></i> Manage Packages</a></li>
                <?php if (strtolower($current_role) === 'superadmin') : ?>
                    <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-microchip"></i> Quantum Hardware Registry</h2>
                    <p style="color: #64748b; margin-top:5px;">Add, modify, and monitor component parameters feeding the front-end AI Builder.</p>
                </div>
                <a href="add_product.php" class="btn-action" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color:#000; font-weight:900; border:none; padding:12px 24px; border-radius:6px; text-decoration:none; display:flex; align-items:center; gap:8px; box-shadow:0 0 15px rgba(0,242,254,0.3); transition:0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-plus"></i> Launch New Hardware
                </a>
            </header>

            <?php echo $message; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px; color:#00f2fe; width:60px;">Photo</th>
                            <th style="padding:15px; color:#00f2fe;">Component Name</th>
                            <th style="padding:15px; color:#00f2fe;">Category</th>
                            <th style="padding:15px; color:#00f2fe;">Price</th>
                            <th style="padding:15px; color:#00f2fe;">Stock</th>
                            <th style="padding:15px; color:#00f2fe; text-align:center;">AI Tier</th>
                            <th style="padding:15px; color:#00f2fe; text-align:right;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 連動點：跨表連接拉取真實分類名稱，並按最新上架排序
                        $sql = "SELECT p.*, c.category_name 
                                FROM products p 
                                JOIN categories c ON p.category_id = c.category_id 
                                ORDER BY p.product_id DESC";
                        $res = $conn->query($sql);
                        
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $pid = $row['product_id'];
                                $stock = intval($row['stock_quantity']);
                                $tier = intval($row['performance_tier'] ?? 1);
                                
                                // 智慧庫存霓虹色彩防呆雷達
                                $stock_style = "color: #00e676;"; // 庫存充足
                                if ($stock == 0) {
                                    $stock_style = "color: #ff4d4d; font-weight:bold; text-shadow: 0 0 10px rgba(255,77,77,0.3);";
                                } elseif ($stock <= 3) {
                                    $stock_style = "color: #facc15; font-weight:bold;";
                                }

                                // AI Tier 顆星顏色映射
                                $tier_color = "#64748b";
                                if ($tier >= 8) $tier_color = "#ff007f"; // 神級硬體 (God Tier)
                                elseif ($tier >= 5) $tier_color = "#00e676"; // 高階專業
                                
                                $image_src = htmlspecialchars($row['image_url']);
                                if (empty($image_src)) $image_src = 'image/placeholder_pc.png';

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05); transition:0.2s;' onmouseover='this.style.background=\"rgba(255,255,255,0.01)\"' onmouseout='this.style.background=\"none\"'>";
                                
                                // 照片節點
                                echo "<td style='padding:15px;'><img src='{$image_src}' style='height:45px; width:45px; object-fit:cover; border-radius:6px; border:1px solid rgba(255,255,255,0.08); background:#000;'></td>";
                                
                                // 名字與規格
                                echo "<td style='padding:15px;'>
                                        <span style='font-weight:600; font-size:15px; color:#fff;'>".htmlspecialchars($row['product_name'])."</span><br>
                                        <span style='font-size:12px; color:#64748b; font-family:\"JetBrains Mono\";'>".htmlspecialchars($row['specifications'])."</span>
                                      </td>";
                                
                                // 分類名字
                                echo "<td style='padding:15px;'><span style='background:rgba(138,43,226,0.1); color:#a855f7; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:bold; border:1px solid rgba(138,43,226,0.2);'>".htmlspecialchars($row['category_name'])."</span></td>";
                                
                                // 價格
                                echo "<td style='padding:15px; font-weight:bold; color:#00e676; font-family:\"JetBrains Mono\";'>RM " . number_format($row['price'], 2) . "</td>";
                                
                                // 庫存
                                echo "<td style='padding:15px; font-family:\"JetBrains Mono\"; $stock_style'>" . ($stock == 0 ? "OUT OF STOCK" : $stock . " UNITS") . "</td>";
                                
                                // AI 效能等級
                                echo "<td style='padding:15px; text-align:center;'><span style='font-family:\"JetBrains Mono\"; font-weight:bold; color:{$tier_color}; background:rgba(255,255,255,0.03); padding:4px 8px; border-radius:4px;'>Lvl {$tier}</span></td>";
                                
                                // 操作按鈕群
                                echo "<td style='padding:15px; text-align:right;'>
                                        <div style='display:flex; gap:8px; justify-content:flex-end;'>
                                            <a href='edit_product.php?product_id={$pid}' class='btn-action' style='color:#00f2fe; border-color:#00f2fe; padding:6px 14px; font-size:13px; text-decoration:none;'>Modify</a>
                                            <a href='manage_products.php?delete_id={$pid}' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 14px; font-size:13px; text-decoration:none;' onclick='return confirm(\"Are you absolutely sure you want to wipe this component from the core registry?\");'>Purge</a>
                                        </div>
                                      </td>";
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:40px; color:#64748b;'><i class='fas fa-microchip' style='font-size:24px; display:block; margin-bottom:10px;'></i> Silicon valley empty. No hardware registered in the matrix.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>