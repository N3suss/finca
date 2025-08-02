<?php
// ==============================================
// CONFIGURACIÓN INICIAL Y MANEJO DE ERRORES
// ==============================================

// Mostrar errores solo en entorno de desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/error.log');

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// ==============================================
// VALIDACIÓN DE PERMISOS Y SESIÓN
// ==============================================

try {
    // Verificar permisos
    if (!checkPermission('usuarios', 'ver')) {
        throw new Exception('No tienes permisos para acceder a esta sección');
    }

    // Solo el usuario maestro puede gestionar usuarios
    if ($_SESSION['user_role'] != 'maestro') {
        throw new Exception('Acceso restringido: solo usuarios maestros');
    }

// Validar parámetros GET
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
    // Construir consulta principal
    $query = "SELECT * FROM users WHERE 1=1";
    
    // Añadir condiciones de búsqueda
    if (!empty($search)) {
        $query .= " AND (username LIKE :search1 OR alias LIKE :search2 OR fullname LIKE :search3)";
    }

    $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    // Preparar consulta
    $stmt = $pdo->prepare($query);
    
    // Asignar parámetros
    if (!empty($search)) {
        $stmt->bindValue(':search1', "%$search%");
        $stmt->bindValue(':search2', "%$search%");
        $stmt->bindValue(':search3', "%$search%");
    }
    
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        throw new PDOException('Error al obtener listado de usuarios');
    }
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Consulta para contar total de registros
    $countQuery = "SELECT COUNT(*) as total FROM users";
    $countParams = [];
    
    if (!empty($search)) {
        $countQuery .= " WHERE username LIKE ? OR alias LIKE ? OR fullname LIKE ?";
        $countParams = array_fill(0, 3, "%$search%");
    }

    $stmt = $pdo->prepare($countQuery);
    if (!$stmt->execute($countParams)) {
        throw new PDOException('Error al contar usuarios');
    }
    
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
    error_log("Error en usuarios/index.php: " . $e->getMessage());
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error en usuarios/index.php: " . $e->getMessage());
}

// ==============================================
// CABECERA Y SIDEBAR
// ==============================================

$title = "Gestión de Usuarios";
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
    </div>
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-users"></i> Gestión de Usuarios</h1>
        <?php if (checkPermission('usuarios', 'crear')): ?>
        <a href="agregar.php" class="btn"><i class="fas fa-plus"></i> Nuevo Usuario</a>
        <?php endif; ?>
        
        <?php if (checkPermission('usuarios', 'crear')): ?>
        <a href="../agricola/admin_options.php" class="btn"><i class="fas fa-plus"></i> Secciones Agricolas</a>
        <?php endif; ?>
        
        <?php if (checkPermission('usuarios', 'crear')): ?>
        <a href="../agricola/cierre_agricola.php" class="btn"><i class="fas fa-plus"></i> Cierre Agricola </a>
        <?php endif; ?>
        
        <?php if (checkPermission('usuarios', 'crear')): ?>
        <a href="../ovejas/admin_options_ovejas.php" class="btn"><i class="fas fa-plus"></i> Secciones Pecuarias</a>
        <?php endif; ?>
        
        <?php if (checkPermission('usuarios', 'crear')): ?>
        <a href="../ovejas/cierres/dashboard_cierres.php" class="btn"><i class="fas fa-plus"></i> Cierre Pecuario </a>
        <?php endif; ?>
        
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Listado de Usuarios</h3>
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
                    case 'create': echo 'Usuario creado exitosamente.'; break;
                    case 'update': echo 'Usuario actualizado exitosamente.'; break;
                    case 'delete': echo 'Usuario eliminado exitosamente.'; break;
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
            
            <!-- Tabla de usuarios -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Alias</th>
                            <th>Nombre Completo</th>
                            <th>Rol</th>
                            <th>Registrado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay usuarios registrados</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['username']) ?></td>
                                <td><?= htmlspecialchars($usuario['alias']) ?></td>
                                <td><?= htmlspecialchars($usuario['fullname']) ?></td>
                                <td>
                                    <span class="badge <?= $usuario['role'] == 'maestro' ? 'badge-primary' : ($usuario['role'] == 'administrador' ? 'badge-success' : 'badge-secondary') ?>">
                                        <?= strtoupper($usuario['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                                <td class="table-actions">
                                    <a href="editar.php?id=<?= $usuario['id'] ?>" class="btn btn-secondary btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="permisos.php?id=<?= $usuario['id'] ?>" class="btn btn-info btn-sm" title="Permisos">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <?php if ($usuario['id'] != $_SESSION['user_id'] && checkPermission('usuarios', 'eliminar')): ?>
                                    <a href="eliminar.php?id=<?= $usuario['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
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