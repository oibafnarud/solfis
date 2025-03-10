<?php
// Script de instalación para el blog SolFis
// Este script crea las tablas necesarias y permite crear un usuario administrador personalizado

// Inicializar variables para el formulario
$db_created = false;
$admin_created = false;
$error = null;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Incluir archivos necesarios
    require_once 'config.php';
    
    try {
        // Establecer conexión a la base de datos
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        // Verificar conexión
        if ($mysqli->connect_error) {
            throw new Exception('Error de conexión a la base de datos: ' . $mysqli->connect_error);
        }
        
        // Crear la base de datos si no existe
        $mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $mysqli->select_db(DB_NAME);
        $db_created = true;
        
        // Crear tablas
        // Tabla de usuarios
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'editor', 'author') NOT NULL DEFAULT 'author',
            image VARCHAR(255) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
        ");
        
        // Tabla de categorías
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
        ");
        
        // Tabla de posts
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT NOT NULL,
            excerpt TEXT DEFAULT NULL,
            category_id INT,
            author_id INT,
            status ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'draft',
            image VARCHAR(255) DEFAULT NULL,
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id),
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
        ");
        
        // Tabla de comentarios
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            parent_id INT DEFAULT 0,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
        ");
        
        // Tabla de multimedia
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            path VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            size INT NOT NULL,
            created_at DATETIME NOT NULL
        )
        ");
        
        // Tabla de suscriptores
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL
        )
        ");
        
        // Crear usuario administrador con los datos del formulario
        if (isset($_POST['create_admin']) && $_POST['create_admin'] == 1) {
            $admin_name = trim($_POST['admin_name']);
            $admin_email = trim($_POST['admin_email']);
            $admin_password = trim($_POST['admin_password']);
            
            // Validar campos
            if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
                throw new Exception('Todos los campos son obligatorios para crear el administrador.');
            }
            
            if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo electrónico no es válido.');
            }
            
            if (strlen($admin_password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres.');
            }
            
            // Verificar si el correo ya existe
            $check_email = $mysqli->query("SELECT id FROM users WHERE email = '$admin_email'");
            if ($check_email->num_rows > 0) {
                throw new Exception('Este correo electrónico ya está registrado.');
            }
            
            // Crear el usuario administrador
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');
            
            $mysqli->query("
            INSERT INTO users (name, email, password, role, created_at, updated_at) 
            VALUES ('$admin_name', '$admin_email', '$hashed_password', 'admin', '$now', '$now')
            ");
            
            $admin_created = true;
        }
        
        // Crear categoría por defecto si no existe ninguna
        $result = $mysqli->query("SELECT COUNT(*) as count FROM categories");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            $now = date('Y-m-d H:i:s');
            $mysqli->query("
            INSERT INTO categories (name, slug, description, created_at, updated_at) 
            VALUES ('General', 'general', 'Categoría general para artículos diversos', '$now', '$now')
            ");
        }
        
        $mysqli->close();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación del Blog SolFis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 40px 0;
        }
        .install-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .install-header img {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .success-message {
            text-align: center;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="install-header">
                <img src="img/logo.png" alt="SolFis">
                <h1>Instalación del Blog</h1>
                <p class="text-muted">Configure la base de datos y cree un usuario administrador</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($db_created && $admin_created): ?>
                <div class="success-message">
                    <div class="alert alert-success">
                        <h4>¡Instalación completada con éxito!</h4>
                        <p>La base de datos ha sido configurada y se ha creado el usuario administrador.</p>
                    </div>
                    <p>Ahora puede acceder al panel de administración con las credenciales que ha proporcionado.</p>
                    <div class="d-grid gap-2">
                        <a href="admin/login.php" class="btn btn-primary">Ir al panel de administración</a>
                        <a href="index.php" class="btn btn-outline-secondary">Ir a la página principal</a>
                    </div>
                </div>
            <?php elseif ($db_created): ?>
                <div class="alert alert-success">
                    <strong>Base de datos creada:</strong> Las tablas han sido configuradas correctamente.
                </div>
                <form method="post" action="">
                    <h3 class="mb-3">Crear Usuario Administrador</h3>
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                    </div>
                    <input type="hidden" name="create_admin" value="1">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Crear Administrador</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" action="">
                    <p>Este asistente configurará la base de datos y creará las tablas necesarias para el blog.</p>
                    <div class="alert alert-info">
                        <strong>Nota:</strong> Asegúrese de que la configuración de la base de datos en el archivo <code>config.php</code> sea correcta antes de continuar.
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Configurar Base de Datos</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>