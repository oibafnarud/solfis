<?php
/**
 * Registro de Candidatos
 * includes/candidate-register.php
 */

// Verificar si ya hay sesión activa
if (isset($_SESSION['candidate_id'])) {
    header('Location: candidate-dashboard.php');
    exit;
}

// Incluir clases necesarias
require_once 'jobs-system.php';

// Instanciar gestores necesarios
$db = Database::getInstance();
$candidateManager = new CandidateManager();

// Inicializar variables
$formData = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'password' => '',
    'telefono' => '',
    'direccion' => '',
    'ciudad' => '',
    'pais' => '',
    'nivel_educativo_id' => '',
    'titulo_educativo' => '',
    'anos_experiencia' => '',
    'area_profesional_id' => '',
    'modalidad_trabajo' => '',
    'tipo_contrato' => '',
    'nivel_contratacion' => '',
    'salario_esperado' => '',
    'disponibilidad_inmediata' => '',
    'disponibilidad_viajar' => '',
    'resumen_profesional' => '',
    'linkedin_url' => '',
    'github_url' => '',
    'portfolio_url' => ''
];

// Obtener datos para selects
$educationLevels = $candidateManager->getEducationLevels();
$professionalAreas = $candidateManager->getProfessionalAreas();
$skills = $candidateManager->getAllSkills();

$errors = [];
$registrationSuccess = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar datos del formulario
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['apellido'] = trim($_POST['apellido'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['telefono'] = trim($_POST['telefono'] ?? '');
    $formData['direccion'] = trim($_POST['direccion'] ?? '');
    $formData['ciudad'] = trim($_POST['ciudad'] ?? '');
    $formData['pais'] = trim($_POST['pais'] ?? '');
    $formData['nivel_educativo_id'] = intval($_POST['nivel_educativo_id'] ?? 0);
    $formData['titulo_educativo'] = trim($_POST['titulo_educativo'] ?? '');
    $formData['anos_experiencia'] = intval($_POST['anos_experiencia'] ?? 0);
    $formData['area_profesional_id'] = intval($_POST['area_profesional_id'] ?? 0);
    $formData['modalidad_trabajo'] = trim($_POST['modalidad_trabajo'] ?? '');
    $formData['tipo_contrato'] = trim($_POST['tipo_contrato'] ?? '');
    $formData['nivel_contratacion'] = trim($_POST['nivel_contratacion'] ?? '');
    $formData['salario_esperado'] = trim($_POST['salario_esperado'] ?? '');
    $formData['disponibilidad_inmediata'] = isset($_POST['disponibilidad_inmediata']) ? 1 : 0;
    $formData['disponibilidad_viajar'] = isset($_POST['disponibilidad_viajar']) ? 1 : 0;
    $formData['resumen_profesional'] = trim($_POST['resumen_profesional'] ?? '');
    $formData['linkedin_url'] = trim($_POST['linkedin_url'] ?? '');
    $formData['github_url'] = trim($_POST['github_url'] ?? '');
    $formData['portfolio_url'] = trim($_POST['portfolio_url'] ?? '');
    
    // Validar campos obligatorios
    if (empty($formData['nombre'])) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($formData['apellido'])) {
        $errors[] = "El apellido es obligatorio";
    }
    
    if (empty($formData['email'])) {
        $errors[] = "El correo electrónico es obligatorio";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido";
    } elseif ($candidateManager->emailExists($formData['email'])) {
        $errors[] = "Este correo electrónico ya está registrado";
    }
    
    if (empty($formData['password'])) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($formData['password']) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if (empty($formData['telefono'])) {
        $errors[] = "El teléfono es obligatorio";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errors)) {
        try {
            // Habilidades seleccionadas
            $selectedSkills = isset($_POST['skills']) && is_array($_POST['skills']) ? $_POST['skills'] : [];
            
            // Registrar candidato
            $result = $candidateManager->registerCandidate($formData, $selectedSkills);
            
            if ($result['success']) {
                $candidateId = $result['candidate_id'];
                
                // Asignar pruebas psicométricas predeterminadas si el módulo está disponible
                if (file_exists('TestManager.php')) {
                    require_once 'TestManager.php';
                    if (class_exists('TestManager')) {
                        $testManager = new TestManager();
                        $testManager->assignDefaultTests($candidateId);
                    }
                }
                
                $registrationSuccess = true;
                $formData = []; // Limpiar datos del formulario
                
                // Opcional: Auto-login tras registro exitoso
                // $_SESSION['candidate_id'] = $candidateId;
                // $_SESSION['candidate_name'] = $formData['nombre'] . ' ' . $formData['apellido'];
                // header('Location: candidate-dashboard.php');
                // exit;
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>
<!-- El HTML del formulario de registro queda igual -->