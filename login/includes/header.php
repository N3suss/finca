<?php
// includes/header.php
if (!isset($title)) {
    $title = SITE_NAME;
}

$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/main.css">
</head>
<body>
    <header class="header">
        <div class="container header-container">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span><?= SITE_NAME ?></span>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name"><?= $currentUser['alias'] ?></span>
                    <span class="user-role"><?= strtoupper($currentUser['role']) ?></span>
                </div>
                <a href="<?= SITE_URL ?>logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>