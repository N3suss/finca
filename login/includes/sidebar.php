<?php
// includes/sidebar.php
if (!isLoggedIn()) {
    return;
}
?>
<aside class="bg-white w-64 fixed top-16 left-0 bottom-0 shadow-md border-r overflow-y-auto">
    <ul class="p-4 space-y-1">
        <li>
            <a href="<?= SITE_URL ?>dashboard.php" class="flex items-center px-3 py-2 rounded hover:bg-green-50 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-green-100 text-green-700 font-medium' : '' ?>">
                <i class="fas fa-tachometer-alt w-5 mr-2 text-green-700"></i> Dashboard
            </a>
        </li>

        <?php if (checkPermission('agricola', 'ver')): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/agricola/" class="flex items-center px-3 py-2 rounded hover:bg-green-50 <?= strpos($_SERVER['REQUEST_URI'], 'agricola') !== false ? 'bg-green-100 text-green-700 font-medium' : '' ?>">
                <i class="fas fa-seedling w-5 mr-2 text-green-700"></i> Producción Agrícola
            </a>
        </li>
        <?php endif; ?>

        <?php if (checkPermission('ovejas', 'ver')): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/ovejas/" class="flex items-center px-3 py-2 rounded hover:bg-green-50 <?= strpos($_SERVER['REQUEST_URI'], 'ovejas') !== false ? 'bg-green-100 text-green-700 font-medium' : '' ?>">
                <i class="fas fa-paw w-5 mr-2 text-green-700"></i> Inventario Ovejas
            </a>
        </li>
        <?php endif; ?>

        <?php if ($_SESSION['user_role'] == 'maestro'): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/usuarios/" class="flex items-center px-3 py-2 rounded hover:bg-green-50 <?= strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false ? 'bg-green-100 text-green-700 font-medium' : '' ?>">
                <i class="fas fa-users-cog w-5 mr-2 text-green-700"></i> Gestión de Usuarios
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>

