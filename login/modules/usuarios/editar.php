<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('usuarios', 'editar') || $_SESSION['user_role'] != 'maestro') {
    header('Location: /login/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Obtener datos del usuario a editar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $alias = trim($_POST['alias']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    try {
        // Actualizar datos básicos
        $updateData = [
            'username' => $username,
            'alias' => $alias,
            'fullname' => $fullname,
            'role' => $role,
            'id' => $user['id']
        ];
        
        $query = "UPDATE users SET username = :username, alias = :alias, 
                  fullname = :fullname, role = :role";
        
        // Actualizar contraseña si se proporcionó
        if (!empty($password)) {
            $query .= ", password = :password";
            $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($updateData);
        
        logActivity('usuarios', 'Actualizó el usuario: ' . $username);
        
        header('Location: index.php?success=update');
        exit();
    } catch (PDOException $e) {
        $error = "Error al actualizar el usuario: " . $e->getMessage();
    }
}

$title = "Editar Usuario";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
    </div>
        <div class="page-header">
        <h1 class="page-title"><i class="fas fa-user-edit"></i> Editar Usuario</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Información</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username">Usuario</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="alias">Alias (Nombre único)</label>
                            <input type="text" id="alias" name="alias" class="form-control" 
                                   value="<?= htmlspecialchars($user['alias']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fullname">Nombre Completo</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
                                   value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rol</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="maestro" <?= $user['role'] == 'maestro' ? 'selected' : '' ?>>Maestro</option>
                                <option value="administrador" <?= $user['role'] == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                <option value="usuario" <?= $user['role'] == 'usuario' ? 'selected' : '' ?>>Usuario</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Nueva Contraseña (dejar vacío para no cambiar)</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>