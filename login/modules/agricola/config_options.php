<?php
// Ruta del archivo JSON donde se guardarán las opciones
define('OPTIONS_FILE', __DIR__ . '/agricola_options.json');

// Función para cargar las opciones desde el archivo JSON
function loadOptions() {
    if (!file_exists(OPTIONS_FILE)) {
        // Valores por defecto si el archivo no existe
        $defaultOptions = [
            'tipo_movimiento' => ['INGRESO', 'EGRESO', 'TRASLADO'],
            'desglose_movimiento' => ['PRODUCCIÓN AGRÍCOLA', 'COMPRA', 'VENTA', 'TRASLADO INTERNO'],
            'ubicacion' => ['FINCA 1', 'FINCA 2', 'BODEGA CENTRAL'],
            'lugar_prod' => ['CAFETALES', 'POTREROS', 'INVERNADERO', 'VIVEROS'],
            'rubro' => ['CAFÉ', 'LECHE', 'HORTALIZAS', 'FRUTALES'],
            'producto' => ['CAFÉ MADURO', 'CAFÉ VERDE', 'LECHE FRESCA', 'TOMATE', 'LECHUGA'],
            'unidad_medida' => ['quintal', 'libra', 'galón', 'litro', 'unidad']
        ];
        
        // Guardar los valores por defecto
        file_put_contents(OPTIONS_FILE, json_encode($defaultOptions, JSON_PRETTY_PRINT));
        return $defaultOptions;
    }
    
    $json = file_get_contents(OPTIONS_FILE);
    return json_decode($json, true);
}

// Función para guardar las opciones
function saveOptions($options) {
    file_put_contents(OPTIONS_FILE, json_encode($options, JSON_PRETTY_PRINT));
}
?>