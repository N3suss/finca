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

<main class="ml-64 mt-16 p-6">
    <h1 class="text-2xl font-bold mb-6 flex items-center"><i class="fas fa-tachometer-alt mr-2"></i> Panel de Control</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="flex items-center">
                <div class="text-green-600 text-3xl"><i class="fas fa-seedling"></i></div>
                <div class="ml-4">
                    <h3 class="text-xl font-semibold"><?= $stats['agricola']['total'] ?></h3>
                    <p class="text-gray-600">Registros Agrícolas</p>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                <?= $stats['agricola']['cantidad'] ?? 0 ?> unidades
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="flex items-center">
                <div class="text-green-600 text-3xl"><i class="fas fa-paw"></i></div>
                <div class="ml-4">
                    <h3 class="text-xl font-semibold"><?= $stats['ovejas']['total'] ?></h3>
                    <p class="text-gray-600">Ovejas Registradas</p>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                Inventario
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="flex items-center">
                <div class="text-green-600 text-3xl"><i class="fas fa-users"></i></div>
                <div class="ml-4">
                    <h3 class="text-xl font-semibold"><?= $stats['usuarios']['total'] ?></h3>
                    <p class="text-gray-600">Usuarios</p>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                Sistema
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded shadow">
            <div class="border-b px-4 py-2">
                <h3 class="font-semibold flex items-center"><i class="fas fa-history mr-2"></i> Actividad Reciente</h3>
            </div>
            <div class="p-4">
                <?php if (empty($actividad)): ?>
                    <p>No hay actividad reciente.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($actividad as $item): ?>
                        <li>
                            <div class="flex justify-between">
                                <div>
                                    <strong><?= htmlspecialchars($item['alias']) ?></strong>
                                    <small class="text-gray-500 ml-2"><?= htmlspecialchars($item['username']) ?></small>
                                </div>
                                <div class="text-right">
                                    <p><?= htmlspecialchars($item['action']) ?></p>
                                    <small class="text-gray-500"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded shadow">
            <div class="border-b px-4 py-2">
                <h3 class="font-semibold flex items-center"><i class="fas fa-chart-line mr-2"></i> Resumen</h3>
            </div>
            <div class="p-4">
                <p>Bienvenido al sistema de gestión de finca. Aquí podrás administrar:</p>
                <ul class="list-disc pl-6">
                    <li>Producción agrícola</li>
                    <li>Inventario de ovejas</li>
                    <li>Usuarios y permisos</li>
                </ul>
                <p class="mt-2">Utiliza el menú lateral para navegar entre las diferentes secciones.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

