/*
 * Archivos de inclusión para el panel de administración
 */

// header.php - Encabezado común para todas las páginas del panel
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
    
    <!-- Custom CSS -->
    <style>
        .admin-main {
            padding-top: 20px;
        }
        
        .sidebar {
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            padding-top: 20px;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        
        .sidebar .nav-link.active {
            color: #007bff;
        }
        
        .sidebar .nav-link:hover {
            color: #0056b3;
        }
        
        .sidebar .nav-link .feather {
            margin-right: 4px;
            color: #999;
        }
        
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }
        
        .logo-admin {
            max-height: 50px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
            <img src="../img/logo-white.png" alt="SolFis" height="30">
            <span class="ms-2">Admin Blog</span>
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

<?php
// sidebar.php - Barra lateral para el panel de administración
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky">
        <div class="text-center mb-4 d-none d-md-block">
            <img src="../img/logo.png" alt="SolFis" class="logo-admin">
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
                    <?php 
                    $comment = new Comment();
                    $pendingCount = $comment->getAdminComments(1, 1, 'pending')['total'];
                    if ($pendingCount > 0):
                    ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
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
        
        <?php if ($auth->isAdmin()): ?>
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
        <?php endif; ?>
        
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

<?php
// footer.php - Pie de página común para todas las páginas del panel
?>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Admin Script -->
    <script>
        // Mensajes de alerta con autocierre
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 4000);
        });
    </script>
</body>
</html>