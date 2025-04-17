<?php
/**
 * Herramienta para procesar y visualizar resultados de pruebas psicométricas
 * 
 * Este script se puede usar para procesar y mostrar correctamente los resultados
 * de diferentes tipos de pruebas psicométricas, incluyendo:
 * - Cuestionario de Motivaciones y Valores (CMV)
 * - Inventario de Personalidad Laboral (IPL)
 * - Otras pruebas que presenten problemas de visualización
 * 
 * Uso: process-test-results.php?sesion_id=X&test_type=Y
 * donde X es el ID de sesión y Y puede ser "cmv", "ipl", etc.
 */

session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    die("Acceso no autorizado. Por favor inicie sesión.");
}

require_once '../includes/jobs-system.php';

// Obtener parámetros
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;
$test_type = isset($_GET['test_type']) ? strtolower($_GET['test_type']) : '';

if (!$sesion_id) {
    die("Se requiere un ID de sesión válido");
}

$db = Database::getInstance();
$candidato_id = $_SESSION['candidato_id'];

// Verificar que la sesión pertenezca al candidato
$sql = "SELECT sp.*, p.titulo as prueba_titulo
        FROM sesiones_prueba sp
        JOIN pruebas p ON sp.prueba_id = p.id
        WHERE sp.id = $sesion_id AND sp.candidato_id = $candidato_id";
$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Sesión no encontrada o no autorizada");
}

$session = $result->fetch_assoc();
$prueba_id = $session['prueba_id'];
$prueba_titulo = $session['prueba_titulo'];

// Determinar automáticamente el tipo de prueba si no se especificó
if (empty($test_type)) {
    $test_type = detectTestType($prueba_titulo);
}

// Función para detectar el tipo de prueba basado en el título
function detectTestType($title) {
    $title = strtolower($title);
    
    if (strpos($title, 'motivaciones') !== false || strpos($title, 'cmv') !== false) {
        return 'cmv';
    } elseif (strpos($title, 'personalidad') !== false || strpos($title, 'ipl') !== false) {
        return 'ipl';
    } else {
        return 'generic';
    }
}

// Iniciar la salida HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesamiento de Resultados: <?php echo htmlspecialchars($prueba_titulo); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            color: #333;
        }
        h1, h2, h3 {
            color: #0088cc;
        }
        .panel {
            background: #f5f7fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0088cc;
        }
        .success {
            background: #f8fff8;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #fff8f8;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fffbf0;
            border-left: 4px solid #ffc107;
        }
        pre {
            background: #f1f1f1;
            padding: 10px;
            overflow-x: auto;
            border-radius: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .dimension-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        .dimension-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease-in-out;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #0088cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .btn:hover {
            background: #006699;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
    </style>
</head>
<body>
    <h1>Procesamiento de Resultados: <?php echo htmlspecialchars($prueba_titulo); ?></h1>
    <div class="panel">
        <p><strong>Sesión ID:</strong> <?php echo $sesion_id; ?></p>
        <p><strong>Tipo de prueba:</strong> <?php echo strtoupper($test_type); ?></p>
        <p><strong>Fecha de finalización:</strong> <?php echo date('d/m/Y H:i', strtotime($session['fecha_fin'])); ?></p>
    </div>

<?php
// Paso 1: Verificar dimensiones
echo "<h2>PASO 1: Verificando dimensiones para " . strtoupper($test_type) . "</h2>";

// La consulta SQL depende del tipo de prueba
switch ($test_type) {
    case 'cmv':
        $dimension_query = "SELECT id, nombre FROM dimensiones WHERE nombre LIKE '%Motivación%' OR tipo = 'motiv'";
        break;
    case 'ipl':
        $dimension_query = "SELECT id, nombre FROM dimensiones WHERE nombre LIKE '%Personalidad%' OR tipo = 'pers'";
        break;
    default:
        $dimension_query = "SELECT id, nombre FROM dimensiones WHERE prueba_id = $prueba_id OR prueba_id IS NULL";
        break;
}

$result = $db->query($dimension_query);
$dimensiones = [];
$dimensionIds = [];

if ($result && $result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID Dimensión</th>
                <th>Nombre</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        $dimensiones[$row['id']] = $row['nombre'];
        $dimensionIds[] = $row['id'];
        
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['nombre']}</td>
              </tr>";
    }
    
    echo "</table>";
    echo "<div class='panel success'><p>✓ Se encontraron " . count($dimensiones) . " dimensiones.</p></div>";
} else {
    echo "<div class='panel warning'><p>⚠️ No se encontraron dimensiones específicas. Probando con consulta genérica:</p></div>";
    
    // Intentar con una consulta más genérica
    $dimension_query = "SELECT id, nombre, tipo FROM dimensiones LIMIT 20";
    $result = $db->query($dimension_query);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>ID Dimensión</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            $dimensiones[$row['id']] = $row['nombre'];
            $dimensionIds[] = $row['id'];
            
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['tipo']}</td>
                  </tr>";
        }
        
        echo "</table>";
        echo "<div class='panel warning'><p>⚠️ Se encontraron " . count($dimensiones) . " dimensiones genéricas.</p></div>";
    } else {
        echo "<div class='panel error'><p>❌ No se encontraron dimensiones en la base de datos.</p></div>";
    }
}

// Paso 2: Verificar respuestas del candidato
echo "<h2>PASO 2: Verificando respuestas del candidato</h2>";

$sql = "SELECT r.id, r.pregunta_id, r.opcion_id, o.texto as opcion_texto, 
        o.dimension_id, d.nombre as dimension_nombre, p.par_id
        FROM respuestas r
        JOIN opciones_respuesta o ON r.opcion_id = o.id
        JOIN preguntas p ON r.pregunta_id = p.id
        LEFT JOIN dimensiones d ON o.dimension_id = d.id
        WHERE r.sesion_id = $sesion_id
        ORDER BY r.id";
$result = $db->query($sql);

$conteo = [];
$total = 0;

if ($result && $result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID Respuesta</th>
                <th>Par ID</th>
                <th>Opción</th>
                <th>Dimensión ID</th>
                <th>Dimensión</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['par_id']}</td>
                <td>" . substr($row['opcion_texto'], 0, 50) . "...</td>
                <td>{$row['dimension_id']}</td>
                <td>{$row['dimension_nombre']}</td>
              </tr>";
        
        // Contar selecciones por dimensión
        if (!empty($row['dimension_id'])) {
            $dimensionId = $row['dimension_id'];
            if (!isset($conteo[$dimensionId])) {
                $conteo[$dimensionId] = 0;
            }
            $conteo[$dimensionId]++;
            $total++;
        }
    }
    
    echo "</table>";
    echo "<div class='panel success'><p>✓ Se encontraron $total respuestas del candidato.</p></div>";
} else {
    echo "<div class='panel error'><p>❌ No se encontraron respuestas para esta sesión.</p></div>";
}

// Paso 3: Crear/actualizar resultados
echo "<h2>PASO 3: Procesando resultados</h2>";

if ($total > 0) {
    // Mostrar conteo por dimensión
    echo "<h3>Conteo por dimensión:</h3>";
    echo "<table>
            <tr>
                <th>Dimensión</th>
                <th>Conteo</th>
                <th>Porcentaje</th>
                <th>Visualización</th>
            </tr>";
    
    foreach ($dimensiones as $dimId => $dimNombre) {
        $count = isset($conteo[$dimId]) ? $conteo[$dimId] : 0;
        $porcentaje = ($total > 0) ? round(($count / $total) * 100) : 0;
        
        $barClass = $porcentaje >= 80 ? 'background-color: #28a745;' : ($porcentaje >= 60 ? 'background-color: #17a2b8;' : 'background-color: #ffc107;');
        
        echo "<tr>
                <td>$dimNombre</td>
                <td>$count</td>
                <td>$porcentaje%</td>
                <td>
                    <div class='dimension-bar'>
                        <div class='dimension-fill' style='width: $porcentaje%; $barClass'></div>
                    </div>
                </td>
              </tr>";
    }
    
    echo "</table>";
    
    // Borrar resultados anteriores para esta sesión
    $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
    echo "<div class='panel success'><p>✓ Resultados anteriores eliminados.</p></div>";
    
    // Insertar nuevos resultados
    $resultados_insertados = 0;
    
    foreach ($dimensiones as $dimId => $dimNombre) {
        $count = isset($conteo[$dimId]) ? $conteo[$dimId] : 0;
        $valor = ($total > 0) ? round(($count / $total) * 100) : 0;
        $percentil = $valor; // Por simplicidad, usamos el mismo valor como percentil
        
        $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                VALUES ($sesion_id, $dimId, $valor, $percentil, $candidato_id)";
        
        if ($db->query($sql)) {
            $resultados_insertados++;
        } else {
            echo "<div class='panel error'><p>❌ Error al guardar resultado para dimensión $dimNombre: " . $db->getConnection()->error . "</p></div>";
        }
    }
    
    echo "<div class='panel success'><p>✓ $resultados_insertados resultados insertados correctamente.</p></div>";
    
    // Calcular y actualizar resultado global
    $valores = [];
    foreach ($dimensiones as $dimId => $dimNombre) {
        if (isset($conteo[$dimId])) {
            $valores[] = round(($conteo[$dimId] / $total) * 100);
        }
    }
    
    if (!empty($valores)) {
        $valorGlobal = round(array_sum($valores) / count($valores));
        
        $updateSql = "UPDATE sesiones_prueba SET resultado_global = $valorGlobal WHERE id = $sesion_id";
        
        if ($db->query($updateSql)) {
            echo "<div class='panel success'><p>✓ Resultado global actualizado: $valorGlobal%</p></div>";
        } else {
            echo "<div class='panel error'><p>❌ Error al actualizar resultado global: " . $db->getConnection()->error . "</p></div>";
        }
    }
} else {
    echo "<div class='panel error'><p>❌ No hay respuestas suficientes para generar resultados.</p></div>";
}

// Paso 4: Verificar resultados actualizados
echo "<h2>PASO 4: Verificando resultados actualizados</h2>";

$sql = "SELECT r.*, d.nombre as dimension_nombre
        FROM resultados r
        JOIN dimensiones d ON r.dimension_id = d.id
        WHERE r.sesion_id = $sesion_id AND r.valor > 0
        ORDER BY r.valor DESC";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID Resultado</th>
                <th>Dimensión</th>
                <th>Valor</th>
                <th>Percentil</th>
                <th>Visualización</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        $barClass = $row['valor'] >= 80 ? 'background-color: #28a745;' : ($row['valor'] >= 60 ? 'background-color: #17a2b8;' : 'background-color: #ffc107;');
        
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['dimension_nombre']}</td>
                <td>{$row['valor']}%</td>
                <td>{$row['percentil']}</td>
                <td>
                    <div class='dimension-bar'>
                        <div class='dimension-fill' style='width: {$row['valor']}%; $barClass'></div>
                    </div>
                </td>
              </tr>";
    }
    
    echo "</table>";
    echo "<div class='panel success'><p>✓ Resultados procesados correctamente.</p></div>";
} else {
    echo "<div class='panel error'><p>❌ No se encontraron resultados procesados con valor mayor a cero.</p></div>";
    
    // Verificar si hay resultados con valor cero
    $sql = "SELECT r.*, d.nombre as dimension_nombre
            FROM resultados r
            JOIN dimensiones d ON r.dimension_id = d.id
            WHERE r.sesion_id = $sesion_id
            ORDER BY r.id
            LIMIT 10";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='panel warning'><p>⚠️ Se encontraron algunos resultados con valor cero:</p></div>";
        echo "<table>
                <tr>
                    <th>ID Resultado</th>
                    <th>Dimensión</th>
                    <th>Valor</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['dimension_nombre']}</td>
                    <td>{$row['valor']}%</td>
                  </tr>";
        }
        
        echo "</table>";
    }
}

// Obtener resultado global actualizado
$sql = "SELECT resultado_global FROM sesiones_prueba WHERE id = $sesion_id";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<div class='panel'><p>Resultado global en sesiones_prueba: {$row['resultado_global']}%</p></div>";
}

// Verificar estructura en resultado-prueba.php
echo "<h2>PASO 5: Verificando integración con resultado-prueba.php</h2>";

// Crear una función que puede resolver problemas comunes
echo "<div class='panel'>";
echo "<p>La estructura esperada para resultado-prueba.php debe ser un array con dimensiones en el formato correcto.</p>";
echo "<p>Si los resultados no aparecen correctamente, asegúrate de revisar las siguientes condiciones en resultado-prueba.php:</p>";
echo "<ol>
        <li>Verificar que 'dimensiones' esté siendo correctamente formateado con 'nombre' y 'porcentaje' como claves</li>
        <li>Asegurar que sólo se muestren dimensiones con valor mayor que cero</li>
        <li>Comprobar que el resultado global se esté recuperando correctamente</li>
      </ol>";
echo "</div>";

// Enlaces para navegación
echo "<div style='margin-top: 30px; display: flex; gap: 10px;'>
        <a href='resultado-prueba.php?sesion_id=$sesion_id' class='btn btn-success'>
            <i></i> Ver resultados en página principal
        </a>
        <a href='pruebas.php' class='btn'>
            Volver a evaluaciones
        </a>
        <a href='process-test-results.php?sesion_id=$sesion_id&test_type=$test_type&force=1' class='btn btn-warning'>
            Forzar reprocesamiento
        </a>
      </div>";

// Proporcionar código para corregir problemas comunes
echo "<h2>Código para corregir problemas comunes</h2>";
echo "<div class='panel'>";
echo "<p>Si los resultados no se muestran correctamente en resultado-prueba.php, puedes ajustar la siguiente consulta SQL:</p>";
echo "<pre>";
echo "// Consulta directa para obtener dimensiones con valor > 0
\$sql = \"SELECT r.*, d.nombre as dimension_nombre 
        FROM resultados r
        JOIN dimensiones d ON r.dimension_id = d.id
        WHERE r.sesion_id = \$sesion_id AND r.valor > 0
        ORDER BY r.valor DESC\";

\$result = \$db->query(\$sql);

\$dimensiones = [];
if (\$result && \$result->num_rows > 0) {
    while (\$row = \$result->fetch_assoc()) {
        \$dimensiones[] = [
            'nombre' => \$row['dimension_nombre'],
            'porcentaje' => \$row['valor'],
            'interpretacion' => \$row['interpretacion'] ?? null
        ];
    }
}";
echo "</pre>";
echo "</div>";
?>

<script>
// Animar las barras de progreso al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const bars = document.querySelectorAll('.dimension-fill');
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