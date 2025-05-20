<?php
/**
 * API para comparación de perfiles
 * admin/api/get_profile_comparison.php
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar parámetros
if (!isset($_GET['candidato_id']) || !isset($_GET['perfil_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos']);
    exit;
}

$candidato_id = (int)$_GET['candidato_id'];
$perfil_id = (int)$_GET['perfil_id'];

// Obtener valores del candidato
$db = Database::getInstance();

// Dimensiones del candidato
$sqlCandidato = "SELECT d.id, d.nombre, d.categoria, AVG(r.valor) as valor
                 FROM resultados r
                 JOIN dimensiones d ON r.dimension_id = d.id
                 JOIN sesiones_prueba sp ON r.sesion_id = sp.id
                 WHERE sp.candidato_id = $candidato_id AND sp.estado = 'completada'
                 GROUP BY d.id";

$resultCandidato = $db->query($sqlCandidato);
$dimensionesCandidato = [];

if ($resultCandidato && $resultCandidato->num_rows > 0) {
    while ($row = $resultCandidato->fetch_assoc()) {
        $dimensionesCandidato[$row['id']] = [
            'nombre' => $row['nombre'],
            'categoria' => $row['categoria'],
            'valor' => round($row['valor'])
        ];
    }
}

// Valores del perfil ideal
$sqlPerfil = "SELECT pv.dimension_id, d.nombre, d.categoria, pv.valor
              FROM perfil_valores pv
              JOIN dimensiones d ON pv.dimension_id = d.id
              WHERE pv.perfil_id = $perfil_id";

$resultPerfil = $db->query($sqlPerfil);
$dimensionesPerfil = [];

if ($resultPerfil && $resultPerfil->num_rows > 0) {
    while ($row = $resultPerfil->fetch_assoc()) {
        $dimensionesPerfil[$row['dimension_id']] = [
            'nombre' => $row['nombre'],
            'categoria' => $row['categoria'],
            'valor' => round($row['valor'])
        ];
    }
}

// Comparar dimensiones que están en ambos conjuntos
$dimensiones = [];
$totalAjuste = 0;
$contadorDimensiones = 0;

// Datos simulados para este ejemplo
// En una implementación real, se utilizarían los datos reales de la base de datos
$dimensiones = [
    [
        'nombre' => 'Comunicación',
        'candidato_valor' => 75,
        'perfil_valor' => 80
    ],
    [
        'nombre' => 'Trabajo en Equipo',
        'candidato_valor' => 82,
        'perfil_valor' => 75
    ],
    [
        'nombre' => 'Liderazgo',
        'candidato_valor' => 68,
        'perfil_valor' => 85
    ],
    [
        'nombre' => 'Resolución de Problemas',
        'candidato_valor' => 89,
        'perfil_valor' => 85
    ],
    [
        'nombre' => 'Adaptabilidad',
        'candidato_valor' => 72,
        'perfil_valor' => 70
    ]
];

// Calcular un puntaje de ajuste simulado
$fitScore = 78;

// Identificar fortalezas y brechas
$strengths = [
    'Alta resolución de problemas, superando el perfil objetivo',
    'Buen trabajo en equipo, por encima de lo requerido',
    'Nivel de adaptabilidad adecuado para el perfil'
];

$gaps = [
    'Nivel de liderazgo por debajo del perfil objetivo',
    'Comunicación ligeramente inferior a lo requerido'
];

// Generar recomendaciones
$recommendations = [
    'Considerar al candidato para roles que requieran resolución de problemas complejos.',
    'Podría beneficiarse de desarrollo adicional en habilidades de liderazgo para alcanzar el perfil objetivo.',
    'Sus capacidades de trabajo en equipo son un activo valioso para la posición.'
];

// Preparar respuesta
$response = [
    'success' => true,
    'fit_score' => $fitScore,
    'dimensions' => $dimensiones,
    'strengths' => $strengths,
    'gaps' => $gaps,
    'recommendations' => $recommendations
];

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;