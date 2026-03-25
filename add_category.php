<?php
session_start();
include 'db_connect.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // SQL Insert
    $sql = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_categories.php?msg=created");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Category - PC shop Admin</title>
    <link href="style.css" rel="stylesheet">
    <link href="admin_style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <style>
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-family: 'Inter', serif;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Inter', serif;
            box-sizing: border-box; 
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--gold-accent);
            outline: none;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <nav class="admin-sidebar">
        <div class="sidebar-header">
            <h2 class="logo" style="float:none; color:#fff;">LAOBEIJING</h2>
            <p>Administration</p>
        </div>
        <ul class="admin-menu">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li>
            <li><a href="manage_categories.php" class="active">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="manage_users.php">Users</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main class="admin-content">
        <header class="admin-header">
            <h2>Add New Category</h2>
            <a href="manage_categories.php" class="btn btn-ghost-edit" style="text-decoration:none;">&larr; Back</a>
        </header>

        <div class="form-card">
            <?php if(isset($error)) echo "<p style='color:red; background:#fee; padding:10px; border-radius:5px;'>$error</p>"; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" required placeholder="e.g., Classic Beds">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Short description..."></textarea>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-add" style="width:100%; padding:12px; font-size:1rem; cursor:pointer;">
                        Create Category
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>