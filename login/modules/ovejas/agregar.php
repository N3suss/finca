<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once 'config_options_ovejas.php'; // Incluir el archivo de configuración de opciones

if (!checkPermission('ovejas', 'crear')) {
    header('Location: /login/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Cargar opciones desde el archivo JSON
$options = loadOvejasOptions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $sexo = $_POST['sexo'];
    $raza = trim($_POST['raza']);
    $tipo_animal = trim($_POST['tipo_animal']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $estado_prenaz = $_POST['estado_prenaz'];
    
    try {
        // Verificar si el código ya existe
        $stmt = $pdo->prepare("SELECT conteo FROM inventario_ovejas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetch()) {
            $error = 'El código de la oveja ya está registrado';
        } else {
            // Insertar nueva oveja (los triggers calcularán la edad automáticamente)
            $stmt = $pdo->prepare("INSERT INTO inventario_ovejas 
                                  (codigo, sexo, raza, tipo_animal, fecha_nacimiento, estado_prenaz, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $codigo,
                $sexo,
                $raza,
                $tipo_animal,
                $fecha_nacimiento,
                $estado_prenaz,
                $_SESSION['user_id']
            ]);
            
            logActivity('ovejas', 'Registró nueva oveja: ' . $codigo);
            
            header('Location: index.php?success=create');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error al registrar la oveja: " . $e->getMessage();
    }
}

$title = "Registrar Nueva Oveja";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-sheep"></i> Registrar Nueva Oveja</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Información de la Oveja</h3>
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
                            <label for="codigo"><i class="fas fa-barcode"></i> Código</label>
                            <input type="text" id="codigo" name="codigo" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" required>
                            <small class="text-muted">Código único de identificación</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="sexo"><i class="fas fa-venus-mars"></i> Sexo</label>
                            <select id="sexo" name="sexo" class="form-control" required>
                                <?php foreach ($options['sexo'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['sexo'] ?? '') == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="raza"><i class="fas fa-dna"></i> Raza</label>
                            <select id="raza" name="raza" class="form-control" required>
                                <?php foreach ($options['raza'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['raza'] ?? '') == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo_animal"><i class="fas fa-tag"></i> Tipo de Animal</label>
                            <select id="tipo_animal" name="tipo_animal" class="form-control" required>
                                <?php foreach ($options['tipo_animal'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['tipo_animal'] ?? 'OVEJA') == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_nacimiento"><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado_prenaz"><i class="fas fa-heartbeat"></i> Estado de Preñez</label>
                            <select id="estado_prenaz" name="estado_prenaz" class="form-control" required>
                                <?php foreach ($options['estado_prenaz'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['estado_prenaz'] ?? '') == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="age-preview">
                            <h4><i class="fas fa-clock"></i> Edad Calculada</h4>
                            <div class="age-values">
                                <div>
                                    <span id="edad_anos">0</span>
                                    <small>Años</small>
                                </div>
                                <div>
                                    <span id="edad_meses">0</span>
                                    <small>Meses</small>
                                </div>
                                <div>
                                    <span id="edad_dias">0</span>
                                    <small>Días</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Oveja</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    
    function calcularEdad() {
        if (fechaNacimiento.value) {
            const fecha = new Date(fechaNacimiento.value);
            const hoy = new Date();
            const diffTime = hoy - fecha;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const diffMonths = Math.floor(diffDays / 30);
            const diffYears = Math.floor(diffDays / 365);
            
            document.getElementById('edad_dias').textContent = diffDays;
            document.getElementById('edad_meses').textContent = diffMonths;
            document.getElementById('edad_anos').textContent = diffYears;
        }
    }
    
    // Calcular edad al cargar y cuando cambia la fecha
    calcularEdad();
    fechaNacimiento.addEventListener('change', calcularEdad);
});
</script>

<style>
.age-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.age-preview h4 {
    margin-bottom: 15px;
    color: var(--primary-dark);
    display: flex;
    align-items: center;
}

.age-preview h4 i {
    margin-right: 10px;
}

.age-values {
    display: flex;
    gap: 20px;
}

.age-values div {
    text-align: center;
}

.age-values span {
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--primary-color);
    display: block;
}

.age-values small {
    color: var(--text-light);
    font-size: 0.9rem;
}
</style>

<?php require_once '../../includes/footer.php'; ?>