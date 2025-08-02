<?php
// Configuración inicial para mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar todas las excepciones no capturadas
set_exception_handler(function($exception) {
    error_log("Excepción no capturada: " . $exception->getMessage());
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-danger'><strong>Error crítico:</strong> " . htmlspecialchars($exception->getMessage()) . "</div>";
    } else {
        echo "<div class='alert alert-danger'>Ocurrió un error inesperado. Por favor contacte al administrador.</div>";
    }
});

// Registrar todos los errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error PHP [$errno] $errstr en $errfile línea $errline");
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-warning'><strong>Error:</strong> $errstr en $errfile línea $errline</div>";
    }
    return true;
});

// Configuración del directorio de logs
$logDir = __DIR__ . '/../../logs';
if (!file_exists($logDir)) {
    try {
        if (!mkdir($logDir, 0755, true)) {
            error_log("No se pudo crear el directorio de logs. Verifica los permisos.");
        }
    } catch (Exception $e) {
        error_log("Error al crear directorio de logs: " . $e->getMessage());
    }
}

// Habilitar el registro de errores
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/php_errors.log');

require_once '../../includes/config.php';
require_once '../../includes/auth.php';

try {
    if (!checkPermission('agricola', 'ver')) {
        header('Location: /login/dashboard.php');
        exit();
    }

    $search = $_GET['search'] ?? '';
    $page = $_GET['page'] ?? 1;
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Construir consulta con búsqueda
    $query = "SELECT p.*, u.alias as creador 
              FROM produccion_agricola p 
              LEFT JOIN users u ON p.created_by = u.id 
              WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (p.producto LIKE :search1 OR p.rubro LIKE :search2 OR p.ubicacion LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }

    // Filtro por fechas
    if (!empty($fecha_desde)) {
        $query .= " AND p.fecha >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $query .= " AND p.fecha <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }

    $query .= " ORDER BY p.fecha DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = (int)$limit;
    $params[':offset'] = (int)$offset;

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . implode(" ", $stmt->errorInfo()));
    }
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $countQuery = "SELECT COUNT(*) as total FROM produccion_agricola WHERE 1=1";
    $countParams = [];

    if (!empty($search)) {
        $countQuery .= " AND (producto LIKE ? OR rubro LIKE ? OR ubicacion LIKE ?)";
        $countParams = array_merge($countParams, array_fill(0, 3, "%$search%"));
    }

    if (!empty($fecha_desde)) {
        $countQuery .= " AND fecha >= ?";
        $countParams[] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $countQuery .= " AND fecha <= ?";
        $countParams[] = $fecha_hasta;
    }
    
    $stmt = $pdo->prepare($countQuery);
    if (!$stmt->execute($countParams)) {
        throw new Exception("Error al contar registros: " . implode(" ", $stmt->errorInfo()));
    }
    
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    die("<div class='alert alert-danger'><strong>Error de base de datos:</strong> " . htmlspecialchars($e->getMessage()) . "</div>");
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    die("<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>");
}

$title = "Producción Agrícola";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-seedling"></i> Producción Agrícola</h1>
        <?php if (checkPermission('agricola', 'crear')): ?>
        <a href="agregar.php" class="btn"><i class="fas fa-plus"></i> Nuevo Registro</a>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Listado de Producción</h3>
            <form method="get" class="search-form">
                <div class="filter-row">
                    <input type="text" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
                    <div class="date-filters">
                        <label>Desde:</label>
                        <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>">
                        <label>Hasta:</label>
                        <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
                    </div>
                    <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($search)): ?>
                        <a href="?" class="btn btn-secondary">Limpiar filtros</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                switch ($_GET['success']) {
                    case 'create': echo 'Registro creado exitosamente.'; break;
                    case 'update': echo 'Registro actualizado exitosamente.'; break;
                    case 'delete': echo 'Registro eliminado exitosamente.'; break;
                }
                ?>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Rubro</th>
                            <th>Ubicación</th>
                            <th>Cantidad</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay registros encontrados</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $registro): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($registro['fecha'])) ?></td>
                                <td><?= htmlspecialchars($registro['producto']) ?></td>
                                <td><?= htmlspecialchars($registro['rubro']) ?></td>
                                <td><?= htmlspecialchars($registro['ubicacion']) ?></td>
                                <td><?= $registro['cantidad'] ?> <?= htmlspecialchars($registro['unidad_medida']) ?></td>
                                <td><?= htmlspecialchars($registro['creador'] ?? 'Sistema') ?></td>
                                <td class="table-actions">
                                    <a href="editar.php?id=<?= $registro['id'] ?>" class="btn btn-secondary btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (checkPermission('agricola', 'eliminar')): ?>
                                    <a href="eliminar.php?id=<?= $registro['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este registro?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&fecha_desde=<?= urlencode($fecha_desde) ?>&fecha_hasta=<?= urlencode($fecha_hasta) ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
                <?php endif; ?>
                
                <span>Página <?= $page ?> de <?= $totalPages ?></span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&fecha_desde=<?= urlencode($fecha_desde) ?>&fecha_hasta=<?= urlencode($fecha_hasta) ?>" class="page-link">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Estilos para los filtros de fecha */
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.date-filters {
    display: flex;
    align-items: center;
    gap: 5px;
}

.date-filters label {
    margin: 0;
    white-space: nowrap;
}

.date-filters input[type="date"] {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.search-form button[type="submit"] {
    padding: 5px 15px;
    background: #4e73df;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.search-form button[type="submit"]:hover {
    background: #2e59d9;
}

.btn-secondary {
    padding: 5px 10px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}
</style>

<?php require_once '../../includes/footer.php'; ?>