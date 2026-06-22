<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }


$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$error = "";
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id <= 0) { header("Location: manage_categories.php"); exit(); }


$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cat) { header("Location: manage_categories.php"); exit(); }


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        $sql = "UPDATE categories SET category_name=?, description=? WHERE category_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $category_id);
        
        if ($stmt->execute()) {
            header("Location: manage_categories.php?msg=updated");
            exit();
        } else {
            $error = "Database Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error = "⚠️ Category Name cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Category - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
       
        .form-card { background: rgba(0,0,0,0.4); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); max-width: 800px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; box-sizing: border-box; }
        .form-control { background: rgba(0,0,0,0.5); color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; width: 100%; box-sizing: border-box; font-family: 'Inter', sans-serif; transition: 0.3s; }
        .form-control:focus { border-color: #00f2fe; outline: none; box-shadow: 0 0 10px rgba(0,242,254,0.2); }
        label { color: #00f2fe; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="admin-container" style="display: flex; min-height: 100vh; width: 100%;">
        
      <?php include 'admin_sidebar.php'; ?>
        <div class="admin-content" style="padding: 30px; flex: 1; width: 100%; box-sizing: border-box;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-edit"></i> Modify Category Definition</h2>
                <a href="manage_categories.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none; padding: 8px 15px; border: 1px solid #555; border-radius: 6px;">&larr; Abort</a>
            </header>

            <div class="form-card">
                <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; ?>

                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label>Category Name *</label>
                        <input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($cat['category_name']); ?>" required>
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($cat['description']); ?></textarea>
                    </div>

                    <button type="submit" name="update_category" style="width: 100%; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</body>
</html>