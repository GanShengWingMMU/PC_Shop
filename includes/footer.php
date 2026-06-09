<style>
    /* 🌟 GridCitY 高定 Footer 樣式 */
    .site-footer {
        background-color: #0b0f17;
        border-top: 1px solid #1e293b;
        padding: 60px 20px 20px;
        color: #94a3b8;
        font-family: 'Inter', sans-serif;
        margin-top: auto;
        
        /* 🚀 橫向強制滿版黑科技（即使被卡在 container 內也能完美填滿畫面） */
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        box-sizing: border-box;
    }

    /* 🚀 同步清除全域 body 的瀏覽器預設邊距，並防止橫向出現滾動條 */
    body {
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden;
    }
    
    .footer-grid {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 40px;
        margin-bottom: 40px;
    }
    
    .footer-brand {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .footer-logo {
        font-size: 1.6rem;
        font-weight: 900;
        color: #fff;
        text-decoration: none;
        letter-spacing: -0.5px;
    }
    
    .footer-logo span {
        color: #00f2fe;
    }
    
    .footer-desc {
        font-size: 0.9rem;
        line-height: 1.6;
        max-width: 300px;
        color: #64748b;
    }
    
    .footer-heading {
        color: #fff;
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .footer-links a {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .footer-links a:hover {
        color: #00f2fe;
        transform: translateX(4px); /* 🌟 高级悬浮交互：平滑右移 */
    }
    
    .social-icons {
        display: flex;
        gap: 12px;
        margin-top: 10px;
    }
    
    .social-icon {
        width: 38px;
        height: 38px;
        background: #111827;
        border: 1px solid #1e293b;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        text-decoration: none;
        transition: 0.3s;
        font-size: 1.1rem;
    }
    
    .social-icon:hover {
        background: rgba(0, 242, 254, 0.1);
        border-color: #00f2fe;
        color: #00f2fe;
        transform: translateY(-3px);
    }
    
    .footer-bottom {
        max-width: 1200px;
        margin: 0 auto;
        padding-top: 24px;
        border-top: 1px solid #1e293b;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        color: #64748b;
    }
    
    .payment-methods {
        display: flex;
        gap: 12px;
        align-items: center;
        font-size: 1.5rem;
        color: #475569;
    }

    .admin-link {
        color: #64748b;
        text-decoration: none;
        transition: 0.3s;
        margin-left: 15px;
        padding-left: 15px;
        border-left: 1px solid #1e293b;
    }

    .admin-link:hover {
        color: #a855f7;
    }

    /* 移动端适配 */
    @media (max-width: 900px) {
        .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
        .footer-grid { grid-template-columns: 1fr; }
        .footer-bottom { flex-direction: column; gap: 15px; text-align: center; }
        .admin-link { border-left: none; margin-left: 0; padding-left: 0; display: block; margin-top: 10px; }
    }
</style>

<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="footer-logo">GridCitY <span>PC</span></a>
            <p class="footer-desc">Your ultimate destination for premium components, AI-powered builds, and an elite tech community.</p>
            <div class="social-icons">
                <a href="#" class="social-icon" title="Discord Community"><i class="fa-brands fa-discord"></i></a>
                <a href="#" class="social-icon" title="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" class="social-icon" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="social-icon" title="GitHub Source"><i class="fa-brands fa-github"></i></a>
            </div>
        </div>
        
        <div>
            <h4 class="footer-heading">Hardware & Tech</h4>
            <ul class="footer-links">
                <li><a href="components.php"><i class="fas fa-microchip" style="font-size:0.8rem; color:#475569;"></i> All Components</a></li>
                <li><a href="packages.php"><i class="fas fa-box" style="font-size:0.8rem; color:#475569;"></i> Pre-built Packages</a></li>
                <li><a href="builder.php"><i class="fas fa-tools" style="font-size:0.8rem; color:#475569;"></i> Custom PC Builder</a></li>
                <li><a href="community.php"><i class="fas fa-network-wired" style="font-size:0.8rem; color:#475569;"></i> Neural Network</a></li>
            </ul>
        </div>
        
        <div>
            <h4 class="footer-heading">My Account</h4>
            <ul class="footer-links">
                <li><a href="profile.php">Profile & Security</a></li>
                <li><a href="my_orders.php">Order History</a></li>
                <li><a href="vouchers.php">Vouchers & Promos</a></li>
                <li><a href="wallet_topup.php">Digital Wallet</a></li>
                <li><a href="membership.php" style="color: #d8b4fe;"><i class="fas fa-crown" style="font-size:0.8rem;"></i> Elite Membership</a></li>
            </ul>
        </div>
        
        <div>
            <h4 class="footer-heading">Support</h4>
            <ul class="footer-links">
                <li><a href="#"><i class="fa-solid fa-headset" style="color: #00f2fe;"></i> Help Center</a></li>
                <li><a href="#"><i class="fa-solid fa-envelope" style="color: #00f2fe;"></i> support@gridcity.com</a></li>
                <li><a href="#"><i class="fa-solid fa-location-dot" style="color: #00f2fe;"></i> Cyberjaya, Malaysia</a></li>
            </ul>
            <div style="margin-top: 15px; font-size: 0.8rem; color: #475569; font-weight: 600;">
                Mon-Fri: 9:00 AM - 6:00 PM (MYT)
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div>
            &copy; <?php echo date('Y'); ?> GridCitY PC. Crafted for FYP.
            <a href="admin_login.php" class="admin-link"><i class="fas fa-shield-halved"></i> Admin Portal</a>
        </div>
        <div class="payment-methods">
            <i class="fa-brands fa-cc-visa" title="Visa"></i>
            <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
            <i class="fa-solid fa-building-columns" title="FPX Online Banking"></i>
            <i class="fa-solid fa-wallet" title="Grid Coins Wallet"></i>
        </div>
    </div>
</footer>