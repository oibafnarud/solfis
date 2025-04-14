<?php
// Inicializar sesión
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Instanciar clases necesarias
$candidateManager = new CandidateManager();
$categoryManager = new CategoryManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Si no existe el candidato, cerrar sesión
if (!$candidato) {
    session_destroy();
    header('Location: login.php?error=candidato_no_encontrado');
    exit;
}

// Obtener categorías para el formulario
$categorias = $categoryManager->getCategories();

// Procesar formulario de actualización
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validar datos requeridos
    $required_fields = ['nombre', 'apellido', 'email', 'telefono'];
    $formData = $_POST;
    $is_valid = true;
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $is_valid = false;
            $error = 'Por favor complete todos los campos obligatorios.';
            break;
        }
    }
    
    // Validar email
    if ($is_valid && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $is_valid = false;
        $error = 'Por favor ingrese un email válido.';
    }
    
    // Verificar si el email ya existe para otro candidato
    if ($is_valid && $_POST['email'] !== $candidato['email']) {
        $email = $_POST['email'];
        
        // Obtener instancia de base de datos
        $db = Database::getInstance();
        $email_safe = $db->escape($email);
        
        $checkSql = "SELECT id FROM candidatos WHERE email = '$email_safe' AND id != $candidato_id";
        $checkResult = $db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $is_valid = false;
            $error = 'Este email ya está siendo utilizado por otro candidato.';
        }
    }
    
    // Validar foto (si se proporcionó)
    $foto_filename = $candidato['foto_path']; // Mantener la foto actual por defecto
    
    if ($is_valid && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $is_valid = false;
            $error = 'Error al cargar la foto.';
        } else {
            // Verificar tipo de archivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = $_FILES['foto']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $is_valid = false;
                $error = 'Solo se permiten archivos JPG o PNG para la foto.';
            }
            
            // Verificar tamaño (max 2MB)
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($_FILES['foto']['size'] > $max_size) {
                $is_valid = false;
                $error = 'La foto excede el tamaño máximo permitido (2MB).';
            }
            
            // Procesar foto si todo está bien
            if ($is_valid) {
                $tmp_name = $_FILES['foto']['tmp_name'];
                $name = basename($_FILES['foto']['name']);
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                
                // Generar nombre único
                $foto_filename = 'profile_' . uniqid() . '.' . $extension;
                
                // Asegurarse de que el directorio existe
                $upload_dir = __DIR__ . '/../uploads/profile_photos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Mover archivo
                move_uploaded_file($tmp_name, $upload_dir . $foto_filename);
            }
        }
    }
    
    // Si todo está correcto, actualizar el candidato
    if ($is_valid) {
        // Preparar áreas de interés
        $areas_interes = isset($_POST['areas_interes']) ? implode(',', $_POST['areas_interes']) : '';
        
        // Datos del candidato
        $candidatoData = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'email' => $_POST['email'],
            'telefono' => $_POST['telefono'],
            'ubicacion' => $_POST['ubicacion'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'genero' => $_POST['genero'] ?? '',
            'foto_path' => $foto_filename,
            'linkedin' => $_POST['linkedin'] ?? '',
            'nivel_educativo' => $_POST['nivel_educativo'] ?? '',
            'areas_interes' => $areas_interes,
            'habilidades_destacadas' => $_POST['habilidades_destacadas'] ?? '',
            'experiencia_general' => $_POST['experiencia_general'] ?? '',
            'salario_esperado' => $_POST['salario_esperado'] ?? '',
            'modalidad_preferida' => $_POST['modalidad_preferida'] ?? '',
            'tipo_contrato_preferido' => $_POST['tipo_contrato_preferido'] ?? '',
            'disponibilidad' => $_POST['disponibilidad'] ?? '',
            'recibir_notificaciones' => !empty($_POST['subscribe']) ? 1 : 0
        ];
        
        // Actualizar candidato
        $updateResult = $candidateManager->updateCandidate($candidato_id, $candidatoData);
        
        if ($updateResult['success']) {
            $success = true;
            // Recargar datos del candidato
            $candidato = $candidateManager->getCandidateById($candidato_id);
        } else {
            $error = 'Error al actualizar el perfil: ' . $updateResult['message'];
        }
    }
}

// Preparar datos para el formulario
$areas_seleccionadas = !empty($candidato['areas_interes']) ? explode(',', $candidato['areas_interes']) : [];

// Variables para la página
$site_title = "Mi Perfil - Portal de Candidatos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    
    <!-- CSS para el perfil -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0088cc;
            --accent-color: #ff9900;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #333333;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            display: flex;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        
        .dashboard-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .content-header h1 {
            font-size: 24px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .btn-primary {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        
        .btn-primary i {
            margin-right: 5px;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            padding: 8px 15px;
            border: 1px solid var(--secondary-color);
            border-radius: 5px;
            background-color: transparent;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-outline i {
            margin-right: 5px;
        }
        
        .btn-outline:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        /* Profile Styles */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            position: sticky;
            top: 80px;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid var(--light-gray);
        }
        
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-photo i {
            font-size: 5em;
            color: var(--medium-gray);
        }
        
        .profile-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-info h2 {
            margin: 0 0 5px;
            color: var(--primary-color);
            font-size: 22px;
        }
        
        .profile-info p {
            margin: 0 0 5px;
            color: #666;
        }
        
        .profile-social {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--light-gray);
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-link:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .profile-nav {
            margin-top: 20px;
        }
        
        .profile-nav-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            color: var(--dark-gray);
            text-decoration: none;
            border-bottom: 1px solid var(--light-gray);
            transition: color 0.3s;
        }
        
        .profile-nav-item:hover {
            color: var(--secondary-color);
        }
        
        .profile-nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .profile-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-section {
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .form-section h3 i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--medium-gray);
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.1);
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .photo-upload-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-upload-info {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .areas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .required-mark {
            color: var(--danger-color);
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: flex-start;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
            margin-top: 2px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: static;
            }
            
            .dashboard-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                margin-left: 0;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .areas-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar-fix.php'; ?>
        
        <main class="dashboard-content">
            <div class="content-header">
                <h1>Mi Perfil</h1>
                <a href="panel.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>¡Perfil actualizado con éxito!</h3>
                    <p>Tus datos han sido actualizados correctamente.</p>
                </div>
            </div>
            <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <h3>Ha ocurrido un error</h3>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="profile-container">
                <aside class="profile-sidebar">
                    <div class="profile-photo">
                        <?php if (!empty($candidato['foto_path'])): ?>
                        <img src="../uploads/profile_photos/<?php echo $candidato['foto_path']; ?>" alt="<?php echo $candidato['nombre']; ?>">
                        <?php else: ?>
                        <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-info">
                        <h2><?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?></h2>
                        <p><?php echo $candidato['email']; ?></p>
                        <?php if (!empty($candidato['telefono'])): ?>
                        <p><?php echo $candidato['telefono']; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($candidato['ubicacion'])): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $candidato['ubicacion']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-social">
                        <?php if (!empty($candidato['linkedin'])): ?>
                        <a href="<?php echo $candidato['linkedin']; ?>" class="social-link" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-nav">
                        <a href="#personal-info" class="profile-nav-item">
                            <i class="fas fa-user"></i> Información Personal
                        </a>
                        <a href="#professional-info" class="profile-nav-item">
                            <i class="fas fa-briefcase"></i> Información Profesional
                        </a>
                        <a href="#preferences" class="profile-nav-item">
                            <i class="fas fa-cog"></i> Preferencias Laborales
                        </a>
                        <a href="#notifications" class="profile-nav-item">
                            <i class="fas fa-bell"></i> Notificaciones
                        </a>
                    </div>
                </aside>
                
                <div class="profile-content">
                    <form class="profile-form" action="profile.php" method="POST" enctype="multipart/form-data">
                        <div id="personal-info" class="form-section">
                            <h3><i class="fas fa-user"></i> Información Personal</h3>
                            
                            <div class="photo-upload-container">
                                <div class="photo-preview" id="photoPreview">
                                    <?php if (!empty($candidato['foto_path'])): ?>
                                    <img src="../uploads/profile_photos/<?php echo $candidato['foto_path']; ?>" alt="<?php echo $candidato['nombre']; ?>" id="previewImage">
                                    <?php else: ?>
                                    <i class="fas fa-user fa-2x" id="defaultIcon"></i>
                                    <img src="" alt="Vista previa" id="previewImage" style="display: none;">
                                    <?php endif; ?>
                                </div>
                                <div class="photo-upload-info">
                                    <label for="foto" class="form-label">Foto de Perfil</label>
                                    <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" class="form-control">
                                    <div class="form-text">Sube una foto profesional. Formatos: JPG, JPEG, PNG (Máx: 2MB)</div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre" class="form-label">Nombre <span class="required-mark">*</span></label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($candidato['nombre']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="apellido" class="form-label">Apellido <span class="required-mark">*</span></label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($candidato['apellido']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($candidato['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono" class="form-label">Teléfono <span class="required-mark">*</span></label>
                                    <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($candidato['telefono']); ?>" placeholder="Ej: +18091234567" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" value="<?php echo htmlspecialchars($candidato['fecha_nacimiento'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="genero" class="form-label">Género</label>
                                    <select id="genero" name="genero" class="form-control">
                                        <option value="" <?php echo (!isset($candidato['genero']) || $candidato['genero'] === '') ? 'selected' : ''; ?>>Prefiero no especificar</option>
                                        <option value="masculino" <?php echo (isset($candidato['genero']) && $candidato['genero'] === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="femenino" <?php echo (isset($candidato['genero']) && $candidato['genero'] === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="otro" <?php echo (isset($candidato['genero']) && $candidato['genero'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ubicacion" class="form-label">Ubicación <span class="required-mark">*</span></label>
                                <input type="text" id="ubicacion" name="ubicacion" class="form-control" value="<?php echo htmlspecialchars($candidato['ubicacion'] ?? ''); ?>" placeholder="Ej: Santo Domingo, República Dominicana" required>
                            </div>
                        </div>
                        
                        <div id="professional-info" class="form-section">
                            <h3><i class="fas fa-briefcase"></i> Información Profesional</h3>
                            
                            <div class="form-group">
                                <label for="linkedin" class="form-label">Perfil de LinkedIn</label>
                                <input type="url" id="linkedin" name="linkedin" class="form-control" value="<?php echo htmlspecialchars($candidato['linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/in/tu-perfil">
                            </div>
                            
                            <div class="form-group">
                                <label for="nivel_educativo" class="form-label">Nivel Educativo <span class="required-mark">*</span></label>
                                <select id="nivel_educativo" name="nivel_educativo" class="form-control" required>
                                    <option value="" <?php echo (!isset($candidato['nivel_educativo']) || $candidato['nivel_educativo'] === '') ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="bachiller" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'bachiller') ? 'selected' : ''; ?>>Bachiller</option>
                                    <option value="tecnico" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'tecnico') ? 'selected' : ''; ?>>Técnico</option>
                                    <option value="grado" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'grado') ? 'selected' : ''; ?>>Grado Universitario</option>
                                    <option value="postgrado" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'postgrado') ? 'selected' : ''; ?>>Postgrado</option>
                                    <option value="maestria" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'maestria') ? 'selected' : ''; ?>>Maestría</option>
                                    <option value="doctorado" <?php echo (isset($candidato['nivel_educativo']) && $candidato['nivel_educativo'] === 'doctorado') ? 'selected' : ''; ?>>Doctorado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Áreas de Interés <span class="required-mark">*</span></label>
                                <div class="areas-grid">
                                    <?php foreach ($categorias as $categoria): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="area_<?php echo $categoria['id']; ?>" name="areas_interes[]" value="<?php echo $categoria['id']; ?>" <?php echo in_array($categoria['id'], $areas_seleccionadas) ? 'checked' : ''; ?>>
                                        <label for="area_<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="experiencia_general" class="form-label">Años de Experiencia General <span class="required-mark">*</span></label>
                                <select id="experiencia_general" name="experiencia_general" class="form-control" required>
                                    <option value="" <?php echo (!isset($candidato['experiencia_general']) || $candidato['experiencia_general'] === '') ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="sin-experiencia" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === 'sin-experiencia') ? 'selected' : ''; ?>>Sin experiencia</option>
                                    <option value="menos-1" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === 'menos-1') ? 'selected' : ''; ?>>Menos de 1 año</option>
                                    <option value="1-3" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === '1-3') ? 'selected' : ''; ?>>1-3 años</option>
                                    <option value="3-5" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === '3-5') ? 'selected' : ''; ?>>3-5 años</option>
                                    <option value="5-10" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === '5-10') ? 'selected' : ''; ?>>5-10 años</option>
                                    <option value="mas-10" <?php echo (isset($candidato['experiencia_general']) && $candidato['experiencia_general'] === 'mas-10') ? 'selected' : ''; ?>>Más de 10 años</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="habilidades_destacadas" class="form-label">Habilidades Destacadas <span class="required-mark">*</span></label>
                                <textarea id="habilidades_destacadas" name="habilidades_destacadas" class="form-control" rows="3" placeholder="Enumera tus principales habilidades y competencias profesionales (separadas por comas)" required><?php echo htmlspecialchars($candidato['habilidades_destacadas'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div id="preferences" class="form-section">
                            <h3><i class="fas fa-cog"></i> Preferencias Laborales</h3>
                            
                            <div class="form-group">
                                <label for="salario_esperado" class="form-label">Expectativa salarial (RD$)</label>
                                <input type="text" id="salario_esperado" name="salario_esperado" class="form-control" value="<?php echo htmlspecialchars($candidato['salario_esperado'] ?? ''); ?>" placeholder="Ej: RD$ 60,000 mensuales">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="modalidad_preferida" class="form-label">Modalidad preferida</label>
                                    <select id="modalidad_preferida" name="modalidad_preferida" class="form-control">
                                        <option value="" <?php echo (!isset($candidato['modalidad_preferida']) || $candidato['modalidad_preferida'] === '') ? 'selected' : ''; ?>>Selecciona una opción</option>
                                        <option value="presencial" <?php echo (isset($candidato['modalidad_preferida']) && $candidato['modalidad_preferida'] === 'presencial') ? 'selected' : ''; ?>>Presencial</option>
                                        <option value="remoto" <?php echo (isset($candidato['modalidad_preferida']) && $candidato['modalidad_preferida'] === 'remoto') ? 'selected' : ''; ?>>Remoto</option>
                                        <option value="hibrido" <?php echo (isset($candidato['modalidad_preferida']) && $candidato['modalidad_preferida'] === 'hibrido') ? 'selected' : ''; ?>>Híbrido</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tipo_contrato_preferido" class="form-label">Tipo de contrato preferido</label>
                                    <select id="tipo_contrato_preferido" name="tipo_contrato_preferido" class="form-control">
                                        <option value="" <?php echo (!isset($candidato['tipo_contrato_preferido']) || $candidato['tipo_contrato_preferido'] === '') ? 'selected' : ''; ?>>Selecciona una opción</option>
                                        <option value="tiempo_completo" <?php echo (isset($candidato['tipo_contrato_preferido']) && $candidato['tipo_contrato_preferido'] === 'tiempo_completo') ? 'selected' : ''; ?>>Tiempo Completo</option>
                                        <option value="tiempo_parcial" <?php echo (isset($candidato['tipo_contrato_preferido']) && $candidato['tipo_contrato_preferido'] === 'tiempo_parcial') ? 'selected' : ''; ?>>Tiempo Parcial</option>
                                        <option value="proyecto" <?php echo (isset($candidato['tipo_contrato_preferido']) && $candidato['tipo_contrato_preferido'] === 'proyecto') ? 'selected' : ''; ?>>Por Proyecto</option>
                                        <option value="temporal" <?php echo (isset($candidato['tipo_contrato_preferido']) && $candidato['tipo_contrato_preferido'] === 'temporal') ? 'selected' : ''; ?>>Temporal</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="disponibilidad" class="form-label">Disponibilidad para comenzar</label>
                                <select id="disponibilidad" name="disponibilidad" class="form-control">
                                    <option value="" <?php echo (!isset($candidato['disponibilidad']) || $candidato['disponibilidad'] === '') ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="inmediata" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === 'inmediata') ? 'selected' : ''; ?>>Inmediata</option>
                                    <option value="2-semanas" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === '2-semanas') ? 'selected' : ''; ?>>2 semanas</option>
                                    <option value="1-mes" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === '1-mes') ? 'selected' : ''; ?>>1 mes</option>
                                    <option value="mas-1-mes" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === 'mas-1-mes') ? 'selected' : ''; ?>>Más de 1 mes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="notifications" class="form-section">
                            <h3><i class="fas fa-bell"></i> Notificaciones</h3>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="subscribe" name="subscribe" value="1" <?php echo (!empty($candidato['recibir_notificaciones'])) ? 'checked' : ''; ?>>
                                    <label for="subscribe">Recibir notificaciones de nuevas vacantes y oportunidades profesionales</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <input type="hidden" name="update_profile" value="1">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Vista previa de foto
        document.getElementById('foto')?.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImage');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    const defaultIcon = document.getElementById('defaultIcon');
                    if (defaultIcon) {
                        defaultIcon.style.display = 'none';
                    }
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Navegación interna suave
        document.querySelectorAll('.profile-nav-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>