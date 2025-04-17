<?php
/**
 * Script para reiniciar o generar datos para sesiones de pruebas completadas sin respuestas
 * reset_test_sessions.php
 * 
 * Este script permite:
 * 1. Reiniciar pruebas completadas sin datos para permitir tomarlas nuevamente
 * 2. Generar respuestas ficticias aleatorias para pruebas sin datos
 */

// Configuración básica
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutos

// Incluir archivos de configuración
require_once 'config.php';
require_once 'includes/jobs-system.php';

// Obtener conexión a base de datos
$db = Database::getInstance();

// Iniciar contadores para estadísticas
$counters = [
    'reiniciadas' => 0,
    'completadas_ficticias' => 0,
    'errores' => 0
];

// Obtener parámetros de la URL
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$candidato_id = isset($_GET['candidato_id']) ? (int)$_GET['candidato_id'] : 0;
$prueba_id = isset($_GET['prueba_id']) ? (int)$_GET['prueba_id'] : 0;
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

echo "<h1>Herramienta de gestión de sesiones de pruebas</h1>";

// Función para reiniciar una sesión de prueba
function reiniciarSesion($db, $sesion_id) {
    // 1. Eliminar todas las respuestas asociadas a esta sesión
    $sql = "DELETE FROM respuestas WHERE sesion_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $sesion_id);
    $stmt->execute();
    
    // 2. Actualizar el estado de la sesión a 'en_progreso'
    $sql = "UPDATE sesiones_prueba SET estado = 'en_progreso', fecha_fin = NULL WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $sesion_id);
    return $stmt->execute();
}

// Función para generar respuestas aleatorias para una sesión
function generarRespuestasFicticias($db, $sesion_id, $prueba_id) {
    // 1. Obtener todas las preguntas de la prueba
    $sql = "SELECT id, tipo_pregunta, dimension_id FROM preguntas WHERE prueba_id = ? AND activa = 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $prueba_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preguntas = [];
    while ($row = $result->fetch_assoc()) {
        $preguntas[] = $row;
    }
    
    if (empty($preguntas)) {
        return false;
    }
    
    // 2. Para cada pregunta, obtener sus opciones y generar una respuesta aleatoria
    $respuestasGeneradas = 0;
    
    foreach ($preguntas as $pregunta) {
        // Obtener opciones para esta pregunta
        $sql = "SELECT id, valor FROM opciones_respuesta WHERE pregunta_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $pregunta['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $opciones = [];
        while ($row = $result->fetch_assoc()) {
            $opciones[] = $row;
        }
        
        // Si no hay opciones y es tipo likert, crear opciones predeterminadas
        if (empty($opciones) && $pregunta['tipo_pregunta'] === 'likert') {
            for ($i = 1; $i <= 5; $i++) {
                $opciones[] = ['id' => null, 'valor' => $i];
            }
        }
        
        if (!empty($opciones)) {
            // Seleccionar una opción aleatoria
            $opcion_aleatoria = $opciones[array_rand($opciones)];
            
            // Insertar la respuesta
            $sql = "INSERT INTO respuestas (sesion_id, pregunta_id, opcion_id, valor_escala, dimension_id, fecha_respuesta) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->getConnection()->prepare($sql);
            
            $opcion_id = isset($opcion_aleatoria['id']) ? $opcion_aleatoria['id'] : null;
            $valor = $opcion_aleatoria['valor'];
            
            $stmt->bind_param("iiiii", $sesion_id, $pregunta['id'], $opcion_id, $valor, $pregunta['dimension_id']);
            
            if ($stmt->execute()) {
                $respuestasGeneradas++;
            }
        }
    }
    
    // 3. Actualizar estado de la sesión a completada
    if ($respuestasGeneradas > 0) {
        $sql = "UPDATE sesiones_prueba SET estado = 'completada', fecha_fin = NOW() WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $sesion_id);
        $stmt->execute();
        
        return $respuestasGeneradas;
    }
    
    return false;
}

// Función para mostrar sesiones vacías o sin respuestas
function mostrarSesionesSinRespuestas($db, $candidato_id = 0) {
    // Verificamos primero qué campo existe en la tabla para fecha de fin
    $campos_posibles = ['fecha_fin', 'fecha_finalizacion', 'fechaFin'];
    $campo_fecha_fin = 'fecha_fin'; // Por defecto
    
    foreach ($campos_posibles as $campo) {
        $check_sql = "SHOW COLUMNS FROM sesiones_prueba LIKE '$campo'";
        $check_result = $db->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            $campo_fecha_fin = $campo;
            break;
        }
    }
    
    // Consulta base que incluya el conteo de respuestas
    $baseQuery = "
        SELECT s.id as sesion_id, s.candidato_id, s.prueba_id, s.fecha_inicio, s.$campo_fecha_fin, s.estado,
               p.titulo as prueba_titulo, 
               COUNT(r.id) as total_respuestas
        FROM sesiones_prueba s
        JOIN pruebas p ON s.prueba_id = p.id
        LEFT JOIN respuestas r ON s.id = r.sesion_id
        WHERE s.estado = 'completada'
    ";
    
    // Añadir filtro por candidato si se especificó
    if ($candidato_id > 0) {
        $baseQuery .= " AND s.candidato_id = " . (int)$candidato_id;
    }
    
    // Agrupar y ordenar
    $baseQuery .= " GROUP BY s.id ORDER BY s.fecha_inicio DESC";
    
    $result = $db->query($baseQuery);
    
    if ($result && $result->num_rows > 0) {
        echo "<h2>Sesiones completadas sin suficientes respuestas</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>
                <th>ID Sesión</th>
                <th>Candidato ID</th>
                <th>Prueba</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <th>Estado</th>
                <th>Respuestas</th>
                <th>Acciones</th>
              </tr>";
              
        while ($row = $result->fetch_assoc()) {
            // Determinar si es una sesión sin suficientes respuestas
            $esSesionVacia = ($row['total_respuestas'] < 5); // Consideramos "vacía" si tiene menos de 5 respuestas
            
            // Solo mostrar sesiones sin suficientes respuestas
            if ($esSesionVacia) {
                echo "<tr>
                        <td>{$row['sesion_id']}</td>
                        <td>{$row['candidato_id']}</td>
                        <td>{$row['prueba_titulo']}</td>
                        <td>{$row['fecha_inicio']}</td>
                        <td>{$row[$campo_fecha_fin]}</td>
                        <td>{$row['estado']}</td>
                        <td>{$row['total_respuestas']}</td>
                        <td>
                            <a href='reset_test_sessions.php?accion=reiniciar&sesion_id={$row['sesion_id']}' class='button'>Reiniciar</a>
                            <a href='reset_test_sessions.php?accion=generar&sesion_id={$row['sesion_id']}&prueba_id={$row['prueba_id']}' class='button'>Generar Respuestas</a>
                        </td>
                      </tr>";
            }
        }
        
        echo "</table>";
    } else {
        echo "<p>No se encontraron sesiones sin respuestas.</p>";
    }
}

// Aplicar estilos CSS básicos
echo "
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .button {
        display: inline-block;
        background-color: #4CAF50;
        color: white;
        padding: 6px 12px;
        text-align: center;
        text-decoration: none;
        margin: 2px;
        border-radius: 4px;
        cursor: pointer;
    }
    .button:hover { background-color: #45a049; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .navigation { margin: 20px 0; }
    .navigation a { margin-right: 15px; }
</style>
";

// Navegación
echo "<div class='navigation'>";
echo "<a href='reset_test_sessions.php' class='button'>Ver sesiones sin respuestas</a>";
echo "<a href='reset_test_sessions.php?accion=ver_candidatos' class='button'>Ver candidatos</a>";
echo "<a href='panel.php' class='button'>Volver al panel</a>";
echo "</div>";

// Manejar las diferentes acciones
if ($accion === 'reiniciar' && $sesion_id > 0) {
    // Reiniciar una sesión específica
    if (reiniciarSesion($db, $sesion_id)) {
        echo "<div class='success'>✓ Sesión #$sesion_id reiniciada correctamente. Ahora puede tomar la prueba nuevamente.</div>";
        $counters['reiniciadas']++;
    } else {
        echo "<div class='error'>✗ Error al reiniciar la sesión #$sesion_id.</div>";
        $counters['errores']++;
    }
    
    echo "<p><a href='reset_test_sessions.php' class='button'>Volver a la lista</a></p>";
} 
elseif ($accion === 'generar' && $sesion_id > 0 && $prueba_id > 0) {
    // Generar respuestas ficticias para una sesión
    $respuestasGeneradas = generarRespuestasFicticias($db, $sesion_id, $prueba_id);
    
    if ($respuestasGeneradas) {
        echo "<div class='success'>✓ Se generaron $respuestasGeneradas respuestas aleatorias para la sesión #$sesion_id.</div>";
        $counters['completadas_ficticias']++;
    } else {
        echo "<div class='error'>✗ Error al generar respuestas para la sesión #$sesion_id.</div>";
        $counters['errores']++;
    }
    
    echo "<p><a href='reset_test_sessions.php' class='button'>Volver a la lista</a></p>";
} 
elseif ($accion === 'ver_candidatos') {
    // Mostrar lista de candidatos
    $sql = "SELECT id, nombre, apellidos, email FROM candidatos ORDER BY apellidos, nombre";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<h2>Candidatos registrados</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Acción</th>
              </tr>";
              
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['apellidos']}</td>
                    <td>{$row['email']}</td>
                    <td><a href='reset_test_sessions.php?candidato_id={$row['id']}' class='button'>Ver sesiones</a></td>
                  </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No se encontraron candidatos registrados.</p>";
    }
} 
else {
    // Mostrar sesiones sin respuestas (vista predeterminada)
    mostrarSesionesSinRespuestas($db, $candidato_id);
}

// Mostrar resumen si se realizaron acciones
if ($counters['reiniciadas'] > 0 || $counters['completadas_ficticias'] > 0 || $counters['errores'] > 0) {
    echo "<h2>Resumen de acciones</h2>";
    echo "<ul>";
    if ($counters['reiniciadas'] > 0) echo "<li>{$counters['reiniciadas']} sesiones reiniciadas</li>";
    if ($counters['completadas_ficticias'] > 0) echo "<li>{$counters['completadas_ficticias']} sesiones completadas con datos ficticios</li>";
    if ($counters['errores'] > 0) echo "<li>{$counters['errores']} errores encontrados</li>";
    echo "</ul>";
}

// Mostrar información sobre el uso
echo "<h2>Instrucciones</h2>";
echo "<div>";
echo "<h3>Para reiniciar una prueba:</h3>";
echo "<ol>";
echo "<li>Seleccione 'Reiniciar' en la sesión que desee permitir tomar nuevamente</li>";
echo "<li>Esto eliminará todas las respuestas y cambiará el estado a 'en_progreso'</li>";
echo "<li>El candidato podrá retomar la prueba desde el principio</li>";
echo "</ol>";

echo "<h3>Para generar respuestas ficticias:</h3>";
echo "<ol>";
echo "<li>Seleccione 'Generar Respuestas' en la sesión que desee completar con datos aleatorios</li>";
echo "<li>El sistema generará respuestas aleatorias para todas las preguntas de la prueba</li>";
echo "<li>Esto es útil para pruebas de concepto o para simular datos de ejemplo</li>";
echo "</ol>";
echo "</div>";
?>