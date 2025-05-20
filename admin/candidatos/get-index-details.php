<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que se proporciona un ID de índice
if (!isset($_GET['index_id']) || empty($_GET['index_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de índice no proporcionado']);
    exit;
}

$index_id = (int)$_GET['index_id'];

// Incluir las dependencias necesarias
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/TestManager.php';

// Inicializar el gestor de pruebas
$testManager = new TestManager();

// Obtener componentes del índice
$components = $testManager->getCompositeIndexComponents($index_id);

// Determinar nivel de interpretación basado en el valor proporcionado
$interpretation = null;
if (isset($_GET['value']) && !empty($_GET['value'])) {
    $value = (float)$_GET['value'];
    
    // Buscar el nivel de interpretación para este valor
    $sql = "SELECT ni.* 
            FROM niveles_interpretacion ni
            WHERE $value BETWEEN ni.rango_min AND ni.rango_max
            ORDER BY ni.orden
            LIMIT 1";
    
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $nivel = $result->fetch_assoc();
        $nivel_id = $nivel['id'];
        
        // Buscar interpretación específica para este índice y nivel
        $sql = "SELECT i.*
                FROM interpretaciones i
                WHERE i.nivel_id = $nivel_id
                AND i.dimension_id = $index_id
                LIMIT 1";
        
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $interpretation = $result->fetch_assoc();
        } else {
            // Si no hay interpretación específica, usar una genérica
            switch ($nivel['nombre']) {
                case 'Excepcional':
                case 'Sobresaliente':
                    $interpretation = [
                        'descripcion' => 'Nivel destacado en esta competencia. Representa una fortaleza significativa que puede ser aprovechada en roles de liderazgo o que requieran excelencia en esta área.',
                        'implicacion_laboral' => 'Ideal para posiciones que demanden un alto rendimiento en esta competencia. Puede servir como mentor o referente para otros.'
                    ];
                    break;
                case 'Notable':
                case 'Adecuado':
                    $interpretation = [
                        'descripcion' => 'Buen nivel de desarrollo en esta competencia. Representa una fortaleza que puede ser aprovechada en la mayoría de los contextos laborales.',
                        'implicacion_laboral' => 'Apropiado para roles que requieran un nivel sólido en esta área. Tiene potencial para seguir desarrollándose con la práctica y experiencia adecuadas.'
                    ];
                    break;
                case 'Moderado':
                    $interpretation = [
                        'descripcion' => 'Nivel medio en esta competencia. No representa una debilidad significativa, pero tampoco destaca como una fortaleza particular.',
                        'implicacion_laboral' => 'Puede desempeñarse adecuadamente en roles que no exijan un nivel excepcional en esta área. Se beneficiaría de desarrollo adicional si esta competencia es importante para el puesto objetivo.'
                    ];
                    break;
                case 'En desarrollo':
                case 'Incipiente':
                    $interpretation = [
                        'descripcion' => 'Área de oportunidad que requiere desarrollo. Puede representar un desafío en roles que demanden un nivel alto en esta competencia.',
                        'implicacion_laboral' => 'Se recomienda un plan de desarrollo específico si esta competencia es crucial para el desempeño en el puesto objetivo. Considerar posiciones donde esta competencia no sea crítica mientras se desarrolla.'
                    ];
                    break;
                default:
                    $interpretation = [
                        'descripcion' => 'No hay una interpretación específica disponible para este nivel.',
                        'implicacion_laboral' => 'Consulte con un especialista en evaluación para obtener más información.'
                    ];
            }
        }
    }
}

// Generar implicaciones laborales si no existen en la interpretación
$job_implications = [];
if ($interpretation && !empty($interpretation['implicacion_laboral'])) {
    $job_implications = explode("\n", $interpretation['implicacion_laboral']);
} else {
    // Obtener el índice para generar implicaciones genéricas
    $sql = "SELECT * FROM indices_compuestos WHERE id = $index_id LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $indice = $result->fetch_assoc();
        $nombreIndice = strtolower($indice['nombre']);
        
        // Generar implicaciones basadas en el tipo de índice
        if (strpos($nombreIndice, 'capacidad analítica') !== false || 
            strpos($nombreIndice, 'razonamiento') !== false) {
            $job_implications = [
                'Adecuado para roles que requieren análisis de datos y resolución de problemas complejos.',
                'Puede destacar en investigación, planificación estratégica y toma de decisiones basadas en datos.',
                'Considerar posiciones en análisis de datos, investigación, estrategia o consultoría.'
            ];
        } elseif (strpos($nombreIndice, 'habilidad comunicativa') !== false || 
                 strpos($nombreIndice, 'comunicación') !== false) {
            $job_implications = [
                'Apropiado para roles que demandan interacción frecuente con personas y presentación de ideas.',
                'Puede destacar en ventas, atención al cliente, relaciones públicas o formación.',
                'Considerar posiciones donde la comunicación clara sea un factor clave de éxito.'
            ];
        } elseif (strpos($nombreIndice, 'colaboración') !== false || 
                 strpos($nombreIndice, 'trabajo en equipo') !== false) {
            $job_implications = [
                'Adecuado para entornos colaborativos donde el trabajo en equipo sea fundamental.',
                'Puede contribuir positivamente en roles que requieran coordinación entre diferentes áreas.',
                'Considerar posiciones en gestión de proyectos, trabajo multidisciplinar o atención al cliente.'
            ];
        } elseif (strpos($nombreIndice, 'liderazgo') !== false) {
            $job_implications = [
                'Potencial para asumir responsabilidades de liderazgo o coordinación de equipos.',
                'Puede destacar en la dirección de proyectos o gestión de personas.',
                'Considerar roles con componente de supervisión, mentoría o gestión.'
            ];
        } elseif (strpos($nombreIndice, 'innovación') !== false || 
                 strpos($nombreIndice, 'creatividad') !== false) {
            $job_implications = [
                'Adecuado para entornos que valoren el pensamiento creativo y las soluciones innovadoras.',
                'Puede aportar ideas frescas y enfoques originales a los desafíos.',
                'Considerar roles en desarrollo de productos, marketing o mejora de procesos.'
            ];
        } elseif (strpos($nombreIndice, 'meticulosidad') !== false || 
                 strpos($nombreIndice, 'atención al detalle') !== false) {
            $job_implications = [
                'Adecuado para roles que requieren precisión, exactitud y seguimiento de procedimientos.',
                'Puede destacar en control de calidad, auditoría o administración.',
                'Considerar posiciones donde los errores tengan un alto costo o impacto.'
            ];
        } elseif (strpos($nombreIndice, 'resiliencia') !== false || 
                 strpos($nombreIndice, 'adaptabilidad') !== false) {
            $job_implications = [
                'Apropiado para entornos cambiantes o de alta presión.',
                'Puede afrontar eficazmente situaciones de crisis o cambios organizacionales.',
                'Considerar roles en startups, gestión de cambio o atención a crisis.'
            ];
        } else {
            // Implicaciones genéricas
            $job_implications = [
                'Esta competencia puede ser relevante en diversos contextos laborales.',
                'Evaluar la importancia de esta competencia para el rol específico considerado.',
                'Consultar con un especialista en selección para determinar la idoneidad para posiciones concretas.'
            ];
        }
    }
}

// Preparar el resultado
$result = [
    'components' => $components,
    'interpretation' => $interpretation,
    'job_implications' => $job_implications
];

// Devolver el resultado en formato JSON
header('Content-Type: application/json');
echo json_encode($result);