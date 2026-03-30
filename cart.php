<?php
session_start();
require_once 'config.php';

// 1. 檢查是否登入 (沒登入踢回 login)
if (!isset($_SESSION['customer_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$customer_id = $_SESSION['customer_id'];
$cart_items = [];
$total_price = 0;

// ==========================================
// 🛒 從資料庫撈取購物車資料 (同時支援 單一零件 與 整台主機)
// ==========================================
$sql = "SELECT c.cart_id, c.quantity, c.product_id, c.build_id, 
               p.name AS product_name, p.price AS product_price, p.image_url,
               b.build_name, b.total_price AS build_price
        FROM shopping_cart c 
        LEFT JOIN products p ON c.product_id = p.product_id 
        LEFT JOIN saved_builds b ON c.build_id = b.build_id
        WHERE c.customer_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        // 判斷是主機還是零件，來計算總價
        if (!empty($row['build_id'])) {
            $total_price += ($row['build_price'] * $row['quantity']);
        } else {
            $total_price += ($row['product_price'] * $row['quantity']);
        }
    }
    $stmt->close();
} else {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - PC Store</title>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container cart-page-wrapper">
    
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        
        <div class="cart-header" style="margin-bottom: 0;">
            <i class="fa-solid fa-cart-shopping"></i>
            <h2>MY CART</h2>
        </div>
        
        <?php if(!empty($cart_items)): ?>
            <a href="remove_cart.php?action=clear" 
               onclick="return confirm('Are you sure you want to remove ALL items from your cart?');" 
               style="color: #ff4d4d; border: 1px solid #ff4d4d; padding: 8px 15px; text-decoration: none; border-radius: 6px; transition: 0.3s; font-weight: bold; background: rgba(255, 77, 77, 0.05);">
                <i class="fa-solid fa-trash-can"></i> Remove All
            </a>
        <?php endif; ?>

    </div>

    <div class="cart-layout">
        
        <div class="cart-items-column">
            
            <?php if(empty($cart_items)): ?>
                <div class="cart-empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any gear yet.</p>
                    <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
<?php foreach ($cart_items as $item): ?>
    <div class="cart-item-card">
        
        <?php if (!empty($item['build_id'])): ?>
            <div class="cart-item-img" style="background: rgba(0, 242, 254, 0.1);">
                <i class="fa-solid fa-computer" style="font-size: 3rem; color: #00f2fe;"></i>
            </div>
            <div class="cart-item-info">
                <h4 style="color: #00f2fe;"><i class="fa-solid fa-wrench"></i> <?php echo htmlspecialchars($item['build_name']); ?></h4>
                <div class="price">RM <?php echo number_format($item['build_price'], 2); ?></div>
                
                <a href="view_build.php?id=<?php echo $item['build_id']; ?>" style="display: inline-block; margin-top: 8px; font-size: 0.9rem; color: var(--text-muted); text-decoration: underline;">
                    <i class="fa-solid fa-list"></i> View Configuration
                </a>
            </div>

        <?php else: ?>
            <div class="cart-item-img">
                <img src="<?php echo htmlspecialchars($item['image_url'] ? $item['image_url'] : 'Image/placeholder.png'); ?>" alt="Product">
            </div>
            <div class="cart-item-info">
                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                <div class="price">RM <?php echo number_format($item['product_price'], 2); ?></div>
            </div>
        <?php endif; ?>

        <div class="cart-item-controls">
            <div class="qty">Qty: <strong><?php echo $item['quantity']; ?></strong></div>
            <a href="remove_cart.php?id=<?php echo $item['cart_id']; ?>" class="btn-remove" title="Remove Item">
                <i class="fa-solid fa-trash-can"></i>
            </a>
        </div>
    </div>
<?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="order-summary-column">
            <h3>Order Summary</h3>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span>RM <?php echo number_format($total_price, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span>Calculated at checkout</span>
            </div>
            
            <div class="summary-total">
                <span>Total</span>
                <span class="amount">RM <?php echo number_format($total_price, 2); ?></span>
            </div>

            <?php if(!empty($cart_items)): ?>
                <a href="checkout.php" class="btn btn-primary" style="display: block; width: 100%; text-align: center; margin-top: 25px; font-size: 1.1rem; box-sizing: border-box;">
                    Proceed to Checkout <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>