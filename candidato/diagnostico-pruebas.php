<?php
// Archivo: candidato/diagnostico-pruebas.php
// Descripción: Diagnóstico detallado para pruebas completadas

session_start();

// Verificar sesión
if (!isset($_SESSION['candidato_id'])) {
    echo "<p>No hay sesión activa. Debes iniciar sesión.</p>";
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

$candidato_id = $_SESSION['candidato_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Pruebas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #003366;
        }
        .panel {
            background: #f5f7fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0088cc;
        }
        .error {
            background: #fff8f8;
            border-left: 4px solid #dc3545;
        }
        .success {
            background: #f8fff8;
            border-left: 4px solid #28a745;
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
    </style>
</head>
<body>
    <h1>Diagnóstico de Pruebas Completadas</h1>
    <p>Candidato ID: <?php echo $candidato_id; ?></p>

    <div class="panel">
        <h2>1. Consulta directa a la base de datos</h2>
        <?php
        try {
            $db = Database::getInstance();
            
            $sql = "SELECT COUNT(*) as total FROM sesiones_prueba 
                    WHERE candidato_id = $candidato_id 
                    AND estado = 'completada'";
            
            $result = $db->query($sql);
            
            if ($result) {
                $total = $result->fetch_assoc()['total'];
                echo "<p>Total de sesiones completadas según la base de datos: <strong>$total</strong></p>";
                
                if ($total > 0) {
                    // Mostrar detalles de las sesiones
                    $sql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion 
                            FROM sesiones_prueba s
                            LEFT JOIN pruebas p ON s.prueba_id = p.id
                            WHERE s.candidato_id = $candidato_id 
                            AND s.estado = 'completada'
                            ORDER BY s.fecha_fin DESC";
                    
                    $result = $db->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        echo "<h3>Detalles de las sesiones completadas:</h3>";
                        echo "<table>
                                <tr>
                                    <th>ID</th>
                                    <th>Prueba ID</th>
                                    <th>Título</th>
                                    <th>Fecha Fin</th>
                                    <th>Resultado</th>
                                </tr>";
                        
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['prueba_id']}</td>
                                    <td>" . ($row['prueba_titulo'] ?? 'Desconocido') . "</td>
                                    <td>" . ($row['fecha_fin'] ?? 'No disponible') . "</td>
                                    <td>" . ($row['resultado_global'] ?? 'No disponible') . "</td>
                                  </tr>";
                        }
                        
                        echo "</table>";
                    } else {
                        echo "<p class='error'>No se pudieron obtener detalles de las sesiones a pesar del conteo positivo.</p>";
                    }
                } else {
                    echo "<p>No hay sesiones completadas en la base de datos.</p>";
                }
            } else {
                echo "<p class='error'>Error en la consulta: " . $db->getConnection()->error . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Excepción: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="panel">
        <h2>2. Consulta usando TestManager</h2>
        <?php
        try {
            if (file_exists(__DIR__ . '/../includes/TestManager.php')) {
                require_once __DIR__ . '/../includes/TestManager.php';
                
                if (class_exists('TestManager')) {
                    echo "<p>✅ Clase TestManager cargada correctamente</p>";
                    
                    $testManager = new TestManager();
                    
                    if (method_exists($testManager, 'getCompletedTests')) {
                        echo "<p>✅ Método getCompletedTests existe</p>";
                        
                        $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
                        
                        if (is_array($pruebasCompletadas)) {
                            echo "<p>Total de pruebas completadas según TestManager: <strong>" . count($pruebasCompletadas) . "</strong></p>";
                            
                            if (!empty($pruebasCompletadas)) {
                                echo "<h3>Detalles de las pruebas completadas:</h3>";
                                echo "<table>
                                        <tr>
                                            <th>ID</th>
                                            <th>Sesión ID</th>
                                            <th>Título</th>
                                            <th>Fecha Fin</th>
                                            <th>Resultado</th>
                                        </tr>";
                                
                                foreach ($pruebasCompletadas as $prueba) {
                                    echo "<tr>
                                            <td>" . ($prueba['id'] ?? 'N/A') . "</td>
                                            <td>" . ($prueba['sesion_id'] ?? 'N/A') . "</td>
                                            <td>" . ($prueba['prueba_titulo'] ?? 'Desconocido') . "</td>
                                            <td>" . ($prueba['fecha_fin'] ?? 'No disponible') . "</td>
                                            <td>" . ($prueba['resultado_global'] ?? 'No disponible') . "</td>
                                          </tr>";
                                }
                                
                                echo "</table>";
                                
                                // Verificar estructura para debug
                                echo "<h3>Estructura del primer elemento:</h3>";
                                echo "<pre>" . print_r($pruebasCompletadas[0], true) . "</pre>";
                            } else {
                                echo "<p class='error'>El método getCompletedTests devolvió un array vacío.</p>";
                            }
                        } else {
                            echo "<p class='error'>El método getCompletedTests no devolvió un array. Tipo devuelto: " . gettype($pruebasCompletadas) . "</p>";
                        }
                    } else {
                        echo "<p class='error'>❌ El método getCompletedTests no existe en la clase TestManager</p>";
                    }
                } else {
                    echo "<p class='error'>❌ La clase TestManager no existe en el archivo cargado</p>";
                }
            } else {
                echo "<p class='error'>❌ El archivo TestManager.php no existe en la ruta especificada</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Excepción al usar TestManager: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        ?>
    </div>

    <div class="panel">
        <h2>3. Código HTML actual de las pestañas</h2>
        <?php
        $file = __DIR__ . '/pruebas.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Buscar el fragmento de HTML para la pestaña de completadas
            if (preg_match('/<div class="tab-content(.*?)" id="tab-completadas">(.*?)<\/div>/s', $content, $matches)) {
                echo "<p>✅ Se encontró el código de la pestaña de completadas</p>";
                echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
                
                // Buscar la condición if-else
                if (preg_match('/empty\(\$pruebasCompletadas\)(.*?)<\/div>/s', $content, $condition)) {
                    echo "<p>✅ Se encontró la condición de verificación</p>";
                    echo "<pre>" . htmlspecialchars($condition[0]) . "</pre>";
                } else {
                    echo "<p class='error'>❌ No se encontró la condición para verificar si hay pruebas completadas</p>";
                }
            } else {
                echo "<p class='error'>❌ No se encontró el código de la pestaña de completadas</p>";
            }
        } else {
            echo "<p class='error'>❌ No se encontró el archivo pruebas.php</p>";
        }
        ?>
    </div>

    <div class="panel">
        <h2>4. Solución recomendada</h2>
        <p>Basado en los resultados anteriores, aquí hay una posible solución:</p>
        
        <div class="panel success">
            <h3>Modificar el archivo pruebas.php</h3>
            <p>Si los contadores muestran pruebas completadas pero no se están mostrando en la interfaz, el problema podría ser que:</p>
            <ol>
                <li>La variable $pruebasCompletadas está vacía a pesar de haber pruebas en la base de datos.</li>
                <li>Hay un error al procesar la condición if (empty($pruebasCompletadas)).</li>
                <li>La estructura de los datos en $pruebasCompletadas no es la esperada por el código HTML.</li>
            </ol>
            
            <p>Prueba la siguiente solución:</p>
            
            <pre>
// En pruebas.php, reemplaza el código que obtiene las pruebas completadas con esto:

// Si no hay pruebas completadas, intentar obtenerlas directamente de la base de datos
if (empty($pruebasCompletadas) && isset($candidato_id) && $candidato_id > 0) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT s.id as sesion_id, s.prueba_id, s.estado, s.fecha_inicio, s.fecha_fin, s.resultado_global,
                   p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                   c.nombre as categoria_nombre
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE s.candidato_id = $candidato_id
                AND s.estado = 'completada'
                ORDER BY s.fecha_fin DESC";
                
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $directCompletadas = [];
            while ($row = $result->fetch_assoc()) {
                // Asegurar que todos los campos necesarios estén presentes
                if (!isset($row['sesion_id'])) {
                    $row['sesion_id'] = $row['id'];
                }
                $directCompletadas[] = $row;
            }
            
            if (!empty($directCompletadas)) {
                $pruebasCompletadas = $directCompletadas;
                $pruebasCompletadasCount = count($pruebasCompletadas);
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta directa para pruebas completadas: " . $e->getMessage());
    }
}
            </pre>
        </div>
    </div>

    <a href="pruebas.php" class="btn">Volver a Pruebas</a>
</body>
</html>