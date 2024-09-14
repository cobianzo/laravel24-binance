<?php

function listarCarpetas($ruta) {

    // if the ruta includes vendor, ignore it
    if (strpos($ruta, 'vendor') !== false) {
        return [];
    }
    if (strpos($ruta, 'node_modules') !== false) {
        return [];
    }
    if (strpos($ruta, '.git') !== false) {
        return [];
    }

    $directorios = [];

    // Obtener una lista de todos los elementos del directorio
    $items = scandir($ruta);

    foreach ($items as $item) {
        // Ignorar los directorios especiales "." y ".."
        if ($item === '.' || $item === '..') {
            continue;
        }

        // Construir la ruta completa del elemento
        $rutaCompleta = $ruta . DIRECTORY_SEPARATOR . $item;

        // Verificar si es un directorio
        if (is_dir($rutaCompleta)) {
            // Guardar la ruta del directorio
            $directorios[] = $rutaCompleta;

            // Llamar a la función recursivamente para listar las subcarpetas
            $directorios = array_merge($directorios, listarCarpetas($rutaCompleta));
        } else {
          $directorios[] = '-' . basename($rutaCompleta);
        }
    }

    return $directorios;
}

// Definir la ruta raíz desde donde quieres listar
$ruta = __DIR__; // Cambia esto por la ruta de tu root

// Llamamos a la función
$carpetas = listarCarpetas($ruta);

// Mostrar la lista de carpetas
foreach ($carpetas as $carpeta) {
    echo $carpeta . PHP_EOL;
}


/**
 TODOS LOS PATHS

 > php paths-map.php
/app
/app/Http
/app/Http/Controllers
/app/Http/Controllers/Auth
/app/Http/Middleware
/app/Http/Requests
/app/Http/Requests/Auth
/app/Models
/app/Providers
/bootstrap
/bootstrap/cache
/bootstrap/ssr
/bootstrap/ssr/assets
/config
/database
/database/factories
/database/migrations
/database/seeders
/node_modules
/public
/public/build
/public/build/assets
/resources
/resources/css
/resources/js
/resources/js/Components
/resources/js/Layouts
/resources/js/Pages
/resources/js/Pages/Auth
/resources/js/Pages/Profile
/resources/js/Pages/Profile/Partials
/resources/js/api
/resources/js/types
/resources/views
/routes
/storage
/storage/app
/storage/app/public
/storage/framework
/storage/framework/cache
/storage/framework/cache/data
/storage/framework/sessions
/storage/framework/testing
/storage/framework/views
/storage/logs
/tests
/tests/Feature
/tests/Feature/Auth
/tests/Unit
/vendor
 */