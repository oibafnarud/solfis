<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/users.php - Página para gestionar usuarios
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

// Verificar que sea administrador
if (!$auth->isAdmin()) {
    header('Location: index.php?message=permission-denied');
    exit;
}

// Instanciar clase de usuarios
$user = new User();

// Obtener la lista de usuarios
$users = $user->getUsers();

// Mensajes de notificación
$messages = [
    'user-updated' => ['type' => 'success', 'text' => 'Usuario actualizado correctamente.'],
    'user-deleted' => ['type' => 'success', 'text' => 'Usuario eliminado correctamente.'],
    'user-created' => ['type' => 'success', 'text' => 'Nuevo usuario creado correctamente.'],
    'user-error' => ['type' => 'danger', 'text' => 'No se puede eliminar el usuario porque tiene artículos asociados.'],
    'permission-denied' => ['type' => 'danger', 'text' => 'No tienes permisos para realizar esta acción.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Usuarios - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestión de Usuarios</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
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
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Fecha de registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No hay usuarios registrados.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $userItem): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($userItem['image'])): ?>
                                            <img src="<?php echo '../' . $userItem['image']; ?>" alt="<?php echo $userItem['name']; ?>" class="rounded-circle me-2" width="30" height="30">
                                            <?php endif; ?>
                                            <?php echo $userItem['name']; ?>
                                        </td>
                                        <td><?php echo $userItem['email']; ?></td>
                                        <td>
                                            <?php if ($userItem['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Administrador</span>
                                            <?php elseif ($userItem['role'] === 'editor'): ?>
                                                <span class="badge bg-success">Editor</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Autor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($userItem['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-user" 
                                                        data-id="<?php echo $userItem['id']; ?>"
                                                        data-name="<?php echo $userItem['name']; ?>"
                                                        data-email="<?php echo $userItem['email']; ?>"
                                                        data-role="<?php echo $userItem['role']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <?php if ($userItem['id'] !== $auth->getUserId()): ?>
                                                <a href="user-delete.php?id=<?php echo $userItem['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar este usuario?');">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </a>
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

<!-- Modal para añadir usuario -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="user-add.php" method="post" id="addUserForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="author">Autor</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Crear Usuario</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="user-edit.php" method="post" id="editUserForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <div class="form-text">Dejar en blanco para mantener la contraseña actual.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rol *</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="author">Autor</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editUserForm" class="btn btn-primary">Actualizar Usuario</button>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar datos del usuario en el modal de edición
document.querySelectorAll('.edit-user').forEach(function(button) {
    button.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.getAttribute('data-id');
        document.getElementById('edit_name').value = this.getAttribute('data-name');
        document.getElementById('edit_email').value = this.getAttribute('data-email');
        document.getElementById('edit_role').value = this.getAttribute('data-role');
    });
});
</script>

<?php include 'includes/footer.php'; ?>