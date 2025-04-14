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
$applicationManager = new ApplicationManager();

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
    header('Location: vacantes.php');
    exit;
}

// Obtener ID de la vacante
$id = (int)$_GET['id'];

// Obtener vacante por ID
$vacante = $vacancyManager->getVacancyById($id);

// Si la vacante no existe o no está publicada, redirigir
if (!$vacante || $vacante['estado'] !== 'publicada') {
    header('Location: vacantes.php');
    exit;
}

// Verificar si el candidato ya aplicó a esta vacante
$yaAplico = false;
$aplicaciones = $applicationManager->getApplicationsByCandidate($candidato_id);
foreach ($aplicaciones as $aplicacion) {
    if ($aplicacion['vacante_id'] == $id) {
        $yaAplico = true;
        $aplicacionActual = $aplicacion;
        break;
    }
}

// Procesar formulario de aplicación
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar'])) {
    // Validación básica
    if (empty($_POST['carta_presentacion'])) {
        $error = 'Por favor complete la carta de presentación.';
    } else {
        // Datos de la aplicación
        $aplicacionData = [
            'vacante_id' => $id,
            'candidato_id' => $candidato_id,
            'carta_presentacion' => $_POST['carta_presentacion'],
            'experiencia' => $_POST['experiencia'] ?? '',
            'empresa_actual' => $_POST['empresa_actual'] ?? '',
            'cargo_actual' => $_POST['cargo_actual'] ?? '',
            'salario_esperado' => $_POST['salario_esperado'] ?? '',
            'disponibilidad' => $_POST['disponibilidad'] ?? '',
            'fuente' => 'portal_candidato',
            'modalidad_preferida' => $_POST['modalidad_preferida'] ?? '',
            'tipo_contrato_preferido' => $_POST['tipo_contrato_preferido'] ?? ''
        ];
        
        // Crear aplicación
        $resultado = $applicationManager->createApplication($aplicacionData);
        
        if ($resultado['success']) {
            $success = true;
            $yaAplico = true;
            $aplicacionActual = [
                'id' => $resultado['id'],
                'estado' => 'recibida',
                'fecha_aplicacion' => date('Y-m-d H:i:s')
            ];
        } else {
            $error = $resultado['message'];
        }
    }
}

// Formatear descripción, requisitos, etc.
$requisitos = explode("\n", $vacante['requisitos']);
$responsabilidades = explode("\n", $vacante['responsabilidades']);
$beneficios = $vacante['beneficios'] ? explode("\n", $vacante['beneficios']) : [];

// Calcular porcentaje de compatibilidad (simulado)
$compatibilidad = rand(65, 95); // En una implementación real, esto vendría de un algoritmo de matching

// Título de la página
$site_title = htmlspecialchars($vacante['titulo']) . " - SolFis Talentos";
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
    
    <!-- Estilos personalizados para el detalle de vacante -->
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
        .job-detail-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        /* Información principal de la vacante */
        .job-detail-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .job-detail-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            position: relative;
        }
        
        .job-closed-alert {
            display: flex;
            align-items: center;
            background-color: var(--danger-color);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.25rem;
        }
        
        .job-closed-alert i {
            margin-right: 0.5rem;
        }
        
        .job-detail-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .job-detail-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-top: 0;
            margin-bottom: 1rem;
        }
        
        .job-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        
        .job-detail-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .job-detail-meta-item i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }
        
        .job-detail-company {
            display: flex;
            align-items: center;
        }
        
        .job-detail-company-logo {
            width: 50px;
            height: 50px;
            margin-right: 1rem;
            border-radius: 8px;
            background-color: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .job-detail-company-info h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--gray-800);
        }
        
        .job-detail-company-info p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .job-detail-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .job-detail-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .job-detail-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 0;
            margin-bottom: 1rem;
        }
        
        .job-detail-description {
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .job-detail-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        
        .job-detail-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
        }
        
        .job-detail-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .benefits-list li:before {
            color: var(--success-color);
        }
        
        .job-detail-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .btn-apply-now {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.25rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.25rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-apply-now i {
            margin-right: 0.5rem;
        }
        
        .btn-apply-now:hover {
            background-color: #00264d;
        }
        
        .btn-save-job {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background-color: var(--gray-200);
            color: var(--gray-700);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-save-job:hover, .btn-save-job.saved {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-job-closed {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.25rem;
            background-color: var(--gray-400);
            color: white;
            border: none;
            border-radius: 0.25rem;
            font-weight: 500;
            cursor: not-allowed;
        }
        
        .btn-job-closed i {
            margin-right: 0.5rem;
        }
        
        /* Compartir vacante */
        .job-detail-share {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .job-detail-share h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 0;
            margin-bottom: 1rem;
        }
        
        .share-buttons {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-share {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .btn-share:hover {
            opacity: 0.8;
        }
        
        .btn-share.facebook {
            background-color: #3b5998;
        }
        
        .btn-share.twitter {
            background-color: #1da1f2;
        }
        
        .btn-share.linkedin {
            background-color: #0077b5;
        }
        
        .btn-share.whatsapp {
            background-color: #25d366;
        }
        
        .btn-share.email {
            background-color: var(--gray-700);
        }
        
        /* Sidebar de la vacante */
        .job-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .job-sidebar-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .job-sidebar-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 0;
            margin-bottom: 1rem;
        }
        
        .job-summary {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .job-summary-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
        }
        
        .job-summary-label {
            color: var(--gray-600);
        }
        
        .job-summary-value {
            color: var(--gray-800);
            font-weight: 500;
        }
        
        /* Match indicator */
        .match-indicator {
            margin-bottom: 1.5rem;
        }
        
        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .match-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .match-percentage {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .match-bar {
            height: 0.5rem;
            background-color: var(--gray-200);
            border-radius: 1rem;
            overflow: hidden;
        }
        
        .match-progress {
            height: 100%;
            border-radius: 1rem;
            transition: width 0.3s ease;
        }
        
        .match-progress.high {
            background-color: var(--success-color);
        }
        
        .match-progress.medium {
            background-color: var(--warning-color);
        }
        
        .match-progress.low {
            background-color: var(--danger-color);
        }
        
        /* Lista de vacantes similares */
        .similar-jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .similar-job-item {
            display: flex;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .similar-job-item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .similar-job-logo {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            border-radius: 8px;
            background-color: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .similar-job-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .similar-job-info {
            flex: 1;
            min-width: 0;
        }
        
        .similar-job-info h4 {
            margin: 0 0 0.25rem;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .similar-job-info h4 a {
            color: var(--gray-800);
            text-decoration: none;
        }
        
        .similar-job-info h4 a:hover {
            color: var(--primary-color);
        }
        
        .similar-job-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-600);
        }
        
        /* Formulario de aplicación */
        .application-form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .application-form-container h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .application-form-container h3 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .application-status {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .application-status.success {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .application-status.success i {
            color: var(--success-color);
            font-size: 2rem;
            margin-right: 1rem;
        }
        
        .application-status-content h4 {
            color: var(--success-color);
            margin: 0 0 0.5rem;
        }
        
        .application-status-content p {
            margin: 0;
            color: var(--gray-700);
        }
        
        .application-form .form-group {
            margin-bottom: 1.25rem;
        }
        
        .application-form .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .application-form .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            transition: border-color 0.2s;
        }
        
        .application-form .form-control:focus {
            border-color: var(--secondary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 136, 204, 0.25);
        }
        
        .application-form textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .form-text {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            display: flex;
            align-items: flex-start;
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        /* Dashboard content updates */
        .dashboard-content {
            padding: 1.5rem;
        }
        
        /* Modal para aplicación */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-dialog {
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s ease;
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid var(--gray-200);
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out forwards;
        }
        
        /* Media queries para dispositivos móviles */
        @media (max-width: 991px) {
            .job-detail-layout {
                grid-template-columns: 1fr;
            }
            
            .job-sidebar {
                order: -1;
            }
        }
        
        /* Utilidades */
        .mt-3 { margin-top: 1rem; }
        .mb-3 { margin-bottom: 1rem; }
        .text-center { text-align: center; }
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
       <?php include 'includes/sidebar-fix.php'; ?>
        
        <main class="dashboard-content">
            <div class="breadcrumbs mb-3 animate-fade-in">
                <a href="panel.php">Panel</a> <span class="separator">/</span>
                <a href="vacantes.php">Vacantes</a> <span class="separator">/</span>
                <span class="current"><?php echo htmlspecialchars($vacante['titulo']); ?></span>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Aplicación enviada con éxito</h3>
                    <p>Tu aplicación para la posición de <?php echo htmlspecialchars($vacante['titulo']); ?> ha sido recibida. Puedes dar seguimiento a su estado desde tu panel de candidato.</p>
                </div>
            </div>
            <?php elseif ($error): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <h3>Ha ocurrido un error</h3>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="job-detail-layout">
                <div class="job-detail-main">
                    <div class="job-detail-card animate-fade-in">
                        <?php if ($vacante['estado'] === 'cerrada'): ?>
                        <div class="job-closed-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Esta vacante ya no está disponible.</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="job-detail-header">
                            <h1 class="job-detail-title"><?php echo htmlspecialchars($vacante['titulo']); ?></h1>
                            
                            <div class="job-detail-meta">
                                <span class="job-detail-meta-item">
                                    <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?>
                                </span>
                                <span class="job-detail-meta-item">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?>
                                </span>
                                <span class="job-detail-meta-item">
                                    <i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?>
                                </span>
                                <span class="job-detail-meta-item">
                                    <i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?>
                                </span>
                            </div>
                            
                            <div class="job-detail-company">
                                <img src="../img/logo-icon.png" alt="SolFis" class="job-detail-company-logo">
                                <div class="job-detail-company-info">
                                    <h3>SolFis</h3>
                                    <p>Soluciones Fiscales y Contables</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-detail-section">
                            <h3>Descripción de la Posición</h3>
                            <div class="job-detail-description">
                                <?php echo nl2br(htmlspecialchars($vacante['descripcion'])); ?>
                            </div>
                        </div>
                        
                        <div class="job-detail-section">
                            <h3>Responsabilidades</h3>
                            <ul class="job-detail-list">
                                <?php foreach ($responsabilidades as $resp): ?>
                                    <?php if (trim($resp)): ?>
                                    <li><?php echo htmlspecialchars($resp); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="job-detail-section">
                            <h3>Requisitos</h3>
                            <ul class="job-detail-list">
                                <?php foreach ($requisitos as $req): ?>
                                    <?php if (trim($req)): ?>
                                    <li><?php echo htmlspecialchars($req); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <?php if (!empty($beneficios) && trim($beneficios[0])): ?>
                        <div class="job-detail-section">
                            <h3>Beneficios</h3>
                            <ul class="job-detail-list benefits-list">
                                <?php foreach ($beneficios as $ben): ?>
                                    <?php if (trim($ben)): ?>
                                    <li><?php echo htmlspecialchars($ben); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="job-detail-actions">
                            <?php if ($vacante['estado'] === 'publicada'): ?>
                                <?php if ($yaAplico): ?>
                                <a href="aplicacion.php?id=<?php echo $aplicacionActual['id']; ?>" class="btn-apply-now">
                                    <i class="fas fa-eye"></i> Ver mi aplicación
                                </a>
                                <?php else: ?>
                                <button id="btnAplicar" class="btn-apply-now">
                                    <i class="fas fa-paper-plane"></i> Aplicar Ahora
                                </button>
                                <?php endif; ?>
                                <button class="btn-save-job" id="btnGuardarVacante" data-job-id="<?php echo $vacante['id']; ?>" title="Guardar vacante">
                                    <i class="far fa-bookmark"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-job-closed" disabled>
                                    <i class="fas fa-times-circle"></i> Vacante Cerrada
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="job-detail-share animate-fade-in">
                        <h3>Compartir esta vacante</h3>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share facebook" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis'); ?>" class="btn-share twitter" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($vacante['titulo']); ?>&summary=<?php echo urlencode(VacancyUtils::truncate(strip_tags($vacante['descripcion']), 100)); ?>" class="btn-share linkedin" target="_blank" rel="noopener noreferrer" aria-label="Compartir en LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis - ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share whatsapp" target="_blank" rel="noopener noreferrer" aria-label="Compartir por WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis'); ?>&body=<?php echo urlencode("He encontrado esta vacante que podría interesarte:\n\n" . $vacante['titulo'] . "\n\n" . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share email" aria-label="Compartir por Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="job-sidebar">
                    <!-- Match Indicator -->
                    <div class="job-sidebar-card animate-fade-in">
                        <div class="match-indicator">
                            <div class="match-header">
                                <span class="match-label">Compatibilidad con tu perfil</span>
                                <span class="match-percentage"><?php echo $compatibilidad; ?>%</span>
                            </div>
                            <div class="match-bar">
                                <div class="match-progress <?php echo $compatibilidad >= 80 ? 'high' : ($compatibilidad >= 60 ? 'medium' : 'low'); ?>" style="width: <?php echo $compatibilidad; ?>%"></div>
                            </div>
                        </div>
                        
                        <h3>Resumen de la Vacante</h3>
                        <div class="job-summary">
                            <div class="job-summary-item">
                                <span class="job-summary-label">Fecha de Publicación</span>
                                <span class="job-summary-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                            </div>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Categoría</span>
                                <span class="job-summary-value"><?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                            </div>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Ubicación</span>
                                <span class="job-summary-value"><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                            </div>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Modalidad</span>
                                <span class="job-summary-value"><?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                            </div>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Tipo de Contrato</span>
                                <span class="job-summary-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                            </div>
                            
                            <?php if (!empty($vacante['experiencia'])): ?>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Experiencia</span>
                                <span class="job-summary-value"><?php echo htmlspecialchars($vacante['experiencia']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($vacante['mostrar_salario'] && ($vacante['salario_min'] > 0 || $vacante['salario_max'] > 0)): ?>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Salario</span>
                                <span class="job-summary-value">
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
                            
                            <?php if ($vacante['fecha_cierre']): ?>
                            <div class="job-summary-item">
                                <span class="job-summary-label">Fecha de Cierre</span>
                                <span class="job-summary-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_cierre'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($yaAplico): ?>
            <!-- Sección de aplicación ya realizada -->
            <div class="application-form-container animate-fade-in">
                <h3><i class="fas fa-check-circle"></i> Ya has aplicado a esta vacante</h3>
                
                <div class="application-status success">
                    <i class="fas fa-clipboard-check"></i>
                    <div class="application-status-content">
                        <h4>Aplicación enviada</h4>
                        <p>
                            Tu aplicación fue enviada el <?php echo date('d/m/Y', strtotime($aplicacionActual['fecha_aplicacion'])); ?> 
                            y actualmente se encuentra en estado: <strong><?php echo ucfirst($aplicacionActual['estado']); ?></strong>
                        </p>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="aplicacion.php?id=<?php echo $aplicacionActual['id']; ?>" class="btn-apply-now" style="display: inline-flex; max-width: 250px;">
                        <i class="fas fa-eye"></i> Ver detalles de mi aplicación
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal de aplicación -->
    <div class="modal" id="aplicarModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Aplicar a: <?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                <button type="button" class="modal-close" id="btnCerrarModal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="detalle-vacante.php?id=<?php echo $id; ?>" method="POST" class="application-form" id="applicationForm">
                    <p>Por favor completa la siguiente información para aplicar a esta vacante. Tus datos personales ya están registrados en nuestro sistema.</p>
                    
                    <div class="form-group">
                        <label for="carta_presentacion" class="form-label">Carta de Presentación <span class="required-mark">*</span></label>
                        <textarea id="carta_presentacion" name="carta_presentacion" class="form-control" rows="6" placeholder="Cuéntanos por qué estás interesado en esta posición y por qué eres un buen candidato" required></textarea>
                        <div class="form-text">Describe brevemente tu experiencia relevante y por qué te interesa esta posición.</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="experiencia" class="form-label">Años de experiencia específica</label>
                            <select id="experiencia" name="experiencia" class="form-control">
                                <option value="">Selecciona una opción</option>
                                <option value="sin-experiencia">Sin experiencia</option>
                                <option value="menos-1">Menos de 1 año</option>
                                <option value="1-3">1-3 años</option>
                                <option value="3-5">3-5 años</option>
                                <option value="5-10">5-10 años</option>
                                <option value="mas-10">Más de 10 años</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="salario_esperado" class="form-label">Expectativa salarial (RD$)</label>
                            <input type="text" id="salario_esperado" name="salario_esperado" class="form-control" placeholder="Ej: RD$ 60,000 mensuales">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="empresa_actual" class="form-label">Empresa actual o más reciente</label>
                            <input type="text" id="empresa_actual" name="empresa_actual" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="cargo_actual" class="form-label">Cargo actual o más reciente</label>
                            <input type="text" id="cargo_actual" name="cargo_actual" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="disponibilidad" class="form-label">Disponibilidad para comenzar</label>
                            <select id="disponibilidad" name="disponibilidad" class="form-control">
                                <option value="">Selecciona una opción</option>
                                <option value="inmediata">Inmediata</option>
                                <option value="2-semanas">2 semanas</option>
                                <option value="1-mes">1 mes</option>
                                <option value="mas-1-mes">Más de 1 mes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="modalidad_preferida" class="form-label">Modalidad preferida</label>
                            <select id="modalidad_preferida" name="modalidad_preferida" class="form-control">
                                <option value="">Selecciona una opción</option>
                                <option value="presencial">Presencial</option>
                                <option value="remoto">Remoto</option>
                                <option value="hibrido">Híbrido</option>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="aplicar" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-primary" id="btnCancelarAplicacion">Cancelar</button>
                <button type="submit" form="applicationForm" class="btn-apply-now">
                    <i class="fas fa-paper-plane"></i> Enviar Aplicación
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para modal
            const aplicarModal = document.getElementById('aplicarModal');
            const btnAplicar = document.getElementById('btnAplicar');
            const btnCerrarModal = document.getElementById('btnCerrarModal');
            const btnCancelarAplicacion = document.getElementById('btnCancelarAplicacion');
            const btnGuardarVacante = document.getElementById('btnGuardarVacante');
            
            // Evento para abrir modal
            if (btnAplicar) {
                btnAplicar.addEventListener('click', function() {
                    aplicarModal.classList.add('show');
                    document.body.style.overflow = 'hidden'; // Prevenir scroll
                });
            }
            
            // Eventos para cerrar modal
            if (btnCerrarModal) {
                btnCerrarModal.addEventListener('click', function() {
                    aplicarModal.classList.remove('show');
                    document.body.style.overflow = ''; // Restaurar scroll
                });
            }
            
            if (btnCancelarAplicacion) {
                btnCancelarAplicacion.addEventListener('click', function() {
                    aplicarModal.classList.remove('show');
                    document.body.style.overflow = ''; // Restaurar scroll
                });
            }
            
            // Click fuera del modal
            window.addEventListener('click', function(event) {
                if (event.target === aplicarModal) {
                    aplicarModal.classList.remove('show');
                    document.body.style.overflow = ''; // Restaurar scroll
                }
            });
            
            // Guardar vacante (favoritos)
            if (btnGuardarVacante) {
                btnGuardarVacante.addEventListener('click', function() {
                    this.classList.toggle('saved');
                    
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.title = 'Quitar de guardados';
                        
                        // Aquí se podría implementar la lógica para guardar la vacante
                        // usando AJAX o un formulario oculto
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.title = 'Guardar vacante';
                        
                        // Aquí se podría implementar la lógica para quitar la vacante de guardados
                    }
                });
            }
            
            // Validación del formulario
            const applicationForm = document.getElementById('applicationForm');
            if (applicationForm) {
                applicationForm.addEventListener('submit', function(event) {
                    const cartaPresentacion = document.getElementById('carta_presentacion');
                    
                    if (!cartaPresentacion.value.trim()) {
                        event.preventDefault();
                        cartaPresentacion.classList.add('is-invalid');
                        alert('Por favor complete la carta de presentación.');
                    }
                });
            }
        });
    </script>
</body>
</html>