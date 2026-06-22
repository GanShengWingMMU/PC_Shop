<?php
session_start();

if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $message = "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> Category name cannot be empty.</div>";
    } else {
        // check is that have same name
        $check_stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "<div class='alert-warning'><i class='fas fa-radiation'></i> Warning: The category '{$name}' already exists in the registry!</div>";
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $name, $description);
            
            if ($insert_stmt->execute()) {
                //add success message
                header("Location: manage_categories.php?msg=created");
                exit();
            } else {
                $message = "<div class='alert-error'><i class='fas fa-bug'></i> System Error: Could not create category.</div>";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Define Category - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .alert-error { color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3); }
        .alert-warning { color: #facc15; background: rgba(250,204,21,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(250,204,21,0.3); }
    </style>
</head>
<body>
    <div class="admin-container">
       <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-network-wired"></i> Define New Node Category</h2>
                <a href="manage_categories.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Back to Ontology</a>
            </header>

            <?php echo $message; ?>

            <form method="POST" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); max-width: 600px;">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Category Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g., Motherboard" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Technical Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Brief description of the component type..." style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;"></textarea>
                </div>

                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #a855f7, #00f2fe); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;">
                    <i class="fas fa-plus-circle"></i> Initialize Category
                </button>
            </form>
        </div>
    </div>
</body>
</html>