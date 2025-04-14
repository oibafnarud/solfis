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
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Estilos para perfil de candidato */
        .profile-header {
            position: relative;
            padding: 2rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
            margin-bottom: 2rem;
            border-left: 5px solid #0d6efd;
        }
        
        .profile-header-top {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 2rem;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid #fff;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-image i {
            font-size: 4rem;
            color: #adb5bd;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
        }
        
        .profile-title {
            margin: 0.25rem 0 1rem;
            font-size: 1.25rem;
            color: #6c757d;
        }
        
        .profile-contact {
            display: flex;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .contact-item {
            margin-right: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .contact-item i {
            margin-right: 0.5rem;
            color: #0d6efd;
        }
        
        .profile-badges {
            display: flex;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .profile-badge {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background-color: #e9ecef;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .profile-badge.primary {
            background-color: #cfe2ff;
            color: #0a58ca;
        }
        
        .profile-badge.success {
            background-color: #d1e7dd;
            color: #146c43;
        }
        
        .profile-badge.warning {
            background-color: #fff3cd;
            color: #997404;
        }
        
        .profile-badge.info {
            background-color: #cff4fc;
            color: #087990;
        }
        
        .profile-actions {
            position: absolute;
            top: 2rem;
            right: 2rem;
        }
        
        .profile-section {
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background-color: #fff;
            overflow: hidden;
        }
        
        .profile-section-header {
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .profile-section-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .profile-section-body {
            padding: 1.5rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 3rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .timeline-marker {
            position: absolute;
            left: -3rem;
            top: 0;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background-color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .timeline-dates {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        
        .timeline-title {
            margin: 0 0 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .timeline-subtitle {
            margin: 0 0 1rem;
            font-size: 1rem;
            color: #6c757d;
        }
        
        .timeline-description {
            margin: 0;
            font-size: 0.875rem;
            color: #212529;
        }
        
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .skill-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            background-color: #f8f9fa;
            color: #212529;
        }
        
        .skill-badge.tech {
            background-color: #cff4fc;
            color: #087990;
        }
        
        .skill-badge.soft {
            background-color: #d1e7dd;
            color: #146c43;
        }
        
        .skill-badge.lang {
            background-color: #cfe2ff;
            color: #0a58ca;
        }
        
        .skill-level {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 8px;
        }
        
        .level-basic {
            background-color: #ffc107;
        }
        
        .level-intermediate {
            background-color: #0dcaf0;
        }
        
        .level-advanced {
            background-color: #198754;
        }
        
        .assessment-result {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .result-score {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 4px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .result-info {
            flex: 1;
        }
        
        .result-title {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .result-date {
            margin: 0;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .score-high {
            border-color: #198754;
            color: #198754;
        }
        
        .score-medium {
            border-color: #0dcaf0;
            color: #0dcaf0;
        }
        
        .score-low {
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .dimension-item {
            margin-bottom: 1rem;
        }
        
        .dimension-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .dimension-title {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .dimension-score {
            font-weight: 700;
            font-size: 0.875rem;
        }
        
        .dimension-bar {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .dimension-progress {
            height: 100%;
            transition: width 1s ease;
        }
        
        .high {
            background-color: #198754;
        }
        
        .medium {
            background-color: #0dcaf0;
        }
        
        .low {
            background-color: #ffc107;
        }
        
        /* Estilos para impresión */
        @media print {
            body {
                padding: 0;
                margin: 0;
                background-color: white !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .container-fluid {
                width: 100%;
                max-width: none;
                padding: 0;
            }
            
            .profile-header {
                border-radius: 0;
                box-shadow: none;
                padding: 1rem 0;
            }
            
            .profile-section {
                page-break-inside: avoid;
                border-radius: 0;
                box-shadow: none;
                margin-bottom: 1rem;
                border: 1px solid #e0e0e0;
            }
            
            .col-md-4, .col-md-8, .col-md-6 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
            
            .profile-image {
                width: 100px;
                height: 100px;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .profile-title {
                font-size: 1rem;
            }
            
            .profile-section-title {
                font-size: 1.25rem;
            }
            
            .timeline-title {
                font-size: 1rem;
            }
            
            .timeline::before {
                display: none;
            }
            
            .timeline-marker {
                display: none;
            }
            
            .timeline-item {
                padding-left: 0;
            }
        }
    </style>
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
                    
                    <!-- Encabezado del perfil -->
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
                                <p class="profile-title">
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
                                    
                                    echo $titulo ?: 'Candidato';
                                    ?>
                                </p>
                                
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
                    
                    <!-- Secciones del perfil -->
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Resumen profesional -->
                            <?php if (!empty($candidato['resumen_profesional'])): ?>
                            <div class="profile-section mb-4">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-file-alt me-2"></i> Resumen profesional</h2>
                                </div>
                                <div class="profile-section-body">
                                    <p><?php echo nl2br(htmlspecialchars($candidato['resumen_profesional'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Experiencia laboral -->
                            <?php 
                            // En un sistema real, obtendríamos la experiencia laboral desde la base de datos
                            // Creamos un ejemplo para mostrar el diseño
                            $experiencias = isset($experiencias) ? $experiencias : [];
                            
                            // Si no hay experiencias reales pero necesitamos probar el diseño, descomentar esto
                            /*
                            if (empty($experiencias)) {
                                $experiencias = [
                                    [
                                        'cargo' => 'Desarrollador Web Senior',
                                        'empresa' => 'ABC Technologies',
                                        'fecha_inicio' => '2020-03-01',
                                        'fecha_fin' => '2023-05-15',
                                        'actual' => false,
                                        'descripcion' => 'Responsable del desarrollo de aplicaciones web utilizando PHP, MySQL y JavaScript. Liderazgo de equipo de 3 desarrolladores junior.'
                                    ],
                                    [
                                        'cargo' => 'Desarrollador Web',
                                        'empresa' => 'XYZ Solutions',
                                        'fecha_inicio' => '2017-06-01',
                                        'fecha_fin' => '2020-02-28',
                                        'actual' => false,
                                        'descripcion' => 'Desarrollo de sitios web para clientes. Implementación de bases de datos y sistemas de gestión de contenidos.'
                                    ]
                                ];
                            }
                            */
                            ?>
                            
                            <?php if (!empty($experiencias)): ?>
                            <div class="profile-section mb-4">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-briefcase me-2"></i> Experiencia laboral</h2>
                                </div>
                                <div class="profile-section-body">
                                    <div class="timeline">
                                        <?php foreach ($experiencias as $experiencia): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker">
                                                <i class="fas fa-briefcase"></i>
                                            </div>
                                            <div class="timeline-dates">
                                                <?php 
                                                echo date('M Y', strtotime($experiencia['fecha_inicio'])); 
                                                echo ' - '; 
                                                echo $experiencia['actual'] ? 'Presente' : date('M Y', strtotime($experiencia['fecha_fin'])); 
                                                ?>
                                            </div>
                                            <h3 class="timeline-title"><?php echo htmlspecialchars($experiencia['cargo']); ?></h3>
                                            <h4 class="timeline-subtitle"><?php echo htmlspecialchars($experiencia['empresa']); ?></h4>
                                            <?php if (!empty($experiencia['descripcion'])): ?>
                                            <p class="timeline-description"><?php echo nl2br(htmlspecialchars($experiencia['descripcion'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Educación -->
                            <?php
                            // En un sistema real, obtendríamos la educación desde la base de datos
                            // Creamos un ejemplo para mostrar el diseño
                            $educacion = isset($educacion) ? $educacion : [];
                            
                            // Si no hay educación real pero necesitamos probar el diseño, descomentar esto
                            /*
                            if (empty($educacion)) {
                                $educacion = [
                                    [
                                        'titulo' => 'Licenciatura en Ingeniería de Sistemas',
                                        'institucion' => 'Universidad Autónoma de Santo Domingo',
                                        'fecha_inicio' => '2013-09-01',
                                        'fecha_fin' => '2017-06-30',
                                        'actual' => false,
                                        'campo_estudio' => 'Ingeniería de Sistemas',
                                        'descripcion' => 'Especialización en desarrollo de software y bases de datos.'
                                    ],
                                    [
                                        'titulo' => 'Bachillerato en Ciencias y Tecnología',
                                        'institucion' => 'Colegio San Ignacio',
                                        'fecha_inicio' => '2009-09-01',
                                        'fecha_fin' => '2013-06-30',
                                        'actual' => false,
                                        'campo_estudio' => '',
                                        'descripcion' => ''
                                    ]
                                ];
                            }
                            */
                            ?>
                            
                            <?php if (!empty($educacion)): ?>
                            <div class="profile-section mb-4">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-graduation-cap me-2"></i> Educación</h2>
                                </div>
                                <div class="profile-section-body">
                                    <div class="timeline">
                                        <?php foreach ($educacion as $edu): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div class="timeline-dates">
                                                <?php 
                                                echo date('Y', strtotime($edu['fecha_inicio'])); 
                                                echo ' - '; 
                                                echo $edu['actual'] ? 'Presente' : date('Y', strtotime($edu['fecha_fin'])); 
                                                ?>
                                            </div>
                                            <h3 class="timeline-title"><?php echo htmlspecialchars($edu['titulo']); ?></h3>
                                            <h4 class="timeline-subtitle"><?php echo htmlspecialchars($edu['institucion']); ?></h4>
                                            <?php if (!empty($edu['campo_estudio'])): ?>
                                            <p class="timeline-description">
                                                <strong>Campo de estudio:</strong> <?php echo htmlspecialchars($edu['campo_estudio']); ?>
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($edu['descripcion'])): ?>
                                            <p class="timeline-description"><?php echo nl2br(htmlspecialchars($edu['descripcion'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Habilidades -->
                            <div class="profile-section mb-4">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-tools me-2"></i> Habilidades</h2>
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
                                        ?>
                                        <div class="skill-badge <?php echo $tipo; ?>">
                                            <?php echo htmlspecialchars($habilidad); ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php elseif (isset($habilidades) && !empty($habilidades)): ?>
                                    <div class="skills-container">
                                        <?php foreach ($habilidades as $habilidad):
                                            $nivelClass = '';
                                            switch ($habilidad['nivel']) {
                                                case 'basico':
                                                    $nivelClass = 'level-basic';
                                                    break;
                                                case 'intermedio':
                                                    $nivelClass = 'level-intermediate';
                                                    break;
                                                case 'avanzado':
                                                    $nivelClass = 'level-advanced';
                                                    break;
                                            }
                                        ?>
                                        <div class="skill-badge <?php echo $habilidad['tipo']; ?>">
                                            <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                            <span class="skill-level <?php echo $nivelClass; ?>"></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">No hay habilidades registradas.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Aplicaciones a vacantes -->
                            <?php if (!empty($aplicaciones)): ?>
                            <div class="profile-section mb-4">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-briefcase me-2"></i> Aplicaciones a vacantes</h2>
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
                                    <h2 class="profile-section-title"><i class="fas fa-user me-2"></i> Información personal</h2>
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
                                    <h2 class="profile-section-title"><i class="fas fa-chart-pie me-2"></i> Evaluaciones psicométricas</h2>
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
                            
                            <!-- Notas sobre el candidato (solo en modo admin) -->
                            <?php if (!$isPrintMode && !empty($notas)): ?>
                            <div class="profile-section mb-4 no-print">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-sticky-note me-2"></i> Notas</h2>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                        <i class="fas fa-plus"></i> Añadir
                                    </button>
                                </div>
                                <div class="profile-section-body">
                                    <?php foreach ($notas as $nota): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($nota['titulo']); ?></h6>
                                            <p class="card-text small"><?php echo nl2br(htmlspecialchars($nota['contenido'])); ?></p>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></small>
                                                <div>
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
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Acciones solo para modo admin -->
                            <?php if (!$isPrintMode): ?>
                            <div class="profile-section no-print">
                                <div class="profile-section-header">
                                    <h2 class="profile-section-title"><i class="fas fa-cog me-2"></i> Acciones</h2>
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
                <form action="agregar-nota.php" method="post">
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
                <form action="eliminar-nota.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="nota_id" id="delete_nota_id">
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
    
    <!-- JavaScript para el perfil -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de resultados si existe el elemento
            const scoreChartEl = document.getElementById('scoreChart');
            if (scoreChartEl) {
                const scoreChart = new Chart(scoreChartEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Puntuación', 'Restante'],
                        datasets: [{
                            data: [<?php echo $promedioResultados; ?>, <?php echo 100 - $promedioResultados; ?>],
                            backgroundColor: [
                                <?php 
                                if ($promedioResultados >= 75) {
                                    echo "'#198754', '#f8f9fa'";
                                } elseif ($promedioResultados >= 60) {
                                    echo "'#0dcaf0', '#f8f9fa'";
                                } else {
                                    echo "'#ffc107', '#f8f9fa'";
                                }
                                ?>
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        cutout: '75%',
                        responsive: true,
                        maintainAspectRatio: true,
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
            
            // Animación de barras de dimensiones
            const dimensionBars = document.querySelectorAll('.dimension-progress');
            dimensionBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Modal para editar nota
            const editNoteModal = document.getElementById('editNoteModal');
            if (editNoteModal) {
                editNoteModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const titulo = button.getAttribute('data-titulo');
                    const contenido = button.getAttribute('data-contenido');
                    
                    document.getElementById('edit_nota_id').value = id;
                    document.getElementById('edit_titulo').value = titulo;
                    document.getElementById('edit_contenido').value = contenido;
                });
            }
            
            // Modal para eliminar nota
            const deleteNoteModal = document.getElementById('deleteNoteModal');
            if (deleteNoteModal) {
                deleteNoteModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    
                    document.getElementById('delete_nota_id').value = id;
                });
            }
        });
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>