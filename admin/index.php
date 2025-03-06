<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/index.php - Página principal del panel
 */

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

// Título de la página
$pageTitle = 'Dashboard - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="post-new.php" class="btn btn-sm btn-outline-primary">Nuevo Artículo</a>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Artículos</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalPosts; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="posts.php" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Categorías</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalCategories; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-folder fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="categories.php" class="text-white mt-3 d-block small">Ver todas <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Comentarios</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalComments; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-comments fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="comments.php" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Suscriptores</h5>
                                        <h2 class="mt-2 mb-0"><?php echo $totalSubscribers; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <a href="subscribers.php" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Content Row -->
                <div class="row">
                    <!-- Recent Posts -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
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
                    
                    <!-- Recent Comments -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Comentarios Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
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
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="comments.php" class="btn btn-sm btn-outline-primary">Ver todos los comentarios</a>
                                <?php if ($pendingComments > 0): ?>
                                <a href="comments.php?status=pending" class="btn btn-sm btn-warning ms-2">
                                    <?php echo $pendingComments; ?> comentarios pendientes
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="post-new.php" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-file-alt me-2"></i> Nuevo Artículo
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="category-new.php" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-folder-plus me-2"></i> Nueva Categoría
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="media.php" class="btn btn-info btn-lg w-100">
                                            <i class="fas fa-images me-2"></i> Gestionar Multimedia
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="comments.php?status=pending" class="btn btn-warning btn-lg w-100">
                                            <i class="fas fa-comment-dots me-2"></i> Moderar Comentarios
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>