<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/login.php - Página de inicio de sesión
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar si ya está autenticado
$auth = Auth::getInstance();
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario de inicio de sesión
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        if ($auth->login($email, $password)) {
            // Redireccionar al dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales inválidas. Por favor, intente de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel de Administración SolFis</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
            margin: auto;
        }
        
        .form-login {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px;
        }
        
        .logo {
            max-width: 180px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container text-center">
        <div class="form-login">
            <img src="../img/logo.png" alt="SolFis" class="logo">
            <h1 class="h4 mb-3 fw-normal">Panel de Administración</h1>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                    <label for="email">Correo electrónico</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <label for="password">Contraseña</label>
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                    </button>
                </div>
                <div class="mt-3">
                    <a href="../" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Volver al sitio
                    </a>
                </div>
            </form>
        </div>
        <p class="mt-4 text-muted">&copy; <?php echo date('Y'); ?> SolFis. Todos los derechos reservados.</p>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>