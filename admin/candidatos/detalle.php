<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/detalle.php - Ver detalles de un candidato (Versión mejorada)
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
    header('Location: ../login.php');
    exit;
}

// Verificar que se proporciona un ID de candidato
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de candidato no proporcionado";
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Instanciar gestores
$candidateManager = new CandidateManager();
$applicationManager = new ApplicationManager();

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

// Obtener aplicaciones del candidato
$db = Database::getInstance();
$candidato_id = (int)$candidato_id;
$sql = "SELECT a.*, v.titulo as vacante_titulo 
        FROM aplicaciones a 
        JOIN vacantes v ON a.vacante_id = v.id 
        WHERE a.candidato_id = $candidato_id 
        ORDER BY a.fecha_aplicacion DESC";
$result = $db->query($sql);
$aplicaciones = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $aplicaciones[] = $row;
    }
}

// Obtener notas del candidato usando función mejorada
$notas = getCandidateNotesWithUsernames($candidato_id);

// Función mejorada para obtener notas del candidato
function getCandidateNotesWithUsernames($candidato_id) {
    $db = Database::getInstance();
    $candidato_id = (int)$candidato_id;
    
    $sql = "SELECT n.*, 
                 COALESCE(u.nombre, 'Usuario desconocido') as usuario_nombre 
            FROM notas_candidatos n 
            LEFT JOIN usuarios u ON n.usuario_id = u.id 
            WHERE n.candidato_id = $candidato_id 
            ORDER BY n.created_at DESC";
    
    $result = $db->query($sql);
    $notas = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notas[] = $row;
        }
    }
    
    return $notas;
}

// Verificar si existe el TestManager para obtener resultados de pruebas
$testManager = null;
$hasTestManager = false;
$pruebasCompletadas = [];
$evaluationResults = [];
$perfilPsicometrico = [];
$promedioResultados = 0;

if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
        
        // Obtener pruebas completadas por el candidato
        try {
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Calcular promedio de resultados y determinar perfil psicométrico
            if (!empty($pruebasCompletadas)) {
                $totalResultados = 0;
                $countResultados = 0;
                
                foreach ($pruebasCompletadas as $prueba) {
                    if (isset($prueba['resultado_global']) && is_numeric($prueba['resultado_global'])) {
                        $totalResultados += $prueba['resultado_global'];
                        $countResultados++;
                    }
                }
                
                if ($countResultados > 0) {
                    $promedioResultados = round($totalResultados / $countResultados);
                    
                    // Determinar perfil psicométrico
                    if ($promedioResultados >= 90) {
                        $perfilPsicometrico = [
                            'tipo' => 'Excepcional',
                            'descripcion' => 'Candidato con habilidades y competencias excepcionales en todas las áreas evaluadas.',
                            'fortalezas' => ['Capacidad analítica superior', 'Excelente comunicación', 'Toma de decisiones efectiva'],
                            'recomendaciones' => ['Posiciones de liderazgo', 'Roles con alta responsabilidad', 'Proyectos estratégicos'],
                            'class' => 'success'
                        ];
                    } elseif ($promedioResultados >= 75) {
                        $perfilPsicometrico = [
                            'tipo' => 'Sobresaliente',
                            'descripcion' => 'Candidato con alto nivel de competencias y habilidades bien desarrolladas.',
                            'fortalezas' => ['Buena capacidad analítica', 'Comunicación efectiva', 'Habilidades de resolución de problemas'],
                            'recomendaciones' => ['Posiciones técnicas avanzadas', 'Roles con responsabilidad moderada', 'Oportunidades de crecimiento'],
                            'class' => 'primary'
                        ];
                    } elseif ($promedioResultados >= 60) {
                        $perfilPsicometrico = [
                            'tipo' => 'Competente',
                            'descripcion' => 'Candidato con buen nivel de habilidades, con áreas destacadas y otras por desarrollar.',
                            'fortalezas' => ['Habilidades técnicas adecuadas', 'Trabajo en equipo', 'Capacidad de aprendizaje'],
                            'recomendaciones' => ['Posiciones técnicas intermedias', 'Entrenamiento específico en áreas más débiles', 'Mentoría'],
                            'class' => 'info'
                        ];
                    } elseif ($promedioResultados >= 40) {
                        $perfilPsicometrico = [
                            'tipo' => 'En desarrollo',
                            'descripcion' => 'Candidato con potencial, pero con áreas específicas que requieren desarrollo.',
                            'fortalezas' => ['Motivación para aprender', 'Adaptabilidad', 'Actitud positiva'],
                            'recomendaciones' => ['Posiciones juniors', 'Programas de entrenamiento', 'Seguimiento cercano'],
                            'class' => 'warning'
                        ];
                    } else {
                        $perfilPsicometrico = [
                            'tipo' => 'Inicial',
                            'descripcion' => 'Candidato en etapas iniciales de desarrollo de las habilidades evaluadas.',
                            'fortalezas' => ['Potencial de crecimiento', 'Disposición al aprendizaje'],
                            'recomendaciones' => ['Posiciones iniciales', 'Entrenamiento intensivo', 'Evaluación periódica de progreso'],
                            'class' => 'danger'
                        ];
                    }
                }
                
                // Obtener resultados por dimensiones (habilidades)
                $dimensionsQuery = "SELECT d.nombre, AVG(r.valor) as promedio, 
                                    CASE 
                                        WHEN AVG(r.valor) >= 90 THEN 'Alto' 
                                        WHEN AVG(r.valor) >= 60 THEN 'Medio' 
                                        ELSE 'Bajo' 
                                    END as nivel
                                    FROM resultados r
                                    JOIN dimensiones d ON r.dimension_id = d.id
                                    JOIN sesiones_prueba s ON r.sesion_id = s.id
                                    WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                                    GROUP BY d.id
                                    ORDER BY promedio DESC";
                
                $result = $db->query($dimensionsQuery);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $evaluationResults[] = $row;
                    }
                }
            }
        } catch (Exception $e) {
            // Si hay error, registrarlo pero continuar
            error_log("Error al obtener resultados de pruebas: " . $e->getMessage());
        }
    }
}

// Obtener si se solicita impresión
$isPrintMode = isset($_GET['print']) && $_GET['print'] == 1;

// Determinar la pestaña activa
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'resumen';

// Función para obtener y calcular los índices compuestos
function getIndicesCompuestos($candidato_id) {
    $db = Database::getInstance();
    $candidato_id = (int)$candidato_id;
    
    // Consulta para obtener los índices compuestos principales
    $sql = "SELECT id, nombre, descripcion FROM indices_compuestos 
            WHERE id IN (SELECT MIN(id) FROM indices_compuestos GROUP BY nombre) 
            ORDER BY id";
    
    $result = $db->query($sql);
    $indices = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Por cada índice, calculamos su valor basado en las dimensiones evaluadas
            $indice_id = $row['id'];
            $valor = calcularIndiceCompuesto($indice_id, $candidato_id);
            
            // Asignar clase visual según el valor
            $class = asignarClaseVisualizacion($valor);
            
            $indices[] = [
                'id' => $indice_id,
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'valor' => $valor,
                'class' => $class
            ];
        }
    }
    
    return $indices;
}

// Función para calcular el valor de un índice compuesto
function calcularIndiceCompuesto($indice_id, $candidato_id) {
    $db = Database::getInstance();
    $indice_id = (int)$indice_id;
    $candidato_id = (int)$candidato_id;
    
    // Obtener componentes del índice
    $sql = "SELECT ic.origen_tipo, ic.origen_id, ic.ponderacion 
            FROM indices_componentes ic 
            WHERE ic.indice_id = $indice_id 
            ORDER BY ic.id";
    
    $result = $db->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        // Si no hay componentes definidos, retornar valor por defecto
        return 0;
    }
    
    $totalValor = 0;
    $totalPonderacion = 0;
    
    while ($componente = $result->fetch_assoc()) {
        $valor_componente = 0;
        
        if ($componente['origen_tipo'] === 'dimension') {
            // Si es una dimensión, obtener el resultado de evaluación
            $dimension_id = (int)$componente['origen_id'];
            $sql_valor = "SELECT AVG(r.valor) as promedio 
                          FROM resultados r 
                          JOIN sesiones_prueba s ON r.sesion_id = s.id 
                          WHERE r.dimension_id = $dimension_id 
                          AND s.candidato_id = $candidato_id 
                          AND s.estado = 'completada'";
            
            $result_valor = $db->query($sql_valor);
            
            if ($result_valor && $result_valor->num_rows > 0) {
                $row_valor = $result_valor->fetch_assoc();
                $valor_componente = !is_null($row_valor['promedio']) ? floatval($row_valor['promedio']) : 0;
            }
        } else if ($componente['origen_tipo'] === 'indice') {
            // Si es otro índice, llamada recursiva
            $indice_origen_id = (int)$componente['origen_id'];
            $valor_componente = calcularIndiceCompuesto($indice_origen_id, $candidato_id);
        }
        
        $ponderacion = floatval($componente['ponderacion']);
        $totalValor += $valor_componente * $ponderacion;
        $totalPonderacion += $ponderacion;
    }
    
    // Si no hay ponderación total (error en datos), retornar 0
    if ($totalPonderacion == 0) {
        return 0;
    }
    
    // Calcular promedio ponderado y redondear
    return round($totalValor / $totalPonderacion);
}

// Función para asignar clase visual según el valor
function asignarClaseVisualizacion($valor) {
    if ($valor >= 90) return 'success';
    else if ($valor >= 80) return 'primary';
    else if ($valor >= 70) return 'info';
    else if ($valor >= 60) return 'primary';
    else if ($valor >= 50) return 'warning';
    else return 'danger';
}

// Obtener índices compuestos para mostrar en el perfil
$indicesCompuestos = getIndicesCompuestos($candidato_id);

// Título de la página
$pageTitle = 'Perfil del Candidato - Panel de Administración';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="css/candidato-detalle.css">
    <link rel="stylesheet" href="css/candidato-resultados.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if (!$isPrintMode): ?>
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-main">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="no-print">
                    <?php include '../includes/sidebar.php'; ?>
                </div>
    <?php endif; ?>
                
                <main class="<?php echo !$isPrintMode ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : ''; ?>">
                    <?php if (!$isPrintMode): ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                        <h1 class="h2">Perfil del Candidato</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <a href="editar.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-info me-2">
                                <i class="fas fa-chart-bar"></i> Resultados Detallados
                            </a>
                            <a href="detalle.php?id=<?php echo $candidato_id; ?>&print=1" target="_blank" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-print"></i> Imprimir
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Encabezado del perfil - Estilo moderno -->
                    <div class="profile-header">
                        <?php if (!$isPrintMode): ?>
                        <div class="profile-actions no-print">
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item" href="editar.php?id=<?php echo $candidato_id; ?>"><i class="fas fa-edit me-2"></i> Editar perfil</a></li>
                                    <?php if (!empty($candidato['cv_path'])): ?>
                                    <li><a class="dropdown-item" href="<?php echo '../../uploads/resumes/' . $candidato['cv_path']; ?>" target="_blank"><i class="fas fa-file-pdf me-2"></i> Ver CV</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addNoteModal"><i class="fas fa-sticky-note me-2"></i> Agregar nota</a></li>
                                    <li><a class="dropdown-item" href="resultados.php?id=<?php echo $candidato_id; ?>"><i class="fas fa-chart-bar me-2"></i> Ver resultados detallados</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deactivateModal"><i class="fas fa-user-slash me-2"></i> Desactivar cuenta</a></li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="profile-header-top">
                            <div class="profile-image">
                                <?php if (!empty($candidato['foto_path'])): ?>
                                <img src="<?php echo '../../uploads/profile_photos/' . $candidato['foto_path']; ?>" alt="Foto de perfil">
                                <?php else: ?>
                                <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-info">
                                <h1 class="profile-name"><?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?></h1>
                                
                                <div>
                                    <?php 
                                    // Mostrar título basado en experiencia general o nivel educativo
                                    $titulo = '';
                                    
                                    if (!empty($candidato['experiencia_general'])) {
                                        switch ($candidato['experiencia_general']) {
                                            case 'sin-experiencia':
                                                $titulo = 'Sin experiencia previa';
                                                break;
                                            case 'menos-1':
                                                $titulo = 'Menos de 1 año de experiencia';
                                                break;
                                            case '1-3':
                                                $titulo = '1-3 años de experiencia';
                                                break;
                                            case '3-5':
                                                $titulo = '3-5 años de experiencia';
                                                break;
                                            case '5-10':
                                                $titulo = '5-10 años de experiencia';
                                                break;
                                            case 'mas-10':
                                                $titulo = 'Más de 10 años de experiencia';
                                                break;
                                        }
                                    }
                                    
                                    if (empty($titulo) && !empty($candidato['nivel_educativo'])) {
                                        switch ($candidato['nivel_educativo']) {
                                            case 'bachiller':
                                                $titulo = 'Bachiller';
                                                break;
                                            case 'tecnico':
                                                $titulo = 'Técnico';
                                                break;
                                            case 'grado':
                                                $titulo = 'Graduado Universitario';
                                                break;
                                            case 'postgrado':
                                                $titulo = 'Postgrado';
                                                break;
                                            case 'maestria':
                                                $titulo = 'Maestría';
                                                break;
                                            case 'doctorado':
                                                $titulo = 'Doctorado';
                                                break;
                                        }
                                    }
                                    
                                    echo $titulo ? '<div class="profile-title">' . $titulo . '</div>' : '<div class="profile-title">Candidato</div>';
                                    ?>
                                </div>
                                
                                <!-- Fecha de evaluación -->
                                <?php if ($hasTestManager && !empty($pruebasCompletadas)): ?>
                                <div>Evaluado el: <strong><?php echo date('d/m/Y', strtotime($pruebasCompletadas[0]['fecha_fin'])); ?></strong></div>
                                <?php endif; ?>
                                
                                <!-- Compatibilidad si existe perfilPsicometrico -->
                                <?php if (!empty($perfilPsicometrico)): ?>
                                <div class="profile-match">
                                    <span class="match-icon">✓</span>
                                    <?php echo $promedioResultados; ?>% de compatibilidad con puestos de <?php echo $perfilPsicometrico['tipo']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="profile-contact">
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i> <?php echo $candidato['email']; ?>
                                    </div>
                                    
                                    <?php if (!empty($candidato['telefono'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i> <?php echo $candidato['telefono']; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['ubicacion'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo $candidato['ubicacion']; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['linkedin'])): ?>
                                    <div class="contact-item">
                                        <i class="fab fa-linkedin"></i> <a href="<?php echo $candidato['linkedin']; ?>" target="_blank">Perfil LinkedIn</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Insignias del perfil -->
                                <div class="profile-badges">
                                    <?php if (!empty($perfilPsicometrico)): ?>
                                    <div class="profile-badge <?php echo $perfilPsicometrico['class']; ?>">
                                        <i class="fas fa-chart-bar me-1"></i> Perfil: <?php echo $perfilPsicometrico['tipo']; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['modalidad_preferida'])): ?>
                                    <div class="profile-badge primary">
                                        <?php 
                                        $modalidad = '';
                                        switch ($candidato['modalidad_preferida']) {
                                            case 'presencial':
                                                $modalidad = 'Presencial';
                                                break;
                                            case 'remoto':
                                                $modalidad = 'Remoto';
                                                break;
                                            case 'hibrido':
                                                $modalidad = 'Híbrido';
                                                break;
                                        }
                                        echo $modalidad;
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['tipo_contrato_preferido'])): ?>
                                    <div class="profile-badge primary">
                                        <?php 
                                        $contrato = '';
                                        switch ($candidato['tipo_contrato_preferido']) {
                                            case 'tiempo_completo':
                                                $contrato = 'Tiempo completo';
                                                break;
                                            case 'tiempo_parcial':
                                                $contrato = 'Tiempo parcial';
                                                break;
                                            case 'proyecto':
                                                $contrato = 'Por proyecto';
                                                break;
                                            case 'temporal':
                                                $contrato = 'Temporal';
                                                break;
                                        }
                                        echo $contrato;
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['disponibilidad'])): ?>
                                    <div class="profile-badge primary">
                                        <?php 
                                        $disponibilidad = '';
                                        switch ($candidato['disponibilidad']) {
                                            case 'inmediata':
                                                $disponibilidad = 'Disponibilidad inmediata';
                                                break;
                                            case '2-semanas':
                                                $disponibilidad = 'Disponibilidad en 2 semanas';
                                                break;
                                            case '1-mes':
                                                $disponibilidad = 'Disponibilidad en 1 mes';
                                                break;
                                            case 'mas-1-mes':
                                                $disponibilidad = 'Disponibilidad en más de 1 mes';
                                                break;
                                        }
                                        echo $disponibilidad;
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (count($aplicaciones) > 0): ?>
                                    <div class="profile-badge warning">
                                        <i class="fas fa-briefcase me-1"></i> <?php echo count($aplicaciones); ?> Aplicaciones
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($hasTestManager && count($pruebasCompletadas) > 0): ?>
                                    <div class="profile-badge info">
                                        <i class="fas fa-clipboard-check me-1"></i> <?php echo count($pruebasCompletadas); ?> Evaluaciones
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestañas - Estilo moderno -->
                    <div class="tabs-container">
                        <div class="tabs">
                            <div class="tab <?php echo $activeTab == 'resumen' ? 'active' : ''; ?>" data-tab="resumen">
                                <i class="fas fa-th-large me-2"></i>Resumen
                            </div>
                            <div class="tab <?php echo $activeTab == 'evaluaciones' ? 'active' : ''; ?>" data-tab="evaluaciones">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluaciones
                            </div>
                            <div class="tab <?php echo $activeTab == 'competencias' ? 'active' : ''; ?>" data-tab="competencias">
                                <i class="fas fa-chart-bar me-2"></i>Competencias
                            </div>
                            <div class="tab <?php echo $activeTab == 'aplicaciones' ? 'active' : ''; ?>" data-tab="aplicaciones">
                                <i class="fas fa-briefcase me-2"></i>Aplicaciones
                            </div>
                            <div class="tab <?php echo $activeTab == 'notas' ? 'active' : ''; ?>" data-tab="notas">
                                <i class="fas fa-sticky-note me-2"></i>Notas
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido de las pestañas -->
                    <!-- PESTAÑA RESUMEN -->
                    <div class="tab-content <?php echo $activeTab == 'resumen' ? 'active' : ''; ?>" id="resumen">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Resumen profesional -->
                                <?php if (!empty($candidato['resumen'])): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-file-alt me-2"></i> Resumen profesional</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <p><?php echo nl2br(htmlspecialchars($candidato['resumen'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Evaluación general si hay resultados -->
                                <?php if ($hasTestManager && !empty($pruebasCompletadas) && $promedioResultados > 0): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-chart-pie me-2"></i> Evaluación General</div>
                                        <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-5 text-center">
                                                <div class="gauge-container">
                                                    <canvas id="evaluationGauge" width="200" height="120"></canvas>
                                                    <div class="gauge-value"><?php echo $promedioResultados; ?>%</div>
                                                </div>
                                                <h5 class="mt-2 <?php echo 'text-' . $perfilPsicometrico['class']; ?>"><?php echo $perfilPsicometrico['tipo']; ?></h5>
                                            </div>
                                            <div class="col-md-7">
                                                <p><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                                <?php if (!empty($perfilPsicometrico['fortalezas'])): ?>
                                                <h6 class="mt-3">Fortalezas principales:</h6>
                                                <ul class="mb-0">
                                                    <?php foreach ($perfilPsicometrico['fortalezas'] as $fortaleza): ?>
                                                    <li><?php echo $fortaleza; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Aplicaciones recientes -->
                                <?php if (!empty($aplicaciones)): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-briefcase me-2"></i> Aplicaciones recientes</div>
                                        <?php if (!$isPrintMode && count($aplicaciones) > 3): ?>
                                        <a href="#" class="btn btn-sm btn-outline-primary no-print tab-link" data-tab="aplicaciones">
                                            Ver todas
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Vacante</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <?php if (!$isPrintMode): ?>
                                                        <th class="no-print">Acciones</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Mostrar solo las 3 aplicaciones más recientes
                                                    $recentAplicaciones = array_slice($aplicaciones, 0, 3);
                                                    foreach ($recentAplicaciones as $aplicacion): 
                                                        // Determinar clases para el estado
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = ucfirst($aplicacion['estado']);
                                                        
                                                        switch($aplicacion['estado']) {
                                                            case 'recibida':
                                                                $statusClass = 'bg-info';
                                                                $statusText = 'Recibida';
                                                                break;
                                                            case 'revision':
                                                                $statusClass = 'bg-primary';
                                                                $statusText = 'En revisión';
                                                                break;
                                                            case 'entrevista':
                                                                $statusClass = 'bg-warning';
                                                                $statusText = 'Entrevista';
                                                                break;
                                                            case 'seleccionado':
                                                                $statusClass = 'bg-success';
                                                                $statusText = 'Seleccionado';
                                                                break;
                                                            case 'rechazado':
                                                                $statusClass = 'bg-danger';
                                                                $statusText = 'Rechazado';
                                                                break;
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!$isPrintMode): ?>
                                                            <a href="../vacantes/vacante-editar.php?id=<?php echo $aplicacion['vacante_id']; ?>" class="fw-bold text-decoration-none">
                                                                <?php echo $aplicacion['vacante_titulo']; ?>
                                                            </a>
                                                            <?php else: ?>
                                                            <span class="fw-bold"><?php echo $aplicacion['vacante_titulo']; ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                        <?php if (!$isPrintMode): ?>
                                                        <td class="no-print">
                                                            <a href="../aplicaciones/detalle.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Índices Compuestos Mejorados -->
                                <?php if (!empty($indicesCompuestos)): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-cubes me-2"></i> Índices Compuestos</div>
                                        <?php if (!$isPrintMode): ?>
                                        <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="row">
                                            <?php foreach ($indicesCompuestos as $indice): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0" title="<?php echo htmlspecialchars($indice['descripcion']); ?>"><?php echo htmlspecialchars($indice['nombre']); ?></h6>
                                                        <span class="badge bg-<?php echo $indice['class']; ?>"><?php echo $indice['valor']; ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-<?php echo $indice['class']; ?>" role="progressbar" 
                                                             style="width: <?php echo $indice['valor']; ?>%;" 
                                                             aria-valuenow="<?php echo $indice['valor']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <p class="small text-muted">
                                                Estos índices representan una combinación ponderada de diferentes dimensiones evaluadas
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Información personal y detalles -->
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-user me-2"></i> Información personal</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <ul class="list-group list-group-flush">
                                            <?php if (!empty($candidato['fecha_nacimiento'])): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Fecha de nacimiento</span>
                                                <span><?php echo date('d/m/Y', strtotime($candidato['fecha_nacimiento'])); ?></span>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($candidato['genero'])): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Género</span>
                                                <span><?php echo ucfirst($candidato['genero']); ?></span>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Nivel educativo</span>
                                                <span>
                                                    <?php 
                                                    switch ($candidato['nivel_educativo']) {
                                                        case 'bachiller':
                                                            echo 'Bachiller';
                                                            break;
                                                        case 'tecnico':
                                                            echo 'Técnico';
                                                            break;
                                                        case 'grado':
                                                            echo 'Grado Universitario';
                                                            break;
                                                        case 'postgrado':
                                                            echo 'Postgrado';
                                                            break;
                                                        case 'maestria':
                                                            echo 'Maestría';
                                                            break;
                                                        case 'doctorado':
                                                            echo 'Doctorado';
                                                            break;
                                                        default:
                                                            echo 'No especificado';
                                                    }
                                                    ?>
                                                </span>
                                            </li>
                                            
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Experiencia general</span>
                                                <span>
                                                    <?php 
                                                    switch ($candidato['experiencia_general']) {
                                                        case 'sin-experiencia':
                                                            echo 'Sin experiencia';
                                                            break;
                                                        case 'menos-1':
                                                            echo 'Menos de 1 año';
                                                            break;
                                                        case '1-3':
                                                            echo '1-3 años';
                                                            break;
                                                        case '3-5':
                                                            echo '3-5 años';
                                                            break;
                                                        case '5-10':
                                                            echo '5-10 años';
                                                            break;
                                                        case 'mas-10':
                                                            echo 'Más de 10 años';
                                                            break;
                                                        default:
                                                            echo 'No especificada';
                                                    }
                                                    ?>
                                                </span>
                                            </li>
                                            
                                            <?php if (!empty($candidato['salario_esperado'])): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Salario esperado</span>
                                                <span><?php echo $candidato['salario_esperado']; ?></span>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($candidato['ubicacion_preferida'])): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Ubicación preferida</span>
                                                <span><?php echo $candidato['ubicacion_preferida']; ?></span>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Fecha de registro</span>
                                                <span><?php echo date('d/m/Y', strtotime($candidato['created_at'])); ?></span>
                                            </li>
                                            
                                            <?php if (!empty($candidato['last_login'])): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Último acceso</span>
                                                <span><?php echo date('d/m/Y H:i', strtotime($candidato['last_login'])); ?></span>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Resultados de evaluaciones si existen -->
                                <?php if ($hasTestManager && !empty($pruebasCompletadas)): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-chart-pie me-2"></i> Evaluaciones</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="text-center mb-4">
                                            <div class="d-inline-block position-relative">
                                                <canvas id="scoreChart" width="180" height="180"></canvas>
                                                <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                    <h3 class="mb-0"><?php echo $promedioResultados; ?>%</h3>
                                                    <div class="small text-muted">Promedio</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($evaluationResults)): ?>
                                        <h6 class="fw-bold mb-3">Resultados principales</h6>
                                        
                                        <?php 
                                        // Mostrar solo los 4 primeros resultados
                                        $topResults = array_slice($evaluationResults, 0, 4);
                                        foreach ($topResults as $dimension): 
                                        ?>
                                        <div class="dimension-item">
                                            <div class="dimension-header">
                                                <h6 class="dimension-title"><?php echo htmlspecialchars($dimension['nombre']); ?></h6>
                                                <span class="dimension-score"><?php echo round($dimension['promedio']); ?>%</span>
                                            </div>
                                            <div class="dimension-bar">
                                                <?php
                                                $score = round($dimension['promedio']);
                                                $class = 'low';
                                                
                                                if ($score >= 75) {
                                                    $class = 'high';
                                                } elseif ($score >= 60) {
                                                    $class = 'medium';
                                                }
                                                ?>
                                                <div class="dimension-progress <?php echo $class; ?>" style="width: <?php echo $score; ?>%"></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($perfilPsicometrico)): ?>
                                        <div class="mt-4">
                                            <h6 class="fw-bold">Perfil: <?php echo $perfilPsicometrico['tipo']; ?></h6>
                                            <p class="small mb-0"><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-center mt-3">
                                            <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-chart-bar"></i> Ver dashboard completo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Habilidades -->
                                <?php if (!empty($candidato['habilidades_destacadas'])): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-tools me-2"></i> Habilidades</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="skills-container">
                                            <?php 
                                            $habilidades_array = explode(',', $candidato['habilidades_destacadas']);
                                            foreach ($habilidades_array as $habilidad):
                                                $habilidad = trim($habilidad);
                                                // Determinar tipo de habilidad aleatoriamente para demo
                                                $tipos = ['tech', 'soft', 'lang'];
                                                $tipo = $tipos[array_rand($tipos)];
                                                
                                                $badgeClass = '';
                                                switch ($tipo) {
                                                    case 'tech':
                                                        $badgeClass = 'info';
                                                        break;
                                                    case 'soft':
                                                        $badgeClass = 'success';
                                                        break;
                                                    case 'lang':
                                                        $badgeClass = 'primary';
                                                        break;
                                                }
                                            ?>
                                            <div class="profile-badge <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($habilidad); ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Acciones solo para modo admin -->
                                <?php if (!$isPrintMode): ?>
                                <div class="profile-section no-print">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-cog me-2"></i> Acciones</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="d-grid gap-2">
                                            <a href="editar.php?id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i> Editar perfil
                                            </a>
                                            <?php if (!empty($candidato['cv_path'])): ?>
                                            <a href="<?php echo '../../uploads/resumes/' . $candidato['cv_path']; ?>" class="btn btn-outline-info" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Ver CV
                                            </a>
                                            <?php endif; ?>
                                            <a href="mailto:<?php echo $candidato['email']; ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-envelope"></i> Contactar
                                            </a>
                                            <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-chart-bar"></i> Ver resultados detallados
                                            </a>
                                            <a href="exportar-pdf.php?candidato_id=<?php echo $candidato_id; ?>" target="_blank" class="btn btn-outline-success">
                                                <i class="fas fa-file-pdf"></i> Exportar informe
                                            </a>
                                            <a href="detalle.php?id=<?php echo $candidato_id; ?>&print=1" target="_blank" class="btn btn-outline-dark">
                                                <i class="fas fa-print"></i> Imprimir perfil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA EVALUACIONES -->
                    <div class="tab-content <?php echo $activeTab == 'evaluaciones' ? 'active' : ''; ?>" id="evaluaciones">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-clipboard-check me-2"></i> Evaluaciones Completadas</div>
                                <?php if (!$isPrintMode && $hasTestManager): ?>
                                <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-primary no-print">
                                    <i class="fas fa-plus"></i> Asignar evaluación
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="profile-section-body">
                                <?php if ($hasTestManager && !empty($pruebasCompletadas)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Prueba</th>
                                                <th>Fecha</th>
                                                <th>Resultado</th>
                                                <?php if (!$isPrintMode): ?>
                                                <th class="no-print">Acciones</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pruebasCompletadas as $prueba): 
                                                $score = isset($prueba['resultado_global']) ? $prueba['resultado_global'] : 0;
                                                
                                                // Determinar clases para el resultado
                                                $resultClass = 'bg-secondary';
                                                $resultText = 'N/A';
                                                
                                                if ($score >= 90) {
                                                    $resultClass = 'bg-success';
                                                    $resultText = 'Excepcional';
                                                } elseif ($score >= 75) {
                                                    $resultClass = 'bg-primary';
                                                    $resultText = 'Sobresaliente';
                                                } elseif ($score >= 60) {
                                                    $resultClass = 'bg-info';
                                                    $resultText = 'Adecuado';
                                                } elseif ($score >= 40) {
                                                    $resultClass = 'bg-warning';
                                                    $resultText = 'Básico';
                                                } else {
                                                    $resultClass = 'bg-danger';
                                                    $resultText = 'Limitado';
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></span>
                                                    <?php if (!empty($prueba['prueba_descripcion'])): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars(substr($prueba['prueba_descripcion'], 0, 50) . (strlen($prueba['prueba_descripcion']) > 50 ? '...' : '')); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2"><?php echo $score; ?>%</div>
                                                        <span class="badge <?php echo $resultClass; ?>"><?php echo $resultText; ?></span>
                                                    </div>
                                                </td>
                                                <?php if (!$isPrintMode): ?>
                                                <td class="no-print">
                                                    <a href="../pruebas/resultados.php?session_id=<?php echo $prueba['sesion_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Ver resultados
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Botón para resultados detallados -->
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3 no-print">
                                    <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-chart-bar"></i> Ver dashboard completo de resultados
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php elseif ($hasTestManager): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    El candidato aún no ha completado ninguna evaluación psicométrica.
                                </div>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <p>Asigne evaluaciones para obtener un perfil psicométrico completo del candidato.</p>
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar nueva evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    El módulo de evaluaciones psicométricas no está disponible.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($hasTestManager && !empty($pruebasCompletadas)): ?>
                        <!-- Resumen de resultados -->
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-chart-line me-2"></i> Resumen de Resultados</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="text-center mb-4">
                                            <div class="d-inline-block position-relative">
                                                <canvas id="evaluationScoreChart" width="220" height="220"></canvas>
                                                <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                    <h2 class="mb-0"><?php echo $promedioResultados; ?>%</h2>
                                                    <div class="text-muted">Promedio global</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <?php if (!empty($perfilPsicometrico)): ?>
                                        <div class="card h-100 border-0">
                                            <div class="card-body">
                                                <h4 class="card-title">Perfil: <?php echo $perfilPsicometrico['tipo']; ?></h4>
                                                <p class="card-text"><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                                
                                                <h5 class="mt-3">Fortalezas principales</h5>
                                                <ul class="mb-0">
                                                    <?php foreach ($perfilPsicometrico['fortalezas'] as $fortaleza): ?>
                                                    <li><?php echo $fortaleza; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="card h-100 border-0">
                                            <div class="card-body">
                                                <h4 class="card-title">Interpretación de resultados</h4>
                                                <p class="card-text">
                                                    El candidato ha completado <?php echo count($pruebasCompletadas); ?> evaluación(es) con un resultado promedio de <?php echo $promedioResultados; ?>%.
                                                    <?php if ($promedioResultados >= 75): ?>
                                                    Este puntaje indica un nivel sobresaliente de competencias y habilidades evaluadas.
                                                    <?php elseif ($promedioResultados >= 60): ?>
                                                    Este puntaje indica un nivel adecuado de competencias y habilidades evaluadas.
                                                    <?php else: ?>
                                                    Este puntaje sugiere que existen áreas de mejora en las competencias evaluadas.
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <p class="card-text mt-3">
                                                    Para obtener un perfil más completo, se recomienda completar las evaluaciones pendientes.
                                                </p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
</div>
                                </div>
                                
                                <?php if (!empty($evaluationResults)): ?>
                                <h5 class="mt-4 mb-3">Resultados por dimensión</h5>
                                
                                <div class="row">
                                <?php foreach ($evaluationResults as $index => $dimension): ?>
                                    <?php if ($index < 6): // Mostrar solo las primeras 6 dimensiones ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="dimension-item">
                                            <div class="dimension-header">
                                                <h6 class="dimension-title"><?php echo htmlspecialchars($dimension['nombre']); ?></h6>
                                                <span class="dimension-score"><?php echo round($dimension['promedio']); ?>%</span>
                                            </div>
                                            <div class="dimension-bar">
                                                <?php
                                                $score = round($dimension['promedio']);
                                                $class = 'low';
                                                
                                                if ($score >= 75) {
                                                    $class = 'high';
                                                } elseif ($score >= 60) {
                                                    $class = 'medium';
                                                }
                                                ?>
                                                <div class="dimension-progress <?php echo $class; ?>" style="width: <?php echo $score; ?>%"></div>
                                            </div>
                                            <div class="small text-muted"><?php echo $dimension['nivel']; ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($evaluationResults) > 6 && !$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                        Ver todos los resultados (<?php echo count($evaluationResults); ?> dimensiones)
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PESTAÑA COMPETENCIAS -->
                    <div class="tab-content <?php echo $activeTab == 'competencias' ? 'active' : ''; ?>" id="competencias">
                        <?php if ($hasTestManager && !empty($evaluationResults)): ?>
                        <!-- Gráfico de competencias -->
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-chart-radar me-2"></i> Perfil de Competencias</div>
                                <a href="resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                            </div>
                            <div class="profile-section-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="chart-container">
                                            <canvas id="competenciasRadarChart" height="300"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-center mb-4">
                                            <div class="gauge-container">
                                                <canvas id="competenciasGaugeChart" width="160" height="120"></canvas>
                                                <div class="gauge-value"><?php echo $promedioResultados; ?>%</div>
                                            </div>
                                            <h5 class="mt-2 <?php echo 'text-' . $perfilPsicometrico['class']; ?>"><?php echo $perfilPsicometrico['tipo']; ?></h5>
                                        </div>
                                        
                                        <?php if (!empty($perfilPsicometrico['fortalezas'])): ?>
                                        <h6>Principales fortalezas:</h6>
                                        <ul class="mb-0">
                                            <?php 
                                            $fortalezasCortas = array_slice($perfilPsicometrico['fortalezas'], 0, 3);
                                            foreach ($fortalezasCortas as $fortaleza): 
                                            ?>
                                            <li><?php echo $fortaleza; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalles de competencias -->
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-th-list me-2"></i> Competencias Evaluadas</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="row">
                                <?php foreach ($evaluationResults as $index => $dimension): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="competency-card">
                                            <div class="competency-header">
                                                <h5 class="competency-title"><?php echo htmlspecialchars($dimension['nombre']); ?></h5>
                                                <?php
                                                $score = round($dimension['promedio']);
                                                $badgeClass = 'bg-info';
                                                
                                                if ($score >= 80) {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($score >= 60) {
                                                    $badgeClass = 'bg-primary';
                                                } elseif ($score < 50) {
                                                    $badgeClass = 'bg-warning';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $score; ?>%</span>
                                            </div>
                                            <div class="competency-bar">
                                                <div class="competency-progress <?php echo $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : 'low'); ?>" style="width: <?php echo $score; ?>%"></div>
                                            </div>
                                            <div class="competency-description">
                                                <?php
                                                // Generar descripción dinámica basada en el nivel de puntuación
                                                if ($score >= 80) {
                                                    echo 'Nivel superior. Demuestra dominio consistente de esta competencia en diversas situaciones.';
                                                } elseif ($score >= 60) {
                                                    echo 'Nivel adecuado. Aplica esta competencia efectivamente en situaciones habituales.';
                                                } else {
                                                    echo 'Nivel en desarrollo. Muestra esta competencia en situaciones estructuradas y puede beneficiarse de mayor entrenamiento.';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Índices compuestos mejorados -->
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-cubes me-2"></i> Índices Compuestos</div>
                            </div>
                            <div class="profile-section-body">
                                <?php 
                                // Obtener índices compuestos para mostrar en la pestaña de competencias
                                $indicesCompuestosDetallados = getIndicesCompuestos($candidato_id);
                                ?>
                                
                                <?php if (!empty($indicesCompuestosDetallados)): ?>
                                <div id="compoundIndices">
                                    <?php foreach ($indicesCompuestosDetallados as $indice): ?>
                                    <div class="compound-index mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0" title="<?php echo htmlspecialchars($indice['descripcion']); ?>"><?php echo htmlspecialchars($indice['nombre']); ?></h6>
                                            <span class="badge bg-<?php echo $indice['class']; ?>"><?php echo $indice['valor']; ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-<?php echo $indice['class']; ?>" role="progressbar" 
                                                style="width: <?php echo $indice['valor']; ?>%;" 
                                                aria-valuenow="<?php echo $indice['valor']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <p class="small text-muted">
                                        Los índices compuestos integran resultados de diferentes pruebas para ofrecer una visión más completa 
                                        de las capacidades del candidato en áreas clave.
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay suficientes datos para calcular índices compuestos. Asigne más pruebas al candidato para generar un perfil más completo.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recomendaciones -->
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-lightbulb me-2"></i> Recomendaciones</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($perfilPsicometrico) && !empty($perfilPsicometrico['recomendaciones'])): ?>
                                <h6>Posiciones recomendadas:</h6>
                                <div class="recommendation-pills">
                                    <?php foreach ($perfilPsicometrico['recomendaciones'] as $recomendacion): ?>
                                    <span class="recommendation-pill"><?php echo $recomendacion; ?></span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <h6 class="mt-4">Áreas de mejora:</h6>
                                <ul>
                                    <?php 
                                    // Mostrar áreas de mejora para dimensiones con puntuación baja
                                    $areasDebiles = array_filter($evaluationResults, function($dim) {
                                        return $dim['promedio'] < 60;
                                    });
                                    
                                    if (!empty($areasDebiles)) {
                                        foreach (array_slice($areasDebiles, 0, 3) as $area) {
                                            echo '<li>Fortalecer ' . htmlspecialchars($area['nombre']) . ' a través de capacitación específica</li>';
                                        }
                                    } else {
                                        echo '<li>Continuar desarrollando competencias ya sólidas para alcanzar nivel de excelencia</li>';
                                        echo '<li>Buscar nuevos desafíos que permitan aplicar sus fortalezas en contextos diferentes</li>';
                                    }
                                    ?>
                                </ul>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Se requieren evaluaciones adicionales para generar recomendaciones específicas.
                                </div>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar nueva evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-chart-bar me-2"></i> Competencias</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay datos de competencias disponibles para este candidato.
                                </div>
                                
                                <?php if (!$isPrintMode && $hasTestManager): ?>
                                <div class="text-center mt-3">
                                    <p>Para obtener un perfil de competencias, asigne evaluaciones al candidato.</p>
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PESTAÑA APLICACIONES -->
                    <div class="tab-content <?php echo $activeTab == 'aplicaciones' ? 'active' : ''; ?>" id="aplicaciones">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-briefcase me-2"></i> Historial de Aplicaciones</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($aplicaciones)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Vacante</th>
                                                <th>Fecha aplicación</th>
                                                <th>Estado</th>
                                                <th>Última actualización</th>
                                                <?php if (!$isPrintMode): ?>
                                                <th class="no-print">Acciones</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            foreach ($aplicaciones as $aplicacion): 
                                                // Determinar clases para el estado
                                                $statusClass = 'bg-secondary';
                                                $statusText = ucfirst($aplicacion['estado']);
                                                
                                                switch($aplicacion['estado']) {
                                                    case 'recibida':
                                                        $statusClass = 'bg-info';
                                                        $statusText = 'Recibida';
                                                        break;
                                                    case 'revision':
                                                        $statusClass = 'bg-primary';
                                                        $statusText = 'En revisión';
                                                        break;
                                                    case 'entrevista':
                                                        $statusClass = 'bg-warning';
                                                        $statusText = 'Entrevista';
                                                        break;
                                                    case 'seleccionado':
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Seleccionado';
                                                        break;
                                                    case 'rechazado':
                                                        $statusClass = 'bg-danger';
                                                        $statusText = 'Rechazado';
                                                        break;
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php if (!$isPrintMode): ?>
                                                    <a href="../vacantes/vacante-editar.php?id=<?php echo $aplicacion['vacante_id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td><?php echo !empty($aplicacion['ultima_actualizacion']) ? date('d/m/Y', strtotime($aplicacion['ultima_actualizacion'])) : 'N/A'; ?></td>
                                                <?php if (!$isPrintMode): ?>
                                                <td class="no-print">
                                                    <div class="btn-group">
                                                        <a href="../aplicaciones/detalle.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="../aplicaciones/actualizar-estado.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    El candidato no ha aplicado a ninguna vacante.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
					<!-- PESTAÑA NOTAS -->
					<div class="tab-content <?php echo $activeTab == 'notas' ? 'active' : ''; ?>" id="notas">
						<div class="profile-section">
							<div class="profile-section-header">
								<div class="profile-section-title"><i class="fas fa-sticky-note me-2"></i> Notas sobre el candidato</div>
								<?php if (!$isPrintMode): ?>
								<button type="button" class="btn btn-sm btn-primary no-print" data-bs-toggle="modal" data-bs-target="#addNoteModal">
									<i class="fas fa-plus"></i> Agregar nota
								</button>
								<?php endif; ?>
							</div>
							<div class="profile-section-body">
								<?php if (!empty($notas)): ?>
								<div class="notes-container">
									<?php foreach ($notas as $nota): ?>
									<div class="note-card">
										<div class="note-header">
											<div class="note-date"><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></div>
											<div class="note-author"><?php echo htmlspecialchars($nota['usuario_nombre']); ?></div>
											<?php if (!$isPrintMode): ?>
											<div class="note-actions no-print">
												<a href="editar-nota.php?id=<?php echo $nota['id']; ?>" class="btn btn-sm btn-outline-primary note-action-btn">
													<i class="fas fa-edit"></i>
												</a>
												<a href="#" class="btn btn-sm btn-outline-danger note-action-btn" 
												   data-bs-toggle="modal" data-bs-target="#deleteNoteModal" 
												   data-note-id="<?php echo $nota['id']; ?>">
													<i class="fas fa-trash"></i>
												</a>
											</div>
											<?php endif; ?>
										</div>
										<div class="note-content">
											<?php echo nl2br(htmlspecialchars($nota['contenido'])); ?>
										</div>
										<?php if (!empty($nota['tipo'])): ?>
										<div class="note-tag <?php echo $nota['tipo']; ?>">
											<?php echo ucfirst($nota['tipo']); ?>
										</div>
										<?php endif; ?>
									</div>
									<?php endforeach; ?>
								</div>
								<?php else: ?>
								<div class="alert alert-info">
									<i class="fas fa-info-circle me-2"></i>
									No hay notas registradas para este candidato.
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
                </main>
                
                <?php if (!$isPrintMode): ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar nota -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">Agregar nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="agregar-nota.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                        
                        <div class="mb-3">
                            <label for="nota_contenido" class="form-label">Contenido de la nota</label>
                            <textarea class="form-control" id="nota_contenido" name="contenido" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nota_tipo" class="form-label">Tipo de nota</label>
                            <select class="form-select" id="nota_tipo" name="tipo">
                                <option value="">Sin categoría</option>
                                <option value="entrevista">Entrevista</option>
                                <option value="evaluacion">Evaluación</option>
                                <option value="seguimiento">Seguimiento</option>
                                <option value="importante">Importante</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar nota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para eliminar nota -->
    <div class="modal fade" id="deleteNoteModal" tabindex="-1" aria-labelledby="deleteNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteNoteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar esta nota? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteNote" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para desactivar candidato -->
    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivateModalLabel">Confirmar desactivación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea desactivar la cuenta de este candidato? El candidato no podrá acceder al sistema ni aplicar a vacantes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="desactivar.php?id=<?php echo $candidato_id; ?>" class="btn btn-danger">Desactivar cuenta</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar componentes
        document.addEventListener('DOMContentLoaded', function() {
            // Manejo de pestañas
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Desactivar todas las pestañas y contenidos
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(tc => tc.classList.remove('active'));
                    
                    // Activar la pestaña y contenido seleccionados
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                    
                    // Actualizar URL (opcional)
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url);
                });
            });
            
            // Enlaces de tabs desde otras partes
            document.querySelectorAll('.tab-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    document.querySelector(`.tab[data-tab="${tabId}"]`).click();
                });
            });
            
            // Manejo de eliminación de notas
            document.querySelectorAll('[data-bs-target="#deleteNoteModal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');
                    document.getElementById('confirmDeleteNote').href = 'eliminar-nota.php?id=' + noteId;
                });
            });
            
            // Inicializar gráficos si existe Chart.js
            if (typeof Chart !== 'undefined') {
                initCharts();
            }
        });
        
        // Función para inicializar gráficos
        function initCharts() {
            // Gráfico de evaluación general (gauge)
            const evaluationGaugeCtx = document.getElementById('evaluationGauge');
            if (evaluationGaugeCtx) {
                const score = <?php echo $promedioResultados; ?>;
                
                // Determinar color según puntuación
                let color = '#f6c23e'; // Amarillo (default)
                if (score >= 80) {
                    color = '#1cc88a'; // Verde
                } else if (score >= 60) {
                    color = '#4e73df'; // Azul
                } else if (score < 40) {
                    color = '#e74a3b'; // Rojo
                }
                
                new Chart(evaluationGaugeCtx, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [score, 100 - score],
                            backgroundColor: [color, '#e9ecef'],
                            borderWidth: 0,
                            circumference: 180,
                            rotation: 270
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });
            }
            
            // Gráfico circular de puntuaciones
            const scoreChartCtx = document.getElementById('scoreChart');
            if (scoreChartCtx) {
                const score = <?php echo $promedioResultados; ?>;
                
                // Determinar color según puntuación
                let color = '#f6c23e'; // Amarillo (default)
                if (score >= 80) {
                    color = '#1cc88a'; // Verde
                } else if (score >= 60) {
                    color = '#4e73df'; // Azul
                } else if (score < 40) {
                    color = '#e74a3b'; // Rojo
                }
                
                new Chart(scoreChartCtx, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [score, 100 - score],
                            backgroundColor: [color, '#eaecf4'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });
            }
            
            // Gráfico de evaluación score en pestaña evaluaciones
            const evaluationScoreChartCtx = document.getElementById('evaluationScoreChart');
            if (evaluationScoreChartCtx) {
                const score = <?php echo $promedioResultados; ?>;
                
                // Determinar color según puntuación
                let color = '#f6c23e'; // Amarillo (default)
                if (score >= 80) {
                    color = '#1cc88a'; // Verde
                } else if (score >= 60) {
                    color = '#4e73df'; // Azul
                } else if (score < 40) {
                    color = '#e74a3b'; // Rojo
                }
                
                new Chart(evaluationScoreChartCtx, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [score, 100 - score],
                            backgroundColor: [color, '#eaecf4'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });
            }
            
            // Gráfico de radar de competencias
            const competenciasRadarCtx = document.getElementById('competenciasRadarChart');
            if (competenciasRadarCtx) {
                // Obtener las dimensiones principales - hasta 8 para mantener legibilidad
                <?php
                $radarDimensions = [];
                if (!empty($evaluationResults)) {
                    $radarDimensions = array_slice($evaluationResults, 0, 8);
                }
                ?>
                
                const labels = [
                    <?php foreach ($radarDimensions as $dim): ?>
                    "<?php echo addslashes($dim['nombre']); ?>",
                    <?php endforeach; ?>
                ];
                
                const data = [
                    <?php foreach ($radarDimensions as $dim): ?>
                    <?php echo round($dim['promedio']); ?>,
                    <?php endforeach; ?>
                ];
                
                new Chart(competenciasRadarCtx, {
                    type: 'radar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Competencias',
                            data: data,
                            backgroundColor: 'rgba(78, 115, 223, 0.2)',
                            borderColor: '#4e73df',
                            borderWidth: 2,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#4e73df'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                angleLines: {
                                    display: true
                                },
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    stepSize: 20,
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // Gauge chart de competencias
            const competenciasGaugeCtx = document.getElementById('competenciasGaugeChart');
            if (competenciasGaugeCtx) {
                const score = <?php echo $promedioResultados; ?>;
                
                // Determinar color según puntuación
                let color = '#f6c23e'; // Amarillo (default)
                if (score >= 80) {
                    color = '#1cc88a'; // Verde
                } else if (score >= 60) {
                    color = '#4e73df'; // Azul
                } else if (score < 40) {
                    color = '#e74a3b'; // Rojo
                }
                
                new Chart(competenciasGaugeCtx, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [score, 100 - score],
                            backgroundColor: [color, '#e9ecef'],
                            borderWidth: 0,
                            circumference: 180,
                            rotation: 270
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>