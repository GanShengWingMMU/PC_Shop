<?php
session_start();
require_once 'config.php';

// 1. 檢查登入狀態
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_msg = "";

// ==========================================
// 2. 處理新增地址請求 (POST Request)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 接收所有拆分的小欄位
    $recipient_name = trim(mysqli_real_escape_string($conn, $_POST['recipient_name']));
    $phone_number   = trim(mysqli_real_escape_string($conn, $_POST['phone_number']));
    $unit_number    = trim(mysqli_real_escape_string($conn, $_POST['unit_number']));
    $street_name    = trim(mysqli_real_escape_string($conn, $_POST['street_name']));
    $postcode       = trim(mysqli_real_escape_string($conn, $_POST['postcode']));
    $city           = trim(mysqli_real_escape_string($conn, $_POST['city']));
    $state          = trim(mysqli_real_escape_string($conn, $_POST['state']));
    
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // 🌟 核心魔法：將所有欄位組合成一個完美排版的完整地址 (Shipping Label Format)
    $full_address = "$recipient_name | $phone_number\n";
    if (!empty($unit_number)) {
        $full_address .= "$unit_number, ";
    }
    $full_address .= "$street_name\n$postcode $city, $state";

    if (!empty($recipient_name) && !empty($phone_number) && !empty($street_name) && !empty($postcode) && !empty($city)) {
        
        $conn->begin_transaction();

        try {
            $check_first = $conn->query("SELECT COUNT(*) as count FROM customer_addresses WHERE customer_id = $customer_id");
            $row = $check_first->fetch_assoc();
            if ($row['count'] == 0) {
                $is_default = 1; 
            }

            if ($is_default == 1) {
                $remove_default = "UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?";
                $stmt_remove = $conn->prepare($remove_default);
                $stmt_remove->bind_param("i", $customer_id);
                $stmt_remove->execute();
                $stmt_remove->close();
            }

            // 寫入組合好的 full_address
            $insert_query = "INSERT INTO customer_addresses (customer_id, full_address, is_default) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bind_param("isi", $customer_id, $full_address, $is_default);
            $stmt_insert->execute();
            $stmt_insert->close();

            $conn->commit();
            $_SESSION['success_msg'] = "New delivery address added successfully!";
            header("Location: profile.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error adding address. Please try again. Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Add New Address</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 雙欄排版小幫手 */
        .grid-2-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 600px) {
            .grid-2-cols { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-container" style="max-width: 650px;">
            
            <div class="auth-title">
                <h2><i class="fa-solid fa-map-location-dot"></i> New Delivery Address</h2>
                <p class="specs">Enter the exact shipping details for your PC parts.</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <p class="text-danger" style="margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></p>
            <?php endif; ?>

            <form action="add_address.php" method="POST" class="form">
                
                <h4 style="color: var(--accent-blue); margin-bottom: 15px; border-bottom: 1px solid rgba(0, 243, 255, 0.2); padding-bottom: 5px;">Contact Info</h4>
                <div class="grid-2-cols" style="margin-bottom: 20px;">
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="recipient_name">Full Name</label>
                        <input type="text" id="recipient_name" name="recipient_name" class="form-control" required placeholder="Receiver's Name">
                    </div>
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-control" required placeholder="e.g. 012-3456789">
                    </div>
                </div>

                <h4 style="color: var(--accent-blue); margin-bottom: 15px; border-bottom: 1px solid rgba(0, 243, 255, 0.2); padding-bottom: 5px;">Address Details</h4>
                
                <div class="form-group input-group">
                    <label class="form-label" for="unit_number">House / Unit / Block No. (Optional)</label>
                    <input type="text" id="unit_number" name="unit_number" class="form-control" placeholder="e.g. No. 12, Level 3">
                </div>

                <div class="form-group input-group">
                    <label class="form-label" for="street_name">Street Name / Building / Taman</label>
                    <input type="text" id="street_name" name="street_name" class="form-control" required placeholder="e.g. Jalan Multimedia, Cyberjaya">
                </div>

                <div class="grid-2-cols" style="margin-bottom: 20px;">
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode" class="form-control" required placeholder="e.g. 63100" maxlength="5">
                    </div>
                    <div class="form-group input-group" style="margin-bottom: 0;">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" required placeholder="e.g. Cyberjaya">
                    </div>
                </div>

                <div class="form-group input-group">
                    <label class="form-label" for="state">State</label>
                    <select id="state" name="state" class="form-control" required style="background-color: var(--bg-surface); color: var(--text-main);">
                        <option value="">Select State</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Melaka">Melaka</option>
                        <option value="Negeri Sembilan">Negeri Sembilan</option>
                        <option value="Pahang">Pahang</option>
                        <option value="Penang">Penang</option>
                        <option value="Perak">Perak</option>
                        <option value="Perlis">Perlis</option>
                        <option value="Sabah">Sabah</option>
                        <option value="Sarawak">Sarawak</option>
                        <option value="Selangor">Selangor</option>
                        <option value="Terengganu">Terengganu</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Putrajaya">Putrajaya</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 25px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                    <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-main); font-size: 0.95rem;">
                        <input type="checkbox" name="is_default" value="1" style="margin-right: 12px; width: 18px; height: 18px; accent-color: var(--accent-blue);">
                        Set as Default Delivery Address
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Address
                </button>
            </form>

            <div class="specs" style="margin-top: 1.5rem; text-align: center;">
                <a href="profile.php" class="highlight-link"><i class="fa-solid fa-arrow-left"></i> Cancel and Return</a>
            </div>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>