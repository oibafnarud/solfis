<?php
$site_title = "Registro de Candidato - SolFis";
$site_description = "Crea tu perfil de candidato en SolFis y accede a todas nuestras vacantes y evaluaciones.";
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir el sistema de vacantes
require_once __DIR__ . '/includes/jobs-system.php';

// Instanciar gestores
$categoryManager = new CategoryManager();

// Obtener categorías para el formulario
$categorias = $categoryManager->getCategories();

// Procesar formulario
$success = false;
$error = '';
$errors = []; // Array para errores por campo
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $formData = $_POST;
    $is_valid = true;
    
    // Validar datos requeridos
    $required_fields = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'email' => 'Email',
        'telefono' => 'Teléfono',
        'password' => 'Contraseña',
        'confirm_password' => 'Confirmar Contraseña',
        'nivel_educativo' => 'Nivel Educativo',
        'experiencia_general' => 'Experiencia General'
    ];
    
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $is_valid = false;
            $errors[$field] = "El campo $label es obligatorio.";
        }
    }
    
    // Validar email
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $is_valid = false;
        $errors['email'] = 'Por favor ingrese un email válido.';
    }
    
    // Validar teléfono (formato básico, ajustar según necesidades locales)
    if (!empty($_POST['telefono']) && !preg_match('/^\+?[0-9]{8,15}$/', $_POST['telefono'])) {
        $is_valid = false;
        $errors['telefono'] = 'Por favor ingrese un número de teléfono válido.';
    }
    
    // Validar contraseñas
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $is_valid = false;
            $errors['confirm_password'] = 'Las contraseñas no coinciden.';
        } elseif (strlen($_POST['password']) < 8) {
            $is_valid = false;
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/[A-Za-z]/', $_POST['password']) || !preg_match('/[0-9]/', $_POST['password'])) {
            $is_valid = false;
            $errors['password'] = 'La contraseña debe incluir al menos una letra y un número.';
        }
    }
    
    // Validar fecha de nacimiento
    if (!empty($_POST['fecha_nacimiento'])) {
        $fecha_nacimiento = new DateTime($_POST['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nacimiento)->y;
        
        if ($edad < 18) {
            $is_valid = false;
            $errors['fecha_nacimiento'] = 'Debes ser mayor de 18 años para registrarte.';
        } elseif ($edad > 120) {
            $is_valid = false;
            $errors['fecha_nacimiento'] = 'Por favor ingrese una fecha de nacimiento válida.';
        }
    }
    
    // Validar foto (si se proporcionó)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $is_valid = false;
            $errors['foto'] = 'Error al cargar la foto.';
        } else {
            // Verificar tipo de archivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = $_FILES['foto']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $is_valid = false;
                $errors['foto'] = 'Solo se permiten archivos JPG o PNG para la foto.';
            }
            
            // Verificar tamaño (max 2MB)
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($_FILES['foto']['size'] > $max_size) {
                $is_valid = false;
                $errors['foto'] = 'La foto excede el tamaño máximo permitido (2MB).';
            }
        }
    }
    
    // Validar áreas de interés
    if (empty($_POST['areas_interes'])) {
        $is_valid = false;
        $errors['areas_interes'] = 'Por favor seleccione al menos un área de interés.';
    }
    
    // Validar habilidades destacadas
    if (empty($_POST['habilidades_destacadas'])) {
        $is_valid = false;
        $errors['habilidades_destacadas'] = 'Por favor ingrese al menos algunas habilidades destacadas.';
    }
    
    // Validar términos y condiciones
    if (empty($_POST['terminos'])) {
        $is_valid = false;
        $errors['terminos'] = 'Debe aceptar los términos y condiciones.';
    }
    
    // Si hay errores, mostrar mensaje general
    if (!$is_valid) {
        $error = 'Por favor corrija los errores en el formulario.';
    }
    
    // Si todo está correcto, crear el candidato
    if ($is_valid) {
        try {
            // Instanciar gestor de candidatos
            $candidateManager = new CandidateManager();
            
            // Verificar si el email ya existe - usando la base de datos directamente
            $email = $_POST['email'];
            $db = Database::getInstance(); // O VacanciesDatabase::getInstance()
            $email_escaped = $db->escape($email);
            $checkSql = "SELECT * FROM candidatos WHERE email = '$email_escaped' LIMIT 1";
            $checkResult = $db->query($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $is_valid = false;
                $errors['email'] = 'Este email ya está registrado. Si ya tienes una cuenta, por favor inicia sesión.';
                $error = 'Este email ya está registrado. Si ya tienes una cuenta, por favor inicia sesión.';
            } else {
                // Procesar foto
                $foto_filename = '';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['foto']['tmp_name'];
                    $name = basename($_FILES['foto']['name']);
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    
                    // Generar nombre único
                    $foto_filename = 'profile_' . uniqid() . '.' . $extension;
                    
                    // Asegurarse de que el directorio existe
                    $upload_dir = __DIR__ . '/uploads/profile_photos/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Mover archivo
                    move_uploaded_file($tmp_name, $upload_dir . $foto_filename);
                }
                
                // Datos del candidato
                $candidatoData = [
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'email' => $_POST['email'],
                    'telefono' => $_POST['telefono'],
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'ubicacion' => $_POST['ubicacion'] ?? '',
                    'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                    'genero' => $_POST['genero'] ?? '',
                    'foto_path' => $foto_filename,
                    'linkedin' => $_POST['linkedin'] ?? '',
                    'nivel_educativo' => $_POST['nivel_educativo'] ?? '',
                    'areas_interes' => isset($_POST['areas_interes']) ? implode(',', $_POST['areas_interes']) : '',
                    'habilidades_destacadas' => $_POST['habilidades_destacadas'] ?? '',
                    'experiencia_general' => $_POST['experiencia_general'] ?? '',
                    'salario_esperado' => $_POST['salario_esperado'] ?? '',
                    'modalidad_preferida' => $_POST['modalidad_preferida'] ?? '',
                    'tipo_contrato_preferido' => $_POST['tipo_contrato_preferido'] ?? '',
                    'disponibilidad' => $_POST['disponibilidad'] ?? '',
                    'evaluaciones_pendientes' => 1, // Marcar que tiene evaluaciones pendientes
                    'recibir_notificaciones' => !empty($_POST['subscribe']) ? 1 : 0
                ];
                
                // Crear candidato
                $createResult = $candidateManager->createCandidate($candidatoData);
                
                if (!$createResult['success']) {
                    $is_valid = false;
                    $error = 'Error al crear el perfil. ' . ($createResult['message'] ?? '');
                } else {
                    $candidato_id = $createResult['id'];
                    $success = true;
                    
                    // Enviar email de confirmación
                    $to = $_POST['email'];
                    $subject = "Bienvenido a SolFis Talentos";
                    $message = "Hola " . $_POST['nombre'] . ",\n\n";
                    $message .= "Gracias por registrarte en SolFis Talentos. Tu cuenta ha sido creada exitosamente.\n\n";
                    $message .= "Puedes iniciar sesión usando los siguientes datos:\n";
                    $message .= "Email: " . $_POST['email'] . "\n";
                    $message .= "Contraseña: La que estableciste al registrarte\n\n";
                    $message .= "Te invitamos a completar nuestras evaluaciones psicométricas para que podamos conocer mejor tus talentos y habilidades.\n\n";
                    $message .= "Accede a tu panel aquí: https://solfis.com.do/candidato/login.php\n\n";
                    $message .= "Atentamente,\n";
                    $message .= "El equipo de Recursos Humanos de SolFis";
                    
                    $headers = "From: rrhh@solfis.com.do";
                    
                    // Enviar email (en producción)
                    // mail($to, $subject, $message, $headers);
                    
                    // Si está disponible, usar la clase EmailSender
                    if (class_exists('EmailSender')) {
                        $emailSender = new EmailSender();
                        $emailSender->sendCredentials($candidatoData, $_POST['password']);
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Error al procesar el registro: ' . $e->getMessage();
        }
    }
}

// Función auxiliar para mostrar errores de campo
function showError($field, $errors) {
    if (isset($errors[$field])) {
        return '<div class="form-error">' . $errors[$field] . '</div>';
    }
    return '';
}

// Función auxiliar para marcar campos con error
function hasError($field, $errors) {
    return isset($errors[$field]) ? 'has-error' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-base.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-aplicar.css">
    <style>
        /* Estilos específicos para el registro de candidatos */
        .registration-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .registration-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section.bordered {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .form-section h3 {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }
        
        .steps-container::after {
            content: "";
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            width: 25%;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f8f8f8;
            border: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            color: #777;
            transition: all 0.3s ease;
        }
        
        .step.active .step-number {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: #4caf50;
            border-color: #4caf50;
            color: white;
        }
        
        .photo-upload-container {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 2rem;
            background-color: #f8f8f8;
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
        
        .areas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .evaluation-teaser {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .evaluation-teaser h4 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        /* Estilos para validación */
        .form-error {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .form-control.has-error {
            border-color: #dc3545;
        }
        
        .form-control.is-valid {
            border-color: #28a745;
        }
        
        .required-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-left: 3px solid #6c757d;
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <section class="job-application">
            <div class="container">
                <div class="breadcrumbs" data-aos="fade-up">
                    <a href="index.php">Inicio</a> <span class="separator">/</span>
                    <a href="vacantes/index.php">Vacantes</a> <span class="separator">/</span>
                    <span class="current">Registro de Candidato</span>
                </div>
                
                <div class="registration-header" data-aos="fade-up">
                    <h1>Crea tu Perfil de Candidato</h1>
                    <p>Regístrate para acceder a todas nuestras vacantes y realizar nuestras evaluaciones psicométricas.</p>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>¡Registro completado con éxito!</h3>
                        <p>Gracias por registrarte en nuestra plataforma de talentos. Hemos creado tu perfil de candidato.</p>
                        <p>Te hemos enviado un correo electrónico con tus credenciales de acceso y próximos pasos.</p>
                        <div class="alert-actions">
                            <a href="candidato/login.php" class="btn-primary">Iniciar Sesión</a>
                            <a href="vacantes/index.php" class="btn-secondary">Ver Vacantes</a>
                        </div>
                    </div>
                </div>
                
                <div class="evaluation-teaser" data-aos="fade-up">
                    <h4><i class="fas fa-star"></i> ¡Completa nuestras evaluaciones psicométricas!</h4>
                    <p>Ahora puedes acceder a tu panel de candidato y realizar nuestras evaluaciones psicométricas para mostrar tus talentos y habilidades. Los reclutadores dan prioridad a los candidatos que han completado estas evaluaciones.</p>
                    <a href="candidato/login.php" class="btn-primary">Iniciar Sesión y Realizar Evaluaciones</a>
                </div>
                <?php elseif ($error): ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Ha ocurrido un error</h3>
                        <p><?php echo $error; ?></p>
                        <p>Por favor revisa los campos marcados en rojo e intenta nuevamente.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <div class="steps-container" data-aos="fade-up">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-title">Información Personal</div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-title">Perfil Profesional</div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-title">Preferencias</div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-title">Evaluaciones</div>
                    </div>
                </div>
                
                <div class="required-info" data-aos="fade-up">
                    <p><i class="fas fa-info-circle"></i> Los campos marcados con <span class="required-mark">*</span> son obligatorios. Un perfil completo aumenta tus posibilidades de ser contactado para oportunidades laborales.</p>
                </div>
                
                <form action="registro-candidato.php" method="POST" enctype="multipart/form-data" class="registration-form" id="registration-form" data-aos="fade-up">
                    <!-- Sección 1: Información Personal -->
                    <div class="form-section bordered">
                        <h3>Información Personal</h3>
                        
                        <div class="photo-upload-container">
                            <div class="photo-preview" id="photoPreview">
                                <i class="fas fa-user fa-2x" id="defaultIcon"></i>
                                <img src="" alt="Vista previa" id="previewImage" style="display: none;">
                            </div>
                            <div class="photo-upload-info">
                                <label for="foto" class="form-label">Foto de Perfil</label>
                                <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" class="form-control <?php echo hasError('foto', $errors); ?>">
                                <div class="form-text">Sube una foto profesional. Formatos: JPG, JPEG, PNG (Máx: 2MB)</div>
                                <?php echo showError('foto', $errors); ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre <span class="required-mark">*</span></label>
                                <input type="text" id="nombre" name="nombre" class="form-control <?php echo hasError('nombre', $errors); ?>" value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>" required>
                                <?php echo showError('nombre', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellido" class="form-label">Apellido <span class="required-mark">*</span></label>
                                <input type="text" id="apellido" name="apellido" class="form-control <?php echo hasError('apellido', $errors); ?>" value="<?php echo htmlspecialchars($formData['apellido'] ?? ''); ?>" required>
                                <?php echo showError('apellido', $errors); ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                                <input type="email" id="email" name="email" class="form-control <?php echo hasError('email', $errors); ?>" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                <?php echo showError('email', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono" class="form-label">Teléfono <span class="required-mark">*</span></label>
                                <input type="tel" id="telefono" name="telefono" class="form-control <?php echo hasError('telefono', $errors); ?>" value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>" placeholder="Ej: +18091234567" required>
                                <?php echo showError('telefono', $errors); ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="required-mark">*</span></label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control <?php echo hasError('fecha_nacimiento', $errors); ?>" value="<?php echo htmlspecialchars($formData['fecha_nacimiento'] ?? ''); ?>" required>
                                <?php echo showError('fecha_nacimiento', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="genero" class="form-label">Género</label>
                                <select id="genero" name="genero" class="form-control">
                                    <option value="" <?php echo !isset($formData['genero']) || $formData['genero'] === '' ? 'selected' : ''; ?>>Prefiero no especificar</option>
                                    <option value="masculino" <?php echo isset($formData['genero']) && $formData['genero'] === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="femenino" <?php echo isset($formData['genero']) && $formData['genero'] === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="otro" <?php echo isset($formData['genero']) && $formData['genero'] === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="ubicacion" class="form-label">Ubicación <span class="required-mark">*</span></label>
                            <input type="text" id="ubicacion" name="ubicacion" class="form-control <?php echo hasError('ubicacion', $errors); ?>" value="<?php echo htmlspecialchars($formData['ubicacion'] ?? ''); ?>" placeholder="Ej: Santo Domingo, República Dominicana" required>
                            <?php echo showError('ubicacion', $errors); ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label">Contraseña <span class="required-mark">*</span></label>
                                <input type="password" id="password" name="password" class="form-control <?php echo hasError('password', $errors); ?>" required>
                                <div class="form-text">Mínimo 8 caracteres, debe incluir letras y números</div>
                                <?php echo showError('password', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="required-mark">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control <?php echo hasError('confirm_password', $errors); ?>" required>
                                <?php echo showError('confirm_password', $errors); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección 2: Perfil Profesional -->
                    <div class="form-section bordered">
                        <h3>Perfil Profesional</h3>
                        
                        <div class="form-group">
                            <label for="linkedin" class="form-label">Perfil de LinkedIn</label>
                            <input type="url" id="linkedin" name="linkedin" class="form-control <?php echo hasError('linkedin', $errors); ?>" value="<?php echo htmlspecialchars($formData['linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/in/tu-perfil">
                            <?php echo showError('linkedin', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="nivel_educativo" class="form-label">Nivel Educativo <span class="required-mark">*</span></label>
                            <select id="nivel_educativo" name="nivel_educativo" class="form-control <?php echo hasError('nivel_educativo', $errors); ?>" required>
                                <option value="" <?php echo !isset($formData['nivel_educativo']) || $formData['nivel_educativo'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                <option value="bachiller" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'bachiller' ? 'selected' : ''; ?>>Bachiller</option>
                                <option value="tecnico" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                                <option value="grado" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'grado' ? 'selected' : ''; ?>>Grado Universitario</option>
                                <option value="postgrado" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'postgrado' ? 'selected' : ''; ?>>Postgrado</option>
                                <option value="maestria" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'maestria' ? 'selected' : ''; ?>>Maestría</option>
                                <option value="doctorado" <?php echo isset($formData['nivel_educativo']) && $formData['nivel_educativo'] === 'doctorado' ? 'selected' : ''; ?>>Doctorado</option>
                            </select>
                            <?php echo showError('nivel_educativo', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Áreas de Interés <span class="required-mark">*</span></label>
                            <div class="areas-grid <?php echo hasError('areas_interes', $errors); ?>">
                                <?php foreach ($categorias as $categoria): ?>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="area_<?php echo $categoria['id']; ?>" name="areas_interes[]" value="<?php echo $categoria['id']; ?>" <?php echo isset($formData['areas_interes']) && in_array($categoria['id'], (array)$formData['areas_interes']) ? 'checked' : ''; ?>>
                                    <label for="area_<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php echo showError('areas_interes', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="experiencia_general" class="form-label">Años de Experiencia General <span class="required-mark">*</span></label>
                            <select id="experiencia_general" name="experiencia_general" class="form-control <?php echo hasError('experiencia_general', $errors); ?>" required>
                                <option value="" <?php echo !isset($formData['experiencia_general']) || $formData['experiencia_general'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                <option value="sin-experiencia" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === 'sin-experiencia' ? 'selected' : ''; ?>>Sin experiencia</option>
                                <option value="menos-1" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === 'menos-1' ? 'selected' : ''; ?>>Menos de 1 año</option>
                                <option value="1-3" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === '1-3' ? 'selected' : ''; ?>>1-3 años</option>
                                <option value="3-5" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === '3-5' ? 'selected' : ''; ?>>3-5 años</option>
                                <option value="5-10" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === '5-10' ? 'selected' : ''; ?>>5-10 años</option>
                                <option value="mas-10" <?php echo isset($formData['experiencia_general']) && $formData['experiencia_general'] === 'mas-10' ? 'selected' : ''; ?>>Más de 10 años</option>
                            </select>
                            <?php echo showError('experiencia_general', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="habilidades_destacadas" class="form-label">Habilidades Destacadas <span class="required-mark">*</span></label>
                            <textarea id="habilidades_destacadas" name="habilidades_destacadas" class="form-control <?php echo hasError('habilidades_destacadas', $errors); ?>" rows="3" placeholder="Enumera tus principales habilidades y competencias profesionales (separadas por comas)" required><?php echo htmlspecialchars($formData['habilidades_destacadas'] ?? ''); ?></textarea>
                            <?php echo showError('habilidades_destacadas', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="resumen_profesional" class="form-label">Resumen Profesional <span class="required-mark">*</span></label>
                            <textarea id="resumen_profesional" name="resumen_profesional" class="form-control <?php echo hasError('resumen_profesional', $errors); ?>" rows="4" placeholder="Describe brevemente tu trayectoria, experiencia y objetivos profesionales" required><?php echo htmlspecialchars($formData['resumen_profesional'] ?? ''); ?></textarea>
                            <?php echo showError('resumen_profesional', $errors); ?>
                            <div class="form-text">Este resumen será utilizado para presentar tu perfil a potenciales empleadores.</div>
                        </div>
                    </div>
                    
                    <!-- Sección 3: Preferencias Laborales -->
                    <div class="form-section bordered">
                        <h3>Preferencias Laborales</h3>
                        
                        <div class="form-group">
                            <label for="salario_esperado" class="form-label">Expectativa salarial (RD$) <span class="required-mark">*</span></label>
                            <input type="text" id="salario_esperado" name="salario_esperado" class="form-control <?php echo hasError('salario_esperado', $errors); ?>" value="<?php echo htmlspecialchars($formData['salario_esperado'] ?? ''); ?>" placeholder="Ej: RD$ 60,000 mensuales" required>
                            <?php echo showError('salario_esperado', $errors); ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modalidad_preferida" class="form-label">Modalidad preferida <span class="required-mark">*</span></label>
                                <select id="modalidad_preferida" name="modalidad_preferida" class="form-control <?php echo hasError('modalidad_preferida', $errors); ?>" required>
                                    <option value="" <?php echo !isset($formData['modalidad_preferida']) || $formData['modalidad_preferida'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="presencial" <?php echo isset($formData['modalidad_preferida']) && $formData['modalidad_preferida'] === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                                    <option value="remoto" <?php echo isset($formData['modalidad_preferida']) && $formData['modalidad_preferida'] === 'remoto' ? 'selected' : ''; ?>>Remoto</option>
                                    <option value="hibrido" <?php echo isset($formData['modalidad_preferida']) && $formData['modalidad_preferida'] === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                                </select>
                                <?php echo showError('modalidad_preferida', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo_contrato_preferido" class="form-label">Tipo de contrato preferido <span class="required-mark">*</span></label>
                                <select id="tipo_contrato_preferido" name="tipo_contrato_preferido" class="form-control <?php echo hasError('tipo_contrato_preferido', $errors); ?>" required>
                                    <option value="" <?php echo !isset($formData['tipo_contrato_preferido']) || $formData['tipo_contrato_preferido'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="tiempo_completo" <?php echo isset($formData['tipo_contrato_preferido']) && $formData['tipo_contrato_preferido'] === 'tiempo_completo' ? 'selected' : ''; ?>>Tiempo Completo</option>
                                    <option value="tiempo_parcial" <?php echo isset($formData['tipo_contrato_preferido']) && $formData['tipo_contrato_preferido'] === 'tiempo_parcial' ? 'selected' : ''; ?>>Tiempo Parcial</option>
                                    <option value="proyecto" <?php echo isset($formData['tipo_contrato_preferido']) && $formData['tipo_contrato_preferido'] === 'proyecto' ? 'selected' : ''; ?>>Por Proyecto</option>
                                    <option value="temporal" <?php echo isset($formData['tipo_contrato_preferido']) && $formData['tipo_contrato_preferido'] === 'temporal' ? 'selected' : ''; ?>>Temporal</option>
                                </select>
                                <?php echo showError('tipo_contrato_preferido', $errors); ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="disponibilidad" class="form-label">Disponibilidad para comenzar <span class="required-mark">*</span></label>
                                <select id="disponibilidad" name="disponibilidad" class="form-control <?php echo hasError('disponibilidad', $errors); ?>" required>
                                    <option value="" <?php echo !isset($formData['disponibilidad']) || $formData['disponibilidad'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="inmediata" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === 'inmediata' ? 'selected' : ''; ?>>Inmediata</option>
                                    <option value="2-semanas" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === '2-semanas' ? 'selected' : ''; ?>>2 semanas</option>
                                    <option value="1-mes" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === '1-mes' ? 'selected' : ''; ?>>1 mes</option>
                                    <option value="mas-1-mes" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === 'mas-1-mes' ? 'selected' : ''; ?>>Más de 1 mes</option>
                                </select>
                                <?php echo showError('disponibilidad', $errors); ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="disponibilidad_viajar" class="form-label">Disponibilidad para viajar</label>
                                <select id="disponibilidad_viajar" name="disponibilidad_viajar" class="form-control">
                                    <option value="" <?php echo !isset($formData['disponibilidad_viajar']) || $formData['disponibilidad_viajar'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                    <option value="no" <?php echo isset($formData['disponibilidad_viajar']) && $formData['disponibilidad_viajar'] === 'no' ? 'selected' : ''; ?>>No disponible</option>
                                    <option value="ocasional" <?php echo isset($formData['disponibilidad_viajar']) && $formData['disponibilidad_viajar'] === 'ocasional' ? 'selected' : ''; ?>>Ocasionalmente</option>
                                    <option value="frecuente" <?php echo isset($formData['disponibilidad_viajar']) && $formData['disponibilidad_viajar'] === 'frecuente' ? 'selected' : ''; ?>>Frecuentemente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="ubicacion_preferida" class="form-label">Ubicación preferida de trabajo</label>
                            <input type="text" id="ubicacion_preferida" name="ubicacion_preferida" class="form-control" value="<?php echo htmlspecialchars($formData['ubicacion_preferida'] ?? ''); ?>" placeholder="Ej: Santo Domingo Este, Distrito Nacional, etc.">
                            <div class="form-text">Indica la zona o localidad donde prefieres trabajar (si aplica)</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Sectores de interés</label>
                            <div class="areas-grid">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="sector_publico" name="sectores[]" value="publico" <?php echo isset($formData['sectores']) && in_array('publico', (array)$formData['sectores']) ? 'checked' : ''; ?>>
                                    <label for="sector_publico">Sector Público</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="sector_privado" name="sectores[]" value="privado" <?php echo isset($formData['sectores']) && in_array('privado', (array)$formData['sectores']) ? 'checked' : ''; ?>>
                                    <label for="sector_privado">Sector Privado</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="sector_ong" name="sectores[]" value="ong" <?php echo isset($formData['sectores']) && in_array('ong', (array)$formData['sectores']) ? 'checked' : ''; ?>>
                                    <label for="sector_ong">ONG / Sin fines de lucro</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="sector_internacional" name="sectores[]" value="internacional" <?php echo isset($formData['sectores']) && in_array('internacional', (array)$formData['sectores']) ? 'checked' : ''; ?>>
                                    <label for="sector_internacional">Organismos Internacionales</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Evaluaciones y Confirmación -->
                    <div class="form-section bordered">
                        <h3>Evaluaciones Psicométricas</h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p>Como parte de nuestro proceso de selección, te solicitaremos completar las siguientes evaluaciones después del registro:</p>
                                <ul>
                                    <li><strong>Test de personalidad laboral:</strong> Evalúa tus rasgos de personalidad en el entorno laboral.</li>
                                    <li><strong>Evaluación de competencias:</strong> Determina tus habilidades profesionales clave.</li>
                                    <li><strong>Test de razonamiento:</strong> Mide tu capacidad analítica y resolución de problemas.</li>
                                    <li><strong>Evaluación de valores y motivación:</strong> Identifica tus prioridades y factores de motivación profesional.</li>
                                </ul>
                                <p>Estas evaluaciones te tomarán aproximadamente 60-90 minutos en total y te ayudarán a mostrar tus talentos de manera efectiva.</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group <?php echo hasError('terminos', $errors); ?>">
                                <input type="checkbox" id="terminos" name="terminos" value="1" <?php echo isset($formData['terminos']) ? 'checked' : ''; ?> required>
                                <label for="terminos">Acepto los <a href="terminos.php" target="_blank">términos y condiciones</a> y la <a href="privacidad.php" target="_blank">política de privacidad</a> <span class="required-mark">*</span></label>
                            </div>
                            <?php echo showError('terminos', $errors); ?>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="subscribe" name="subscribe" value="1" <?php echo isset($formData['subscribe']) ? 'checked' : ''; ?>>
                                <label for="subscribe">Me gustaría recibir notificaciones de nuevas vacantes y oportunidades profesionales en SolFis</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="compartir_datos" name="compartir_datos" value="1" <?php echo isset($formData['compartir_datos']) ? 'checked' : ''; ?>>
                                <label for="compartir_datos">Autorizo a SolFis a compartir mi perfil profesional con empresas que buscan talento en mi área de especialización</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i> Completar Registro
                        </button>
                        <a href="candidato/login.php" class="btn-secondary">Ya tengo una cuenta</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Vista previa de foto
        document.getElementById('foto')?.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImage');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('defaultIcon').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Validación de formulario en tiempo real
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
        
        // Destacar campos requeridos cuando el usuario hace clic en enviar
        document.getElementById('registration-form')?.addEventListener('submit', function(e) {
            let valid = true;
            
            // Validar todos los campos
            document.querySelectorAll('[required]').forEach(function(field) {
                if (!validateField(field)) {
                    valid = false;
                }
            });
            
            // Validar email
            const emailField = document.getElementById('email');
            if (emailField && emailField.value.trim()) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailField.value.trim())) {
                    valid = false;
                    emailField.classList.add('has-error');
                    // Mostrar mensaje de error
                    let errorDiv = emailField.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('form-error')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        emailField.parentNode.insertBefore(errorDiv, emailField.nextSibling);
                    }
                    errorDiv.textContent = 'Por favor ingrese un email válido';
                }
            }
            
            // Validar contraseñas
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                // Verificar que coinciden
                if (password.value !== confirmPassword.value) {
                    valid = false;
                    confirmPassword.classList.add('has-error');
                    // Mostrar mensaje de error
                    let errorDiv = confirmPassword.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('form-error')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        confirmPassword.parentNode.insertBefore(errorDiv, confirmPassword.nextSibling);
                    }
                    errorDiv.textContent = 'Las contraseñas no coinciden';
                }
                
                // Verificar fortaleza
                if (password.value.length < 8) {
                    valid = false;
                    password.classList.add('has-error');
                    // Mostrar mensaje de error
                    let errorDiv = password.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('form-error')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        password.parentNode.insertBefore(errorDiv, password.nextSibling.nextSibling);
                    }
                    errorDiv.textContent = 'La contraseña debe tener al menos 8 caracteres';
                } else if (!/[A-Za-z]/.test(password.value) || !/[0-9]/.test(password.value)) {
                    valid = false;
                    password.classList.add('has-error');
                    // Mostrar mensaje de error
                    let errorDiv = password.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('form-error')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        password.parentNode.insertBefore(errorDiv, password.nextSibling.nextSibling);
                    }
                    errorDiv.textContent = 'La contraseña debe incluir al menos una letra y un número';
                }
            }
            
            // Validar foto (si se seleccionó)
            const photoField = document.getElementById('foto');
            if (photoField && photoField.files.length > 0) {
                const file = photoField.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!allowedTypes.includes(file.type)) {
                    valid = false;
                    photoField.classList.add('has-error');
                    alert('El formato de la foto no es válido. Por favor, sube un archivo JPG o PNG.');
                } else if (file.size > maxSize) {
                    valid = false;
                    photoField.classList.add('has-error');
                    alert('El tamaño de la foto excede el límite de 2MB.');
                }
            }
            
            // Si hay errores, evitar envío del formulario
            if (!valid) {
                e.preventDefault();
                // Desplazar a la parte superior para ver los errores
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Mostrar mensaje general de error
                if (!document.querySelector('.alert.alert-danger')) {
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger';
                    errorAlert.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <h3>Ha ocurrido un error</h3>
                            <p>Por favor revisa los campos marcados en rojo e intenta nuevamente.</p>
                        </div>
                    `;
                    document.querySelector('.registration-header').insertAdjacentElement('afterend', errorAlert);
                }
            }
        });
        
        // Función para validar un campo individual
        function validateField(field) {
            if (field.hasAttribute('required') && !field.value.trim()) {
                field.classList.add('has-error');
                field.classList.remove('is-valid');
                
                // Mostrar mensaje de error si no existe
                let errorDiv = field.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('form-error')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'form-error';
                    field.parentNode.insertBefore(errorDiv, field.nextSibling);
                }
                errorDiv.textContent = 'Este campo es obligatorio';
                
                return false;
            } else {
                field.classList.remove('has-error');
                field.classList.add('is-valid');
                
                // Eliminar mensaje de error si existe
                const errorDiv = field.nextElementSibling;
                if (errorDiv && errorDiv.classList.contains('form-error')) {
                    errorDiv.remove();
                }
                
                return true;
            }
        }
        
        // Activar el paso del formulario correspondiente según el scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.form-section');
            const steps = document.querySelectorAll('.step');
            
            sections.forEach(function(section, index) {
                const rect = section.getBoundingClientRect();
                
                if (rect.top <= 200 && rect.bottom >= 200) {
                    // Desactivar todos los pasos
                    steps.forEach(step => step.classList.remove('active'));
                    
                    // Activar el paso actual
                    if (steps[index]) {
                        steps[index].classList.add('active');
                    }
                }
            });
        });
    </script>
</body>
</html>