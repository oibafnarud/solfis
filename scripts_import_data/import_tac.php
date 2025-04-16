<?php
/**
 * Script para importar preguntas del Test de Aptitudes Cognitivas (TAC)
 * import_tac.php
 * 
 * Este script importa todas las preguntas y opciones para el Test de Aptitudes Cognitivas.
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

echo "<h1>Importación de Preguntas para Test de Aptitudes Cognitivas (TAC)</h1>";
echo "<p>Iniciando proceso de importación...</p>";

// Obtener ID de la prueba TAC
$sql = "SELECT id FROM pruebas WHERE titulo LIKE '%Aptitudes Cognitivas%'";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $tacId = $result->fetch_assoc()['id'];
} else {
    die("<div style='color:red'>Error: Prueba TAC no encontrada. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

// Obtener IDs de dimensiones
$sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN ('Razonamiento Verbal', 'Razonamiento Numérico', 'Razonamiento Lógico', 'Atención al Detalle')";
$result = $db->query($sql);
$dimensionIds = [];
while ($row = $result->fetch_assoc()) {
    $dimensionIds[$row['nombre']] = $row['id'];
}

if (count($dimensionIds) < 4) {
    die("<div style='color:red'>Error: No se encontraron todas las dimensiones para TAC. Asegúrese de haber ejecutado el script de importación principal primero.</div>");
}

// 1. Preguntas de Razonamiento Verbal (10 preguntas)
echo "<h2>1. Importando preguntas de Razonamiento Verbal</h2>";

$preguntasTAC_Verbal = [
    // Analogías Verbales
    [
        'texto' => 'MARTILLO es a CLAVO como DESTORNILLADOR es a:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 1,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Madera', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Tornillo', 'valor' => 1, 'orden' => 2], // Correcta
            ['texto' => 'Metal', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Herramienta', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'TELÉFONO es a HABLAR como LIBRO es a:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 2,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Papel', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Autor', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Leer', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'Biblioteca', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'AGUA es a SED como COMIDA es a:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 3,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Cocina', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Sabor', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Hambre', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'Nutrición', 'valor' => 0, 'orden' => 4]
        ]
    ],
    
    // Antónimos
    [
        'texto' => 'EFÍMERO:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 4,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Duradero', 'valor' => 1, 'orden' => 1], // Correcta
            ['texto' => 'Frágil', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Volátil', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Inestable', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'DILIGENTE:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 5,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Descuidado', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Lento', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Incompetente', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Negligente', 'valor' => 1, 'orden' => 4] // Correcta
        ]
    ],
    [
        'texto' => 'ABUNDANTE:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 6,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Limitado', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Escaso', 'valor' => 1, 'orden' => 2], // Correcta
            ['texto' => 'Insuficiente', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Pequeño', 'valor' => 0, 'orden' => 4]
        ]
    ],
    
    // Comprensión Verbal (texto sobre IA generativa)
    [
        'texto' => 'Según el texto sobre IA generativa, ¿qué característica distingue a la IA generativa de sistemas anteriores?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 7,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Su velocidad de procesamiento', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Su capacidad para crear contenido original', 'valor' => 1, 'orden' => 2], // Correcta
            ['texto' => 'Su amplia adopción en 2023', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Su potencial para reemplazar trabajadores', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Los debates éticos mencionados en el texto sobre IA generativa se centran principalmente en:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 8,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Los costos de implementación', 'valor' => 0, 'orden' => 1],
            ['texto' => 'La privacidad de datos', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Cuestiones de autoría y originalidad', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'La seguridad informática', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Según el texto sobre IA generativa, la actitud de los profesionales hacia esta tecnología:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 9,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Es uniformemente negativa', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Es uniformemente positiva', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Está dividida entre temor y adaptación', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'No se menciona en el texto', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'La frase "colaboradores digitales" en el texto sobre IA generativa sugiere:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 10,
        'dimensionId' => $dimensionIds['Razonamiento Verbal'],
        'opciones' => [
            ['texto' => 'Que los humanos trabajarán exclusivamente con robots', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Un enfoque de coexistencia entre humanos y tecnología', 'valor' => 1, 'orden' => 2], // Correcta
            ['texto' => 'Que la IA reemplazará eventualmente a los humanos', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Que los humanos controlarán completamente a la IA', 'valor' => 0, 'orden' => 4]
        ]
    ]
];

// 2. Preguntas de Razonamiento Numérico (10 preguntas)
echo "<h2>2. Importando preguntas de Razonamiento Numérico</h2>";

$preguntasTAC_Numerico = [
    // Series Numéricas
    [
        'texto' => '¿Qué número continúa la serie? 2, 4, 8, 16, 32, ...',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 11,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '36', 'valor' => 0, 'orden' => 1],
            ['texto' => '48', 'valor' => 0, 'orden' => 2],
            ['texto' => '64', 'valor' => 1, 'orden' => 3], // Correcta (se multiplica por 2 cada vez)
            ['texto' => '96', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Qué número continúa la serie? 1, 4, 9, 16, 25, ...',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 12,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '30', 'valor' => 0, 'orden' => 1],
            ['texto' => '36', 'valor' => 1, 'orden' => 2], // Correcta (son los cuadrados: 1², 2², 3², 4², 5², 6²)
            ['texto' => '42', 'valor' => 0, 'orden' => 3],
            ['texto' => '49', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Qué número continúa la serie? 3, 6, 12, 24, 48, ...',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 13,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '72', 'valor' => 0, 'orden' => 1],
            ['texto' => '84', 'valor' => 0, 'orden' => 2],
            ['texto' => '96', 'valor' => 1, 'orden' => 3], // Correcta (se multiplica por 2 cada vez)
            ['texto' => '108', 'valor' => 0, 'orden' => 4]
        ]
    ],
    
    // Operaciones Numéricas
    [
        'texto' => 'Si 5x + 3 = 28, entonces x =',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 14,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '4', 'valor' => 0, 'orden' => 1],
            ['texto' => '5', 'valor' => 1, 'orden' => 2], // Correcta (5x = 25, x = 5)
            ['texto' => '6', 'valor' => 0, 'orden' => 3],
            ['texto' => '7', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Un producto cuesta $80 y se aplica un descuento del 15%. ¿Cuál es el precio final?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 15,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '$65', 'valor' => 0, 'orden' => 1],
            ['texto' => '$68', 'valor' => 1, 'orden' => 2], // Correcta (80 - 12 = 68)
            ['texto' => '$72', 'valor' => 0, 'orden' => 3],
            ['texto' => '$76', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Si 8 trabajadores pueden completar un proyecto en 10 días, ¿cuántos días tardarían 5 trabajadores en completar el mismo proyecto?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 16,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '12 días', 'valor' => 0, 'orden' => 1],
            ['texto' => '14 días', 'valor' => 0, 'orden' => 2],
            ['texto' => '16 días', 'valor' => 1, 'orden' => 3], // Correcta (8×10 = 5×x, x = 16)
            ['texto' => '18 días', 'valor' => 0, 'orden' => 4]
        ]
    ],
    
    // Razonamiento con Datos
    [
        'texto' => '¿Cuál fue el aumento porcentual en ventas totales de 2023 a 2024 según la tabla? (Ventas 2023: 570, Ventas 2024: 630)',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 17,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '9.5%', 'valor' => 0, 'orden' => 1],
            ['texto' => '10.5%', 'valor' => 1, 'orden' => 2], // Correcta ((630-570)/570×100 = 10.5%)
            ['texto' => '11.5%', 'valor' => 0, 'orden' => 3],
            ['texto' => '12.5%', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿En qué trimestre se produjo el mayor crecimiento porcentual entre 2023 y 2024? (Q1: 120→135, Q2: 145→160, Q3: 130→150, Q4: 175→185)',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 18,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => 'Q1', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Q2', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Q3', 'valor' => 1, 'orden' => 3], // Correcta (Q1: 12.5%, Q2: 10.3%, Q3: 15.4%, Q4: 5.7%)
            ['texto' => 'Q4', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Si la tendencia de crecimiento continúa al mismo ritmo, ¿cuál sería la proyección aproximada de ventas totales para 2025? (Ventas 2024: 630)',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 19,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => '680', 'valor' => 0, 'orden' => 1],
            ['texto' => '690', 'valor' => 0, 'orden' => 2],
            ['texto' => '700', 'valor' => 1, 'orden' => 3], // Correcta (10.5% más que 630 ≈ 696, redondeado a 700)
            ['texto' => '710', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Cuál fue el trimestre con menor contribución porcentual a las ventas totales de 2024? (Q1: 135, Q2: 160, Q3: 150, Q4: 185, Total: 630)',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 20,
        'dimensionId' => $dimensionIds['Razonamiento Numérico'],
        'opciones' => [
            ['texto' => 'Q1', 'valor' => 1, 'orden' => 1], // Correcta (Q1: 21.4%, Q2: 25.4%, Q3: 23.8%, Q4: 29.4%)
            ['texto' => 'Q2', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Q3', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Q4', 'valor' => 0, 'orden' => 4]
        ]
    ]
];

// 3. Preguntas de Razonamiento Lógico (10 preguntas)
echo "<h2>3. Importando preguntas de Razonamiento Lógico</h2>";

$preguntasTAC_Logico = [
    // Secuencias Lógicas
    [
        'texto' => '¿Qué figura completa la secuencia? [Secuencia: círculo → triángulo → cuadrado → pentágono → ?]',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 21,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Hexágono', 'valor' => 1, 'orden' => 1], // Correcta (figuras con número creciente de lados)
            ['texto' => 'Círculo', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Triángulo', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Rectángulo', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Qué número completa la secuencia? 7, 10, 8, 11, 9, 12, ?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 22,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => '7', 'valor' => 0, 'orden' => 1],
            ['texto' => '10', 'valor' => 1, 'orden' => 2], // Correcta (alterna +3, -2, +3, -2, +3...)
            ['texto' => '11', 'valor' => 0, 'orden' => 3],
            ['texto' => '13', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Si A = 1, B = 2, C = 3... ¿qué continúa la secuencia? AD, BE, CF, ?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 23,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'DG', 'valor' => 1, 'orden' => 1], // Correcta (primera letra: secuencia, segunda letra: +3 posiciones)
            ['texto' => 'DH', 'valor' => 0, 'orden' => 2],
            ['texto' => 'EG', 'valor' => 0, 'orden' => 3],
            ['texto' => 'EH', 'valor' => 0, 'orden' => 4]
        ]
    ],
    
    // Silogismos
    [
        'texto' => 'Todos los delfines son mamíferos. Algunos mamíferos viven en tierra. Por lo tanto:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 24,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Todos los delfines viven en tierra', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Ningún delfín vive en tierra', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Algunos delfines viven en tierra', 'valor' => 0, 'orden' => 3],
            ['texto' => 'No se puede concluir si los delfines viven en tierra', 'valor' => 1, 'orden' => 4] // Correcta
        ]
    ],
    [
        'texto' => 'Ningún ave es reptil. Todos los cocodrilos son reptiles. Por lo tanto:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 25,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Algunos cocodrilos son aves', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Algunos reptiles no son cocodrilos', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Ningún cocodrilo es ave', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'Algunas aves son cocodrilos', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Todos los ingenieros de la empresa X saben programar. María trabaja en la empresa X pero no es ingeniera. Por lo tanto:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 26,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'María sabe programar', 'valor' => 0, 'orden' => 1],
            ['texto' => 'María no sabe programar', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Todos en la empresa X saben programar', 'valor' => 0, 'orden' => 3],
            ['texto' => 'No se puede concluir si María sabe programar', 'valor' => 1, 'orden' => 4] // Correcta
        ]
    ],
    
    // Problemas Lógicos
    [
        'texto' => 'Ana, Beatriz y Carmen tienen diferentes profesiones: médica, ingeniera y abogada, no necesariamente en ese orden. Ana no es abogada. Beatriz no es médica. ¿Cuál es la profesión de Carmen?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 27,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Médica', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Ingeniera', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Abogada', 'valor' => 1, 'orden' => 3], // Correcta
            ['texto' => 'No se puede determinar', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Cinco personas están en una fila. Juan está detrás de Pedro pero delante de Sara. María está delante de Juan pero detrás de Carlos. ¿Quién está en medio de los cinco?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 28,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Carlos', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Juan', 'valor' => 1, 'orden' => 2], // Correcta (El orden es: Carlos, María, Juan, Pedro, Sara)
            ['texto' => 'Pedro', 'valor' => 0, 'orden' => 3],
            ['texto' => 'María', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Si no está lloviendo, entonces Pablo va al parque. Pablo no fue al parque hoy. Por lo tanto:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 29,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Estaba lloviendo', 'valor' => 1, 'orden' => 1], // Correcta (modus tollens)
            ['texto' => 'No estaba lloviendo', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Pablo decidió no ir al parque a pesar del clima', 'valor' => 0, 'orden' => 3],
            ['texto' => 'No se puede determinar si estaba lloviendo', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'En una carrera, Daniel llegó antes que Eduardo. Carlos llegó después que Bernardo pero antes que Daniel. Alberto llegó después que Eduardo. ¿Quién llegó en tercer lugar?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 30,
        'dimensionId' => $dimensionIds['Razonamiento Lógico'],
        'opciones' => [
            ['texto' => 'Alberto', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Bernardo', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Carlos', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Daniel', 'valor' => 1, 'orden' => 4] // Correcta (Orden: Bernardo, Carlos, Daniel, Eduardo, Alberto)
        ]
    ]
];

// 4. Preguntas de Atención al Detalle (10 preguntas)
echo "<h2>4. Importando preguntas de Atención al Detalle</h2>";

$preguntasTAC_Detalle = [
    // Detección de Errores
    [
        'texto' => 'Identifique la secuencia que contiene un error:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 31,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ', 'valor' => 0, 'orden' => 1],
            ['texto' => 'ABCDEFGHIJKLMNNÑOPQRSTUVWXYZ', 'valor' => 0, 'orden' => 2],
            ['texto' => 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ', 'valor' => 0, 'orden' => 3],
            ['texto' => 'ABCDEFGHIJKLMNÑOPQRSTUVWXYX', 'valor' => 1, 'orden' => 4] // Correcta (termina con X en lugar de Z)
        ]
    ],
    [
        'texto' => '¿Cuál de estas secuencias numéricas contiene un error?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 32,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '2, 4, 6, 8, 10, 12, 14, 16, 18, 20', 'valor' => 0, 'orden' => 1],
            ['texto' => '1, 4, 7, 10, 13, 16, 19, 22, 25, 28', 'valor' => 0, 'orden' => 2],
            ['texto' => '1, 2, 4, 8, 16, 32, 63, 128, 256, 512', 'valor' => 1, 'orden' => 3], // Correcta (debería ser 64 en lugar de 63)
            ['texto' => '1, 3, 6, 10, 15, 21, 28, 36, 45, 55', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Identifique la opción con un error ortográfico:',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 33,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => 'Desarrollo, desafío, descenso, desenlace', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Antibiótico, antihistamínico, antiséptico, antitérmico', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Prehispánico, prehistoria, preinscripción, prejuicio', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Subconciente, subrayar, submarino, subterráneo', 'valor' => 1, 'orden' => 4] // Correcta (es "subconsciente")
        ]
    ],
    
    // Comparación de Datos
    [
        'texto' => '¿Cuáles dos filas son idénticas?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 34,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '583947261059372', 'valor' => 0, 'orden' => 1],
            ['texto' => '583947261059372', 'valor' => 0, 'orden' => 2],
            ['texto' => '583947261095372', 'valor' => 0, 'orden' => 3],
            ['texto' => '583974261059372', 'valor' => 0, 'orden' => 4],
            ['texto' => 'Opciones A y B', 'valor' => 1, 'orden' => 5] // Correcta (a y b son idénticas)
        ]
    ],
    [
        'texto' => 'Compare las siguientes direcciones. ¿Cuáles dos son idénticas?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 35,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => 'Av. Constitución #1524, Oficina 702', 'valor' => 0, 'orden' => 1],
            ['texto' => 'Av. Constitución #1542, Oficina 702', 'valor' => 0, 'orden' => 2],
            ['texto' => 'Av. Constitución #1524, Oficina 720', 'valor' => 0, 'orden' => 3],
            ['texto' => 'Av. Constitución #1524, Oficina 702', 'valor' => 0, 'orden' => 4],
            ['texto' => 'Opciones A y D', 'valor' => 1, 'orden' => 5] // Correcta (a y d son idénticas)
        ]
    ],
    [
        'texto' => '¿Cuáles dos códigos son idénticos?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 45,
        'orden' => 36,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => 'XDG-5723-BNMQ-4210', 'valor' => 0, 'orden' => 1],
            ['texto' => 'XDG-5723-BNMQ-4210', 'valor' => 0, 'orden' => 2],
            ['texto' => 'XDG-5732-BNMQ-4210', 'valor' => 0, 'orden' => 3],
            ['texto' => 'XDG-5723-BNMQ-4201', 'valor' => 0, 'orden' => 4],
            ['texto' => 'Opciones A y B', 'valor' => 1, 'orden' => 5] // Correcta (a y b son idénticos)
        ]
    ],
    
    // Atención Visual
    [
        'texto' => '¿Cuántos números pares hay en la cuadrícula de 5x5 con los números del 1 al 25?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 37,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '10', 'valor' => 0, 'orden' => 1],
            ['texto' => '12', 'valor' => 1, 'orden' => 2], // Correcta (2,4,6,8,10,12,14,16,18,20,22,24)
            ['texto' => '13', 'valor' => 0, 'orden' => 3],
            ['texto' => '15', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Cuál es la suma de los números en la diagonal que va desde la esquina superior izquierda hasta la esquina inferior derecha en una cuadrícula de 5x5 con los números del 1 al 25?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 38,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '63', 'valor' => 0, 'orden' => 1],
            ['texto' => '65', 'valor' => 1, 'orden' => 2], // Correcta (1+7+13+19+25 = 65)
            ['texto' => '67', 'valor' => 0, 'orden' => 3],
            ['texto' => '75', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => '¿Cuántos números en la cuadrícula de 5x5 con los números del 1 al 25 son divisibles por 3?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 39,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '6', 'valor' => 0, 'orden' => 1],
            ['texto' => '7', 'valor' => 0, 'orden' => 2],
            ['texto' => '8', 'valor' => 1, 'orden' => 3], // Correcta (3,6,9,12,15,18,21,24)
            ['texto' => '9', 'valor' => 0, 'orden' => 4]
        ]
    ],
    [
        'texto' => 'Si se colorean todos los números primos en la cuadrícula de 5x5 con los números del 1 al 25, ¿cuántos números quedarían sin colorear?',
        'tipo_pregunta' => 'multiple',
        'tiempo_estimado' => 60,
        'orden' => 40,
        'dimensionId' => $dimensionIds['Atención al Detalle'],
        'opciones' => [
            ['texto' => '15', 'valor' => 0, 'orden' => 1],
            ['texto' => '16', 'valor' => 0, 'orden' => 2],
            ['texto' => '17', 'valor' => 1, 'orden' => 3], // Correcta (números primos: 2,3,5,7,11,13,17,19,23)
            ['texto' => '18', 'valor' => 0, 'orden' => 4]
        ]
    ]
];

// Unir todas las preguntas
$preguntasTAC = array_merge(
    $preguntasTAC_Verbal,
    $preguntasTAC_Numerico,
    $preguntasTAC_Logico,
    $preguntasTAC_Detalle
);

// Importar todas las preguntas y opciones
foreach ($preguntasTAC as $pregunta) {
    $texto = $db->escape($pregunta['texto']);
    $tipo_pregunta = $db->escape($pregunta['tipo_pregunta']);
    $dimensionId = (int)$pregunta['dimensionId'];
    $tiempo_estimado = (int)$pregunta['tiempo_estimado'];
    $orden = (int)$pregunta['orden'];
    
    $sql = "INSERT INTO preguntas (prueba_id, texto, dimension_id, tipo_pregunta, tiempo_estimado, orden, obligatoria)
            VALUES ($tacId, '$texto', $dimensionId, '$tipo_pregunta', $tiempo_estimado, $orden, 1)
            ON DUPLICATE KEY UPDATE
            texto = VALUES(texto),
            dimension_id = VALUES(dimension_id),
            tipo_pregunta = VALUES(tipo_pregunta),
            tiempo_estimado = VALUES(tiempo_estimado),
            orden = VALUES(orden)";
            
    if ($db->query($sql)) {
        $preguntaId = $db->lastInsertId();
        $counters['preguntas']++;
        echo "<div style='color:green'>✓ Pregunta TAC importada: " . substr($pregunta['texto'], 0, 50) . "...</div>";
        
        // Crear opciones para cada pregunta
        foreach ($pregunta['opciones'] as $opcion) {
            $opcionTexto = $db->escape($opcion['texto']);
            $valor = (int)$opcion['valor'];
            $opcionOrden = (int)$opcion['orden'];
            
            $sql = "INSERT INTO opciones_respuesta (pregunta_id, texto, valor, dimension_id, orden)
                    VALUES ($preguntaId, '$opcionTexto', $valor, $dimensionId, $opcionOrden)
                    ON DUPLICATE KEY UPDATE
                    texto = VALUES(texto),
                    valor = VALUES(valor),
                    dimension_id = VALUES(dimension_id),
                    orden = VALUES(orden)";
                    
            if ($db->query($sql)) {
                $counters['opciones']++;
            } else {
                echo "<div style='color:red'>✗ Error al importar opción para pregunta TAC: " . $db->getConnection()->error . "</div>";
            }
        }
    } else {
        echo "<div style='color:red'>✗ Error al importar pregunta TAC: " . $db->getConnection()->error . "</div>";
    }
}

// Mostrar estadísticas
echo "<h2>Resumen de la importación del TAC</h2>";
echo "<p>Se han importado:</p>";
echo "<ul>";
echo "<li><strong>{$counters['preguntas']}</strong> preguntas</li>";
echo "<li><strong>{$counters['opciones']}</strong> opciones de respuesta</li>";
echo "</ul>";

echo "<h2>Importación completada</h2>";
echo "<p>La importación de preguntas y opciones para el Test de Aptitudes Cognitivas (TAC) se ha completado correctamente.</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>