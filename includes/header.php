<?php session_start(); require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ideal PC Shop - Custom Builds & Parts</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="index.php">IDEAL PC</a>
    </div>
    
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="components.php">Components</a>
        <a href="packages.php">Packages</a>
        <a href="builder.php" class="highlight-link"><i class="fas fa-tools"></i> PC Builder</a>
    </div>

    <div class="nav-actions">
        <a href="register.php" class="btn btn-outline" style="padding: 8px 16px;"><i class="fas fa-user-plus"></i> Register</a>
        <a href="cart.php" class="btn btn-primary" style="padding: 8px 16px;"><i class="fas fa-shopping-cart"></i> Cart (0)</a>
    </div>
</nav>

<main class="main-container">