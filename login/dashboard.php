<?php
// dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Obtener estadísticas
$stats = [];
try {
    // Producción agrícola
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(cantidad) as cantidad FROM produccion_agricola");
    $stats['agricola'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ovejas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventario_ovejas");
    $stats['ovejas'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actividad reciente
    $stmt = $pdo->prepare("SELECT a.*, u.username, u.alias 
                          FROM user_activity a 
                          JOIN users u ON a.user_id = u.id 
                          ORDER BY a.created_at DESC 
                          LIMIT 5");
    $stmt->execute();
    $actividad = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener estadísticas: " . $e->getMessage());
}

$title = "Panel de Control";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i></i></h1>
    </div>
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Panel de Control</h1>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['agricola']['total'] ?></h3>
                <p>Registros Agrícolas</p>
            </div>
            <div class="stat-detail">
                <span><?= $stats['agricola']['cantidad'] ?? 0 ?> unidades</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-paw"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['ovejas']['total'] ?></h3>
                <p>Ovejas Registradas</p>
            </div>
            <div class="stat-detail">
                <span>Inventario</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['usuarios']['total'] ?></h3>
                <p>Usuarios</p>
            </div>
            <div class="stat-detail">
                <span>Sistema</span>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Actividad Reciente</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($actividad)): ?>
                        <p>No hay actividad reciente.</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($actividad as $item): ?>
                            <li>
                                <div class="activity-item">
                                    <div class="activity-user">
                                        <strong><?= htmlspecialchars($item['alias']) ?></strong>
                                        <small><?= htmlspecialchars($item['username']) ?></small>
                                    </div>
                                    <div class="activity-details">
                                        <p><?= htmlspecialchars($item['action']) ?></p>
                                        <small><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Resumen</h3>
                </div>
                <div class="card-body">
                    <p>Bienvenido al sistema de gestión de finca. Aquí podrás administrar:</p>
                    <ul>
                        <li>Producción agrícola</li>
                        <li>Inventario de ovejas</li>
                        <li>Usuarios y permisos</li>
                    </ul>
                    <p>Utiliza el menú lateral para navegar entre las diferentes secciones.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>