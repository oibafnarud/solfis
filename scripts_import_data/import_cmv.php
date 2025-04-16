<?php
/**
 * Script para importar preguntas del Cuestionario de Motivaciones y Valores (CMV)
 * import_cmv.php
 * 
 * Este script importa todas las preguntas y opciones para el Cuestionario de Motivaciones y Valores.
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
    'opciones' => 0,
    'pares' => 0
];

echo "<h1>Importación de Preguntas para Cuestionario de Motivaciones y Valores (CMV)</h1>";
echo "<p>Iniciando proceso de importación...</p>";

// Obtener ID de la prueba CMV
$sql = "SELECT id FROM pruebas WHERE titulo LIKE '%Motivaciones y Valores%'";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $cmvId = $result->fetch_assoc()['id'];
} else {
    die("<div style='color:red'>Error: Prueba CMV no encontrada. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

// Obtener IDs de dimensiones de motivación
$sql = "SELECT id, nombre FROM dimensiones WHERE tipo = 'motivacion'";
$result = $db->query($sql);
$dimensionIds = [];
while ($row = $result->fetch_assoc()) {
    $dimensionIds[$row['nombre']] = $row['id'];
}

if (count($dimensionIds) < 8) {
    die("<div style='color:red'>Error: No se encontraron todas las dimensiones de motivación para CMV. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

echo "<h2>Importando preguntas de pares para el CMV</h2>";

// Pares de afirmaciones para el CMV
$paresCMV = [
    // PAR 1
    [
        'par_id' => 1,
        'orden' => 1,
        'opciones' => [
            [
                'texto' => 'Me motiva alcanzar metas desafiantes que pongan a prueba mis capacidades.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva tener influencia sobre decisiones importantes en la organización.',
                'dimension' => 'Motivación por Poder',
                'orden' => 2
            ]
        ]
    ],
    // PAR 2
    [
        'par_id' => 2,
        'orden' => 2,
        'opciones' => [
            [
                'texto' => 'Prefiero trabajar en un ambiente estable donde sepa qué esperar cada día.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero entornos donde pueda establecer relaciones cercanas con mis compañeros.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 3
    [
        'par_id' => 3,
        'orden' => 3,
        'opciones' => [
            [
                'texto' => 'Me resulta motivador poder tomar mis propias decisiones sin supervisión constante.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva saber que mi trabajo contribuye positivamente a la vida de otras personas.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 4
    [
        'par_id' => 4,
        'orden' => 4,
        'opciones' => [
            [
                'texto' => 'Busco oportunidades para enfrentar y superar desafíos complejos.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro poder equilibrar mis responsabilidades laborales y personales.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 5
    [
        'par_id' => 5,
        'orden' => 5,
        'opciones' => [
            [
                'texto' => 'Me impulsa obtener reconocimiento por mis logros y contribuciones.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva construir y mantener relaciones cercanas con colegas.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 6
    [
        'par_id' => 6,
        'orden' => 6,
        'opciones' => [
            [
                'texto' => 'Prefiero roles donde pueda influir en el desarrollo de otros profesionales.',
                'dimension' => 'Motivación por Poder',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro entornos de trabajo con estabilidad y procesos predecibles.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 2
            ]
        ]
    ],
    // PAR 7
    [
        'par_id' => 7,
        'orden' => 7,
        'opciones' => [
            [
                'texto' => 'Me motiva poder tomar decisiones independientemente, sin necesidad de consultas constantes.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me impulsa superar obstáculos y resolver problemas que otros consideran difíciles.',
                'dimension' => 'Motivación por Reto',
                'orden' => 2
            ]
        ]
    ],
    // PAR 8
    [
        'par_id' => 8,
        'orden' => 8,
        'opciones' => [
            [
                'texto' => 'Me resulta motivador saber que mi trabajo tiene un impacto positivo en la sociedad.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro un trabajo que me permita dedicar tiempo suficiente a mi vida personal.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 9
    [
        'par_id' => 9,
        'orden' => 9,
        'opciones' => [
            [
                'texto' => 'Me motiva superar mis propias marcas y mejorar constantemente.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero roles donde pueda crear impacto dirigiendo equipos o proyectos.',
                'dimension' => 'Motivación por Poder',
                'orden' => 2
            ]
        ]
    ],
    // PAR 10
    [
        'par_id' => 10,
        'orden' => 10,
        'opciones' => [
            [
                'texto' => 'Me impulsa trabajar en un entorno donde pueda desarrollar amistades.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro tener libertad para decidir cómo realizar mi trabajo.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 2
            ]
        ]
    ],
    // PAR 11
    [
        'par_id' => 11,
        'orden' => 11,
        'opciones' => [
            [
                'texto' => 'Prefiero trabajar en organizaciones con políticas y procedimientos claros.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva enfrentarme a situaciones que exigen soluciones innovadoras.',
                'dimension' => 'Motivación por Reto',
                'orden' => 2
            ]
        ]
    ],
    // PAR 12
    [
        'par_id' => 12,
        'orden' => 12,
        'opciones' => [
            [
                'texto' => 'Me impulsa poder contribuir al bienestar de los demás a través de mi trabajo.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro alcanzar objetivos ambiciosos que demuestren mi capacidad.',
                'dimension' => 'Motivación por Logro',
                'orden' => 2
            ]
        ]
    ],
    // PAR 13
    [
        'par_id' => 13,
        'orden' => 13,
        'opciones' => [
            [
                'texto' => 'Prefiero un trabajo que respete mis tiempos de descanso y vida familiar.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva tener influencia sobre las decisiones y dirección del equipo.',
                'dimension' => 'Motivación por Poder',
                'orden' => 2
            ]
        ]
    ],
    // PAR 14
    [
        'par_id' => 14,
        'orden' => 14,
        'opciones' => [
            [
                'texto' => 'Me impulsa desarrollar relaciones significativas en mi entorno laboral.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro entornos laborales estables y predecibles.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 2
            ]
        ]
    ],
    // PAR 15
    [
        'par_id' => 15,
        'orden' => 15,
        'opciones' => [
            [
                'texto' => 'Prefiero trabajar con autonomía, definiendo mis propios métodos y tiempos.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva sentir que mi trabajo tiene un propósito social positivo.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 16
    [
        'par_id' => 16,
        'orden' => 16,
        'opciones' => [
            [
                'texto' => 'Me impulsa enfrentar situaciones que me sacan de mi zona de confort.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro un trabajo que no interfiera con mi calidad de vida personal.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 17
    [
        'par_id' => 17,
        'orden' => 17,
        'opciones' => [
            [
                'texto' => 'Me motiva establecer y alcanzar objetivos ambiciosos.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero entornos donde pueda formar parte de un grupo unido.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 18
    [
        'par_id' => 18,
        'orden' => 18,
        'opciones' => [
            [
                'texto' => 'Me impulsa tener autoridad para dirigir proyectos o personas.',
                'dimension' => 'Motivación por Poder',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro poder trabajar a mi manera sin interferencias constantes.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 2
            ]
        ]
    ],
    // PAR 19
    [
        'par_id' => 19,
        'orden' => 19,
        'opciones' => [
            [
                'texto' => 'Prefiero organizaciones que ofrezcan estabilidad laboral a largo plazo.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva ayudar a otros a través de mi trabajo.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 20
    [
        'par_id' => 20,
        'orden' => 20,
        'opciones' => [
            [
                'texto' => 'Me impulsa resolver problemas que requieren pensar fuera de lo convencional.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro que mi trabajo me permita disfrutar de tiempo libre de calidad.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 21
    [
        'par_id' => 21,
        'orden' => 21,
        'opciones' => [
            [
                'texto' => 'Me motiva ser reconocido por la excelencia de mi trabajo.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero roles donde pueda ejercer liderazgo e influencia.',
                'dimension' => 'Motivación por Poder',
                'orden' => 2
            ]
        ]
    ],
    // PAR 22
    [
        'par_id' => 22,
        'orden' => 22,
        'opciones' => [
            [
                'texto' => 'Me impulsa trabajar en un ambiente colaborativo y amistoso.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro la predictibilidad y claridad en mis responsabilidades laborales.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 2
            ]
        ]
    ],
    // PAR 23
    [
        'par_id' => 23,
        'orden' => 23,
        'opciones' => [
            [
                'texto' => 'Prefiero poder organizar mi trabajo según mi propio criterio.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva saber que mi trabajo mejora la vida de otras personas.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 24
    [
        'par_id' => 24,
        'orden' => 24,
        'opciones' => [
            [
                'texto' => 'Me impulsa asumir tareas que otros consideran demasiado difíciles.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro un trabajo que me permita atender adecuadamente mis asuntos personales.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 25
    [
        'par_id' => 25,
        'orden' => 25,
        'opciones' => [
            [
                'texto' => 'Me motiva superar estándares de excelencia en mi campo.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero trabajar en un entorno donde las relaciones interpersonales sean prioritarias.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 26
    [
        'par_id' => 26,
        'orden' => 26,
        'opciones' => [
            [
                'texto' => 'Me impulsa poder tomar decisiones que afecten a la organización.',
                'dimension' => 'Motivación por Poder',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro entornos de trabajo donde me sienta seguro y estable.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 2
            ]
        ]
    ],
    // PAR 27
    [
        'par_id' => 27,
        'orden' => 27,
        'opciones' => [
            [
                'texto' => 'Prefiero tener libertad para decidir cómo realizar mis tareas.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva contribuir a causas que considero importantes para la sociedad.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 28
    [
        'par_id' => 28,
        'orden' => 28,
        'opciones' => [
            [
                'texto' => 'Me impulsa enfrentarme a problemas complejos que requieren toda mi capacidad.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro que mi trabajo respete mi vida personal y familiar.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 29
    [
        'par_id' => 29,
        'orden' => 29,
        'opciones' => [
            [
                'texto' => 'Me motiva establecer metas difíciles y trabajar hasta alcanzarlas.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero entornos donde pueda crear vínculos significativos con compañeros.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 30
    [
        'par_id' => 30,
        'orden' => 30,
        'opciones' => [
            [
                'texto' => 'Me impulsa tener autoridad para implementar mis ideas.',
                'dimension' => 'Motivación por Poder',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro la independencia para tomar mis propias decisiones profesionales.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 2
            ]
        ]
    ],
    // PAR 31
    [
        'par_id' => 31,
        'orden' => 31,
        'opciones' => [
            [
                'texto' => 'Prefiero trabajar en organizaciones establecidas con trayectoria probada.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva saber que mi trabajo tiene un impacto positivo en los demás.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 32
    [
        'par_id' => 32,
        'orden' => 32,
        'opciones' => [
            [
                'texto' => 'Me impulsa encontrar soluciones a problemas que parecen no tenerlas.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro poder disfrutar tanto de mi vida profesional como personal.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 33
    [
        'par_id' => 33,
        'orden' => 33,
        'opciones' => [
            [
                'texto' => 'Me motiva alcanzar resultados que superen las expectativas.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero roles donde pueda influir en el desarrollo del equipo.',
                'dimension' => 'Motivación por Poder',
                'orden' => 2
            ]
        ]
    ],
    // PAR 34
    [
        'par_id' => 34,
        'orden' => 34,
        'opciones' => [
            [
                'texto' => 'Me impulsa formar parte de un equipo con buenas relaciones personales.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro tener autonomía para decidir sobre mi trabajo.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 2
            ]
        ]
    ],
    // PAR 35
    [
        'par_id' => 35,
        'orden' => 35,
        'opciones' => [
            [
                'texto' => 'Prefiero entornos laborales estructurados y estables.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva ayudar a otros a través de mi actividad profesional.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 36
    [
        'par_id' => 36,
        'orden' => 36,
        'opciones' => [
            [
                'texto' => 'Me impulsa enfrentar situaciones laborales que me exijan al máximo.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro un trabajo que respete mis prioridades personales.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 37
    [
        'par_id' => 37,
        'orden' => 37,
        'opciones' => [
            [
                'texto' => 'Me motiva el reconocimiento por hacer bien mi trabajo.',
                'dimension' => 'Motivación por Logro',
                'orden' => 1
            ],
            [
                'texto' => 'Prefiero entornos donde pueda socializar y conocer a mis compañeros.',
                'dimension' => 'Motivación por Afiliación',
                'orden' => 2
            ]
        ]
    ],
    // PAR 38
    [
        'par_id' => 38,
        'orden' => 38,
        'opciones' => [
            [
                'texto' => 'Me impulsa poder dirigir e influir en el trabajo de otros.',
                'dimension' => 'Motivación por Poder',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro sentirme seguro respecto a mi futuro laboral.',
                'dimension' => 'Motivación por Seguridad',
                'orden' => 2
            ]
        ]
    ],
    // PAR 39
    [
        'par_id' => 39,
        'orden' => 39,
        'opciones' => [
            [
                'texto' => 'Prefiero decidir por mí mismo cómo organizar mi trabajo.',
                'dimension' => 'Motivación por Autonomía',
                'orden' => 1
            ],
            [
                'texto' => 'Me motiva contribuir al bienestar de la comunidad a través de mi trabajo.',
                'dimension' => 'Motivación por Servicio',
                'orden' => 2
            ]
        ]
    ],
    // PAR 40
    [
        'par_id' => 40,
        'orden' => 40,
        'opciones' => [
            [
                'texto' => 'Me impulsa asumir tareas que requieren aprender habilidades nuevas.',
                'dimension' => 'Motivación por Reto',
                'orden' => 1
            ],
            [
                'texto' => 'Valoro un trabajo que me permita mantener una vida equilibrada.',
                'dimension' => 'Motivación por Equilibrio',
                'orden' => 2
            ]
        ]
    ]
];

// Importar todos los pares de preguntas
// Importar todos los pares de preguntas
foreach ($paresCMV as $par) {
    // Para el CMV, creamos una pregunta principal para el par
    $preguntaTexto = "PAR " . $par['par_id'] . ": Seleccione la afirmación que refleja mejor lo que usted valora o prefiere en su entorno laboral:";
    $preguntaTexto = $db->escape($preguntaTexto);
    $par_id = (int)$par['par_id'];
    $orden = (int)$par['orden'];
    
    $sql = "INSERT INTO preguntas (prueba_id, texto, tipo_pregunta, par_id, tiempo_estimado, orden, obligatoria)
            VALUES ($cmvId, '$preguntaTexto', 'pares', $par_id, 45, $orden, 1)
            ON DUPLICATE KEY UPDATE
            texto = VALUES(texto),
            tipo_pregunta = VALUES(tipo_pregunta),
            par_id = VALUES(par_id),
            tiempo_estimado = VALUES(tiempo_estimado),
            orden = VALUES(orden)";
            
    if ($db->query($sql)) {
        $preguntaId = $db->lastInsertId();
        $counters['preguntas']++;
        $counters['pares']++;
        echo "<div style='color:green'>✓ Par de preguntas CMV #" . $par['par_id'] . " importado.</div>";
        
        // Importar las dos opciones (A y B) para este par
		foreach ($par['opciones'] as $opcion) {
			$dimensionId = $dimensionIds[$opcion['dimension']];
			$opcionTexto = $db->escape($opcion['texto']);
			$opcionOrden = (int)$opcion['orden'];
			
			$sql = "INSERT INTO opciones_respuesta (pregunta_id, texto, valor, dimension_id, orden)
					VALUES ($preguntaId, '$opcionTexto', 1, $dimensionId, $opcionOrden)
					ON DUPLICATE KEY UPDATE
					texto = VALUES(texto),
					valor = VALUES(valor),
					dimension_id = VALUES(dimension_id),
					orden = VALUES(orden)";
					
			if ($db->query($sql)) {
				$counters['opciones']++;
			} else {
				echo "<div style='color:red'>✗ Error al importar opción para par CMV: " . $db->getConnection()->error . "</div>";
			}
		}
    } else {
        echo "<div style='color:red'>✗ Error al importar par CMV: " . $db->getConnection()->error . "</div>";
    }
}

// Mostrar estadísticas
echo "<h2>Resumen de la importación del CMV</h2>";
echo "<p>Se han importado:</p>";
echo "<ul>";
echo "<li><strong>{$counters['preguntas']}</strong> preguntas</li>";
echo "<li><strong>{$counters['pares']}</strong> pares de opciones forzadas</li>";
echo "<li><strong>{$counters['opciones']}</strong> opciones de respuesta</li>";
echo "</ul>";

echo "<h2>Importación completada</h2>";
echo "<p>La importación de preguntas y opciones para el Cuestionario de Motivaciones y Valores (CMV) se ha completado correctamente.</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>