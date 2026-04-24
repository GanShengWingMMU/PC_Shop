<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$customer_id = $_SESSION['customer_id'];
$cart_items = [];
$total_price = 0;

$sql = "SELECT c.cart_id, c.quantity, c.product_id, c.pc_build, 
               p.product_name AS product_name, p.price AS product_price, p.image_url,
               b.build_name, b.total_price AS build_price
        FROM shopping_cart c 
        LEFT JOIN products p ON c.product_id = p.product_id 
        LEFT JOIN saved_builds b ON c.pc_build = b.pc_build
        WHERE c.customer_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        
        if (!empty($row['pc_build'])) {
            $build_id = $row['pc_build'];
            $components = [];
            
            $comp_sql = "SELECT p.product_name, p.price, p.image_url, cat.category_name, bi.quantity 
                         FROM build_items bi
                         JOIN products p ON bi.product_id = p.product_id
                         JOIN categories cat ON p.category_id = cat.category_id
                         WHERE bi.pc_build = ?";
            
            $c_stmt = $conn->prepare($comp_sql);
            $c_stmt->bind_param("i", $build_id);
            $c_stmt->execute();
            $c_res = $c_stmt->get_result();
            while ($c_row = $c_res->fetch_assoc()) {
                $components[] = $c_row;
            }
            $c_stmt->close();
            
            // 把零件清單塞進這筆購物車資料裡
            $row['components'] = $components; 
            $total_price += ($row['build_price'] * $row['quantity']);
        } else {
            $total_price += ($row['product_price'] * $row['quantity']);
        }
        
        $cart_items[] = $row;
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
    <title>My Cart - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 🌟 彈出式視窗 (Modal) 的魔法 CSS 🌟 */
        .modal-overlay {
            display: none; /* 預設隱藏 */
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px); /* 磨砂玻璃背景 */
            z-index: 9999;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.show {
            display: flex;
            opacity: 1;
        }
        .build-modal {
            background: #11151c;
            border: 1px solid var(--accent-blue);
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 243, 255, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            position: relative;
        }
        .modal-overlay.show .build-modal {
            transform: translateY(0);
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; color: var(--accent-blue); font-size: 1.2rem; }
        .btn-close-modal {
            background: transparent; border: none; color: var(--text-muted);
            font-size: 1.5rem; cursor: pointer; transition: 0.3s;
        }
        .btn-close-modal:hover { color: #ff4d4d; }
        .modal-body {
            padding: 20px 25px;
            overflow-y: auto;
        }
        
        /* 零件清單排版 */
        .comp-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        }
        .comp-item:last-child { border-bottom: none; }
        .comp-icon { width: 40px; text-align: center; color: var(--text-muted); font-size: 1.2rem; }
        .comp-details { flex: 1; padding: 0 15px; }
        .comp-cat { font-size: 0.75rem; color: var(--accent-blue); text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
        .comp-name { font-size: 0.95rem; color: var(--text-main); margin-top: 3px; }
        .comp-price { font-size: 0.9rem; color: var(--text-muted); font-weight: bold; text-align: right; }
    </style>
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
                        
                        <?php if (!empty($item['pc_build'])): ?>
                            <div class="cart-item-img" style="background: rgba(0, 242, 254, 0.1);">
                                <i class="fa-solid fa-computer" style="font-size: 3rem; color: #00f2fe;"></i>
                            </div>
                            <div class="cart-item-info">
                                <h4 style="color: #00f2fe;"><i class="fa-solid fa-wrench"></i> <?php echo htmlspecialchars($item['build_name']); ?></h4>
                                <div class="price">RM <?php echo number_format($item['build_price'], 2); ?></div>
                                
                                <button type="button" onclick="openModal('modal_<?php echo $item['pc_build']; ?>')" style="background: transparent; border: none; padding: 0; margin-top: 8px; font-size: 0.9rem; color: var(--text-muted); text-decoration: underline; cursor: pointer; transition: 0.3s;" onmouseover="this.style.color='#00f2fe'" onmouseout="this.style.color='var(--text-muted)'">
                                    <i class="fa-solid fa-list"></i> View Configuration
                                </button>
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

<?php foreach ($cart_items as $item): ?>
    <?php if (!empty($item['pc_build']) && !empty($item['components'])): ?>
        <div id="modal_<?php echo $item['pc_build']; ?>" class="modal-overlay" onclick="closeModal(event, 'modal_<?php echo $item['pc_build']; ?>')">
            <div class="build-modal" onclick="event.stopPropagation();"> <div class="modal-header">
                    <h3><i class="fa-solid fa-microchip"></i> <?php echo htmlspecialchars($item['build_name']); ?> Components</h3>
                    <button class="btn-close-modal" onclick="forceClose('modal_<?php echo $item['pc_build']; ?>')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="modal-body">
                    <?php foreach ($item['components'] as $comp): ?>
                        <div class="comp-item">
                            <div class="comp-icon"><i class="fa-solid fa-check" style="color: #4CAF50;"></i></div>
                            <div class="comp-details">
                                <div class="comp-cat"><?php echo htmlspecialchars($comp['category_name']); ?></div>
                                <div class="comp-name"><?php echo htmlspecialchars($comp['product_name']); ?></div>
                            </div>
                            <div class="comp-price">
                                <?php echo $comp['quantity'] > 1 ? $comp['quantity'] . 'x ' : ''; ?>
                                RM <?php echo number_format($comp['price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="padding: 15px 25px; border-top: 1px solid rgba(255,255,255,0.1); text-align: right; background: rgba(0,0,0,0.2);">
                    <span style="color: var(--text-muted); font-size: 0.9rem;">Build Total: </span>
                    <strong style="color: var(--accent-blue); font-size: 1.2rem;">RM <?php echo number_format($item['build_price'], 2); ?></strong>
                </div>

            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('show');
        document.body.style.overflow = 'hidden'; 
    }

    function closeModal(event, modalId) {
        if (event.target.id === modalId) {
            forceClose(modalId);
        }
    }

    function forceClose(modalId) {
        document.getElementById(modalId).classList.remove('show');
        document.body.style.overflow = 'auto'; 
    }
</script>

</body>
</html>