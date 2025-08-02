<?php
// ==============================================
// CONFIGURACIÓN INICIAL Y MANEJO DE ERRORES
// ==============================================

// Mostrar errores solo en entorno de desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log');

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// ==============================================
// VALIDACIÓN DE PERMISOS Y SESIÓN
// ==============================================

try {
    // Verificar permisos
    if (!checkPermission('ovejas', 'ver')) {
        throw new Exception('No tienes permisos para ver el inventario de ovejas');
    }

$search = filter_input(INPUT_GET, 'search') ?? '';
$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1, 'min_range' => 1]
]);





    // Configuración de paginación
    $limit = 10;
    $offset = ($page - 1) * $limit;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /login/dashboard.php');
    exit();
}

// ==============================================
// CONSULTAS A LA BASE DE DATOS
// ==============================================

try {
    // Construir consulta principal con JOIN
    $query = "SELECT o.*, u.alias as creador 
              FROM inventario_ovejas o 
              LEFT JOIN users u ON o.created_by = u.id 
              WHERE 1=1";
    
    // Añadir condiciones de búsqueda
    if (!empty($search)) {
        $query .= " AND (o.codigo LIKE :search1 OR o.raza LIKE :search2 OR o.tipo_animal LIKE :search3)";
    }

    $query .= " ORDER BY o.conteo DESC LIMIT :limit OFFSET :offset";

    // Preparar consulta con parámetros nombrados
    $stmt = $pdo->prepare($query);
    
    // Asignar parámetros de búsqueda
    if (!empty($search)) {
        $stmt->bindValue(':search1', "%$search%");
        $stmt->bindValue(':search2', "%$search%");
        $stmt->bindValue(':search3', "%$search%");
    }
    
    // Asignar parámetros de paginación como enteros
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        throw new PDOException('Error al obtener el listado de ovejas');
    }
    $ovejas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para contar total de registros
    $countQuery = "SELECT COUNT(*) as total FROM inventario_ovejas";
    $countParams = [];
    
    if (!empty($search)) {
        $countQuery .= " WHERE codigo LIKE :search OR raza LIKE :search OR tipo_animal LIKE :search";
        $countParams[':search'] = "%$search%";
    }

    $stmt = $pdo->prepare($countQuery);
    if (!$stmt->execute($countParams)) {
        throw new PDOException('Error al contar ovejas');
    }
    
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
    error_log("Error en ovejas/index.php: " . $e->getMessage());
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error en ovejas/index.php: " . $e->getMessage());
}

// ==============================================
// CABECERA Y SIDEBAR
// ==============================================

$title = "Inventario de Ovejas";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ==============================================
     CONTENIDO PRINCIPAL
=============================================== -->
<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i></i></h1>
    </div>
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-paw"></i> Inventario de Ovejas</h1>
        <?php if (checkPermission('ovejas', 'crear')): ?>
        <a href="agregar.php" class="btn"><i class="fas fa-plus"></i> Nueva Oveja</a>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Listado de Ovejas</h3>
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="card-body">
            <!-- Mensajes de éxito -->
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                switch ($_GET['success']) {
                    case 'create': echo 'Oveja registrada exitosamente.'; break;
                    case 'update': echo 'Oveja actualizada exitosamente.'; break;
                    case 'delete': echo 'Oveja eliminada exitosamente.'; break;
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Mensajes de error -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                <?php if (isset($e) && ($e instanceof PDOException)): ?>
                <details style="margin-top: 10px;">
                    <summary>Detalles técnicos</summary>
                    <div style="margin-top: 5px; padding: 5px; background: #f8f9fa; border-radius: 3px;">
                        <small>
                            <strong>Error:</strong> <?= htmlspecialchars($e->getMessage()) ?><br>
                            <strong>Archivo:</strong> <?= htmlspecialchars($e->getFile()) ?>:<?= $e->getLine() ?><br>
                            <?php if (!empty($query)): ?>
                            <strong>Consulta:</strong> <?= htmlspecialchars($query) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabla de ovejas -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Sexo</th>
                            <th>Raza</th>
                            <th>Tipo</th>
                            <th>Edad</th>
                            <th>Estado</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ovejas)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay ovejas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ovejas as $oveja): ?>
                            <!-- Dentro del bucle foreach donde se muestran las ovejas -->
<tr>
    <td><?= htmlspecialchars($oveja['codigo']) ?></td>
    <td><?= htmlspecialchars($oveja['sexo']) ?></td>
    <td><?= htmlspecialchars($oveja['raza']) ?></td>
    <td><?= htmlspecialchars($oveja['tipo_animal']) ?></td>
    <td>
        <?php
        $edad = '';
        
        // Mostrar años si es mayor a 0
        if ($oveja['edad_anos'] > 0) {
            $edad .= $oveja['edad_anos'] . ' año' . ($oveja['edad_anos'] != 1 ? 's' : '');
        }
        
        // Mostrar meses si es mayor a 0
        if ($oveja['edad_meses'] > 0) {
            if (!empty($edad)) $edad .= ', ';
            $edad .= $oveja['edad_meses'] . ' mes' . ($oveja['edad_meses'] != 1 ? 'es' : '');
        }
        
        // Mostrar días si no hay años ni meses, o si hay días adicionales
        if ($oveja['edad_dias'] > 0 && (empty($edad) || $oveja['edad_anos'] == 0 && $oveja['edad_meses'] == 0)) {
            if (!empty($edad)) $edad .= ', ';
            $edad .= $oveja['edad_dias'] . ' día' . ($oveja['edad_dias'] != 1 ? 's' : '');
        }
        
        // Si todo es cero (recién nacido)
        if (empty($edad)) {
            $edad = 'Recién nacido';
        }
        
        echo $edad;
        ?>
    </td>
    <td><?= htmlspecialchars($oveja['estado_prenaz']) ?></td>
    <td><?= htmlspecialchars($oveja['creador'] ?? 'Sistema') ?></td>
    <td class="table-actions">
        <a href="editar.php?id=<?= $oveja['conteo'] ?>" class="btn btn-secondary btn-sm" title="Editar">
            <i class="fas fa-edit"></i>
        </a>
        <?php if (checkPermission('ovejas', 'eliminar')): ?>
        <a href="eliminar.php?id=<?= $oveja['conteo'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar esta oveja?')">
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
            
            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
                <?php endif; ?>
                
                <span>Página <?= $page ?> de <?= $totalPages ?></span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>