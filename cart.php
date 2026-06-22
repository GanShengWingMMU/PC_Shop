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


if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $qty_action = $_POST['qty_action'];

    if ($qty_action === 'plus') {
       
        $check_type = $conn->prepare("SELECT product_id, package_id, pc_build, quantity FROM shopping_cart WHERE cart_id = ? AND customer_id = ?");
        $check_type->bind_param("ii", $cart_id, $customer_id);
        $check_type->execute();
        $c_data = $check_type->get_result()->fetch_assoc();
        $check_type->close();
        
        if ($c_data) {
            $can_add = true;
            $new_qty = $c_data['quantity'] + 1;
            
            
            if ($c_data['product_id']) {
                $st_check = $conn->query("SELECT stock_quantity FROM products WHERE product_id = " . intval($c_data['product_id']));
                $st = $st_check->fetch_assoc();
                if ($new_qty > $st['stock_quantity']) $can_add = false;
            } elseif ($c_data['package_id']) {
                
                $st_check = $conn->query("SELECT MIN(FLOOR(p.stock_quantity / pi.quantity)) as max_pkg FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = " . intval($c_data['package_id']));
                $st = $st_check->fetch_assoc();
                if ($new_qty > $st['max_pkg']) $can_add = false;
            } elseif ($c_data['pc_build']) {
                
                $st_check = $conn->query("SELECT MIN(FLOOR(p.stock_quantity / bi.quantity)) as max_bld FROM build_items bi JOIN products p ON bi.product_id = p.product_id WHERE bi.pc_build = " . intval($c_data['pc_build']));
                $st = $st_check->fetch_assoc();
                if ($new_qty > $st['max_bld']) $can_add = false;
            }
            
            
            if ($can_add) {
                $conn->query("UPDATE shopping_cart SET quantity = $new_qty WHERE cart_id = $cart_id");
            } else {
                $_SESSION['error_msg'] = "Maximum available stock reached. Cannot add more.";
            }
        }
    } elseif ($qty_action === 'minus') {
        $update_stmt = $conn->prepare("UPDATE shopping_cart SET quantity = GREATEST(1, quantity - 1) WHERE cart_id = ? AND customer_id = ?");
        $update_stmt->bind_param("ii", $cart_id, $customer_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    header("Location: cart.php");
    exit();
}

$sql = "SELECT c.cart_id, c.quantity, c.product_id, c.pc_build, c.package_id, 
               p.product_name AS product_name, p.price AS product_price, p.image_url,
               b.build_name, b.total_price AS old_build_price,
               pk.package_name, pk.price AS old_package_price, pk.image_url AS package_image
        FROM shopping_cart c 
        LEFT JOIN products p ON c.product_id = p.product_id 
        LEFT JOIN saved_builds b ON c.pc_build = b.pc_build
        LEFT JOIN packages pk ON c.package_id = pk.package_id
        WHERE c.customer_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['pc_build'])) {
            $build_id = $row['pc_build'];
            $components = [];
            $dynamic_build_price = 0;
            
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
                $dynamic_build_price += ($c_row['price'] * $c_row['quantity']);
            }
            $c_stmt->close();
            
            $row['components'] = $components; 
            $row['build_price'] = $dynamic_build_price; 
            $total_price += ($dynamic_build_price * $row['quantity']);
            
        } elseif (!empty($row['package_id'])) {
            $pkg_id = $row['package_id'];
            $dynamic_pkg_price = 0;
            
            $pkg_sql = "SELECT p.price, pi.quantity FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = ?";
            $pkg_stmt = $conn->prepare($pkg_sql);
            $pkg_stmt->bind_param("i", $pkg_id);
            $pkg_stmt->execute();
            $pkg_res = $pkg_stmt->get_result();
            
            while ($p_row = $pkg_res->fetch_assoc()) {
                $dynamic_pkg_price += ($p_row['price'] * $p_row['quantity']); 
            }
            $pkg_stmt->close();
            
            $row['package_price'] = $dynamic_pkg_price; 
            $total_price += ($dynamic_pkg_price * $row['quantity']);
            
        } else {
            $total_price += ($row['product_price'] * $row['quantity']);
        }
        
        $cart_items[] = $row;
    }
    $stmt->close();
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
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px);
            z-index: 9999; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-overlay.show { display: flex; opacity: 1; }
        .build-modal {
            background: #11151c; border: 1px solid var(--accent-blue); border-radius: 12px;
            width: 90%; max-width: 600px; max-height: 80vh; display: flex; flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 243, 255, 0.2); transform: translateY(-20px);
            transition: transform 0.3s ease; position: relative;
        }
        .modal-overlay.show .build-modal { transform: translateY(0); }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: var(--accent-blue); font-size: 1.2rem; }
        .btn-close-modal { background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: 0.3s; }
        .btn-close-modal:hover { color: #ff4d4d; }
        .modal-body { padding: 20px 25px; overflow-y: auto; }
        .comp-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.1); }
        .comp-item:last-child { border-bottom: none; }
        .comp-icon { width: 40px; text-align: center; color: var(--text-muted); font-size: 1.2rem; }
        .comp-details { flex: 1; padding: 0 15px; }
        .comp-cat { font-size: 0.75rem; color: var(--accent-blue); text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
        .comp-name { font-size: 0.95rem; color: var(--text-main); margin-top: 3px; }
        .comp-price { font-size: 0.9rem; color: var(--text-muted); font-weight: bold; text-align: right; }

        .item-tag {
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
            padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 8px;
        }
        .tag-component { color: #00f2fe; background: rgba(0, 242, 254, 0.1); border: 1px solid rgba(0, 242, 254, 0.2); }
        .tag-package { color: #a855f7; background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2); }
        .tag-custom { color: #ffd700; background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.2); }
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
            <a href="javascript:void(0);" onclick="cyberConfirm('[WARNING] Purge all items from your payload? This action cannot be reversed.', function() { window.location.href='remove_cart.php?action=clear'; }, null, true);" style="color: #ff4d4d; border: 1px solid #ff4d4d; padding: 8px 15px; text-decoration: none; border-radius: 6px; transition: 0.3s; font-weight: bold; background: rgba(255, 77, 77, 0.05);" onmouseover="this.style.background='rgba(255, 77, 77, 0.15)'" onmouseout="this.style.background='rgba(255, 77, 77, 0.05)'">
                <i class="fa-solid fa-trash-can"></i> Remove All
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

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
                            <div class="cart-item-img" style="background: rgba(255, 215, 0, 0.05); border: 1px solid rgba(255, 215, 0, 0.2);">
                                <i class="fa-solid fa-screwdriver-wrench" style="font-size: 3rem; color: #ffd700;"></i>
                            </div>
                            <div class="cart-item-info">
                                <span class="item-tag tag-custom"><i class="fa-solid fa-gear"></i> Custom Rig</span>
                                <h4><?php echo htmlspecialchars($item['build_name'] ?? 'Custom PC'); ?></h4>
                                <div class="price">RM <?php echo number_format($item['build_price'], 2); ?></div>
                                
                                <button type="button" onclick="openModal('modal_<?php echo $item['pc_build']; ?>')" style="background: transparent; border: none; padding: 0; margin-top: 8px; font-size: 0.9rem; color: var(--text-muted); text-decoration: underline; cursor: pointer; transition: 0.3s;" onmouseover="this.style.color='#ffd700'" onmouseout="this.style.color='var(--text-muted)'">
                                    <i class="fa-solid fa-list"></i> View Configuration
                                </button>
                            </div>

                        <?php elseif (!empty($item['package_id'])): ?>
                            <div class="cart-item-img">
                                <img src="<?php echo htmlspecialchars($item['package_image'] ? $item['package_image'] : 'image/placeholder_pc.png'); ?>" alt="Package">
                            </div>
                            <div class="cart-item-info">
                                <span class="item-tag tag-package"><i class="fa-solid fa-box"></i> Pre-Built Package</span>
                                <h4><?php echo htmlspecialchars($item['package_name'] ?? 'PC Package'); ?></h4>
                                <div class="price">RM <?php echo number_format($item['package_price'], 2); ?></div>
                            </div>

                        <?php else: ?>
                            <div class="cart-item-img">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ? $item['image_url'] : 'image/placeholder.png'); ?>" alt="Product">
                            </div>
                            <div class="cart-item-info">
                                <span class="item-tag tag-component"><i class="fa-solid fa-microchip"></i> Component</span>
                                <h4><?php echo htmlspecialchars($item['product_name'] ?? 'PC Component'); ?></h4>
                                <div class="price">RM <?php echo number_format($item['product_price'], 2); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="cart-item-controls">
                            <div class="qty" style="background: rgba(255,255,255,0.05); padding: 5px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
                             <form method="POST" style="display: inline-block; margin: 0;">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="hidden" name="update_quantity" value="1">
                                <div class="quantity-badge" style="display: flex; align-items: center; gap: 10px; padding: 2px 10px;">
                                    <button type="submit" name="qty_action" value="minus" style="background: transparent; border: none; color: #fff; cursor: pointer; font-weight: bold; font-size: 1.1rem; transition: 0.2s;" onmouseover="this.style.color='#ff4d4d'" onmouseout="this.style.color='#fff'">-</button>
                                    
                                    <span style="color: var(--accent-blue); font-weight: bold; font-size: 1rem;"><?php echo $item['quantity']; ?></span>
                                    
                                    <button type="submit" name="qty_action" value="plus" style="background: transparent; border: none; color: #fff; cursor: pointer; font-weight: bold; font-size: 1.1rem; transition: 0.2s;" onmouseover="this.style.color='#00e676'" onmouseout="this.style.color='#fff'">+</button>
                                </div>
                            </form>
                            </div>
                            <a href="remove_cart.php?id=<?php echo $item['cart_id']; ?>" class="btn-remove" title="Remove Item" style="color: #ff4d4d; transition: 0.3s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="order-summary-column">
            <h3><i class="fa-solid fa-receipt" style="color: var(--accent-blue); margin-right: 10px;"></i> Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal</span>
                <span style="color: var(--text-main); font-weight: bold;">RM <?php echo number_format($total_price, 2); ?></span>
            </div>
<div style="display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center;">
    <span style="color: #888;">Shipping</span>
    <span style="color: #00e676; font-weight: 900; letter-spacing: 1px; font-family: 'JetBrains Mono', monospace;">FREE</span>
</div>
            <div class="summary-total" style="margin-top: 15px;">
                <span>Total</span>
                <span class="amount" style="color: #ffd700; font-size: 1.8rem; text-shadow: 0 0 10px rgba(255,215,0,0.2);">RM <?php echo number_format($total_price, 2); ?></span>
            </div>

            <?php if(!empty($cart_items)): ?>
                <a href="checkout.php" class="btn btn-primary" style="display: flex; justify-content: center; align-items: center; width: 100%; margin-top: 25px; padding: 15px; font-size: 1.1rem; box-sizing: border-box; background: var(--accent-blue); color: #000; border-radius: 8px; font-weight: 800; transition: 0.3s;" onmouseover="this.style.boxShadow='0 0 20px rgba(0, 243, 255, 0.4)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='none'; this.style.transform='none';">
                    Proceed to Checkout <i class="fa-solid fa-arrow-right" style="margin-left: 10px;"></i>
                </a>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php foreach ($cart_items as $item): ?>
    <?php if (!empty($item['pc_build']) && !empty($item['components'])): ?>
        <div id="modal_<?php echo $item['pc_build']; ?>" class="modal-overlay" onclick="closeModal(event, 'modal_<?php echo $item['pc_build']; ?>')">
            <div class="build-modal" onclick="event.stopPropagation();"> 
                <div class="modal-header">
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