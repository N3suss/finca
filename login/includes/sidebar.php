<?php
// includes/sidebar.php
if (!isLoggedIn()) {
    return;
}
?>
<aside class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="<?= SITE_URL ?>dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        
        <?php if (checkPermission('agricola', 'ver')): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/agricola/" class="<?= strpos($_SERVER['REQUEST_URI'], 'agricola') !== false ? 'active' : '' ?>">
                <i class="fas fa-seedling"></i> Producción Agrícola
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (checkPermission('ovejas', 'ver')): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/ovejas/" class="<?= strpos($_SERVER['REQUEST_URI'], 'ovejas') !== false ? 'active' : '' ?>">
                <i class="fas fa-paw"></i> Inventario Ovejas
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] == 'maestro'): ?>
        <li>
            <a href="<?= SITE_URL ?>modules/usuarios/" class="<?= strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i> Gestión de Usuarios
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>