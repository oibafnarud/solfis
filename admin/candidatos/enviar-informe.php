<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/enviar-informe.php - Envía informe del candidato por email
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

// Verificar que es una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    // Si no es AJAX, verificar que se envió un formulario
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }
}

// Verificar parámetros obligatorios
if (!isset($_POST['candidato_id']) || !isset($_POST['recipientEmail']) || !isset($_POST['emailSubject']) || !isset($_POST['emailMessage'])) {
    $response = [
        'success' => false,
        'message' => 'Faltan parámetros obligatorios'
    ];
    
    // Si es AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } 
    // Si no es AJAX, redirigir con error
    else {
        $_SESSION['error'] = $response['message'];
        header('Location: resultados.php?id=' . (isset($_POST['candidato_id']) ? $_POST['candidato_id'] : ''));
        exit;
    }
}

// Obtener parámetros
$candidato_id = (int)$_POST['candidato_id'];
$recipientEmail = filter_var($_POST['recipientEmail'], FILTER_SANITIZE_EMAIL);
$emailSubject = filter_var($_POST['emailSubject'], FILTER_SANITIZE_STRING);
$emailMessage = filter_var($_POST['emailMessage'], FILTER_SANITIZE_STRING);
$includeCV = isset($_POST['includeCV']) && $_POST['includeCV'] == '1';

// Validar email
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    $response = [
        'success' => false,
        'message' => 'La dirección de email no es válida'
    ];
    
    // Si es AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } 
    // Si no es AJAX, redirigir con error
    else {
        $_SESSION['error'] = $response['message'];
        header('Location: resultados.php?id=' . $candidato_id);
        exit;
    }
}

// Instanciar gestores
$candidateManager = new CandidateManager();

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $response = [
        'success' => false,
        'message' => 'Candidato no encontrado'
    ];
    
    // Si es AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } 
    // Si no es AJAX, redirigir con error
    else {
        $_SESSION['error'] = $response['message'];
        header('Location: index.php');
        exit;
    }
}

// Generar el PDF del informe temporal
// Crear directorio temporal si no existe
$tempDir = sys_get_temp_dir() . '/solfis_informes';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Nombre del archivo temporal
$tempFileName = 'Informe_' . $candidato['nombre'] . '_' . $candidato['apellido'] . '_' . date('Ymd_His') . '.pdf';
$tempFilePath = $tempDir . '/' . $tempFileName;

// Generar el PDF
// Incluimos aquí una versión simplificada de la generación del PDF
// En una implementación real, este código debería estar en una función o clase separada
// para evitar duplicación con el archivo generar-informe.php

// Cargar la librería TCPDF para generar el PDF
require_once '../../vendor/autoload.php';

// Crear nueva instancia de TCPDF (clase simplificada para este ejemplo)
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar la información del documento
$pdf->SetCreator('SolFis Consultores');
$pdf->SetAuthor('SolFis RRHH');
$pdf->SetTitle('Informe de Evaluación - ' . $candidato['nombre'] . ' ' . $candidato['apellido']);
$pdf->SetSubject('Evaluación de Candidato');

// Configuración básica del PDF
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->SetFont('helvetica', '', 11);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'INFORME DE EVALUACIÓN', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 10, $candidato['nombre'] . ' ' . $candidato['apellido'], 0, 1, 'C');
$pdf->Ln(5);

// Contenido básico (versión simplificada)
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Información del Candidato', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$pdf->Cell(40, 7, 'Nombre:', 0, 0, 'L');
$pdf->Cell(0, 7, $candidato['nombre'] . ' ' . $candidato['apellido'], 0, 1, 'L');

$pdf->Cell(40, 7, 'Email:', 0, 0, 'L');
$pdf->Cell(0, 7, $candidato['email'], 0, 1, 'L');

if (!empty($candidato['telefono'])) {
    $pdf->Cell(40, 7, 'Teléfono:', 0, 0, 'L');
    $pdf->Cell(0, 7, $candidato['telefono'], 0, 1, 'L');
}

// Nota sobre informe completo
$pdf->Ln(10);
$pdf->MultiCell(0, 7, 'Este documento es un resumen con información básica del candidato. El informe completo con resultados detallados de evaluaciones y recomendaciones se adjunta a este correo electrónico.', 0, 'L');

// Nota de privacidad
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(0, 5, 'Aviso de Confidencialidad: Este informe contiene información confidencial y está destinado únicamente al receptor especificado. Si ha recibido este documento por error, por favor notifique al remitente y elimine el documento.', 0, 'L');

// Generar salida del PDF
$pdf->Output($tempFilePath, 'F');

// Verificar que se generó el archivo
if (!file_exists($tempFilePath)) {
    $response = [
        'success' => false,
        'message' => 'Error al generar el informe PDF'
    ];
    
    // Si es AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } 
    // Si no es AJAX, redirigir con error
    else {
        $_SESSION['error'] = $response['message'];
        header('Location: resultados.php?id=' . $candidato_id);
        exit;
    }
}

// Configurar y enviar el email
// Cargar la librería PHPMailer si está disponible
// En una implementación real, esto estaría en el autoloader
if (file_exists('../../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
    
    // Usar PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configurar servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Remitente y destinatarios
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        
        // Cuerpo del mensaje HTML
        $htmlBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { font-size: 12px; color: #777; padding: 20px; text-align: center; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Informe de Evaluación</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a,</p>
                    <p>" . nl2br(htmlspecialchars($emailMessage)) . "</p>
                    <p>Adjunto encontrará el informe completo de evaluación en formato PDF.</p>
                    <p>Si tiene alguna pregunta o requiere información adicional, no dude en contactarnos.</p>
                    <p>Saludos cordiales,<br>
                    " . SMTP_FROM_NAME . "<br>
                    " . COMPANY_NAME . "</p>
                </div>
                <div class='footer'>
                    <p>Este correo y sus adjuntos contienen información confidencial y están destinados únicamente al receptor especificado.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Versión texto plano del mensaje
        $textBody = strip_tags(str_replace('<br>', "\n", $emailMessage)) . "\n\n" .
                   "Adjunto encontrará el informe completo de evaluación en formato PDF.\n\n" .
                   "Si tiene alguna pregunta o requiere información adicional, no dude en contactarnos.\n\n" .
                   "Saludos cordiales,\n" .
                   SMTP_FROM_NAME . "\n" .
                   COMPANY_NAME . "\n\n" .
                   "Este correo y sus adjuntos contienen información confidencial y están destinados únicamente al receptor especificado.";
        
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        
        // Adjuntar el informe PDF
        $mail->addAttachment($tempFilePath, $tempFileName);
        
        // Adjuntar CV si está disponible y se solicitó
        if ($includeCV && !empty($candidato['cv_path']) && file_exists('../../' . $candidato['cv_path'])) {
            $cvFileName = basename($candidato['cv_path']);
            $mail->addAttachment('../../' . $candidato['cv_path'], $cvFileName);
        }
        
        // Enviar email
        $mail->send();
        
        // Registrar el envío en la base de datos
        $db = Database::getInstance();
        $usuario_id = $auth->getCurrentUser()['id'];
        $currentDate = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO informes_enviados (candidato_id, usuario_id, destinatario, asunto, fecha_envio) 
                  VALUES ($candidato_id, $usuario_id, '" . $db->escape_string($recipientEmail) . "', '" . 
                  $db->escape_string($emailSubject) . "', '$currentDate')";
        
        $db->query($query);
        
        // Eliminar archivo temporal
        @unlink($tempFilePath);
        
        $response = [
            'success' => true,
            'message' => 'El informe ha sido enviado correctamente a ' . $recipientEmail
        ];
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Error al enviar el correo: ' . $mail->ErrorInfo
        ];
        
        // Intenta eliminar el archivo temporal incluso si hubo error
        @unlink($tempFilePath);
    }
} 
// Si no está disponible PHPMailer, usar la función mail() de PHP
else {
    try {
        // Cabeceras del email
        $boundary = md5(time());
        
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SMTP_REPLY_TO . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
        
        // Mensaje en texto plano
        $message = "--" . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        
        $textBody = $emailMessage . "\n\n" .
                  "Adjunto encontrará el informe completo de evaluación en formato PDF.\n\n" .
                  "Si tiene alguna pregunta o requiere información adicional, no dude en contactarnos.\n\n" .
                  "Saludos cordiales,\n" .
                  SMTP_FROM_NAME . "\n" .
                  COMPANY_NAME . "\n\n" .
                  "Este correo y sus adjuntos contienen información confidencial y están destinados únicamente al receptor especificado.";
        
        $message .= $textBody . "\r\n\r\n";
        
        // Adjuntar el informe PDF
        if (file_exists($tempFilePath)) {
            $fileContent = file_get_contents($tempFilePath);
            $message .= "--" . $boundary . "\r\n";
            $message .= "Content-Type: application/pdf; name=\"" . $tempFileName . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"" . $tempFileName . "\"\r\n\r\n";
            $message .= chunk_split(base64_encode($fileContent)) . "\r\n\r\n";
        }
        
        // Adjuntar CV si está disponible y se solicitó
        if ($includeCV && !empty($candidato['cv_path']) && file_exists('../../' . $candidato['cv_path'])) {
            $cvFileName = basename($candidato['cv_path']);
            $cvContent = file_get_contents('../../' . $candidato['cv_path']);
            
            $message .= "--" . $boundary . "\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"" . $cvFileName . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"" . $cvFileName . "\"\r\n\r\n";
            $message .= chunk_split(base64_encode($cvContent)) . "\r\n\r\n";
        }
        
        // Finalizar mensaje
        $message .= "--" . $boundary . "--";
        
        // Enviar email
        $mailSent = mail($recipientEmail, $emailSubject, $message, $headers);
        
        if ($mailSent) {
            // Registrar el envío en la base de datos
            $db = Database::getInstance();
            $usuario_id = $auth->getCurrentUser()['id'];
            $currentDate = date('Y-m-d H:i:s');
            
            $query = "INSERT INTO informes_enviados (candidato_id, usuario_id, destinatario, asunto, fecha_envio) 
                      VALUES ($candidato_id, $usuario_id, '" . $db->escape_string($recipientEmail) . "', '" . 
                      $db->escape_string($emailSubject) . "', '$currentDate')";
            
            $db->query($query);
            
            $response = [
                'success' => true,
                'message' => 'El informe ha sido enviado correctamente a ' . $recipientEmail
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Error al enviar el correo electrónico'
            ];
        }
        
        // Eliminar archivo temporal
        @unlink($tempFilePath);
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Error al enviar el correo: ' . $e->getMessage()
        ];
        
        // Intenta eliminar el archivo temporal incluso si hubo error
        @unlink($tempFilePath);
    }
}

// Responder según el tipo de petición
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Si es AJAX, devolver JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Si no es AJAX, redirigir con mensaje
    if ($response['success']) {
        $_SESSION['success'] = $response['message'];
    } else {
        $_SESSION['error'] = $response['message'];
    }
    
    header('Location: resultados.php?id=' . $candidato_id);
    exit;
}