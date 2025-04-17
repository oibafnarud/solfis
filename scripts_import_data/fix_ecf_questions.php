<?php
/**
 * Script para corregir y recargar las preguntas de ECF (Evaluación de Competencias Fundamentales)
 * fix_ecf_questions.php
 */

// Configuración básica
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutos

// Incluir archivos de configuración
require_once '../config.php';
require_once '../includes/jobs-system.php';

// Obtener conexión a base de datos
$db = Database::getInstance();

// Iniciar contadores para estadísticas
$counters = [
    'preguntas_activadas' => 0,
    'preguntas_creadas' => 0,
    'opciones_creadas' => 0,
    'errores' => 0
];

// Obtener parámetros de la URL
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

echo "<h1>Corrección de Preguntas ECF</h1>";

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
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
    .navigation { margin: 20px 0; }
    .navigation a { margin-right: 15px; }
    .progress { margin: 10px 0; }
    .progress-bar {
        width: 100%;
        background-color: #e0e0e0;
        padding: 3px;
        border-radius: 3px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, .2);
    }
    .progress-bar-fill {
        display: block;
        height: 22px;
        background-color: #4CAF50;
        border-radius: 3px;
        transition: width 500ms ease-in-out;
    }
</style>
";

// Navegación
echo "<div class='navigation'>";
echo "<a href='fix_ecf_questions.php' class='button'>Inicio</a>";
echo "<a href='fix_ecf_questions.php?accion=analizar' class='button'>Analizar ECF</a>";
echo "<a href='fix_ecf_questions.php?accion=activar' class='button'>Activar Preguntas</a>";
echo "<a href='fix_ecf_questions.php?accion=recargar' class='button'>Recargar Preguntas</a>";
echo "<a href='panel.php' class='button'>Volver al Panel</a>";
echo "</div>";

/**
 * Función para obtener información sobre la prueba ECF
 */
function obtenerInfoECF($db) {
    // Buscar la prueba ECF
    $sql = "SELECT * FROM pruebas WHERE titulo LIKE '%Competencias Fundamentales%' OR titulo LIKE '%ECF%'";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Función para analizar el estado actual de las preguntas ECF
 */
function analizarPreguntasECF($db) {
    $prueba = obtenerInfoECF($db);
    
    if (!$prueba) {
        echo "<div class='error'>No se encontró la prueba ECF en la base de datos.</div>";
        return;
    }
    
    $prueba_id = $prueba['id'];
    echo "<h2>Análisis de la prueba: {$prueba['titulo']} (ID: $prueba_id)</h2>";
    
    // Contar preguntas
    $sql = "SELECT 
                COUNT(*) as total_preguntas,
                SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as preguntas_activas,
                COUNT(DISTINCT tipo_pregunta) as tipos_distintos
            FROM preguntas
            WHERE prueba_id = $prueba_id";
    
    $result = $db->query($sql);
    $stats = $result->fetch_assoc();
    
    echo "<div class='stats'>";
    echo "<p><strong>Total de preguntas:</strong> {$stats['total_preguntas']}</p>";
    echo "<p><strong>Preguntas activas:</strong> {$stats['preguntas_activas']}</p>";
    echo "<p><strong>Tipos de preguntas:</strong> {$stats['tipos_distintos']}</p>";
    echo "</div>";
    
    // Mostrar distribución por tipo
    $sql = "SELECT tipo_pregunta, COUNT(*) as total
            FROM preguntas
            WHERE prueba_id = $prueba_id
            GROUP BY tipo_pregunta";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Distribución por tipo de pregunta</h3>";
        echo "<table>";
        echo "<tr><th>Tipo</th><th>Cantidad</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['tipo_pregunta']}</td><td>{$row['total']}</td></tr>";
        }
        
        echo "</table>";
    }
    
    // Mostrar distribución por dimensión
    $sql = "SELECT d.nombre, COUNT(p.id) as total
            FROM preguntas p
            JOIN dimensiones d ON p.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id
            GROUP BY d.id";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Distribución por dimensión</h3>";
        echo "<table>";
        echo "<tr><th>Dimensión</th><th>Cantidad</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['nombre']}</td><td>{$row['total']}</td></tr>";
        }
        
        echo "</table>";
    }
    
    // Mostrar algunas preguntas de ejemplo
    $sql = "SELECT p.id, p.texto, p.tipo_pregunta, p.activa, d.nombre as dimension
            FROM preguntas p
            LEFT JOIN dimensiones d ON p.dimension_id = d.id
            WHERE p.prueba_id = $prueba_id
            ORDER BY p.orden
            LIMIT 10";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Primeras 10 preguntas</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Texto</th><th>Tipo</th><th>Dimensión</th><th>Activa</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $activa = $row['activa'] ? '<span class="success">Sí</span>' : '<span class="error">No</span>';
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . substr($row['texto'], 0, 50) . "...</td>
                    <td>{$row['tipo_pregunta']}</td>
                    <td>{$row['dimension']}</td>
                    <td>$activa</td>
                  </tr>";
        }
        
        echo "</table>";
    }
    
    // Verificar si hay opciones para todas las preguntas
    $sql = "SELECT p.id, 
                  (SELECT COUNT(*) FROM opciones_respuesta WHERE pregunta_id = p.id) as num_opciones
            FROM preguntas p
            WHERE p.prueba_id = $prueba_id";
    
    $result = $db->query($sql);
    $preguntas_sin_opciones = 0;
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['num_opciones'] == 0) {
                $preguntas_sin_opciones++;
            }
        }
    }
    
    if ($preguntas_sin_opciones > 0) {
        echo "<div class='warning'><p>Hay $preguntas_sin_opciones preguntas sin opciones de respuesta.</p></div>";
    } else {
        echo "<div class='success'><p>Todas las preguntas tienen opciones de respuesta.</p></div>";
    }
    
    // Recomendar acciones
    echo "<h3>Recomendaciones</h3>";
    
    if ($stats['preguntas_activas'] < $stats['total_preguntas']) {
        echo "<p class='warning'>⚠️ Se recomienda activar todas las preguntas.</p>";
        echo "<p><a href='fix_ecf_questions.php?accion=activar' class='button'>Activar todas las preguntas</a></p>";
    }
    
    if ($preguntas_sin_opciones > 0) {
        echo "<p class='warning'>⚠️ Se recomienda recargar las preguntas para asegurar que todas tengan opciones.</p>";
        echo "<p><a href='fix_ecf_questions.php?accion=recargar' class='button'>Recargar preguntas</a></p>";
    }
    
    if ($stats['total_preguntas'] < 30) {
        echo "<p class='warning'>⚠️ La prueba tiene menos preguntas de las esperadas. Se recomienda recargar las preguntas.</p>";
        echo "<p><a href='fix_ecf_questions.php?accion=recargar' class='button'>Recargar preguntas</a></p>";
    }
}

/**
 * Función para activar todas las preguntas ECF
 */
function activarPreguntasECF($db) {
    $prueba = obtenerInfoECF($db);
    
    if (!$prueba) {
        echo "<div class='error'>No se encontró la prueba ECF en la base de datos.</div>";
        return;
    }
    
    $prueba_id = $prueba['id'];
    echo "<h2>Activando preguntas para: {$prueba['titulo']}</h2>";
    
    $sql = "UPDATE preguntas SET activa = 1 WHERE prueba_id = $prueba_id AND (activa = 0 OR activa IS NULL)";
    
    if ($db->query($sql)) {
        $filas_afectadas = $db->getConnection()->affected_rows;
        echo "<div class='success'>✅ $filas_afectadas preguntas activadas correctamente.</div>";
        $GLOBALS['counters']['preguntas_activadas'] += $filas_afectadas;
    } else {
        echo "<div class='error'>❌ Error al activar las preguntas: " . $db->getConnection()->error . "</div>";
        $GLOBALS['counters']['errores']++;
    }
    
    echo "<p><a href='fix_ecf_questions.php?accion=analizar' class='button'>Ver estado actualizado</a></p>";
}

/**
 * Función para recargar las preguntas ECF
 */
function recargarPreguntasECF($db) {
    $prueba = obtenerInfoECF($db);
    
    if (!$prueba) {
        echo "<div class='error'>No se encontró la prueba ECF en la base de datos.</div>";
        return;
    }
    
    $prueba_id = $prueba['id'];
    echo "<h2>Recargando preguntas para: {$prueba['titulo']}</h2>";

    // 1. Obtener dimensiones necesarias
    echo "<h3>1. Verificando dimensiones...</h3>";
    
    $dimensiones = [
        'Responsabilidad' => null,
        'Integridad' => null,
        'Adaptabilidad' => null,
        'Comunicación Básica' => null,
        'Trabajo en Equipo' => null
    ];
    
    foreach ($dimensiones as $nombre => $id) {
        $sql = "SELECT id FROM dimensiones WHERE nombre = '" . $db->escape($nombre) . "'";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $dimensiones[$nombre] = $result->fetch_assoc()['id'];
            echo "<div class='success'>✅ Dimensión '$nombre' encontrada (ID: {$dimensiones[$nombre]})</div>";
        } else {
            // Crear la dimensión si no existe
            $sql = "INSERT INTO dimensiones (nombre, descripcion, tipo, bipolar) 
                    VALUES (
                        '" . $db->escape($nombre) . "', 
                        'Dimensión para ECF: $nombre', 
                        'primaria', 
                        0
                    )";
            
            if ($db->query($sql)) {
                $dimensiones[$nombre] = $db->getConnection()->insert_id;
                echo "<div class='success'>✅ Dimensión '$nombre' creada (ID: {$dimensiones[$nombre]})</div>";
                
                // Asociar dimensión a la prueba
                $sql = "INSERT INTO pruebas_dimensiones (prueba_id, dimension_id, ponderacion) 
                        VALUES ($prueba_id, {$dimensiones[$nombre]}, 1.0)";
                $db->query($sql);
            } else {
                echo "<div class='error'>❌ Error al crear dimensión '$nombre': " . $db->getConnection()->error . "</div>";
                $GLOBALS['counters']['errores']++;
            }
        }
    }
    
    // Verificar que todas las dimensiones existan
    $dimensiones_faltantes = false;
    foreach ($dimensiones as $nombre => $id) {
        if ($id === null) {
            echo "<div class='error'>❌ No se pudo crear la dimensión '$nombre'</div>";
            $dimensiones_faltantes = true;
            $GLOBALS['counters']['errores']++;
        }
    }
    
    if ($dimensiones_faltantes) {
        echo "<div class='error'>❌ No se pueden recargar las preguntas porque faltan dimensiones.</div>";
        return;
    }
    
    // 2. Definir preguntas ECF Likert
    echo "<h3>2. Definiendo preguntas Likert...</h3>";
    
    $preguntasECF_Likert = [
        // Responsabilidad
        ['dimension' => 'Responsabilidad', 'texto' => 'Cumplo con mis plazos incluso cuando surgen obstáculos.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 1],
        ['dimension' => 'Responsabilidad', 'texto' => 'Prefiero asumir las consecuencias de mis errores que ocultarlos.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 2],
        ['dimension' => 'Responsabilidad', 'texto' => 'Asumo tareas adicionales cuando es necesario, aunque no sean mi responsabilidad directa.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 3],
        ['dimension' => 'Responsabilidad', 'texto' => 'Soy puntual en mis compromisos laborales.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 4],
        ['dimension' => 'Responsabilidad', 'texto' => 'Reviso mi trabajo para asegurarme de que no tiene errores.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 5],
        ['dimension' => 'Responsabilidad', 'texto' => 'Si prometo hacer algo, lo cumplo sin importar las dificultades que encuentre.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 6],
        
        // Integridad
        ['dimension' => 'Integridad', 'texto' => 'Digo la verdad aunque pueda causarme problemas.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 7],
        ['dimension' => 'Integridad', 'texto' => 'Reconozco cuando no tengo conocimiento suficiente sobre un tema.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 8],
        ['dimension' => 'Integridad', 'texto' => 'Mantengo las promesas que hago a mis compañeros.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 9],
        ['dimension' => 'Integridad', 'texto' => 'Informo de errores aunque nadie se daría cuenta si no lo hiciera.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 10],
        ['dimension' => 'Integridad', 'texto' => 'Respeto la confidencialidad de la información sensible.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 11],
        ['dimension' => 'Integridad', 'texto' => 'Trato a todos con respeto, independientemente de su posición jerárquica.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 12],
        
        // Adaptabilidad
        ['dimension' => 'Adaptabilidad', 'texto' => 'Me siento cómodo cuando cambian los procedimientos de trabajo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 13],
        ['dimension' => 'Adaptabilidad', 'texto' => 'Puedo modificar mi enfoque cuando las circunstancias lo requieren.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 14],
        ['dimension' => 'Adaptabilidad', 'texto' => 'Disfruto aprendiendo nuevas herramientas y metodologías.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 15],
        ['dimension' => 'Adaptabilidad', 'texto' => 'Me adapto fácilmente a diferentes estilos de liderazgo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 16],
        ['dimension' => 'Adaptabilidad', 'texto' => 'Veo los cambios organizacionales como oportunidades más que como amenazas.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 17],
        ['dimension' => 'Adaptabilidad', 'texto' => 'Busco soluciones alternativas cuando la primera opción no funciona.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 18],
        
        // Comunicación Básica
        ['dimension' => 'Comunicación Básica', 'texto' => 'Expreso mis ideas de forma clara y concisa.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 19],
        ['dimension' => 'Comunicación Básica', 'texto' => 'Escucho atentamente lo que otros tienen que decir antes de responder.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 20],
        ['dimension' => 'Comunicación Básica', 'texto' => 'Verifico que he entendido correctamente lo que me comunican.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 21],
        ['dimension' => 'Comunicación Básica', 'texto' => 'Adapto mi forma de comunicar según la persona con quien hablo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 22],
        ['dimension' => 'Comunicación Básica', 'texto' => 'Expreso desacuerdos de manera respetuosa y constructiva.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 23],
        ['dimension' => 'Comunicación Básica', 'texto' => 'Presto atención tanto a las palabras como al lenguaje corporal.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 24],
        
        // Trabajo en Equipo
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Valoro las contribuciones de todos los miembros del equipo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 25],
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Ofrezco ayuda a mis compañeros cuando veo que tienen dificultades.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 26],
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Comparto información útil con mi equipo de trabajo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 27],
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Busco consenso en decisiones que afectan al grupo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 28],
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Cedo en mis posiciones cuando es beneficioso para el equipo.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 29],
        ['dimension' => 'Trabajo en Equipo', 'texto' => 'Reconozco y celebro los logros de mis compañeros.', 'tipo_pregunta' => 'likert', 'tiempo_estimado' => 30, 'orden' => 30],
    ];
    
    // 3. Definir preguntas ECF Situacionales
    echo "<h3>3. Definiendo preguntas situacionales...</h3>";
    
    $preguntasECF_Situacionales = [
        [
            'dimension' => 'Responsabilidad',
            'texto' => 'Su supervisor le asigna una tarea urgente justo cuando estaba por salir. Ya tenía planes importantes personales. ¿Qué haría?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 31,
            'opciones' => [
                ['texto' => 'Me disculpo y explico que tengo un compromiso previo inevitable.', 'valor' => 2],
                ['texto' => 'Cancelo mis planes y me quedo a completar la tarea sin cuestionar.', 'valor' => 1],
                ['texto' => 'Negocio para completar parte de la tarea ahora y terminarla temprano al día siguiente.', 'valor' => 4],
                ['texto' => 'Acepto la tarea pero busco a alguien que pueda ayudarme para terminar antes.', 'valor' => 3]
            ]
        ],
        [
            'dimension' => 'Integridad',
            'texto' => 'Descubre que un compañero está reportando horas extras que no ha trabajado. ¿Qué haría?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 32,
            'opciones' => [
                ['texto' => 'No hago nada, no es mi problema.', 'valor' => 1],
                ['texto' => 'Hablo directamente con el compañero sobre la situación.', 'valor' => 3],
                ['texto' => 'Lo comento con otros compañeros para ver qué piensan al respecto.', 'valor' => 2],
                ['texto' => 'Informo a mi supervisor o al departamento correspondiente.', 'valor' => 4]
            ]
        ],
        [
            'dimension' => 'Adaptabilidad',
            'texto' => 'La empresa implementa un nuevo sistema que cambia completamente su forma de trabajo. ¿Cómo reacciona?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 33,
            'opciones' => [
                ['texto' => 'Me quejo y sigo usando el sistema antiguo tanto como sea posible.', 'valor' => 1],
                ['texto' => 'Acepto el cambio pero espero que alguien me enseñe todo lo necesario.', 'valor' => 2],
                ['texto' => 'Busco capacitación y manuales para aprender por mi cuenta.', 'valor' => 3],
                ['texto' => 'Aprendo rápidamente y además ayudo a mis compañeros a adaptarse.', 'valor' => 4]
            ]
        ],
        [
            'dimension' => 'Comunicación Básica',
            'texto' => 'Durante una reunión, un colega malinterpreta completamente su idea y la critica. ¿Cómo responde?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 34,
            'opciones' => [
                ['texto' => 'Me molesto y defiendo mi posición firmemente.', 'valor' => 2],
                ['texto' => 'No digo nada para evitar conflictos.', 'valor' => 1],
                ['texto' => 'Aclaro mi punto de vista y explico nuevamente la idea con otros términos.', 'valor' => 3],
                ['texto' => 'Pregunto primero qué entendió exactamente para identificar la confusión.', 'valor' => 4]
            ]
        ],
        [
            'dimension' => 'Trabajo en Equipo',
            'texto' => 'En un proyecto grupal, uno de los miembros no está cumpliendo con su parte. ¿Qué hace?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 35,
            'opciones' => [
                ['texto' => 'Lo reporto inmediatamente al supervisor.', 'valor' => 2],
                ['texto' => 'Asumo su trabajo para que el proyecto no se retrase.', 'valor' => 3],
                ['texto' => 'Hablo con él en privado para entender qué está pasando y buscar soluciones.', 'valor' => 4],
                ['texto' => 'Lo expongo durante la reunión de equipo para presionarlo.', 'valor' => 1]
            ]
        ],
        [
            'dimension' => 'Responsabilidad',
            'texto' => 'Comete un error que afecta a un cliente pero nadie más lo ha notado aún. ¿Qué hace?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 36,
            'opciones' => [
                ['texto' => 'No digo nada y espero que no lo descubran.', 'valor' => 1],
                ['texto' => 'Intento corregirlo discretamente sin informar a nadie.', 'valor' => 2],
                ['texto' => 'Lo informo a mi supervisor y propongo una solución.', 'valor' => 4],
                ['texto' => 'Espero a ver si alguien lo nota y entonces explico lo que pasó.', 'valor' => 2]
            ]
        ],
        [
            'dimension' => 'Integridad',
            'texto' => 'Le piden que asista a una reunión en representación de su jefe. Durante la reunión, le preguntan sobre un tema del que no tiene información completa. ¿Qué hace?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 37,
            'opciones' => [
                ['texto' => 'Improviso una respuesta que suene convincente.', 'valor' => 1],
                ['texto' => 'Doy mi opinión personal sobre el tema.', 'valor' => 2],
                ['texto' => 'Reconozco que no tengo esa información y me comprometo a consultarla.', 'valor' => 4],
                ['texto' => 'Evito la pregunta y cambio de tema.', 'valor' => 2]
            ]
        ],
        [
            'dimension' => 'Adaptabilidad',
            'texto' => 'Su equipo debe reducir costos en un 15%. Le piden ideas. ¿Cuál sería su enfoque?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 38,
            'opciones' => [
                ['texto' => 'Sugiero recortar personal, es lo más efectivo.', 'valor' => 1],
                ['texto' => 'Propongo hacer exactamente lo mismo pero intentando gastar menos.', 'valor' => 2],
                ['texto' => 'Analizo procesos para identificar ineficiencias y reducir gastos innecesarios.', 'valor' => 3],
                ['texto' => 'Busco formas innovadoras de trabajar que puedan requerir cambios pero generar ahorros.', 'valor' => 4]
            ]
        ],
        [
            'dimension' => 'Comunicación Básica',
            'texto' => 'Debe explicar un procedimiento técnico complejo a un nuevo empleado sin experiencia. ¿Cómo lo haría?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 39,
            'opciones' => [
                ['texto' => 'Le entrego el manual técnico para que lo estudie por su cuenta.', 'valor' => 1],
                ['texto' => 'Explico todo el procedimiento técnico con todos los detalles de una vez.', 'valor' => 2],
                ['texto' => 'Divido la explicación en pasos sencillos y verifico su comprensión en cada etapa.', 'valor' => 3],
                ['texto' => 'Hago una demostración práctica y luego le pido que lo intente mientras lo guío.', 'valor' => 4]
            ]
        ],
        [
            'dimension' => 'Trabajo en Equipo',
            'texto' => 'Su equipo debe tomar una decisión importante, pero hay opiniones divididas. Usted tiene una preferencia clara. ¿Cómo actúa?',
            'tipo_pregunta' => 'situacional',
            'tiempo_estimado' => 60,
            'orden' => 40,
            'opciones' => [
                ['texto' => 'Insisto en mi punto de vista, ya que estoy convencido de que es el mejor.', 'valor' => 1],
                ['texto' => 'Cedo a la opinión de la mayoría aunque no esté de acuerdo.', 'valor' => 2],
                ['texto' => 'Propongo evaluar pros y contras de cada opción de forma objetiva.', 'valor' => 4],
                ['texto' => 'Sugiero una solución intermedia que incorpore elementos de las diferentes propuestas.', 'valor' => 3]
            ]
        ]
    ];
    
    // 4. Insertar/actualizar preguntas y opciones
    echo "<h3>4. Insertando preguntas Likert...</h3>";
    
    $totalInsertadas = 0;
    $totalOpciones = 0;
    $opcionesLikert = [
        ['texto' => 'Totalmente en desacuerdo', 'valor' => 1, 'orden' => 1],
        ['texto' => 'En desacuerdo', 'valor' => 2, 'orden' => 2],
        ['texto' => 'Ni de acuerdo ni en desacuerdo', 'valor' => 3, 'orden' => 3],
        ['texto' => 'De acuerdo', 'valor' => 4, 'orden' => 4],
        ['texto' => 'Totalmente de acuerdo', 'valor' => 5, 'orden' => 5]
    ];
    
    // Mostrar barra de progreso
    echo "<div class='progress'>";
    echo "<div class='progress-bar'>";
    echo "<span class='progress-bar-fill' style='width: 0%'></span>";
    echo "</div>";
    echo "</div>";
    
    $total_preguntas = count($preguntasECF_Likert);
    $contador = 0;
    
    foreach ($preguntasECF_Likert as $pregunta) {
        $contador++;
        $porcentaje = round(($contador / $total_preguntas) * 100);
        
        // Actualizar barra de progreso cada 5 preguntas
        if ($contador % 5 == 0 || $contador == $total_preguntas) {
            echo "<script>
                document.querySelector('.progress-bar-fill').style.width = '$porcentaje%';
            </script>";
            echo str_pad('', 4096); // Flush buffer
            flush();
        }
        
        $dimensionId = $dimensiones[$pregunta['dimension']];
        $texto = $db->escape($pregunta['texto']);
        $tipo_pregunta = $db->escape($pregunta['tipo_pregunta']);
        $tiempo_estimado = (int)$pregunta['tiempo_estimado'];
        $orden = (int)$pregunta['orden'];
        
        // Primero, verificar si la pregunta ya existe
        $sql = "SELECT id FROM preguntas 
                WHERE prueba_id = $prueba_id AND texto = '$texto'";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // Actualizar pregunta existente
            $preguntaId = $result->fetch_assoc()['id'];
            
            $sql = "UPDATE preguntas 
                    SET dimension_id = $dimensionId,
                        tipo_pregunta = '$tipo_pregunta',
                        tiempo_estimado = $tiempo_estimado,
                        orden = $orden,
                        activa = 1
                    WHERE id = $preguntaId";
                    
            if ($db->query($sql)) {
                echo "<div class='success'>✅ Pregunta actualizada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
            } else {
                echo "<div class='error'>❌ Error al actualizar pregunta: " . $db->getConnection()->error . "</div>";
                $GLOBALS['counters']['errores']++;
                continue;
            }
        } else {
            // Insertar nueva pregunta
            $sql = "INSERT INTO preguntas 
                    (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria, activa) 
                    VALUES 
                    ($prueba_id, '$texto', $dimensionId, '$tipo_pregunta', $tiempo_estimado, $orden, 1, 1)";
                    
            if ($db->query($sql)) {
                $preguntaId = $db->getConnection()->insert_id;
                $totalInsertadas++;
                echo "<div class='success'>✅ Nueva pregunta creada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
            } else {
                echo "<div class='error'>❌ Error al crear pregunta: " . $db->getConnection()->error . "</div>";
                $GLOBALS['counters']['errores']++;
                continue;
            }
        }
        
        // Insertar/actualizar opciones Likert
        $sql = "SELECT COUNT(*) as total FROM opciones_respuesta WHERE pregunta_id = $preguntaId";
        $result = $db->query($sql);
        $tiene_opciones = ($result && $result->fetch_assoc()['total'] > 0);
        
        if (!$tiene_opciones) {
            foreach ($opcionesLikert as $opcion) {
                $opcion_texto = $db->escape($opcion['texto']);
                $opcion_valor = (int)$opcion['valor'];
                $opcion_orden = (int)$opcion['orden'];
                
                $sql = "INSERT INTO opciones_respuesta 
                        (pregunta_id, texto, valor, dimension_id, orden) 
                        VALUES 
                        ($preguntaId, '$opcion_texto', $opcion_valor, $dimensionId, $opcion_orden)";
                        
                if ($db->query($sql)) {
                    $totalOpciones++;
                }
            }
        }
    }
    
    // Insertar preguntas situacionales
    echo "<h3>5. Insertando preguntas situacionales...</h3>";
    
    foreach ($preguntasECF_Situacionales as $pregunta) {
        $dimensionId = $dimensiones[$pregunta['dimension']];
        $texto = $db->escape($pregunta['texto']);
        $tipo_pregunta = $db->escape($pregunta['tipo_pregunta']);
        $tiempo_estimado = (int)$pregunta['tiempo_estimado'];
        $orden = (int)$pregunta['orden'];
        
        // Verificar si la pregunta ya existe
        $sql = "SELECT id FROM preguntas 
                WHERE prueba_id = $prueba_id AND texto = '$texto'";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // Actualizar pregunta existente
            $preguntaId = $result->fetch_assoc()['id'];
            
            $sql = "UPDATE preguntas 
                    SET dimension_id = $dimensionId,
                        tipo_pregunta = '$tipo_pregunta',
                        tiempo_estimado = $tiempo_estimado,
                        orden = $orden,
                        activa = 1
                    WHERE id = $preguntaId";
                    
            if ($db->query($sql)) {
                echo "<div class='success'>✅ Pregunta situacional actualizada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
            } else {
                echo "<div class='error'>❌ Error al actualizar pregunta situacional: " . $db->getConnection()->error . "</div>";
                $GLOBALS['counters']['errores']++;
                continue;
            }
        } else {
            // Insertar nueva pregunta
            $sql = "INSERT INTO preguntas 
                    (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria, activa) 
                    VALUES 
                    ($prueba_id, '$texto', $dimensionId, '$tipo_pregunta', $tiempo_estimado, $orden, 1, 1)";
                    
            if ($db->query($sql)) {
                $preguntaId = $db->getConnection()->insert_id;
                $totalInsertadas++;
                echo "<div class='success'>✅ Nueva pregunta situacional creada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
            } else {
                echo "<div class='error'>❌ Error al crear pregunta situacional: " . $db->getConnection()->error . "</div>";
                $GLOBALS['counters']['errores']++;
                continue;
            }
        }
        
        // Insertar/actualizar opciones situacionales
        $sql = "SELECT COUNT(*) as total FROM opciones_respuesta WHERE pregunta_id = $preguntaId";
        $result = $db->query($sql);
        $tiene_opciones = ($result && $result->fetch_assoc()['total'] > 0);
        
        if (!$tiene_opciones && isset($pregunta['opciones']) && is_array($pregunta['opciones'])) {
            foreach ($pregunta['opciones'] as $index => $opcion) {
                $opcion_texto = $db->escape($opcion['texto']);
                $opcion_valor = (int)$opcion['valor'];
                $opcion_orden = $index + 1;
                
                $sql = "INSERT INTO opciones_respuesta 
                        (pregunta_id, texto, valor, dimension_id, orden) 
                        VALUES 
                        ($preguntaId, '$opcion_texto', $opcion_valor, $dimensionId, $opcion_orden)";
                        
                if ($db->query($sql)) {
                    $totalOpciones++;
                }
            }
        }
    }
    
    // Mostrar resumen
    echo "<h3>6. Resumen del proceso</h3>";
    echo "<ul>";
    echo "<li><strong>Preguntas insertadas:</strong> $totalInsertadas</li>";
    echo "<li><strong>Opciones creadas:</strong> $totalOpciones</li>";
    echo "</ul>";
    
    $GLOBALS['counters']['preguntas_creadas'] += $totalInsertadas;
    $GLOBALS['counters']['opciones_creadas'] += $totalOpciones;
    
    echo "<p><a href='fix_ecf_questions.php?accion=analizar' class='button'>Ver estado actualizado</a></p>";
}

// Manejar acciones según parámetro
switch ($accion) {
    case 'analizar':
        analizarPreguntasECF($db);
        break;
        
    case 'activar':
        activarPreguntasECF($db);
        break;
        
    case 'recargar':
        recargarPreguntasECF($db);
        break;
        
    default:
        echo "<div class='intro'>";
        echo "<h2>¿Qué hace esta herramienta?</h2>";
        echo "<p>Esta herramienta le permite diagnosticar y corregir problemas con las preguntas de la Evaluación de Competencias Fundamentales (ECF). Puede:</p>";
        echo "<ul>";
        echo "<li><strong>Analizar:</strong> Ver el estado actual de las preguntas, identificar problemas</li>";
        echo "<li><strong>Activar:</strong> Marcar todas las preguntas como activas para que sean visibles en las evaluaciones</li>";
        echo "<li><strong>Recargar:</strong> Regenerar todas las preguntas y opciones de respuesta, asegurando que estén completas</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<p><a href='fix_ecf_questions.php?accion=analizar' class='button'>Comenzar análisis</a></p>";
        break;
}

// Mostrar resumen de acciones
if ($counters['preguntas_activadas'] > 0 || $counters['preguntas_creadas'] > 0 || $counters['opciones_creadas'] > 0 || $counters['errores'] > 0) {
    echo "<h2>Resumen de acciones</h2>";
    echo "<ul>";
    if ($counters['preguntas_activadas'] > 0) echo "<li><strong>Preguntas activadas:</strong> {$counters['preguntas_activadas']}</li>";
    if ($counters['preguntas_creadas'] > 0) echo "<li><strong>Preguntas creadas/actualizadas:</strong> {$counters['preguntas_creadas']}</li>";
    if ($counters['opciones_creadas'] > 0) echo "<li><strong>Opciones de respuesta creadas:</strong> {$counters['opciones_creadas']}</li>";
    if ($counters['errores'] > 0) echo "<li><strong class='error'>Errores encontrados:</strong> {$counters['errores']}</li>";
    echo "</ul>";
}
?>