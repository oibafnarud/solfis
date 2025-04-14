<?php
// Inicializar sesión
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$vacancyManager = new VacancyManager();

// Añadir estas líneas al principio de panel.php (después de session_start() y las verificaciones de autenticación)
// para inicializar correctamente la variable $testManager y evitar errores

// Inicializar la variable testManager
$testManager = null;
$pruebasPendientes = [];
$pruebasEnProgreso = [];
$pruebasCompletadas = [];
$totalPruebas = 0;
$pruebasPendientesCount = 0;
$pruebasEnProgresoCount = 0;
$pruebasCompletadasCount = 0;
$progresoGeneral = 0;
$resultado_global = 0;
$perfil_tipo = '';

// Comprobar si existe el archivo TestManager.php
if (file_exists(__DIR__ . '/../includes/TestManager.php')) {
    require_once __DIR__ . '/../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        
        // Obtener pruebas del candidato si existe testManager
        if ($testManager) {
            $candidato_id = $_SESSION['candidato_id'];
            $pruebasPendientes = $testManager->getPendingTests($candidato_id);
            $pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Asegurar que todos son arrays
            $pruebasPendientes = is_array($pruebasPendientes) ? $pruebasPendientes : [];
            $pruebasEnProgreso = is_array($pruebasEnProgreso) ? $pruebasEnProgreso : [];
            $pruebasCompletadas = is_array($pruebasCompletadas) ? $pruebasCompletadas : [];
            
            // Contar correctamente
            $pruebasPendientesCount = count($pruebasPendientes);
            $pruebasEnProgresoCount = count($pruebasEnProgreso);
            $pruebasCompletadasCount = count($pruebasCompletadas);
            
            $totalPruebas = $pruebasPendientesCount + $pruebasEnProgresoCount + $pruebasCompletadasCount;
            
            // Calcular progreso general
            $progresoGeneral = $totalPruebas > 0 ? round(($pruebasCompletadasCount / $totalPruebas) * 100) : 0;
            
            // Calcular resultado global promediando todos los resultados
            if (!empty($pruebasCompletadas)) {
                $total_resultados = 0;
                $count_resultados = 0;
                
                foreach ($pruebasCompletadas as $prueba) {
                    if (isset($prueba['resultado_global']) && is_numeric($prueba['resultado_global'])) {
                        $total_resultados += $prueba['resultado_global'];
                        $count_resultados++;
                    }
                }
                
                if ($count_resultados > 0) {
                    $resultado_global = round($total_resultados / $count_resultados);
                    
                    // Determinar tipo de perfil según el resultado global
                    if ($resultado_global >= 90) {
                        $perfil_tipo = 'Sobresaliente';
                        $perfil_descripcion = 'Has demostrado habilidades y competencias excepcionales en todas las áreas evaluadas.';
                        $perfil_class = 'sobresaliente';
                    } elseif ($resultado_global >= 75) {
                        $perfil_tipo = 'Avanzado';
                        $perfil_descripcion = 'Tu perfil muestra un alto nivel de competencia y habilidades bien desarrolladas.';
                        $perfil_class = 'avanzado';
                    } elseif ($resultado_global >= 60) {
                        $perfil_tipo = 'Competente';
                        $perfil_descripcion = 'Demuestras un buen nivel de habilidades, con algunas áreas destacadas y otras por desarrollar.';
                        $perfil_class = 'competente';
                    } elseif ($resultado_global >= 40) {
                        $perfil_tipo = 'En desarrollo';
                        $perfil_descripcion = 'Tu perfil muestra potencial, con áreas específicas que requieren más desarrollo.';
                        $perfil_class = 'desarrollo';
                    } else {
                        $perfil_tipo = 'Inicial';
                        $perfil_descripcion = 'Estás en las etapas iniciales de desarrollo de las habilidades evaluadas.';
                        $perfil_class = 'inicial';
                    }
                }
            }
        }
    }
}

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Si no existe el candidato, cerrar sesión
if (!$candidato) {
    session_destroy();
    header('Location: login.php?error=candidato_no_encontrado');
    exit;
}

// Obtener aplicaciones del candidato
$aplicaciones = [];
$sql = "SELECT a.*, v.titulo as vacante_titulo, v.modalidad, v.tipo_contrato, v.categoria_id, 
               c.nombre as categoria_nombre
        FROM aplicaciones a
        JOIN vacantes v ON a.vacante_id = v.id
        LEFT JOIN categorias_vacantes c ON v.categoria_id = c.id
        WHERE a.candidato_id = $candidato_id
        ORDER BY a.fecha_aplicacion DESC
        LIMIT 3";

$db = Database::getInstance();
$result = $db->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $aplicaciones[] = $row;
    }
}

// Obtener vacantes recomendadas
$vacantesRecomendadas = $vacancyManager->getRecommendedVacancies($candidato_id, 3);

// Calcular progreso del perfil
$camposRequeridos = ['nombre', 'apellido', 'email', 'telefono', 'ubicacion', 'nivel_educativo', 'experiencia_general'];
$camposCompletados = 0;

foreach ($camposRequeridos as $campo) {
    if (!empty($candidato[$campo])) {
        $camposCompletados++;
    }
}

$completitudPerfil = round(($camposCompletados / count($camposRequeridos)) * 100);

// Variables para la página
$site_title = "Panel de Candidato - SolFis Talentos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    
    <!-- Fuentes y íconos -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Estilos adicionales específicos para el panel -->
    <style>
        /* Estilos para el panel mejorado */
        .welcome-banner {
            background: linear-gradient(135deg, #0088cc, #003366);
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-content {
            flex: 1;
        }
        
        .welcome-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .welcome-stat {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 15px;
            flex: 1;
            min-width: 120px;
            backdrop-filter: blur(5px);
        }
        
        .welcome-stat h3 {
            margin: 0 0 5px;
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .welcome-stat p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .welcome-actions {
            margin-left: 30px;
        }
        
        .profile-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .perfil-header {
            padding: 20px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .perfil-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .perfil-header h2 i {
            margin-right: 10px;
            color: #0088cc;
        }
        
        .perfil-body {
            padding: 20px;
        }
        
        .perfil-body p {
            margin-top: 0;
        }
        
        .perfil-completion {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .perfil-completion span {
            font-weight: 500;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #eef2f7;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #0088cc;
            border-radius: 4px;
        }
        
        .progress-fill.success {
            background-color: #36b37e;
        }
        
        .progress-fill.warning {
            background-color: #ffab00;
        }
        
        .missing-items {
            font-size: 0.9rem;
        }
        
        .missing-items ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }
        
        .missing-items li {
            margin-bottom: 5px;
            color: #6b7280;
        }
        
        .results-card {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 20px;
        }
        
        .test-chart-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chart-score {
            position: absolute;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0088cc;
        }
        
        .test-results-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .profile-type {
            margin-bottom: 15px;
        }
        
        .profile-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .profile-badge.sobresaliente {
            background-color: #e3f9e5;
            color: #22863a;
        }
        
        .profile-badge.avanzado {
            background-color: #def0fc;
            color: #0366d6;
        }
        
        .profile-badge.competente {
            background-color: #fff8c5;
            color: #b08800;
        }
        
        .profile-badge.desarrollo {
            background-color: #ffebe9;
            color: #d73a49;
        }
        
        .profile-badge.inicial {
            background-color: #f6f8fa;
            color: #6a737d;
        }
        
        .dashboard-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #333;
        }
        
        .section-header a {
            color: #0088cc;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .section-header a i {
            margin-left: 5px;
            font-size: 0.9rem;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .aplication-card, .vacancy-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .aplication-card:hover, .vacancy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .aplication-card-header {
            padding: 20px;
            background-color: #f8fafc;
            border-bottom: 1px solid #eef2f7;
        }
        
        .aplication-card-header h3, .vacancy-card-header h3 {
            margin: 0 0 10px;
            font-size: 1.1rem;
        }
        
        .vacancy-card-header {
            padding: 20px;
            border-bottom: 1px solid #eef2f7;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.received {
            background-color: #def0fc;
            color: #0366d6;
        }
        
        .status-badge.review {
            background-color: #fff8c5;
            color: #b08800;
        }
        
        .status-badge.interview {
            background-color: #e3f9e5;
            color: #22863a;
        }
        
        .status-badge.rejected {
            background-color: #ffebe9;
            color: #d73a49;
        }
        
        .aplication-card-body, .vacancy-card-body {
            padding: 20px;
            flex: 1;
        }
        
        .aplication-meta, .vacancy-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .meta-item i {
            margin-right: 5px;
            color: #0088cc;
            font-size: 0.85rem;
        }
        
        .match-indicator {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .match-value {
            font-weight: 600;
            color: #0088cc;
            margin-right: 10px;
        }
        
        .match-bar {
            flex: 1;
            height: 6px;
            background-color: #eef2f7;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .match-fill {
            height: 100%;
            border-radius: 3px;
        }
        
        .match-fill.high {
            background-color: #36b37e;
        }
        
        .match-fill.medium {
            background-color: #0088cc;
        }
        
        .match-fill.low {
            background-color: #ffab00;
        }
        
        .aplication-card-footer, .vacancy-card-footer {
            padding: 15px 20px;
            border-top: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .aplication-date, .vacancy-date {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .chart-container {
            margin-top: 20px;
            height: 200px;
        }
        
        .highlights-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .highlights-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .highlights-header h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #333;
        }
        
        .highlights-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .highlight-card {
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .highlight-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .highlight-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .highlight-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #def0fc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .highlight-icon i {
            color: #0088cc;
            font-size: 1.1rem;
        }
        
        .highlight-title {
            font-weight: 600;
            color: #333;
        }
        
        .highlight-content {
            color: #6b7280;
            font-size: 0.95rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f0f7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .action-icon i {
            color: #0088cc;
            font-size: 1.5rem;
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .action-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .welcome-banner {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .welcome-stats {
                margin-top: 20px;
                gap: 10px;
            }
            
            .welcome-stat {
                min-width: 100px;
                padding: 12px;
            }
            
            .welcome-actions {
                margin-left: 0;
                margin-top: 20px;
                width: 100%;
            }
            
            .welcome-actions .btn {
                width: 100%;
            }
            
            .profile-summary {
                grid-template-columns: 1fr;
            }
            
            .results-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .test-chart-container {
                margin-bottom: 20px;
            }
            
            .highlights-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <!-- Banner de bienvenida -->
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>Bienvenido, <?php echo htmlspecialchars($candidato['nombre']); ?></h1>
                    <p>Este es tu panel personal donde encontrarás toda la información sobre tu perfil, evaluaciones y oportunidades laborales.</p>
                    
                    <div class="welcome-stats">
                        <div class="welcome-stat">
                            <h3>Pruebas Completadas</h3>
                            <p><?php echo $pruebasCompletadasCount; ?></p>
                        </div>
                        <div class="welcome-stat">
                            <h3>Aplicaciones</h3>
                            <p><?php echo count($aplicaciones); ?></p>
                        </div>
                        <div class="welcome-stat">
                            <h3>Perfil</h3>
                            <p><?php echo $completitudPerfil; ?>%</p>
                        </div>
                    </div>
                </div>
                
                <div class="welcome-actions">
                    <a href="vacantes.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Explorar Vacantes
                    </a>
                </div>
            </div>
            
            <!-- Resumen de perfil -->
            <div class="profile-summary">
                <!-- Tarjeta de Perfil -->
                <div class="profile-card">
                    <div class="perfil-header">
                        <h2><i class="fas fa-user"></i> Perfil Profesional</h2>
                        <a href="profile.php" class="btn-link">Editar</a>
                    </div>
                    <div class="perfil-body">
                        <div class="perfil-completion">
                            <span>Completitud del perfil</span>
                            <span><?php echo $completitudPerfil; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $completitudPerfil >= 80 ? 'success' : ($completitudPerfil >= 50 ? '' : 'warning'); ?>" style="width: <?php echo $completitudPerfil; ?>%"></div>
                        </div>
                        
                        <?php if ($completitudPerfil < 100): ?>
                        <div class="missing-items">
                            <p>Complete los siguientes campos para mejorar su perfil:</p>
                            <ul>
                                <?php if (empty($candidato['ubicacion'])): ?>
                                <li>Ubicación</li>
                                <?php endif; ?>
                                <?php if (empty($candidato['nivel_educativo'])): ?>
                                <li>Nivel educativo</li>
                                <?php endif; ?>
                                <?php if (empty($candidato['experiencia_general'])): ?>
                                <li>Experiencia general</li>
                                <?php endif; ?>
                                <?php if (empty($candidato['cv_path'])): ?>
                                <li>Currículum Vitae</li>
                                <?php endif; ?>
                            </ul>
                            <a href="profile.php" class="btn btn-sm btn-primary">Completar perfil</a>
                        </div>
                        <?php else: ?>
                        <p>¡Felicidades! Has completado tu perfil al 100%.</p>
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">Ver perfil</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tarjeta de Resultados -->
                <?php if ($pruebasCompletadasCount > 0 && $resultado_global > 0): ?>
                <div class="profile-card">
                    <div class="perfil-header">
                        <h2><i class="fas fa-chart-pie"></i> Resultados de Evaluaciones</h2>
                        <a href="pruebas.php?tab=completadas" class="btn-link">Ver todo</a>
                    </div>
                    <div class="perfil-body">
                        <div class="results-card">
                            <div class="test-chart-container">
                                <canvas id="scoreChart"></canvas>
                                <div class="chart-score"><?php echo $resultado_global; ?></div>
                            </div>
                            <div class="test-results-info">
                                <div class="profile-type">
                                    <span class="profile-badge <?php echo $perfil_class; ?>"><?php echo $perfil_tipo; ?></span>
                                    <p><?php echo $perfil_descripcion; ?></p>
                                </div>
                                <div class="profile-actions">
                                    <a href="pruebas.php?tab=completadas" class="btn btn-sm btn-primary">Ver resultados detallados</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($pruebasPendientesCount > 0): ?>
                <div class="profile-card">
                    <div class="perfil-header">
                        <h2><i class="fas fa-clipboard-list"></i> Evaluaciones Pendientes</h2>
                    </div>
                    <div class="perfil-body">
                        <p>Tienes <?php echo $pruebasPendientesCount; ?> evaluaciones pendientes. Completa estas evaluaciones para obtener un perfil profesional más detallado.</p>
                        
                        <a href="pruebas.php?tab=pendientes" class="btn btn-primary">
                            <i class="fas fa-play"></i> Iniciar evaluaciones
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="profile-card">
                    <div class="perfil-header">
                        <h2><i class="fas fa-star"></i> Servicios Premium</h2>
                    </div>
                    <div class="perfil-body">
                        <p>Descubre nuestros servicios premium para potenciar tu desarrollo profesional y destacar entre los candidatos.</p>
                        
                        <a href="premium.php" class="btn btn-primary">
                            <i class="fas fa-crown"></i> Ver servicios premium
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="action-title">Buscar Vacantes</h3>
                    <p class="action-description">Encuentra las mejores oportunidades laborales según tu perfil.</p>
                    <a href="vacantes.php" class="btn btn-sm btn-primary">Explorar</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 class="action-title">Evaluaciones</h3>
                    <p class="action-description">Completa pruebas para mejorar tu perfil profesional.</p>
                    <a href="pruebas.php" class="btn btn-sm btn-primary">Ver evaluaciones</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="action-title">Mi CV</h3>
                    <p class="action-description">Sube o actualiza tu currículum vitae.</p>
                    <a href="profile.php#cv" class="btn btn-sm btn-primary">Actualizar CV</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="action-title">Premium</h3>
                    <p class="action-description">Accede a servicios exclusivos para potenciar tu carrera.</p>
                    <a href="premium.php" class="btn btn-sm btn-primary">Ver planes</a>
                </div>
            </div>
            
            <!-- Aplicaciones recientes -->
            <?php if (!empty($aplicaciones)): ?>
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Aplicaciones Recientes</h2>
                    <a href="aplicaciones.php">Ver todas <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($aplicaciones as $aplicacion): ?>
                    <div class="aplication-card">
                        <div class="aplication-card-header">
                            <h3><?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?></h3>
                            <?php 
                            $statusClass = '';
                            $statusText = '';
                            
                            switch($aplicacion['estado']) {
                                case 'recibida':
                                    $statusClass = 'received';
                                    $statusText = 'Recibida';
                                    break;
                                case 'revision':
                                    $statusClass = 'review';
                                    $statusText = 'En revisión';
                                    break;
                                case 'entrevista':
                                    $statusClass = 'interview';
                                    $statusText = 'Entrevista';
                                    break;
                                case 'rechazada':
                                    $statusClass = 'rejected';
                                    $statusText = 'Rechazada';
                                    break;
                                default:
                                    $statusClass = 'received';
                                    $statusText = ucfirst($aplicacion['estado']);
                            }
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </div>
                        <div class="aplication-card-body">
                            <div class="aplication-meta">
                                <div class="meta-item">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($aplicacion['categoria_nombre']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($aplicacion['modalidad']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo htmlspecialchars($aplicacion['tipo_contrato']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="aplication-card-footer">
                            <span class="aplication-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?>
                            </span>
                            <a href="aplicacion.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary">Ver detalles</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Vacantes recomendadas -->
            <?php if (!empty($vacantesRecomendadas)): ?>
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Vacantes Recomendadas</h2>
                    <a href="vacantes.php">Ver todas <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($vacantesRecomendadas as $vacante): ?>
                    <div class="vacancy-card">
                        <div class="vacancy-card-header">
                            <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                        </div>
                        <div class="vacancy-card-body">
                            <div class="vacancy-meta">
                                <div class="meta-item">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if (isset($vacante['match_percentage'])): ?>
                            <div class="match-indicator">
                                <span class="match-value"><?php echo $vacante['match_percentage']; ?>%</span>
                                <div class="match-bar">
                                    <?php
                                    $matchClass = '';
                                    if ($vacante['match_percentage'] >= 80) {
                                        $matchClass = 'high';
                                    } elseif ($vacante['match_percentage'] >= 60) {
                                        $matchClass = 'medium';
                                    } else {
                                        $matchClass = 'low';
                                    }
                                    ?>
                                    <div class="match-fill <?php echo $matchClass; ?>" style="width: <?php echo $vacante['match_percentage']; ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="vacancy-card-footer">
                            <span class="vacancy-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?>
                            </span>
                            <a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>" class="btn btn-sm btn-primary">Ver detalle</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recomendaciones y mejoras -->
            <div class="highlights-section">
                <div class="highlights-header">
                    <h2>Recomendaciones para tu desarrollo profesional</h2>
                </div>
                
                <div class="highlights-container">
                    <div class="highlight-card">
                        <div class="highlight-header">
                            <div class="highlight-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h3 class="highlight-title">Actualiza tu experiencia laboral</h3>
                        </div>
                        <p class="highlight-content">Completa toda tu experiencia laboral para aumentar las coincidencias con las vacantes disponibles.</p>
                    </div>
                    
                    <div class="highlight-card">
                        <div class="highlight-header">
                            <div class="highlight-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3 class="highlight-title">Completa tus evaluaciones</h3>
                        </div>
                        <p class="highlight-content">Las empresas valoran candidatos con evaluaciones completas. Realiza todas tus evaluaciones pendientes.</p>
                    </div>
                    
                    <div class="highlight-card">
                        <div class="highlight-header">
                            <div class="highlight-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3 class="highlight-title">Mantén tu CV actualizado</h3>
                        </div>
                        <p class="highlight-content">Un CV actualizado y bien estructurado aumenta significativamente tus oportunidades laborales.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración de gráfico de puntuación
            <?php if ($pruebasCompletadasCount > 0 && $resultado_global > 0): ?>
            var ctx = document.getElementById('scoreChart').getContext('2d');
            var scoreChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [<?php echo $resultado_global; ?>, 100 - <?php echo $resultado_global; ?>],
                        backgroundColor: [
                            '#0088cc',
                            '#eef2f7'
                        ],
                        borderWidth: 0,
                        cutout: '80%'
                    }]
                },
                options: {
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
            <?php endif; ?>
            
            // Toggle para el menú desplegable de usuario
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            
            if (userDropdown && userMenu) {
                userDropdown.addEventListener('click', function() {
                    userMenu.classList.toggle('show');
                });
                
                // Cerrar menú al hacer clic fuera
                document.addEventListener('click', function(event) {
                    if (!userDropdown.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>