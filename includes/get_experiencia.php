<?php
/**
 * Script para obtener datos de una experiencia laboral por AJAX
 */
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de experiencia no proporcionado']);
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID de experiencia
$experiencia_id = (int)$_GET['id'];
$candidato_id = (int)$_SESSION['candidato_id'];

// Conexión a base de datos
$db = Database::getInstance();

// Verificar que la experiencia pertenece al candidato
$sql = "SELECT * FROM experiencia_laboral WHERE id = $experiencia_id AND candidato_id = $candidato_id";
$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Experiencia no encontrada o no pertenece a este candidato']);
    exit;
}

$experiencia = $result->fetch_assoc();

// Formatear fechas para HTML
if (!empty($experiencia['fecha_inicio'])) {
    $experiencia['fecha_inicio'] = date('Y-m-d', strtotime($experiencia['fecha_inicio']));
}

if (!empty($experiencia['fecha_fin'])) {
    $experiencia['fecha_fin'] = date('Y-m-d', strtotime($experiencia['fecha_fin']));
}

// Devolver datos en formato JSON
echo json_encode(['success' => true, 'experiencia' => $experiencia]);
?>