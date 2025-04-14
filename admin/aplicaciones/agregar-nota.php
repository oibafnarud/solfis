<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/agregar-nota.php - Agregar nota a aplicación
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
if (empty($_POST['aplicacion_id']) || empty($_POST['etapa']) || empty($_POST['notas'])) {
    header('Location: index.php?error=missing_fields');
    exit;
}

// Obtener datos
$aplicacion_id = (int)$_POST['aplicacion_id'];
$candidato_id = isset($_POST['candidato_id']) ? (int)$_POST['candidato_id'] : 0;
$etapa = $_POST['etapa'];
$notas = $_POST['notas'];
$fecha = !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();

// Agregar etapa
$result = $applicationManager->addApplicationStage([
    'aplicacion_id' => $aplicacion_id,
    'etapa' => $etapa,
    'notas' => $notas,
    'estado' => 'completada',
    'fecha' => $fecha
]);

// Redireccionar según resultado
if ($result['success']) {
    // Determinar página de redirección
    $redirect = 'detalle.php?id=' . $aplicacion_id . '&message=note-added';
    
    // Si venimos de la página de detalle de candidato, regresar allí
    if ($candidato_id > 0) {
        $redirect = '../candidatos/detalle.php?id=' . $candidato_id . '&tab=aplicaciones&message=note-added';
    }
    
    header('Location: ' . $redirect);
} else {
    header('Location: index.php?error=' . urlencode($result['message']));
}
exit;