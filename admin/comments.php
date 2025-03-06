<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/comments.php - Página para gestionar comentarios
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

// Instanciar clase de comentarios
$comment = new Comment();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$per_page = 10;

// Obtener comentarios con paginación y filtros
$commentsData = $comment->getAdminComments($page, $per_page, $status);
$comments = $commentsData['comments'];
$totalPages = $commentsData['pages'];

// Procesar acciones masivas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['comment_ids'])) {
    $action = $_POST['action'];
    $commentIds = $_POST['comment_ids'];
    
    switch ($action) {
        case 'approve':
            foreach ($commentIds as $id) {
                $comment->approveComment($id);
            }
            header('Location: comments.php?message=comments-approved');
            exit;
            break;
            
        case 'reject':
            foreach ($commentIds as $id) {
                $comment->rejectComment($id);
            }
            header('Location: comments.php?message=comments-rejected');
            exit;
            break;
            
        case 'delete':
            foreach ($commentIds as $id) {
                $comment->deleteComment($id);
            }
            header('Location: comments.php?message=comments-deleted');
            exit;
            break;
    }
}

// Mensajes de notificación
$messages = [
    'comment-approved' => ['type' => 'success', 'text' => 'Comentario aprobado correctamente.'],
    'comment-rejected' => ['type' => 'success', 'text' => 'Comentario rechazado correctamente.'],
    'comment-deleted' => ['type' => 'success', 'text' => 'Comentario eliminado correctamente.'],
    'comments-approved' => ['type' => 'success', 'text' => 'Comentarios aprobados correctamente.'],
    'comments-rejected' => ['type' => 'success', 'text' => 'Comentarios rechazados correctamente.'],
    'comments-deleted' => ['type' => 'success', 'text' => 'Comentarios eliminados correctamente.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Comentarios - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Comentarios</h1>
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
                        <div class="d-flex">
                            <a href="comments.php" class="btn <?php echo empty($status) ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                Todos
                            </a>
                            <a href="comments.php?status=pending" class="btn <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?> me-2">
                                Pendientes
                            </a>
                            <a href="comments.php?status=approved" class="btn <?php echo $status === 'approved' ? 'btn-success' : 'btn-outline-success'; ?> me-2">
                                Aprobados
                            </a>
                            <a href="comments.php?status=rejected" class="btn <?php echo $status === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                Rechazados
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de comentarios -->
                <div class="card">
                    <div class="card-body">
                        <form action="comments.php" method="post">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input select-all" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Autor</th>
                                            <th>Comentario</th>
                                            <th>En respuesta a</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($comments)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">No hay comentarios que coincidan con los filtros aplicados.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($comments as $comment): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input comment-select" type="checkbox" name="comment_ids[]" value="<?php echo $comment['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo $comment['name']; ?></strong><br>
                                                    <small><?php echo $comment['email']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo substr($comment['content'], 0, 100) . (strlen($comment['content']) > 100 ? '...' : ''); ?>
                                                </td>
                                                <td>
                                                    <a href="post-edit.php?id=<?php echo $comment['post_id']; ?>" class="text-decoration-none">
                                                        <?php echo $comment['post_title']; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if ($comment['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Aprobado</span>
                                                    <?php elseif ($comment['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rechazado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($comment['status'] !== 'approved'): ?>
                                                        <a href="comment-approve.php?id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($comment['status'] !== 'rejected'): ?>
                                                        <a href="comment-reject.php?id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="comment-delete.php?id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar este comentario?');">
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
                                        <option value="approve">Aprobar</option>
                                        <option value="reject">Rechazar</option>
                                        <option value="delete">Eliminar</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de comentarios">
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">
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
    const checkboxes = document.querySelectorAll('.comment-select');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php include 'includes/footer.php'; ?>