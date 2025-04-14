<?php
// Inicializar sesión
session_start();

// Redirigir si ya está autenticado
if (isset($_SESSION['candidato_id'])) {
    header('Location: panel.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Variables de control
$error = '';
$email = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validar que no estén vacíos
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingrese su email y contraseña.';
    } else {
        try {
            // Instanciar base de datos
            $db = Database::getInstance();
            
            // Escapar datos para prevenir inyección SQL
            $email_safe = $db->escape($email);
            
            // Buscar candidato por email
            $sql = "SELECT id, nombre, apellido, email, password FROM candidatos WHERE email = '$email_safe'";
            $result = $db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $candidato = $result->fetch_assoc();
                
                // Verificar contraseña
                if (password_verify($password, $candidato['password'])) {
                    // Contraseña correcta, iniciar sesión
                    $_SESSION['candidato_id'] = $candidato['id'];
                    $_SESSION['candidato_nombre'] = $candidato['nombre'];
                    $_SESSION['candidato_apellido'] = $candidato['apellido'];
                    $_SESSION['candidato_email'] = $candidato['email'];
                    
                    // Registrar fecha de último login
                    $update_sql = "UPDATE candidatos SET ultimo_login = NOW() WHERE id = " . $candidato['id'];
                    $db->query($update_sql);
                    
                    // Redirigir al panel
                    header('Location: panel.php');
                    exit;
                } else {
                    // Contraseña incorrecta
                    $error = 'La contraseña ingresada es incorrecta.';
                }
            } else {
                // Email no encontrado
                $error = 'No existe una cuenta registrada con este email.';
            }
        } catch (Exception $e) {
            $error = 'Error al procesar el inicio de sesión. Por favor intente nuevamente.';
        }
    }
}

// Variables para la página
$site_title = "Iniciar Sesión - Portal de Candidatos";
$site_description = "Accede a tu cuenta de candidato en SolFis y gestiona tus aplicaciones.";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    
    <!-- Si no existe el archivo CSS específico de candidato, puedes usar estos estilos inline -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0088cc;
            --accent-color: #ff9900;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #333333;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        .login-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 16px;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .login-form input[type="email"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .login-form input[type="email"]:focus,
        .login-form input[type="password"]:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.2);
        }
        
        .login-form .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .login-form .remember-me input {
            margin-right: 10px;
        }
        
        .login-form button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-form button:hover {
            background-color: var(--secondary-color);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
        
        .login-footer a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
        }
        
        .alert-danger {
            background-color: #dc3545;
        }
        
        .alert-info {
            background-color: var(--secondary-color);
        }
        
        .brand-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .brand-logo img {
            max-height: 60px;
        }
        
        .testable {
            display: none;
            cursor: pointer;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: var(--light-gray);
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }
        
        .testable:hover {
            background-color: var(--medium-gray);
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="brand-logo">
            <img src="../assets/img/logo.png" alt="SolFis Logo">
        </div>
        
        <div class="login-header">
            <h1>Iniciar Sesión</h1>
            <p>Accede a tu cuenta como candidato</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Recordar mi sesión</label>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
            
            <div class="testable" id="autofill-test">
                Autocompletar (Solo para pruebas)
            </div>
        </form>
        
        <div class="login-footer">
            <p>¿No tienes una cuenta? <a href="../registro-candidato.php">Regístrate aquí</a></p>
            <p><a href="recuperar-contrasena.php">¿Olvidaste tu contraseña?</a></p>
        </div>
    </div>
    
    <script>
        // Función para auto-rellenar en entorno de desarrollo
        document.getElementById('autofill-test').addEventListener('click', function() {
            document.getElementById('email').value = 'test@example.com';
            document.getElementById('password').value = 'password123';
        });
        
        // Mostrar auto-relleno solo en desarrollo (localhost)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            document.getElementById('autofill-test').style.display = 'block';
        }
    </script>
</body>
</html>