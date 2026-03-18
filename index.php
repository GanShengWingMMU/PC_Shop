<?php include 'includes/header.php'; ?>

<section class="tech-card" style="text-align: center; padding: 5rem 2rem; background: linear-gradient(180deg, rgba(20,20,20,1) 0%, rgba(0,80,120,0.2) 100%); margin-bottom: 3rem; border: 1px solid rgba(0, 242, 254, 0.2);">
    <h1 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem;">Craft Your <span style="color: var(--accent-blue);">Ultimate</span> Machine</h1>
    <p style="color: var(--text-muted); font-size: 1.2rem; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">
        Experience seamless compatibility checking and real-time wattage calculation with our advanced PC Builder engine.
    </p>
    <a href="builder.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px;">Launch PC Builder <i class="fas fa-rocket"></i></a>
</section>

<h2 class="section-title"><i class="fas fa-fire" style="color: #ff5e62;"></i> Featured Packages</h2>

<div class="card-grid">
    <div class="tech-card">
        <h3>Level 1: Entry Gaming</h3>
        <p class="specs">Intel Core i5-12400F<br>16GB DDR4 3200MHz<br>NVIDIA RTX 3060 12GB<br>512GB NVMe Gen3 SSD</p>
        <div class="price">RM 2,500.00</div>
        <div style="display: flex; gap: 10px;">
            <a href="#" class="btn btn-outline" style="flex: 1;">Details</a>
            <a href="#" class="btn btn-primary" style="flex: 1;">Buy Now</a>
        </div>
    </div>

    <div class="tech-card">
        <h3>Level 2: Esport Pro</h3>
        <p class="specs">Intel Core i5-13400F<br>32GB DDR5 5200MHz<br>NVIDIA RTX 4060 8GB<br>1TB NVMe Gen4 SSD</p>
        <div class="price">RM 3,500.00</div>
        <div style="display: flex; gap: 10px;">
            <a href="#" class="btn btn-outline" style="flex: 1;">Details</a>
            <a href="builder.php?package=2" class="btn btn-primary" style="flex: 1;">Customize</a>
        </div>
    </div>
    
    <div class="tech-card">
        <h3 style="color: var(--accent-purple);">Master: Extreme Loop</h3>
        <p class="specs">AMD Ryzen 7 7800X3D<br>64GB DDR5 6000MHz<br>NVIDIA RTX 4080 SUPER<br>Custom Hard-Tube Water Cooling</p>
        <div class="price">RM 12,800.00</div>
        <a href="#" class="btn btn-outline" style="width: 100%;"><i class="fas fa-envelope"></i> Request Consultation</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>