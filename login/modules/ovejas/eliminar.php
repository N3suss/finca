<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('ovejas', 'eliminar')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Obtener información de la oveja a eliminar
$stmt = $pdo->prepare("SELECT * FROM inventario_ovejas WHERE conteo = ?");
$stmt->execute([$_GET['id']]);
$oveja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oveja) {
    header('Location: index.php');
    exit();
}

// Eliminar la oveja
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    try {
        $pdo->prepare("DELETE FROM inventario_ovejas WHERE conteo = ?")->execute([$oveja['conteo']]);
        
        logActivity('ovejas', 'Eliminó la oveja: ' . $oveja['codigo']);
        
        header('Location: index.php?success=delete');
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar la oveja: " . $e->getMessage());
    }
}

$title = "Eliminar Oveja";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-sheep"></i> Eliminar Oveja</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Confirmar Eliminación</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <strong>¡Advertencia!</strong> Estás a punto de eliminar permanentemente este registro de oveja.
            </div>
            
            <div class="animal-details">
                <h4>Información de la Oveja:</h4>
                <div class="details-grid">
                    <div>
                        <span>Código:</span>
                        <strong><?= htmlspecialchars($oveja['codigo']) ?></strong>
                    </div>
                    <div>
                        <span>Raza:</span>
                        <strong><?= htmlspecialchars($oveja['raza']) ?></strong>
                    </div>
                    <div>
                        <span>Sexo:</span>
                        <strong><?= htmlspecialchars($oveja['sexo']) ?></strong>
                    </div>
                    <div>
                        <span>Edad:</span>
                        <strong>
                            <?= $oveja['edad_anos'] ?> años, 
                            <?= $oveja['edad_meses'] ?> meses, 
                            <?= $oveja['edad_dias'] ?> días
                        </strong>
                    </div>
                    <div>
                        <span>Registrada por:</span>
                        <strong>
                            <?php 
                            $stmt = $pdo->prepare("SELECT alias FROM users WHERE id = ?");
                            $stmt->execute([$oveja['created_by']]);
                            echo htmlspecialchars($stmt->fetchColumn() ?? 'Sistema');
                            ?>
                        </strong>
                    </div>
                    <div>
                        <span>Fecha registro:</span>
                        <strong><?= date('d/m/Y H:i', strtotime($oveja['created_at'])) ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-buttons">
                <a href="eliminar.php?id=<?= $oveja['conteo'] ?>&confirm=true" class="btn btn-danger">
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
.animal-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.animal-details h4 {
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