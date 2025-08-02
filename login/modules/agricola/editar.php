<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('agricola', 'editar')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Obtener el registro a editar
$stmt = $pdo->prepare("SELECT * FROM produccion_agricola WHERE id = ?");
$stmt->execute([$_GET['id']]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

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
        
        $stmt = $pdo->prepare("UPDATE produccion_agricola 
                              SET fecha = ?, año = ?, mes = ?, semana = ?, hoja_produccion = ?, 
                                  tipo_movimiento = ?, desglose_movimiento = ?, ubicacion = ?, 
                                  lugar_prod = ?, rubro = ?, producto = ?, unidad_medida = ?, cantidad = ?
                              WHERE id = ?");
        $stmt->execute([
            $fecha, $año, $mes, $semana, $hoja_produccion, $tipo_movimiento, $desglose_movimiento,
            $ubicacion, $lugar_prod, $rubro, $producto, $unidad_medida, $cantidad, $registro['id']
        ]);
        
        logActivity('agricola', 'Actualizó registro agrícola: ' . $producto . ' - ' . $cantidad . ' ' . $unidad_medida);
        
        header('Location: index.php?success=update');
        exit();
    } catch (PDOException $e) {
        $error = "Error al actualizar el registro: " . $e->getMessage();
    }
}

$title = "Editar Producción Agrícola";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
    </div>
        <div class="page-header">
        <h1 class="page-title"><i class="fas fa-edit"></i> Editar Producción Agrícola</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Registro</h3>
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
                                   value="<?= htmlspecialchars($registro['fecha']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hoja_produccion"><i class="fas fa-file-alt"></i> Hoja de Producción</label>
                            <input type="text" id="hoja_produccion" name="hoja_produccion" class="form-control" 
                                   value="<?= htmlspecialchars($registro['hoja_produccion']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_movimiento"><i class="fas fa-exchange-alt"></i> Tipo de Movimiento</label>
                            <select id="tipo_movimiento" name="tipo_movimiento" class="form-control" required>
                                <option value="INGRESO" <?= $registro['tipo_movimiento'] == 'INGRESO' ? 'selected' : '' ?>>INGRESO</option>
                                <option value="EGRESO" <?= $registro['tipo_movimiento'] == 'EGRESO' ? 'selected' : '' ?>>EGRESO</option>
                                <option value="TRASLADO" <?= $registro['tipo_movimiento'] == 'TRASLADO' ? 'selected' : '' ?>>TRASLADO</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="desglose_movimiento"><i class="fas fa-info-circle"></i> Desglose de Movimiento</label>
                            <input type="text" id="desglose_movimiento" name="desglose_movimiento" class="form-control" 
                                   value="<?= htmlspecialchars($registro['desglose_movimiento']) ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación</label>
                            <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                                   value="<?= htmlspecialchars($registro['ubicacion']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lugar_prod"><i class="fas fa-tractor"></i> Lugar de Producción</label>
                            <input type="text" id="lugar_prod" name="lugar_prod" class="form-control" 
                                   value="<?= htmlspecialchars($registro['lugar_prod']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="rubro"><i class="fas fa-tags"></i> Rubro</label>
                            <input type="text" id="rubro" name="rubro" class="form-control" 
                                   value="<?= htmlspecialchars($registro['rubro']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="producto"><i class="fas fa-apple-alt"></i> Producto</label>
                            <input type="text" id="producto" name="producto" class="form-control" 
                                   value="<?= htmlspecialchars($registro['producto']) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="unidad_medida"><i class="fas fa-balance-scale"></i> Unidad de Medida</label>
                            <input type="text" id="unidad_medida" name="unidad_medida" class="form-control" 
                                   value="<?= htmlspecialchars($registro['unidad_medida']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cantidad"><i class="fas fa-calculator"></i> Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" class="form-control" step="0.01" min="0" 
                                   value="<?= htmlspecialchars($registro['cantidad']) ?>" required>
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