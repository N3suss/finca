<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('ovejas', 'editar')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Obtener datos de la oveja a editar
$stmt = $pdo->prepare("SELECT * FROM inventario_ovejas WHERE conteo = ?");
$stmt->execute([$_GET['id']]);
$oveja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oveja) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $sexo = $_POST['sexo'];
    $raza = trim($_POST['raza']);
    $tipo_animal = trim($_POST['tipo_animal']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $estado_prenaz = $_POST['estado_prenaz'];
    
    try {
        // Verificar si el código ya existe en otra oveja
        $stmt = $pdo->prepare("SELECT conteo FROM inventario_ovejas WHERE codigo = ? AND conteo != ?");
        $stmt->execute([$codigo, $oveja['conteo']]);
        
        if ($stmt->fetch()) {
            $error = 'El código de la oveja ya está registrado en otro animal';
        } else {
            // Actualizar los datos (el trigger actualizará la edad automáticamente)
            $stmt = $pdo->prepare("UPDATE inventario_ovejas 
                                  SET codigo = ?, sexo = ?, raza = ?, tipo_animal = ?, 
                                      fecha_nacimiento = ?, estado_prenaz = ?
                                  WHERE conteo = ?");
            $stmt->execute([
                $codigo,
                $sexo,
                $raza,
                $tipo_animal,
                $fecha_nacimiento,
                $estado_prenaz,
                $oveja['conteo']
            ]);
            
            logActivity('ovejas', 'Actualizó información de la oveja: ' . $codigo);
            
            header('Location: index.php?success=update');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error al actualizar la oveja: " . $e->getMessage();
    }
}

$title = "Editar Oveja";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
    </div>
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-sheep"></i> Editar Oveja</h1>
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
                                   value="<?= htmlspecialchars($oveja['codigo']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sexo"><i class="fas fa-venus-mars"></i> Sexo</label>
                            <select id="sexo" name="sexo" class="form-control" required>
                                <option value="HEMBRA" <?= $oveja['sexo'] == 'HEMBRA' ? 'selected' : '' ?>>HEMBRA</option>
                                <option value="MACHO" <?= $oveja['sexo'] == 'MACHO' ? 'selected' : '' ?>>MACHO</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="raza"><i class="fas fa-dna"></i> Raza</label>
                            <input type="text" id="raza" name="raza" class="form-control" 
                                   value="<?= htmlspecialchars($oveja['raza']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo_animal"><i class="fas fa-tag"></i> Tipo de Animal</label>
                            <input type="text" id="tipo_animal" name="tipo_animal" class="form-control" 
                                   value="<?= htmlspecialchars($oveja['tipo_animal']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_nacimiento"><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                                   value="<?= htmlspecialchars($oveja['fecha_nacimiento']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado_prenaz"><i class="fas fa-heartbeat"></i> Estado de Preñez</label>
                            <select id="estado_prenaz" name="estado_prenaz" class="form-control" required>
                                <option value="n/a" <?= $oveja['estado_prenaz'] == 'n/a' ? 'selected' : '' ?>>No aplica</option>
                                <option value="PREÑADA" <?= $oveja['estado_prenaz'] == 'PREÑADA' ? 'selected' : '' ?>>Preñada</option>
                                <option value="NO PREÑADA" <?= $oveja['estado_prenaz'] == 'NO PREÑADA' ? 'selected' : '' ?>>No preñada</option>
                                <option value="LACTANCIA" <?= $oveja['estado_prenaz'] == 'LACTANCIA' ? 'selected' : '' ?>>En lactancia</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="age-preview">
                            <h4><i class="fas fa-clock"></i> Edad Actual</h4>
                            <div class="age-values">
                                <div>
                                    <span id="edad_anos"><?= $oveja['edad_anos'] ?></span>
                                    <small>Años</small>
                                </div>
                                <div>
                                    <span id="edad_meses"><?= $oveja['edad_meses'] ?></span>
                                    <small>Meses</small>
                                </div>
                                <div>
                                    <span id="edad_dias"><?= $oveja['edad_dias'] ?></span>
                                    <small>Días</small>
                                </div>
                            </div>
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
    
    fechaNacimiento.addEventListener('change', calcularEdad);
});
</script>

<?php require_once '../../includes/footer.php'; ?>