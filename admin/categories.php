<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/categories.php - Página para gestionar categorías
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

// Instanciar clase de categorías
$category = new Category();

// Obtener todas las categorías
$categories = $category->getCategories();

// Mensajes de notificación
$messages = [
    'category-updated' => ['type' => 'success', 'text' => 'Categoría actualizada correctamente.'],
    'category-deleted' => ['type' => 'success', 'text' => 'Categoría eliminada correctamente.'],
    'category-created' => ['type' => 'success', 'text' => 'Nueva categoría creada correctamente.'],
    'category-error' => ['type' => 'danger', 'text' => 'No se puede eliminar la categoría porque tiene artículos asociados.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Categorías - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Categorías</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="category-new.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Nueva Categoría
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
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Slug</th>
                                        <th>Descripción</th>
                                        <th>Artículos</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No hay categorías disponibles.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><?php echo $cat['name']; ?></td>
                                            <td><?php echo $cat['slug']; ?></td>
                                            <td><?php echo substr($cat['description'], 0, 100) . (strlen($cat['description']) > 100 ? '...' : ''); ?></td>
                                            <td><?php echo $cat['post_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="category-edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <?php if ($cat['post_count'] == 0): ?>
                                                    <a href="category-delete.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar esta categoría?');">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-danger" disabled title="No se puede eliminar una categoría con artículos">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>