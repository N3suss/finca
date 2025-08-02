<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('usuarios', 'editar') || $_SESSION['user_role'] != 'maestro') {
    header('Location: /finca/dashboard.php');
    exit();
}

// Obtener usuario a editar permisos
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit();
}

// Definir módulos y acciones disponibles
$modules = [
    'agricola' => ['ver', 'crear', 'editar', 'eliminar'],
    'ovejas' => ['ver', 'crear', 'editar', 'eliminar'],
    'usuarios' => ['ver', 'crear', 'editar', 'eliminar']
];

// Obtener permisos actuales del usuario
$stmt = $pdo->prepare("SELECT * FROM user_permissions WHERE user_id = ?");
$stmt->execute([$user['id']]);
$currentPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar permisos en estructura más manejable
$userPermissions = [];
foreach ($currentPermissions as $perm) {
    $userPermissions[$perm['module']][$perm['action']] = (bool)$perm['allowed'];
}

// Procesar formulario de permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Eliminar permisos existentes
        $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$user['id']]);
        
        // Insertar nuevos permisos
        $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, module, action, allowed) VALUES (?, ?, ?, ?)");
        
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $allowed = isset($_POST['perms'][$module][$action]) ? 1 : 0;
                $stmt->execute([$user['id'], $module, $action, $allowed]);
            }
        }
        
        logActivity('usuarios', 'Actualizó permisos para: ' . $user['username']);
        
        header('Location: index.php?success=perms');
        exit();
    } catch (PDOException $e) {
        $error = "Error al actualizar permisos: " . $e->getMessage();
    }
}

$title = "Gestionar Permisos";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-key"></i> Gestionar Permisos</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Permisos para: <?= htmlspecialchars($user['fullname']) ?> (<?= htmlspecialchars($user['username']) ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="permissions-grid">
                    <?php foreach ($modules as $module => $actions): ?>
                    <div class="permission-module">
                        <h4>
                            <?php 
                            $moduleNames = [
                                'agricola' => 'Producción Agrícola',
                                'ovejas' => 'Inventario de Ovejas',
                                'usuarios' => 'Gestión de Usuarios'
                            ];
                            echo $moduleNames[$module] ?? ucfirst($module);
                            ?>
                        </h4>
                        
                        <div class="permission-actions">
                            <?php foreach ($actions as $action): ?>
                            <div class="permission-item">
                                <label>
                                    <input type="checkbox" name="perms[<?= $module ?>][<?= $action ?>]" 
                                           <?= isset($userPermissions[$module][$action]) && $userPermissions[$module][$action] ? 'checked' : '' ?>>
                                    <?php
                                    $actionNames = [
                                        'ver' => 'Ver',
                                        'crear' => 'Crear',
                                        'editar' => 'Editar',
                                        'eliminar' => 'Eliminar'
                                    ];
                                    echo $actionNames[$action] ?? ucfirst($action);
                                    ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-group text-right mt-4">
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Permisos</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.permission-module {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #eee;
}

.permission-module h4 {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    color: var(--primary-dark);
}

.permission-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.permission-item label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.permission-item input[type="checkbox"] {
    margin-right: 8px;
}
</style>

<?php require_once '../../includes/footer.php'; ?>