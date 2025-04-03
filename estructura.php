<?php
// Guardar este archivo como dir_structure.php y colocarlo en la raíz de tu proyecto
// Luego, accede a él desde tu navegador para ver la estructura

// Funciones de utilidad
function scan_directory($directory, $indent = 0) {
    $result = "";
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $directory . '/' . $file;
        $isDir = is_dir($path);
        
        // Indentación para mostrar la jerarquía
        $spacing = str_repeat('    ', $indent);
        
        // Mostrar archivo o directorio
        $result .= $spacing . ($isDir ? "📁 " : "📄 ") . $file . "\n";
        
        // Si es un directorio, escanear recursivamente (excepto ciertos directorios)
        if ($isDir && !in_array($file, ['vendor', 'node_modules', '.git'])) {
            $result .= scan_directory($path, $indent + 1);
        }
    }
    
    return $result;
}

function show_paths() {
    echo "<h3>Rutas importantes del sistema:</h3>";
    echo "<pre>";
    echo "Directorio actual: " . getcwd() . "\n";
    echo "__DIR__: " . __DIR__ . "\n";
    echo "Directorio raíz del servidor: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "Ruta al script: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estructura de Directorios del Proyecto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #0066cc;
        }
        pre {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
            font-family: Consolas, Monaco, 'Andale Mono', monospace;
            font-size: 14px;
            line-height: 1.4;
        }
        .note {
            background-color: #fff8e1;
            border-left: 4px solid #ffca28;
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .tip {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <h1>Estructura de Directorios del Proyecto</h1>
    
    <div class="note">
        <strong>Nota:</strong> Esta herramienta muestra la estructura de directorios de tu proyecto y rutas importantes para ayudar a solucionar problemas de inclusión de archivos.
    </div>
    
    <?php show_paths(); ?>
    
    <h2>Estructura de Archivos y Directorios</h2>
    <pre><?php 
        $rootDir = dirname(__FILE__); // Directorio donde se encuentra este script
        echo "🏠 " . basename($rootDir) . " (directorio raíz)\n";
        echo scan_directory($rootDir);
    ?></pre>
    
    <h2>Verificación Específica de Archivos Importantes</h2>
    <pre><?php
        $files_to_check = [
            'includes/blog-system.php',
            'admin/includes/header.php',
            'admin/includes/footer.php',
            'admin/vacantes/index.php',
            'includes/jobs-system.php'
        ];
        
        foreach ($files_to_check as $file) {
            $full_path = $rootDir . '/' . $file;
            if (file_exists($full_path)) {
                echo "✅ $file existe en: $full_path\n";
            } else {
                echo "❌ $file no existe en: $full_path\n";
            }
        }
    ?></pre>
    
    <div class="tip">
        <strong>Consejo:</strong> Con base en esta información, asegúrate de que las rutas en los require_once sean correctas según la estructura de tu proyecto.
    </div>
    
    <h3>Ejemplo de código para corrección de rutas</h3>
    <pre>
// En admin/vacantes/index.php:
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/jobs-system.php';
    </pre>
</body>
</html>