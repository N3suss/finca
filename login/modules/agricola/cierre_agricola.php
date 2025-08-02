<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!checkPermission('agricola', 'reportes')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Incluir librería para PDF
require_once '../../libs/tcpdf/tcpdf.php';

// Procesar solicitud de generación de PDF
if (isset($_GET['generar_pdf'])) {
    generarPDF();
    exit();
}

// Obtener meses disponibles con datos
$stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as mes FROM produccion_agricola ORDER BY mes DESC");
$mesesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el mes seleccionado (o el actual si no se ha seleccionado)
$mesSeleccionado = $_GET['mes'] ?? date('Y-m');

// Obtener datos para el mes seleccionado
$datosMes = obtenerDatosMes($mesSeleccionado);

$title = "Dashboard de Cierres Mensuales Agrícolas";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

function obtenerDatosMes($mes) {
    global $pdo;
    
    $inicioMes = date('Y-m-01', strtotime($mes));
    $finMes = date('Y-m-t', strtotime($mes));
    
    // Consulta para obtener resumen por rubro
    $queryResumen = "SELECT 
                        rubro,
                        COUNT(*) as total_registros,
                        SUM(cantidad) as cantidad_total,
                        unidad_medida,
                        GROUP_CONCAT(DISTINCT producto) as productos,
                        SUM(CASE WHEN tipo_movimiento = 'INGRESO' THEN cantidad ELSE 0 END) as ingresos,
                        SUM(CASE WHEN tipo_movimiento = 'EGRESO' THEN cantidad ELSE 0 END) as egresos
                    FROM produccion_agricola
                    WHERE fecha BETWEEN ? AND ?
                    GROUP BY rubro, unidad_medida
                    ORDER BY rubro";
    
    $stmt = $pdo->prepare($queryResumen);
    $stmt->execute([$inicioMes, $finMes]);
    $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener detalles
    $queryDetalles = "SELECT 
                        fecha, producto, rubro, ubicacion, 
                        tipo_movimiento, desglose_movimiento,
                        cantidad, unidad_medida, lugar_prod
                    FROM produccion_agricola
                    WHERE fecha BETWEEN ? AND ?
                    ORDER BY fecha, rubro, producto";
    
    $stmt = $pdo->prepare($queryDetalles);
    $stmt->execute([$inicioMes, $finMes]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'resumen' => $resumen,
        'detalles' => $detalles,
        'inicio_mes' => $inicioMes,
        'fin_mes' => $finMes
    ];
}

function generarPDF() {
    global $pdo, $mesSeleccionado, $datosMes;
    
    $mesSeleccionado = $_GET['mes'];
    $datosMes = obtenerDatosMes($mesSeleccionado);
    
    // Crear nuevo documento PDF con configuración personalizada
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator('Sistema Agrícola');
    $pdf->SetAuthor('Tu Empresa');
    $pdf->SetTitle('Reporte Mensual - ' . date('F Y', strtotime($mesSeleccionado)));
    $pdf->SetSubject('Producción Agrícola');
    
    // Configuración de márgenes
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Configuración de fuente por defecto
    $pdf->SetFont('helvetica', '', 10);
    
    // Agregar página
    $pdf->AddPage();
    
    // Logo y cabecera
    $pdf->Image('../../img/logo.jpg', 15, 15, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    
    // Título principal
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 15, 'REPORTE MENSUAL DE PRODUCCIÓN AGRÍCOLA', 0, 1, 'C');
    
    // Subtítulo
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, date('F Y', strtotime($mesSeleccionado)), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
    
    // Período
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Del ' . date('d/m/Y', strtotime($datosMes['inicio_mes'])) . ' al ' . date('d/m/Y', strtotime($datosMes['fin_mes'])), 0, 1, 'C');
    
    // Espacio
    $pdf->Ln(10);
    
    // --- RESUMEN ---
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(79, 115, 223); // Azul
    $pdf->SetTextColor(255, 255, 255); // Blanco
    $pdf->Cell(0, 10, 'Resumen por Rubro', 0, 1, 'L', 1);
    
    // Restaurar colores
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    
    // Configurar tabla resumen
    $header = ['Rubro', 'Productos', 'Ingresos', 'Egresos', 'Balance', 'Unidad'];
    $w = [40, 85, 25, 25, 25, 20]; // Anchuras personalizadas
    
    // Cabecera de tabla
    $pdf->SetFont('helvetica', 'B', 10);
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Datos resumen
    $pdf->SetFont('helvetica', '', 9);
    foreach($datosMes['resumen'] as $row) {
        $balance = $row['ingresos'] - $row['egresos'];
        
        $pdf->Cell($w[0], 6, $row['rubro'], 'LR');
        $pdf->Cell($w[1], 6, substr($row['productos'], 0, 40) . (strlen($row['productos']) > 40 ? '...' : ''), 'LR');
        $pdf->Cell($w[2], 6, number_format($row['ingresos'], 2), 'LR', 0, 'R');
        $pdf->Cell($w[3], 6, number_format($row['egresos'], 2), 'LR', 0, 'R');
        
        // Balance con color condicional
        if ($balance >= 0) {
            $pdf->SetTextColor(40, 167, 69); // Verde
        } else {
            $pdf->SetTextColor(220, 53, 69); // Rojo
        }
        $pdf->Cell($w[4], 6, number_format($balance, 2), 'LR', 0, 'R');
        $pdf->SetTextColor(0, 0, 0); // Restaurar color
        
        $pdf->Cell($w[5], 6, $row['unidad_medida'], 'LR', 0, 'C');
        $pdf->Ln();
    }
    
    // Pie de tabla resumen
    $pdf->Cell(array_sum($w), 0, '', 'T');
    $pdf->Ln(10);
    
    // --- DETALLE ---
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(23, 162, 184); // Turquesa
    $pdf->SetTextColor(255, 255, 255); // Blanco
    $pdf->Cell(0, 10, 'Detalle de Movimientos', 0, 1, 'L', 1);
    
    // Restaurar colores
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    
    // Configurar tabla detalle
    $header = ['Fecha', 'Producto', 'Rubro', 'Tipo', 'Cantidad', 'Ubicación', 'Lugar'];
    $w = [20, 85, 40, 20, 25, 30, 30]; // Anchuras personalizadas
    
    // Cabecera de tabla
    $pdf->SetFont('helvetica', 'B', 9);
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Datos detalle
    $pdf->SetFont('helvetica', '', 8);
    foreach($datosMes['detalles'] as $row) {
        $pdf->Cell($w[0], 6, date('d/m/Y', strtotime($row['fecha'])), 'LR', 0, 'C');
        $pdf->Cell($w[1], 6, $row['producto'], 'LR');
        $pdf->Cell($w[2], 6, $row['rubro'], 'LR');
        
        // Tipo con color
        if ($row['tipo_movimiento'] == 'INGRESO') {
            $pdf->SetTextColor(40, 167, 69); // Verde
        } else {
            $pdf->SetTextColor(220, 53, 69); // Rojo
        }
        $pdf->Cell($w[3], 6, $row['tipo_movimiento'], 'LR', 0, 'C');
        $pdf->SetTextColor(0, 0, 0); // Restaurar color
        
        $pdf->Cell($w[4], 6, number_format($row['cantidad'], 2) . ' ' . $row['unidad_medida'], 'LR', 0, 'R');
        $pdf->Cell($w[5], 6, $row['ubicacion'], 'LR');
        $pdf->Cell($w[6], 6, $row['lugar_prod'], 'LR');
        $pdf->Ln();
    }
    
    // Pie de tabla detalle
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    // Pie de página
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('reporte_agricola_' . $mesSeleccionado . '.pdf', 'D');
}
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Cierres Mensuales Agrícolas</h1>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Selección de Mes</h3>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-3">
                    <label for="mes" class="mr-2">Mes:</label>
                    <select id="mes" name="mes" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($mesesDisponibles as $mes): ?>
                            <?php $mesFormateado = date('F Y', strtotime($mes['mes'] . '-01')); ?>
                            <option value="<?= $mes['mes'] ?>" <?= $mes['mes'] == $mesSeleccionado ? 'selected' : '' ?>>
                                <?= $mesFormateado ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a href="?mes=<?= $mesSeleccionado ?>&generar_pdf=1" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </a>
            </form>
        </div>
    </div>
    
    <!-- Resumen del Mes -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h3>Resumen del Mes - <?= date('F Y', strtotime($mesSeleccionado)) ?></h3>
            <small>Período: <?= date('d/m/Y', strtotime($datosMes['inicio_mes'])) ?> al <?= date('d/m/Y', strtotime($datosMes['fin_mes'])) ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Rubro</th>
                            <th>Productos</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Balance</th>
                            <th>Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($datosMes['resumen'])): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay registros para este mes</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $totalIngresos = 0;
                            $totalEgresos = 0;
                            ?>
                            <?php foreach ($datosMes['resumen'] as $item): ?>
                                <?php 
                                $balance = $item['ingresos'] - $item['egresos'];
                                $totalIngresos += $item['ingresos'];
                                $totalEgresos += $item['egresos'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['rubro']) ?></td>
                                    <td><?= htmlspecialchars(substr($item['productos'], 0, 50) . (strlen($item['productos']) > 50 ? '...' : '')) ?></td>
                                    <td class="text-right"><?= number_format($item['ingresos'], 2) ?></td>
                                    <td class="text-right"><?= number_format($item['egresos'], 2) ?></td>
                                    <td class="text-right <?= $balance < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($balance, 2) ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['unidad_medida']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-active">
                                <td colspan="2" class="text-right"><strong>Totales:</strong></td>
                                <td class="text-right"><strong><?= number_format($totalIngresos, 2) ?></strong></td>
                                <td class="text-right"><strong><?= number_format($totalEgresos, 2) ?></strong></td>
                                <td class="text-right <?= ($totalIngresos - $totalEgresos) < 0 ? 'text-danger' : 'text-success' ?>">
                                    <strong><?= number_format($totalIngresos - $totalEgresos, 2) ?></strong>
                                </td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Gráficos -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Distribución por Rubro</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="chartRubro" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Balance Ingresos/Egresos</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="chartBalance" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detalle de Movimientos -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h3>Detalle de Movimientos</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Rubro</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Ubicación</th>
                            <th>Lugar Prod.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($datosMes['detalles'])): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay movimientos registrados en este mes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($datosMes['detalles'] as $movimiento): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($movimiento['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($movimiento['producto']) ?></td>
                                    <td><?= htmlspecialchars($movimiento['rubro']) ?></td>
                                    <td class="<?= $movimiento['tipo_movimiento'] == 'INGRESO' ? 'text-success' : 'text-danger' ?>">
                                        <?= htmlspecialchars($movimiento['tipo_movimiento']) ?>
                                    </td>
                                    <td class="text-right">
                                        <?= number_format($movimiento['cantidad'], 2) ?> <?= htmlspecialchars($movimiento['unidad_medida']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($movimiento['ubicacion']) ?></td>
                                    <td><?= htmlspecialchars($movimiento['lugar_prod']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para los gráficos
    const resumenData = <?= json_encode($datosMes['resumen']) ?>;
    
    if (resumenData.length > 0) {
        // Gráfico por rubro
        const ctxRubro = document.getElementById('chartRubro').getContext('2d');
        new Chart(ctxRubro, {
            type: 'bar',
            data: {
                labels: resumenData.map(item => item.rubro),
                datasets: [{
                    label: 'Balance (Ingresos - Egresos)',
                    data: resumenData.map(item => item.ingresos - item.egresos),
                    backgroundColor: resumenData.map(item => 
                        (item.ingresos - item.egresos) >= 0 ? 'rgba(40, 167, 69, 0.7)' : 'rgba(220, 53, 69, 0.7)'),
                    borderColor: resumenData.map(item => 
                        (item.ingresos - item.egresos) >= 0 ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)'),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico de balance
        const ctxBalance = document.getElementById('chartBalance').getContext('2d');
        new Chart(ctxBalance, {
            type: 'pie',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    data: [
                        resumenData.reduce((sum, item) => sum + parseFloat(item.ingresos), 0),
                        resumenData.reduce((sum, item) => sum + parseFloat(item.egresos), 0)
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    }
});
</script>

<style>
.card-header.bg-primary h3,
.card-header.bg-info h3 {
    margin-bottom: 0;
    color: white;
}

.card-header.bg-primary small,
.card-header.bg-info small {
    color: rgba(255,255,255,0.8);
}

.table-active td {
    font-weight: bold;
}

.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.table-responsive {
    overflow-x: auto;
}

canvas {
    width: 100% !important;
    max-height: 300px;
}
</style>

<?php require_once '../../includes/footer.php'; ?>