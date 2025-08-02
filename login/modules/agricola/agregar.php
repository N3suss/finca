<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once 'config_options.php'; // Incluir el archivo de configuración de opciones

if (!checkPermission('agricola', 'crear')) {
    header('Location: /login/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Cargar opciones desde el archivo JSON
$options = loadOptions();

// Valores por defecto para el formulario
$defaultValues = [
    'fecha' => date('Y-m-d'),
    'hoja_produccion' => '',
    'tipo_movimiento' => $options['tipo_movimiento'][0],
    'desglose_movimiento' => $options['desglose_movimiento'][0],
    'ubicacion' => $options['ubicacion'][0],
    'lugar_prod' => $options['lugar_prod'][0],
    'rubro' => $options['rubro'][0],
    'producto' => $options['producto'][0],
    'unidad_medida' => $options['unidad_medida'][0],
    'cantidad' => '1.00'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $hoja_produccion = $_POST['hoja_produccion'];
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $desglose_movimiento = $_POST['desglose_movimiento'];
    $ubicacion = $_POST['ubicacion'];
    $lugar_prod = $_POST['lugar_prod'];
    $rubro = $_POST['rubro'];
    $producto = $_POST['producto'];
    $unidad_medida = $_POST['unidad_medida'];
    $cantidad = $_POST['cantidad'];
    
    try {
        $fechaObj = new DateTime($fecha);
        $año = $fechaObj->format('Y');
        $mes = strtoupper($fechaObj->format('F'));
        $semana = $fechaObj->format('W');
        
        $stmt = $pdo->prepare("INSERT INTO produccion_agricola 
                              (fecha, año, mes, semana, hoja_produccion, tipo_movimiento, desglose_movimiento, 
                               ubicacion, lugar_prod, rubro, producto, unidad_medida, cantidad, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fecha, $año, $mes, $semana, $hoja_produccion, $tipo_movimiento, $desglose_movimiento,
            $ubicacion, $lugar_prod, $rubro, $producto, $unidad_medida, $cantidad, $_SESSION['user_id']
        ]);
        
        logActivity('agricola', 'Registró producción agrícola: ' . $producto . ' - ' . $cantidad . ' ' . $unidad_medida);
        
        header('Location: index.php?success=create');
        exit();
    } catch (PDOException $e) {
        $error = "Error al guardar el registro: " . $e->getMessage();
    }
}

$title = "Registrar Producción Agrícola";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        
    </div>
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-seedling"></i> Registrar Producción Agrícola</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Nuevo Registro</h3>
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
                            <label for="fecha"><i class="fas fa-calendar-alt"></i> Fecha</label>
                            <input type="date" id="fecha" name="fecha" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['fecha'] ?? $defaultValues['fecha']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hoja_produccion"><i class="fas fa-file-alt"></i> Hoja de Producción</label>
                            <input type="text" id="hoja_produccion" name="hoja_produccion" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['hoja_produccion'] ?? $defaultValues['hoja_produccion']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_movimiento"><i class="fas fa-exchange-alt"></i> Tipo de Movimiento</label>
                            <select id="tipo_movimiento" name="tipo_movimiento" class="form-control" required>
                                <?php foreach ($options['tipo_movimiento'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['tipo_movimiento'] ?? $defaultValues['tipo_movimiento']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="desglose_movimiento"><i class="fas fa-info-circle"></i> Desglose de Movimiento</label>
                            <select id="desglose_movimiento" name="desglose_movimiento" class="form-control">
                                <?php foreach ($options['desglose_movimiento'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['desglose_movimiento'] ?? $defaultValues['desglose_movimiento']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación</label>
                            <select id="ubicacion" name="ubicacion" class="form-control" required>
                                <?php foreach ($options['ubicacion'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['ubicacion'] ?? $defaultValues['ubicacion']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="lugar_prod"><i class="fas fa-tractor"></i> Lugar de Producción</label>
                            <select id="lugar_prod" name="lugar_prod" class="form-control">
                                <?php foreach ($options['lugar_prod'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['lugar_prod'] ?? $defaultValues['lugar_prod']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="rubro"><i class="fas fa-tags"></i> Rubro</label>
                            <select id="rubro" name="rubro" class="form-control" required>
                                <?php foreach ($options['rubro'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['rubro'] ?? $defaultValues['rubro']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="producto"><i class="fas fa-apple-alt"></i> Producto</label>
                            <select id="producto" name="producto" class="form-control" required>
                                <?php foreach ($options['producto'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['producto'] ?? $defaultValues['producto']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="unidad_medida"><i class="fas fa-balance-scale"></i> Unidad de Medida</label>
                            <select id="unidad_medida" name="unidad_medida" class="form-control" required>
                                <?php foreach ($options['unidad_medida'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= ($_POST['unidad_medida'] ?? $defaultValues['unidad_medida']) == $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cantidad"><i class="fas fa-calculator"></i> Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" class="form-control" step="0.01" min="0" 
                                   value="<?= htmlspecialchars($_POST['cantidad'] ?? $defaultValues['cantidad']) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>