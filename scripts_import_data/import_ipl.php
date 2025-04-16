<?php
/**
 * Script para importar preguntas del Inventario de Personalidad Laboral (IPL)
 * import_ipl.php
 * 
 * Este script importa todas las preguntas y opciones para el Inventario de Personalidad Laboral.
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
    'preguntas' => 0,
    'opciones' => 0
];

echo "<h1>Importación de Preguntas para Inventario de Personalidad Laboral (IPL)</h1>";
echo "<p>Iniciando proceso de importación...</p>";

// Obtener ID de la prueba IPL
$sql = "SELECT id FROM pruebas WHERE titulo LIKE '%Personalidad Laboral%'";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $iplId = $result->fetch_assoc()['id'];
} else {
    die("<div style='color:red'>Error: Prueba IPL no encontrada. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

// Obtener IDs de dimensiones
$sql = "SELECT id, nombre FROM dimensiones WHERE nombre LIKE '%vs.%' OR nombre IN (
    'Extroversión vs. Introversión', 
    'Estabilidad vs. Reactividad Emocional', 
    'Apertura vs. Convencionalidad', 
    'Cooperación vs. Independencia', 
    'Meticulosidad vs. Flexibilidad')";
$result = $db->query($sql);
$dimensionIds = [];
while ($row = $result->fetch_assoc()) {
    $dimensionIds[$row['nombre']] = $row['id'];
}

if (count($dimensionIds) < 5) {
    die("<div style='color:red'>Error: No se encontraron todas las dimensiones para IPL. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

// Escala Likert para todas las preguntas IPL
$opcionesLikert = [
    ['texto' => 'Totalmente en desacuerdo', 'valor' => 1, 'orden' => 1],
    ['texto' => 'En desacuerdo', 'valor' => 2, 'orden' => 2],
    ['texto' => 'Ni de acuerdo ni en desacuerdo', 'valor' => 3, 'orden' => 3],
    ['texto' => 'De acuerdo', 'valor' => 4, 'orden' => 4],
    ['texto' => 'Totalmente de acuerdo', 'valor' => 5, 'orden' => 5]
];

// 1. Preguntas de Extroversión vs. Introversión
echo "<h2>1. Importando preguntas de Extroversión vs. Introversión</h2>";

$preguntasIPL_Extroversion = [
    // Ítems de Extroversión
    [
        'texto' => 'Disfruto conociendo nuevas personas en el trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 1,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    [
        'texto' => 'Prefiero trabajar en un ambiente con mucha interacción social.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 2,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    [
        'texto' => 'Suelo tomar la iniciativa en reuniones o grupos de trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 3,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    [
        'texto' => 'Me resulta fácil iniciar conversaciones con desconocidos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 4,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    [
        'texto' => 'Disfruto siendo el centro de atención en situaciones laborales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 5,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    [
        'texto' => 'Me siento energizado después de interactuar con mucha gente.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 6,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'positivo' // Extroversión
    ],
    
    // Ítems de Introversión
    [
        'texto' => 'Prefiero trabajar solo que en grupo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 7,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ],
    [
        'texto' => 'Necesito tiempo a solas para recargar energías después de reuniones largas.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 8,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ],
    [
        'texto' => 'Pienso cuidadosamente antes de hablar en reuniones.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 9,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ],
    [
        'texto' => 'Las interacciones sociales prolongadas me resultan agotadoras.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 10,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ],
    [
        'texto' => 'Prefiero expresar mis ideas por escrito más que verbalmente.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 11,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ],
    [
        'texto' => 'Me concentro mejor en entornos tranquilos con pocas distracciones.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 12,
        'dimensionId' => $dimensionIds['Extroversión vs. Introversión'],
        'polo' => 'negativo' // Introversión
    ]
];

// 2. Preguntas de Estabilidad vs. Reactividad Emocional
echo "<h2>2. Importando preguntas de Estabilidad vs. Reactividad Emocional</h2>";

$preguntasIPL_Estabilidad = [
    // Ítems de Estabilidad
    [
        'texto' => 'Mantengo la calma en situaciones de presión laboral.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 13,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    [
        'texto' => 'Rara vez me siento ansioso antes de presentaciones o reuniones importantes.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 14,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    [
        'texto' => 'Puedo manejar las críticas constructivas sin sentirme personalmente atacado.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 15,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    [
        'texto' => 'Me recupero rápidamente de los contratiempos laborales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 16,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    [
        'texto' => 'Mantengo un estado de ánimo constante en el trabajo, independientemente de las circunstancias.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 17,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    [
        'texto' => 'Tomo decisiones importantes con serenidad, sin dejarme llevar por emociones momentáneas.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 18,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'positivo' // Estabilidad
    ],
    
    // Ítems de Reactividad Emocional
    [
        'texto' => 'Me afectan profundamente las críticas sobre mi trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 19,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ],
    [
        'texto' => 'Tiendo a preocuparme por los posibles problemas futuros en el trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 20,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ],
    [
        'texto' => 'Cambio de humor con facilidad dependiendo de cómo va el día.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 21,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ],
    [
        'texto' => 'Me cuesta controlar mi frustración cuando las cosas no salen como esperaba.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 22,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ],
    [
        'texto' => 'Me siento tenso cuando enfrento plazos ajustados.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 23,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ],
    [
        'texto' => 'Me cuesta concentrarme después de recibir malas noticias en el trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 24,
        'dimensionId' => $dimensionIds['Estabilidad vs. Reactividad Emocional'],
        'polo' => 'negativo' // Reactividad Emocional
    ]
];

// 3. Preguntas de Apertura vs. Convencionalidad
echo "<h2>3. Importando preguntas de Apertura vs. Convencionalidad</h2>";

$preguntasIPL_Apertura = [
    // Ítems de Apertura
    [
        'texto' => 'Me atraen las ideas novedosas y poco convencionales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 25,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    [
        'texto' => 'Disfruto explorando enfoques alternativos a los problemas.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 26,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    [
        'texto' => 'Me interesa probar nuevos métodos aunque los establecidos funcionen bien.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 27,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    [
        'texto' => 'Me apasionan las discusiones teóricas y conceptuales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 28,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    [
        'texto' => 'Valoro la originalidad más que seguir procedimientos establecidos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 29,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    [
        'texto' => 'Busco activamente oportunidades para aprender cosas nuevas y diferentes.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 30,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'positivo' // Apertura
    ],
    
    // Ítems de Convencionalidad
    [
        'texto' => 'Prefiero seguir métodos probados que experimentar con nuevos enfoques.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 31,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
'polo' => 'negativo' // Convencionalidad
    ],
    [
        'texto' => 'Me siento más cómodo con rutinas predecibles en el trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 32,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'negativo' // Convencionalidad
    ],
    [
        'texto' => 'Considero que las tradiciones y procedimientos establecidos son importantes.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 33,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'negativo' // Convencionalidad
    ],
    [
        'texto' => 'Prefiero proyectos concretos y prácticos más que teóricos o abstractos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 34,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'negativo' // Convencionalidad
    ],
    [
        'texto' => 'Me incomoda la ambigüedad y la incertidumbre en el trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 35,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'negativo' // Convencionalidad
    ],
    [
        'texto' => 'Prefiero especialización profunda a conocimientos amplios en varios campos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 36,
        'dimensionId' => $dimensionIds['Apertura vs. Convencionalidad'],
        'polo' => 'negativo' // Convencionalidad
    ]
];

// 4. Preguntas de Cooperación vs. Independencia
echo "<h2>4. Importando preguntas de Cooperación vs. Independencia</h2>";

$preguntasIPL_Cooperacion = [
    // Ítems de Cooperación
    [
        'texto' => 'Priorizo mantener buenas relaciones laborales, incluso a costa de mis objetivos personales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 37,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    [
        'texto' => 'Suelo ceder en discusiones para evitar conflictos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 38,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    [
        'texto' => 'Me preocupo constantemente por cómo mis acciones afectan a los demás.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 39,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    [
        'texto' => 'Prefiero ambientes de trabajo colaborativos más que competitivos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 40,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    [
        'texto' => 'Estoy dispuesto a asumir más trabajo para ayudar a compañeros que lo necesitan.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 41,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    [
        'texto' => 'Creo que el consenso de equipo es más importante que imponer mi opinión.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 42,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'positivo' // Cooperación
    ],
    
    // Ítems de Independencia
    [
        'texto' => 'Defiendo firmemente mis ideas aunque no sean populares.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 43,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ],
    [
        'texto' => 'Prefiero tomar decisiones basadas en lógica pura que en consideraciones emocionales.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 44,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ],
    [
        'texto' => 'Valoro más la eficiencia que mantener a todos contentos.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 45,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ],
    [
        'texto' => 'No tengo problema en expresar desacuerdo con figuras de autoridad.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 46,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ],
    [
        'texto' => 'Prefiero ser directo aunque algunos puedan percibirlo como brusco.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 47,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ],
    [
        'texto' => 'Considero que la competencia sana mejora el rendimiento del equipo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 48,
        'dimensionId' => $dimensionIds['Cooperación vs. Independencia'],
        'polo' => 'negativo' // Independencia
    ]
];

// 5. Preguntas de Meticulosidad vs. Flexibilidad
echo "<h2>5. Importando preguntas de Meticulosidad vs. Flexibilidad</h2>";

$preguntasIPL_Meticulosidad = [
    // Ítems de Meticulosidad
    [
        'texto' => 'Tengo altos estándares de organización y precisión en mi trabajo.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 49,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    [
        'texto' => 'Prefiero planificar con antelación que improvisar.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 50,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    [
        'texto' => 'Sigo las reglas y procedimientos al pie de la letra.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 51,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    [
        'texto' => 'Reviso varias veces mi trabajo antes de darlo por terminado.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 52,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    [
        'texto' => 'Mantengo mis compromisos incluso cuando resulta inconveniente.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 53,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    [
        'texto' => 'Mantengo mis espacios de trabajo perfectamente ordenados.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 54,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'positivo' // Meticulosidad
    ],
    
    // Ítems de Flexibilidad
    [
        'texto' => 'Prefiero adaptar mis planes según surjan las circunstancias.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 55,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ],
    [
        'texto' => 'Me aburren las tareas que requieren atención meticulosa a detalles rutinarios.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 56,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ],
    [
        'texto' => 'Considero que algunas reglas pueden flexibilizarse dependiendo de la situación.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 57,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ],
    [
        'texto' => 'Prefiero tener múltiples proyectos en marcha simultáneamente.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 58,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ],
    [
        'texto' => 'Puedo trabajar eficientemente incluso en entornos desorganizados.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 59,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ],
    [
        'texto' => 'Confío en mi capacidad para resolver problemas sobre la marcha.',
        'tipo_pregunta' => 'likert',
        'tiempo_estimado' => 30,
        'orden' => 60,
        'dimensionId' => $dimensionIds['Meticulosidad vs. Flexibilidad'],
        'polo' => 'negativo' // Flexibilidad
    ]
];

// Unir todas las preguntas del IPL
$preguntasIPL = array_merge(
    $preguntasIPL_Extroversion,
    $preguntasIPL_Estabilidad,
    $preguntasIPL_Apertura,
    $preguntasIPL_Cooperacion,
    $preguntasIPL_Meticulosidad
);

// Importar todas las preguntas y opciones
foreach ($preguntasIPL as $pregunta) {
    $texto = $db->escape($pregunta['texto']);
    $tipo_pregunta = $db->escape($pregunta['tipo_pregunta']);
    $dimensionId = (int)$pregunta['dimensionId'];
    $tiempo_estimado = (int)$pregunta['tiempo_estimado'];
    $orden = (int)$pregunta['orden'];
    
    $sql = "INSERT INTO preguntas (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria)
            VALUES ($iplId, '$texto', $dimensionId, '$tipo_pregunta', $tiempo_estimado, $orden, 1)
            ON DUPLICATE KEY UPDATE
            texto = VALUES(texto),
            dimension_id = VALUES(dimension_id),
            tipo_pregunta = VALUES(tipo_pregunta),
            tiempo_estimado = VALUES(tiempo_estimado),
            orden = VALUES(orden)";
            
    if ($db->query($sql)) {
        $preguntaId = $db->lastInsertId();
        $counters['preguntas']++;
        echo "<div style='color:green'>✓ Pregunta IPL importada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
        
        // Crear opciones Likert (iguales para todas las preguntas)
        foreach ($opcionesLikert as $opcion) {
            $valor = $opcion['valor'];
            $opcionTexto = $db->escape($opcion['texto']);
            $opcionOrden = (int)$opcion['orden'];
            $polo = $db->escape($pregunta['polo']);
            
            // Ajustar valor según el polo (para preguntas en polo negativo invertimos el valor)
            if ($pregunta['polo'] == 'negativo') {
                $valor = 6 - $valor; // Invertir escala: 5→1, 4→2, 3→3, 2→4, 1→5
            }
            
            $sql = "INSERT INTO opciones_respuesta (pregunta_id, texto, valor, dimension_id, orden, polo)
                    VALUES ($preguntaId, '$opcionTexto', $valor, $dimensionId, $opcionOrden, '$polo')
                    ON DUPLICATE KEY UPDATE
                    texto = VALUES(texto),
                    valor = VALUES(valor),
                    dimension_id = VALUES(dimension_id),
                    orden = VALUES(orden),
                    polo = VALUES(polo)";
                    
            if ($db->query($sql)) {
                $counters['opciones']++;
            } else {
                echo "<div style='color:red'>✗ Error al importar opción para pregunta IPL: " . $db->getConnection()->error . "</div>";
            }
        }
    } else {
        echo "<div style='color:red'>✗ Error al importar pregunta IPL: " . $stmt->error . "</div>";
    }
}

// Mostrar estadísticas
echo "<h2>Resumen de la importación del IPL</h2>";
echo "<p>Se han importado:</p>";
echo "<ul>";
echo "<li><strong>{$counters['preguntas']}</strong> preguntas</li>";
echo "<li><strong>{$counters['opciones']}</strong> opciones de respuesta</li>";
echo "</ul>";

echo "<h2>Importación completada</h2>";
echo "<p>La importación de preguntas y opciones para el Inventario de Personalidad Laboral (IPL) se ha completado correctamente.</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>