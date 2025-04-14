<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener datos para el dashboard
$blogPost = new BlogPost();
$category = new Category();
$comment = new Comment();
$subscriber = new Subscriber();
$user = new User();

// Estadísticas para el dashboard
$totalPosts = $blogPost->getAdminPosts()['total'];
$totalCategories = count($category->getCategories());
$totalComments = $comment->getAdminComments()['total'];
$pendingComments = $comment->getAdminComments(1, 10, 'pending')['total'];
$totalSubscribers = $subscriber->getSubscribers()['total'];
$recentPosts = array_slice($blogPost->getAdminPosts()['posts'], 0, 5);
$recentComments = array_slice($comment->getAdminComments()['comments'], 0, 5);

// Cargar sistema de empleos si existe
$vacancyManager = null;
$candidateManager = null;
$applicationManager = null;
$testManager = null;

$totalVacancies = 0;
$totalApplications = 0;
$totalCandidates = 0;
$recentApplications = [];
$topCandidates = [];

if (file_exists('../includes/jobs-system.php')) {
    require_once '../includes/jobs-system.php';
    
    // Verificar si existen las clases necesarias
    if (class_exists('VacancyManager')) {
        $vacancyManager = new VacancyManager();
        // Obtener estadísticas de vacantes
        try {
            $vacanciesData = $vacancyManager->getVacancies(1, 10);
            $totalVacancies = $vacanciesData['total'];
        } catch (Exception $e) {
            error_log("Error al obtener vacantes: " . $e->getMessage());
            $totalVacancies = 0;
        }
    }
    
    if (class_exists('ApplicationManager')) {
        $applicationManager = new ApplicationManager();
        // Obtener estadísticas de aplicaciones
        try {
            $applicationsData = $applicationManager->getApplications(1, 10);
            $totalApplications = $applicationsData['total'];
            $recentApplications = array_slice($applicationsData['applications'], 0, 5);
        } catch (Exception $e) {
            error_log("Error al obtener aplicaciones: " . $e->getMessage());
            $totalApplications = 0;
            $recentApplications = [];
        }
    }
    
    if (class_exists('CandidateManager')) {
        $candidateManager = new CandidateManager();
        // Obtener estadísticas de candidatos
        try {
            $candidatesData = $candidateManager->getCandidates(1, 10);
            $totalCandidates = $candidatesData['total'];
        } catch (Exception $e) {
            error_log("Error al obtener candidatos: " . $e->getMessage());
            $totalCandidates = 0;
        }
    }
}

// Cargar TestManager si existe
$totalTests = 0;
$completedSessions = 0;

if (file_exists('../includes/TestManager.php')) {
    require_once '../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        
        // Obtener estadísticas de pruebas
        try {
            $db = Database::getInstance();
            
            // Contar pruebas totales
            $testsQuery = "SELECT COUNT(*) as total FROM pruebas";
            $result = $db->query($testsQuery);
            if ($result && $result->num_rows > 0) {
                $totalTests = $result->fetch_assoc()['total'];
            }
            
            // Contar sesiones completadas
            $sessionsQuery = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE estado = 'completada'";
            $result = $db->query($sessionsQuery);
            if ($result && $result->num_rows > 0) {
                $completedSessions = $result->fetch_assoc()['total'];
            }
            
            // Obtener los 5 mejores candidatos (aquellos con más pruebas completadas)
            if ($candidateManager) {
                $topCandidatesQuery = "SELECT c.id, c.nombre, c.apellido, c.email, c.telefono, 
                                     COUNT(s.id) as tests_completed, 
                                     AVG(IFNULL(s.resultado_global, 0)) as avg_score
                                     FROM candidatos c
                                     JOIN sesiones_prueba s ON c.id = s.candidato_id
                                     WHERE s.estado = 'completada'
                                     GROUP BY c.id
                                     ORDER BY tests_completed DESC, avg_score DESC
                                     LIMIT 5";
                
                $result = $db->query($topCandidatesQuery);
                if ($result) {
                    $topCandidates = [];
                    while ($row = $result->fetch_assoc()) {
                        $topCandidates[] = $row;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas de pruebas: " . $e->getMessage());
        }
    }
}

// Título de la página
$pageTitle = 'Dashboard - Panel de Administración';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Panel de Administración - Blog SolFis'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
            <img src="../img/logo-white.png" alt="SolFis" height="30" style="max-width: 100%;">
            <span class="ms-2">Admin SolFis</span>
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex">
                <a class="nav-link px-3 text-white" href="../" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Ver Sitio
                </a>
                <a class="nav-link px-3 text-white" href="profile.php">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a class="nav-link px-3 text-white" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="post-new.php" class="btn btn-sm btn-outline-primary">Nuevo Artículo</a>
                            <?php if ($vacancyManager): ?>
                            <a href="vacantes/vacante-nueva.php" class="btn btn-sm btn-outline-success">Nueva Vacante</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen general del sistema -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h5 class="card-title">Resumen del Sistema</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="icon-box bg-primary text-white">
                                                    <i class="fas fa-globe"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0">Visitas hoy</h6>
                                                    <h3 class="mb-0"><?php echo rand(50, 200); ?></h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="icon-box bg-success text-white">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0">Usuarios</h6>
                                                    <h3 class="mb-0"><?php echo count($user->getUsers()); ?></h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="icon-box bg-info text-white">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0">Posts</h6>
                                                    <h3 class="mb-0"><?php echo $totalPosts; ?></h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="icon-box bg-warning text-white">
                                                    <i class="fas fa-comments"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0">Comentarios</h6>
                                                    <h3 class="mb-0"><?php echo $totalComments; ?></h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards para el módulo de Empleos -->
                <?php if ($vacancyManager || $candidateManager || $applicationManager): ?>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Vacantes</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalVacancies; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-briefcase fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="vacantes/index.php" class="text-white mt-3 d-block small">Ver todas <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Candidatos</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalCandidates; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="candidatos/index.php" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Aplicaciones</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalApplications; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="aplicaciones/index.php" class="text-white mt-3 d-block small">Ver todas <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Evaluaciones</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalTests; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-chart-bar fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="pruebas/index.php" class="text-white mt-3 d-block small">Ver todas <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sección de evaluaciones psicométricas si existe TestManager -->
                <?php if ($testManager && $completedSessions > 0): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <h5 class="card-title mb-0">Resumen de Evaluaciones Psicométricas</h5>
                                <a href="pruebas/index.php" class="btn btn-sm btn-outline-primary">
                                    Gestionar Evaluaciones
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-4">
                                            <h6>Pruebas Completadas por Candidatos</h6>
                                            <div class="chart-container" style="height: 250px;">
                                                <canvas id="sessionsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-4">
                                            <h6>Distribución de Resultados</h6>
                                            <div class="chart-container" style="height: 250px;">
                                                <canvas id="scoresChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sección de candidatos destacados si hay datos -->
                <?php if (!empty($topCandidates)): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Candidatos Destacados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidato</th>
                                                <th>Email</th>
                                                <th>Teléfono</th>
                                                <th>Pruebas Completadas</th>
                                                <th>Puntuación Promedio</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topCandidates as $candidate): ?>
                                            <tr>
                                                <td>
                                                    <a href="candidatos/detalle.php?id=<?php echo $candidate['id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo htmlspecialchars($candidate['nombre'] . ' ' . $candidate['apellido']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                                                <td><?php echo $candidate['telefono'] ?: 'No disponible'; ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">
                                                        <?php echo $candidate['tests_completed']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $score = round($candidate['avg_score']);
                                                    $badgeClass = 'bg-secondary';
                                                    
                                                    if ($score >= 90) {
                                                        $badgeClass = 'bg-success';
                                                    } elseif ($score >= 75) {
                                                        $badgeClass = 'bg-primary';
                                                    } elseif ($score >= 60) {
                                                        $badgeClass = 'bg-info';
                                                    } elseif ($score >= 40) {
                                                        $badgeClass = 'bg-warning';
                                                    } else {
                                                        $badgeClass = 'bg-danger';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo $score; ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="candidatos/detalle.php?id=<?php echo $candidate['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sección Blog y Aplicaciones -->
                <div class="row">
                    <!-- Artículos Recientes -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Artículos Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Autor</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPosts as $post): ?>
                                            <tr>
                                                <td>
                                                    <a href="post-edit.php?id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a>
                                                </td>
                                                <td><?php echo $post['author_name']; ?></td>
                                                <td>
                                                    <?php if ($post['status'] == 'published'): ?>
                                                        <span class="badge bg-success">Publicado</span>
                                                    <?php elseif ($post['status'] == 'draft'): ?>
                                                        <span class="badge bg-secondary">Borrador</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Archivado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="posts.php" class="btn btn-sm btn-outline-primary">Ver todos los artículos</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aplicaciones Recientes / Comentarios Recientes -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <?php if (!empty($recentApplications)): ?>
                                    Aplicaciones Recientes
                                    <?php else: ?>
                                    Comentarios Recientes
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <?php if (!empty($recentApplications)): ?>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidato</th>
                                                <th>Vacante</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentApplications as $application): ?>
                                            <tr>
                                                <td>
                                                    <a href="candidatos/detalle.php?id=<?php echo $application['candidato_id']; ?>">
                                                        <?php echo $application['candidato_nombre'] . ' ' . $application['candidato_apellido']; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="vacantes/vacante-editar.php?id=<?php echo $application['vacante_id']; ?>">
                                                        <?php echo substr($application['vacante_titulo'], 0, 30) . (strlen($application['vacante_titulo']) > 30 ? '...' : ''); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $statuses = [
                                                        'recibida' => ['text' => 'Recibida', 'class' => 'bg-info'],
                                                        'revision' => ['text' => 'En Revisión', 'class' => 'bg-primary'],
                                                        'entrevista' => ['text' => 'Entrevista', 'class' => 'bg-warning'],
                                                        'prueba' => ['text' => 'Prueba', 'class' => 'bg-warning'],
                                                        'oferta' => ['text' => 'Oferta', 'class' => 'bg-success'],
                                                        'contratado' => ['text' => 'Contratado', 'class' => 'bg-success'],
                                                        'rechazado' => ['text' => 'Rechazado', 'class' => 'bg-danger']
                                                    ];
                                                    $status = $statuses[$application['estado']] ?? ['text' => ucfirst($application['estado']), 'class' => 'bg-secondary'];
                                                    ?>
                                                    <span class="badge <?php echo $status['class']; ?>">
                                                        <?php echo $status['text']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($application['fecha_aplicacion'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php else: ?>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Autor</th>
                                                <th>Comentario</th>
                                                <th>Estado</th>
                                                <th>Artículo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentComments as $comment): ?>
                                            <tr>
                                                <td><?php echo $comment['name']; ?></td>
                                                <td><?php echo substr($comment['content'], 0, 50) . '...'; ?></td>
                                                <td>
                                                    <?php if ($comment['status'] == 'approved'): ?>
                                                        <span class="badge bg-success">Aprobado</span>
                                                    <?php elseif ($comment['status'] == 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rechazado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="post-edit.php?id=<?php echo $comment['post_id']; ?>"><?php echo substr($comment['post_title'], 0, 20) . '...'; ?></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <?php if (!empty($recentApplications)): ?>
                                <a href="aplicaciones/index.php" class="btn btn-sm btn-outline-primary">Ver todas las aplicaciones</a>
                                <?php else: ?>
                                <a href="comments.php" class="btn btn-sm btn-outline-primary">Ver todos los comentarios</a>
                                <?php if ($pendingComments > 0): ?>
                                <a href="comments.php?status=pending" class="btn btn-sm btn-warning ms-2">
                                    <?php echo $pendingComments; ?> comentarios pendientes
                                </a>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Agregar scripts necesarios para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <?php if ($testManager && $completedSessions > 0): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos de ejemplo para el gráfico de sesiones completadas
        const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
        const sessionsChart = new Chart(sessionsCtx, {
            type: 'bar',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo'],
                datasets: [{
                    label: 'Pruebas Completadas',
                    data: [12, 19, 15, 25, <?php echo $completedSessions; ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
        
        // Datos de ejemplo para el gráfico de distribución de resultados
        const scoresCtx = document.getElementById('scoresChart').getContext('2d');
        const scoresChart = new Chart(scoresCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sobresaliente (90-100%)', 'Avanzado (75-89%)', 'Competente (60-74%)', 'En desarrollo (40-59%)', 'Inicial (0-39%)'],
                datasets: [{
                    data: [15, 25, 35, 20, 5],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',  // Verde
                        'rgba(0, 123, 255, 0.7)',  // Azul
                        'rgba(23, 162, 184, 0.7)', // Cyan
                        'rgba(255, 193, 7, 0.7)',  // Amarillo
                        'rgba(220, 53, 69, 0.7)'   // Rojo
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <style>
    /* Estilos para la sección de estadísticas */
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    body {
        font-size: 0.95rem;
        line-height: 1.5;
        color: #333;
        background-color: #f5f7fb;
    }
    
    .card {
        border: 0;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
        transition: all 0.2s ease-in-out;
        border-radius: 0.375rem;
        overflow: hidden;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 48px 0 0;
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    }
    
    @media (max-width: 767.98px) {
        .sidebar {
            position: static;
            top: auto;
            height: auto;
            padding-top: 0;
        }
        
        .sidebar-sticky {
            height: auto !important;
            position: relative !important;
            top: 0 !important;
        }
    }
    
    .sidebar-sticky {
        position: relative;
        top: 0;
        height: calc(100vh - 48px);
        padding-top: .5rem;
        overflow-x: hidden;
        overflow-y: auto;
    }
    
    .sidebar .nav-link {
        font-weight: 500;
        color: #333;
        padding: .5rem 1rem;
    }
    
    .sidebar .nav-link.active {
        color: #007bff;
    }
    
    .sidebar .nav-link:hover {
        color: #007bff;
    }
    
    .navbar-brand {
        padding-top: .75rem;
        padding-bottom: .75rem;
        font-size: 1rem;
        background-color: rgba(0, 0, 0, .25);
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
    }
    </style>
</body>
</html>