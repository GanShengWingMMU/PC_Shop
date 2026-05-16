<?php
session_start();
require_once 'config.php';

// 1. 檢查登入狀態
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$error_msg = "";
$success_msg = "";

// 2. 抓取要評價的商品資訊
$stmt = $conn->prepare("SELECT product_name, image_url FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}

// 🌟 安全修復 1：驗證「購買真實性」防禦越權刷評 (Broken Access Control)
$verify_purchase = $conn->prepare("
    SELECT o.order_id FROM orders o 
    JOIN order_details od ON o.order_id = od.order_id 
    WHERE o.customer_id = ? AND od.product_id = ? AND o.order_status = 'Completed' 
    LIMIT 1
");
$verify_purchase->bind_param("ii", $customer_id, $product_id);
$verify_purchase->execute();
$has_purchased = $verify_purchase->get_result()->num_rows > 0;
$verify_purchase->close();

// 3. 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    
    // 🌟 Bug 修復 2：移除 mysqli_real_escape_string 防止雙重轉義導致的斜槓亂碼
    $comment = htmlspecialchars(trim($_POST['comment']));

    if (!$has_purchased) {
        $error_msg = "SECURITY ALERT: You can only review products you have purchased and received.";
    } elseif ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        // 檢查是否已經評價過
        $check_stmt = $conn->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND customer_id = ?");
        $check_stmt->bind_param("ii", $product_id, $customer_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_msg = "You have already reviewed this product.";
        } else {
            // 新增評價
            $insert_stmt = $conn->prepare("INSERT INTO reviews (product_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiis", $product_id, $customer_id, $rating, $comment);
            if ($insert_stmt->execute()) {
                $success_msg = "Thank you for your feedback! Your review has been submitted.";
            } else {
                $error_msg = "Error submitting review. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        $error_msg = "Please select a star rating and write a comment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Feedback - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-container" style="max-width: 600px; text-align: center;">
            
            <div class="auth-title">
                <h2><i class="fa-solid fa-comment-dots"></i> Leave Feedback</h2>
                <p class="specs">Tell us what you think about your purchase.</p>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                <?php if($product['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product" style="max-width: 100px; border-radius: 8px; margin-bottom: 10px;">
                <?php endif; ?>
                <h3 style="color: var(--text-main);"><?php echo htmlspecialchars($product['product_name']); ?></h3>
            </div>

            <?php if (!empty($error_msg)): ?>
                <p class="text-danger" style="margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></p>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="cart-empty-state" style="border-color: #4CAF50; padding: 20px;">
                    <i class="fa-solid fa-circle-check" style="color: #4CAF50; font-size: 3rem; margin-bottom: 10px;"></i>
                    <p style="color: #4CAF50; font-weight: bold; font-size: 1.2rem; margin: 0;"><?php echo $success_msg; ?></p>
                    <a href="my_orders.php" class="btn btn-primary" style="margin-top: 20px;">Return to Orders</a>
                </div>
            <?php elseif (!$has_purchased && empty($error_msg)): ?>
                <div class="cart-empty-state" style="border-color: #ff4d4d; padding: 20px;">
                    <i class="fa-solid fa-lock" style="color: #ff4d4d; font-size: 3rem; margin-bottom: 10px;"></i>
                    <p style="color: #ff4d4d; font-weight: bold; font-size: 1.1rem; margin: 0;">Verified Buyers Only</p>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">You must purchase and receive this item before leaving a review.</p>
                    <a href="my_orders.php" class="btn btn-secondary-action" style="margin-top: 20px; border: 1px solid #ff4d4d; color: #ff4d4d;">Check My Orders</a>
                </div>
            <?php else: ?>

                <form action="leave_review.php?product_id=<?php echo $product_id; ?>" method="POST" class="form" style="text-align: left;">
                    
                    <label class="form-label">Rate this product</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5" title="5 stars"><i class="fa-solid fa-star"></i></label>
                        
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 stars"><i class="fa-solid fa-star"></i></label>
                        
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 stars"><i class="fa-solid fa-star"></i></label>
                        
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 stars"><i class="fa-solid fa-star"></i></label>
                        
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 star"><i class="fa-solid fa-star"></i></label>
                    </div>

                    <div class="form-group input-group">
                        <label class="form-label" for="comment">Your Feedback</label>
                        <textarea id="comment" name="comment" class="form-control" rows="5" required placeholder="What did you like or dislike?"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>

            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>