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
        </div>
        
        <div>
            <h4 class="footer-heading">Hardware & Tech</h4>
            <ul class="footer-links">
                <li><a href="components.php">All Components</a></li>
                <li><a href="packages.php">Pre-built Packages</a></li>
                <li><a href="builder.php">Custom PC Builder</a></li>
                <li><a href="community.php">Neural Network</a></li>
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
    <div id="cyberSystemModal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
    <div id="cyberModalBox" style="background: rgba(10, 10, 15, 0.95); margin: 15vh auto; padding: 0; width: 90%; max-width: 450px; border-radius: 8px; border: 1px solid #00f2fe; overflow: hidden; box-shadow: 0 0 30px rgba(0, 242, 254, 0.2), inset 0 0 15px rgba(0, 242, 254, 0.05); animation: cyberModalFlicker 0.3s forwards;">
        <div style="padding: 15px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); background: rgba(255, 255, 255, 0.02); display: flex; align-items: center;">
            <h3 id="cyberModalTitle" style="margin: 0; color: #fff; font-weight: 800; font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; letter-spacing: 1px;">
                <i class="fas fa-terminal" style="color: #00f2fe; margin-right: 8px;"></i> SYSTEM PROMPT
            </h3>
        </div>
        <div style="padding: 25px 20px;">
            <p id="cyberModalMessage" style="color: #cbd5e1; font-family: 'JetBrains Mono', monospace; font-size: 0.95rem; line-height: 1.6; margin: 0 0 25px 0;"></p>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button id="cyberModalCancelBtn" style="background: transparent; color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 4px; cursor: pointer; font-family: 'JetBrains Mono', monospace; font-weight: bold; transition: 0.3s;">CANCEL</button>
                <button id="cyberModalConfirmBtn" style="background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-family: 'JetBrains Mono', monospace; font-weight: bold; transition: 0.3s;">ACKNOWLEDGE</button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes cyberModalFlicker { 0% { opacity: 0; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1); } }
    #cyberModalCancelBtn:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.3); }
</style>

<script>
    function cyberConfirm(message, confirmCallback, cancelCallback = null, isWarning = false) {
        const modal = document.getElementById('cyberSystemModal');
        const modalBox = document.getElementById('cyberModalBox');
        const title = document.getElementById('cyberModalTitle');
        const confirmBtn = document.getElementById('cyberModalConfirmBtn');
        const cancelBtn = document.getElementById('cyberModalCancelBtn');

        document.getElementById('cyberModalMessage').innerHTML = message;
        modal.style.display = 'block';

        if (isWarning) {
            modalBox.style.borderColor = '#ff4d4d';
            modalBox.style.boxShadow = '0 0 30px rgba(255, 77, 77, 0.2), inset 0 0 15px rgba(255, 77, 77, 0.05)';
            title.style.color = '#ff4d4d';
            title.innerHTML = '<i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i> CRITICAL WARNING';
            confirmBtn.style.borderColor = '#ff4d4d';
            confirmBtn.style.color = '#ff4d4d';
            confirmBtn.style.background = 'rgba(255, 77, 77, 0.1)';
            confirmBtn.innerHTML = 'EXECUTE PURGE';
            confirmBtn.onmouseover = () => { confirmBtn.style.background = '#ff4d4d'; confirmBtn.style.color = '#000'; confirmBtn.style.boxShadow = '0 0 20px rgba(255, 77, 77, 0.5)'; };
            confirmBtn.onmouseout = () => { confirmBtn.style.background = 'rgba(255, 77, 77, 0.1)'; confirmBtn.style.color = '#ff4d4d'; confirmBtn.style.boxShadow = 'none'; };
        } else {
            modalBox.style.borderColor = '#00f2fe';
            modalBox.style.boxShadow = '0 0 30px rgba(0, 242, 254, 0.2), inset 0 0 15px rgba(0, 242, 254, 0.05)';
            title.style.color = '#fff';
            title.innerHTML = '<i class="fas fa-terminal" style="color: #00f2fe; margin-right:8px;"></i> SYSTEM PROMPT';
            confirmBtn.style.borderColor = '#00f2fe';
            confirmBtn.style.color = '#00f2fe';
            confirmBtn.style.background = 'rgba(0, 242, 254, 0.1)';
            confirmBtn.innerHTML = 'ACKNOWLEDGE';
            confirmBtn.onmouseover = () => { confirmBtn.style.background = '#00f2fe'; confirmBtn.style.color = '#000'; confirmBtn.style.boxShadow = '0 0 20px rgba(0, 242, 254, 0.5)'; };
            confirmBtn.onmouseout = () => { confirmBtn.style.background = 'rgba(0, 242, 254, 0.1)'; confirmBtn.style.color = '#00f2fe'; confirmBtn.style.boxShadow = 'none'; };
        }

        confirmBtn.onclick = function() {
            modal.style.display = 'none';
            if(confirmCallback) confirmCallback();
        };
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
            if(cancelCallback) cancelCallback();
        };
    }
</script>
</footer>