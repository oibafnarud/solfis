<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/actualizar-estado.php - Actualizar estado de una aplicación
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar parámetros requeridos
if (!isset($_POST['id']) || !isset($_POST['estado'])) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener datos del formulario
$id = (int)$_POST['id'];
$estado = $_POST['estado'];
$comentario = $_POST['comentario'] ?? '';
$redirect = $_POST['redirect'] ?? "detalle.php?id={$id}";

// Instanciar gestor de aplicaciones
$applicationManager = new ApplicationManager();

// Obtener estado actual
$aplicacion = $applicationManager->getApplicationById($id);
if (!$aplicacion) {
    header('Location: index.php?message=error');
    exit;
}

$estado_anterior = $aplicacion['estado'];

// Actualizar estado
$result = $applicationManager->updateApplicationStatus($id, $estado);

// Registrar en historial si existe el método
if ($result && method_exists($applicationManager, 'addApplicationHistory')) {
    $usuario_id = $auth->getUserId();
    $applicationManager->addApplicationHistory($id, $estado_anterior, $estado, $comentario, $usuario_id);
}

// Obtener datos del candidato y la vacante para enviar notificación
if ($result && $estado !== $estado_anterior) {
    $candidateManager = new CandidateManager();
    $vacancyManager = new VacancyManager();
    
    $candidato = $candidateManager->getCandidateById($aplicacion['candidato_id']);
    $vacante = $vacancyManager->getVacancyById($aplicacion['vacante_id']);
    
    // Si existe la función de enviar notificación, utilizarla
    if (function_exists('sendStatusChangeNotification')) {
        $data = [
            'candidato' => $candidato,
            'vacante' => $vacante,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado,
            'comentario' => $comentario
        ];
        
        sendStatusChangeNotification($data);
    }
}

// Redirigir según resultado
if ($result) {
    header("Location: {$redirect}&message=status-updated");
} else {
    header("Location: {$redirect}&message=error");
}
exit;
?>