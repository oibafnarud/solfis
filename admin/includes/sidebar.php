<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky sidebar-content">
        <div class="text-center mb-3 d-none d-md-block">
            <img src="../img/logo.png" alt="SolFis" class="img-fluid" style="max-width: 120px;">
        </div>
        
<<<<<<< HEAD
        <!-- Menú colapsable con acordeón -->
        <div class="accordion" id="sidebarAccordion">
            <!-- BLOG -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingBlog">
                    <button class="accordion-button <?php echo (strpos($_SERVER['PHP_SELF'], 'posts.php') === false && strpos($_SERVER['PHP_SELF'], 'categories.php') === false && strpos($_SERVER['PHP_SELF'], 'comments.php') === false) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBlog" aria-expanded="<?php echo (strpos($_SERVER['PHP_SELF'], 'posts.php') !== false || strpos($_SERVER['PHP_SELF'], 'categories.php') !== false || strpos($_SERVER['PHP_SELF'], 'comments.php') !== false) ? 'true' : 'false'; ?>" aria-controls="collapseBlog">
                        <i class="fas fa-blog me-2"></i> BLOG
                    </button>
                </h2>
                <div id="collapseBlog" class="accordion-collapse collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'posts.php') !== false || strpos($_SERVER['PHP_SELF'], 'categories.php') !== false || strpos($_SERVER['PHP_SELF'], 'comments.php') !== false) ? 'show' : ''; ?>" aria-labelledby="headingBlog" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>index.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['posts.php', 'post-new.php', 'post-edit.php']) ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>posts.php">
                                    <i class="fas fa-file-alt"></i> Artículos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'category-new.php', 'category-edit.php']) ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>categories.php">
                                    <i class="fas fa-folder"></i> Categorías
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'comments.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>comments.php">
                                    <i class="fas fa-comments"></i> Comentarios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>media.php">
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
                    <button class="accordion-button <?php echo strpos($_SERVER['PHP_SELF'], 'contact.php') === false ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMessages" aria-expanded="<?php echo strpos($_SERVER['PHP_SELF'], 'contact.php') !== false ? 'true' : 'false'; ?>" aria-controls="collapseMessages">
                        <i class="fas fa-envelope me-2"></i> MENSAJES
                    </button>
                </h2>
                <div id="collapseMessages" class="accordion-collapse collapse <?php echo strpos($_SERVER['PHP_SELF'], 'contact.php') !== false ? 'show' : ''; ?>" aria-labelledby="headingMessages" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>contact.php">
                                    <i class="fas fa-envelope"></i> Mensajes de Contacto
                                    <?php 
                                    if (class_exists('Contact')) {
                                        $contact = new Contact();
                                        $newCount = $contact->getMessages(1, 1, 'new')['total'];
                                        if ($newCount > 0): 
                                    ?>
                                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $newCount; ?></span>
                                    <?php 
                                        endif;
                                    } 
                                    ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'test-email.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>test-email.php">
                                    <i class="fas fa-paper-plane"></i> Probar Correo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'email-settings.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>email-settings.php">
                                    <i class="fas fa-cog"></i> Configuración de Correo
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- VACANTES Y RECLUTAMIENTO -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingVacancies">
                    <button class="accordion-button <?php echo (strpos($_SERVER['PHP_SELF'], '/vacantes/') === false && strpos($_SERVER['PHP_SELF'], '/aplicaciones/') === false && strpos($_SERVER['PHP_SELF'], '/candidatos/') === false) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVacancies" aria-expanded="<?php echo (strpos($_SERVER['PHP_SELF'], '/vacantes/') !== false || strpos($_SERVER['PHP_SELF'], '/aplicaciones/') !== false || strpos($_SERVER['PHP_SELF'], '/candidatos/') !== false) ? 'true' : 'false'; ?>" aria-controls="collapseVacancies">
                        <i class="fas fa-briefcase me-2"></i> VACANTES
                    </button>
                </h2>
                <div id="collapseVacancies" class="accordion-collapse collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/vacantes/') !== false || strpos($_SERVER['PHP_SELF'], '/aplicaciones/') !== false || strpos($_SERVER['PHP_SELF'], '/candidatos/') !== false) ? 'show' : ''; ?>" aria-labelledby="headingVacancies" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'vacante-nueva.php', 'vacante-editar.php']) && strpos($_SERVER['PHP_SELF'], '/vacantes/') !== false ? 'active' : ''; ?>" href="<?php echo getBaseAdminUrl(); ?>vacantes/index.php">
                                    <i class="fas fa-list"></i> Todas las Vacantes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'vacante-nueva.php' ? 'active' : ''; ?>" href="<?php echo getBaseAdminUrl(); ?>vacantes/vacante-nueva.php">
                                    <i class="fas fa-plus-circle"></i> Nueva Vacante
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/aplicaciones/') !== false ? 'active' : ''; ?>" href="<?php echo getBaseAdminUrl(); ?>aplicaciones/index.php">
                                    <i class="fas fa-clipboard-list"></i> Aplicaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/candidatos/') !== false ? 'active' : ''; ?>" href="<?php echo getBaseAdminUrl(); ?>candidatos/index.php">
                                    <i class="fas fa-user-tie"></i> Candidatos
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- ADMINISTRACIÓN -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingAdmin">
                    <button class="accordion-button <?php echo (strpos($_SERVER['PHP_SELF'], 'users.php') === false && strpos($_SERVER['PHP_SELF'], 'settings.php') === false) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="<?php echo (strpos($_SERVER['PHP_SELF'], 'users.php') !== false || strpos($_SERVER['PHP_SELF'], 'settings.php') !== false) ? 'true' : 'false'; ?>" aria-controls="collapseAdmin">
                        <i class="fas fa-cogs me-2"></i> ADMINISTRACIÓN
                    </button>
                </h2>
                <div id="collapseAdmin" class="accordion-collapse collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'users.php') !== false || strpos($_SERVER['PHP_SELF'], 'settings.php') !== false) ? 'show' : ''; ?>" aria-labelledby="headingAdmin" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'user-new.php', 'user-edit.php']) ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>users.php">
                                    <i class="fas fa-user-cog"></i> Usuarios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>settings.php">
                                    <i class="fas fa-cogs"></i> Configuración
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
=======
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>BLOG</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['posts.php', 'post-new.php', 'post-edit.php']) ? 'active' : ''; ?>" href="posts.php">
                    <i class="fas fa-file-alt"></i> Artículos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'category-new.php', 'category-edit.php']) ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-folder"></i> Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                    <i class="fas fa-comments"></i> Comentarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : ''; ?>" href="media.php">
                    <i class="fas fa-images"></i> Multimedia
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'subscribers.php' ? 'active' : ''; ?>" href="subscribers.php">
                    <i class="fas fa-users"></i> Suscriptores
                </a>
            </li>
        </ul>
		
		<!-- 
		MENSAJES
		-->

		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
			<span>MENSAJES</span>
		</h6>
		<ul class="nav flex-column">
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
					<i class="fas fa-envelope"></i> Mensajes de Contacto
					<?php 
					if (class_exists('Contact')) {
						$contact = new Contact();
						$newCount = $contact->getMessages(1, 1, 'new')['total'];
						if ($newCount > 0): 
					?>
					<span class="badge bg-danger rounded-pill ms-2"><?php echo $newCount; ?></span>
					<?php 
						endif;
					} 
					?>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'test-email.php' ? 'active' : ''; ?>" href="test-email.php">
					<i class="fas fa-paper-plane"></i> Probar Correo
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'email-settings.php' ? 'active' : ''; ?>" href="email-settings.php">
					<i class="fas fa-cog"></i> Configuración de Correo
				</a>
			</li>
		</ul>
		
		<!-- 
		VACANTES Y RECLUTAMIENTO
		-->

		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
			<span>VACANTES Y RECLUTAMIENTO</span>
		</h6>
		<ul class="nav flex-column">
			<li class="nav-item">
				<a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'vacante-nueva.php', 'vacante-editar.php']) && strpos($_SERVER['PHP_SELF'], '/vacantes/') !== false ? 'active' : ''; ?>" href="<?php echo isset($adminUrl) ? $adminUrl : ''; ?>vacantes/index.php">
					<i class="fas fa-briefcase"></i> Vacantes
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/aplicaciones/') !== false ? 'active' : ''; ?>" href="<?php echo isset($adminUrl) ? $adminUrl : ''; ?>aplicaciones/index.php">
					<i class="fas fa-clipboard-list"></i> Aplicaciones
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/candidatos/') !== false ? 'active' : ''; ?>" href="<?php echo isset($adminUrl) ? $adminUrl : ''; ?>candidatos/index.php">
					<i class="fas fa-user-tie"></i> Candidatos
				</a>
			</li>
		</ul>
>>>>>>> bfdd4b60a420df76ff03f2ca490715c5b78545c5
        
        <!-- Botón de Ver Sitio -->
        <div class="mt-4 px-3">
            <a href="../" class="btn btn-outline-primary w-100" target="_blank">
                <i class="fas fa-external-link-alt"></i> Ver Sitio
            </a>
        </div>
    </div>
</nav>

<?php
/**
 * Función para obtener la URL base del panel de administración
 * Soluciona problemas de rutas relativas
 */
function getBaseUrl() {
    $currentPath = $_SERVER['PHP_SELF'];
    
    // Si estamos en un subdirectorio (como vacantes, aplicaciones, etc.)
    if (strpos($currentPath, '/vacantes/') !== false || 
        strpos($currentPath, '/aplicaciones/') !== false || 
        strpos($currentPath, '/candidatos/') !== false) {
        return '../';
    }
    
    return '';
}

/**
 * Función para obtener la URL base específica para módulos de admin
 */
function getBaseAdminUrl() {
    $currentPath = $_SERVER['PHP_SELF'];
    
    // Si estamos en el directorio principal de admin
    if (basename(dirname($currentPath)) === 'admin') {
        return '';
    }
    
    // Si estamos en un módulo
    if (strpos($currentPath, '/vacantes/') !== false) {
        return '../';
    }
    
    // Si estamos en otro módulo diferente de vacantes
    if (strpos($currentPath, '/aplicaciones/') !== false || 
        strpos($currentPath, '/candidatos/') !== false) {
        return '../';
    }
    
    return '';
}
?>

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