<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

if (empty($_SESSION['pc_build'])) { 
    header("Location: builder.php"); 
    exit(); 
}

$total_price = 0;
foreach ($_SESSION['pc_build'] as $part) {
    $total_price += $part['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Name Your Rig</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-container">
            
            <div class="auth-title">
                <h2><i class="fa-solid fa-server"></i> Name Your Custom Rig</h2>
                <p class="specs">Every legendary build deserves a name. What will you call yours?</p>
            </div>

            <div class="cart-empty-state" style="margin-bottom: 25px; padding: 1.5rem;">
                <span class="specs">Total Build Value:</span>
                <span class="price" style="font-size: 1.5rem; display: block; margin-top: 5px;">RM <?php echo number_format($total_price, 2); ?></span>
            </div>

            <form action="add_build_to_cart.php" method="POST" class="form">
                
                <div class="form-group input-group">
                    <label class="form-label" for="build_name">Build Name (Optional)</label>
                    <input type="text" id="build_name" name="build_name" class="form-control" placeholder="e.g. Project Midnight, Titan V..." maxlength="50" autocomplete="off" autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login" style="margin-top: 20px;">
                    <i class="fa-solid fa-cart-plus"></i> Save & Add to Cart
                </button>
            </form>

            <div class="specs" style="margin-top: 1.5rem; text-align: center;">
                <a href="builder.php" class="highlight-link"><i class="fa-solid fa-arrow-left"></i> Back to Editing</a>
            </div>

        </div>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>