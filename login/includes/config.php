<?php
// includes/config.php
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'cqmlyvuixf_n3suss');
define('DB_PASS', 'dykki4-xoqneS-cunhib');
define('DB_NAME', 'cqmlyvuixf_fincadata');

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Configuración del sistema
define('SITE_NAME', 'Gestión de Finca');
define('SITE_URL', 'https://'.$_SERVER['HTTP_HOST'].'/login/');

// Función para verificar permisos
function checkPermission($module, $action) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: '.SITE_URL.'login.php');
        exit();
    }
    
    // El usuario maestro tiene todos los permisos
    if ($_SESSION['user_role'] == 'maestro') {
        return true;
    }
    
    // Verificar permisos específicos
    if (isset($_SESSION['permissions'][$module][$action])) {
        return $_SESSION['permissions'][$module][$action];
    }
    
    return false;
}

// Función para registrar actividad
function logActivity($action, $details) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, action, details, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}
?>