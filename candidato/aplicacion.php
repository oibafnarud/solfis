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
$applicationManager = new ApplicationManager();
$vacancyManager = new VacancyManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Si no existe el candidato, cerrar sesión
if (!$candidato) {
    session_destroy();
    header('Location: login.php?error=candidato_no_encontrado');
    exit;
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: aplicaciones.php');
    exit;
}

// Obtener ID de la aplicación
$id = (int)$_GET['id'];

// Obtener la aplicación
$aplicacion = $applicationManager->getApplicationById($id);

// Si la aplicación no existe o no pertenece al candidato, redirigir
if (!$aplicacion || $aplicacion['candidato_id'] != $candidato_id) {
    header('Location: aplicaciones.php');
    exit;
}

// Obtener detalles de la vacante
$vacante = $vacancyManager->getVacancyById($aplicacion['vacante_id']);

// Obtener historial de etapas/estados de la aplicación
$etapas = $applicationManager->getApplicationStages($aplicacion['id']);

// Obtener entrevistas programadas
$entrevistas = $applicationManager->getScheduledInterviews($aplicacion['id']);

// Título de la página
$site_title = "Detalle de Aplicación - SolFis Talentos";
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
    
    <!-- Estilos personalizados para el detalle de aplicación -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0088cc;
            --accent-color: #ff9900;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
        
        body {
            background-color: var(--gray-100);
            font-family: 'Poppins', sans-serif;
        }
        
        /* Layout de la página */
        .application-detail-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        /* Tarjetas generales */
        .detail-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .detail-card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            display: flex;
            align-items: center;
        }
        
        .detail-card-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        /* Header de la aplicación */
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .application-job-info h1 {
            font-size: 1.5rem;
            margin: 0 0 0.5rem;
            color: var(--gray-800);
        }
        
        .application-job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .application-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .application-meta-item i {
            margin-right: 0.5rem;
        }
        
        /* Estado actual de la aplicación */
        .application-status-banner {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            background-color: rgba(0, 136, 204, 0.1);
        }
        
        .application-status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--info-color);
            font-size: 1.25rem;
        }
        
        .application-status-content h2 {
            margin: 0 0 0.25rem;
            font-size: 1.1rem;
            color: var(--info-color);
            font-weight: 600;
        }
        
        .application-status-content p {
            margin: 0;
            color: var(--gray-700);
            font-size: 0.9rem;
        }
        
        /* Estilos específicos para cada estado */
        .application-status-banner.recibida {
            background-color: rgba(23, 162, 184, 0.1);
        }
        
        .application-status-banner.recibida .application-status-icon {
            color: var(--info-color);
        }
        
        .application-status-banner.recibida .application-status-content h2 {
            color: var(--info-color);
        }
        
        .application-status-banner.revision {
            background-color: rgba(0, 51, 102, 0.1);
        }
        
        .application-status-banner.revision .application-status-icon {
            color: var(--primary-color);
        }
        
        .application-status-banner.revision .application-status-content h2 {
            color: var(--primary-color);
        }
        
        .application-status-banner.entrevista,
        .application-status-banner.prueba {
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .application-status-banner.entrevista .application-status-icon,
        .application-status-banner.prueba .application-status-icon {
            color: #d39e00;
        }
        
        .application-status-banner.entrevista .application-status-content h2,
        .application-status-banner.prueba .application-status-content h2 {
            color: #d39e00;
        }
        
        .application-status-banner.oferta,
        .application-status-banner.contratado {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .application-status-banner.oferta .application-status-icon,
        .application-status-banner.contratado .application-status-icon {
            color: var(--success-color);
        }
        
        .application-status-banner.oferta .application-status-content h2,
        .application-status-banner.contratado .application-status-content h2 {
            color: var(--success-color);
        }
        
        .application-status-banner.rechazada {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .application-status-banner.rechazada .application-status-icon {
            color: var(--danger-color);
        }
        
        .application-status-banner.rechazada .application-status-content h2 {
            color: var(--danger-color);
        }
        
        /* Entrevistas programadas */
        .interview-card {
            background-color: var(--gray-100);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--warning-color);
        }
        
        .interview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .interview-title {
            font-weight: 600;
            margin: 0;
            color: var(--gray-800);
            font-size: 1rem;
        }
        
        .interview-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
        }
        
        .interview-status.pendiente {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .interview-status.completada {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .interview-status.cancelada {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .interview-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .interview-detail-item {
            display: flex;
            align-items: center;
        }
        
        .interview-detail-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--gray-700);
        }
        
        .interview-detail-text {
            display: flex;
            flex-direction: column;
        }
        
        .interview-detail-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 0.125rem;
        }
        
        .interview-detail-value {
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 500;
        }
        
        .interview-actions {
            margin-top: 0.75rem;
            display: flex;
            justify-content: flex-end;
        }
        
        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #00264d;
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #006699;
            color: white;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            color: #212529;
        }
        
        .btn-outline-primary {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-outline-secondary {
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
            background: transparent;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        /* Timeline de la aplicación */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0.6875rem;
            width: 2px;
            background-color: var(--gray-300);
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-marker {
            position: absolute;
            top: 0;
            left: -2rem;
            width: 1.375rem;
            height: 1.375rem;
            border-radius: 50%;
            background-color: white;
            border: 2px solid var(--gray-400);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
            font-size: 0.75rem;
            z-index: 1;
        }
        
        .timeline-marker.status-change {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .timeline-marker.interview {
            border-color: var(--warning-color);
            color: #d39e00;
        }
        
        .timeline-marker.note {
            border-color: var(--info-color);
            color: var(--info-color);
        }
        
        .timeline-content {
            background-color: var(--gray-100);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .timeline-title {
            margin: 0 0 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .timeline-date {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .timeline-text {
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        
        /* Detalles de la vacante */
        .job-info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .job-info-item:last-child {
            border-bottom: none;
        }
        
        .job-info-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .job-info-value {
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 500;
            text-align: right;
        }
        
        /* Dashboard content updates */
        .dashboard-content {
            padding: 1.5rem;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out forwards;
        }
        
        /* Media queries para dispositivos móviles */
        @media (max-width: 991px) {
            .application-detail-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="breadcrumbs animate-fade-in" style="margin-bottom: 1.5rem;">
                <a href="panel.php">Panel</a> <span class="separator">/</span>
                <a href="aplicaciones.php">Aplicaciones</a> <span class="separator">/</span>
                <span class="current">Detalle de Aplicación</span>
            </div>
            
            <!-- Header de la aplicación -->
            <div class="application-header animate-fade-in">
                <div class="application-job-info">
                    <h1><?php echo htmlspecialchars($vacante['titulo']); ?></h1>
                    <div class="application-job-meta">
                        <span class="application-meta-item">
                            <i class="fas fa-building"></i> SolFis
                        </span>
                        <span class="application-meta-item">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?>
                        </span>
                        <span class="application-meta-item">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?>
                        </span>
                        <span class="application-meta-item">
                            <i class="fas fa-calendar-alt"></i> Aplicada: <?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?>
                        </span>
                    </div>
                </div>
                <a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>" class="btn-outline-primary">
                    <i class="fas fa-eye"></i> Ver Vacante
                </a>
            </div>
            
            <!-- Estado de la aplicación -->
            <div class="application-status-banner <?php echo $aplicacion['estado']; ?> animate-fade-in">
                <div class="application-status-icon">
                    <?php
                    // Icono según estado
                    $statusIcon = '';
                    switch ($aplicacion['estado']) {
                        case 'recibida':
                            $statusIcon = 'fas fa-inbox';
                            break;
                        case 'revision':
                            $statusIcon = 'fas fa-eye';
                            break;
                        case 'entrevista':
                            $statusIcon = 'fas fa-user-tie';
                            break;
                        case 'prueba':
                            $statusIcon = 'fas fa-clipboard-check';
                            break;
                        case 'oferta':
                            $statusIcon = 'fas fa-file-contract';
                            break;
                        case 'contratado':
                            $statusIcon = 'fas fa-handshake';
                            break;
                        case 'rechazada':
                            $statusIcon = 'fas fa-times-circle';
                            break;
                        default:
                            $statusIcon = 'fas fa-circle';
                    }
                    ?>
                    <i class="<?php echo $statusIcon; ?>"></i>
                </div>
                <div class="application-status-content">
                    <h2>Estado actual: <?php echo ucfirst($aplicacion['estado']); ?></h2>
                    <?php if ($aplicacion['estado'] == 'recibida'): ?>
                    <p>Tu aplicación ha sido recibida. El equipo de Recursos Humanos revisará tu perfil y te notificará cuando haya actualizaciones.</p>
                    <?php elseif ($aplicacion['estado'] == 'revision'): ?>
                    <p>Tu aplicación está siendo revisada por el equipo de Recursos Humanos. Te contactaremos pronto para los siguientes pasos.</p>
                    <?php elseif ($aplicacion['estado'] == 'entrevista'): ?>
                    <p>¡Felicitaciones! Has avanzado a la etapa de entrevistas. Revisa la información de tus entrevistas programadas a continuación.</p>
                    <?php elseif ($aplicacion['estado'] == 'prueba'): ?>
                    <p>Estás en la etapa de pruebas. Por favor completa todas las evaluaciones asignadas en tu panel de evaluaciones.</p>
                    <?php elseif ($aplicacion['estado'] == 'oferta'): ?>
                    <p>¡Felicitaciones! Hemos preparado una oferta para ti. Recibirás un correo electrónico con los detalles próximamente.</p>
                    <?php elseif ($aplicacion['estado'] == 'contratado'): ?>
                    <p>¡Bienvenido a SolFis! El proceso de aplicación ha sido completado exitosamente. El equipo de Recursos Humanos te contactará para el proceso de onboarding.</p>
                    <?php elseif ($aplicacion['estado'] == 'rechazada'): ?>
                    <p>Lamentablemente, no continuaremos con tu aplicación en esta oportunidad. Te animamos a aplicar a otras vacantes que se ajusten a tu perfil.</p>
                    <?php else: ?>
                    <p>El estado de tu aplicación es: <?php echo $aplicacion['estado']; ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="application-detail-layout">
                <div class="application-main">
                    <!-- Entrevistas Programadas -->
                    <?php if (!empty($entrevistas)): ?>
                    <div class="detail-card animate-fade-in">
                        <div class="detail-card-header">
                            <h2 class="detail-card-title">
                                <i class="fas fa-calendar-check"></i> Entrevistas Programadas
                            </h2>
                        </div>
                        
                        <?php foreach ($entrevistas as $entrevista): ?>
                        <?php
                            // Extraer información de la entrevista
                            $info = $applicationManager->parseInterviewInfo($entrevista['notas']);
                        ?>
                        <div class="interview-card">
                            <div class="interview-header">
                                <h3 class="interview-title"><?php echo htmlspecialchars($entrevista['etapa']); ?></h3>
                                <span class="interview-status <?php echo $entrevista['estado']; ?>">
                                    <?php echo ucfirst($entrevista['estado']); ?>
                                </span>
                            </div>
                            
                            <div class="interview-details">
                                <?php if (!empty($info['tipo'])): ?>
                                <div class="interview-detail-item">
                                    <div class="interview-detail-icon">
                                        <i class="fas fa-video"></i>
                                    </div>
                                    <div class="interview-detail-text">
                                        <span class="interview-detail-label">Tipo</span>
                                        <span class="interview-detail-value"><?php echo htmlspecialchars($info['tipo']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($info['fecha'])): ?>
                                <div class="interview-detail-item">
                                    <div class="interview-detail-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="interview-detail-text">
                                        <span class="interview-detail-label">Fecha y hora</span>
                                        <span class="interview-detail-value"><?php echo htmlspecialchars($info['fecha']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($info['lugar'])): ?>
                                <div class="interview-detail-item">
                                    <div class="interview-detail-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="interview-detail-text">
                                        <span class="interview-detail-label">Lugar/Enlace</span>
                                        <span class="interview-detail-value">
                                            <?php if (filter_var($info['lugar'], FILTER_VALIDATE_URL)): ?>
                                            <a href="<?php echo htmlspecialchars($info['lugar']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($info['lugar']); ?></a>
                                            <?php else: ?>
                                            <?php echo htmlspecialchars($info['lugar']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($entrevista['notas']) && $entrevista['estado'] == 'pendiente'): ?>
                            <div class="interview-actions">
                                <?php if (!empty($info['lugar']) && filter_var($info['lugar'], FILTER_VALIDATE_URL)): ?>
                                <a href="<?php echo htmlspecialchars($info['lugar']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                    <i class="fas fa-video"></i> Unirse a la entrevista
                                </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-sm btn-outline-primary add-to-calendar" data-date="<?php echo htmlspecialchars($info['fecha'] ?? ''); ?>" data-title="<?php echo htmlspecialchars($entrevista['etapa']); ?>" data-location="<?php echo htmlspecialchars($info['lugar'] ?? ''); ?>">
                                    <i class="fas fa-calendar-plus"></i> Añadir al calendario
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Timeline de la aplicación -->
                    <div class="detail-card animate-fade-in">
                        <div class="detail-card-header">
                            <h2 class="detail-card-title">
                                <i class="fas fa-history"></i> Historial de la Aplicación
                            </h2>
                        </div>
                        
                        <div class="timeline">
                            <?php if (!empty($etapas)): ?>
                                <?php foreach ($etapas as $etapa): ?>
                                <div class="timeline-item">
                                    <?php
                                    // Determinar tipo de etapa para el icono
                                    $tipoEtapa = 'note';
                                    $iconoEtapa = 'fas fa-sticky-note';
                                    
                                    if (strpos($etapa['etapa'], 'Cambio de estado') === 0) {
                                        $tipoEtapa = 'status-change';
                                        $iconoEtapa = 'fas fa-exchange-alt';
                                    } elseif (strpos($etapa['etapa'], 'Entrevista') === 0) {
                                        $tipoEtapa = 'interview';
                                        $iconoEtapa = 'fas fa-user-tie';
                                    } elseif (strpos($etapa['etapa'], 'Evaluación') === 0 || strpos($etapa['etapa'], 'Prueba') === 0) {
                                        $tipoEtapa = 'test';
                                        $iconoEtapa = 'fas fa-clipboard-check';
                                    }
                                    ?>
                                    <div class="timeline-marker <?php echo $tipoEtapa; ?>">
                                        <i class="<?php echo $iconoEtapa; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h3 class="timeline-title"><?php echo htmlspecialchars($etapa['etapa']); ?></h3>
                                        <div class="timeline-date">
                                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($etapa['fecha'])); ?>
                                        </div>
                                        <?php if (!empty($etapa['notas'])): ?>
                                        <div class="timeline-text">
                                            <?php echo nl2br(htmlspecialchars($etapa['notas'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <i class="fas fa-paper-plane"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h3 class="timeline-title">Aplicación enviada</h3>
                                        <div class="timeline-date">
                                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($aplicacion['fecha_aplicacion'])); ?>
                                        </div>
                                        <div class="timeline-text">
                                            Tu aplicación ha sido recibida. El equipo de Recursos Humanos revisará tu información.
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Carta de presentación / Otra información -->
                    <?php if (!empty($aplicacion['carta_presentacion']) || !empty($datos_adicionales)): ?>
                    <div class="detail-card animate-fade-in">
                        <div class="detail-card-header">
                            <h2 class="detail-card-title">
                                <i class="fas fa-file-alt"></i> Información de la Aplicación
                            </h2>
                        </div>
                        
                        <?php if (!empty($aplicacion['carta_presentacion'])): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <h3 style="font-size: 1rem; margin-bottom: 0.75rem;">Carta de Presentación</h3>
                            <div style="padding: 1rem; background-color: var(--gray-100); border-radius: 0.5rem; color: var(--gray-700);">
                                <?php echo nl2br(htmlspecialchars($aplicacion['carta_presentacion'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Mostrar datos adicionales si existen
                        if (!empty($aplicacion['datos_adicionales'])) {
                            $datos_adicionales = json_decode($aplicacion['datos_adicionales'], true);
                            if ($datos_adicionales) {
                                foreach ($datos_adicionales as $campo => $valor) {
                                    if (!empty($valor)) {
                                        $campo_formato = str_replace('_', ' ', ucfirst($campo));
                                        echo '<div class="job-info-item">';
                                        echo '<span class="job-info-label">' . htmlspecialchars($campo_formato) . '</span>';
echo '<span class="job-info-label">' . htmlspecialchars($campo_formato) . '</span>';
                                        echo '<span class="job-info-value">' . htmlspecialchars($valor) . '</span>';
                                        echo '</div>';
                                    }
                                }
                            }
                        }
                        
                        // Mostrar campos comunes de la aplicación
                        $campos_mostrar = [
                            'experiencia' => 'Experiencia específica',
                            'empresa_actual' => 'Empresa actual',
                            'cargo_actual' => 'Cargo actual',
                            'salario_esperado' => 'Expectativa salarial',
                            'disponibilidad' => 'Disponibilidad para comenzar',
                            'modalidad_preferida' => 'Modalidad preferida',
                            'tipo_contrato_preferido' => 'Tipo de contrato preferido'
                        ];
                        
                        foreach ($campos_mostrar as $campo => $etiqueta) {
                            if (!empty($aplicacion[$campo])) {
                                $valor = $aplicacion[$campo];
                                
                                // Dar formato a algunos valores
                                if ($campo === 'experiencia') {
                                    switch ($valor) {
                                        case 'sin-experiencia': $valor = 'Sin experiencia'; break;
                                        case 'menos-1': $valor = 'Menos de 1 año'; break;
                                        case '1-3': $valor = '1-3 años'; break;
                                        case '3-5': $valor = '3-5 años'; break;
                                        case '5-10': $valor = '5-10 años'; break;
                                        case 'mas-10': $valor = 'Más de 10 años'; break;
                                    }
                                } elseif ($campo === 'disponibilidad') {
                                    switch ($valor) {
                                        case 'inmediata': $valor = 'Inmediata'; break;
                                        case '2-semanas': $valor = '2 semanas'; break;
                                        case '1-mes': $valor = '1 mes'; break;
                                        case 'mas-1-mes': $valor = 'Más de 1 mes'; break;
                                    }
                                } elseif ($campo === 'modalidad_preferida') {
                                    $valor = ucfirst($valor);
                                } elseif ($campo === 'tipo_contrato_preferido') {
                                    $valor = ucfirst(str_replace('_', ' ', $valor));
                                }
                                
                                echo '<div class="job-info-item">';
                                echo '<span class="job-info-label">' . htmlspecialchars($etiqueta) . '</span>';
                                echo '<span class="job-info-value">' . htmlspecialchars($valor) . '</span>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="application-sidebar">
                    <!-- Detalles de la vacante -->
                    <div class="detail-card animate-fade-in">
                        <div class="detail-card-header">
                            <h2 class="detail-card-title">
                                <i class="fas fa-briefcase"></i> Detalles de la Vacante
                            </h2>
                        </div>
                        
                        <div class="job-info-item">
                            <span class="job-info-label">Categoría</span>
                            <span class="job-info-value"><?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                        </div>
                        
                        <div class="job-info-item">
                            <span class="job-info-label">Ubicación</span>
                            <span class="job-info-value"><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                        </div>
                        
                        <div class="job-info-item">
                            <span class="job-info-label">Modalidad</span>
                            <span class="job-info-value"><?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                        </div>
                        
                        <div class="job-info-item">
                            <span class="job-info-label">Tipo de Contrato</span>
                            <span class="job-info-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                        </div>
                        
                        <?php if (!empty($vacante['experiencia'])): ?>
                        <div class="job-info-item">
                            <span class="job-info-label">Experiencia requerida</span>
                            <span class="job-info-value"><?php echo htmlspecialchars($vacante['experiencia']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($vacante['mostrar_salario'] && ($vacante['salario_min'] > 0 || $vacante['salario_max'] > 0)): ?>
                        <div class="job-info-item">
                            <span class="job-info-label">Rango salarial</span>
                            <span class="job-info-value">
                                <?php 
                                if ($vacante['salario_min'] > 0 && $vacante['salario_max'] > 0) {
                                    echo 'RD$ ' . number_format($vacante['salario_min'], 0, '.', ',') . ' - ' . number_format($vacante['salario_max'], 0, '.', ',');
                                } elseif ($vacante['salario_min'] > 0) {
                                    echo 'Desde RD$ ' . number_format($vacante['salario_min'], 0, '.', ',');
                                } elseif ($vacante['salario_max'] > 0) {
                                    echo 'Hasta RD$ ' . number_format($vacante['salario_max'], 0, '.', ',');
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="job-info-item">
                            <span class="job-info-label">Fecha de publicación</span>
                            <span class="job-info-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                        </div>
                        
                        <?php if ($vacante['estado'] == 'cerrada'): ?>
                        <div class="job-info-item">
                            <span class="job-info-label">Estado de la vacante</span>
                            <span class="job-info-value">Cerrada</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center" style="margin-top: 1.5rem;">
                            <a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Ver vacante completa
                            </a>
                        </div>
                    </div>
                    
                    <!-- Enlaces de interés -->
                    <div class="detail-card animate-fade-in">
                        <div class="detail-card-header">
                            <h2 class="detail-card-title">
                                <i class="fas fa-link"></i> Enlaces de interés
                            </h2>
                        </div>
                        
                        <div class="links-list">
                            <a href="aplicaciones.php" class="btn btn-outline-primary" style="width: 100%; margin-bottom: 0.75rem;">
                                <i class="fas fa-chevron-left"></i> Volver a aplicaciones
                            </a>
                            <a href="panel.php" class="btn btn-outline-secondary" style="width: 100%; margin-bottom: 0.75rem;">
                                <i class="fas fa-tachometer-alt"></i> Ir al panel
                            </a>
                            <a href="vacantes.php" class="btn btn-outline-secondary" style="width: 100%;">
                                <i class="fas fa-search"></i> Explorar vacantes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad para botones "Añadir al calendario"
            const calendarButtons = document.querySelectorAll('.add-to-calendar');
            
            calendarButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const title = this.getAttribute('data-title');
                    const dateTimeString = this.getAttribute('data-date');
                    const location = this.getAttribute('data-location');
                    
                    // Parsear fecha y hora
                    let startDate, endDate;
                    if (dateTimeString) {
                        // Formato esperado: "DD/MM/YYYY HH:MM"
                        const parts = dateTimeString.split(' ');
                        if (parts.length === 2) {
                            const dateParts = parts[0].split('/');
                            const timeParts = parts[1].split(':');
                            
                            if (dateParts.length === 3 && timeParts.length === 2) {
                                startDate = new Date(
                                    parseInt(dateParts[2]), // Año
                                    parseInt(dateParts[1]) - 1, // Mes (0-indexed)
                                    parseInt(dateParts[0]), // Día
                                    parseInt(timeParts[0]), // Hora
                                    parseInt(timeParts[1])  // Minutos
                                );
                                
                                // Entrevista de 1 hora por defecto
                                endDate = new Date(startDate);
                                endDate.setHours(endDate.getHours() + 1);
                            }
                        }
                    }
                    
                    if (!startDate) {
                        alert('Formato de fecha no reconocido. No se puede generar la invitación al calendario.');
                        return;
                    }
                    
                    // Formatear fechas para iCalendar
                    function formatDate(date) {
                        return date.toISOString().replace(/-|:|\.\d+/g, '');
                    }
                    
                    // Crear evento iCalendar
                    const icsContent = 
                        'BEGIN:VCALENDAR\n' +
                        'VERSION:2.0\n' +
                        'BEGIN:VEVENT\n' +
                        'DTSTART:' + formatDate(startDate) + '\n' +
                        'DTEND:' + formatDate(endDate) + '\n' +
                        'SUMMARY:' + title + ' - SolFis\n' +
                        'DESCRIPTION:Entrevista para posición en SolFis\n' +
                        'LOCATION:' + location + '\n' +
                        'END:VEVENT\n' +
                        'END:VCALENDAR';
                    
                    // Crear y descargar archivo
                    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'entrevista_solfis.ics';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            });
        });
    </script>
</body>
</html>