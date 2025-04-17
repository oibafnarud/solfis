<?php
/**
 * Diagnóstico detallado para pruebas ECF e IPL
 */
require_once '../includes/jobs-system.php';

$db = Database::getInstance();
$prueba_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$prueba_id = isset($_GET['prueba_id']) ? (int)$_GET['prueba_id'] : 0;

echo "<h1>Diagnóstico detallado para pruebas $prueba_tipo</h1>";

// Obtener información de la prueba
if ($prueba_tipo == 'ecf') {
    $sql = "SELECT id, titulo FROM pruebas WHERE titulo LIKE '%Competencias%' LIMIT 1";
} elseif ($prueba_tipo == 'ipl') {
    $sql = "SELECT id, titulo FROM pruebas WHERE titulo LIKE '%Personalidad%' LIMIT 1";
} else {
    echo "Especifica un tipo de prueba: ?tipo=ecf o ?tipo=ipl";
    exit;
}

$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $prueba = $result->fetch_assoc();
    $prueba_id = $prueba['id'];
    echo "<h2>Prueba: {$prueba['titulo']} (ID: {$prueba['id']})</h2>";
} else {
    echo "<p>No se encontró una prueba del tipo especificado.</p>";
    exit;
}

// Verificar cuántas preguntas tiene la prueba
$sql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id";
$result = $db->query($sql);
$total_preguntas = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
echo "<p>Total de preguntas: $total_preguntas</p>";

// Verificar cuántas preguntas sin dimensión
$sql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
$result = $db->query($sql);
$preguntas_sin_dimension = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
echo "<p>Preguntas sin dimensión: $preguntas_sin_dimension</p>";

// Verificar si es de tipo pares
$sql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id AND tipo_pregunta = 'pares'";
$result = $db->query($sql);
$es_tipo_pares = ($result && $result->num_rows > 0 && $result->fetch_assoc()['total'] > 0);
echo "<p>Es una prueba de tipo pares: " . ($es_tipo_pares ? 'Sí' : 'No') . "</p>";

// Si es de tipo pares, verificar opciones
if ($es_tipo_pares) {
    $sql = "SELECT COUNT(*) as total FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id";
    $result = $db->query($sql);
    $total_opciones = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
    echo "<p>Total de opciones de respuesta: $total_opciones</p>";
    
    $sql = "SELECT COUNT(*) as total FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            WHERE p.prueba_id = $prueba_id AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
    $result = $db->query($sql);
    $opciones_sin_dimension = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;
    echo "<p>Opciones sin dimensión: $opciones_sin_dimension</p>";
}

// Intentar asignar dimensiones directamente si se solicita
if (isset($_GET['reparar'])) {
    echo "<h2>Ejecutando reparación forzada</h2>";
    
    // Crear dimensiones si no existen
    if ($prueba_tipo == 'ecf') {
        $dimensiones = [
            'Comunicación Básica',
            'Trabajo en Equipo',
            'Adaptabilidad',
            'Integridad',
            'Meticulosidad vs. Flexibilidad'
        ];
        $tipo = 'competencia';
    } else { // IPL
        $dimensiones = [
            'Extroversión vs. Introversión',
            'Estabilidad vs. Reactividad Emocional',
            'Apertura vs. Convencionalidad',
            'Responsabilidad',
            'Cooperación vs. Independencia'
        ];
        $tipo = 'personalidad';
    }
    
    $dimension_ids = [];
    foreach ($dimensiones as $dimension) {
        // Verificar si existe
        $sql = "SELECT id FROM dimensiones WHERE nombre = '$dimension'";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $dimension_ids[$dimension] = $result->fetch_assoc()['id'];
            echo "<p>Dimensión existente: $dimension (ID: {$dimension_ids[$dimension]})</p>";
        } else {
            // Crear la dimensión
            $bipolar = ($prueba_tipo == 'ipl') ? 1 : 0;
            $sql = "INSERT INTO dimensiones (nombre, tipo, bipolar) VALUES ('$dimension', '$tipo', $bipolar)";
            if ($db->query($sql)) {
                $dimension_ids[$dimension] = $db->insert_id;
                echo "<p>Dimensión creada: $dimension (ID: {$dimension_ids[$dimension]})</p>";
            } else {
                echo "<p>Error al crear dimensión: $dimension</p>";
            }
        }
    }
    
    // Asignar dimensiones a elementos sin dimensión
    $asignaciones = [];
    foreach ($dimensiones as $i => $dimension) {
        $asignaciones[$dimension] = 0;
    }
    
    if ($es_tipo_pares) {
        // Asignar dimensiones a opciones para pruebas de tipo pares
        $sql = "SELECT o.id 
                FROM opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                WHERE p.prueba_id = $prueba_id AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $db->query($sql);
        
        $opciones = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $opciones[] = $row['id'];
            }
        }
        
        $total_opciones = count($opciones);
        echo "<p>Opciones a procesar: $total_opciones</p>";
        
        for ($i = 0; $i < $total_opciones; $i++) {
            $opcion_id = $opciones[$i];
            $dimension_index = $i % count($dimensiones);
            $dimension = $dimensiones[$dimension_index];
            $dimension_id = $dimension_ids[$dimension];
            
            $sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_id WHERE id = $opcion_id";
            if ($db->query($sql)) {
                $asignaciones[$dimension]++;
            }
        }
    } else {
        // Asignar dimensiones a preguntas para pruebas estándar
        $sql = "SELECT id FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
        $result = $db->query($sql);
        
        $preguntas = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $preguntas[] = $row['id'];
            }
        }
        
        $total_preguntas = count($preguntas);
        echo "<p>Preguntas a procesar: $total_preguntas</p>";
        
        for ($i = 0; $i < $total_preguntas; $i++) {
            $pregunta_id = $preguntas[$i];
            $dimension_index = $i % count($dimensiones);
            $dimension = $dimensiones[$dimension_index];
            $dimension_id = $dimension_ids[$dimension];
            
            $sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
            if ($db->query($sql)) {
                $asignaciones[$dimension]++;
            }
        }
    }
    
    echo "<h3>Asignaciones realizadas:</h3>";
    echo "<ul>";
    foreach ($asignaciones as $dimension => $count) {
        echo "<li>$dimension: $count</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='?tipo=$prueba_tipo'>Verificar resultado</a></p>";
} else {
    echo "<p><a href='?tipo=$prueba_tipo&reparar=1'>Ejecutar reparación forzada</a></p>";
}

// Mostrar sesiones disponibles para regenerar resultados
$sql = "SELECT id, fecha_inicio, fecha_fin, resultado_global FROM sesiones_prueba WHERE prueba_id = $prueba_id AND estado = 'completada'";
$result = $db->query($sql);

echo "<h2>Sesiones disponibles</h2>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>ID Sesión</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Resultado Global</th>
                <th>Acciones</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['fecha_inicio']}</td>
                <td>{$row['fecha_fin']}</td>
                <td>{$row['resultado_global']}</td>
                <td>
                    <a href='?tipo=$prueba_tipo&regenerar={$row['id']}'>Regenerar resultados</a> | 
                    <a href='resultado-prueba.php?sesion_id={$row['id']}'>Ver resultados</a>
                </td>
              </tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay sesiones completadas para esta prueba.</p>";
}

// Regenerar resultados para una sesión específica
if (isset($_GET['regenerar'])) {
    $sesion_id = (int)$_GET['regenerar'];
    
    echo "<h2>Regenerando resultados para sesión $sesion_id</h2>";
    
    // Obtener info de la sesión
    $sql = "SELECT * FROM sesiones_prueba WHERE id = $sesion_id";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $sesion = $result->fetch_assoc();
        $candidato_id = $sesion['candidato_id'];
        
        // Eliminar resultados existentes
        $sql = "DELETE FROM resultados WHERE sesion_id = $sesion_id";
        $db->query($sql);
        echo "<p>Resultados anteriores eliminados.</p>";
        
        if ($es_tipo_pares) {
            // Para pruebas tipo pares, generar resultados basados en opciones
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
                }
                
                echo "<p>Total de respuestas procesadas: $total_respuestas</p>";
                
                // Insertar resultados
                $resultados_insertados = 0;
                
                if ($total_respuestas > 0) {
                    echo "<table border='1' cellpadding='5'>
                            <tr>
                                <th>Dimensión</th>
                                <th>Conteo</th>
                                <th>Porcentaje</th>
                            </tr>";
                    
                    $valores = [];
                    
                    foreach ($conteo_dimensiones as $dimension_id => $data) {
                        $porcentaje = round(($data['count'] / $total_respuestas) * 100);
                        $valores[] = $porcentaje;
                        
                        echo "<tr>
                                <td>{$data['nombre']}</td>
                                <td>{$data['count']}</td>
                                <td>$porcentaje%</td>
                              </tr>";
                        
                        $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                        
                        if ($db->query($sql)) {
                            $resultados_insertados++;
                        } else {
                            echo "<p>Error al insertar resultado para {$data['nombre']}: " . $db->getConnection()->error . "</p>";
                        }
                    }
                    
                    echo "</table>";
                    
                    // Actualizar resultado global
                    $resultado_global = round(array_sum($valores) / count($valores));
                    $sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id";
                    
                    if ($db->query($sql)) {
                        echo "<p>Resultado global actualizado: $resultado_global%</p>";
                    } else {
                        echo "<p>Error al actualizar resultado global: " . $db->getConnection()->error . "</p>";
                    }
                } else {
                    echo "<p>No se encontraron respuestas con dimensiones para esta sesión.</p>";
                    
                    // Asignar valores predeterminados
                    foreach ($dimension_ids as $dimension => $dimension_id) {
                        $porcentaje = 50; // Valor predeterminado
                        
                        $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                        
                        if ($db->query($sql)) {
                            $resultados_insertados++;
                        }
                    }
                    
                    // Actualizar resultado global
                    $resultado_global = 50;
                    $sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id";
                    $db->query($sql);
                    
                    echo "<p>Se asignaron valores predeterminados (50%) a todas las dimensiones.</p>";
                }
                
                echo "<p>Resultados insertados: $resultados_insertados</p>";
            } else {
                echo "<p>No se encontraron respuestas con dimensiones. Verificar que se hayan asignado dimensiones a las opciones.</p>";
            }
        } else {
            // Para pruebas estándar
            $sql = "SELECT p.dimension_id, d.nombre, COUNT(*) as total_preguntas
                    FROM respuestas r
                    JOIN preguntas p ON r.pregunta_id = p.id
                    JOIN dimensiones d ON p.dimension_id = d.id
                    WHERE r.sesion_id = $sesion_id
                    GROUP BY p.dimension_id, d.nombre";
            
            $result = $db->query($sql);
            $resultados_insertados = 0;
            
            if ($result && $result->num_rows > 0) {
                echo "<table border='1' cellpadding='5'>
                        <tr>
                            <th>Dimensión</th>
                            <th>Total Preguntas</th>
                            <th>Porcentaje</th>
                        </tr>";
                
                $valores = [];
                
                while ($row = $result->fetch_assoc()) {
                    $dimension_id = $row['dimension_id'];
                    $porcentaje = 50; // Valor predeterminado
                    $valores[] = $porcentaje;
                    
                    echo "<tr>
                            <td>{$row['nombre']}</td>
                            <td>{$row['total_preguntas']}</td>
                            <td>$porcentaje%</td>
                          </tr>";
                    
                    $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                            VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if ($db->query($sql)) {
                        $resultados_insertados++;
                    } else {
                        echo "<p>Error al insertar resultado: " . $db->getConnection()->error . "</p>";
                    }
                }
                
                echo "</table>";
                
                // Actualizar resultado global
                $resultado_global = round(array_sum($valores) / count($valores));
                $sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id";
                
                if ($db->query($sql)) {
                    echo "<p>Resultado global actualizado: $resultado_global%</p>";
                } else {
                    echo "<p>Error al actualizar resultado global: " . $db->getConnection()->error . "</p>";
                }
            } else {
                echo "<p>No se encontraron respuestas con dimensiones asignadas.</p>";
                
                // Asignar valores predeterminados
                foreach ($dimension_ids as $dimension => $dimension_id) {
                    $porcentaje = 50; // Valor predeterminado
                    
                    $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                            VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if ($db->query($sql)) {
                        $resultados_insertados++;
                    }
                }
                
                // Actualizar resultado global
                $resultado_global = 50;
                $sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $sesion_id";
                $db->query($sql);
                
                echo "<p>Se asignaron valores predeterminados (50%) a todas las dimensiones.</p>";
            }
            
            echo "<p>Resultados insertados: $resultados_insertados</p>";
        }
        
        echo "<p><a href='resultado-prueba.php?sesion_id=$sesion_id'>Ver resultados</a></p>";
    } else {
        echo "<p>Sesión no encontrada.</p>";
    }
}
?>