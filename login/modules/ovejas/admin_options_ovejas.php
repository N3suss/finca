<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once 'config_options_ovejas.php';

// Verificar permisos de administrador
if (!checkPermission('admin', 'manage_options')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Cargar las opciones actuales
$options = loadOvejasOptions();

// Procesar el formulario de administración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar qué acción se está realizando
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $category = $_POST['category'];
        $value = trim($_POST['value']);
        
        if ($action === 'add') {
            // Agregar un nuevo valor a la categoría
            if (!empty($value) && !in_array($value, $options[$category])) {
                $options[$category][] = $value;
                saveOvejasOptions($options);
                $success = "Opción agregada correctamente.";
            }
        } elseif ($action === 'remove') {
            // Eliminar un valor de la categoría
            if (($key = array_search($value, $options[$category])) !== false) {
                unset($options[$category][$key]);
                $options[$category] = array_values($options[$category]); // Reindexar
                saveOvejasOptions($options);
                $success = "Opción eliminada correctamente.";
            }
        }
    }
}

$title = "Administrar Opciones de Ovejas";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
    </div>
    
        <div class="page-header">
        <h1 class="page-title"><i class="fas fa-cog"></i> Administrar Opciones de Ovejas</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Gestión de Opciones</h3>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <form method="post" class="mb-4">
                        <div class="form-group">
                            <label for="category">Categoría:</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="sexo">Sexo</option>
                                <option value="raza">Raza</option>
                                <option value="tipo_animal">Tipo de Animal</option>
                                <option value="estado_prenaz">Estado de Preñez</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="value">Nuevo Valor:</label>
                            <input type="text" id="value" name="value" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="action" value="add" class="btn btn-success">
                                <i class="fas fa-plus"></i> Agregar Opción
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <form method="post">
                        <div class="form-group">
                            <label for="remove_category">Categoría:</label>
                            <select id="remove_category" name="category" class="form-control" required>
                                <option value="sexo">Sexo</option>
                                <option value="raza">Raza</option>
                                <option value="tipo_animal">Tipo de Animal</option>
                                <option value="estado_prenaz">Estado de Preñez</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="remove_value">Valor a Eliminar:</label>
                            <select id="remove_value" name="value" class="form-control" required>
                                <!-- Las opciones se cargarán dinámicamente con JavaScript -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="action" value="remove" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Eliminar Opción
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <hr>
            
            <h4>Opciones Actuales</h4>
            <div class="row">
                <?php foreach ($options as $category => $values): ?>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><?= ucfirst(str_replace('_', ' ', $category)) ?></h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($values as $value): ?>
                                <li class="list-group-item"><?= htmlspecialchars($value) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Cargar dinámicamente los valores cuando se selecciona una categoría
document.getElementById('remove_category').addEventListener('change', function() {
    const category = this.value;
    const removeValueSelect = document.getElementById('remove_value');
    
    // Limpiar opciones actuales
    removeValueSelect.innerHTML = '';
    
    // Obtener las opciones de la categoría seleccionada
    const options = <?= json_encode($options) ?>;
    
    if (options[category]) {
        options[category].forEach(function(value) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            removeValueSelect.appendChild(option);
        });
    }
});

// Disparar el evento change al cargar la página para inicializar el select
document.getElementById('remove_category').dispatchEvent(new Event('change'));
</script>

<?php require_once '../../includes/footer.php'; ?>