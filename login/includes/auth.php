<?php
// includes/auth.php
require_once 'config.php';

// Función para iniciar sesión
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Establecer variables de sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_alias'] = $user['alias'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_fullname'] = $user['fullname'];
        
        // Obtener permisos del usuario
        $stmt = $pdo->prepare("SELECT module, action, allowed FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar permisos en un array multidimensional
        $permArray = [];
        foreach ($permissions as $perm) {
            $permArray[$perm['module']][$perm['action']] = (bool)$perm['allowed'];
        }
        
        $_SESSION['permissions'] = $permArray;
        
        // Registrar actividad
        logActivity('login', 'Inicio de sesión exitoso');
        
        return true;
    }
    
    return false;
}

// Función para cerrar sesión
function logout() {
    // Registrar actividad
    logActivity('logout', 'Cierre de sesión');
    
    // Destruir la sesión
    session_unset();
    session_destroy();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para obtener información del usuario actual
function currentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'alias' => $_SESSION['user_alias'],
            'role' => $_SESSION['user_role'],
            'fullname' => $_SESSION['user_fullname']
        ];
    }
    return null;
}
?>