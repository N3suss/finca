<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../includes/config.php';
require_once '../../../includes/auth.php';

if (!checkPermission('animales', 'reportes')) {
    header('Location: /login/dashboard.php');
    exit();
}

// Incluir librería para PDF
require_once '../../../libs/tcpdf/tcpdf.php';

// Procesar solicitud de generación de PDF
if (isset($_GET['generar_pdf'])) {
    generarPDF();
    exit();
}

// Obtener meses disponibles con datos
$stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(fecha_nacimiento, '%Y-%m') as mes FROM inventario_ovejas ORDER BY mes DESC");
$mesesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el mes seleccionado (o el actual si no se ha seleccionado)
$mesSeleccionado = $_GET['mes'] ?? date('Y-m');

// Obtener datos para el mes seleccionado
$datosMes = obtenerDatosMes($mesSeleccionado);

$title = "Dashboard de Cierres Mensuales";
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

function obtenerDatosMes($mes) {
    global $pdo;
    
    $inicioMes = date('Y-m-01', strtotime($mes));
    $finMes = date('Y-m-t', strtotime($mes));
    
    // Consulta para obtener resumen por tipo de animal
    $queryResumen = "SELECT 
                        tipo_animal,
                        COUNT(*) as total,
                        SUM(CASE WHEN sexo = 'HEMBRA' THEN 1 ELSE 0 END) as hembras,
                        SUM(CASE WHEN sexo = 'MACHO' THEN 1 ELSE 0 END) as machos,
                        SUM(CASE WHEN estado_prenaz = 'PREÑADA' THEN 1 ELSE 0 END) as preñadas,
                        SUM(CASE WHEN estado_prenaz = 'LACTANCIA' THEN 1 ELSE 0 END) as lactancia
                    FROM inventario_ovejas
                    WHERE fecha_nacimiento BETWEEN ? AND ?
                    GROUP BY tipo_animal";
    
    $stmt = $pdo->prepare($queryResumen);
    $stmt->execute([$inicioMes, $finMes]);
    $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener detalles
    $queryDetalles = "SELECT 
                        codigo, sexo, raza, tipo_animal, 
                        fecha_nacimiento, estado_prenaz,
                        edad_anos, edad_meses, edad_dias
                    FROM inventario_ovejas
                    WHERE fecha_nacimiento BETWEEN ? AND ?
                    ORDER BY tipo_animal, codigo";
    
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
    
    // Crear nuevo documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Gestión Animal');
    $pdf->SetTitle('Cierre Mensual - ' . date('F Y', strtotime($mesSeleccionado)));
    $pdf->SetSubject('Reporte de Cierre Mensual');
    
    // Eliminar márgenes por defecto
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Auto saltar página
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Agregar página
    $pdf->AddPage();
    
    // Logo
    $pdf->Image('../../img/logo.png', 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    
    // Título
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 15, 'Reporte de Cierre Mensual', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('F Y', strtotime($mesSeleccionado)), 0, 1, 'C');
    
    // Resumen
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Resumen por Tipo de Animal', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    // Cabecera de tabla resumen
    $header = ['Tipo', 'Total', 'Hembras', 'Machos', 'Preñadas', 'Lactancia'];
    $w = [40, 20, 20, 20, 25, 25];
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Datos resumen
    foreach($datosMes['resumen'] as $row) {
        $pdf->Cell($w[0], 6, $row['tipo_animal'], 'LR');
        $pdf->Cell($w[1], 6, $row['total'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, $row['hembras'], 'LR', 0, 'C');
        $pdf->Cell($w[3], 6, $row['machos'], 'LR', 0, 'C');
        $pdf->Cell($w[4], 6, $row['preñadas'], 'LR', 0, 'C');
        $pdf->Cell($w[5], 6, $row['lactancia'], 'LR', 0, 'C');
        $pdf->Ln();
    }
    
    // Cerrar tabla resumen
    $pdf->Cell(array_sum($w), 0, '', 'T');
    $pdf->Ln(10);
    
    // Detalles
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Detalle de Animales', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    
    // Cabecera de tabla detalles
    $header = ['Código', 'Sexo', 'Raza', 'Tipo', 'Nacimiento', 'Estado', 'Edad'];
    $w = [30, 15, 30, 25, 25, 30, 30];
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Datos detalles
    foreach($datosMes['detalles'] as $row) {
        $edad = '';
        if ($row['edad_anos'] > 0) $edad .= $row['edad_anos'] . 'a ';
        if ($row['edad_meses'] > 0) $edad .= $row['edad_meses'] . 'm ';
        if ($row['edad_dias'] > 0) $edad .= $row['edad_dias'] . 'd';
        
        $pdf->Cell($w[0], 6, $row['codigo'], 'LR');
        $pdf->Cell($w[1], 6, $row['sexo'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, $row['raza'], 'LR');
        $pdf->Cell($w[3], 6, $row['tipo_animal'], 'LR');
        $pdf->Cell($w[4], 6, date('d/m/Y', strtotime($row['fecha_nacimiento'])), 'LR', 0, 'C');
        $pdf->Cell($w[5], 6, $row['estado_prenaz'], 'LR');
        $pdf->Cell($w[6], 6, trim($edad), 'LR', 0, 'C');
        $pdf->Ln();
    }
    
    // Cerrar tabla detalles
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    // Pie de página
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('cierre_mensual_' . $mesSeleccionado . '.pdf', 'D');
}
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Cierres Mensuales de Animales</h1>
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
        <div class="card-header">
            <h3>Resumen del Mes - <?= date('F Y', strtotime($mesSeleccionado)) ?></h3>
            <small>Período: <?= date('d/m/Y', strtotime($datosMes['inicio_mes'])) ?> al <?= date('d/m/Y', strtotime($datosMes['fin_mes'])) ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tipo de Animal</th>
                            <th>Total</th>
                            <th>Hembras</th>
                            <th>Machos</th>
                            <th>Preñadas</th>
                            <th>En Lactancia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($datosMes['resumen'])): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay datos para este mes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($datosMes['resumen'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['tipo_animal']) ?></td>
                                    <td><?= $item['total'] ?></td>
                                    <td><?= $item['hembras'] ?></td>
                                    <td><?= $item['machos'] ?></td>
                                    <td><?= $item['preñadas'] ?></td>
                                    <td><?= $item['lactancia'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Gráficos -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Distribución por Tipo</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTipo" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Distribución por Sexo</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="chartSexo" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detalle de Animales -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Detalle de Animales</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Sexo</th>
                            <th>Raza</th>
                            <th>Tipo</th>
                            <th>Nacimiento</th>
                            <th>Estado</th>
                            <th>Edad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($datosMes['detalles'])): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay animales registrados en este mes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($datosMes['detalles'] as $animal): ?>
                                <?php
                                $edad = '';
                                if ($animal['edad_anos'] > 0) $edad .= $animal['edad_anos'] . ' años ';
                                if ($animal['edad_meses'] > 0) $edad .= $animal['edad_meses'] . ' meses ';
                                if ($animal['edad_dias'] > 0) $edad .= $animal['edad_dias'] . ' días';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($animal['codigo']) ?></td>
                                    <td><?= htmlspecialchars($animal['sexo']) ?></td>
                                    <td><?= htmlspecialchars($animal['raza']) ?></td>
                                    <td><?= htmlspecialchars($animal['tipo_animal']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($animal['fecha_nacimiento'])) ?></td>
                                    <td><?= htmlspecialchars($animal['estado_prenaz']) ?></td>
                                    <td><?= trim($edad) ?></td>
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
        // Gráfico por tipo de animal
        const ctxTipo = document.getElementById('chartTipo').getContext('2d');
        new Chart(ctxTipo, {
            type: 'bar',
            data: {
                labels: resumenData.map(item => item.tipo_animal),
                datasets: [{
                    label: 'Total de Animales',
                    data: resumenData.map(item => item.total),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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
        
        // Gráfico por sexo
        const ctxSexo = document.getElementById('chartSexo').getContext('2d');
        new Chart(ctxSexo, {
            type: 'pie',
            data: {
                labels: ['Hembras', 'Machos'],
                datasets: [{
                    data: [
                        resumenData.reduce((sum, item) => sum + parseInt(item.hembras), 0),
                        resumenData.reduce((sum, item) => sum + parseInt(item.machos), 0)
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
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

<?php require_once '../../../includes/footer.php'; ?>