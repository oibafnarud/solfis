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

// Obtener experiencia laboral
$experiencias = [];
if (method_exists($candidateManager, 'getExperienciaLaboral')) {
    $experiencias = $candidateManager->getExperienciaLaboral($candidato_id);
}

// Obtener referencias
$referencias = [];
if (method_exists($candidateManager, 'getReferencias')) {
    $referencias = $candidateManager->getReferencias($candidato_id);
}

// Calcular porcentaje de completitud
$porcentaje_completitud = 0;
if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
}

// Determinar qué secciones están completas
$secciones_completas = [
    'personal' => false,
    'profesional' => false,
    'experiencia' => false,
    'referencias' => false
];

if (method_exists($candidateManager, 'obtenerSeccionesCompletas')) {
    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
}

// Procesar formulario de actualización
$success = false;
$error = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'informacion-personal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar qué formulario se envió
    if (isset($_POST['update_profile'])) {
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
        $foto_filename = $candidato['foto_path'] ?? ''; // Mantener la foto actual por defecto
        
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
                $active_tab = 'informacion-personal';
                // Recargar datos del candidato
                $candidato = $candidateManager->getCandidateById($candidato_id);
                // Recalcular completitud si el método existe
                if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
                }
            } else {
                $error = 'Error al actualizar el perfil: ' . $updateResult['message'];
            }
        }
    }
    elseif (isset($_POST['add_experience']) && method_exists($candidateManager, 'addExperienciaLaboral')) {
        // Validar datos de experiencia laboral
        $required_exp_fields = ['empresa', 'puesto', 'fecha_inicio'];
        $is_valid = true;
        
        foreach ($required_exp_fields as $field) {
            if (empty($_POST[$field])) {
                $is_valid = false;
                $error = 'Por favor complete todos los campos obligatorios de experiencia laboral.';
                break;
            }
        }
        
        if ($is_valid) {
            // Procesar fecha_fin si no es trabajo actual
            $fecha_fin = null;
            $actual = isset($_POST['trabajo_actual']) ? 1 : 0;
            
            if (!$actual && !empty($_POST['fecha_fin'])) {
                $fecha_fin = $_POST['fecha_fin'];
            }
            
            $experienciaData = [
                'candidato_id' => $candidato_id,
                'empresa' => $_POST['empresa'],
                'puesto' => $_POST['puesto'],
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $fecha_fin,
                'actual' => $actual,
                'ubicacion' => $_POST['ubicacion'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'logros' => $_POST['logros'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'razon_salida' => $_POST['razon_salida'] ?? ''
            ];
            
            $result = $candidateManager->addExperienciaLaboral($experienciaData);
            
            if ($result['success']) {
                $success = true;
                $active_tab = 'experiencia-laboral';
                $experiencias = $candidateManager->getExperienciaLaboral($candidato_id);
                
                if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
                }
            } else {
                $error = 'Error al añadir experiencia laboral: ' . $result['message'];
            }
        }
    }
    elseif (isset($_POST['update_experience']) && method_exists($candidateManager, 'updateExperienciaLaboral')) {
        // Validar datos de experiencia laboral
        $required_exp_fields = ['empresa', 'puesto', 'fecha_inicio'];
        $is_valid = true;
        
        foreach ($required_exp_fields as $field) {
            if (empty($_POST[$field])) {
                $is_valid = false;
                $error = 'Por favor complete todos los campos obligatorios de experiencia laboral.';
                break;
            }
        }
        
        if ($is_valid) {
            // Procesar fecha_fin si no es trabajo actual
            $fecha_fin = null;
            $actual = isset($_POST['trabajo_actual']) ? 1 : 0;
            
            if (!$actual && !empty($_POST['fecha_fin'])) {
                $fecha_fin = $_POST['fecha_fin'];
            }
            
            $experienciaData = [
                'id' => $_POST['experiencia_id'],
                'candidato_id' => $candidato_id,
                'empresa' => $_POST['empresa'],
                'puesto' => $_POST['puesto'],
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $fecha_fin,
                'actual' => $actual,
                'ubicacion' => $_POST['ubicacion'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'logros' => $_POST['logros'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'razon_salida' => $_POST['razon_salida'] ?? ''
            ];
            
            $result = $candidateManager->updateExperienciaLaboral($experienciaData);
            
            if ($result['success']) {
                $success = true;
                $active_tab = 'experiencia-laboral';
                $experiencias = $candidateManager->getExperienciaLaboral($candidato_id);
                
                if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
                }
            } else {
                $error = 'Error al actualizar experiencia laboral: ' . $result['message'];
            }
        }
    }
    elseif (isset($_POST['delete_experience']) && method_exists($candidateManager, 'deleteExperienciaLaboral')) {
        $experiencia_id = $_POST['experiencia_id'];
        $result = $candidateManager->deleteExperienciaLaboral($experiencia_id, $candidato_id);
        
        if ($result['success']) {
            $success = true;
            $active_tab = 'experiencia-laboral';
            $experiencias = $candidateManager->getExperienciaLaboral($candidato_id);
            
            if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
            }
        } else {
            $error = 'Error al eliminar experiencia laboral: ' . $result['message'];
        }
    }
    elseif (isset($_POST['add_reference']) && method_exists($candidateManager, 'addReferencia')) {
        // Validar datos de referencia
        $required_ref_fields = ['nombre', 'puesto', 'empresa', 'relacion'];
        $is_valid = true;
        
        foreach ($required_ref_fields as $field) {
            if (empty($_POST[$field])) {
                $is_valid = false;
                $error = 'Por favor complete todos los campos obligatorios de referencia.';
                break;
            }
        }
        
        if ($is_valid) {
            $referenciaData = [
                'candidato_id' => $candidato_id,
                'nombre' => $_POST['nombre'],
                'puesto' => $_POST['puesto'],
                'empresa' => $_POST['empresa'],
                'relacion' => $_POST['relacion'],
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'autoriza_contacto' => isset($_POST['autoriza_contacto']) ? 1 : 0
            ];
            
            $result = $candidateManager->addReferencia($referenciaData);
            
            if ($result['success']) {
                $success = true;
                $active_tab = 'referencias';
                $referencias = $candidateManager->getReferencias($candidato_id);
                
                if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
                }
            } else {
                $error = 'Error al añadir referencia: ' . $result['message'];
            }
        }
    }
    elseif (isset($_POST['update_reference']) && method_exists($candidateManager, 'updateReferencia')) {
        // Validar datos de referencia
        $required_ref_fields = ['nombre', 'puesto', 'empresa', 'relacion'];
        $is_valid = true;
        
        foreach ($required_ref_fields as $field) {
            if (empty($_POST[$field])) {
                $is_valid = false;
                $error = 'Por favor complete todos los campos obligatorios de referencia.';
                break;
            }
        }
        
        if ($is_valid) {
            $referenciaData = [
                'id' => $_POST['referencia_id'],
                'candidato_id' => $candidato_id,
                'nombre' => $_POST['nombre'],
                'puesto' => $_POST['puesto'],
                'empresa' => $_POST['empresa'],
                'relacion' => $_POST['relacion'],
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'autoriza_contacto' => isset($_POST['autoriza_contacto']) ? 1 : 0
            ];
            
            $result = $candidateManager->updateReferencia($referenciaData);
            
            if ($result['success']) {
                $success = true;
                $active_tab = 'referencias';
                $referencias = $candidateManager->getReferencias($candidato_id);
                
                if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                    $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                    $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
                }
            } else {
                $error = 'Error al actualizar referencia: ' . $result['message'];
            }
        }
    }
    elseif (isset($_POST['delete_reference']) && method_exists($candidateManager, 'deleteReferencia')) {
        $referencia_id = $_POST['referencia_id'];
        $result = $candidateManager->deleteReferencia($referencia_id, $candidato_id);
        
        if ($result['success']) {
            $success = true;
            $active_tab = 'referencias';
            $referencias = $candidateManager->getReferencias($candidato_id);
            
            if (method_exists($candidateManager, 'calcularCompletitudPerfil')) {
                $porcentaje_completitud = $candidateManager->calcularCompletitudPerfil($candidato_id);
                $secciones_completas = $candidateManager->obtenerSeccionesCompletas($candidato_id);
            }
        } else {
            $error = 'Error al eliminar referencia: ' . $result['message'];
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
        
        .btn-danger {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background-color: var(--danger-color);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        
        .btn-danger i {
            margin-right: 5px;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
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
        
        .profile-completeness {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .progress-container {
            background-color: var(--light-gray);
            height: 10px;
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 5px;
            background-color: var(--success-color);
            transition: width 0.3s ease;
        }
        
        .progress-text {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .progress-sections {
            margin-top: 15px;
            text-align: left;
        }
        
        .progress-section {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .section-status {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .section-complete {
            color: var(--success-color);
        }
        
        .section-incomplete {
            color: var(--warning-color);
        }
        
        .section-name {
            font-size: 14px;
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
        
        .profile-nav-item:hover, .profile-nav-item.active {
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
        
        .profile-tab {
            display: none;
        }
        
        .profile-tab.active {
            display: block;
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
        
        /* Estilos para experiencia laboral */
        .experiences-list {
            margin-bottom: 20px;
        }
        
        .experience-item {
            background-color: var(--light-gray);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .experience-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .experience-info h4 {
            margin: 0 0 5px;
            color: var(--primary-color);
        }
        
        .experience-company {
            margin: 0 0 5px;
            font-weight: 500;
        }
        
        .experience-dates {
            font-size: 14px;
            color: #666;
        }
        
        .experience-location {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .experience-actions {
            display: flex;
            gap: 10px;
        }
        
        .experience-description {
            margin-top: 10px;
            font-size: 14px;
        }
        
        .action-link {
            color: var(--secondary-color);
            cursor: pointer;
            font-size: 14px;
        }
        
        .action-link i {
            margin-right: 5px;
        }
        
        .action-link.delete {
            color: var(--danger-color);
        }
        
        .add-experience-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--light-gray);
            color: var(--secondary-color);
            border: 1px dashed var(--secondary-color);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            width: 100%;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .add-experience-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .add-experience-btn i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .modal-title {
            font-size: 18px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .close-modal {
            font-size: 24px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: var(--dark-gray);
        }
        
        .modal-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Estilos para referencias */
        .references-list {
            margin-bottom: 20px;
        }
        
        .reference-item {
            background-color: var(--light-gray);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .reference-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .reference-info h4 {
            margin: 0 0 5px;
            color: var(--primary-color);
        }
        
        .reference-position {
            margin: 0 0 5px;
            font-weight: 500;
        }
        
        .reference-company {
            font-size: 14px;
            color: #666;
        }
        
        .reference-contact {
            margin-top: 10px;
            font-size: 14px;
        }
        
        .reference-contact div {
            margin-bottom: 5px;
        }
        
        .reference-actions {
            display: flex;
            gap: 10px;
        }
        
        .reference-relation {
            margin-top: 5px;
            font-size: 14px;
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
        }
        
        .add-reference-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--light-gray);
            color: var(--secondary-color);
            border: 1px dashed var(--secondary-color);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            width: 100%;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .add-reference-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .add-reference-btn i {
            margin-right: 10px;
            font-size: 18px;
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
                    
                    <div class="profile-completeness">
                        <h3>Completitud del perfil</h3>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $porcentaje_completitud; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $porcentaje_completitud; ?>% completado</div>
                        
                        <div class="progress-sections">
                            <div class="progress-section">
                                <span class="section-status <?php echo $secciones_completas['personal'] ? 'section-complete' : 'section-incomplete'; ?>">
                                    <i class="fas <?php echo $secciones_completas['personal'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                </span>
                                <span class="section-name">Información Personal</span>
                            </div>
                            <div class="progress-section">
                                <span class="section-status <?php echo $secciones_completas['profesional'] ? 'section-complete' : 'section-incomplete'; ?>">
                                    <i class="fas <?php echo $secciones_completas['profesional'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                </span>
                                <span class="section-name">Información Profesional</span>
                            </div>
                            <div class="progress-section">
                                <span class="section-status <?php echo $secciones_completas['experiencia'] ? 'section-complete' : 'section-incomplete'; ?>">
                                    <i class="fas <?php echo $secciones_completas['experiencia'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                </span>
                                <span class="section-name">Experiencia Laboral</span>
                            </div>
                            <div class="progress-section">
                                <span class="section-status <?php echo $secciones_completas['referencias'] ? 'section-complete' : 'section-incomplete'; ?>">
                                    <i class="fas <?php echo $secciones_completas['referencias'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                </span>
                                <span class="section-name">Referencias</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-nav">
                        <a href="#informacion-personal" class="profile-nav-item <?php echo $active_tab == 'informacion-personal' ? 'active' : ''; ?>" data-tab="informacion-personal">
                            <i class="fas fa-user"></i> Información Personal
                        </a>
                        <a href="#informacion-profesional" class="profile-nav-item <?php echo $active_tab == 'informacion-profesional' ? 'active' : ''; ?>" data-tab="informacion-profesional">
                            <i class="fas fa-briefcase"></i> Información Profesional
                        </a>
                        <a href="#experiencia-laboral" class="profile-nav-item <?php echo $active_tab == 'experiencia-laboral' ? 'active' : ''; ?>" data-tab="experiencia-laboral">
                            <i class="fas fa-business-time"></i> Experiencia Laboral
                        </a>
                        <a href="#referencias" class="profile-nav-item <?php echo $active_tab == 'referencias' ? 'active' : ''; ?>" data-tab="referencias">
                            <i class="fas fa-address-card"></i> Referencias
                        </a>
                        <a href="#preferencias" class="profile-nav-item <?php echo $active_tab == 'preferencias' ? 'active' : ''; ?>" data-tab="preferencias">
                            <i class="fas fa-cog"></i> Preferencias Laborales
                        </a>
                        <a href="#notificaciones" class="profile-nav-item <?php echo $active_tab == 'notificaciones' ? 'active' : ''; ?>" data-tab="notificaciones">
                            <i class="fas fa-bell"></i> Notificaciones
                        </a>
                    </div>
                </aside>
                
                <div class="profile-content">
                    <!-- Pestaña Información Personal -->
                    <div id="informacion-personal" class="profile-tab <?php echo $active_tab == 'informacion-personal' ? 'active' : ''; ?>">
                        <form class="profile-form" action="profile.php?tab=informacion-personal" method="POST" enctype="multipart/form-data">
                            <div class="form-section">
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
                            
                            <div class="form-buttons">
                                <input type="hidden" name="update_profile" value="1">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Pestaña Información Profesional -->
                    <div id="informacion-profesional" class="profile-tab <?php echo $active_tab == 'informacion-profesional' ? 'active' : ''; ?>">
                        <form class="profile-form" action="profile.php?tab=informacion-profesional" method="POST">
                            <div class="form-section">
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
                            
                            <div class="form-buttons">
                                <input type="hidden" name="update_profile" value="1">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Pestaña Experiencia Laboral -->
                    <div id="experiencia-laboral" class="profile-tab <?php echo $active_tab == 'experiencia-laboral' ? 'active' : ''; ?>">
                        <div class="form-section">
                            <h3><i class="fas fa-business-time"></i> Experiencia Laboral</h3>
                            
                            <?php if (!empty($secciones_completas) && !$secciones_completas['experiencia'] && method_exists($candidateManager, 'getExperienciaLaboral')): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <p>Debes añadir al menos una experiencia laboral relevante para completar tu perfil.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="experiences-list">
                                <?php if (!empty($experiencias)): ?>
                                    <?php foreach ($experiencias as $experiencia): ?>
                                    <div class="experience-item">
                                        <div class="experience-header">
                                            <div class="experience-info">
                                                <h4><?php echo htmlspecialchars($experiencia['puesto']); ?></h4>
                                                <div class="experience-company"><?php echo htmlspecialchars($experiencia['empresa']); ?></div>
                                                <div class="experience-dates">
                                                    <?php 
                                                    echo date('M Y', strtotime($experiencia['fecha_inicio'])); 
                                                    echo ' - ';
                                                    if ($experiencia['actual']) {
                                                        echo 'Actual';
                                                    } else if (!empty($experiencia['fecha_fin'])) {
                                                        echo date('M Y', strtotime($experiencia['fecha_fin']));
                                                    } else {
                                                        echo 'No especificado';
                                                    }
                                                    ?>
                                                </div>
                                                <?php if (!empty($experiencia['ubicacion'])): ?>
                                                <div class="experience-location">
                                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($experiencia['ubicacion']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="experience-actions">
                                                <span class="action-link edit-experience" data-id="<?php echo $experiencia['id']; ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </span>
                                                <span class="action-link delete action-delete-experience" data-id="<?php echo $experiencia['id']; ?>">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($experiencia['descripcion'])): ?>
                                        <div class="experience-description">
                                            <p><?php echo nl2br(htmlspecialchars($experiencia['descripcion'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($experiencia['logros'])): ?>
                                        <div class="experience-description">
                                            <strong>Logros:</strong>
                                            <p><?php echo nl2br(htmlspecialchars($experiencia['logros'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <p>No has agregado ninguna experiencia laboral. Añade tu historial profesional para mejorar tus posibilidades de encontrar empleo.</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="add-experience-btn" id="btnAddExperience">
                                    <i class="fas fa-plus"></i> Añadir experiencia laboral
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Referencias -->
                    <div id="referencias" class="profile-tab <?php echo $active_tab == 'referencias' ? 'active' : ''; ?>">
                        <div class="form-section">
                            <h3><i class="fas fa-address-card"></i> Referencias</h3>
                            
                            <?php if (!empty($secciones_completas) && !$secciones_completas['referencias'] && method_exists($candidateManager, 'getReferencias')): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <p>Debes añadir al menos una referencia profesional para completar tu perfil.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="references-list">
                                <?php if (!empty($referencias)): ?>
                                    <?php foreach ($referencias as $referencia): ?>
                                    <div class="reference-item">
                                        <div class="reference-header">
                                            <div class="reference-info">
                                                <h4><?php echo htmlspecialchars($referencia['nombre']); ?></h4>
                                                <div class="reference-position"><?php echo htmlspecialchars($referencia['puesto']); ?></div>
                                                <div class="reference-company"><?php echo htmlspecialchars($referencia['empresa']); ?></div>
                                                <div class="reference-relation"><?php echo htmlspecialchars($referencia['relacion']); ?></div>
                                                
                                                <?php if (!empty($referencia['email']) || !empty($referencia['telefono'])): ?>
                                                <div class="reference-contact">
                                                    <?php if (!empty($referencia['email'])): ?>
                                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($referencia['email']); ?></div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($referencia['telefono'])): ?>
                                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($referencia['telefono']); ?></div>
                                                    <?php endif; ?>
                                                    
                                                    <div>
                                                        <i class="fas <?php echo $referencia['autoriza_contacto'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                                        <?php echo $referencia['autoriza_contacto'] ? 'Autoriza contacto' : 'No autoriza contacto'; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reference-actions">
                                                <span class="action-link edit-reference" data-id="<?php echo $referencia['id']; ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </span>
                                                <span class="action-link delete action-delete-reference" data-id="<?php echo $referencia['id']; ?>">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <p>No has agregado ninguna referencia laboral. Añadir referencias mejora tu credibilidad profesional.</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="add-reference-btn" id="btnAddReference">
                                    <i class="fas fa-plus"></i> Añadir referencia
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Preferencias Laborales -->
                    <div id="preferencias" class="profile-tab <?php echo $active_tab == 'preferencias' ? 'active' : ''; ?>">
                        <form class="profile-form" action="profile.php?tab=preferencias" method="POST">
                            <div class="form-section">
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
                            
                            <div class="form-buttons">
                                <input type="hidden" name="update_profile" value="1">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Pestaña Notificaciones -->
                    <div id="notificaciones" class="profile-tab <?php echo $active_tab == 'notificaciones' ? 'active' : ''; ?>">
                        <form class="profile-form" action="profile.php?tab=notificaciones" method="POST">
                            <div class="form-section">
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
            </div>
        </main>
    </div>
    
    <!-- Modal para añadir/editar experiencia laboral -->
    <div class="modal" id="experienceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="experienceModalTitle">Añadir Experiencia Laboral</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="experienceForm" action="profile.php?tab=experiencia-laboral" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="empresa" class="form-label">Empresa <span class="required-mark">*</span></label>
                        <input type="text" id="empresa" name="empresa" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="puesto" class="form-label">Puesto <span class="required-mark">*</span></label>
                        <input type="text" id="puesto" name="puesto" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio" class="form-label">Fecha de inicio <span class="required-mark">*</span></label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin" class="form-label">Fecha de fin</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="form-control">
                        <div class="checkbox-group" style="margin-top: 5px;">
                            <input type="checkbox" id="trabajo_actual" name="trabajo_actual" value="1">
                            <label for="trabajo_actual">Trabajo actual</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" class="form-control" placeholder="Ej: Santo Domingo, República Dominicana">
                </div>
                
                <div class="form-group">
                    <label for="sector" class="form-label">Sector</label>
                    <input type="text" id="sector" name="sector" class="form-control" placeholder="Ej: Tecnología, Salud, Educación">
                </div>
                
                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción de responsabilidades</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Describe tus principales responsabilidades en este puesto"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="logros" class="form-label">Logros destacados</label>
                    <textarea id="logros" name="logros" class="form-control" rows="3" placeholder="Menciona tus principales logros o resultados obtenidos"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="razon_salida" class="form-label">Razón de salida</label>
                    <input type="text" id="razon_salida" name="razon_salida" class="form-control" placeholder="Ej: Mejor oportunidad, fin de contrato, etc.">
                </div>
                
                <div class="modal-footer">
                    <input type="hidden" name="experiencia_id" id="experiencia_id" value="">
                    <input type="hidden" name="add_experience" id="add_experience" value="1">
                    <button type="button" class="btn-outline close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para añadir/editar referencia -->
    <div class="modal" id="referenceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="referenceModalTitle">Añadir Referencia</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="referenceForm" action="profile.php?tab=referencias" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ref_nombre" class="form-label">Nombre completo <span class="required-mark">*</span></label>
                        <input type="text" id="ref_nombre" name="nombre" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ref_puesto" class="form-label">Puesto <span class="required-mark">*</span></label>
                        <input type="text" id="ref_puesto" name="puesto" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ref_empresa" class="form-label">Empresa <span class="required-mark">*</span></label>
                        <input type="text" id="ref_empresa" name="empresa" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ref_relacion" class="form-label">Relación laboral <span class="required-mark">*</span></label>
                        <select id="ref_relacion" name="relacion" class="form-control" required>
                            <option value="">Selecciona una opción</option>
                            <option value="Jefe directo">Jefe directo</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Colega">Colega</option>
                            <option value="Subordinado">Subordinado</option>
                            <option value="Cliente">Cliente</option>
                            <option value="Proveedor">Proveedor</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ref_email" class="form-label">Email</label>
                        <input type="email" id="ref_email" name="email" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="ref_telefono" class="form-label">Teléfono</label>
                        <input type="tel" id="ref_telefono" name="telefono" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="autoriza_contacto" name="autoriza_contacto" value="1" checked>
                        <label for="autoriza_contacto">Autorizo contactar a esta persona como referencia</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <input type="hidden" name="referencia_id" id="referencia_id" value="">
                    <input type="hidden" name="add_reference" id="add_reference" value="1">
                    <button type="button" class="btn-outline close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para confirmar eliminación de experiencia -->
    <div class="modal" id="deleteExperienceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Eliminar Experiencia Laboral</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form action="profile.php?tab=experiencia-laboral" method="POST">
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta experiencia laboral? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="experiencia_id" id="delete_experiencia_id" value="">
                    <input type="hidden" name="delete_experience" value="1">
                    <button type="button" class="btn-outline close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para confirmar eliminación de referencia -->
    <div class="modal" id="deleteReferenceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Eliminar Referencia</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form action="profile.php?tab=referencias" method="POST">
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta referencia? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="referencia_id" id="delete_referencia_id" value="">
                    <input type="hidden" name="delete_reference" value="1">
                    <button type="button" class="btn-outline close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
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
        
        // Navegación por pestañas
        document.querySelectorAll('.profile-nav-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Ocultar todas las pestañas
                document.querySelectorAll('.profile-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Desactivar todos los enlaces
                document.querySelectorAll('.profile-nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Mostrar la pestaña seleccionada
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Activar el enlace
                this.classList.add('active');
                
                // Actualizar URL
                history.pushState(null, null, `profile.php?tab=${tabId}`);
            });
        });
        
        // Funciones para modales
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Cerrar modal con X o botón Cancelar
        document.querySelectorAll('.close-modal, .close-modal-btn').forEach(element => {
            element.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
        
        // Abrir modal de añadir experiencia
        document.getElementById('btnAddExperience')?.addEventListener('click', function() {
            // Reiniciar formulario
            document.getElementById('experienceForm').reset();
            document.getElementById('experienceModalTitle').textContent = 'Añadir Experiencia Laboral';
            document.getElementById('experiencia_id').value = '';
            document.getElementById('add_experience').name = 'add_experience';
            
            openModal('experienceModal');
        });
        
        // Manejar checkbox de trabajo actual
        document.getElementById('trabajo_actual')?.addEventListener('change', function() {
            const fechaFinInput = document.getElementById('fecha_fin');
            fechaFinInput.disabled = this.checked;
            if (this.checked) {
                fechaFinInput.value = '';
            }
        });
        
        // Editar experiencia
        document.querySelectorAll('.edit-experience').forEach(button => {
            button.addEventListener('click', function() {
                const experienciaId = this.getAttribute('data-id');
                // Aquí deberías cargar los datos de la experiencia por AJAX o desde un dataset
                // Por ahora, simularemos algunos datos
                
                // Reiniciar formulario
                document.getElementById('experienceForm').reset();
                
                // Cambiar título del modal y acción del formulario
                document.getElementById('experienceModalTitle').textContent = 'Editar Experiencia Laboral';
                document.getElementById('experiencia_id').value = experienciaId;
                document.getElementById('add_experience').name = 'update_experience';
                
                openModal('experienceModal');
                
                // Hacer una petición AJAX para obtener los datos de la experiencia
                fetch(`get_experiencia.php?id=${experienciaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('empresa').value = data.experiencia.empresa;
                            document.getElementById('puesto').value = data.experiencia.puesto;
                            document.getElementById('fecha_inicio').value = data.experiencia.fecha_inicio;
                            
                            if (data.experiencia.actual == 1) {
                                document.getElementById('trabajo_actual').checked = true;
                                document.getElementById('fecha_fin').disabled = true;
                            } else if (data.experiencia.fecha_fin) {
                                document.getElementById('fecha_fin').value = data.experiencia.fecha_fin;
                            }
                            
                            document.getElementById('ubicacion').value = data.experiencia.ubicacion || '';
                            document.getElementById('sector').value = data.experiencia.sector || '';
                            document.getElementById('descripcion').value = data.experiencia.descripcion || '';
                            document.getElementById('logros').value = data.experiencia.logros || '';
                            document.getElementById('razon_salida').value = data.experiencia.razon_salida || '';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
        
        // Eliminar experiencia
        document.querySelectorAll('.action-delete-experience').forEach(button => {
            button.addEventListener('click', function() {
                const experienciaId = this.getAttribute('data-id');
                document.getElementById('delete_experiencia_id').value = experienciaId;
                openModal('deleteExperienceModal');
            });
        });
        
        // Abrir modal de añadir referencia
        document.getElementById('btnAddReference')?.addEventListener('click', function() {
            // Reiniciar formulario
            document.getElementById('referenceForm').reset();
            document.getElementById('referenceModalTitle').textContent = 'Añadir Referencia';
            document.getElementById('referencia_id').value = '';
            document.getElementById('add_reference').name = 'add_reference';
            
            openModal('referenceModal');
        });
        
        // Editar referencia
        document.querySelectorAll('.edit-reference').forEach(button => {
            button.addEventListener('click', function() {
                const referenciaId = this.getAttribute('data-id');
                
                // Reiniciar formulario
                document.getElementById('referenceForm').reset();
                
                // Cambiar título del modal y acción del formulario
                document.getElementById('referenceModalTitle').textContent = 'Editar Referencia';
                document.getElementById('referencia_id').value = referenciaId;
                document.getElementById('add_reference').name = 'update_reference';
                
                openModal('referenceModal');
                
                // Hacer una petición AJAX para obtener los datos de la referencia
                fetch(`get_referencia.php?id=${referenciaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('ref_nombre').value = data.referencia.nombre;
                            document.getElementById('ref_puesto').value = data.referencia.puesto;
                            document.getElementById('ref_empresa').value = data.referencia.empresa;
                            document.getElementById('ref_relacion').value = data.referencia.relacion;
                            document.getElementById('ref_email').value = data.referencia.email || '';
                            document.getElementById('ref_telefono').value = data.referencia.telefono || '';
                            document.getElementById('autoriza_contacto').checked = data.referencia.autoriza_contacto == 1;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
        
        // Eliminar referencia
        document.querySelectorAll('.action-delete-reference').forEach(button => {
            button.addEventListener('click', function() {
                const referenciaId = this.getAttribute('data-id');
                document.getElementById('delete_referencia_id').value = referenciaId;
                openModal('deleteReferenceModal');
            });
        });
    </script>
</body>
</html>