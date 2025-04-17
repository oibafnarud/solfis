<?php
/**
 * Reparador de pruebas
 * 
 * Esta herramienta diagnostica y repara los resultados para diferentes tipos de pruebas:
 * - Inventario de Personalidad Laboral (IPL)
 * - Test de Aptitudes Cognitivas (TAC)
 * - Evaluación de Competencias Fundamentales (ECF)
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['candidato_id']) && !isset($_SESSION['admin_id'])) {
    die("Por favor inicie sesión para acceder a esta herramienta.");
}

require_once '../includes/jobs-system.php';

$db = Database::getInstance();
$prueba_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$prueba_id = isset($_GET['prueba_id']) ? (int)$_GET['prueba_id'] : 0;
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'diagnostico';

$mensajes = [];
$errores = [];
$resultados = [];

// Función para obtener info de la prueba
function obtenerInfoPrueba($tipo) {
    global $db;
    
    switch ($tipo) {
        case 'ipl':
            $sql = "SELECT id, titulo, descripcion FROM pruebas WHERE titulo LIKE '%Personalidad%'";
            break;
        case 'tac':
            $sql = "SELECT id, titulo, descripcion FROM pruebas WHERE titulo LIKE '%Aptitudes%'";
            break;
        case 'ecf':
            $sql = "SELECT id, titulo, descripcion FROM pruebas WHERE titulo LIKE '%Competencias%'";
            break;
        default:
            $sql = "SELECT id, titulo, descripcion FROM pruebas";
    }
    
    $result = $db->query($sql);
    
    $pruebas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pruebas[] = $row;
        }
    }
    
    return $pruebas;
}

// Función para obtener dimensiones de una prueba
function obtenerDimensionesPrueba($prueba_id) {
    global $db;
    $sql = "SELECT DISTINCT d.id, d.nombre, d.tipo, d.bipolar
            FROM dimensiones d
            JOIN preguntas p ON d.id = p.dimension_id
            WHERE p.prueba_id = $prueba_id
            ORDER BY d.id";
            
    $result = $db->query($sql);
    
    $dimensiones = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones[] = $row;
        }
    }
    
    return $dimensiones;
}

// Función para detectar el tipo de estructura de la prueba
function detectarEstructuraPrueba($prueba_id) {
    global $db;
    
    // Verificar tipo de preguntas
    $sql = "SELECT tipo_pregunta, COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id GROUP BY tipo_pregunta";
    $result = $db->query($sql);
    
    $estructura = [
        'tipos_pregunta' => [],
        'dimensiones_en_preguntas' => 0,
        'dimensiones_en_opciones' => 0,
        'total_preguntas' => 0,
        'tiene_pares' => false
    ];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $estructura['tipos_pregunta'][$row['tipo_pregunta']] = $row['total'];
            $estructura['total_preguntas'] += $row['total'];
            
            if ($row['tipo_pregunta'] == 'pares') {
                $estructura['tiene_pares'] = true;
            }
        }
    }
    
    // Contar preguntas con dimensión asignada
    $sql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id AND dimension_id IS NOT NULL";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $estructura['dimensiones_en_preguntas'] = $result->fetch_assoc()['total'];
    }
    
    // Contar opciones con dimensión asignada
    $sql = "SELECT COUNT(*) as total FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id AND o.dimension_id IS NOT NULL";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $estructura['dimensiones_en_opciones'] = $result->fetch_assoc()['total'];
    }
    
    return $estructura;
}

// Función para reparar dimensiones de TAC
function repararDimensionesTAC($prueba_id) {
    global $db;
    $resultados = [];
    
    // Verificar dimensiones existentes para TAC
    $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN ('Razonamiento Verbal', 'Razonamiento Numérico', 'Razonamiento Lógico', 'Atención al Detalle')";
    $result = $db->query($sql);
    
    $dimensiones_tac = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_tac[$row['nombre']] = $row['id'];
        }
    }
    
    // Si no existen todas las dimensiones, crearlas
    $dimensiones_necesarias = [
        'Razonamiento Verbal',
        'Razonamiento Numérico',
        'Razonamiento Lógico',
        'Atención al Detalle'
    ];
    
    foreach ($dimensiones_necesarias as $dimension) {
        if (!isset($dimensiones_tac[$dimension])) {
            $sql = "INSERT INTO dimensiones (nombre, tipo) VALUES ('$dimension', 'cognitiva')";
            
            if ($db->query($sql)) {
                $dimensiones_tac[$dimension] = $db->insert_id;
                $resultados[] = "Dimensión '$dimension' creada con ID: " . $db->insert_id;
            }
        }
    }
    
    // Asignar dimensiones a preguntas según su contenido
    $sql = "SELECT id, texto FROM preguntas WHERE prueba_id = $prueba_id AND dimension_id IS NULL";
    $result = $db->query($sql);
    
    $asignaciones = [
        'verbal' => 0,
        'numerico' => 0,
        'logico' => 0,
        'detalle' => 0
    ];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $texto = strtolower($row['texto']);
            $pregunta_id = $row['id'];
            $dimension_id = null;
            
            // Asignar dimensión según el contenido de la pregunta
            if (strpos($texto, 'palabra') !== false || 
                strpos($texto, 'texto') !== false || 
                strpos($texto, 'lectura') !== false) {
                $dimension_id = $dimensiones_tac['Razonamiento Verbal'];
                $asignaciones['verbal']++;
            }
            elseif (strpos($texto, 'número') !== false || 
                   strpos($texto, 'cálculo') !== false || 
                   strpos($texto, 'matemáticas') !== false) {
                $dimension_id = $dimensiones_tac['Razonamiento Numérico'];
                $asignaciones['numerico']++;
            }
            elseif (strpos($texto, 'lógica') !== false || 
                   strpos($texto, 'secuencia') !== false || 
                   strpos($texto, 'patrón') !== false) {
                $dimension_id = $dimensiones_tac['Razonamiento Lógico'];
                $asignaciones['logico']++;
            }
            else {
                $dimension_id = $dimensiones_tac['Atención al Detalle'];
                $asignaciones['detalle']++;
            }
            
            // Actualizar pregunta
            if ($dimension_id) {
                $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
                $db->query($update_sql);
            }
        }
    }
    
    return [
        'dimensiones_creadas' => count($resultados),
        'asignaciones' => $asignaciones,
        'mensajes' => $resultados
    ];
}

// Función mejorada para reparar dimensiones de IPL
function repararDimensionesIPL($prueba_id) {
    global $db;
    $resultados = [];
    
    // Verificar dimensiones existentes para IPL
    $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN (
        'Extroversión vs. Introversión', 
        'Estabilidad vs. Reactividad Emocional', 
        'Apertura vs. Convencionalidad', 
        'Responsabilidad', 
        'Cooperación vs. Independencia'
    )";
    $result = $db->query($sql);
    
    $dimensiones_ipl = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_ipl[$row['nombre']] = $row['id'];
        }
    }
    
    // Si no existen todas las dimensiones, crearlas
    $dimensiones_necesarias = [
        'Extroversión vs. Introversión',
        'Estabilidad vs. Reactividad Emocional',
        'Apertura vs. Convencionalidad',
        'Responsabilidad',
        'Cooperación vs. Independencia'
    ];
    
    foreach ($dimensiones_necesarias as $dimension) {
        if (!isset($dimensiones_ipl[$dimension])) {
            $sql = "INSERT INTO dimensiones (nombre, tipo, bipolar) VALUES ('$dimension', 'personalidad', 1)";
            
            if ($db->query($sql)) {
                $dimensiones_ipl[$dimension] = $db->insert_id;
                $resultados[] = "Dimensión '$dimension' creada con ID: " . $db->insert_id;
            }
        }
    }
    
    // Si IPL es de tipo pares, vincular opciones a dimensiones
    $sql = "SELECT COUNT(*) as count FROM preguntas WHERE prueba_id = $prueba_id AND tipo_pregunta = 'pares'";
    $result = $db->query($sql);
    $es_prueba_pares = ($result && $result->fetch_assoc()['count'] > 0);
    
    $asignaciones = [
        'extroversion' => 0,
        'estabilidad' => 0,
        'apertura' => 0,
        'responsabilidad' => 0,
        'cooperacion' => 0
    ];
    
    if ($es_prueba_pares) {
        // Para pruebas de tipo pares, asignar dimensiones a las opciones
        $sql = "SELECT o.id 
                FROM opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                WHERE p.prueba_id = $prueba_id AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $db->query($sql);
        
        $opciones_sin_dimension = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $opciones_sin_dimension[] = $row['id'];
            }
        }
        
        $dimension_keys = array_keys($dimensiones_ipl);
        $total_opciones = count($opciones_sin_dimension);
        
        for ($i = 0; $i < $total_opciones; $i++) {
            $opcion_id = $opciones_sin_dimension[$i];
            // Determinar qué dimensión asignar (distribución equitativa)
            $dimension_index = $i % count($dimension_keys);
            $dimension_nombre = $dimension_keys[$dimension_index];
            $dimension_id = $dimensiones_ipl[$dimension_nombre];
            
            // Actualizar opción
            $update_sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_id WHERE id = $opcion_id";
            if ($db->query($update_sql)) {
                // Determinar qué contador incrementar
                if ($dimension_nombre == 'Extroversión vs. Introversión') {
                    $asignaciones['extroversion']++;
                } elseif ($dimension_nombre == 'Estabilidad vs. Reactividad Emocional') {
                    $asignaciones['estabilidad']++;
                } elseif ($dimension_nombre == 'Apertura vs. Convencionalidad') {
                    $asignaciones['apertura']++;
                } elseif ($dimension_nombre == 'Responsabilidad') {
                    $asignaciones['responsabilidad']++;
                } elseif ($dimension_nombre == 'Cooperación vs. Independencia') {
                    $asignaciones['cooperacion']++;
                }
            }
        }
    } else {
        // Para pruebas estándar, asignar dimensiones a las preguntas
        $sql = "SELECT id FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
        $result = $db->query($sql);
        
        $preguntas_sin_dimension = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $preguntas_sin_dimension[] = $row['id'];
            }
        }
        
        $dimension_keys = array_keys($dimensiones_ipl);
        $total_preguntas = count($preguntas_sin_dimension);
        
        for ($i = 0; $i < $total_preguntas; $i++) {
            $pregunta_id = $preguntas_sin_dimension[$i];
            // Determinar qué dimensión asignar (distribución equitativa)
            $dimension_index = $i % count($dimension_keys);
            $dimension_nombre = $dimension_keys[$dimension_index];
            $dimension_id = $dimensiones_ipl[$dimension_nombre];
            
            // Actualizar pregunta
            $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
            if ($db->query($update_sql)) {
                // Determinar qué contador incrementar
                if ($dimension_nombre == 'Extroversión vs. Introversión') {
                    $asignaciones['extroversion']++;
                } elseif ($dimension_nombre == 'Estabilidad vs. Reactividad Emocional') {
                    $asignaciones['estabilidad']++;
                } elseif ($dimension_nombre == 'Apertura vs. Convencionalidad') {
                    $asignaciones['apertura']++;
                } elseif ($dimension_nombre == 'Responsabilidad') {
                    $asignaciones['responsabilidad']++;
                } elseif ($dimension_nombre == 'Cooperación vs. Independencia') {
                    $asignaciones['cooperacion']++;
                }
            }
        }
    }
    
    return [
        'dimensiones_creadas' => count($resultados),
        'asignaciones' => $asignaciones,
        'mensajes' => $resultados,
        'es_prueba_pares' => $es_prueba_pares,
        'elementos_procesados' => $es_prueba_pares ? count($opciones_sin_dimension ?? []) : count($preguntas_sin_dimension ?? [])
    ];
}


// Función mejorada para reparar dimensiones de ECF
function repararDimensionesECF($prueba_id) {
    global $db;
    $resultados = [];
    
    // Verificar dimensiones existentes para ECF
    $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN (
        'Comunicación Básica', 
        'Trabajo en Equipo', 
        'Adaptabilidad', 
        'Integridad', 
        'Meticulosidad vs. Flexibilidad'
    )";
    $result = $db->query($sql);
    
    $dimensiones_ecf = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_ecf[$row['nombre']] = $row['id'];
        }
    }
    
    // Si no existen todas las dimensiones, crearlas
    $dimensiones_necesarias = [
        'Comunicación Básica',
        'Trabajo en Equipo',
        'Adaptabilidad',
        'Integridad',
        'Meticulosidad vs. Flexibilidad'
    ];
    
    foreach ($dimensiones_necesarias as $dimension) {
        if (!isset($dimensiones_ecf[$dimension])) {
            $sql = "INSERT INTO dimensiones (nombre, tipo) VALUES ('$dimension', 'competencia')";
            
            if ($db->query($sql)) {
                $dimensiones_ecf[$dimension] = $db->insert_id;
                $resultados[] = "Dimensión '$dimension' creada con ID: " . $db->insert_id;
            }
        }
    }
    
    // Obtener todas las preguntas sin dimensión
    $sql = "SELECT id FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
    $result = $db->query($sql);
    
    $preguntas_sin_dimension = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $preguntas_sin_dimension[] = $row['id'];
        }
    }
    
    // Asignar dimensiones a las preguntas de forma equitativa
    $asignaciones = [
        'comunicacion' => 0,
        'trabajo_equipo' => 0,
        'adaptabilidad' => 0,
        'integridad' => 0,
        'meticulosidad' => 0
    ];
    
    $dimension_keys = array_keys($dimensiones_ecf);
    $total_preguntas = count($preguntas_sin_dimension);
    
    for ($i = 0; $i < $total_preguntas; $i++) {
        $pregunta_id = $preguntas_sin_dimension[$i];
        // Determinar qué dimensión asignar (distribución equitativa)
        $dimension_index = $i % count($dimension_keys);
        $dimension_nombre = $dimension_keys[$dimension_index];
        $dimension_id = $dimensiones_ecf[$dimension_nombre];
        
        // Actualizar pregunta
        $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
        if ($db->query($update_sql)) {
            // Determinar qué contador incrementar
            if ($dimension_nombre == 'Comunicación Básica') {
                $asignaciones['comunicacion']++;
            } elseif ($dimension_nombre == 'Trabajo en Equipo') {
                $asignaciones['trabajo_equipo']++;
            } elseif ($dimension_nombre == 'Adaptabilidad') {
                $asignaciones['adaptabilidad']++;
            } elseif ($dimension_nombre == 'Integridad') {
                $asignaciones['integridad']++;
            } elseif ($dimension_nombre == 'Meticulosidad vs. Flexibilidad') {
                $asignaciones['meticulosidad']++;
            }
        }
    }
    
    return [
        'dimensiones_creadas' => count($resultados),
        'asignaciones' => $asignaciones,
        'mensajes' => $resultados,
        'preguntas_procesadas' => $total_preguntas
    ];
}

// Función para regenerar resultados de una sesión
function regenerarResultadosSesion($sesion_id) {
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
    
    // Detectar tipo de prueba
    $estructura = detectarEstructuraPrueba($prueba_id);
    
    // Eliminar resultados existentes
    $sql = "DELETE FROM resultados WHERE sesion_id = $sesion_id";
    $db->query($sql);
    
    $resultados_generados = 0;
    $dimensiones_procesadas = 0;
    
    // Procesar según la estructura de la prueba
    if ($estructura['tiene_pares']) {
        // Para pruebas tipo "pares" (IPL, CMV) - agrupar por dimensión de opción
        $sql = "SELECT o.dimension_id, d.nombre, COUNT(*) as count
                FROM respuestas r
                JOIN opciones_respuesta o ON r.opcion_id = o.id
                JOIN dimensiones d ON o.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id
                GROUP BY o.dimension_id, d.nombre";
                
        $result = $db->query($sql);
        
        $total_respuestas = 0;
        $conteo_dimensiones = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conteo_dimensiones[$row['dimension_id']] = [
                    'nombre' => $row['nombre'],
                    'count' => $row['count']
                ];
                $total_respuestas += $row['count'];
                $dimensiones_procesadas++;
            }
            
            // Insertar resultados
            if ($total_respuestas > 0) {
                foreach ($conteo_dimensiones as $dimension_id => $data) {
                    $porcentaje = round(($data['count'] / $total_respuestas) * 100);
                    
                    $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                  VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if ($db->query($insert_sql)) {
                        $resultados_generados++;
                    }
                }
            }
        }
    } else {
        // Para pruebas con dimensiones en preguntas (TAC, ECF)
        $sql = "SELECT p.dimension_id, d.nombre, COUNT(*) as total_preguntas
                FROM respuestas r
                JOIN preguntas p ON r.pregunta_id = p.id
                JOIN dimensiones d ON p.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id
                GROUP BY p.dimension_id, d.nombre";
                
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimension_id = $row['dimension_id'];
                $total_preguntas = $row['total_preguntas'];
                
                // Asignar un valor básico de 50% o
                // Usar un cálculo diferente según la lógica de tu aplicación
                $porcentaje = 50;
                
                // Insertar resultado
                $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                              VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                
                if ($db->query($insert_sql)) {
                    $resultados_generados++;
                }
                
                $dimensiones_procesadas++;
            }
        }
    }
    
    // Calcular y actualizar resultado global
    if ($resultados_generados > 0) {
        $sql = "SELECT AVG(valor) as promedio FROM resultados WHERE sesion_id = $sesion_id";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $resultado_global = round($result->fetch_assoc()['promedio']);
            
            $update_sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id";
            $db->query($update_sql);
        }
    }
    
    return [
        "dimensiones_procesadas" => $dimensiones_procesadas,
        "resultados_generados" => $resultados_generados
    ];
}

// Ejecutar acción según el tipo de prueba seleccionado
if (!empty($prueba_tipo)) {
    switch($prueba_tipo) {
        case 'tac':
            $pruebas = obtenerInfoPrueba('tac');
            if (!empty($pruebas)) {
                $prueba_id = $pruebas[0]['id'];
                $resultados['info_prueba'] = $pruebas[0];
                $resultados['estructura'] = detectarEstructuraPrueba($prueba_id);
                
                if ($accion == 'reparar') {
                    $resultados['reparacion'] = repararDimensionesTAC($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura del Test de Aptitudes Cognitivas (TAC)";
                }
            } else {
                $errores[] = "No se encontró la prueba TAC en la base de datos";
            }
            break;
            
        case 'ipl':
            $pruebas = obtenerInfoPrueba('ipl');
            if (!empty($pruebas)) {
                $prueba_id = $pruebas[0]['id'];
                $resultados['info_prueba'] = $pruebas[0];
                $resultados['estructura'] = detectarEstructuraPrueba($prueba_id);
                
                if ($accion == 'reparar') {
                    $resultados['reparacion'] = repararDimensionesIPL($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura del Inventario de Personalidad Laboral (IPL)";
                }
            } else {
                $errores[] = "No se encontró la prueba IPL en la base de datos";
            }
            break;
            
        case 'ecf':
            $pruebas = obtenerInfoPrueba('ecf');
            if (!empty($pruebas)) {
                $prueba_id = $pruebas[0]['id'];
                $resultados['info_prueba'] = $pruebas[0];
                $resultados['estructura'] = detectarEstructuraPrueba($prueba_id);
                
                if ($accion == 'reparar') {
                    $resultados['reparacion'] = repararDimensionesECF($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura de la Evaluación de Competencias Fundamentales (ECF)";
                }
            } else {
                $errores[] = "No se encontró la prueba ECF en la base de datos";
            }
            break;
            
        default:
            $errores[] = "Tipo de prueba no reconocido";
    }
    
    if ($sesion_id > 0 && $accion == 'regenerar') {
        $resultados['regeneracion'] = regenerarResultadosSesion($sesion_id);
        $mensajes[] = "Se han regenerado los resultados para la sesión " . $sesion_id;
    }
} else {
    // Si no se especificó un tipo, obtener todas las pruebas
    $resultados['pruebas'] = obtenerInfoPrueba('');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparador de Pruebas</title>
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
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 1.25rem;
            margin-top: 0;
            margin-bottom: 15px;
            color: #0088cc;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            margin-right: 5px;
        }
        .badge-primary {
            color: #fff;
            background-color: #0088cc;
        }
        .badge-success {
            color: #fff;
            background-color: #28a745;
        }
        .badge-warning {
            color: #212529;
            background-color: #ffc107;
        }
        .badge-danger {
            color: #fff;
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <h1>Reparador de Pruebas</h1>
        <p>Herramienta para diagnosticar y reparar problemas en las pruebas</p>
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
    
    <?php if (empty($prueba_tipo)): ?>
    <div class="container">
        <h2>Seleccione el tipo de prueba a reparar</h2>
        
        <div class="grid">
            <div class="card">
                <h3 class="card-title">Inventario de Personalidad Laboral (IPL)</h3>
                <p>Evalúa rasgos de personalidad relevantes en entornos laborales.</p>
                <a href="?tipo=ipl" class="btn btn-primary">Diagnosticar</a>
            </div>
            
            <div class="card">
                <h3 class="card-title">Test de Aptitudes Cognitivas (TAC)</h3>
                <p>Evalúa habilidades cognitivas como razonamiento verbal, numérico, lógico y atención al detalle.</p>
                <a href="?tipo=tac" class="btn btn-primary">Diagnosticar</a>
            </div>
            
            <div class="card">
                <h3 class="card-title">Evaluación de Competencias Fundamentales (ECF)</h3>
                <p>Evalúa competencias básicas para el desempeño laboral.</p>
                <a href="?tipo=ecf" class="btn btn-primary">Diagnosticar</a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($resultados['pruebas'])): ?>
    <div class="container">
        <h2>Todas las pruebas disponibles</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados['pruebas'] as $prueba): ?>
                <tr>
                    <td><?php echo $prueba['id']; ?></td>
                    <td><?php echo htmlspecialchars($prueba['titulo']); ?></td>
                    <td><?php echo htmlspecialchars($prueba['descripcion'] ?? ''); ?></td>
                    <td>
                        <a href="?prueba_id=<?php echo $prueba['id']; ?>" class="btn btn-sm btn-primary">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="container">
        <h2>Diagnóstico: <?php echo htmlspecialchars($resultados['info_prueba']['titulo'] ?? $prueba_tipo); ?></h2>
        
        <?php if (isset($resultados['estructura'])): ?>
        <div class="panel">
            <h3 class="panel-title">Estructura de la prueba</h3>
            
            <div class="alert alert-info">
                <p><strong>Tipo de prueba:</strong> 
                    <?php echo $resultados['estructura']['tiene_pares'] ? 'Prueba de elección forzada (pares)' : 'Prueba estándar'; ?>
                </p>
                <p><strong>Total de preguntas:</strong> <?php echo $resultados['estructura']['total_preguntas']; ?></p>
                <p><strong>Preguntas con dimensión asignada:</strong> <?php echo $resultados['estructura']['dimensiones_en_preguntas']; ?></p>
                <p><strong>Opciones con dimensión asignada:</strong> <?php echo $resultados['estructura']['dimensiones_en_opciones']; ?></p>
            </div>
            
            <?php if ($resultados['estructura']['dimensiones_en_preguntas'] == 0 && !$resultados['estructura']['tiene_pares']): ?>
            <div class="alert alert-danger">
                <p><strong>⚠️ Problema detectado:</strong> Esta prueba no tiene dimensiones asignadas a las preguntas.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($resultados['estructura']['tiene_pares'] && $resultados['estructura']['dimensiones_en_opciones'] == 0): ?>
            <div class="alert alert-danger">
                <p><strong>⚠️ Problema detectado:</strong> Esta prueba de tipo pares no tiene dimensiones asignadas a las opciones.</p>
            </div>
            <?php endif; ?>
            
            <a href="?tipo=<?php echo $prueba_tipo; ?>&accion=reparar" class="btn btn-warning">Reparar estructura</a>
        </div>
        
        <?php if (isset($resultados['reparacion'])): ?>
        <div class="panel">
            <h3 class="panel-title">Resultados de la reparación</h3>
            
            <?php if (isset($resultados['reparacion']['dimensiones_creadas']) && $resultados['reparacion']['dimensiones_creadas'] > 0): ?>
            <div class="alert alert-success">
                <p>Se crearon <?php echo $resultados['reparacion']['dimensiones_creadas']; ?> dimensiones nuevas.</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($resultados['reparacion']['asignaciones'])): ?>
            <div class="alert alert-info">
                <p><strong>Asignaciones realizadas:</strong></p>
                <ul>
                    <?php foreach ($resultados['reparacion']['asignaciones'] as $tipo => $cantidad): ?>
                    <li><?php echo ucfirst(str_replace('_', ' ', $tipo)); ?>: <?php echo $cantidad; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <a href="?tipo=<?php echo $prueba_tipo; ?>" class="btn btn-primary">Actualizar diagnóstico</a>
        </div>
        <?php endif; ?>
        
        <div class="panel">
            <h3 class="panel-title">Regenerar resultados de sesiones</h3>
            
            <form method="get" style="margin-bottom: 20px;">
                <input type="hidden" name="tipo" value="<?php echo $prueba_tipo; ?>">
                <input type="hidden" name="accion" value="regenerar">
                <div style="display: flex; gap: 10px;">
                    <input type="number" name="sesion_id" placeholder="ID de la sesión" required style="padding: 8px; border-radius: 4px; border: 1px solid #ced4da; flex-grow: 1;">
                    <button type="submit" class="btn btn-success">Regenerar resultados</button>
                </div>
            </form>
            
            <?php if (isset($resultados['regeneracion'])): ?>
            <div class="alert alert-success">
                <p><strong>Regeneración completada:</strong></p>
                <p>Dimensiones procesadas: <?php echo $resultados['regeneracion']['dimensiones_procesadas']; ?></p>
                <p>Resultados generados: <?php echo $resultados['regeneracion']['resultados_generados']; ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <h2>Enlaces Útiles</h2>
        
        <div class="panel">
            <a href="resultado-prueba.php?sesion_id=<?php echo $sesion_id; ?>" class="btn btn-primary">Ver Página de Resultados</a>
            <a href="pruebas.php" class="btn btn-primary">Volver a Pruebas</a>
            <a href="index.php" class="btn btn-primary">Volver al Panel</a>
        </div>
    </div>
</body>
</html>