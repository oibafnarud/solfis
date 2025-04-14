<?php
// Obtener la ruta del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Función para verificar si la página actual está en un directorio específico
function isInDirectory($dir) {
    return basename(dirname($_SERVER['PHP_SELF'])) === $dir;
}

// Función para verificar si la página actual está activa
function isActive($page, $directory = null) {
    $current = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    
    if ($directory !== null) {
        return $current_dir === $directory && ($page === $current || $page === '*');
    }
    
    return $page === $current;
}

// Contar comentarios pendientes
$pendingComments = 0;
if (class_exists('Comment')) {
    $comment = new Comment();
    $pendingComments = $comment->getAdminComments(1, 10, 'pending')['total'];
}

// Verificar si existen los módulos de reclutamiento
$hasRecruitmentModules = file_exists(dirname(dirname(__DIR__)) . '/includes/jobs-system.php');

// Verificar si existe el módulo de pruebas psicométricas
$hasTestsModule = file_exists(dirname(dirname(__DIR__)) . '/includes/TestManager.php');

// Contador para aplicaciones pendientes
$pendingApplications = 0;
if ($hasRecruitmentModules) {
    // Intentar cargar el sistema de reclutamiento
    require_once dirname(dirname(__DIR__)) . '/includes/jobs-system.php';
    
    if (class_exists('ApplicationManager')) {
        $applicationManager = new ApplicationManager();
        try {
            $pendingApplicationsData = $applicationManager->getApplications(1, 1, ['estado' => 'recibida']);
            $pendingApplications = $pendingApplicationsData['total'];
        } catch (Exception $e) {
            // Si hay un error al obtener las aplicaciones, simplemente continuamos
            error_log("Error al obtener aplicaciones pendientes: " . $e->getMessage());
        }
    }
}

// Obtener estadísticas de pruebas psicométricas
$pendingEvaluations = 0;
if ($hasTestsModule) {
    try {
        $db = Database::getInstance();
        $query = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE estado = 'en_progreso'";
        $result = $db->query($query);
        if ($result && $result->num_rows > 0) {
            $pendingEvaluations = $result->fetch_assoc()['total'];
        }
    } catch (Exception $e) {
        error_log("Error al obtener evaluaciones pendientes: " . $e->getMessage());
    }
}
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky sidebar-content">
        <div class="text-center mb-3 d-none d-md-block">
            <img src="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../../img/logo.png' : '../img/logo.png'; ?>" alt="SolFis" class="img-fluid" style="max-width: 120px;">
        </div>
        
        <!-- Menú colapsable con acordeón -->
        <div class="accordion" id="sidebarAccordion">
            <!-- DASHBOARD -->
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="headingDashboard">
                    <a href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../index.php' : 'index.php'; ?>" class="accordion-button <?php echo $current_page === 'index.php' && $current_dir === 'admin' ? 'active' : 'collapsed'; ?>" type="button">
                        <i class="fas fa-tachometer-alt me-2"></i> DASHBOARD
                    </a>
                </h2>
            </div>
            
            <!-- BLOG -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingBlog">
                    <button class="accordion-button <?php echo (isActive('posts.php') || isActive('categories.php') || isActive('comments.php') || isActive('post-new.php') || isActive('post-edit.php') || isActive('category-new.php') || isActive('category-edit.php')) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBlog" aria-expanded="<?php echo (isActive('posts.php') || isActive('categories.php') || isActive('comments.php')) ? 'true' : 'false'; ?>" aria-controls="collapseBlog">
                        <i class="fas fa-blog me-2"></i> BLOG
                    </button>
                </h2>
                <div id="collapseBlog" class="accordion-collapse collapse <?php echo (isActive('posts.php') || isActive('categories.php') || isActive('comments.php') || isActive('post-new.php') || isActive('post-edit.php') || isActive('category-new.php') || isActive('category-edit.php')) ? 'show' : ''; ?>" aria-labelledby="headingBlog" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('index.php') && $current_dir === 'admin' ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../index.php' : 'index.php'; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('posts.php') || isActive('post-new.php') || isActive('post-edit.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../posts.php' : 'posts.php'; ?>">
                                    <i class="fas fa-file-alt"></i> Artículos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('categories.php') || isActive('category-new.php') || isActive('category-edit.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../categories.php' : 'categories.php'; ?>">
                                    <i class="fas fa-folder"></i> Categorías
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('comments.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../comments.php' : 'comments.php'; ?>">
                                    <i class="fas fa-comments"></i> Comentarios
                                    <?php if ($pendingComments > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $pendingComments; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('media.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../media.php' : 'media.php'; ?>">
                                    <i class="fas fa-images"></i> Multimedia
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- MENSAJES -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMessages">
                    <button class="accordion-button <?php echo isActive('contact.php') ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMessages" aria-expanded="<?php echo isActive('contact.php') ? 'true' : 'false'; ?>" aria-controls="collapseMessages">
                        <i class="fas fa-envelope me-2"></i> MENSAJES
                    </button>
                </h2>
                <div id="collapseMessages" class="accordion-collapse collapse <?php echo isActive('contact.php') ? 'show' : ''; ?>" aria-labelledby="headingMessages" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('contact.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../contact.php' : 'contact.php'; ?>">
                                    <i class="fas fa-envelope"></i> Mensajes de Contacto
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('test-email.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../test-email.php' : 'test-email.php'; ?>">
                                    <i class="fas fa-paper-plane"></i> Probar Correo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('email-settings.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../email-settings.php' : 'email-settings.php'; ?>">
                                    <i class="fas fa-cog"></i> Configuración de Correo
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if ($hasRecruitmentModules): ?>
            <!-- VACANTES Y RECLUTAMIENTO -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingVacancies">
                    <button class="accordion-button <?php echo (isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos')) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVacancies" aria-expanded="<?php echo (isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos')) ? 'true' : 'false'; ?>" aria-controls="collapseVacancies">
                        <i class="fas fa-briefcase me-2"></i> VACANTES
                    </button>
                </h2>
                <div id="collapseVacancies" class="accordion-collapse collapse <?php echo (isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos')) ? 'show' : ''; ?>" aria-labelledby="headingVacancies" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('index.php', 'vacantes') || isActive('vacante-nueva.php', 'vacantes') || isActive('vacante-editar.php', 'vacantes') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../vacantes/index.php' : 'vacantes/index.php'; ?>">
                                    <i class="fas fa-list"></i> Vacantes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('vacante-nueva.php', 'vacantes') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../vacantes/vacante-nueva.php' : 'vacantes/vacante-nueva.php'; ?>">
                                    <i class="fas fa-plus-circle"></i> Nueva Vacante
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('index.php', 'aplicaciones') || isActive('detalle.php', 'aplicaciones') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../aplicaciones/index.php' : 'aplicaciones/index.php'; ?>">
                                    <i class="fas fa-clipboard-list"></i> Aplicaciones
                                    <?php if ($pendingApplications > 0): ?>
                                    <span class="badge bg-primary rounded-pill ms-2"><?php echo $pendingApplications; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('index.php', 'candidatos') || isActive('detalle.php', 'candidatos') || isActive('editar.php', 'candidatos') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../candidatos/index.php' : 'candidatos/index.php'; ?>">
                                    <i class="fas fa-user-tie"></i> Candidatos
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- EVALUACIONES PSICOMÉTRICAS (si existe el módulo) -->
            <?php if ($hasTestsModule): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTests">
                    <button class="accordion-button <?php echo isInDirectory('pruebas') ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTests" aria-expanded="<?php echo isInDirectory('pruebas') ? 'true' : 'false'; ?>" aria-controls="collapseTests">
                        <i class="fas fa-chart-bar me-2"></i> EVALUACIONES
                        <?php if ($pendingEvaluations > 0): ?>
                        <span class="badge bg-primary rounded-pill ms-2"><?php echo $pendingEvaluations; ?></span>
                        <?php endif; ?>
                    </button>
                </h2>
                <div id="collapseTests" class="accordion-collapse collapse <?php echo isInDirectory('pruebas') ? 'show' : ''; ?>" aria-labelledby="headingTests" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('index.php', 'pruebas') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pruebas/index.php' : 'pruebas/index.php'; ?>">
                                    <i class="fas fa-list"></i> Todas las pruebas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('nueva-prueba.php', 'pruebas') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pruebas/nueva-prueba.php' : 'pruebas/nueva-prueba.php'; ?>">
                                    <i class="fas fa-plus-circle"></i> Nueva prueba
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('categorias.php', 'pruebas') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pruebas/categorias.php' : 'pruebas/categorias.php'; ?>">
                                    <i class="fas fa-folder"></i> Categorías
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('resultados.php', 'pruebas') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pruebas/resultados.php' : 'pruebas/resultados.php'; ?>">
                                    <i class="fas fa-chart-pie"></i> Resultados
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('reporte.php', 'pruebas') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pruebas/reporte.php' : 'pruebas/reporte.php'; ?>">
                                    <i class="fas fa-file-alt"></i> Reportes
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SERVICIOS PREMIUM -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingPremium">
                    <button class="accordion-button <?php echo (isActive('premium.php') || isActive('suscripciones.php')) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePremium" aria-expanded="<?php echo (isActive('premium.php') || isActive('suscripciones.php')) ? 'true' : 'false'; ?>" aria-controls="collapsePremium">
                        <i class="fas fa-star me-2"></i> PREMIUM
                        <span class="badge bg-warning rounded-pill ms-2 text-dark">Nuevo</span>
                    </button>
                </h2>
                <div id="collapsePremium" class="accordion-collapse collapse <?php echo (isActive('premium.php') || isActive('suscripciones.php')) ? 'show' : ''; ?>" aria-labelledby="headingPremium" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('premium.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../premium.php' : 'premium.php'; ?>">
                                    <i class="fas fa-star"></i> Gestión de Planes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('suscripciones.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../suscripciones.php' : 'suscripciones.php'; ?>">
                                    <i class="fas fa-users"></i> Suscripciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('pagos.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../pagos.php' : 'pagos.php'; ?>">
                                    <i class="fas fa-credit-card"></i> Pagos
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- ADMINISTRACIÓN -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingAdmin">
                    <button class="accordion-button <?php echo (isActive('users.php') || isActive('settings.php')) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="<?php echo (isActive('users.php') || isActive('settings.php')) ? 'true' : 'false'; ?>" aria-controls="collapseAdmin">
                        <i class="fas fa-cogs me-2"></i> ADMINISTRACIÓN
                    </button>
                </h2>
                <div id="collapseAdmin" class="accordion-collapse collapse <?php echo (isActive('users.php') || isActive('settings.php')) ? 'show' : ''; ?>" aria-labelledby="headingAdmin" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('users.php') || isActive('user-new.php') || isActive('user-edit.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../users.php' : 'users.php'; ?>">
                                    <i class="fas fa-user-cog"></i> Usuarios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('settings.php') ? 'active' : ''; ?>" href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../settings.php' : 'settings.php'; ?>">
                                    <i class="fas fa-cogs"></i> Configuración
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón de Ver Sitio -->
        <div class="mt-4 px-3">
            <a href="<?php echo isInDirectory('vacantes') || isInDirectory('aplicaciones') || isInDirectory('candidatos') || isInDirectory('pruebas') ? '../../' : '../'; ?>" class="btn btn-outline-primary w-100" target="_blank">
                <i class="fas fa-external-link-alt"></i> Ver Sitio
            </a>
        </div>
    </div>
</nav>

<style>
/* Estilos mejorados para el sidebar */
.sidebar {
    height: 100vh;
    overflow-y: auto;
}

.sidebar-content {
    height: calc(100vh - 48px);
    overflow-y: auto;
    padding-bottom: 20px;
}

.accordion-button {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(0, 123, 255, 0.1);
    color: #007bff;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: none;
}

.accordion-body {
    padding: 0;
}

.nav-link {
    padding: 0.5rem 1.5rem;
    font-size: 0.9rem;
}

.nav-link.active {
    background-color: rgba(0, 123, 255, 0.1);
    color: #007bff;
    border-left: 3px solid #007bff;
}

.nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 8px;
}

.accordion-item {
    border-radius: 0 !important;
    border-left: none;
    border-right: none;
}

/* Estilos para pantallas pequeñas */
@media (max-width: 767.98px) {
    .sidebar {
        height: auto;
    }
    
    .sidebar-content {
        height: auto;
    }
}
</style>