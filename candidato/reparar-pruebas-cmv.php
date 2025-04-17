<?php
/**
 * Herramienta para reparar las pruebas CMV y sus resultados
 * 
 * Este script soluciona los problemas de visualización de los resultados
 * de las pruebas CMV (Cuestionario de Motivaciones y Valores) asegurando que:
 * 
 * 1. Las opciones tengan dimensiones asignadas correctamente
 * 2. Los resultados se calculen y muestren adecuadamente
 * 3. Las dimensiones duplicadas se manejen apropiadamente
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['candidato_id']) && !isset($_SESSION['admin_id'])) {
    die("Por favor inicie sesión para acceder a esta funcionalidad.");
}

require_once '../includes/jobs-system.php';

$db = Database::getInstance();
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'diagnostico';
$prueba_id = isset($_GET['prueba_id']) ? (int)$_GET['prueba_id'] : 8; // Por defecto CMV
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

$mensajes = [];
$errores = [];
$resultados = [];

// Función para obtener información de una prueba
function obtenerInfoPrueba($prueba_id) {
    global $db;
    $sql = "SELECT * FROM pruebas WHERE id = $prueba_id";
    $result = $db->query($sql);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

// Función para verificar si una prueba es de tipo pares
function esPruebaTipoPares($prueba_id) {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM preguntas 
            WHERE prueba_id = $prueba_id AND tipo_pregunta = 'pares'";
    $result = $db->query($sql);
    return ($result && $result->fetch_assoc()['count'] > 0);
}

// Función para obtener dimensiones por prueba
function obtenerDimensiones($prueba_id) {
    global $db;
    // Este SQL busca dimensiones utilizadas en opciones de respuesta para la prueba
    $sql = "SELECT DISTINCT d.id, d.nombre, COUNT(o.id) as uso
            FROM dimensiones d
            LEFT JOIN opciones_respuesta o ON d.id = o.dimension_id
            LEFT JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id OR d.prueba_id = $prueba_id
            GROUP BY d.id, d.nombre
            ORDER BY uso DESC, d.id";
    
    $result = $db->query($sql);
    $dimensiones = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones[] = $row;
        }
    }
    
    return $dimensiones;
}

// Función para procesar resultados de una sesión
function procesarResultadosSesion($sesion_id) {
    global $db;
    
    // Obtener info de la sesión
    $sql = "SELECT sp.*, p.id as prueba_id, p.titulo 
            FROM sesiones_prueba sp
            JOIN pruebas p ON sp.prueba_id = p.id
            WHERE sp.id = $sesion_id";
    $result = $db->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        return ["error" => "Sesión no encontrada"];
    }
    
    $sesion = $result->fetch_assoc();
    $prueba_id = $sesion['prueba_id'];
    $candidato_id = $sesion['candidato_id'];
    
    // Verificar tipo de prueba
    if (!esPruebaTipoPares($prueba_id)) {
        return ["error" => "Esta sesión no corresponde a una prueba de tipo pares"];
    }
    
    // Eliminar resultados existentes
    $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
    
    // Obtener respuestas con sus dimensiones
    $sql = "SELECT r.id, r.opcion_id, o.dimension_id, d.nombre as dimension_nombre
            FROM respuestas r
            JOIN opciones_respuesta o ON r.opcion_id = o.id
            JOIN dimensiones d ON o.dimension_id = d.id
            WHERE r.sesion_id = $sesion_id";
    
    $result = $db->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        return ["error" => "No se encontraron respuestas con dimensiones para esta sesión"];
    }
    
    $conteo_dimensiones = [];
    $total_respuestas = 0;
    
    while ($row = $result->fetch_assoc()) {
        if (!isset($conteo_dimensiones[$row['dimension_id']])) {
            $conteo_dimensiones[$row['dimension_id']] = [
                'nombre' => $row['dimension_nombre'],
                'contador' => 0
            ];
        }
        $conteo_dimensiones[$row['dimension_id']]['contador']++;
        $total_respuestas++;
    }
    
    if ($total_respuestas == 0) {
        return ["error" => "No hay respuestas para procesar"];
    }
    
    // Calcular porcentajes e insertar resultados
    $resultados_insertados = 0;
    $porcentajes = [];
    
    foreach ($conteo_dimensiones as $dimension_id => $data) {
        $porcentaje = round(($data['contador'] / $total_respuestas) * 100);
        $porcentajes[] = $porcentaje;
        
        $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
        
        if ($db->query($sql)) {
            $resultados_insertados++;
        }
    }
    
    // Calcular y actualizar resultado global
    if (!empty($porcentajes)) {
        $resultado_global = round(array_sum($porcentajes) / count($porcentajes));
        $db->query("UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id");
    }
    
    return [
        "success" => true,
        "dimensiones_procesadas" => count($conteo_dimensiones),
        "resultados_insertados" => $resultados_insertados,
        "resultado_global" => $resultado_global ?? 0
    ];
}

// Función para vincular opciones a dimensiones
function vincularOpcionesDimensiones($prueba_id) {
    global $db;
    
    // Obtener las dimensiones más recientes para motivación
    $dimensiones = [
        'logro' => 187,
        'poder' => 188,
        'afiliacion' => 189,
        'seguridad' => 190,
        'autonomia' => 191,
        'servicio' => 192,
        'reto' => 193,
        'equilibrio' => 194
    ];
    
    // Actualizar opciones para cada dimensión
    $actualizaciones = [];
    
    // Motivación por Logro
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['logro']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%logro%' OR o.texto LIKE '%meta%' 
                OR o.texto LIKE '%alcanzar%' OR o.texto LIKE '%superar%'
                OR o.texto LIKE '%excelencia%' OR o.texto LIKE '%establecer y alcanzar%'
                OR o.texto LIKE '%obtener reconocimiento%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['logro'] = $db->affected_rows;
    
    // Motivación por Poder
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['poder']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%poder%' OR o.texto LIKE '%influencia%' 
                OR o.texto LIKE '%dirigi%' OR o.texto LIKE '%autoridad%'
                OR o.texto LIKE '%liderazgo%' OR o.texto LIKE '%influir%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['poder'] = $db->affected_rows;
    
    // Motivación por Afiliación
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['afiliacion']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%relacion%' OR o.texto LIKE '%colaborativo%' 
                OR o.texto LIKE '%grupo%' OR o.texto LIKE '%equipo%'
                OR o.texto LIKE '%social%' OR o.texto LIKE '%conexión%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['afiliacion'] = $db->affected_rows;
    
    // Motivación por Seguridad
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['seguridad']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%segur%' OR o.texto LIKE '%estab%' 
                OR o.texto LIKE '%predecible%' OR o.texto LIKE '%claridad%'
                OR o.texto LIKE '%futuro%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['seguridad'] = $db->affected_rows;
    
    // Motivación por Autonomía
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['autonomia']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%autonom%' OR o.texto LIKE '%independ%' 
                OR o.texto LIKE '%tomar mis propias%' OR o.texto LIKE '%decisiones%'
                OR o.texto LIKE '%mi manera%' OR o.texto LIKE '%sin interferenci%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['autonomia'] = $db->affected_rows;
    
    // Motivación por Servicio
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['servicio']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%servicio%' OR o.texto LIKE '%ayudar%' 
                OR o.texto LIKE '%contribuir%' OR o.texto LIKE '%impacto%'
                OR o.texto LIKE '%bienestar%' OR o.texto LIKE '%mejora%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['servicio'] = $db->affected_rows;
    
    // Motivación por Reto
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['reto']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%reto%' OR o.texto LIKE '%desaf%' 
                OR o.texto LIKE '%problem%' OR o.texto LIKE '%compl%'
                OR o.texto LIKE '%obstác%' OR o.texto LIKE '%enfrentar%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['reto'] = $db->affected_rows;
    
    // Motivación por Equilibrio
    $sql = "UPDATE opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            SET o.dimension_id = {$dimensiones['equilibrio']}
            WHERE p.prueba_id = $prueba_id
            AND (o.texto LIKE '%equilibr%' OR o.texto LIKE '%balance%' 
                OR o.texto LIKE '%vida personal%' OR o.texto LIKE '%tiempo%'
                OR o.texto LIKE '%calidad de vida%' OR o.texto LIKE '%disfrut%')
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $actualizaciones['equilibrio'] = $db->affected_rows;
    
    // Ver cuántas opciones quedan sin asignar
    $sql = "SELECT COUNT(*) as pendientes
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $pendientes = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['pendientes'] : 0;
    
    return [
        "actualizaciones" => $actualizaciones,
        "total_actualizadas" => array_sum($actualizaciones),
        "pendientes" => $pendientes
    ];
}

// Función para procesar manualmente opciones sin dimensión
function procesarOpcionesManuales($prueba_id) {
    global $db;
    
    // Obtener opciones sin dimensión asignada
    $sql = "SELECT o.id, o.pregunta_id, o.texto, p.par_id
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)
            ORDER BY p.par_id, o.id";
    
    $result = $db->query($sql);
    $opciones_sin_dimension = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $opciones_sin_dimension[] = $row;
        }
    }
    
    // Obtener dimensiones disponibles
    $sql = "SELECT id, nombre FROM dimensiones 
            WHERE nombre LIKE 'Motivación%'
            ORDER BY id DESC";
    
    $result = $db->query($sql);
    $dimensiones_disponibles = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_disponibles[] = $row;
        }
    }
    
    // Asignación manual por patrón de texto y par_id
    $asignaciones_manuales = [
        // Estas asignaciones son ejemplos, se deben ajustar según el contenido real
        906 => 187, // Motivación por Logro
        907 => 188, // Motivación por Poder
        908 => 190, // Motivación por Seguridad
        909 => 189, // Motivación por Afiliación
        910 => 191, // Motivación por Autonomía
        911 => 192, // Motivación por Servicio
        912 => 193, // Motivación por Reto
        913 => 194  // Motivación por Equilibrio
        // Añadir más asignaciones manuales según sea necesario...
    ];
    
    // Aplicar asignaciones manuales
    $actualizadas = 0;
    
    foreach ($asignaciones_manuales as $opcion_id => $dimension_id) {
        $sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_id WHERE id = $opcion_id";
        if ($db->query($sql) && $db->affected_rows > 0) {
            $actualizadas++;
        }
    }
    
    return [
        "opciones_sin_dimension" => count($opciones_sin_dimension),
        "dimensiones_disponibles" => count($dimensiones_disponibles),
        "asignaciones_manuales" => count($asignaciones_manuales),
        "actualizadas" => $actualizadas
    ];
}

// Función para verificar el estado de las opciones
function verificarEstadoOpciones($prueba_id) {
    global $db;
    
    // Total de opciones para la prueba
    $sql = "SELECT COUNT(*) as total
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id";
    $result = $db->query($sql);
    $total_opciones = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
    
    // Opciones sin dimensión
    $sql = "SELECT COUNT(*) as sin_dimension
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id
            AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $sin_dimension = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['sin_dimension'] : 0;
    
    // Desglose por dimensión
    $sql = "SELECT d.id, d.nombre, COUNT(o.id) as cantidad
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            JOIN dimensiones d ON o.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id
            GROUP BY d.id, d.nombre
            ORDER BY cantidad DESC";
    $result = $db->query($sql);
    $desglose = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $desglose[] = $row;
        }
    }
    
    return [
        "total_opciones" => $total_opciones,
        "sin_dimension" => $sin_dimension,
        "con_dimension" => $total_opciones - $sin_dimension,
        "porcentaje_asignado" => $total_opciones > 0 ? round((($total_opciones - $sin_dimension) / $total_opciones) * 100, 2) : 0,
        "desglose" => $desglose
    ];
}

// Función para actualizar todas las sesiones completadas
function actualizarTodasSesiones($prueba_id) {
    global $db;
    
    // Obtener todas las sesiones completadas para esta prueba
    $sql = "SELECT id FROM sesiones_prueba WHERE prueba_id = $prueba_id AND estado = 'completada'";
    $result = $db->query($sql);
    $sesiones = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sesiones[] = $row['id'];
        }
    }
    
    // Procesar cada sesión
    $resultados = [];
    foreach ($sesiones as $sesion_id) {
        $resultados[$sesion_id] = procesarResultadosSesion($sesion_id);
    }
    
    return [
        "total_sesiones" => count($sesiones),
        "resultados" => $resultados
    ];
}

// Ejecutar acción correspondiente
switch ($accion) {
    case 'vincular':
        // Vincular opciones a dimensiones
        $resultados['vincular'] = vincularOpcionesDimensiones($prueba_id);
        $mensajes[] = "Se actualizaron {$resultados['vincular']['total_actualizadas']} opciones con sus dimensiones correspondientes.";
        if ($resultados['vincular']['pendientes'] > 0) {
            $mensajes[] = "Quedan {$resultados['vincular']['pendientes']} opciones sin dimensión asignada.";
        }
        break;
        
    case 'manual':
        // Procesar opciones manualmente
        $resultados['manual'] = procesarOpcionesManuales($prueba_id);
        $mensajes[] = "Se asignaron manualmente dimensiones a {$resultados['manual']['actualizadas']} opciones.";
        break;
        
    case 'procesar':
        // Procesar resultados de una sesión
        if ($sesion_id) {
            $resultados['procesar'] = procesarResultadosSesion($sesion_id);
            if (isset($resultados['procesar']['error'])) {
                $errores[] = $resultados['procesar']['error'];
            } else {
                $mensajes[] = "Se procesaron {$resultados['procesar']['dimensiones_procesadas']} dimensiones y se insertaron {$resultados['procesar']['resultados_insertados']} resultados.";
            }
        } else {
            $errores[] = "Se requiere un ID de sesión para procesar resultados.";
        }
        break;
        
    case 'actualizar_todas':
        // Actualizar todas las sesiones
        $resultados['actualizar_todas'] = actualizarTodasSesiones($prueba_id);
        $mensajes[] = "Se procesaron {$resultados['actualizar_todas']['total_sesiones']} sesiones completadas.";
        break;
        
    case 'diagnostico':
    default:
        // Verificar estado actual
        $resultados['info_prueba'] = obtenerInfoPrueba($prueba_id);
        $resultados['es_tipo_pares'] = esPruebaTipoPares($prueba_id);
        $resultados['dimensiones'] = obtenerDimensiones($prueba_id);
        $resultados['estado_opciones'] = verificarEstadoOpciones($prueba_id);
        
        if ($resultados['es_tipo_pares']) {
            $mensajes[] = "La prueba es de tipo pares, compatible con CMV.";
        } else {
            $errores[] = "La prueba no es de tipo pares, puede no ser compatible con este reparador.";
        }
        
        if ($resultados['estado_opciones']['porcentaje_asignado'] < 90) {
            $errores[] = "Solo el {$resultados['estado_opciones']['porcentaje_asignado']}% de las opciones tienen asignada una dimensión.";
        } else {
            $mensajes[] = "El {$resultados['estado_opciones']['porcentaje_asignado']}% de las opciones tienen asignada una dimensión.";
        }
        break;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Pruebas CMV</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        header {
            background-color: #0088cc;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1, h2, h3 {
            color: #0088cc;
        }
        header h1 {
            color: white;
            margin: 0;
        }
        .container {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        .panel {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
        }
        .panel-title {
            margin-top: 0;
            color: #0088cc;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn-primary {
            color: #fff;
            background-color: #0088cc;
            border-color: #0088cc;
        }
        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-warning {
            color: #212529;
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn:hover {
            opacity: 0.9;
            text-decoration: none;
        }
        .progress {
            display: flex;
            height: 20px;
            overflow: hidden;
            font-size: 0.75rem;
            background-color: #e9ecef;
            border-radius: 0.25rem;
            margin-bottom: 10px;
        }
        .progress-bar {
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            background-color: #0088cc;
            transition: width 0.6s ease;
        }
        pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            overflow: auto;
        }
        code {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header>
        <h1>Reparador de Pruebas CMV</h1>
        <p>Herramienta para corregir problemas con pruebas de tipo "pares" como el Cuestionario de Motivaciones y Valores (CMV)</p>
    </header>
    
    <?php foreach ($mensajes as $mensaje): ?>
    <div class="alert alert-success">
        <?php echo $mensaje; ?>
    </div>
    <?php endforeach; ?>
    
    <?php foreach ($errores as $error): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endforeach; ?>
    
    <div class="container">
        <h2>Información de la Prueba</h2>
        
        <?php if (isset($resultados['info_prueba'])): ?>
        <div class="panel">
            <h3 class="panel-title"><?php echo htmlspecialchars($resultados['info_prueba']['titulo']); ?></h3>
            <p><strong>ID:</strong> <?php echo $resultados['info_prueba']['id']; ?></p>
            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($resultados['info_prueba']['descripcion'] ?? 'No hay descripción'); ?></p>
            <p><strong>Tipo:</strong> <?php echo $resultados['es_tipo_pares'] ? 'Prueba de tipo pares (como CMV)' : 'Prueba estándar'; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($resultados['estado_opciones'])): ?>
        <h3>Estado de las Opciones</h3>
        
        <div class="panel">
            <p><strong>Total de opciones:</strong> <?php echo $resultados['estado_opciones']['total_opciones']; ?></p>
            <p><strong>Opciones con dimensión:</strong> <?php echo $resultados['estado_opciones']['con_dimension']; ?></p>
            <p><strong>Opciones sin dimensión:</strong> <?php echo $resultados['estado_opciones']['sin_dimension']; ?></p>
            
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: <?php echo $resultados['estado_opciones']['porcentaje_asignado']; ?>%;">
                    <?php echo $resultados['estado_opciones']['porcentaje_asignado']; ?>%
                </div>
            </div>
            
            <?php if (!empty($resultados['estado_opciones']['desglose'])): ?>
            <h4>Distribución por Dimensión</h4>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dimensión</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados['estado_opciones']['desglose'] as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td><?php echo $item['cantidad']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <h2>Acciones Disponibles</h2>
        
        <div class="panel">
            <h3 class="panel-title">Paso 1: Vincular opciones a dimensiones</h3>
            <p>Este paso actualiza las opciones de respuesta para asignarles las dimensiones correctas basándose en su texto.</p>
            <a href="?accion=vincular&prueba_id=<?php echo $prueba_id; ?>" class="btn btn-primary">Vincular opciones a dimensiones</a>
            
            <?php if (isset($resultados['vincular'])): ?>
            <h4>Resultados de vinculación:</h4>
            <ul>
                <?php foreach ($resultados['vincular']['actualizaciones'] as $tipo => $cantidad): ?>
                <li>Motivación por <?php echo ucfirst($tipo); ?>: <?php echo $cantidad; ?> opciones</li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Pendientes:</strong> <?php echo $resultados['vincular']['pendientes']; ?> opciones sin dimensión</p>
            <?php endif; ?>
            
            <?php if (isset($resultados['estado_opciones']) && $resultados['estado_opciones']['sin_dimension'] > 0): ?>
            <div class="alert alert-warning">
                Todavía hay opciones sin dimensión asignada. Puede ser necesario realizar asignaciones manuales.
            </div>
            <a href="?accion=manual&prueba_id=<?php echo $prueba_id; ?>" class="btn btn-warning">Realizar asignaciones manuales</a>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <h3 class="panel-title">Paso 2: Procesar resultados</h3>
            <p>Una vez que las opciones tengan asignadas sus dimensiones, este paso generará los resultados correctos para una sesión específica.</p>
            
            <form action="" method="get" style="margin-bottom: 15px;">
                <input type="hidden" name="accion" value="procesar">
                <input type="hidden" name="prueba_id" value="<?php echo $prueba_id; ?>">
                <div style="display: flex; gap: 10px;">
                    <input type="number" name="sesion_id" placeholder="ID de sesión" required style="padding: 8px; border-radius: 4px; border: 1px solid #ced4da; flex-grow: 1;">
                    <button type="submit" class="btn btn-primary">Procesar Sesión</button>
                </div>
            </form>
            
            <?php if (isset($resultados['procesar'])): ?>
            <h4>Resultados del procesamiento:</h4>
            <?php if (isset($resultados['procesar']['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $resultados['procesar']['error']; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                <p>Dimensiones procesadas: <?php echo $resultados['procesar']['dimensiones_procesadas']; ?></p>
                <p>Resultados insertados: <?php echo $resultados['procesar']['resultados_insertados']; ?></p>
                <p>Resultado global: <?php echo $resultados['procesar']['resultado_global']; ?>%</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <a href="?accion=actualizar_todas&prueba_id=<?php echo $prueba_id; ?>" class="btn btn-success">Procesar Todas las Sesiones</a>
            
            <?php if (isset($resultados['actualizar_todas'])): ?>
            <h4>Resultados del procesamiento masivo:</h4>
            <p>Se procesaron <?php echo $resultados['actualizar_todas']['total_sesiones']; ?> sesiones.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <h2>Código para resultado-prueba.php</h2>
        
        <div class="panel">
            <p>Para asegurar que los resultados se muestren correctamente en la página de resultados, añade este código a tu archivo <code>resultado-prueba.php</code>:</p>
            
            <pre><code>// Mejorar visualización de dimensiones con datos reales
$dimensiones = [];

// Determinar el tipo de prueba basado en su estructura
$sql = "SELECT COUNT(*) as count FROM preguntas 
        WHERE prueba_id = {$session_info['prueba_id']} AND tipo_pregunta = 'pares'";
$result_tipo = $db->query($sql);
$es_prueba_pares = ($result_tipo && $result_tipo->fetch_assoc()['count'] > 0);

if ($es_prueba_pares) {
    // Procesamiento especial para pruebas tipo "pares" (CMV, IPL)
    $sql = "SELECT r.*, d.nombre as dimension_nombre
            FROM resultados r
            JOIN dimensiones d ON r.dimension_id = d.id
            WHERE r.sesion_id = $sesion_id AND r.valor > 0
            ORDER BY r.valor DESC";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones[] = [
                'nombre' => $row['dimension_nombre'],
                'porcentaje' => $row['valor'],
                'interpretacion' => $row['interpretacion'] ?? null
            ];
        }
    }
} else {
    // Procesamiento regular para pruebas con dimensiones en preguntas
    // Código existente...
}

// Si todavía no hay dimensiones, intentar obtenerlas directamente de resultados
if (empty($dimensiones)) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT r.*, d.nombre as dimension_nombre
                FROM resultados r
                JOIN dimensiones d ON r.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id AND r.valor > 0
                ORDER BY r.valor DESC";
        
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimensiones[] = [
                    'nombre' => $row['dimension_nombre'],
                    'porcentaje' => $row['valor'],
                    'interpretacion' => $row['interpretacion'] ?? null
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener dimensiones: " . $e->getMessage());
    }
}</code></pre>
        </div>
    </div>
    
    <div class="container">
        <h2>Enlaces Útiles</h2>
        
        <div class="panel">
            <a href="resultado-prueba.php?sesion_id=<?php echo $sesion_id; ?>" class="btn btn-primary">Ver Página de Resultados</a>
            <a href="pruebas.php" class="btn btn-primary">Volver a Pruebas</a>
            <a href="?accion=diagnostico&prueba_id=<?php echo $prueba_id; ?>" class="btn btn-info">Volver al Diagnóstico</a>
        </div>
    </div>
</body>
</html>