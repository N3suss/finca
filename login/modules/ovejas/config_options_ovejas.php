<?php
// Ruta del archivo JSON donde se guardarán las opciones
define('OPTIONS_FILE_OVEJAS', __DIR__ . '/ovejas_options.json');

// Función para cargar las opciones desde el archivo JSON
function loadOvejasOptions() {
    if (!file_exists(OPTIONS_FILE_OVEJAS)) {
        // Valores por defecto si el archivo no existe
        $defaultOptions = [
            'sexo' => ['HEMBRA', 'MACHO'],
            'raza' => ['DORPER', 'KATAHDIN', 'PELIBUEY', 'SUFFOLK'],
            'tipo_animal' => ['OVEJA', 'CARNERO', 'CORDERO'],
            'estado_prenaz' => ['n/a', 'PREÑADA', 'NO PREÑADA', 'LACTANCIA']
        ];
        
        // Guardar los valores por defecto
        file_put_contents(OPTIONS_FILE_OVEJAS, json_encode($defaultOptions, JSON_PRETTY_PRINT));
        return $defaultOptions;
    }
    
    $json = file_get_contents(OPTIONS_FILE_OVEJAS);
    return json_decode($json, true);
}

// Función para guardar las opciones
function saveOvejasOptions($options) {
    file_put_contents(OPTIONS_FILE_OVEJAS, json_encode($options, JSON_PRETTY_PRINT));
}
?>