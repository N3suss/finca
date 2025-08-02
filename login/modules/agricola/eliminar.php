<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('agricola', 'eliminar')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Obtener el registro a eliminar
$stmt = $pdo->prepare("SELECT p.*, u.alias as creador 
                      FROM produccion_agricola p 
                      LEFT JOIN users u ON p.created_by = u.id 
                      WHERE p.id = ?");
$stmt->execute([$_GET['id']]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
    header('Location: index.php');
    exit();
}

// Eliminar el registro
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    try {
        $pdo->prepare("DELETE FROM produccion_agricola WHERE id = ?")->execute([$registro['id']]);
        
        logActivity('agricola', 'Eliminó registro agrícola: ' . $registro['producto'] . ' - ' . $registro['cantidad'] . ' ' . $registro['unidad_medida']);
        
        header('Location: index.php?success=delete');
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar el registro: " . $e->getMessage());
    }
}

$title = "Eliminar Registro Agrícola";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-trash-alt"></i> Eliminar Registro Agrícola</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Confirmar Eliminación</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <strong>¡Advertencia!</strong> Estás a punto de eliminar permanentemente este registro de producción agrícola.
            </div>
            
            <div class="registro-details">
                <h4>Detalles del Registro:</h4>
                <div class="details-grid">
                    <div>
                        <span>Fecha:</span>
                        <strong><?= date('d/m/Y', strtotime($registro['fecha'])) ?></strong>
                    </div>
                    <div>
                        <span>Producto:</span>
                        <strong><?= htmlspecialchars($registro['producto']) ?></strong>
                    </div>
                    <div>
                        <span>Rubro:</span>
                        <strong><?= htmlspecialchars($registro['rubro']) ?></strong>
                    </div>
                    <div>
                        <span>Cantidad:</span>
                        <strong><?= $registro['cantidad'] ?> <?= htmlspecialchars($registro['unidad_medida']) ?></strong>
                    </div>
                    <div>
                        <span>Ubicación:</span>
                        <strong><?= htmlspecialchars($registro['ubicacion']) ?></strong>
                    </div>
                    <div>
                        <span>Tipo Movimiento:</span>
                        <strong><?= htmlspecialchars($registro['tipo_movimiento']) ?></strong>
                    </div>
                    <div>
                        <span>Registrado por:</span>
                        <strong><?= htmlspecialchars($registro['creador'] ?? 'Sistema') ?></strong>
                    </div>
                    <div>
                        <span>Fecha registro:</span>
                        <strong><?= date('d/m/Y H:i', strtotime($registro['created_at'])) ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-buttons">
                <a href="eliminar.php?id=<?= $registro['id'] ?>&confirm=true" class="btn btn-danger">
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
.registro-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.registro-details h4 {
    margin-bottom: 15px;
    color: var(--primary-dark);
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.details-grid div {
    display: flex;
    flex-direction: column;
}

.details-grid span {
    font-size: 0.85rem;
    color: var(--text-light);
}

.confirmation-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<?php require_once '../../includes/footer.php'; ?>