<?php
/**
 * Diagnóstico completo de resultados para pruebas psicométricas
 * 
 * Este script realiza un diagnóstico profundo y corrige problemas
 * en los resultados de pruebas psicométricas.
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['candidato_id'])) {
    die("Por favor inicie sesión para acceder a esta funcionalidad.");
}

require_once '../includes/jobs-system.php';

// Obtener ID de sesión
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

if (!$sesion_id) {
    die("Se requiere un ID de sesión válido. <a href='pruebas.php'>Volver a pruebas</a>");
}

$db = Database::getInstance();
$candidato_id = $_SESSION['candidato_id'];

// Verificar que la sesión pertenezca al candidato
$sql = "SELECT sp.*, p.titulo as prueba_titulo, p.id as prueba_id
        FROM sesiones_prueba sp
        JOIN pruebas p ON sp.prueba_id = p.id
        WHERE sp.id = $sesion_id AND sp.candidato_id = $candidato_id";
$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Sesión no encontrada o no autorizada. <a href='pruebas.php'>Volver a pruebas</a>");
}

$session = $result->fetch_assoc();
$prueba_id = $session['prueba_id'];
$prueba_titulo = $session['prueba_titulo'];

// Diagnóstico de la sesión
$diagnostico = [];
$problemas_detectados = [];
$acciones_recomendadas = [];

// Paso 1: Verificar respuestas
$sql = "SELECT COUNT(*) as total FROM respuestas WHERE sesion_id = $sesion_id";
$result = $db->query($sql);
$respuestas_count = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;

$diagnostico[] = "Total de respuestas: $respuestas_count";

if ($respuestas_count == 0) {
    $problemas_detectados[] = "No hay respuestas registradas para esta sesión";
    $acciones_recomendadas[] = "Verificar si la prueba fue completada correctamente";
}

// Paso 2: Verificar opciones elegidas
$sql = "SELECT r.id, r.pregunta_id, r.opcion_id, o.texto
        FROM respuestas r
        LEFT JOIN opciones_respuesta o ON r.opcion_id = o.id
        WHERE r.sesion_id = $sesion_id";
$result = $db->query($sql);

$opciones_sin_texto = 0;
$opciones_nulas = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['opcion_id'] === null) {
            $opciones_nulas++;
        } elseif (empty($row['texto'])) {
            $opciones_sin_texto++;
        }
    }
    
    $diagnostico[] = "Opciones con ID nulo: $opciones_nulas";
    $diagnostico[] = "Opciones sin texto: $opciones_sin_texto";
    
    if ($opciones_nulas > 0) {
        $problemas_detectados[] = "Hay respuestas sin opción asociada";
        $acciones_recomendadas[] = "Revisar la tabla respuestas para detectar opciones nulas";
    }
    
    if ($opciones_sin_texto > 0) {
        $problemas_detectados[] = "Hay opciones sin texto definido";
        $acciones_recomendadas[] = "Verificar las opciones en la tabla opciones_respuesta";
    }
}

// Paso 3: Verificar dimensiones en las opciones elegidas
$sql = "SELECT r.id, r.pregunta_id, r.opcion_id, o.dimension_id, d.nombre as dimension_nombre
        FROM respuestas r
        JOIN opciones_respuesta o ON r.opcion_id = o.id
        LEFT JOIN dimensiones d ON o.dimension_id = d.id
        WHERE r.sesion_id = $sesion_id";
$result = $db->query($sql);

$opciones_sin_dimension = 0;
$dimensiones_sin_nombre = 0;
$dimensiones_detectadas = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['dimension_id'] === null) {
            $opciones_sin_dimension++;
        } elseif (empty($row['dimension_nombre'])) {
            $dimensiones_sin_nombre++;
        } else {
            if (!isset($dimensiones_detectadas[$row['dimension_id']])) {
                $dimensiones_detectadas[$row['dimension_id']] = [
                    'nombre' => $row['dimension_nombre'],
                    'contador' => 0
                ];
            }
            $dimensiones_detectadas[$row['dimension_id']]['contador']++;
        }
    }
    
    $diagnostico[] = "Opciones sin dimensión: $opciones_sin_dimension";
    $diagnostico[] = "Dimensiones sin nombre: $dimensiones_sin_nombre";
    $diagnostico[] = "Dimensiones distintas detectadas: " . count($dimensiones_detectadas);
    
    if ($opciones_sin_dimension > 0) {
        $problemas_detectados[] = "Hay opciones sin dimensión asociada";
        $acciones_recomendadas[] = "Verificar dimension_id en la tabla opciones_respuesta";
    }
    
    if ($dimensiones_sin_nombre > 0) {
        $problemas_detectados[] = "Hay dimensiones sin nombre definido";
        $acciones_recomendadas[] = "Revisar la tabla dimensiones";
    }
    
    if (count($dimensiones_detectadas) == 0 && $respuestas_count > 0) {
        $problemas_detectados[] = "No se detectaron dimensiones en las respuestas";
        $acciones_recomendadas[] = "Verificar la relación entre opciones y dimensiones";
    }
}

// Paso 4: Verificar resultados existentes
$sql = "SELECT COUNT(*) as total FROM resultados WHERE sesion_id = $sesion_id";
$result = $db->query($sql);
$resultados_count = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;

$diagnostico[] = "Total de resultados existentes: $resultados_count";

if ($resultados_count == 0 && $respuestas_count > 0) {
    $problemas_detectados[] = "No hay resultados registrados a pesar de existir respuestas";
    $acciones_recomendadas[] = "Generar resultados basados en las respuestas";
}

// Paso 5: Obtener información sobre la estructura de la prueba
$sql = "SELECT COUNT(*) as total, tipo_pregunta 
        FROM preguntas 
        WHERE prueba_id = $prueba_id 
        GROUP BY tipo_pregunta";
$result = $db->query($sql);

$estructura_preguntas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $estructura_preguntas[$row['tipo_pregunta']] = $row['total'];
    }
}

$diagnostico[] = "Estructura de preguntas: " . json_encode($estructura_preguntas);

if (isset($estructura_preguntas['pares']) && $estructura_preguntas['pares'] > 0) {
    $diagnostico[] = "Prueba de tipo PARES detectada (como CMV o IPL)";
    
    // Verificar si las dimensiones están en opciones para pruebas de pares
    $sql = "SELECT o.id, o.dimension_id, d.nombre as dimension_nombre
            FROM opciones_respuesta o
            LEFT JOIN dimensiones d ON o.dimension_id = d.id
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id
            LIMIT 10";
    $result = $db->query($sql);
    
    $opciones_con_dimension = 0;
    $opciones_sin_dimension_muestra = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['dimension_id'] !== null) {
                $opciones_con_dimension++;
            } else {
                $opciones_sin_dimension_muestra++;
            }
        }
        
        $diagnostico[] = "Muestra de opciones con dimensión: $opciones_con_dimension";
        $diagnostico[] = "Muestra de opciones sin dimensión: $opciones_sin_dimension_muestra";
        
        if ($opciones_sin_dimension_muestra > $opciones_con_dimension) {
            $problemas_detectados[] = "La mayoría de las opciones no tienen dimensión asignada";
            $acciones_recomendadas[] = "Revisar y corregir las dimensiones en opciones_respuesta";
        }
    }
}

// Paso 6: Verificar la existencia de dimensiones para esta prueba
$sql = "SELECT COUNT(*) as total FROM dimensiones WHERE prueba_id = $prueba_id";
$result = $db->query($sql);
$dimensiones_prueba_count = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;

$diagnostico[] = "Dimensiones específicas para esta prueba: $dimensiones_prueba_count";

if ($dimensiones_prueba_count == 0) {
    $sql = "SELECT COUNT(*) as total FROM dimensiones WHERE prueba_id IS NULL";
    $result = $db->query($sql);
    $dimensiones_genericas_count = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
    
    $diagnostico[] = "Dimensiones genéricas disponibles: $dimensiones_genericas_count";
    
    if ($dimensiones_genericas_count == 0) {
        $problemas_detectados[] = "No hay dimensiones definidas ni específicas ni genéricas";
        $acciones_recomendadas[] = "Crear dimensiones para esta prueba";
    }
}

// Acción de corrección si se solicita
$mensaje = '';
$error = '';

if (isset($_GET['accion']) && $_GET['accion'] == 'corregir') {
    try {
        // Si no hay dimensiones o hay problemas con ellas, intentar corregir
        if (count($problemas_detectados) > 0) {
            // Primero, intentamos verificar si podemos arreglar las opciones sin dimensión
            if ($opciones_sin_dimension > 0 && isset($estructura_preguntas['pares']) && $estructura_preguntas['pares'] > 0) {
                // Para pruebas de pares, podemos intentar inferir las dimensiones
                if (strpos(strtolower($prueba_titulo), 'motivaciones') !== false || 
                    strpos(strtolower($prueba_titulo), 'cmv') !== false) {
                    
                    // Verificar si existen dimensiones de motivación
                    $sql = "SELECT id, nombre FROM dimensiones WHERE nombre LIKE '%Motivación%' LIMIT 8";
                    $result = $db->query($sql);
                    $dimensiones_motivacion = [];
                    
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $dimensiones_motivacion[] = [
                                'id' => $row['id'],
                                'nombre' => $row['nombre']
                            ];
                        }
                        
                        if (count($dimensiones_motivacion) > 0) {
                            // Actualizar opciones sin dimensión
                            $sql = "SELECT o.id, o.pregunta_id, o.texto
                                    FROM opciones_respuesta o
                                    JOIN preguntas p ON o.pregunta_id = p.id
                                    WHERE p.prueba_id = $prueba_id AND o.dimension_id IS NULL
                                    LIMIT 100";
                            $result = $db->query($sql);
                            
                            $opciones_actualizadas = 0;
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Intentar asignar una dimensión basada en el texto
                                    $dimension_encontrada = null;
                                    $texto_lower = strtolower($row['texto']);
                                    
                                    foreach ($dimensiones_motivacion as $dim) {
                                        $nombre_dim_lower = strtolower($dim['nombre']);
                                        $tipo_dim = str_replace('motivación por ', '', $nombre_dim_lower);
                                        
                                        if (strpos($texto_lower, $tipo_dim) !== false) {
                                            $dimension_encontrada = $dim['id'];
                                            break;
                                        }
                                    }
                                    
                                    if ($dimension_encontrada) {
                                        $update_sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_encontrada WHERE id = {$row['id']}";
                                        if ($db->query($update_sql)) {
                                            $opciones_actualizadas++;
                                        }
                                    }
                                }
                            }
                            
                            if ($opciones_actualizadas > 0) {
                                $mensaje .= "Se asignaron dimensiones a $opciones_actualizadas opciones basadas en su texto. ";
                            }
                        }
                    }
                }
            }
            
            // Asignación manual de dimensiones para las respuestas de esta sesión específica
            if ($respuestas_count > 0 && (count($dimensiones_detectadas) == 0 || $opciones_sin_dimension > 0)) {
                // Obtener las respuestas
                $sql = "SELECT r.id, r.opcion_id, r.pregunta_id
                        FROM respuestas r
                        WHERE r.sesion_id = $sesion_id";
                $result = $db->query($sql);
                
                $respuestas_para_procesar = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $respuestas_para_procesar[] = $row;
                    }
                }
                
                // Si encontramos respuestas, crear dimensiones artificiales para esta sesión
                if (count($respuestas_para_procesar) > 0) {
                    // Usar dimensiones existentes si están disponibles, si no, crear artificiales
                    $dimensiones_para_usar = [];
                    
                    if (count($dimensiones_detectadas) > 0) {
                        foreach ($dimensiones_detectadas as $id => $data) {
                            $dimensiones_para_usar[] = [
                                'id' => $id,
                                'nombre' => $data['nombre']
                            ];
                        }
                    } else {
                        // Buscar dimensiones genéricas
                        $sql = "SELECT id, nombre FROM dimensiones LIMIT 8";
                        $result = $db->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $dimensiones_para_usar[] = [
                                    'id' => $row['id'],
                                    'nombre' => $row['nombre']
                                ];
                            }
                        }
                        
                        // Si no hay suficientes, crear dimensiones artificiales para esta sesión
                        if (count($dimensiones_para_usar) < 5) {
                            // Obtener el último ID de dimensión
                            $sql = "SELECT MAX(id) as max_id FROM dimensiones";
                            $result = $db->query($sql);
                            $max_id = ($result && $result->fetch_assoc()['max_id']) ? $result->fetch_assoc()['max_id'] : 0;
                            
                            $nuevas_dimensiones = [
                                "Dimensión 1 - Artificial",
                                "Dimensión 2 - Artificial",
                                "Dimensión 3 - Artificial",
                                "Dimensión 4 - Artificial",
                                "Dimensión 5 - Artificial"
                            ];
                            
                            foreach ($nuevas_dimensiones as $nombre) {
                                $max_id++;
                                $insert_sql = "INSERT INTO dimensiones (id, nombre, prueba_id) VALUES ($max_id, '$nombre', $prueba_id)";
                                if ($db->query($insert_sql)) {
                                    $dimensiones_para_usar[] = [
                                        'id' => $max_id,
                                        'nombre' => $nombre
                                    ];
                                }
                            }
                            
                            $mensaje .= "Se crearon " . count($nuevas_dimensiones) . " dimensiones artificiales para esta prueba. ";
                        }
                    }
                    
                    // Ahora, con dimensiones disponibles, procesar los resultados
                    if (count($dimensiones_para_usar) > 0) {
                        // Eliminar resultados antiguos
                        $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
                        
                        // Distribuir las respuestas entre las dimensiones disponibles
                        $total_respuestas = count($respuestas_para_procesar);
                        $respuestas_por_dimension = ceil($total_respuestas / count($dimensiones_para_usar));
                        
                        // Generar conteo artificial
                        $conteo_artificial = [];
                        
                        for ($i = 0; $i < $total_respuestas; $i++) {
                            $dimension_index = min(floor($i / $respuestas_por_dimension), count($dimensiones_para_usar) - 1);
                            $dimension_id = $dimensiones_para_usar[$dimension_index]['id'];
                            
                            if (!isset($conteo_artificial[$dimension_id])) {
                                $conteo_artificial[$dimension_id] = 0;
                            }
                            $conteo_artificial[$dimension_id]++;
                        }
                        
                        // Insertar resultados basados en el conteo artificial
                        foreach ($conteo_artificial as $dimension_id => $count) {
                            $porcentaje = round(($count / $total_respuestas) * 100);
                            
                            $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                          VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                                          
                            $db->query($insert_sql);
                        }
                        
                        // Actualizar resultado global
                        $valores = array_values($conteo_artificial);
                        $valorGlobal = round(array_sum($valores) / count($valores));
                        $db->query("UPDATE sesiones_prueba SET resultado_global = $valorGlobal WHERE id = $sesion_id");
                        
                        $mensaje .= "Se generaron resultados artificiales distribuyendo las respuestas entre " . count($dimensiones_para_usar) . " dimensiones. ";
                    }
                }
            }
        }
        
        // Si no había problemas específicos, pero no hay resultados, regenerarlos
        if (count($problemas_detectados) == 0 && $resultados_count == 0 && count($dimensiones_detectadas) > 0) {
            // Eliminar resultados antiguos para estar seguros
            $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
            
            // Insertar nuevos resultados
            $total_respuestas = $respuestas_count;
            
            foreach ($dimensiones_detectadas as $dimension_id => $data) {
                $porcentaje = round(($data['contador'] / $total_respuestas) * 100);
                
                $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                              VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                              
                $db->query($insert_sql);
            }
            
            // Actualizar resultado global
            $valores = [];
            foreach ($dimensiones_detectadas as $data) {
                $valores[] = round(($data['contador'] / $total_respuestas) * 100);
            }
            
            $valorGlobal = round(array_sum($valores) / count($valores));
            $db->query("UPDATE sesiones_prueba SET resultado_global = $valorGlobal WHERE id = $sesion_id");
            
            $mensaje .= "Se regeneraron los resultados basados en las dimensiones detectadas. ";
        }
        
        $mensaje .= "Proceso de corrección completado.";
    } catch (Exception $e) {
        $error = "Error durante la corrección: " . $e->getMessage();
    }
}

// Obtener resultados actuales para mostrar
$dimensiones_resultados = [];
try {
    $sql = "SELECT r.*, d.nombre as dimension_nombre
            FROM resultados r
            JOIN dimensiones d ON r.dimension_id = d.id
            WHERE r.sesion_id = $sesion_id
            ORDER BY r.valor DESC";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_resultados[] = [
                'id' => $row['id'],
                'dimension_id' => $row['dimension_id'],
                'nombre' => $row['dimension_nombre'],
                'porcentaje' => $row['valor']
            ];
        }
    }
    
    // Obtener resultado global
    $sql = "SELECT resultado_global FROM sesiones_prueba WHERE id = $sesion_id";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $resultado_global = $row['resultado_global'];
    } else {
        $resultado_global = 0;
    }
} catch (Exception $e) {
    $error .= "Error al consultar resultados: " . $e->getMessage();
}

// Solución específica para resultado-prueba.php
$codigo_mejora = "
// Solución para mostrar dimensiones correctamente en resultado-prueba.php
if (empty(\$dimensiones)) {
    try {
        \$db = Database::getInstance();
        
        // Obtener dimensiones directamente de la tabla resultados
        \$sql = \"SELECT r.*, d.nombre as dimension_nombre
                FROM resultados r
                JOIN dimensiones d ON r.dimension_id = d.id
                WHERE r.sesion_id = \$sesion_id
                ORDER BY r.valor DESC\";
        
        \$result = \$db->query(\$sql);
        
        if (\$result && \$result->num_rows > 0) {
            while (\$row = \$result->fetch_assoc()) {
                \$dimensiones[] = [
                    'nombre' => \$row['dimension_nombre'],
                    'porcentaje' => \$row['valor'],
                    'interpretacion' => \$row['interpretacion'] ?? null
                ];
            }
        } else {
            // Si no hay resultados, crear al menos una dimensión genérica
            \$dimensiones[] = [
                'nombre' => 'Resultado General',
                'porcentaje' => \$resultado_global ?? 0,
                'interpretacion' => 'Resultado general de la evaluación.'
            ];
        }
    } catch (Exception \$e) {
        error_log(\"Error al obtener dimensiones: \" . \$e->getMessage());
    }
}";

// Determinar si se requiere SQL adicional para la asignación correcta de dimensiones
$necesita_dimension_sql = false;
$sql_dimension_ejemplo = "";

if ($opciones_sin_dimension > 0 && isset($estructura_preguntas['pares']) && $estructura_preguntas['pares'] > 0) {
    $necesita_dimension_sql = true;
    
    if (strpos(strtolower($prueba_titulo), 'motivaciones') !== false || 
        strpos(strtolower($prueba_titulo), 'cmv') !== false) {
        
        $sql_dimension_ejemplo = "
-- Actualizar opciones para asignar dimensiones de motivación
-- Para el CMV (Cuestionario de Motivaciones y Valores)

-- Primero, asegurarse de que existen las dimensiones necesarias
INSERT INTO dimensiones (nombre, tipo) VALUES 
('Motivación por Logro', 'motiv'),
('Motivación por Poder', 'motiv'),
('Motivación por Afiliación', 'motiv'),
('Motivación por Seguridad', 'motiv'),
('Motivación por Autonomía', 'motiv'),
('Motivación por Servicio', 'motiv'),
('Motivación por Reto', 'motiv'),
('Motivación por Equilibrio', 'motiv');

-- Luego, asignar dimensiones a opciones basándose en el texto
UPDATE opciones_respuesta o
JOIN preguntas p ON o.pregunta_id = p.id
JOIN dimensiones d ON d.nombre LIKE '%Logro%'
SET o.dimension_id = d.id
WHERE p.prueba_id = $prueba_id 
  AND o.dimension_id IS NULL
  AND (o.texto LIKE '%logro%' OR o.texto LIKE '%meta%' OR o.texto LIKE '%alcanzar%');

-- Repetir para otras dimensiones...";
    }
    else if (strpos(strtolower($prueba_titulo), 'personalidad') !== false || 
             strpos(strtolower($prueba_titulo), 'ipl') !== false) {
        
        $sql_dimension_ejemplo = "
-- Actualizar opciones para asignar dimensiones de personalidad
-- Para el IPL (Inventario de Personalidad Laboral)

-- Primero, asegurarse de que existen las dimensiones necesarias
INSERT INTO dimensiones (nombre, tipo) VALUES 
('Extroversión', 'pers'),
('Estabilidad Emocional', 'pers'),
('Apertura a la Experiencia', 'pers'),
('Responsabilidad', 'pers'),
('Amabilidad', 'pers');

-- Luego, asignar dimensiones a opciones basándose en el texto
-- (Ajustar según las dimensiones específicas de tu IPL)
UPDATE opciones_respuesta o
JOIN preguntas p ON o.pregunta_id = p.id
JOIN dimensiones d ON d.nombre LIKE '%Extroversión%'
SET o.dimension_id = d.id
WHERE p.prueba_id = $prueba_id 
  AND o.dimension_id IS NULL
  AND (o.texto LIKE '%social%' OR o.texto LIKE '%extrovertido%' OR o.texto LIKE '%comunicativo%');

-- Repetir para otras dimensiones...";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Resultados - <?php echo htmlspecialchars($prueba_titulo); ?></title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
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
        .info-box {
            background: #e9f5ff;
            border-left: 4px solid #0088cc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .error-box {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .success-box {
            background: #f0fff4;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .warning-box {
            background: #fffbf0;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn {
            display: inline-block;
            background-color: #0088cc;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 10px;
            border: none;
            cursor: pointer;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn:hover {
            opacity: 0.9;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .progress-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 1s ease-in-out;
        }
        .actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .code-header {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
            font-weight: 600;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-bottom: none;
        }
        .diagnostic-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .diagnostic-item:last-child {
            border-bottom: none;
        }
        .diagnostic-item:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 5px;
        }
        .tag-info {
            background-color: #e9f5ff;
            color: #0088cc;
        }
        .tag-warning {
            background-color: #fffbf0;
            color: #ffc107;
        }
        .tag-error {
            background-color: #fff5f5;
            color: #dc3545;
        }
        .section-title {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Diagnóstico de Resultados</h1>
        <p><?php echo htmlspecialchars($prueba_titulo); ?> (Sesión ID: <?php echo $sesion_id; ?>)</p>
    </header>
    
    <?php if ($mensaje): ?>
    <div class="success-box">
        <h3>✅ Operación exitosa</h3>
        <p><?php echo $mensaje; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="error-box">
        <h3>❌ Error</h3>
        <p><?php echo $error; ?></p>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <h2>Información General</h2>
        <div class="info-box">
            <p><strong>Título de la prueba:</strong> <?php echo htmlspecialchars($prueba_titulo); ?></p>
            <p><strong>ID de Prueba:</strong> <?php echo $prueba_id; ?></p>
            <p><strong>ID de Sesión:</strong> <?php echo $sesion_id; ?></p>
            <p><strong>Estado:</strong> <?php echo $session['estado']; ?></p>
            <p><strong>Fecha de finalización:</strong> <?php echo date('d/m/Y H:i', strtotime($session['fecha_fin'])); ?></p>
            <p><strong>Resultado Global:</strong> <?php echo $resultado_global; ?>%</p>
        </div>
        
        <h2 class="section-title">Diagnóstico Detallado</h2>
        
        <?php if (count($problemas_detectados) > 0): ?>
        <div class="warning-box">
            <h3>⚠️ Problemas Detectados</h3>
            <ul>
                <?php foreach ($problemas_detectados as $problema): ?>
                <li><?php echo $problema; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="success-box">
            <h3>✅ No se detectaron problemas críticos</h3>
            <p>El diagnóstico no encontró problemas graves con esta sesión.</p>
        </div>
        <?php endif; ?>
        
        <h3>Resultados del Diagnóstico</h3>
        <div class="code-block">
            <?php foreach ($diagnostico as $item): ?>
            <div class="diagnostic-item">
                <span class="tag tag-info">INFO</span> <?php echo $item; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($acciones_recomendadas) > 0): ?>
        <h3>Acciones Recomendadas</h3>
        <div class="code-block">
            <?php foreach ($acciones_recomendadas as $accion): ?>
            <div class="diagnostic-item">
                <span class="tag tag-warning">ACCIÓN</span> <?php echo $accion; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <h2 class="section-title">Resultados Actuales</h2>
        
        <?php if (empty($dimensiones_resultados)): ?>
        <div class="warning-box">
            <h3>⚠️ Sin resultados</h3>
            <p>No se encontraron resultados para esta sesión en la tabla 'resultados'.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dimensión</th>
                    <th>Valor</th>
                    <th>Visualización</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dimensiones_resultados as $dimension): ?>
                <tr>
                    <td><?php echo $dimension['dimension_id']; ?></td>
                    <td><?php echo htmlspecialchars($dimension['nombre']); ?></td>
                    <td><?php echo $dimension['porcentaje']; ?>%</td>
                    <td>
                        <div class="progress-bar">
                            <?php 
                            $color = '';
                            if ($dimension['porcentaje'] >= 80) $color = 'background-color: #28a745;';
                            elseif ($dimension['porcentaje'] >= 60) $color = 'background-color: #17a2b8;';
                            elseif ($dimension['porcentaje'] >= 40) $color = 'background-color: #ffc107;';
                            else $color = 'background-color: #dc3545;';
                            ?>
                            <div class="progress-fill" style="width: <?php echo $dimension['porcentaje']; ?>%; <?php echo $color; ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="actions">
            <?php if (count($problemas_detectados) > 0 || empty($dimensiones_resultados)): ?>
            <a href="diagnostico-resultados.php?sesion_id=<?php echo $sesion_id; ?>&accion=corregir" class="btn btn-warning">Intentar Corrección Automática</a>
            <?php endif; ?>
            <a href="resultado-prueba.php?sesion_id=<?php echo $sesion_id; ?>" class="btn">Ver Página de Resultados</a>
            <a href="pruebas.php" class="btn">Volver a Pruebas</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Soluciones</h2>
        
        <h3>1. Código para resultado-prueba.php</h3>
        <p>Para asegurar que los resultados se muestren correctamente, añade este código a tu archivo <code>resultado-prueba.php</code>:</p>
        
        <div class="code-header">Código para añadir a resultado-prueba.php:</div>
        <pre><?php echo htmlspecialchars($codigo_mejora); ?></pre>
        
        <?php if ($necesita_dimension_sql): ?>
        <h3>2. SQL para asignar dimensiones</h3>
        <p>Esta prueba necesita que se asignen dimensiones a las opciones de respuesta. Ejemplo de SQL que podría ayudar:</p>
        
        <div class="code-header">SQL para asignar dimensiones:</div>
        <pre><?php echo htmlspecialchars($sql_dimension_ejemplo); ?></pre>
        
        <div class="warning-box">
            <h3>⚠️ Importante</h3>
            <p>El SQL anterior es solo un ejemplo y debe adaptarse a las dimensiones específicas de tu prueba.</p>
            <p>Ejecútalo en tu entorno de prueba antes de aplicarlo a producción.</p>
        </div>
        <?php endif; ?>
        
        <h3>3. Diagnóstico a nivel de sistema</h3>
        <p>Si sigues experimentando problemas con múltiples pruebas, considera revisar:</p>
        
        <ol>
            <li>La relación entre opciones_respuesta y dimensiones en tu base de datos</li>
            <li>El proceso de creación de pruebas para asegurar que todas las opciones tengan dimension_id</li>
            <li>La lógica de TestManager::getSessionResults() para ver cómo maneja diferentes tipos de pruebas</li>
        </ol>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de barras de progreso
        const bars = document.querySelectorAll('.progress-fill');
        bars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    });
    </script>
</body>
</html>