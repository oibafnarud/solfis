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

// Instanciar clases necesarias
$candidateManager = new CandidateManager();
$vacancyManager = new VacancyManager();

// Si existe TestManager, usarlo
$testManager = null;
if (file_exists(__DIR__ . '/../includes/TestManager.php')) {
    require_once __DIR__ . '/../includes/TestManager.php';
    $testManager = new TestManager();
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
        ORDER BY a.fecha_aplicacion DESC";

$db = Database::getInstance();
$result = $db->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $aplicaciones[] = $row;
    }
}

// Obtener pruebas pendientes, en progreso y completadas
$pruebasPendientes = [];
$pruebasEnProgreso = [];
$pruebasCompletadas = [];

if ($testManager) {
    $pruebasPendientes = $testManager->getPendingTests($candidato_id);
    $pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);
    $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
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

// Calcular progreso general de pruebas
$totalPruebas = count($pruebasPendientes) + count($pruebasEnProgreso) + count($pruebasCompletadas);
$progresoGeneral = $totalPruebas > 0 ? round((count($pruebasCompletadas) / $totalPruebas) * 100) : 0;

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
    
    <!-- Si no existe el archivo CSS específico, puedes usar estos estilos inline -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0088cc;
            --accent-color: #ff9900;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #333333;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        /* Navbar */
        .dashboard-navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .navbar-nav {
            display: flex;
            align-items: center;
        }
        
        .nav-item {
            margin-left: 20px;
        }
        
        .nav-link {
            color: var(--dark-gray);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--secondary-color);
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .dropdown-toggle img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 8px 20px;
            color: var(--dark-gray);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-gray);
        }
        
        .dropdown-divider {
            border-top: 1px solid var(--medium-gray);
            margin: 5px 0;
        }
        
        /* Sidebar */
        .dashboard-container {
            display: flex;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        
        .dashboard-sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            position: fixed;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-item {
            margin-bottom: 5px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--dark-gray);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-link:hover {
            background-color: var(--light-gray);
            border-left-color: var(--secondary-color);
        }
        
        .sidebar-link.active {
            background-color: var(--light-gray);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .sidebar-category {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            padding: 15px 20px 5px;
            letter-spacing: 1px;
        }
        
        /* Main Content */
        .dashboard-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .content-header h1 {
            font-size: 24px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .welcome-message {
            color: #666;
            margin: 5px 0 0;
        }
        
        .btn-outline {
            padding: 8px 15px;
            border: 1px solid var(--secondary-color);
            border-radius: 5px;
            background-color: transparent;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-link i {
            margin-left: 5px;
        }
        
        .btn-link:hover {
            text-decoration: underline;
        }
        
        /* Profile Completion */
        .profile-completion {
            grid-column: span 2;
        }
        
        .profile-progress {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-progress .progress-percentage {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 20px;
        }
        
        .profile-progress .progress-bar {
            flex: 1;
            height: 12px;
        }
        
        .missing-fields {
            background-color: var(--light-gray);
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        
        .missing-fields h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: var(--dark-gray);
        }
        
        .missing-fields ul {
            margin: 0;
            padding-left: 20px;
            columns: 2;
        }
        
        .missing-fields li {
            margin-bottom: 5px;
            color: #666;
        }
        
        /* Info Banner */
        .info-banner {
            display: flex;
            align-items: flex-start;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-top: 20px;
        }
        
        .info-icon {
            font-size: 24px;
            color: var(--info-color);
            margin-right: 20px;
            padding-top: 5px;
        }
        
        .info-content h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .info-content p {
            margin: 0 0 15px;
            color: #666;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar-link span {
                display: none;
            }
            
            .sidebar-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .sidebar-category {
                display: none;
            }
            
            .dashboard-content {
                margin-left: 70px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-completion {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 15px;
            }
            
            .dashboard-sidebar {
                display: none;
            }
            
            .dashboard-content {
                margin-left: 0;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .content-header .btn-outline {
                margin-top: 10px;
            }
            
            .progress-stats {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .missing-fields ul {
                columns: 1;
            }
            
            .info-banner {
                flex-direction: column;
            }
            
            .info-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <header class="dashboard-navbar">
        <div class="navbar-container">
            <a href="panel.php" class="navbar-brand">
                <img src="../assets/img/logo.png" alt="SolFis Logo">
            </a>
            
            <div class="navbar-nav">
                <div class="nav-item">
                    <a href="../vacantes/index.php" class="nav-link">
                        <i class="fas fa-briefcase"></i> Vacantes
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
                
                <div class="nav-item dropdown">
                    <div class="dropdown-toggle" id="userDropdown">
                        <?php if (!empty($candidato['foto_path'])): ?>
                        <img src="../uploads/profile_photos/<?php echo $candidato['foto_path']; ?>" alt="<?php echo $candidato['nombre']; ?>">
                        <?php else: ?>
                        <i class="fas fa-user-circle fa-2x"></i>
                        <?php endif; ?>
                        <span><?php echo $candidato['nombre']; ?></span>
                    </div>
                    
                    <div class="dropdown-menu" id="userMenu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> Mi Perfil
                        </a>
                        <a href="pruebas.php" class="dropdown-item">
                            <i class="fas fa-clipboard-check"></i> Mis Evaluaciones
                        </a>
                        <a href="aplicaciones.php" class="dropdown-item">
                            <i class="fas fa-briefcase"></i> Mis Aplicaciones
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="configuracion.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="panel.php" class="sidebar-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Panel Principal</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="profile.php" class="sidebar-link">
                        <i class="fas fa-user"></i>
                        <span>Mi Perfil</span>
                    </a>
                </li>
                
                <li class="sidebar-category">Evaluaciones</li>
                
                <li class="sidebar-item">
                    <a href="pruebas.php" class="sidebar-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Mis Evaluaciones</span>
                    </a>
                </li>
                
                <?php if ($testManager && count($pruebasPendientes) > 0): ?>
                <li class="sidebar-item">
                    <a href="pruebas-pendientes.php" class="sidebar-link">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Pendientes (<?php echo count($pruebasPendientes); ?>)</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($testManager && count($pruebasCompletadas) > 0): ?>
                <li class="sidebar-item">
                    <a href="resultados.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Mis Resultados</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="sidebar-category">Empleo</li>
                
                <li class="sidebar-item">
                    <a href="aplicaciones.php" class="sidebar-link">
                        <i class="fas fa-briefcase"></i>
                        <span>Mis Aplicaciones</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="../vacantes/index.php" class="sidebar-link">
                        <i class="fas fa-search"></i>
                        <span>Buscar Vacantes</span>
                    </a>
                </li>
                
                <li class="sidebar-category">Cuenta</li>
                
                <li class="sidebar-item">
                    <a href="configuracion.php" class="sidebar-link">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="logout.php" class="sidebar-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="dashboard-content">
            <div class="content-header">
                <div>
                    <h1>Bienvenido, <?php echo $candidato['nombre']; ?></h1>
                    <p class="welcome-message">Este es tu panel personal donde podrás gestionar tu perfil y completar evaluaciones.</p>
                </div>
                <a href="profile.php" class="btn-outline">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
            </div>
            
            <!-- Progreso General -->
            <?php if ($testManager && $totalPruebas > 0): ?>
            <div class="progress-overview">
                <div class="overview-header">
                    <h2>Progreso de Evaluaciones</h2>
                    <span class="progress-percentage"><?php echo $progresoGeneral; ?>% completado</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progresoGeneral; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($pruebasCompletadas); ?></div>
                        <div class="stat-label">Evaluaciones completadas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($pruebasEnProgreso); ?></div>
                        <div class="stat-label">En progreso</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($pruebasPendientes); ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Evaluaciones Pendientes -->
                <?php if ($testManager && count($pruebasPendientes) > 0): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-clipboard-list"></i> Evaluaciones pendientes</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($pruebasPendientes, 0, 3) as $prueba): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo htmlspecialchars($prueba['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($prueba['descripcion'], 0, 100) . '...'); ?></p>
                                    <div class="test-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo $prueba['tiempo_estimado']; ?> min</span>
                                    </div>
                                </div>
                                <a href="prueba.php?id=<?php echo $prueba['id']; ?>" class="btn-primary">Iniciar</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($pruebasPendientes) > 3): ?>
                        <div class="view-all">
                            <a href="pruebas-pendientes.php" class="btn-link">Ver todas las evaluaciones <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Evaluaciones Completadas -->
                <?php if ($testManager && count($pruebasCompletadas) > 0): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-check-circle"></i> Evaluaciones completadas</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($pruebasCompletadas, 0, 3) as $prueba): ?>
                            <div class="test-item completed">
                                <div class="test-info">
                                    <h3><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-calendar-check"></i> Completada: <?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></span>
                                    </div>
                                </div>
                                <div class="completion-badge">
                                    <i class="fas fa-check"></i> Completada
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="premium-banner">
                            <div class="premium-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="premium-info">
                                <h3>Resultados detallados disponibles</h3>
                                <p>Conoce tus fortalezas, áreas de desarrollo y recibe asesoramiento personalizado.</p>
                                <a href="premium.php" class="btn-outline">Conocer servicios premium</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Aplicaciones Recientes -->
                <?php if (!empty($aplicaciones)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-briefcase"></i> Aplicaciones recientes</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($aplicaciones, 0, 3) as $aplicacion): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></span>
                                        <span>
                                            <i class="fas fa-circle" style="color: 
                                                <?php
                                                    switch ($aplicacion['estado']) {
                                                        case 'recibida': echo '#17a2b8'; break;
                                                        case 'revisada': echo '#ffc107'; break;
                                                        case 'entrevista': echo '#28a745'; break;
                                                        case 'rechazada': echo '#dc3545'; break;
                                                        default: echo '#6c757d';
                                                    }
                                                ?>; font-size: 10px;"></i>
                                            <?php echo ucfirst($aplicacion['estado']); ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="aplicacion.php?id=<?php echo $aplicacion['id']; ?>" class="btn-outline">Detalles</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($aplicaciones) > 3): ?>
                        <div class="view-all">
                            <a href="aplicaciones.php" class="btn-link">Ver todas las aplicaciones <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Vacantes Recomendadas -->
                <?php if (!empty($vacantesRecomendadas)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-thumbs-up"></i> Vacantes recomendadas</h2>
                    </div>
                    <div class="card-body">
                        <div class="job-list">
                            <?php foreach ($vacantesRecomendadas as $vacante): ?>
                            <div class="job-item">
                                <div class="job-info">
                                    <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                                    <div class="job-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                        <span><i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                    </div>
                                </div>
                                <?php if (isset($vacante['match_percentage'])): ?>
                                <div class="job-match">
                                    <div class="match-percentage"><?php echo $vacante['match_percentage']; ?>%</div>
                                    <div class="match-label">Compatibilidad</div>
                                </div>
                                <?php endif; ?>
                                <a href="../vacantes/detalle.php?id=<?php echo $vacante['id']; ?>" class="btn-primary">Ver detalle</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="view-all">
                            <a href="../vacantes/listado.php" class="btn-link">Ver todas las vacantes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Completar Perfil -->
                <?php if ($completitudPerfil < 100): ?>
                <div class="dashboard-card profile-completion">
                    <div class="card-header">
                        <h2><i class="fas fa-user-edit"></i> Completa tu perfil</h2>
                    </div>
                    <div class="card-body">
                        <div class="profile-progress">
                            <div class="progress-percentage"><?php echo $completitudPerfil; ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $completitudPerfil; ?>%"></div>
                            </div>
                        </div>
                        
                        <p>Tener un perfil completo aumenta tus posibilidades de ser contactado para oportunidades laborales.</p>
                        
                        <div class="missing-fields">
                            <h4>Campos pendientes:</h4>
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
                                <?php if (empty($candidato['linkedin'])): ?>
                                <li>Perfil de LinkedIn</li>
                                <?php endif; ?>
                                <?php if (empty($candidato['foto_path'])): ?>
                                <li>Foto de perfil</li>
                                <?php endif; ?>
                                <?php if (empty($candidato['habilidades_destacadas'])): ?>
                                <li>Habilidades destacadas</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <a href="profile.php" class="btn-primary">Completar mi perfil</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Banner informativo -->
            <div class="info-banner">
                <div class="info-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-content">
                    <h3>¿Cómo funciona nuestro sistema de evaluaciones?</h3>
                    <p>Las evaluaciones psicométricas nos permiten identificar tus fortalezas y áreas de desarrollo profesional. Completar todas las evaluaciones aumentará tus posibilidades de ser considerado para las oportunidades que mejor se ajusten a tu perfil.</p>
                    <a href="faq.php" class="btn-link">Más información <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle para el menú desplegable de usuario
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            
            userDropdown.addEventListener('click', function() {
                userMenu.classList.toggle('show');
            });
            
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(event) {
                if (!userDropdown.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.remove('show');
                }
            });
            
            // Resaltar enlace activo del sidebar
            const currentPage = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>