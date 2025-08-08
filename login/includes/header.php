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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/main.css">
</head>
<body class="bg-gray-100">
    <header class="bg-green-700 text-white p-3 shadow-md fixed top-0 inset-x-0 z-50 border-b-4 border-green-900">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center text-lg font-semibold">
                <i class="fas fa-leaf mr-2"></i>
                <span><?= SITE_NAME ?></span>
            </div>

            <?php if (isLoggedIn()): ?>
            <div class="flex items-center gap-4">
                <div class="text-right leading-tight">
                    <span class="block font-medium"><?= $currentUser['alias'] ?></span>
                    <span class="text-xs opacity-90"><?= strtoupper($currentUser['role']) ?></span>
                </div>
                <a href="<?= SITE_URL ?>logout.php" class="bg-green-900 hover:bg-green-950 text-white px-3 py-1 rounded flex items-center gap-1 text-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

