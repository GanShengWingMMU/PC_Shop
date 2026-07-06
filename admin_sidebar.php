<?php
$current_page = basename($_SERVER['PHP_SELF']);
$sidebar_role = strtolower($_SESSION['admin_role'] ?? $_SESSION['role'] ?? '');

if (!function_exists('isActive')) {
    function isActive($page, $current) { return ($page === $current) ? 'active' : ''; }
    function getStyle($page, $current) { return ($page === $current) ? 'color: #facc15; border-left-color: #facc15;' : ''; }
}
?>

<nav class="admin-sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-shield-alt"></i> GridCity PC Admin</h3>
        <p style="color:#555; font-size:11px; font-family:'JetBrains Mono'; margin:5px 0 0 0;">Unified Architecture v4.0</p>
    </div>

    <!-- 🌟 这里是新增的滚动区域 -->
    <div class="sidebar-scroll">
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="<?= isActive('admin_dashboard.php', $current_page) ?>" style="<?= getStyle('admin_dashboard.php', $current_page) ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            
            <?php if ($sidebar_role === 'superadmin'): ?>
                <li><a href="manage_staff.php" class="<?= isActive('manage_staff.php', $current_page) ?>" style="<?= getStyle('manage_staff.php', $current_page) ?>"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
            <?php endif; ?>
            
            <li><a href="manage_users.php" class="<?= isActive('manage_users.php', $current_page) ?>" style="<?= getStyle('manage_users.php', $current_page) ?>"><i class="fas fa-users"></i> Manage Customers</a></li>
            <li><a href="manage_categories.php" class="<?= isActive('manage_categories.php', $current_page) ?>" style="<?= getStyle('manage_categories.php', $current_page) ?>"><i class="fas fa-layer-group"></i> Categories</a></li>
            <li><a href="manage_products.php" class="<?= isActive('manage_products.php', $current_page) ?>" style="<?= getStyle('manage_products.php', $current_page) ?>"><i class="fas fa-microchip"></i> Products</a></li> 
            <li><a href="manage_packages.php" class="<?= isActive('manage_packages.php', $current_page) ?>" style="<?= getStyle('manage_packages.php', $current_page) ?>"><i class="fas fa-boxes"></i> Packages</a></li>
            <li><a href="manage_orders.php" class="<?= isActive('manage_orders.php', $current_page) ?>" style="<?= getStyle('manage_orders.php', $current_page) ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="manage_vouchers.php" class="<?= isActive('manage_vouchers.php', $current_page) ?>" style="<?= getStyle('manage_vouchers.php', $current_page) ?>"><i class="fas fa-ticket-alt"></i> Promo Protocols</a></li>
            <li><a href="manage_coins.php" class="<?= isActive('manage_coins.php', $current_page) ?>" style="<?= getStyle('manage_coins.php', $current_page) ?>"><i class="fas fa-coins"></i> Coin Ledger</a></li>
            <li><a href="manage_forum.php" class="<?= isActive('manage_forum.php', $current_page) ?>" style="<?= getStyle('manage_forum.php', $current_page) ?>"><i class="fas fa-satellite-dish"></i> Nexus Moderation</a></li>
        </ul>
    </div>

    <!-- 🌟 这里是固定在底部的退出按钮 -->
    <div class="logout-btn">
        <a href="admin_logout.php" style="display:block; padding:18px 20px; color:#ff4d4d; font-weight:600; text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Log out
        </a>
    </div>
</nav>