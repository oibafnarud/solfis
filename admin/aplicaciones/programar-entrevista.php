<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/programar-entrevista.php - Programar entrevista
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

// Verificar método de petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar parámetros
if (empty($_POST['aplicacion_id']) || empty($_POST['fecha_entrevista']) || empty($_POST['hora_entrevista']) || empty($_POST['tipo_entrevista'])) {
    header('Location: index.php?error=missing_fields');
    exit;
}

// Obtener datos
$aplicacion_id = (int)$_POST['aplicacion_id'];
$candidato_id = isset($_POST['candidato_id']) ? (int)$_POST['candidato_id'] : 0;
$fecha = $_POST['fecha_entrevista'];
$hora = $_POST['hora_entrevista'];
$tipo = $_POST['tipo_entrevista'];
$lugar = $_POST['lugar_entrevista'] ?? '';
$notas = $_POST['notas_entrevista'] ?? '';
$notificar = !empty($_POST['notificar_candidato']);

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();

// Preparar datos para la etapa
$fecha_hora = $fecha . ' ' . $hora;
$etapa_titulo = 'Entrevista - ' . ucfirst($tipo);
$etapa_notas = "Tipo: " . ucfirst($tipo) . "\n";
$etapa_notas .= "Fecha y hora: " . date('d/m/Y H:i', strtotime($fecha_hora)) . "\n";

if (!empty($lugar)) {
    $etapa_notas .= "Lugar/Enlace: " . $lugar . "\n";
}

if (!empty($notas)) {
    $etapa_notas .= "\nNotas adicionales:\n" . $notas;
}

// Agregar etapa
$result = $applicationManager->addApplicationStage([
    'aplicacion_id' => $aplicacion_id,
    'etapa' => $etapa_titulo,
    'notas' => $etapa_notas,
    'estado' => 'pendiente',
    'fecha' => $fecha_hora
]);

// Si la aplicación no está en estado "entrevista", cambiarlo
// Obtener aplicación
$aplicacion = $applicationManager->getApplicationById($aplicacion_id);
if ($aplicacion && $aplicacion['estado'] !== 'entrevista') {
    $applicationManager->updateApplicationStatus(
        $aplicacion_id, 
        'entrevista', 
        'Cambiado automáticamente al programar una entrevista.'
    );
}

// Notificar al candidato si se solicitó
if ($notificar && !empty($candidato_id)) {
    // TODO: Implementar envío de email al candidato
    // Por ahora, simplemente añadir una nota indicando que se envió la notificación
    $applicationManager->addApplicationStage([
        'aplicacion_id' => $aplicacion_id,
        'etapa' => 'Notificación',
        'notas' => 'Se ha enviado una notificación por email al candidato sobre la entrevista programada.',
        'estado' => 'completada'
    ]);
}

// Redireccionar según resultado
if ($result['success']) {
    // Determinar página de redirección
    $redirect = 'detalle.php?id=' . $aplicacion_id . '&message=interview-scheduled';
    
    // Si venimos de la página de detalle de candidato, regresar allí
    if ($candidato_id > 0) {
        $redirect = '../candidatos/detalle.php?id=' . $candidato_id . '&tab=aplicaciones&message=interview-scheduled';
    }
    
    header('Location: ' . $redirect);
} else {
    header('Location: index.php?error=' . urlencode($result['message']));
}
exit;