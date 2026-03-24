<?php
session_start();
require_once 'config.php';

// 🚨 登入狀態檢查
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit(); 
}

$customer_id = $_SESSION['customer_id'];
$cart_items = [];
$total_price = 0;

// 🛒 從資料庫撈取真實購物車資料 (JOIN products 表)
$sql = "SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price, p.image_url 
        FROM shopping_cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.customer_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += ($row['price'] * $row['quantity']);
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
    
    <div class="cart-header">
        <i class="fa-solid fa-cart-shopping"></i>
        <h2>MY CART</h2>
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
                        
                        <div class="cart-item-img">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ? $item['image_url'] : 'Image/placeholder.png'); ?>" alt="Product">
                        </div>

                        <div class="cart-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <div class="price">RM <?php echo number_format($item['price'], 2); ?></div>
                        </div>

                        <div class="cart-item-controls">
                            <div class="qty">
                                Qty: <strong><?php echo $item['quantity']; ?></strong>
                            </div>
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