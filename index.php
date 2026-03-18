<?php 
// 1. 引入头部 (Navbar 自动出现)
include 'includes/header.php'; 
?>

<div class="p-5 mb-4 bg-primary text-white rounded-3 shadow-sm">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">Build Your Dream PC Today</h1>
        <p class="col-md-8 fs-4">Explore our latest Intel Core Ultra & RTX 40 Series configurations. Use our smart builder to ensure 100% compatibility.</p>
        <a href="builder.php" class="btn btn-warning btn-lg fw-bold">Enter PC Builder ➡️</a>
    </div>
</div>

<h3 class="mb-4 border-bottom pb-2">Featured Packages</h3>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold">Level 1: Entry Gaming</h5>
                <p class="card-text text-muted">Intel i5-12400F | 16GB RAM | RTX 3060</p>
                <h4 class="text-primary mb-3">RM 2,500.00</h4>
                <a href="#" class="btn btn-outline-dark w-100">View Details</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold">Level 2: Esport Pro</h5>
                <p class="card-text text-muted">Intel i5-13400F | 32GB RAM | RTX 4060</p>
                <h4 class="text-primary mb-3">RM 3,500.00</h4>
                <a href="#" class="btn btn-dark w-100">Customize Package</a>
            </div>
        </div>
    </div>
</div>

<?php 
// 2. 引入底部 (Footer 自动出现)
include 'includes/footer.php'; 
?>