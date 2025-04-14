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
            
            // Contar correctamente
            $pruebasPendientesCount = is_array($pruebasPendientes) ? count($pruebasPendientes) : 0;
            $pruebasEnProgresoCount = is_array($pruebasEnProgreso) ? count($pruebasEnProgreso) : 0;
            $pruebasCompletadasCount = is_array($pruebasCompletadas) ? count($pruebasCompletadas) : 0;
            
            $totalPruebas = $pruebasPendientesCount + $pruebasEnProgresoCount + $pruebasCompletadasCount;
            
            // Calcular progreso general
            $progresoGeneral = $totalPruebas > 0 ? round(($pruebasCompletadasCount / $totalPruebas) * 100) : 0;
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
    
    <style>
        /* Estilos para la tarjeta de progreso */
        .progress-overview {
            background-color: #f0f8ff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .overview-header h2 {
            font-size: 1.2rem;
            color: #003366;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .overview-header h2 i {
            margin-right: 10px;
        }

        .progress-percentage {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0088cc;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .progress-fill {
            height: 100%;
            background-color: #0088cc;
            border-radius: 5px;
        }

        .progress-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            padding: 0 15px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #003366;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Estilos para el listado de pruebas */
        .test-list {
            margin-bottom: 20px;
        }

        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .test-item:last-child {
            border-bottom: none;
        }

        .test-info h3 {
            margin: 0 0 5px;
            font-size: 1rem;
            color: #212529;
        }

        .test-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .test-meta span {
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }

        .test-meta span i {
            margin-right: 5px;
        }

        .btn-primary {
            background-color: #0088cc;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #0077b3;
        }

        .view-all {
            text-align: right;
            margin-top: 10px;
        }

        .btn-link {
            color: #0088cc;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .btn-link i {
            margin-left: 5px;
        }
        
        /* Estilos para el botón Iniciar */
        .btn-accent {
            background-color: #ff9900;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .btn-accent:hover {
            background-color: #e68a00;
        }
        
        /* Estilos adicionales para botones */
        .btn-secondary {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #138496;
        }
        
        .btn-outline-primary {
            background-color: transparent;
            color: #0088cc;
            border: 1px solid #0088cc;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .btn-outline-primary:hover {
            background-color: #0088cc;
            color: white;
        }
        
        /* Estilos para tarjetas */
        .card-header.light-primary {
            background-color: rgba(0, 51, 102, 0.1);
            color: #003366;
        }
        
        .card-header.light-secondary {
            background-color: rgba(0, 136, 204, 0.1);
            color: #0088cc;
        }
        
        .card-header.light-accent {
            background-color: rgba(255, 153, 0, 0.1);
            color: #ff9900;
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
                    <a href="vacantes.php" class="nav-link">
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
                
                <?php if (isset($pruebasPendientes) && !empty($pruebasPendientes)): ?>
                <li class="sidebar-item">
                    <a href="pruebas-pendientes.php" class="sidebar-link">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Pendientes (<?php echo count($pruebasPendientes); ?>)</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($pruebasCompletadas) && !empty($pruebasCompletadas)): ?>
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
                    <a href="vacantes.php" class="sidebar-link">
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
                <a href="profile.php" class="btn-outline-primary">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
            </div>
            
            <!-- Progreso General de Evaluaciones -->
            <?php if (isset($testManager) && $testManager && isset($totalPruebas) && $totalPruebas > 0): ?>
            <div class="progress-overview">
                <div class="overview-header">
                    <h2><i class="fas fa-chart-line"></i> Progreso de Evaluaciones</h2>
                    <span class="progress-percentage"><?php echo $progresoGeneral; ?>% completado</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progresoGeneral; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pruebasCompletadasCount; ?></div>
                        <div class="stat-label">Completadas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pruebasEnProgresoCount; ?></div>
                        <div class="stat-label">En progreso</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pruebasPendientesCount; ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Evaluaciones Pendientes -->
                <?php if (isset($testManager) && $testManager && isset($pruebasPendientes) && !empty($pruebasPendientes)): ?>
                <div class="dashboard-card">
                    <div class="card-header light-accent">
                        <h2><i class="fas fa-clipboard-list"></i> Evaluaciones Pendientes</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($pruebasPendientes, 0, 3) as $prueba): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo htmlspecialchars($prueba['titulo']); ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo isset($prueba['tiempo_estimado']) ? $prueba['tiempo_estimado'] : '30'; ?> min</span>
                                        <?php if (isset($prueba['categoria_nombre'])): ?>
                                        <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($prueba['categoria_nombre']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="prueba.php?id=<?php echo $prueba['id']; ?>" class="btn-accent">
                                    <i class="fas fa-play"></i> Iniciar
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($pruebasPendientes) > 3): ?>
                        <div class="view-all">
                            <a href="pruebas.php?tab=pendientes" class="btn-link">
                                Ver todas las evaluaciones <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Evaluaciones en progreso -->
                <?php if (isset($testManager) && $testManager && isset($pruebasEnProgreso) && !empty($pruebasEnProgreso)): ?>
                <div class="dashboard-card">
                    <div class="card-header light-secondary">
                        <h2><i class="fas fa-spinner"></i> Evaluaciones en Progreso</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($pruebasEnProgreso, 0, 3) as $prueba): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo isset($prueba['prueba_titulo']) ? htmlspecialchars($prueba['prueba_titulo']) : htmlspecialchars($prueba['titulo']); ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-calendar"></i> Iniciada: <?php echo date('d/m/Y', strtotime($prueba['fecha_inicio'])); ?></span>
                                        <?php 
                                        // Obtener estadísticas si están disponibles
                                        $stats = null;
                                        if (method_exists($testManager, 'getSessionStats')) {
                                            $stats = $testManager->getSessionStats($prueba['id']);
                                        }
                                        ?>
                                        <?php if (isset($stats) && isset($stats['respondidas']) && isset($stats['total'])): ?>
                                        <span><i class="fas fa-tasks"></i> <?php echo $stats['respondidas']; ?> de <?php echo $stats['total']; ?> preguntas</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="prueba.php?id=<?php echo isset($prueba['prueba_id']) ? $prueba['prueba_id'] : $prueba['id']; ?>" class="btn-secondary">
                                    <i class="fas fa-sync-alt"></i> Continuar
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($pruebasEnProgreso) > 3): ?>
                        <div class="view-all">
                            <a href="pruebas.php?tab=progreso" class="btn-link">
                                Ver todas las evaluaciones en progreso <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Evaluaciones completadas -->
                <?php if (isset($testManager) && $testManager && isset($pruebasCompletadas) && !empty($pruebasCompletadas)): ?>
                <div class="dashboard-card">
                    <div class="card-header light-primary">
                        <h2><i class="fas fa-check-circle"></i> Evaluaciones Completadas</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach (array_slice($pruebasCompletadas, 0, 3) as $prueba): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo isset($prueba['prueba_titulo']) ? htmlspecialchars($prueba['prueba_titulo']) : 'Evaluación'; ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-calendar-check"></i> Completada: <?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></span>
                                        <?php if (isset($prueba['resultado_global'])): ?>
                                        <span><i class="fas fa-chart-bar"></i> Resultado: <?php echo $prueba['resultado_global']; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="resultado-prueba.php?sesion_id=<?php echo isset($prueba['sesion_id']) ? $prueba['sesion_id'] : $prueba['id']; ?>" class="btn-outline-primary">
                                    <i class="fas fa-chart-pie"></i> Ver Resultados
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($pruebasCompletadas) > 3): ?>
                        <div class="view-all">
                            <a href="pruebas.php?tab=completadas" class="btn-link">
                                Ver todas las evaluaciones completadas <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Completar Perfil -->
                <?php if ($completitudPerfil < 100): ?>
                <div class="dashboard-card">
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
                
                <!-- Aplicaciones Recientes -->
                <?php if (!empty($aplicaciones)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-briefcase"></i> Aplicaciones recientes</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach ($aplicaciones as $aplicacion): ?>
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
                                <a href="aplicacion.php?id=<?php echo $aplicacion['id']; ?>" class="btn-outline-primary">Detalles</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="view-all">
                            <a href="aplicaciones.php" class="btn-link">
                                Ver todas las aplicaciones <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Vacantes Recomendadas -->
                <?php if (!empty($vacantesRecomendadas)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-star"></i> Vacantes recomendadas</h2>
                    </div>
                    <div class="card-body">
                        <div class="test-list">
                            <?php foreach ($vacantesRecomendadas as $vacante): ?>
                            <div class="test-item">
                                <div class="test-info">
                                    <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                                    <div class="test-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                        <span><i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                        <?php if (isset($vacante['match_percentage'])): ?>
                                        <span><i class="fas fa-star"></i> <?php echo $vacante['match_percentage']; ?>% compatible</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>" class="btn-primary">Ver detalle</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="view-all">
                            <a href="vacantes.php" class="btn-link">
                                Ver todas las vacantes <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Banner informativo -->
            <div class="info-banner">
                <div class="info-banner-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-banner-content">
                    <h3>¿Cómo funciona nuestro sistema de evaluaciones?</h3>
                    <p>Las evaluaciones psicométricas nos permiten identificar tus fortalezas y áreas de desarrollo profesional. Completar todas las evaluaciones aumentará tus posibilidades de ser considerado para las oportunidades que mejor se ajusten a tu perfil.</p>
                    <a href="faq.php" class="btn-link">
                        Más información <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle para el menú desplegable de usuario
        document.addEventListener('DOMContentLoaded', function() {
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