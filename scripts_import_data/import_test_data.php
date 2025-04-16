<?php
/**
 * Script para importar datos de pruebas psicométricas
 * import_test_data.php
 * 
 * Este script importa todos los datos necesarios para el sistema de evaluaciones psicométricas:
 * - Categorías de pruebas
 * - Pruebas
 * - Dimensiones
 * - Niveles de interpretación
 * - Índices compuestos
 * - Preguntas y opciones de respuesta para cada prueba
 * 
 * INSTRUCCIONES:
 * 1. Coloque este archivo en la raíz del proyecto
 * 2. Ejecute el script desde el navegador o línea de comandos
 * 3. Verifique los mensajes de confirmación
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
    'categorias' => 0,
    'pruebas' => 0,
    'dimensiones' => 0,
    'niveles' => 0,
    'indices' => 0,
    'preguntas' => 0,
    'opciones' => 0
];

echo "<h1>Importación de Datos para Sistema de Evaluaciones Psicométricas</h1>";
echo "<p>Iniciando proceso de importación...</p>";

// 1. Importar categorías de pruebas
echo "<h2>1. Importando categorías de pruebas</h2>";

$categorias = [
    ['nombre' => 'Competencias Fundamentales', 'descripcion' => 'Evalúa responsabilidad, integridad, adaptabilidad, comunicación y trabajo en equipo', 'icono' => 'fa-user-check', 'orden' => 1],
    ['nombre' => 'Aptitudes Cognitivas', 'descripcion' => 'Evalúa razonamiento verbal, numérico, lógico y atención al detalle', 'icono' => 'fa-brain', 'orden' => 2],
    ['nombre' => 'Personalidad Laboral', 'descripcion' => 'Evalúa dimensiones bipolares de personalidad y su impacto en entornos laborales', 'icono' => 'fa-id-card', 'orden' => 3],
    ['nombre' => 'Motivaciones y Valores', 'descripcion' => 'Identifica qué aspectos del trabajo resultan más importantes y motivadores', 'icono' => 'fa-chart-line', 'orden' => 4]
];

foreach ($categorias as $categoria) {
    $nombre = $db->escape($categoria['nombre']);
    $descripcion = $db->escape($categoria['descripcion']);
    $icono = $db->escape($categoria['icono']);
    $orden = (int)$categoria['orden'];
    
    $sql = "INSERT INTO pruebas_categorias (nombre, descripcion, icono, orden) 
            VALUES ('$nombre', '$descripcion', '$icono', $orden)
            ON DUPLICATE KEY UPDATE 
            descripcion = VALUES(descripcion), 
            icono = VALUES(icono), 
            orden = VALUES(orden)";
            
    if ($db->query($sql)) {
        $counters['categorias']++;
        echo "<div style='color:green'>✓ Categoría importada: {$categoria['nombre']}</div>";
    } else {
        echo "<div style='color:red'>✗ Error al importar categoría {$categoria['nombre']}: " . $db->getConnection()->error . "</div>";
    }
}

// Obtener IDs de categorías
$sql = "SELECT id, nombre FROM pruebas_categorias";
$result = $db->query($sql);
$categoriaIds = [];
while ($row = $result->fetch_assoc()) {
    $categoriaIds[$row['nombre']] = $row['id'];
}

// 2. Importar pruebas
echo "<h2>2. Importando pruebas</h2>";

$pruebas = [
    [
        'titulo' => 'Evaluación de Competencias Fundamentales (ECF)',
        'descripcion' => 'Evalúa cinco competencias clave: Responsabilidad, Integridad, Adaptabilidad, Comunicación y Trabajo en Equipo a través de 40 preguntas (30 Likert + 10 situacionales).',
        'instrucciones' => 'Lea atentamente cada pregunta y elija la opción que mejor refleje su forma habitual de actuar en entornos laborales. No hay respuestas correctas o incorrectas, sea honesto en sus respuestas para obtener un perfil más preciso.',
        'tiempo_estimado' => 15,
        'categoria_id' => $categoriaIds['Competencias Fundamentales'],
        'estado' => 'activa',
        'asignacion_automatica' => 1
    ],
    [
        'titulo' => 'Test de Aptitudes Cognitivas (TAC)',
        'descripcion' => 'Mide habilidades mentales en cuatro áreas: Razonamiento Verbal, Razonamiento Numérico, Razonamiento Lógico y Atención al Detalle mediante 40 preguntas de opción múltiple.',
        'instrucciones' => 'Cada pregunta tiene una única respuesta correcta. Intente resolver todas las preguntas, pero no se detenga demasiado tiempo en una sola. Si no está seguro, elija la que considere más probable.',
        'tiempo_estimado' => 25,
        'categoria_id' => $categoriaIds['Aptitudes Cognitivas'],
        'estado' => 'activa',
        'asignacion_automatica' => 1
    ],
    [
        'titulo' => 'Inventario de Personalidad Laboral (IPL)',
        'descripcion' => 'Evalúa cinco dimensiones bipolares de personalidad: Extroversión-Introversión, Estabilidad-Reactividad, Apertura-Convencionalidad, Cooperación-Independencia y Meticulosidad-Flexibilidad.',
        'instrucciones' => 'Indique su nivel de acuerdo o desacuerdo con cada afirmación. No piense demasiado en cada respuesta; su primera impresión suele ser la más representativa de su forma de ser.',
        'tiempo_estimado' => 20,
        'categoria_id' => $categoriaIds['Personalidad Laboral'],
        'estado' => 'activa',
        'asignacion_automatica' => 1
    ],
    [
        'titulo' => 'Cuestionario de Motivaciones y Valores (CMV)',
        'descripcion' => 'Identifica qué aspectos del trabajo son más importantes para una persona entre 8 motivaciones: Logro, Poder, Afiliación, Seguridad, Autonomía, Servicio, Reto y Equilibrio.',
        'instrucciones' => 'Para cada par de afirmaciones, elija aquella que refleje mejor lo que usted valora o prefiere en un entorno laboral, incluso si ambas opciones le parecen deseables.',
        'tiempo_estimado' => 15,
        'categoria_id' => $categoriaIds['Motivaciones y Valores'],
        'estado' => 'activa',
        'asignacion_automatica' => 1
    ]
];

foreach ($pruebas as $prueba) {
    $titulo = $db->escape($prueba['titulo']);
    $descripcion = $db->escape($prueba['descripcion']);
    $instrucciones = $db->escape($prueba['instrucciones']);
    $tiempo_estimado = (int)$prueba['tiempo_estimado'];
    $categoria_id = (int)$prueba['categoria_id'];
    $estado = $db->escape($prueba['estado']);
    $asignacion_automatica = (int)$prueba['asignacion_automatica'];
    
    $sql = "INSERT INTO pruebas (titulo, descripcion, instrucciones, tiempo_estimado, categoria_id, estado, asignacion_automatica)
            VALUES ('$titulo', '$descripcion', '$instrucciones', $tiempo_estimado, $categoria_id, '$estado', $asignacion_automatica)
            ON DUPLICATE KEY UPDATE
            descripcion = VALUES(descripcion),
            instrucciones = VALUES(instrucciones),
            tiempo_estimado = VALUES(tiempo_estimado),
            categoria_id = VALUES(categoria_id),
            estado = VALUES(estado),
            asignacion_automatica = VALUES(asignacion_automatica)";
    
    if ($db->query($sql)) {
        $counters['pruebas']++;
        echo "<div style='color:green'>✓ Prueba importada: {$prueba['titulo']}</div>";
    } else {
        echo "<div style='color:red'>✗ Error al importar prueba {$prueba['titulo']}: " . $db->getConnection()->error . "</div>";
    }
}

// Obtener IDs de pruebas
$sql = "SELECT id, titulo FROM pruebas";
$result = $db->query($sql);
$pruebaIds = [];
while ($row = $result->fetch_assoc()) {
    $pruebaIds[$row['titulo']] = $row['id'];
}

// 3. Importar dimensiones
echo "<h2>3. Importando dimensiones</h2>";

$dimensiones = [
    // ECF - Evaluación de Competencias Fundamentales
    ['nombre' => 'Responsabilidad', 'descripcion' => 'Grado en que la persona asume compromisos y los cumple de manera consistente', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Integridad', 'descripcion' => 'Nivel de adhesión a valores éticos y principios en el entorno laboral', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Adaptabilidad', 'descripcion' => 'Capacidad para ajustarse a cambios y nuevas circunstancias en el entorno laboral', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Comunicación Básica', 'descripcion' => 'Habilidad para transmitir ideas y comprender mensajes de manera efectiva', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Trabajo en Equipo', 'descripcion' => 'Capacidad para colaborar con otros hacia un objetivo común', 'tipo' => 'primaria', 'bipolar' => 0],
    
    // TAC - Test de Aptitudes Cognitivas
    ['nombre' => 'Razonamiento Verbal', 'descripcion' => 'Capacidad para comprender y analizar información presentada en forma verbal', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Razonamiento Numérico', 'descripcion' => 'Habilidad para trabajar con conceptos numéricos y resolver problemas matemáticos', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Razonamiento Lógico', 'descripcion' => 'Capacidad para identificar patrones y relaciones lógicas entre elementos', 'tipo' => 'primaria', 'bipolar' => 0],
    ['nombre' => 'Atención al Detalle', 'descripcion' => 'Precisión en la observación y capacidad para detectar errores o inconsistencias', 'tipo' => 'primaria', 'bipolar' => 0],
    
    // IPL - Inventario de Personalidad Laboral
    ['nombre' => 'Extroversión vs. Introversión', 'descripcion' => 'Orientación hacia el mundo exterior o hacia el mundo interior', 'tipo' => 'primaria', 'bipolar' => 1, 'polo_positivo' => 'Extroversión', 'polo_negativo' => 'Introversión'],
    ['nombre' => 'Estabilidad vs. Reactividad Emocional', 'descripcion' => 'Tendencia a mantener equilibrio emocional o a reaccionar intensamente', 'tipo' => 'primaria', 'bipolar' => 1, 'polo_positivo' => 'Estabilidad', 'polo_negativo' => 'Reactividad Emocional'],
    ['nombre' => 'Apertura vs. Convencionalidad', 'descripcion' => 'Inclinación hacia nuevas experiencias o hacia lo tradicional y familiar', 'tipo' => 'primaria', 'bipolar' => 1, 'polo_positivo' => 'Apertura', 'polo_negativo' => 'Convencionalidad'],
    ['nombre' => 'Cooperación vs. Independencia', 'descripcion' => 'Tendencia a priorizar armonía grupal o autonomía individual', 'tipo' => 'primaria', 'bipolar' => 1, 'polo_positivo' => 'Cooperación', 'polo_negativo' => 'Independencia'],
    ['nombre' => 'Meticulosidad vs. Flexibilidad', 'descripcion' => 'Orientación hacia la estructura y orden o hacia la adaptabilidad', 'tipo' => 'primaria', 'bipolar' => 1, 'polo_positivo' => 'Meticulosidad', 'polo_negativo' => 'Flexibilidad'],
    
    // CMV - Cuestionario de Motivaciones y Valores
    ['nombre' => 'Motivación por Logro', 'descripcion' => 'Impulso por alcanzar metas desafiantes y superar estándares de excelencia', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Poder', 'descripcion' => 'Deseo de influir en otros y tener impacto en la organización', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Afiliación', 'descripcion' => 'Interés por establecer y mantener relaciones interpersonales positivas', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Seguridad', 'descripcion' => 'Preferencia por entornos estables, predecibles y seguros', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Autonomía', 'descripcion' => 'Deseo de independencia y libertad para tomar decisiones propias', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Servicio', 'descripcion' => 'Impulso por contribuir positivamente a la vida de otros', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Reto', 'descripcion' => 'Deseo de enfrentar y superar desafíos complejos', 'tipo' => 'motivacion', 'bipolar' => 0],
    ['nombre' => 'Motivación por Equilibrio', 'descripcion' => 'Valoración del balance entre vida profesional y personal', 'tipo' => 'motivacion', 'bipolar' => 0],
];

foreach ($dimensiones as $dimension) {
    $nombre = $db->escape($dimension['nombre']);
    $descripcion = $db->escape($dimension['descripcion']);
    $tipo = $db->escape($dimension['tipo']);
    $bipolar = (int)$dimension['bipolar'];
    $poloPositivo = isset($dimension['polo_positivo']) ? "'" . $db->escape($dimension['polo_positivo']) . "'" : "NULL";
    $poloNegativo = isset($dimension['polo_negativo']) ? "'" . $db->escape($dimension['polo_negativo']) . "'" : "NULL";
    
    $sql = "INSERT INTO dimensiones (nombre, descripcion, tipo, bipolar, polo_positivo, polo_negativo)
            VALUES ('$nombre', '$descripcion', '$tipo', $bipolar, $poloPositivo, $poloNegativo)
            ON DUPLICATE KEY UPDATE
            descripcion = VALUES(descripcion),
            tipo = VALUES(tipo),
            bipolar = VALUES(bipolar),
            polo_positivo = VALUES(polo_positivo),
            polo_negativo = VALUES(polo_negativo)";
    
    if ($db->query($sql)) {
        $counters['dimensiones']++;
        echo "<div style='color:green'>✓ Dimensión importada: {$dimension['nombre']}</div>";
    } else {
        echo "<div style='color:red'>✗ Error al importar dimensión {$dimension['nombre']}: " . $db->getConnection()->error . "</div>";
    }
}

// Obtener IDs de dimensiones
$sql = "SELECT id, nombre FROM dimensiones";
$result = $db->query($sql);
$dimensionIds = [];
while ($row = $result->fetch_assoc()) {
    $dimensionIds[$row['nombre']] = $row['id'];
}

// 4. Asociar dimensiones a pruebas
echo "<h2>4. Asociando dimensiones a pruebas</h2>";

$pruebasDimensiones = [
    ['prueba' => 'Evaluación de Competencias Fundamentales (ECF)', 'dimensiones' => ['Responsabilidad', 'Integridad', 'Adaptabilidad', 'Comunicación Básica', 'Trabajo en Equipo']],
    ['prueba' => 'Test de Aptitudes Cognitivas (TAC)', 'dimensiones' => ['Razonamiento Verbal', 'Razonamiento Numérico', 'Razonamiento Lógico', 'Atención al Detalle']],
    ['prueba' => 'Inventario de Personalidad Laboral (IPL)', 'dimensiones' => ['Extroversión vs. Introversión', 'Estabilidad vs. Reactividad Emocional', 'Apertura vs. Convencionalidad', 'Cooperación vs. Independencia', 'Meticulosidad vs. Flexibilidad']],
    ['prueba' => 'Cuestionario de Motivaciones y Valores (CMV)', 'dimensiones' => ['Motivación por Logro', 'Motivación por Poder', 'Motivación por Afiliación', 'Motivación por Seguridad', 'Motivación por Autonomía', 'Motivación por Servicio', 'Motivación por Reto', 'Motivación por Equilibrio']]
];

foreach ($pruebasDimensiones as $pd) {
    if (!isset($pruebaIds[$pd['prueba']])) {
        echo "<div style='color:orange'>⚠ Prueba no encontrada: {$pd['prueba']}</div>";
        continue;
    }
    
    $pruebaId = $pruebaIds[$pd['prueba']];
    
    foreach ($pd['dimensiones'] as $dimension) {
        if (!isset($dimensionIds[$dimension])) {
            echo "<div style='color:orange'>⚠ Dimensión no encontrada: {$dimension}</div>";
            continue;
        }
        
        $dimensionId = $dimensionIds[$dimension];
        $ponderacion = 1.0; // Ponderación igual para todas por defecto
        
        $sql = "INSERT IGNORE INTO pruebas_dimensiones (prueba_id, dimension_id, ponderacion)
                VALUES (?, ?, ?)";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("iid", $pruebaId, $dimensionId, $ponderacion);
        
        if ($stmt->execute()) {
            echo "<div style='color:green'>✓ Asociación: {$pd['prueba']} - {$dimension}</div>";
        } else {
            echo "<div style='color:red'>✗ Error al asociar {$dimension} con {$pd['prueba']}: " . $stmt->error . "</div>";
        }
    }
}

// 5. Importar niveles de interpretación
echo "<h2>5. Importando niveles de interpretación</h2>";

$niveles = [
    ['nombre' => 'Excepcional', 'rango_min' => 90.00, 'rango_max' => 100.00, 'color' => '#006400', 'orden' => 1],
    ['nombre' => 'Sobresaliente', 'rango_min' => 80.00, 'rango_max' => 89.99, 'color' => '#008000', 'orden' => 2],
    ['nombre' => 'Notable', 'rango_min' => 70.00, 'rango_max' => 79.99, 'color' => '#90EE90', 'orden' => 3],
    ['nombre' => 'Adecuado', 'rango_min' => 60.00, 'rango_max' => 69.99, 'color' => '#FFFF00', 'orden' => 4],
    ['nombre' => 'Moderado', 'rango_min' => 50.00, 'rango_max' => 59.99, 'color' => '#FFFFE0', 'orden' => 5],
    ['nombre' => 'En desarrollo', 'rango_min' => 35.00, 'rango_max' => 49.99, 'color' => '#FFA500', 'orden' => 6],
    ['nombre' => 'Incipiente', 'rango_min' => 0.00, 'rango_max' => 34.99, 'color' => '#FF0000', 'orden' => 7]
];

foreach ($niveles as $nivel) {
    $nombre = $db->escape($nivel['nombre']);
    $color = $db->escape($nivel['color']);
    $rango_min = (float)$nivel['rango_min'];
    $rango_max = (float)$nivel['rango_max'];
    $orden = (int)$nivel['orden'];
    
    $sql = "INSERT INTO niveles_interpretacion (nombre, rango_min, rango_max, color, orden)
            VALUES ('$nombre', $rango_min, $rango_max, '$color', $orden)
            ON DUPLICATE KEY UPDATE
            rango_min = VALUES(rango_min),
            rango_max = VALUES(rango_max),
            color = VALUES(color),
            orden = VALUES(orden)";
    
    if ($db->query($sql)) {
        $counters['niveles']++;
        echo "<div style='color:green'>✓ Nivel importado: {$nivel['nombre']}</div>";
    } else {
        echo "<div style='color:red'>✗ Error al importar nivel {$nivel['nombre']}: " . $db->getConnection()->error . "</div>";
    }
}

// Obtener IDs de niveles
$sql = "SELECT id, nombre FROM niveles_interpretacion";
$result = $db->query($sql);
$nivelIds = [];
while ($row = $result->fetch_assoc()) {
    $nivelIds[$row['nombre']] = $row['id'];
}

// 6. Importar índices compuestos
echo "<h2>6. Importando índices compuestos</h2>";

$indices = [
    ['nombre' => 'Capacidad Analítica', 'descripcion' => 'Mide la habilidad para analizar información, detectar patrones y resolver problemas complejos.'],
    ['nombre' => 'Habilidad Comunicativa', 'descripcion' => 'Evalúa la capacidad para expresar ideas con claridad y persuasión en diferentes contextos.'],
    ['nombre' => 'Colaboración', 'descripcion' => 'Mide la disposición y capacidad para trabajar eficazmente en equipo.'],
    ['nombre' => 'Liderazgo Potencial', 'descripcion' => 'Evalúa la capacidad para influir, dirigir y motivar a otros.'],
    ['nombre' => 'Autonomía', 'descripcion' => 'Mide la capacidad para trabajar independientemente con mínima supervisión.'],
    ['nombre' => 'Innovación', 'descripcion' => 'Evalúa la tendencia a generar ideas nuevas y enfoques originales.'],
    ['nombre' => 'Orientación al Cliente', 'descripcion' => 'Mide la disposición y habilidad para entender y satisfacer necesidades de clientes.'],
    ['nombre' => 'Meticulosidad', 'descripcion' => 'Evalúa la atención al detalle y precisión en el trabajo.'],
    ['nombre' => 'Resiliencia', 'descripcion' => 'Mide la capacidad para recuperarse de dificultades y adaptarse a cambios.']
];

foreach ($indices as $indice) {
    $nombre = $db->escape($indice['nombre']);
    $descripcion = $db->escape($indice['descripcion']);
    
    $sql = "INSERT INTO indices_compuestos (nombre, descripcion)
            VALUES ('$nombre', '$descripcion')
            ON DUPLICATE KEY UPDATE
            descripcion = VALUES(descripcion)";
    
    if ($db->query($sql)) {
        $counters['indices']++;
        echo "<div style='color:green'>✓ Índice compuesto importado: {$indice['nombre']}</div>";
    } else {
        echo "<div style='color:red'>✗ Error al importar índice {$indice['nombre']}: " . $db->getConnection()->error . "</div>";
    }
}

// Obtener IDs de índices
$sql = "SELECT id, nombre FROM indices_compuestos";
$result = $db->query($sql);
$indiceIds = [];
while ($row = $result->fetch_assoc()) {
    $indiceIds[$row['nombre']] = $row['id'];
}

// 7. Configurar componentes para los índices
echo "<h2>7. Configurando componentes para índices compuestos</h2>";

$indicesComponentes = [
    [
        'indice' => 'Capacidad Analítica', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Razonamiento Lógico', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Razonamiento Numérico', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Atención al Detalle', 'ponderacion' => 0.2],
            ['tipo' => 'dimension', 'nombre' => 'Meticulosidad vs. Flexibilidad', 'ponderacion' => 0.1]
        ]
    ],
    [
        'indice' => 'Habilidad Comunicativa', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Razonamiento Verbal', 'ponderacion' => 0.35],
            ['tipo' => 'dimension', 'nombre' => 'Comunicación Básica', 'ponderacion' => 0.35],
            ['tipo' => 'dimension', 'nombre' => 'Extroversión vs. Introversión', 'ponderacion' => 0.2],
            ['tipo' => 'dimension', 'nombre' => 'Apertura vs. Convencionalidad', 'ponderacion' => 0.1]
        ]
    ],
    [
        'indice' => 'Colaboración', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Trabajo en Equipo', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Cooperación vs. Independencia', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Adaptabilidad', 'ponderacion' => 0.2],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Afiliación', 'ponderacion' => 0.1]
        ]
    ],
    [
        'indice' => 'Liderazgo Potencial', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Responsabilidad', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Extroversión vs. Introversión', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Estabilidad vs. Reactividad Emocional', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Poder', 'ponderacion' => 0.25]
        ]
    ],
    [
        'indice' => 'Autonomía', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Meticulosidad vs. Flexibilidad', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Responsabilidad', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Cooperación vs. Independencia', 'ponderacion' => 0.25],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Autonomía', 'ponderacion' => 0.25]
        ]
    ],
    [
        'indice' => 'Innovación', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Apertura vs. Convencionalidad', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Adaptabilidad', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Razonamiento Lógico', 'ponderacion' => 0.2],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Reto', 'ponderacion' => 0.1]
        ]
    ],
    [
        'indice' => 'Orientación al Cliente', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Comunicación Básica', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Cooperación vs. Independencia', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Estabilidad vs. Reactividad Emocional', 'ponderacion' => 0.2],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Servicio', 'ponderacion' => 0.2]
        ]
    ],
    [
        'indice' => 'Meticulosidad', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Atención al Detalle', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Meticulosidad vs. Flexibilidad', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Responsabilidad', 'ponderacion' => 0.2]
        ]
    ],
    [
        'indice' => 'Resiliencia', 
        'componentes' => [
            ['tipo' => 'dimension', 'nombre' => 'Estabilidad vs. Reactividad Emocional', 'ponderacion' => 0.4],
            ['tipo' => 'dimension', 'nombre' => 'Adaptabilidad', 'ponderacion' => 0.3],
            ['tipo' => 'dimension', 'nombre' => 'Motivación por Reto', 'ponderacion' => 0.3]
        ]
    ]
];

foreach ($indicesComponentes as $ic) {
    if (!isset($indiceIds[$ic['indice']])) {
        echo "<div style='color:orange'>⚠ Índice no encontrado: {$ic['indice']}</div>";
        continue;
    }
    
    $indiceId = $indiceIds[$ic['indice']];
    
    // Eliminar componentes existentes
    $sql = "DELETE FROM indices_componentes WHERE indice_id = $indiceId";
    $db->query($sql);
    
    foreach ($ic['componentes'] as $componente) {
        $origenTipo = $db->escape($componente['tipo']);
        $ponderacion = (float)$componente['ponderacion'];
        $nombreOrigen = $componente['nombre'];
        
        $origenId = null;
        if ($origenTipo == 'dimension' && isset($dimensionIds[$nombreOrigen])) {
            $origenId = $dimensionIds[$nombreOrigen];
        } elseif ($origenTipo == 'indice' && isset($indiceIds[$nombreOrigen])) {
            $origenId = $indiceIds[$nombreOrigen];
        }
        
        if (!$origenId) {
            echo "<div style='color:orange'>⚠ Componente no encontrado: {$nombreOrigen}</div>";
            continue;
        }
        
        $sql = "INSERT INTO indices_componentes (indice_id, origen_tipo, origen_id, ponderacion)
                VALUES ($indiceId, '$origenTipo', $origenId, $ponderacion)";
        
        if ($db->query($sql)) {
            echo "<div style='color:green'>✓ Componente añadido a {$ic['indice']}: {$nombreOrigen} ({$ponderacion})</div>";
        } else {
            echo "<div style='color:red'>✗ Error al añadir componente {$nombreOrigen} a {$ic['indice']}: " . $db->getConnection()->error . "</div>";
        }
    }
}

// 8. Importar preguntas para Evaluación de Competencias Fundamentales (ECF)
echo "<h2>8. Importando preguntas para ECF</h2>";

$ecfId = $pruebaIds['Evaluación de Competencias Fundamentales (ECF)'];

// 8.1 Preguntas Likert
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

// Importar preguntas Likert para ECF
foreach ($preguntasECF_Likert as $pregunta) {
    $dimensionId = $dimensionIds[$pregunta['dimension']];
    
    $sql = "INSERT INTO preguntas (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            texto = VALUES(texto),
            dimension_id = VALUES(dimension_id),
            tipo_pregunta = VALUES(tipo_pregunta),
            tiempo_estimado = VALUES(tiempo_estimado),
            orden = VALUES(orden)";
            
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("isisii", 
        $ecfId, 
        $pregunta['texto'], 
        $dimensionId, 
        $pregunta['tipo_pregunta'], 
        $pregunta['tiempo_estimado'], 
        $pregunta['orden']
    );
    
    if ($stmt->execute()) {
        $preguntaId = $stmt->insert_id;
        $counters['preguntas']++;
        echo "<div style='color:green'>✓ Pregunta ECF importada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
        
        // Crear opciones para preguntas Likert
        $opciones = [
            ['texto' => 'Totalmente en desacuerdo', 'valor' => 1, 'orden' => 1],
            ['texto' => 'En desacuerdo', 'valor' => 2, 'orden' => 2],
            ['texto' => 'Ni de acuerdo ni en desacuerdo', 'valor' => 3, 'orden' => 3],
            ['texto' => 'De acuerdo', 'valor' => 4, 'orden' => 4],
            ['texto' => 'Totalmente de acuerdo', 'valor' => 5, 'orden' => 5]
        ];
        
        foreach ($opciones as $opcion) {
            $sql = "INSERT INTO opciones_respuesta (pregunta_id, texto, valor, dimension_id, orden)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    texto = VALUES(texto),
                    valor = VALUES(valor),
                    dimension_id = VALUES(dimension_id),
                    orden = VALUES(orden)";
                    
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param("isiii", 
                $preguntaId, 
                $opcion['texto'], 
                $opcion['valor'], 
                $dimensionId, 
                $opcion['orden']
            );
            
            if ($stmt->execute()) {
                $counters['opciones']++;
            } else {
                echo "<div style='color:red'>✗ Error al importar opción para pregunta ECF: " . $stmt->error . "</div>";
            }
        }
    } else {
        echo "<div style='color:red'>✗ Error al importar pregunta ECF: " . $stmt->error . "</div>";
    }
}

// 8.2 Preguntas Situacionales para ECF
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

// Importar preguntas situacionales para ECF
foreach ($preguntasECF_Situacionales as $pregunta) {
    $dimensionId = $dimensionIds[$pregunta['dimension']];
    $texto = $db->escape($pregunta['texto']);
    $tipo_pregunta = $db->escape($pregunta['tipo_pregunta']);
    $tiempo_estimado = (int)$pregunta['tiempo_estimado'];
    $orden = (int)$pregunta['orden'];
    
    $sql = "INSERT INTO preguntas (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria)
            VALUES ($ecfId, '$texto', $dimensionId, '$tipo_pregunta', $tiempo_estimado, $orden, 1)
            ON DUPLICATE KEY UPDATE
            texto = VALUES(texto),
            dimension_id = VALUES(dimension_id),
            tipo_pregunta = VALUES(tipo_pregunta),
            tiempo_estimado = VALUES(tiempo_estimado),
            orden = VALUES(orden)";
    
    if ($db->query($sql)) {
        $preguntaId = $db->lastInsertId();
        $counters['preguntas']++;
        echo "<div style='color:green'>✓ Pregunta situacional ECF importada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
        
        // Crear opciones para preguntas situacionales
        foreach ($pregunta['opciones'] as $index => $opcion) {
            $opcion_texto = $db->escape($opcion['texto']);
            $valor = (int)$opcion['valor'];
            $orden = $index + 1;
            
            $sql = "INSERT INTO opciones_respuesta (pregunta_id, texto, valor, dimension_id, orden)
                    VALUES ($preguntaId, '$opcion_texto', $valor, $dimensionId, $orden)
                    ON DUPLICATE KEY UPDATE
                    texto = VALUES(texto),
                    valor = VALUES(valor),
                    dimension_id = VALUES(dimension_id),
                    orden = VALUES(orden)";
            
            if ($db->query($sql)) {
                $counters['opciones']++;
            } else {
                echo "<div style='color:red'>✗ Error al importar opción para pregunta situacional ECF: " . $db->getConnection()->error . "</div>";
            }
        }
    } else {
        echo "<div style='color:red'>✗ Error al importar pregunta situacional ECF: " . $db->getConnection()->error . "</div>";
    }
}

// Nota: Por limitaciones de espacio, aquí incluiríamos las secciones para importar:
// - Preguntas del Test de Aptitudes Cognitivas (TAC)
// - Preguntas del Inventario de Personalidad Laboral (IPL)
// - Preguntas del Cuestionario de Motivaciones y Valores (CMV)

// Para este ejemplo, estamos mostrando solo la importación completa de ECF
// En una implementación real, continuaríamos con el mismo patrón para las demás pruebas

// 9. Mostrar estadísticas finales
echo "<h2>9. Resumen de la importación</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 50%; margin: 20px auto;'>";
echo "<tr><th>Elemento</th><th>Cantidad importada</th></tr>";
echo "<tr><td>Categorías de pruebas</td><td>{$counters['categorias']}</td></tr>";
echo "<tr><td>Pruebas</td><td>{$counters['pruebas']}</td></tr>";
echo "<tr><td>Dimensiones</td><td>{$counters['dimensiones']}</td></tr>";
echo "<tr><td>Niveles de interpretación</td><td>{$counters['niveles']}</td></tr>";
echo "<tr><td>Índices compuestos</td><td>{$counters['indices']}</td></tr>";
echo "<tr><td>Preguntas</td><td>{$counters['preguntas']}</td></tr>";
echo "<tr><td>Opciones de respuesta</td><td>{$counters['opciones']}</td></tr>";
echo "</table>";

echo "<h2>Importación completada</h2>";
echo "<p>La importación de datos básicos se ha completado. El sistema ahora cuenta con la estructura necesaria para realizar evaluaciones psicométricas.</p>";
echo "<p>Para continuar con la implementación completa, será necesario:</p>";
echo "<ol>";
echo "<li>Completar la importación de las preguntas para las pruebas TAC, IPL y CMV</li>";
echo "<li>Configurar las interpretaciones detalladas para cada dimensión y nivel</li>";
echo "<li>Implementar las interfaces de usuario para realizar las pruebas y visualizar resultados</li>";
echo "</ol>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>