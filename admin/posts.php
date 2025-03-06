<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/posts.php - Página para listar y gestionar todos los artículos
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

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$per_page = 10;

// Obtener artículos con paginación y filtros
$postsData = $blogPost->getAdminPosts($page, $per_page, $status);
$posts = $postsData['posts'];
$totalPages = $postsData['pages'];

// Obtener categorías para filtrar
$categories = $category->getCategories();

// Procesar acciones masivas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['post_ids'])) {
    $action = $_POST['action'];
    $postIds = $_POST['post_ids'];
    
    switch ($action) {
        case 'publish':
            foreach ($postIds as $id) {
                $post = $blogPost->getPostById($id);
                if ($post) {
                    $blogPost->updatePost($id, ['status' => 'published'] + $post);
                }
            }
            header('Location: posts.php?message=posts-published');
            exit;
            break;
            
        case 'draft':
            foreach ($postIds as $id) {
                $post = $blogPost->getPostById($id);
                if ($post) {
                    $blogPost->updatePost($id, ['status' => 'draft'] + $post);
                }
            }
            header('Location: posts.php?message=posts-drafted');
            exit;
            break;
            
        case 'delete':
            foreach ($postIds as $id) {
                $blogPost->deletePost($id);
            }
            header('Location: posts.php?message=posts-deleted');
            exit;
            break;
    }
}

// Mensajes de notificación
$messages = [
    'post-updated' => ['type' => 'success', 'text' => 'Artículo actualizado correctamente.'],
    'post-deleted' => ['type' => 'success', 'text' => 'Artículo eliminado correctamente.'],
    'posts-published' => ['type' => 'success', 'text' => 'Artículos publicados correctamente.'],
    'posts-drafted' => ['type' => 'success', 'text' => 'Artículos movidos a borradores.'],
    'posts-deleted' => ['type' => 'success', 'text' => 'Artículos eliminados correctamente.'],
    'post-created' => ['type' => 'success', 'text' => 'Nuevo artículo creado correctamente.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Artículos - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Artículos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="post-new.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Nuevo Artículo
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($notification): ?>
                <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $notification['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="posts.php" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Estado</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Publicados</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Borradores</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archivados</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Categoría</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId === $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['name']; ?> (<?php echo $cat['post_count']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <?php if ($status || $categoryId): ?>
                                <a href="posts.php" class="btn btn-outline-secondary ms-2">Limpiar Filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de artículos -->
                <div class="card">
                    <div class="card-body">
                        <form action="posts.php" method="post">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input select-all" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Comentarios</th>
                                            <th>Publicado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($posts)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No hay artículos que coincidan con los filtros aplicados.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($posts as $post): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input post-select" type="checkbox" name="post_ids[]" value="<?php echo $post['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo $post['title']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $post['author_name']; ?></td>
                                                <td><?php echo $post['category_name']; ?></td>
                                                <td>
                                                    <?php if ($post['status'] === 'published'): ?>
                                                        <span class="badge bg-success">Publicado</span>
                                                    <?php elseif ($post['status'] === 'draft'): ?>
                                                        <span class="badge bg-secondary">Borrador</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Archivado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $comment = new Comment();
                                                    $commentCount = count($comment->getPostComments($post['id'], false));
                                                    echo $commentCount > 0 ? $commentCount : '-';
                                                    ?>
                                                </td>
                                                <td><?php echo $post['published_at'] ? date('d/m/Y', strtotime($post['published_at'])) : '-'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="../blog/<?php echo $post['slug']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="post-delete.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar este artículo?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Acciones en lote -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="bulk-actions d-flex align-items-center">
                                    <select name="action" class="form-select me-2">
                                        <option value="">Acciones en lote</option>
                                        <option value="publish">Publicar</option>
                                        <option value="draft">Mover a borradores</option>
                                        <option value="delete">Eliminar</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de artículos">
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                                &raquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
// Seleccionar todos los checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.post-select');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php include 'includes/footer.php'; ?>