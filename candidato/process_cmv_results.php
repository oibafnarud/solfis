<?php
// Guardar como process_cmv_results.php en la carpeta candidato
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    die("Acceso no autorizado");
}

require_once '../includes/jobs-system.php';

$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

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

echo "<h1>Procesamiento de resultados: $prueba_titulo</h1>";
echo "<h2>Sesión ID: $sesion_id</h2>";

// PASO 1: Verificar dimensiones de motivación
echo "<h2>PASO 1: Verificando dimensiones de motivación</h2>";

$sql = "SELECT id, nombre FROM dimensiones WHERE tipo = 'motiv'";
$result = $db->query($sql);
$dimensiones = [];
$dimensionIds = [];

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
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
    echo "<p>✓ Se encontraron " . count($dimensiones) . " dimensiones de motivación.</p>";
} else {
    echo "<p style='color:red'>❌ No se encontraron dimensiones de tipo 'motiv'. Verificando con otros tipos:</p>";
    
    // Intentar con el nombre en lugar del tipo
    $sql = "SELECT id, nombre, tipo FROM dimensiones WHERE nombre LIKE '%Motivación%'";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>
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
        echo "<p>✓ Se encontraron " . count($dimensiones) . " dimensiones de motivación buscando por nombre.</p>";
    } else {
        echo "<p style='color:red'>❌ No se encontraron dimensiones de motivación. Es necesario verificar la tabla dimensiones.</p>";
        
        // Mostrar todos los tipos disponibles
        $sql = "SELECT DISTINCT tipo FROM dimensiones";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<p>Tipos de dimensiones disponibles:</p><ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>{$row['tipo']}</li>";
            }
            echo "</ul>";
        }
    }
}

if (empty($dimensiones)) {
    echo "<p style='color:red'>❌ No se pueden procesar resultados sin dimensiones de motivación. Por favor verifica la tabla dimensiones.</p>";
    echo "<h3>Todas las dimensiones en la base de datos:</h3>";
    
    $sql = "SELECT id, nombre, tipo, bipolar FROM dimensiones LIMIT 20";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Bipolar</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['tipo']}</td>
                    <td>{$row['bipolar']}</td>
                  </tr>";
        }
        
        echo "</table>";
    }
    
    // No seguir si no hay dimensiones
    echo "<p><a href='pruebas.php'>Volver a evaluaciones</a></p>";
    exit;
}

// PASO 2: Verificar opciones de respuesta y su mapeo a dimensiones
echo "<h2>PASO 2: Verificando opciones de respuesta</h2>";

$sql = "SELECT o.id, o.pregunta_id, p.texto as pregunta_texto, o.texto, o.dimension_id,
        d.nombre as dimension_nombre, p.par_id 
        FROM opciones_respuesta o
        JOIN preguntas p ON o.pregunta_id = p.id
        LEFT JOIN dimensiones d ON o.dimension_id = d.id
        WHERE p.prueba_id = $prueba_id
        ORDER BY p.par_id, p.id, o.id
        LIMIT 50";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>ID Opción</th>
                <th>Par ID</th>
                <th>Pregunta ID</th>
                <th>Opción Texto</th>
                <th>Dimensión ID</th>
                <th>Dimensión Nombre</th>
            </tr>";
    
    $mappingOK = true;
    $totalOpciones = 0;
    
    while ($row = $result->fetch_assoc()) {
        $totalOpciones++;
        $mappingStatus = !empty($row['dimension_id']) ? "✓" : "❌";
        $mappingOK = $mappingOK && !empty($row['dimension_id']);
        
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['par_id']}</td>
                <td>{$row['pregunta_id']}</td>
                <td>" . substr($row['texto'], 0, 50) . "...</td>
                <td>{$row['dimension_id']} $mappingStatus</td>
                <td>{$row['dimension_nombre']}</td>
              </tr>";
    }
    
    echo "</table>";
    
    if ($mappingOK) {
        echo "<p>✓ Todas las opciones tienen asignada una dimensión correctamente.</p>";
    } else {
        echo "<p style='color:red'>❌ Hay opciones sin dimensión asignada. Es necesario corregir la tabla opciones_respuesta.</p>";
    }
} else {
    echo "<p style='color:red'>❌ No se encontraron opciones de respuesta para esta prueba.</p>";
}

// PASO 3: Verificar respuestas del candidato
echo "<h2>PASO 3: Verificando respuestas del candidato</h2>";

$sql = "SELECT r.id, r.pregunta_id, r.opcion_id, o.texto as opcion_texto, 
        o.dimension_id, d.nombre as dimension_nombre, p.par_id
        FROM respuestas r
        JOIN opciones_respuesta o ON r.opcion_id = o.id
        JOIN preguntas p ON r.pregunta_id = p.id
        LEFT JOIN dimensiones d ON o.dimension_id = d.id
        WHERE r.sesion_id = $sesion_id
        ORDER BY p.par_id, r.id";
$result = $db->query($sql);

$conteo = [];
$total = 0;

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
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
    echo "<p>✓ Se encontraron $total respuestas del candidato.</p>";
} else {
    echo "<p style='color:red'>❌ No se encontraron respuestas para esta sesión.</p>";
}

// PASO 4: Crear/actualizar resultados
echo "<h2>PASO 4: Procesando resultados</h2>";

if ($total > 0) {
    // Mostrar conteo por dimensión
    echo "<h3>Conteo por dimensión:</h3>";
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Dimensión</th>
                <th>Conteo</th>
                <th>Porcentaje</th>
            </tr>";
    
    foreach ($dimensiones as $dimId => $dimNombre) {
        $count = isset($conteo[$dimId]) ? $conteo[$dimId] : 0;
        $porcentaje = ($total > 0) ? round(($count / $total) * 100) : 0;
        
        echo "<tr>
                <td>$dimNombre</td>
                <td>$count</td>
                <td>$porcentaje%</td>
              </tr>";
    }
    
    echo "</table>";
    
    // Borrar resultados anteriores para esta sesión
    $db->query("DELETE FROM resultados WHERE sesion_id = $sesion_id");
    echo "<p>✓ Resultados anteriores eliminados.</p>";
    
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
            echo "<p style='color:red'>❌ Error al guardar resultado para dimensión $dimNombre: " . $db->getConnection()->error . "</p>";
        }
    }
    
    echo "<p>✓ $resultados_insertados resultados insertados correctamente.</p>";
    
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
            echo "<p>✓ Resultado global actualizado: $valorGlobal%</p>";
        } else {
            echo "<p style='color:red'>❌ Error al actualizar resultado global: " . $db->getConnection()->error . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ No hay respuestas suficientes para generar resultados.</p>";
}

// PASO 5: Verificar resultados actualizados
echo "<h2>PASO 5: Verificando resultados actualizados</h2>";

$sql = "SELECT r.*, d.nombre as dimension_nombre
        FROM resultados r
        JOIN dimensiones d ON r.dimension_id = d.id
        WHERE r.sesion_id = $sesion_id
        ORDER BY r.valor DESC";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>ID Resultado</th>
                <th>Dimensión</th>
                <th>Valor</th>
                <th>Percentil</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['dimension_nombre']}</td>
                <td>{$row['valor']}%</td>
                <td>{$row['percentil']}</td>
              </tr>";
    }
    
    echo "</table>";
    echo "<p>✓ Resultados procesados correctamente.</p>";
} else {
    echo "<p style='color:red'>❌ No se encontraron resultados procesados. Verifica la tabla resultados.</p>";
}

// Obtener resultado global actualizado
$sql = "SELECT resultado_global FROM sesiones_prueba WHERE id = $sesion_id";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p>Resultado global en sesiones_prueba: {$row['resultado_global']}%</p>";
}

// Enlaces para navegación
echo "<div style='margin-top: 30px;'>
        <a href='resultado-prueba.php?sesion_id=$sesion_id' style='padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Ver resultados actualizados</a>
        &nbsp;
        <a href='pruebas.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Volver a evaluaciones</a>
      </div>";
?>