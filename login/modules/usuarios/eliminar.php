<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('usuarios', 'eliminar') || $_SESSION['user_role'] != 'maestro') {
    header('Location: /finca/dashboard.php');
    exit();
}

// Verificar que no sea el mismo usuario
if ($_GET['id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = "No puedes eliminarte a ti mismo";
    header('Location: index.php');
    exit();
}

// Obtener información del usuario a eliminar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit();
}

// Eliminar el usuario
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    try {
        // Eliminar permisos primero (por la restricción de clave foránea)
        $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$_GET['id']]);
        
        // Eliminar actividades
        $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?")->execute([$_GET['id']]);
        
        // Finalmente eliminar el usuario
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['id']]);
        
        logActivity('usuarios', 'Eliminó el usuario: ' . $user['username']);
        
        header('Location: index.php?success=delete');
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar usuario: " . $e->getMessage());
    }
}

$title = "Eliminar Usuario";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-user-times"></i> Eliminar Usuario</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Confirmar Eliminación</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <strong>¡Advertencia!</strong> Estás a punto de eliminar permanentemente este usuario y todos sus datos asociados.
            </div>
            
            <div class="user-details">
                <h4>Información del Usuario:</h4>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($user['fullname']) ?></p>
                <p><strong>Usuario:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Rol:</strong> <?= strtoupper($user['role']) ?></p>
                <p><strong>Registrado:</strong> <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
            </div>
            
            <div class="confirmation-buttons">
                <a href="eliminar.php?id=<?= $_GET['id'] ?>&confirm=true" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Confirmar Eliminación
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.user-details {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.user-details h4 {
    margin-bottom: 15px;
    color: var(--primary-dark);
}

.confirmation-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<?php require_once '../../includes/footer.php'; ?>