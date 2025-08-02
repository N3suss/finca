<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Solo el usuario maestro puede crear nuevos usuarios
if (!checkPermission('usuarios', 'crear') || $_SESSION['user_role'] != 'maestro') {
    header('Location: /login/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $alias = trim($_POST['alias']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    
    // Validaciones
    if (empty($username) || empty($password) || empty($alias) || empty($fullname)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        try {
            // Verificar si el usuario o alias ya existen
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR alias = ?");
            $stmt->execute([$username, $alias]);
            
            if ($stmt->fetch()) {
                $error = 'El nombre de usuario o alias ya está en uso';
            } else {
                // Crear el nuevo usuario
                $stmt = $pdo->prepare("INSERT INTO users 
                                      (username, password, alias, fullname, role) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $alias,
                    $fullname,
                    $role
                ]);
                
                $newUserId = $pdo->lastInsertId();
                
                // Registrar la actividad
                logActivity('usuarios', 'Creó un nuevo usuario: ' . $username);
                
                // Redirigir a la gestión de permisos
                header('Location: permisos.php?id=' . $newUserId . '&new=true');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error al crear el usuario: " . $e->getMessage();
        }
    }
}

$title = "Agregar Nuevo Usuario";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-user-plus"></i> Agregar Nuevo Usuario</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Información del Nuevo Usuario</h3>
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
                            <label for="username"><i class="fas fa-user"></i> Nombre de Usuario</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            <small class="text-muted">Nombre para iniciar sesión (sin espacios)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="alias"><i class="fas fa-id-card"></i> Alias</label>
                            <input type="text" id="alias" name="alias" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['alias'] ?? '') ?>" required>
                            <small class="text-muted">Nombre único para identificar al usuario en registros</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fullname"><i class="fas fa-signature"></i> Nombre Completo</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Rol</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="usuario" <?= ($_POST['role'] ?? '') == 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="administrador" <?= ($_POST['role'] ?? '') == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                <option value="maestro" <?= ($_POST['role'] ?? '') == 'maestro' ? 'selected' : '' ?>>Maestro (Acceso total)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <small id="strength-text">Seguridad de la contraseña</small>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Longitud mínima
        if (password.length >= 8) strength += 1;
        
        // Contiene números
        if (/\d/.test(password)) strength += 1;
        
        // Contiene mayúsculas
        if (/[A-Z]/.test(password)) strength += 1;
        
        // Contiene caracteres especiales
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Actualizar barra de fuerza
        strengthBar.style.width = (strength * 25) + '%';
        
        // Actualizar colores y texto
        switch(strength) {
            case 0:
            case 1:
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#dc3545';
                break;
            case 2:
                strengthBar.style.backgroundColor = '#fd7e14';
                strengthText.textContent = 'Moderada';
                strengthText.style.color = '#fd7e14';
                break;
            case 3:
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#ffc107';
                break;
            case 4:
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Muy fuerte';
                strengthText.style.color = '#28a745';
                break;
        }
    });
});
</script>

<style>
.password-strength {
    margin-top: -10px;
    margin-bottom: 20px;
}

.strength-meter {
    height: 5px;
    background: #e9ecef;
    border-radius: 3px;
    margin: 5px 0;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0;
    transition: width 0.3s ease, background-color 0.3s ease;
    border-radius: 3px;
}

#strength-text {
    display: block;
    font-size: 0.8rem;
    transition: color 0.3s ease;
}
</style>

<?php require_once '../../includes/footer.php'; ?>