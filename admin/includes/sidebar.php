<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4 d-none d-md-block">
            <img src="../img/logo.png" alt="SolFis" class="img-fluid" style="max-width: 150px;">
        </div>
        
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
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>ADMINISTRACIÓN</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'user-new.php', 'user-edit.php']) ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-user-cog"></i> Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cogs"></i> Configuración
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>ACCESOS DIRECTOS</span>
        </h6>
        <ul class="nav flex-column mb-4">
            <li class="nav-item">
                <a class="nav-link" href="post-new.php">
                    <i class="fas fa-plus-circle"></i> Nuevo Artículo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="media.php">
                    <i class="fas fa-upload"></i> Subir Imagen
                </a>
            </li>
        </ul>
    </div>
</nav>