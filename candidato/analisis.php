<?php
/**
 * Análisis exhaustivo de estructura de pruebas
 */
require_once '../includes/jobs-system.php';

$db = Database::getInstance();

// Función para imprimir datos de forma legible
function printTable($data, $headers = null) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    
    // Imprimir encabezados si existen
    if ($headers) {
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
    }
    
    // Imprimir datos
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . (is_null($cell) ? "NULL" : htmlspecialchars($cell)) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Obtener todas las pruebas
echo "<h1>Análisis de Estructura de Pruebas</h1>";
$sql = "SELECT id, titulo FROM pruebas ORDER BY id";
$result = $db->query($sql);

$pruebas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pruebas[] = $row;
    }
}

echo "<h2>Pruebas disponibles</h2>";
printTable($pruebas, ['ID', 'Título']);

// Para cada prueba, analizar su estructura
foreach ($pruebas as $prueba) {
    $prueba_id = $prueba['id'];
    $prueba_titulo = $prueba['titulo'];
    
    echo "<h2>Análisis de: $prueba_titulo (ID: $prueba_id)</h2>";
    
    // 1. Verificar preguntas
    $sql = "SELECT id, texto, tipo_pregunta, dimension_id FROM preguntas WHERE prueba_id = $prueba_id LIMIT 5";
    $result = $db->query($sql);
    
    $preguntas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $preguntas[] = $row;
        }
    }
    
    echo "<h3>Muestra de preguntas (primeras 5)</h3>";
    if (!empty($preguntas)) {
        printTable($preguntas, ['ID', 'Texto', 'Tipo', 'Dimensión ID']);
    } else {
        echo "<p>No se encontraron preguntas para esta prueba.</p>";
    }
    
    // 2. Contar preguntas por tipo
    $sql = "SELECT tipo_pregunta, COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id GROUP BY tipo_pregunta";
    $result = $db->query($sql);
    
    $tipos_preguntas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tipos_preguntas[] = $row;
        }
    }
    
    echo "<h3>Tipos de preguntas</h3>";
    if (!empty($tipos_preguntas)) {
        printTable($tipos_preguntas, ['Tipo', 'Total']);
    } else {
        echo "<p>No se encontraron preguntas para esta prueba.</p>";
    }
    
    // 3. Verificar dimensiones asignadas a preguntas
    $sql = "SELECT DISTINCT p.dimension_id, d.nombre 
            FROM preguntas p
            LEFT JOIN dimensiones d ON p.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id AND p.dimension_id IS NOT NULL";
    $result = $db->query($sql);
    
    $dimensiones_preguntas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_preguntas[] = $row;
        }
    }
    
    echo "<h3>Dimensiones asignadas a preguntas</h3>";
    if (!empty($dimensiones_preguntas)) {
        printTable($dimensiones_preguntas, ['Dimensión ID', 'Nombre']);
    } else {
        echo "<p>No se encontraron dimensiones asignadas a preguntas.</p>";
    }
    
    // 4. Verificar opciones de respuesta
    $sql = "SELECT o.id, o.pregunta_id, SUBSTRING(o.texto, 1, 50) as texto_corto, o.dimension_id, d.nombre as dimension_nombre
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            LEFT JOIN dimensiones d ON o.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id
            LIMIT 10";
    $result = $db->query($sql);
    
    $opciones = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $opciones[] = $row;
        }
    }
    
    echo "<h3>Muestra de opciones de respuesta (primeras 10)</h3>";
    if (!empty($opciones)) {
        printTable($opciones, ['ID', 'Pregunta ID', 'Texto', 'Dimensión ID', 'Dimensión Nombre']);
    } else {
        echo "<p>No se encontraron opciones de respuesta para esta prueba.</p>";
    }
    
    // 5. Verificar dimensiones asignadas a opciones
    $sql = "SELECT DISTINCT o.dimension_id, d.nombre 
            FROM opciones_respuesta o
            JOIN preguntas p ON o.pregunta_id = p.id
            LEFT JOIN dimensiones d ON o.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id AND o.dimension_id IS NOT NULL";
    $result = $db->query($sql);
    
    $dimensiones_opciones = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dimensiones_opciones[] = $row;
        }
    }
    
    echo "<h3>Dimensiones asignadas a opciones</h3>";
    if (!empty($dimensiones_opciones)) {
        printTable($dimensiones_opciones, ['Dimensión ID', 'Nombre']);
    } else {
        echo "<p>No se encontraron dimensiones asignadas a opciones.</p>";
    }
    
    // 6. Verificar sesiones completadas
    $sql = "SELECT id, fecha_inicio, fecha_fin, resultado_global FROM sesiones_prueba 
            WHERE prueba_id = $prueba_id AND estado = 'completada'";
    $result = $db->query($sql);
    
    $sesiones = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sesiones[] = $row;
        }
    }
    
    echo "<h3>Sesiones completadas</h3>";
    if (!empty($sesiones)) {
        printTable($sesiones, ['ID', 'Fecha Inicio', 'Fecha Fin', 'Resultado Global']);
    } else {
        echo "<p>No se encontraron sesiones completadas para esta prueba.</p>";
    }
    
    // 7. Verificar respuestas para una sesión (si hay)
    if (!empty($sesiones)) {
        $sesion_id = $sesiones[0]['id']; // Tomar la primera sesión
        
        $sql = "SELECT r.id, r.pregunta_id, r.opcion_id, 
                        p.tipo_pregunta,
                        SUBSTRING(o.texto, 1, 50) as opcion_texto,
                        o.dimension_id, d.nombre as dimension_nombre
                FROM respuestas r
                JOIN preguntas p ON r.pregunta_id = p.id
                JOIN opciones_respuesta o ON r.opcion_id = o.id
                LEFT JOIN dimensiones d ON o.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id
                LIMIT 10";
        $result = $db->query($sql);
        
        $respuestas = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $respuestas[] = $row;
            }
        }
        
        echo "<h3>Muestra de respuestas para sesión $sesion_id (primeras 10)</h3>";
        if (!empty($respuestas)) {
            printTable($respuestas, ['ID', 'Pregunta ID', 'Opción ID', 'Tipo Pregunta', 'Texto Opción', 'Dimensión ID', 'Dimensión Nombre']);
        } else {
            echo "<p>No se encontraron respuestas para esta sesión.</p>";
        }
        
        // 8. Verificar resultados para la sesión
        $sql = "SELECT r.id, r.dimension_id, d.nombre as dimension_nombre, r.valor, r.percentil
                FROM resultados r
                JOIN dimensiones d ON r.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id";
        $result = $db->query($sql);
        
        $resultados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $resultados[] = $row;
            }
        }
        
        echo "<h3>Resultados para sesión $sesion_id</h3>";
        if (!empty($resultados)) {
            printTable($resultados, ['ID', 'Dimensión ID', 'Dimensión Nombre', 'Valor', 'Percentil']);
        } else {
            echo "<p>No se encontraron resultados para esta sesión.</p>";
        }
    }
    
    echo "<hr style='margin: 30px 0;'>";
}
?>