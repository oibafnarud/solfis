<?php
/**
 * Fix de resultados para pruebas psicométricas
 * 
 * Este script corrige y visualiza adecuadamente los resultados de pruebas donde
 * las dimensiones están vinculadas a las opciones (como CMV e IPL) en lugar de
 * directamente a las preguntas.
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

// Determinar el tipo de prueba
$tipo_prueba = '';
if (stripos($prueba_titulo, 'motivaciones') !== false || stripos($prueba_titulo, 'cmv') !== false) {
    $tipo_prueba = 'CMV';
} elseif (stripos($prueba_titulo, 'personalidad') !== false || stripos($prueba_titulo, 'ipl') !== false) {
    $tipo_prueba = 'IPL';
} else {
    // Consultar si es un tipo de prueba con pares
    $sql = "SELECT COUNT(*) as count FROM preguntas 
            WHERE prueba_id = $prueba_id AND tipo_pregunta = 'pares'";
    $result = $db->query($sql);
    if ($result && $result->fetch_assoc()['count'] > 0) {
        $tipo_prueba = 'PARES';
    } else {
        $tipo_prueba = 'ESTÁNDAR';
    }
}

// Título basado en el tipo de prueba
$titulo_pagina = "Corrección de Resultados - $tipo_prueba: $prueba_titulo";

// Acciones según el modo
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'verificar';
$mensaje = '';
$error = '';
$dimensiones = [];

if ($modo === 'corregir') {
    try {
        // 1. Eliminar resultados antiguos
        $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
        
        // 2. Obtener respuestas y sus dimensiones asociadas a través de las opciones
        $sql = "SELECT r.id, r.pregunta_id, r.opcion_id, 
                        o.dimension_id, d.nombre as dimension_nombre
                FROM respuestas r
                JOIN opciones_respuesta o ON r.opcion_id = o.id
                JOIN dimensiones d ON o.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id
                ORDER BY r.id";
        
        $result = $db->query($sql);
        $respuestas_count = 0;
        $conteo_dimensiones = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $respuestas_count++;
                
                // Contar apariciones de cada dimensión
                if (!isset($conteo_dimensiones[$row['dimension_id']])) {
                    $conteo_dimensiones[$row['dimension_id']] = [
                        'nombre' => $row['dimension_nombre'],
                        'contador' => 0
                    ];
                }
                $conteo_dimensiones[$row['dimension_id']]['contador']++;
            }
            
            // 3. Insertar nuevos resultados basados en el conteo de dimensiones
            foreach ($conteo_dimensiones as $dimension_id => $data) {
                $porcentaje = ($respuestas_count > 0) 
                    ? round(($data['contador'] / $respuestas_count) * 100) 
                    : 0;
                
                // Solo insertar dimensiones con valor > 0
                if ($porcentaje > 0) {
                    $sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                            VALUES ($sesion_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if (!$db->query($sql)) {
                        throw new Exception("Error al insertar resultado para dimensión {$data['nombre']}: " . $db->getConnection()->error);
                    }
                }
            }
            
            // 4. Actualizar el resultado global en la sesión
            if (!empty($conteo_dimensiones)) {
                $valores = [];
                foreach ($conteo_dimensiones as $data) {
                    $porcentaje = round(($data['contador'] / $respuestas_count) * 100);
                    if ($porcentaje > 0) {
                        $valores[] = $porcentaje;
                    }
                }
                
                if (!empty($valores)) {
                    $valorGlobal = round(array_sum($valores) / count($valores));
                    $updateSql = "UPDATE sesiones_prueba SET resultado_global = $valorGlobal WHERE id = $sesion_id";
                    
                    if (!$db->query($updateSql)) {
                        throw new Exception("Error al actualizar resultado global: " . $db->getConnection()->error);
                    }
                }
            }
            
            $mensaje = "Resultados generados correctamente. Se procesaron $respuestas_count respuestas y " . count($conteo_dimensiones) . " dimensiones.";
        } else {
            $error = "No se encontraron respuestas con dimensiones asociadas para esta sesión.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener resultados actuales para mostrar
try {
    $sql = "SELECT r.*, d.nombre as dimension_nombre
            FROM resultados r
            JOIN dimensiones d ON r.dimension_id = d.id
            WHERE r.sesion_id = $sesion_id
            ORDER BY r.valor DESC";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones[] = [
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
    $error = "Error al consultar resultados: " . $e->getMessage();
}

// Obtener código SQL para actualizar resultado-prueba.php
$codigo_mejora = "
// Añade este código a resultado-prueba.php después de los otros métodos para obtener dimensiones
if (empty(\$dimensiones)) {
    try {
        \$db = Database::getInstance();
        
        // Consulta específica para pruebas donde las dimensiones están en opciones (CMV, IPL)
        \$sql = \"SELECT r.*, d.nombre as dimension_nombre
                FROM resultados r
                JOIN dimensiones d ON r.dimension_id = d.id
                WHERE r.sesion_id = \$sesion_id AND r.valor > 0
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
        }
    } catch (Exception \$e) {
        error_log(\"Error al obtener dimensiones: \" . \$e->getMessage());
    }
}";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?></title>
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
    </style>
</head>
<body>
    <header>
        <h1><?php echo $titulo_pagina; ?></h1>
        <p>Sesión ID: <?php echo $sesion_id; ?></p>
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
        <h2>Información de la Prueba</h2>
        <div class="info-box">
            <p><strong>Título:</strong> <?php echo htmlspecialchars($prueba_titulo); ?></p>
            <p><strong>Tipo detectado:</strong> <?php echo $tipo_prueba; ?></p>
            <p><strong>ID de Prueba:</strong> <?php echo $prueba_id; ?></p>
            <p><strong>Estado:</strong> <?php echo $session['estado']; ?></p>
            <p><strong>Fecha de finalización:</strong> <?php echo date('d/m/Y H:i', strtotime($session['fecha_fin'])); ?></p>
            <p><strong>Resultado Global:</strong> <?php echo $resultado_global; ?>%</p>
        </div>
        
        <h2>Dimensiones Actuales</h2>
        <?php if (empty($dimensiones)): ?>
        <div class="warning-box">
            <h3>⚠️ Sin dimensiones</h3>
            <p>No se encontraron dimensiones para esta sesión. Esto podría indicar que los resultados no se han procesado correctamente.</p>
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
                <?php foreach ($dimensiones as $dimension): ?>
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
            <?php if ($modo !== 'corregir'): ?>
            <a href="fix-results.php?sesion_id=<?php echo $sesion_id; ?>&modo=corregir" class="btn btn-warning">Regenerar Resultados</a>
            <?php endif; ?>
            <a href="resultado-prueba.php?sesion_id=<?php echo $sesion_id; ?>" class="btn">Ver Página de Resultados</a>
            <a href="pruebas.php" class="btn">Volver a Pruebas</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Solución para resultado-prueba.php</h2>
        <p>Para corregir de forma permanente la visualización de resultados en todas las pruebas de tipo <?php echo $tipo_prueba; ?>, puedes añadir el siguiente código a tu archivo <code>resultado-prueba.php</code>:</p>
        
        <div class="code-header">Código para añadir a resultado-prueba.php:</div>
        <pre><?php echo htmlspecialchars($codigo_mejora); ?></pre>
        
        <div class="info-box">
            <h3>⚠️ Importante</h3>
            <p>Añade este código justo después de los otros intentos de obtener dimensiones, pero antes de que se utilicen las dimensiones para mostrarlas en la página.</p>
            <p>Este código garantiza que las dimensiones se obtengan directamente de la tabla <code>resultados</code>, evitando problemas con las diferentes estructuras de datos.</p>
        </div>
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