<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/profile.php - Página de perfil de usuario
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

// Obtener datos del usuario actual
$user = new User();
$userData = $user->getUserById($auth->getUserId());

// Procesar actualización de perfil
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Validar datos
    if (empty($name) || empty($email)) {
        $error = 'El nombre y el email son obligatorios.';
    } elseif (!Helpers::validateEmail($email)) {
        $error = 'El email no es válido.';
    } else {
        // Actualizar perfil
        $data = [
            'name' => $name,
            'email' => $email,
            'bio' => $bio
        ];
        
        // Si se proporcionó una nueva imagen, procesarla
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $media = new Media();
            $result = $media->uploadImage($_FILES['image']);
            
            if ($result['success']) {
                $data['image'] = $result['file'];
            }
        }
        
        // Actualizar datos del usuario
        $result = $user->updateUser($auth->getUserId(), $data);
        
        // Cambiar contraseña si se proporcionaron ambas
        if (!empty($currentPassword) && !empty($newPassword)) {
            // Verificar contraseña actual
            if ($user->verifyPassword($auth->getUserId(), $currentPassword)) {
                if (strlen($newPassword) < 6) {
                    $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                } else {
                    $user->changePassword($auth->getUserId(), $newPassword);
                    $success = true;
                }
            } else {
                $error = 'La contraseña actual no es correcta.';
            }
        } else {
            $success = $result;
        }
        
        // Actualizar datos de sesión si fue exitoso
        if ($success) {
            $updatedUser = $user->getUserById($auth->getUserId());
            $_SESSION['user'] = $updatedUser;
        }
    }
}

// Título de la página
$pageTitle = 'Mi Perfil - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mi Perfil</h1>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Perfil actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if (!empty($userData['image'])): ?>
                            <img src="<?php echo '../' . $userData['image']; ?>" alt="<?php echo $userData['name']; ?>" class="rounded-circle mb-3" width="150" height="150">
                            <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-5x"></i>
                            </div>
                            <?php endif; ?>
                            
                            <h4><?php echo $userData['name']; ?></h4>
                            <p class="text-muted"><?php echo $userData['email']; ?></p>
                            
                            <div class="mt-3">
                                <span class="badge bg-<?php echo $userData['role'] === 'admin' ? 'danger' : ($userData['role'] === 'editor' ? 'success' : 'info'); ?> py-2 px-3">
                                    <?php 
                                    echo $userData['role'] === 'admin' ? 'Administrador' : 
                                        ($userData['role'] === 'editor' ? 'Editor' : 'Autor'); 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Editar Perfil</h5>
                            
                            <form action="profile.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre completo *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $userData['name']; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData['email']; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Biografía</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $userData['bio'] ?? ''; ?></textarea>
                                    <div class="form-text">Breve descripción que aparecerá junto a tus artículos.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Imagen de perfil</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">Formatos permitidos: JPG, JPEG, PNG, GIF. Tamaño máximo: 2MB.</div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5>Cambiar contraseña</h5>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Contraseña actual</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <div class="form-text">Dejar ambos campos en blanco para no cambiar la contraseña.</div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>