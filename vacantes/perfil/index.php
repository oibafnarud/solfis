<?php
/**
 * Portal de Vacantes SolFis
 * Perfil del candidato - Dashboard principal
 */

// Incluir archivos necesarios
require_once '../../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php?redirect=perfil');
    exit;
}

// Inicializar las clases necesarias
$candidate = new Candidate();
$application = new JobApplication();
$jobVacancy = new JobVacancy();

// Obtener perfil del candidato
$candidateProfile = $candidate->getCandidateByUserId($auth->getUserId());

// Si no tiene perfil de candidato, crear uno básico
if (!$candidateProfile) {
    $userData = [
        'user_id' => $auth->getUserId(),
        'profile_completed' => 0
    ];
    
    $candidateId = $candidate->createCandidate($userData);
    if ($candidateId) {
        $candidateProfile = $candidate->getCandidateById($candidateId);
    }
}

// Obtener aplicaciones recientes
$recentApplications = [];
if ($candidateProfile) {
    $applicationsData = $application->getApplicationsByCandidate($candidateProfile['id'], 1, 5);
    $recentApplications = $applicationsData['applications'];
}

// Obtener vacantes recomendadas
$recommendedVacancies = [];
if ($candidateProfile) {
    // Obtener categorías de interés basadas en aplicaciones anteriores
    $categories = [];
    
    foreach ($recentApplications as $app) {
        $vacancy = $jobVacancy->getVacancyById($app['vacancy_id']);
        if ($vacancy) {
            $categories[$vacancy['category_id']] = true;
        }
    }
    
    // Si no hay aplicaciones previas, mostrar vacantes destacadas
    if (empty($categories)) {
        $vacanciesData = $jobVacancy->getVacancies(1, 4, ['featured' => true]);
        $recommendedVacancies = $vacanciesData['vacancies'];
    } else {
        // Mostrar vacantes de categorías similares
        $categoryIds = array_keys($categories);
        $filters = ['category_id' => $categoryIds[0]]; // Usar la primera categoría
        $vacanciesData = $jobVacancy->getVacancies(1, 4, $filters);
        $recommendedVacancies = $vacanciesData['vacancies'];
    }
}

// Calcular porcentaje de completitud del perfil
$profileCompleteness = 0;
$totalFields = 9; // Total de campos a considerar
$completedFields = 0;

if ($candidateProfile) {
    // Verificar campos completos
    if (!empty($candidateProfile['phone'])) $completedFields++;
    if (!empty($candidateProfile['headline'])) $completedFields++;
    if (!empty($candidateProfile['summary'])) $completedFields++;
    if (!empty($candidateProfile['city']) && !empty($candidateProfile['country'])) $completedFields++;
    if (!empty($candidateProfile['cv_path'])) $completedFields++;
    
    // Verificar experiencia
    $experiences = $candidate->getExperiences($candidateProfile['id']);
    if (count($experiences) > 0) $completedFields++;
    
    // Verificar educación
    $education = $candidate->getEducation($candidateProfile['id']);
    if (count($education) > 0) $completedFields++;
    
    // Verificar habilidades
    $skills = $candidate->getSkills($candidateProfile['id']);
    if (count($skills) > 0) $completedFields++;
    
    // Imagen de perfil (de la tabla users)
    if (!empty($candidateProfile['image'])) $completedFields++;
    
    // Calcular porcentaje
    $profileCompleteness = round(($completedFields / $totalFields) * 100);
    
    // Actualizar completitud en la base de datos
    if ($profileCompleteness != $candidateProfile['profile_completed']) {
        $candidate->updateCandidate($candidateProfile['id'], [
            'profile_completed' => $profileCompleteness
        ]);
    }
}

// Definir título de la página
$pageTitle = 'Mi Perfil - Portal de Vacantes SolFis';
?>

<?php include '../../includes/header.php'; ?>

<!-- Cabecera -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <h1 class="display-5 fw-bold">Mi Perfil</h1>
        <p class="lead">Bienvenido, <?php echo htmlspecialchars($auth->getUser()['name']); ?>. Administra tu perfil y tus aplicaciones a vacantes.</p>
    </div>
</section>

<!-- Contenido principal -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar del perfil -->
            <div class="col-lg-3 mb-4">
                <?php include 'sidebar.php'; ?>
            </div>
            
            <!-- Contenido principal -->
            <div class="col-lg-9">
                <!-- Tarjeta de completitud de perfil -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Completitud de tu perfil</h5>
                        <p class="card-text">Un perfil completo aumenta tus posibilidades de ser considerado para las vacantes.</p>
                        
                        <div class="progress mb-3">
                            <div class="progress-bar bg-<?php 
                                echo $profileCompleteness < 30 ? 'danger' : 
                                    ($profileCompleteness < 70 ? 'warning' : 'success'); 
                            ?>" role="progressbar" style="width: <?php echo $profileCompleteness; ?>%" 
                               aria-valuenow="<?php echo $profileCompleteness; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $profileCompleteness; ?>%
                            </div>
                        </div>
                        
                        <?php if ($profileCompleteness < 100): ?>
                            <a href="cv.php" class="btn btn-primary">Completar perfil</a>
                        <?php else: ?>
                            <p class="text-success mb-0"><i class="fas fa-check-circle"></i> ¡Felicidades! Tu perfil está completo.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Aplicaciones recientes -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mis aplicaciones recientes</h5>
                        <a href="mis-aplicaciones.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($recentApplications)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">Aún no has aplicado a ninguna vacante. Encuentra oportunidades en nuestro <a href="../index.php">portal de vacantes</a>.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Puesto</th>
                                            <th>Departamento</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Etapa actual</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentApplications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['title']); ?></td>
                                                <td><?php echo htmlspecialchars($app['department']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($app['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($app['status'] === 'pending'): ?>
                                                        <span class="badge bg-secondary">Pendiente</span>
                                                    <?php elseif ($app['status'] === 'reviewed'): ?>
                                                        <span class="badge bg-info">En revisión</span>
                                                    <?php elseif ($app['status'] === 'interviewing'): ?>
                                                        <span class="badge bg-primary">En entrevista</span>
                                                    <?php elseif ($app['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger">Rechazada</span>
                                                    <?php elseif ($app['status'] === 'offered'): ?>
                                                        <span class="badge bg-warning">Oferta</span>
                                                    <?php elseif ($app['status'] === 'hired'): ?>
                                                        <span class="badge bg-success">Contratado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($app['current_stage'] ?? 'No asignada'); ?></td>
                                                <td>
                                                    <a href="detalle-aplicacion.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Vacantes recomendadas -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Vacantes recomendadas</h5>
                        <a href="../index.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($recommendedVacancies)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No hay vacantes recomendadas en este momento. Consulta todas las <a href="../index.php">vacantes disponibles</a>.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($recommendedVacancies as $vacancy): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($vacancy['title']); ?></h5>
                                                <p class="card-text text-muted"><?php echo htmlspecialchars($vacancy['department']); ?></p>
                                                
                                                <div class="vacancy-meta mb-3 d-flex flex-wrap">
                                                    <span class="badge bg-light text-dark me-2 mb-1">
                                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacancy['location']); ?>
                                                    </span>
                                                    <span class="badge bg-light text-dark mb-1">
                                                        <i class="fas fa-laptop-house"></i> <?php echo ucfirst(htmlspecialchars($vacancy['work_mode'])); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="text-end">
                                                    <a href="../detalle.php?id=<?php echo $vacancy['id']; ?>" class="btn btn-sm btn-outline-primary">Ver más</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>