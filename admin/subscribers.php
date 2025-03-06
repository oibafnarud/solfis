<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/subscribers.php - Página para gestionar suscriptores
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

// Instanciar clase de suscriptores
$subscriber = new Subscriber();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$per_page = 20;

// Obtener suscriptores con paginación y filtros
$subscribersData = $subscriber->getSubscribers($page, $per_page, $status);
$subscribers = $subscribersData['subscribers'];
$totalPages = $subscribersData['pages'];

// Procesar acciones masivas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['subscriber_ids'])) {
    $action = $_POST['action'];
    $subscriberIds = $_POST['subscriber_ids'];
    
    switch ($action) {
        case 'activate':
            foreach ($subscriberIds as $id) {
                $subscriber->changeStatus($id, 'active');
            }
            header('Location: subscribers.php?message=subscribers-activated');
            exit;
            break;
            
        case 'deactivate':
            foreach ($subscriberIds as $id) {
                $subscriber->changeStatus($id, 'inactive');
            }
            header('Location: subscribers.php?message=subscribers-deactivated');
            exit;
            break;
            
        case 'delete':
            foreach ($subscriberIds as $id) {
                $subscriber->deleteSubscriber($id);
            }
            header('Location: subscribers.php?message=subscribers-deleted');
            exit;
            break;
    }
}

// Mensajes de notificación
$messages = [
    'subscriber-activated' => ['type' => 'success', 'text' => 'Suscriptor activado correctamente.'],
    'subscriber-deactivated' => ['type' => 'success', 'text' => 'Suscriptor desactivado correctamente.'],
    'subscriber-deleted' => ['type' => 'success', 'text' => 'Suscriptor eliminado correctamente.'],
    'subscribers-activated' => ['type' => 'success', 'text' => 'Suscriptores activados correctamente.'],
    'subscribers-deactivated' => ['type' => 'success', 'text' => 'Suscriptores desactivados correctamente.'],
    'subscribers-deleted' => ['type' => 'success', 'text' => 'Suscriptores eliminados correctamente.'],
    'subscriber-added' => ['type' => 'success', 'text' => 'Nuevo suscriptor agregado correctamente.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Suscriptores - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Suscriptores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                                <i class="fas fa-user-plus"></i> Agregar Suscriptor
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportSubscribersModal">
                                <i class="fas fa-file-export"></i> Exportar Lista
                            </button>
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
                        <div class="d-flex">
                            <a href="subscribers.php?status=active" class="btn <?php echo $status === 'active' ? 'btn-success' : 'btn-outline-success'; ?> me-2">
                                Activos
                            </a>
                            <a href="subscribers.php?status=inactive" class="btn <?php echo $status === 'inactive' ? 'btn-warning' : 'btn-outline-warning'; ?> me-2">
                                Inactivos
                            </a>
                            <a href="subscribers.php?status=unsubscribed" class="btn <?php echo $status === 'unsubscribed' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                Dados de baja
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de suscriptores -->
                <div class="card">
                    <div class="card-body">
                        <form action="subscribers.php" method="post">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input select-all" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Email</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th>Fecha de Suscripción</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($subscribers)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No hay suscriptores que coincidan con los filtros aplicados.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($subscribers as $sub): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input subscriber-select" type="checkbox" name="subscriber_ids[]" value="<?php echo $sub['id']; ?>">
                                                    </div>
                                                </td>
                                                <td><?php echo $sub['email']; ?></td>
                                                <td><?php echo $sub['name'] ? $sub['name'] : '<em class="text-muted">Sin nombre</em>'; ?></td>
                                                <td>
                                                    <?php if ($sub['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php elseif ($sub['status'] === 'inactive'): ?>
                                                        <span class="badge bg-warning text-dark">Inactivo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Dado de baja</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($sub['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($sub['status'] !== 'active'): ?>
                                                        <a href="subscriber-activate.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-success" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($sub['status'] !== 'inactive'): ?>
                                                        <a href="subscriber-deactivate.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-warning" title="Desactivar">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="subscriber-delete.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este suscriptor?');">
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
                                        <option value="activate">Activar</option>
                                        <option value="deactivate">Desactivar</option>
                                        <option value="delete">Eliminar</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de suscriptores">
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>">
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

<!-- Modal para agregar nuevo suscriptor -->
<div class="modal fade" id="addSubscriberModal" tabindex="-1" aria-labelledby="addSubscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubscriberModalLabel">Agregar Nuevo Suscriptor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="subscriber-add.php" method="post" id="subscriber-form">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre (opcional)</label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="subscriber-form">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para exportar suscriptores -->
<div class="modal fade" id="exportSubscribersModal" tabindex="-1" aria-labelledby="exportSubscribersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportSubscribersModalLabel">Exportar Lista de Suscriptores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="subscribers-export.php" method="post" id="export-form">
                    <div class="mb-3">
                        <label for="export_status" class="form-label">Estado de suscriptores a exportar</label>
                        <select class="form-select" id="export_status" name="export_status">
                            <option value="all">Todos los suscriptores</option>
                            <option value="active">Solo suscriptores activos</option>
                            <option value="inactive">Solo suscriptores inactivos</option>
                            <option value="unsubscribed">Solo suscriptores dados de baja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Formato de exportación</label>
                        <select class="form-select" id="export_format" name="export_format">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" form="export-form">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Seleccionar todos los checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.subscriber-select');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php include 'includes/footer.php'; ?>