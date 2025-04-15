<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/detalle.php - Ver detalles de un candidato
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

// Obtener notas del candidato
$notas = $candidateManager->getCandidateNotes($candidato_id);

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
                            'tipo' => 'Sobresaliente',
                            'descripcion' => 'Candidato con habilidades y competencias excepcionales en todas las áreas evaluadas.',
                            'fortalezas' => ['Capacidad analítica superior', 'Excelente comunicación', 'Toma de decisiones efectiva'],
                            'recomendaciones' => ['Posiciones de liderazgo', 'Roles con alta responsabilidad', 'Proyectos estratégicos'],
                            'class' => 'success'
                        ];
                    } elseif ($promedioResultados >= 75) {
                        $perfilPsicometrico = [
                            'tipo' => 'Avanzado',
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
                
                // Obtener resultados por dimensiones (habilidades) si existen
                $db = Database::getInstance();
                
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
                                    <div class="col-md-6">
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
                                    <div class="col-md-6">
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
                                
                                <?php foreach ($evaluationResults as $dimension): ?>
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
                            </div>
                        </div>
                        
                        <!-- Evaluaciones pendientes -->
                        <?php
                        // Simulamos evaluaciones pendientes para el ejemplo
                        $evaluacionesPendientes = [];
                        
                        // Descomenta esto para mostrar evaluaciones pendientes de ejemplo
                        /*
                        $evaluacionesPendientes = [
                            [
                                'id' => 1,
                                'titulo' => 'Evaluación de Competencias Gerenciales',
                                'descripcion' => 'Evaluación de habilidades de liderazgo, toma de decisiones y gestión de equipos',
                                'tiempo_estimado' => 45
                            ],
                            [
                                'id' => 2,
                                'titulo' => 'Test de Razonamiento Espacial',
                                'descripcion' => 'Evaluación de la capacidad de visualización espacial y orientación',
                                'tiempo_estimado' => 30
                            ]
                        ];
                        */
                        
                        if (!empty($evaluacionesPendientes)):
                        ?>
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-tasks me-2"></i> Evaluaciones Pendientes</div>
                            </div>
                            <div class="profile-section-body">
                                <?php foreach ($evaluacionesPendientes as $evaluacion): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($evaluacion['titulo']); ?></h5>
                                                <p class="card-text text-muted"><?php echo htmlspecialchars($evaluacion['descripcion']); ?></p>
                                                <div class="small">
                                                    <i class="fas fa-clock me-1"></i> Tiempo estimado: <?php echo $evaluacion['tiempo_estimado']; ?> minutos
                                                </div>
                                            </div>
                                            <?php if (!$isPrintMode): ?>
                                            <div class="no-print">
                                                <a href="../pruebas/enviar.php?prueba_id=<?php echo $evaluacion['id']; ?>&candidato_id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-paper-plane"></i> Enviar invitación
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3 no-print">
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-plus"></i> Asignar más evaluaciones
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PESTAÑA NOTAS -->
                    <div class="tab-content <?php echo $activeTab == 'notas' ? 'active' : ''; ?>" id="notas">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-sticky-note me-2"></i> Notas</div>
                                <?php if (!$isPrintMode): ?>
                                <button class="btn btn-sm btn-primary no-print" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                    <i class="fas fa-plus"></i> Añadir nota
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($notas)): ?>
                                    <?php foreach ($notas as $nota): ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($nota['titulo']); ?></h5>
                                            <?php if (!$isPrintMode): ?>
                                            <div class="no-print">
                                                <button class="btn btn-sm btn-link text-primary p-0 me-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editNoteModal" 
                                                        data-id="<?php echo $nota['id']; ?>"
                                                        data-titulo="<?php echo htmlspecialchars($nota['titulo']); ?>"
                                                        data-contenido="<?php echo htmlspecialchars($nota['contenido']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-link text-danger p-0" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteNoteModal"
                                                        data-id="<?php echo $nota['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($nota['contenido'])); ?></p>
                                            <div class="text-muted small">
                                                <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?>
                                                <?php if (!empty($nota['usuario_nombre'])): ?>
                                                <span class="ms-2"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($nota['usuario_nombre']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay notas registradas para este candidato.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PESTAÑA APTITUDES -->
                    <div class="tab-content <?php echo $activeTab == 'aptitudes' ? 'active' : ''; ?>" id="aptitudes">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-brain me-2"></i> Aptitudes Cognitivas</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                                <div class="chart-container mb-4">
                                    <canvas id="aptitudesChart"></canvas>
                                </div>
                                
                                <?php
                                // Simulamos resultados de aptitudes cognitivas
                                $aptitudes = [
                                    'Razonamiento Verbal' => $promedioResultados * 1.05 > 100 ? 100 : $promedioResultados * 1.05,
                                    'Razonamiento Numérico' => $promedioResultados * 0.9,
                                    'Razonamiento Lógico' => $promedioResultados * 0.95,
                                    'Atención al Detalle' => $promedioResultados * 0.85
                                ];
                                
                                foreach ($aptitudes as $aptitud => $puntaje):
                                    $nivelClass = '';
                                    $percentil = round($puntaje * 0.9); // Simulación del percentil
                                    
                                    if ($puntaje >= 80) {
                                        $nivelClass = 'progress-outstanding';
                                        $nivelText = 'Alto (P' . $percentil . ')';
                                    } elseif ($puntaje >= 60) {
                                        $nivelClass = 'progress-adequate';
                                        $nivelText = 'Medio (P' . $percentil . ')';
                                    } else {
                                        $nivelClass = 'progress-moderate';
                                        $nivelText = 'Bajo (P' . $percentil . ')';
                                    }
                                ?>
                                <div class="competency-row">
                                    <div class="competency-label"><?php echo $aptitud; ?></div>
                                    <div class="competency-score"><?php echo round($puntaje); ?></div>
                                    <div class="competency-bar-container">
                                        <div class="competency-bar">
                                            <div class="competency-progress <?php echo $nivelClass; ?>" style="width: <?php echo $puntaje; ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="competency-level"><?php echo $nivelText; ?></div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="competency-details mt-4">
                                    <h3 style="margin-bottom: 16px;">Proyección de aprendizaje</h3>
                                    <p>Se estima una curva de aprendizaje <?php echo $promedioResultados >= 75 ? 'rápida' : ($promedioResultados >= 60 ? 'moderada' : 'gradual'); ?> para las tareas requeridas en el puesto. Sus habilidades cognitivas sugieren un buen potencial para asimilar nueva información y adaptarse a los cambios del entorno laboral.</p>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay evaluaciones de aptitudes cognitivas disponibles para este candidato.
                                </div>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-tasks me-2"></i> Tareas Recomendadas</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Fortalezas Cognitivas</h5>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <?php if ($aptitudes['Razonamiento Verbal'] > $aptitudes['Razonamiento Numérico']): ?>
                                                <div>
                                                    <strong>Comunicación y expresión verbal</strong>
                                                    <div class="small text-muted">Presenta facilidad para articular ideas y explicar conceptos complejos.</div>
                                                </div>
                                                <?php else: ?>
                                                <div>
                                                    <strong>Análisis numérico y cuantitativo</strong>
                                                    <div class="small text-muted">Muestra buena capacidad para trabajar con datos y realizar análisis cuantitativos.</div>
                                                </div>
                                                <?php endif; ?>
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <?php if ($aptitudes['Razonamiento Lógico'] > $aptitudes['Atención al Detalle']): ?>
                                                <div>
                                                    <strong>Resolución de problemas</strong>
                                                    <div class="small text-muted">Demuestra buena capacidad para analizar situaciones y encontrar soluciones lógicas.</div>
                                                </div>
                                                <?php else: ?>
                                                <div>
                                                    <strong>Precisión y minuciosidad</strong>
                                                    <div class="small text-muted">Muestra gran atención a los detalles y capacidad para trabajar con precisión.</div>
                                                </div>
                                                <?php endif; ?>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Tareas Adecuadas</h5>
                                        <ul class="list-group list-group-flush">
                                            <?php if ($aptitudes['Razonamiento Verbal'] > 75): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check text-primary me-2"></i>
                                                <div>
                                                    <strong>Redacción y comunicación</strong>
                                                    <div class="small text-muted">Creación de documentos, presentaciones y comunicación con clientes.</div>
                                                </div>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($aptitudes['Razonamiento Numérico'] > 70): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check text-primary me-2"></i>
                                                <div>
                                                    <strong>Análisis de datos</strong>
                                                    <div class="small text-muted">Evaluación de informes financieros, análisis de tendencias y pronósticos.</div>
                                                </div>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($aptitudes['Razonamiento Lógico'] > 70): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check text-primary me-2"></i>
                                                <div>
                                                    <strong>Toma de decisiones</strong>
                                                    <div class="small text-muted">Evaluación de alternativas y resolución de problemas complejos.</div>
                                                </div>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($aptitudes['Atención al Detalle'] > 70): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check text-primary me-2"></i>
                                                <div>
                                                    <strong>Control de calidad</strong>
                                                    <div class="small text-muted">Revisión de documentos, verificación de datos y seguimiento de procesos.</div>
                                                </div>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PESTAÑA PERSONALIDAD -->
                    <div class="tab-content <?php echo $activeTab == 'personalidad' ? 'active' : ''; ?>" id="personalidad">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-user-tag me-2"></i> Personalidad Laboral</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                                <div class="personality-chart">
                                    <div style="width: 100%;">
                                        <?php
                                        // Simulamos resultados de personalidad
                                        $personalidad = [
                                            ['izquierda' => 'Introversión', 'derecha' => 'Extroversión', 'valor' => 70],
                                            ['izquierda' => 'Reactividad', 'derecha' => 'Estabilidad', 'valor' => 65],
                                            ['izquierda' => 'Convencionalidad', 'derecha' => 'Apertura', 'valor' => 80],
                                            ['izquierda' => 'Independencia', 'derecha' => 'Cooperación', 'valor' => 85],
                                            ['izquierda' => 'Flexibilidad', 'derecha' => 'Meticulosidad', 'valor' => 60],
                                        ];
                                        
                                        foreach ($personalidad as $dimension):
                                        ?>
                                        <div class="personality-dimension">
                                            <div class="dimension-label"><?php echo $dimension['izquierda']; ?></div>
                                            <div class="dimension-scale">
                                                <div class="dimension-marker" style="left: <?php echo $dimension['valor']; ?>%;"></div>
                                            </div>
                                            <div class="opposite-label"><?php echo $dimension['derecha']; ?></div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 30px;">
                                    <div style="margin-bottom: 16px;">
                                        <span class="profile-badge primary" style="margin-right: 8px;">Perfil predominante: Relacional</span>
                                        <span class="profile-badge" style="background-color: #f8f9fa; color: #212529;">Perfil secundario: Coordinador</span>
                                    </div>
                                    
                                    <h3 style="margin-bottom: 16px;">Estilo de trabajo preferido</h3>
                                    <p>El candidato muestra un estilo de trabajo orientado a las personas, con énfasis en la comunicación, colaboración y adaptabilidad. Se desempeña mejor en entornos que valoran el trabajo en equipo y ofrecen cierta flexibilidad, manteniendo al mismo tiempo estructuras claras. Su combinación de estabilidad y apertura sugiere capacidad para innovar dentro de marcos establecidos.</p>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay evaluaciones de personalidad laboral disponibles para este candidato.
                                </div>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-gem me-2"></i> Motivaciones y Valores</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="chart-container mb-4">
                                    <canvas id="motivacionesChart"></canvas>
                                </div>
                                
                                <?php
                                // Simulamos resultados de motivaciones
                                $motivaciones = [
                                    'Servicio/Contribución' => 9,
                                    'Afiliación/Relaciones' => 8,
                                    'Logro' => 7,
                                    'Equilibrio vida-trabajo' => 6,
                                    'Reto/Desafío' => 4,
                                    'Autonomía/Independencia' => 3,
                                    'Seguridad/Estabilidad' => 2,
                                    'Poder/Influencia' => 1
                                ];
                                
                                foreach ($motivaciones as $motivacion => $valor):
                                ?>
                                <div class="motivation-row">
                                    <div class="motivation-label"><?php echo $motivacion; ?></div>
                                    <div class="motivation-score"><?php echo $valor; ?></div>
                                    <div class="motivation-bar-container">
                                        <div class="motivation-bar">
                                            <div class="motivation-progress" style="width: <?php echo $valor * 10; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div style="margin-top: 24px;">
                                    <div style="margin-bottom: 16px;">
                                        <span class="profile-badge primary" style="margin-right: 8px;">Núcleo motivacional: Servicio, Afiliación, Logro</span>
                                        <span class="profile-badge success">Perfil motivacional: Líder Social</span>
                                    </div>
                                    
                                    <h3 style="margin-bottom: 16px;">Factores de satisfacción laboral</h3>
                                    <p>El candidato encontrará mayor satisfacción en roles que le permitan ayudar a otros, trabajar en equipo y recibir reconocimiento por logros específicos. Entornos orientados al servicio, con fuerte componente social y colaborativo, serán ideales para mantener su motivación a largo plazo.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-comments me-2"></i> Guía para Entrevista</div>
                            </div>
                            <div class="profile-section-body">
                                <div class="interview-section">
                                    <div class="interview-section-title">Experiencia previa y habilidades clave</div>
                                    <div class="interview-question">¿Podría describir situaciones específicas donde haya demostrado sus principales fortalezas profesionales?</div>
                                    <div class="interview-question">¿Cómo ha manejado situaciones donde tuvo que equilibrar múltiples prioridades o demandas?</div>
                                </div>
                                
                                <div class="interview-section">
                                    <div class="interview-section-title">Motivación y preferencias de entorno</div>
                                    <div class="interview-question">¿Qué aspectos de sus trabajos anteriores le resultaron más satisfactorios y por qué?</div>
                                    <div class="interview-question">Describa el entorno de trabajo en el que se siente más productivo y motivado.</div>
                                </div>
                                
                                <div class="interview-section">
                                    <div class="interview-section-title">Áreas de desarrollo</div>
                                    <div class="interview-question">¿Qué habilidades o conocimientos está actualmente interesado en desarrollar?</div>
                                    <div class="interview-question">¿Cómo aborda normalmente sus áreas de mejora profesional?</div>
                                </div>
                                
                                <div class="signals-container">
                                    <div>
                                        <h3 style="color: var(--success); margin-bottom: 12px;">Señales positivas</h3>
                                        <ul style="padding-left: 24px; margin-bottom: 0;">
                                            <li style="margin-bottom: 8px;">Ejemplos concretos que demuestren sus habilidades destacadas</li>
                                            <li style="margin-bottom: 8px;">Muestra de autoconocimiento sobre sus fortalezas y áreas de mejora</li>
                                            <li style="margin-bottom: 8px;">Alineación entre sus motivaciones y los valores de la empresa</li>
                                            <li>Capacidad para articular su trayectoria y objetivos claramente</li>
                                        </ul>
                                    </div>
                                    
                                    <div>
                                        <h3 style="color: var(--danger); margin-bottom: 12px;">Posibles alertas</h3>
                                        <ul style="padding-left: 24px; margin-bottom: 0;">
                                            <li style="margin-bottom: 8px;">Dificultad para proporcionar ejemplos específicos</li>
                                            <li style="margin-bottom: 8px;">Falta de interés en desarrollar áreas de mejora identificadas</li>
                                            <li style="margin-bottom: 8px;">Preferencias de entorno que contrasten con la cultura organizacional</li>
                                            <li>Expectativas laborales no alineadas con el puesto</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pestañas - Estilo moderno -->
					<div class="tabs-container">
						<div class="tabs">
							<div class="tab <?php echo $activeTab == 'resumen' ? 'active' : ''; ?>" data-tab="resumen">
								<i class="fas fa-th-large me-2"></i>Resumen
							</div>
                            <div class="tab <?php echo $activeTab == 'competencias' ? 'active' : ''; ?>" data-tab="competencias">
                                <i class="fas fa-chart-bar me-2"></i>Competencias
                            </div>
                            <div class="tab <?php echo $activeTab == 'aptitudes' ? 'active' : ''; ?>" data-tab="aptitudes">
                                <i class="fas fa-brain me-2"></i>Aptitudes Cognitivas
                            </div>
                            <div class="tab <?php echo $activeTab == 'personalidad' ? 'active' : ''; ?>" data-tab="personalidad">
                                <i class="fas fa-user-tag me-2"></i>Personalidad
                            </div>
                            <div class="tab <?php echo $activeTab == 'aplicaciones' ? 'active' : ''; ?>" data-tab="aplicaciones">
                                <i class="fas fa-briefcase me-2"></i>Aplicaciones
                            </div>
                            <div class="tab <?php echo $activeTab == 'evaluaciones' ? 'active' : ''; ?>" data-tab="evaluaciones">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluaciones
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
                                <?php if (!empty($candidato['resumen_profesional'])): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-file-alt me-2"></i> Resumen profesional</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <p><?php echo nl2br(htmlspecialchars($candidato['resumen_profesional'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Fortalezas y áreas de desarrollo (si hay resultados) -->
                                <?php if (!empty($evaluationResults) && count($evaluationResults) > 2): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-balance-scale me-2"></i> Fortalezas y Áreas de Desarrollo</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="strengths-weaknesses">
                                            <div>
                                                <h3 style="margin-bottom: 16px;">Fortalezas</h3>
                                                
                                                <?php 
                                                // Mostrar las 3 mejores dimensiones como fortalezas
                                                $topFortalezas = array_slice($evaluationResults, 0, 3);
                                                foreach ($topFortalezas as $fortaleza):
                                                    $nivel_descripcion = '';
                                                    $promedio = round($fortaleza['promedio']);
                                                    if ($promedio >= 90) {
                                                        $nivel_descripcion = 'Excepcional dominio, destacado en el área';
                                                    } elseif ($promedio >= 80) {
                                                        $nivel_descripcion = 'Muy alto nivel, fortaleza significativa';
                                                    } elseif ($promedio >= 70) {
                                                        $nivel_descripcion = 'Buen nivel, área de competencia sólida';
                                                    } else {
                                                        $nivel_descripcion = 'Nivel adecuado, área positiva';
                                                    }
                                                ?>
                                                <div class="strength-item">
                                                    <div class="item-icon">✓</div>
                                                    <div class="item-content">
                                                        <div class="item-title"><?php echo htmlspecialchars($fortaleza['nombre']); ?> (<?php echo round($fortaleza['promedio']); ?>/100)</div>
                                                        <div class="item-description"><?php echo $nivel_descripcion; ?></div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <div>
                                                <h3 style="margin-bottom: 16px;">Áreas de Desarrollo</h3>
                                                
                                                <?php 
                                                // Mostrar las 2 peores dimensiones como áreas de desarrollo
                                                $flipped = array_reverse($evaluationResults);
                                                $topDebilidades = array_slice($flipped, 0, 2);
                                                foreach ($topDebilidades as $debilidad):
                                                    $nivel_descripcion = '';
                                                    $promedio = round($debilidad['promedio']);
                                                    if ($promedio >= 60) {
                                                        $nivel_descripcion = 'Nivel adecuado, potencial para mejorar';
                                                    } elseif ($promedio >= 40) {
                                                        $nivel_descripcion = 'Nivel moderado, área para fortalecer';
                                                    } else {
                                                        $nivel_descripcion = 'Nivel básico, necesita desarrollo importante';
                                                    }
                                                ?>
                                                <div class="strength-item">
                                                    <div class="item-icon weakness-icon">!</div>
                                                    <div class="item-content">
                                                        <div class="item-title"><?php echo htmlspecialchars($debilidad['nombre']); ?> (<?php echo round($debilidad['promedio']); ?>/100)</div>
                                                        <div class="item-description"><?php echo $nivel_descripcion; ?></div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Compatibilidad con Áreas -->
                                <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-sitemap me-2"></i> Compatibilidad con Áreas</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <div class="chart-container mb-4">
                                            <canvas id="areasChart"></canvas>
                                        </div>
                                        
                                        <div>
                                            <?php
                                            // Áreas empresariales comunes para mostrar compatibilidad
                                            $areas = [
                                                'Atención al Cliente' => $promedioResultados * 0.95, // Simulación
                                                'Comercial' => $promedioResultados * 0.85,
                                                'Administrativo' => $promedioResultados * 0.75,
                                                'Operaciones' => $promedioResultados * 0.65,
                                                'Tecnología' => $promedioResultados * 0.55,
                                            ];
                                            
                                            foreach ($areas as $area => $puntaje):
                                                $nivelClass = '';
                                                $nivelText = '';
                                                
                                                if ($puntaje >= 85) {
                                                    $nivelClass = 'progress-exceptional';
                                                    $nivelText = 'Muy alta';
                                                    $badgeClass = 'badge-success';
                                                } elseif ($puntaje >= 75) {
                                                    $nivelClass = 'progress-outstanding';
                                                    $nivelText = 'Alta';
                                                    $badgeClass = 'badge-success';
                                                } elseif ($puntaje >= 65) {
                                                    $nivelClass = 'progress-notable';
                                                    $nivelText = 'Moderada';
                                                    $badgeClass = 'badge-primary';
                                                } elseif ($puntaje >= 50) {
                                                    $nivelClass = 'progress-adequate';
                                                    $nivelText = 'Moderada';
                                                    $badgeClass = 'badge-primary';
                                                } else {
                                                    $nivelClass = 'progress-moderate';
                                                    $nivelText = 'Baja';
                                                    $badgeClass = 'badge-warning';
                                                }
                                            ?>
                                            <div class="competency-row">
                                                <div class="competency-label"><?php echo $area; ?></div>
                                                <div class="competency-score"><?php echo round($puntaje); ?>%</div>
                                                <div class="competency-bar-container">
                                                    <div class="competency-bar">
                                                        <div class="competency-progress <?php echo $nivelClass; ?>" style="width: <?php echo $puntaje; ?>%;"></div>
                                                    </div>
                                                </div>
                                                <div class="competency-level badge <?php echo $badgeClass; ?>"><?php echo $nivelText; ?></div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Habilidades -->
                                <div class="profile-section mb-4">
                                    <div class="profile-section-header">
                                        <div class="profile-section-title"><i class="fas fa-tools me-2"></i> Habilidades</div>
                                    </div>
                                    <div class="profile-section-body">
                                        <?php if (!empty($candidato['habilidades_destacadas'])): ?>
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
                                        <?php else: ?>
                                        <p class="text-muted">No hay habilidades registradas.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
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
                                        <h6 class="fw-bold mb-3">Resultados por dimensión</h6>
                                        
                                        <?php foreach ($evaluationResults as $dimension): ?>
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
                                            <p class="small"><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                            
                                            <h6 class="fw-bold mt-3">Fortalezas</h6>
                                            <ul class="small">
                                                <?php foreach ($perfilPsicometrico['fortalezas'] as $fortaleza): ?>
                                                <li><?php echo $fortaleza; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <h6 class="fw-bold mt-3 mb-3">Pruebas completadas</h6>
                                        
                                        <?php foreach ($pruebasCompletadas as $prueba): 
                                            $score = isset($prueba['resultado_global']) ? $prueba['resultado_global'] : 0;
                                            $scoreClass = 'score-low';
                                            
                                            if ($score >= 75) {
                                                $scoreClass = 'score-high';
                                            } elseif ($score >= 60) {
                                                $scoreClass = 'score-medium';
                                            }
                                        ?>
                                        <div class="assessment-result">
                                            <div class="result-score <?php echo $scoreClass; ?>"><?php echo $score; ?>%</div>
                                            <div class="result-info">
                                                <h6 class="result-title"><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></h6>
                                                <p class="result-date"><?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></p>
                                            </div>
                                            <?php if (!$isPrintMode): ?>
                                            <a href="../pruebas/resultados.php?session_id=<?php echo $prueba['sesion_id']; ?>" class="btn btn-sm btn-outline-primary no-print">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (!$isPrintMode): ?>
                                        <div class="mt-3 text-center no-print">
                                            <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-plus-circle"></i> Asignar nueva evaluación
                                            </a>
                                        </div>
                                        <?php endif; ?>
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
                    
                    <!-- PESTAÑA COMPETENCIAS -->
                    <div class="tab-content <?php echo $activeTab == 'competencias' ? 'active' : ''; ?>" id="competencias">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-chart-bar me-2"></i> Competencias Fundamentales</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($evaluationResults) && count($evaluationResults) > 0): ?>
                                <div class="chart-container mb-4">
                                    <canvas id="competenciasChart"></canvas>
                                </div>
                                
                                <?php foreach ($evaluationResults as $dimension): 
                                    $nivelClass = '';
                                    $nivelText = '';
                                    $promedio = round($dimension['promedio']);
                                    
                                    if ($promedio >= 90) {
                                        $nivelClass = 'progress-exceptional';
                                        $nivelText = 'Excepcional';
                                    } elseif ($promedio >= 80) {
                                        $nivelClass = 'progress-outstanding';
                                        $nivelText = 'Sobresaliente';
                                    } elseif ($promedio >= 70) {
                                        $nivelClass = 'progress-notable';
                                        $nivelText = 'Notable';
                                    } elseif ($promedio >= 60) {
                                        $nivelClass = 'progress-adequate';
                                        $nivelText = 'Adecuado';
                                    } elseif ($promedio >= 50) {
                                        $nivelClass = 'progress-moderate';
                                        $nivelText = 'Moderado';
                                    } elseif ($promedio >= 40) {
                                        $nivelClass = 'progress-developing';
                                        $nivelText = 'En desarrollo';
                                    } else {
                                        $nivelClass = 'progress-incipient';
                                        $nivelText = 'Incipiente';
                                    }
                                ?>
                                <div class="competency-row">
                                    <div class="competency-label"><?php echo htmlspecialchars($dimension['nombre']); ?></div>
                                    <div class="competency-score"><?php echo $promedio; ?></div>
                                    <div class="competency-bar-container">
                                        <div class="competency-bar">
                                            <div class="competency-progress <?php echo $nivelClass; ?>" style="width: <?php echo $promedio; ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="competency-level"><?php echo $nivelText; ?></div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="competency-details mt-4">
                                    <h3 style="margin-bottom: 16px;">Observaciones</h3>
                                    <?php if (!empty($perfilPsicometrico)): ?>
                                    <p><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                    <?php else: ?>
                                    <p>El candidato demuestra un perfil de competencias con áreas de fortaleza en las dimensiones más altas y oportunidades de desarrollo en las áreas con menor puntuación.</p>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay evaluaciones de competencias disponibles para este candidato.
                                </div>
                                
                                <?php if (!$isPrintMode): ?>
                                <div class="text-center mt-3">
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Asignar evaluación
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($perfilPsicometrico) && !empty($perfilPsicometrico['recomendaciones'])): ?>
                        <div class="profile-section mt-4">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-lightbulb me-2"></i> Recomendaciones basadas en competencias</div>
                            </div>
                            <div class="profile-section-body">
                                <h6 class="fw-bold mb-3">Posiciones recomendadas</h6>
                                
                                <?php foreach ($perfilPsicometrico['recomendaciones'] as $index => $recomendacion): 
                                    $iconos = ['👥', '🚀', '🤝', '📊', '🔍'];
                                    $match = 85 - ($index * 5);
                                ?>
                                <div class="recommendation-card">
                                    <div class="recommendation-icon"><?php echo $iconos[$index % count($iconos)]; ?></div>
                                    <div class="recommendation-content">
                                        <div class="recommendation-title"><?php echo $recomendacion; ?></div>
                                        <div class="recommendation-match"><?php echo $match; ?>% de compatibilidad</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-4">
                                    <h6 class="fw-bold mb-3">Estrategias de desarrollo</h6>
                                    
                                    <div style="margin-bottom: 24px;">
                                        <h6 style="margin-bottom: 12px; color: var(--primary);">1. Fortalecer capacidades analíticas</h6>
                                        <ul style="padding-left: 24px; margin-bottom: 0;">
                                            <li style="margin-bottom: 8px;">Formación en análisis de datos y pensamiento crítico</li>
                                            <li style="margin-bottom: 8px;">Desarrollo de habilidades para identificar patrones y tendencias</li>
                                            <li>Práctica en resolución de problemas complejos</li>
                                        </ul>
                                    </div>
                                    
                                    <div style="margin-bottom: 24px;">
                                        <h6 style="margin-bottom: 12px; color: var(--primary);">2. Mejorar competencias técnicas</h6>
                                        <ul style="padding-left: 24px; margin-bottom: 0;">
                                            <li style="margin-bottom: 8px;">Formación específica en herramientas y tecnologías relevantes</li>
                                            <li style="margin-bottom: 8px;">Actualización de conocimientos en el área profesional</li>
                                            <li>Certificaciones que complementen su perfil</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                                      
                    <!-- PESTAÑA APLICACIONES -->
                    <div class="tab-content <?php echo $activeTab == 'aplicaciones' ? 'active' : ''; ?>" id="aplicaciones">
                        <div class="profile-section">
                            <div class="profile-section-header">
                                <div class="profile-section-title"><i class="fas fa-briefcase me-2"></i> Aplicaciones a vacantes</div>
                            </div>
                            <div class="profile-section-body">
                                <?php if (!empty($aplicaciones)): ?>
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
                                            <?php foreach ($aplicaciones as $aplicacion): 
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
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="actualizar-estado.php?id=<?php echo $aplicacion['id']; ?>&estado=revision&candidato_id=<?php echo $candidato_id; ?>">Marcar en revisión</a></li>
                                                            <li><a class="dropdown-item" href="actualizar-estado.php?id=<?php echo $aplicacion['id']; ?>&estado=entrevista&candidato_id=<?php echo $candidato_id; ?>">Programar entrevista</a></li>
                                                            <li><a class="dropdown-item" href="actualizar-estado.php?id=<?php echo $aplicacion['id']; ?>&estado=seleccionado&candidato_id=<?php echo $candidato_id; ?>">Marcar como seleccionado</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="actualizar-estado.php?id=<?php echo $aplicacion['id']; ?>&estado=rechazado&candidato_id=<?php echo $candidato_id; ?>">Rechazar</a></li>
                                                        </ul>
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
                                    Este candidato aún no ha aplicado a ninguna vacante.
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
    
    <!-- Modal para añadir nota -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">Añadir nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="guardar-nota.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="contenido" class="form-label">Contenido</label>
                            <textarea class="form-control" id="contenido" name="contenido" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para editar nota -->
    <div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNoteModalLabel">Editar nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="editar-nota.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="nota_id" id="edit_nota_id">
                        <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contenido" class="form-label">Contenido</label>
                            <textarea class="form-control" id="edit_contenido" name="contenido" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
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
                    <h5 class="modal-title" id="deleteNoteModalLabel">Eliminar nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="eliminar-nota.php" method="get">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="delete_nota_id">
                        <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                        <p>¿Está seguro de que desea eliminar esta nota? Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para desactivar cuenta -->
    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivateModalLabel">Desactivar cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="desactivar-candidato.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                        <p>¿Está seguro de que desea desactivar la cuenta de este candidato? El candidato no podrá acceder al sistema hasta que la cuenta sea reactivada.</p>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enviar_notificacion" name="enviar_notificacion" value="1" checked>
                            <label class="form-check-label" for="enviar_notificacion">
                                Enviar notificación al candidato
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo (opcional)</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Desactivar cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- JavaScript específico del perfil -->
    <script src="js/candidato-detalle.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
	
	<script>
    // Código de depuración
    console.log("Tabs encontrados:", document.querySelectorAll('.tab').length);
    console.log("Contenidos de tabs encontrados:", document.querySelectorAll('.tab-content').length);
    
    // Listar los IDs de los contenidos de pestañas
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        console.log("Tab content ID:", content.id);
    });
    
    // Verificar que los data-tab de los tabs coinciden con los IDs de los contenidos
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        console.log("Tab data-tab:", tab.getAttribute('data-tab'));
    });
	</script>

</body>
</html>	