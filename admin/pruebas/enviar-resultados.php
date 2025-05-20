<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/enviar-resultados.php - Enviar resultados por email
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
    $_SESSION['error'] = "Método de solicitud inválido";
    header('Location: index.php');
    exit;
}

// Verificar datos necesarios
if (!isset($_POST['session_id']) || empty($_POST['session_id']) || 
    !isset($_POST['email_to']) || empty($_POST['email_to'])) {
    $_SESSION['error'] = "Datos incompletos para enviar el correo";
    header('Location: index.php');
    exit;
}

$session_id = (int)$_POST['session_id'];
$email_to = filter_var($_POST['email_to'], FILTER_SANITIZE_EMAIL);
$email_subject = isset($_POST['email_subject']) ? trim($_POST['email_subject']) : 'Resultados de Evaluación';
$email_message = isset($_POST['email_message']) ? trim($_POST['email_message']) : 'Adjunto encontrará los resultados de la evaluación.';
$include_candidate = isset($_POST['include_candidate']) && $_POST['include_candidate'] == '1';

// Validar email
if (!filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Dirección de correo electrónico inválida";
    header("Location: resultados.php?session_id=$session_id");
    exit;
}

// Obtener datos de la prueba y del candidato
$db = Database::getInstance();
$session_id = $db->real_escape_string($session_id);

$sql = "SELECT sp.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion, 
               c.id as candidato_id, c.nombre as candidato_nombre, c.apellido as candidato_apellido, 
               c.email as candidato_email
        FROM sesiones_prueba sp
        JOIN pruebas p ON sp.prueba_id = p.id
        JOIN candidatos c ON sp.candidato_id = c.id
        WHERE sp.id = '$session_id'";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Sesión de prueba no encontrada";
    header('Location: index.php');
    exit;
}

$prueba = $result->fetch_assoc();
$candidato_id = $prueba['candidato_id'];

// Generar PDF con resultados (esto es un ejemplo simplificado)
// En una implementación real, se utilizaría una biblioteca como TCPDF, FPDF o Dompdf
$pdf_path = generatePDF($session_id);

if (!$pdf_path) {
    $_SESSION['error'] = "Error al generar el PDF de resultados";
    header("Location: resultados.php?session_id=$session_id");
    exit;
}

// Enviar email
$result = sendResultsEmail($email_to, $email_subject, $email_message, $pdf_path, $prueba, $include_candidate);

if ($result) {
    $_SESSION['success'] = "Resultados enviados correctamente a $email_to" . 
                           ($include_candidate ? " y al candidato" : "");
} else {
    $_SESSION['error'] = "Error al enviar el correo electrónico";
}

// Redireccionar
header("Location: resultados.php?session_id=$session_id");
exit;

/**
 * Genera un PDF con los resultados de la evaluación
 * 
 * @param int $session_id ID de la sesión de prueba
 * @return string|false Ruta al archivo PDF generado o false en caso de error
 */
function generatePDF($session_id) {
    // En una implementación real, esto utilizaría una biblioteca de generación de PDF
    // Para este ejemplo, creamos un archivo de texto simple como placeholder
    
    $output_dir = '../../temp/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $filename = 'resultados_' . $session_id . '_' . time() . '.pdf';
    $filepath = $output_dir . $filename;
    
    // Placeholder - En una implementación real, aquí iría el código para generar el PDF
    // con toda la información de resultados, gráficas, etc.
    $content = "Resultados de la evaluación - Sesión #$session_id\n";
    $content .= "Fecha de generación: " . date('Y-m-d H:i:s') . "\n";
    $content .= "Este es un archivo placeholder. En una implementación real, aquí estaría el PDF completo con resultados.";
    
    if (file_put_contents($filepath, $content)) {
        return $filepath;
    }
    
    return false;
}

/**
 * Envía un email con los resultados de la evaluación
 * 
 * @param string $email_to Dirección de correo del destinatario
 * @param string $subject Asunto del correo
 * @param string $message Mensaje del correo
 * @param string $pdf_path Ruta al archivo PDF adjunto
 * @param array $prueba Datos de la prueba y candidato
 * @param bool $include_candidate Si se debe incluir al candidato como destinatario
 * @return bool Éxito o fracaso del envío
 */
function sendResultsEmail($email_to, $subject, $message, $pdf_path, $prueba, $include_candidate) {
    // En una implementación real, se utilizaría PHPMailer o similar
    // Para este ejemplo, usamos la función mail() nativa de PHP
    
    $candidato_nombre = $prueba['candidato_nombre'] . ' ' . $prueba['candidato_apellido'];
    $prueba_titulo = $prueba['prueba_titulo'];
    
    $sender = "noreply@example.com"; // Cambiar por el email real del sistema
    $sender_name = "Sistema de Evaluación - SolFis";
    
    // Construir cabeceras del correo
    $headers = "From: $sender_name <$sender>\r\n";
    $headers .= "Reply-To: $sender\r\n";
    
    // Si se debe incluir al candidato, agregarlo como CC
    if ($include_candidate && !empty($prueba['candidato_email'])) {
        $headers .= "Cc: " . $prueba['candidato_email'] . "\r\n";
    }
    
    // Generar un boundary para el contenido multiparte
    $boundary = md5(time());
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
    // Construir el cuerpo del mensaje
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    
    $body .= "Resultados de Evaluación\r\n";
    $body .= "=======================\r\n\r\n";
    $body .= "Candidato: $candidato_nombre\r\n";
    $body .= "Prueba: $prueba_titulo\r\n";
    $body .= "Fecha: " . date('d/m/Y') . "\r\n\r\n";
    $body .= $message . "\r\n\r\n";
    $body .= "Este correo ha sido enviado automáticamente desde el Sistema de Evaluación de SolFis.\r\n";
    
    // Adjuntar el PDF
    if (file_exists($pdf_path)) {
        $file_content = file_get_contents($pdf_path);
        $file_content = chunk_split(base64_encode($file_content));
        
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"Resultados_$candidato_nombre.pdf\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"Resultados_$candidato_nombre.pdf\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $file_content . "\r\n";
    }
    
    $body .= "--$boundary--";
    
    // Enviar el correo
    return mail($email_to, $subject, $body, $headers);
}