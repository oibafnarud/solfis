<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar que se han proporcionado datos del formulario
if (!isset($_POST['candidato_id']) || empty($_POST['candidato_id']) || 
    !isset($_POST['destinatario']) || empty($_POST['destinatario']) ||
    !isset($_POST['asunto']) || empty($_POST['asunto'])) {
    die("Error: Faltan campos obligatorios");
}

$candidato_id = (int)$_POST['candidato_id'];
$destinatario = $_POST['destinatario'];
$asunto = $_POST['asunto'];
$mensaje = isset($_POST['mensaje']) ? $_POST['mensaje'] : '';
$incluir_cv = isset($_POST['incluir_cv']) && $_POST['incluir_cv'] == 'on';

// Incluir las dependencias necesarias
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/CandidateManager.php';
require_once '../../vendor/autoload.php'; // Asegúrate de tener PHPMailer instalado vía Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inicializar el gestor de candidatos
$candidateManager = new CandidateManager();
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    die("Error: Candidato no encontrado");
}

// Generar PDF del perfil
require_once '../../libs/tcpdf/tcpdf.php'; // Asegúrate de tener TCPDF instalado

// Crear una nueva instancia de TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Configurar el documento
$pdf->SetCreator('SolFis - Sistema de Reclutamiento');
$pdf->SetAuthor('SolFis');
$pdf->SetTitle('Perfil de Candidato - ' . $candidato['nombre'] . ' ' . $candidato['apellido']);
$pdf->SetSubject('Perfil de Candidato');
$pdf->SetKeywords('Candidato, Perfil, Reclutamiento, SolFis');

// Eliminar encabezado y pie de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Establecer el margen izquierdo
$pdf->SetMargins(15, 15, 15);

// Agregar una página
$pdf->AddPage();

// Obtener datos adicionales
$experiencias = $candidateManager->getCandidateExperiences($candidato_id);
$educacion = $candidateManager->getCandidateEducation($candidato_id);
$habilidades = $candidateManager->getCandidateSkills($candidato_id);

// Comprobar si existe TestManager y obtener datos psicométricos
$hasTestManager = false;
$pruebasCompletadas = [];
$evaluationResults = [];
$indicesCompuestos = [];
$perfilMotivacional = null;

if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
        
        // Obtener pruebas completadas y resultados
        try {
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            if (!empty($pruebasCompletadas)) {
                // Asegurarse de que los índices compuestos estén calculados
                $testManager->calculateAndSaveAllCompositeIndices($candidato_id);
                
                // Obtener resultados por tipo
                $resultadosPersonalidad = $testManager->getCandidateResultsByType($candidato_id, 'primaria');
                $resultadosAptitudes = $testManager->getCandidateResultsByType($candidato_id, 'cognitiva');
                $resultadosMotivacion = $testManager->getCandidateResultsByType($candidato_id, 'motiv');
                
                // Obtener índices compuestos
                $indicesCompuestos = $testManager->getCandidateCompositeIndices($candidato_id);
                
                // Obtener perfil motivacional
                $perfilMotivacional = $testManager->getCandidateMotivationalProfile($candidato_id);
                
                // Combinar todos los resultados
                $evaluationResults = array_merge(
                    $resultadosPersonalidad ? $resultadosPersonalidad : [],
                    $resultadosAptitudes ? $resultadosAptitudes : [],
                    $resultadosMotivacion ? $resultadosMotivacion : []
                );
            }
        } catch (Exception $e) {
            // Manejo de errores
            error_log("Error al obtener resultados de pruebas: " . $e->getMessage());
        }
    }
}

// Determinar foto de perfil
$foto_path = !empty($candidato['foto_path']) ? '../../' . $candidato['foto_path'] : '../../img/default-avatar.png';

// Iniciar el contenido HTML
$html = '
<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.5;
    }
    h1 {
        color: #333;
        font-size: 20pt;
        margin-bottom: 10px;
    }
    h2 {
        color: #2c5282;
        font-size: 16pt;
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
        margin-top: 20px;
    }
    h3 {
        color: #2c5282;
        font-size: 14pt;
        margin-top: 15px;
    }
    .section {
        margin-bottom: 20px;
    }
    .header {
        border-bottom: 2px solid #2c5282;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .profile-image {
        text-align: center;
        margin-bottom: 10px;
    }
    .info-block {
        margin-bottom: 10px;
    }
    .info-label {
        font-weight: bold;
        color: #2c5282;
    }
    .experience-item, .education-item {
        margin-bottom: 15px;
    }
    .experience-title, .education-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .experience-company, .education-institution {
        font-style: italic;
        color: #666;
        margin-bottom: 5px;
    }
    .experience-date, .education-date {
        color: #888;
        margin-bottom: 5px;
    }
    .skill-item {
        display: inline-block;
        background-color: #e2e8f0;
        padding: 5px 10px;
        margin-right: 5px;
        margin-bottom: 5px;
        border-radius: 15px;
    }
    .evaluation-item {
        margin-bottom: 15px;
    }
    .evaluation-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .evaluation-score {
        color: #2c5282;
        margin-bottom: 5px;
    }
    .footer {
        text-align: center;
        font-size: 9pt;
        color: #666;
        margin-top: 30px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th {
        background-color: #f2f2f2;
        text-align: left;
        padding: 8px;
    }
    td {
        padding: 8px;
    }
</style>

<div class="header">
    <h1>Perfil de Candidato</h1>
    <h2>' . $candidato['nombre'] . ' ' . $candidato['apellido'] . '</h2>
</div>';

// Sección de Información Personal
$html .= '
<div class="section">
    <table>
        <tr>
            <td width="30%" style="text-align: center; vertical-align: top;">';

if (file_exists($foto_path)) {
    $html .= '<img src="' . $foto_path . '" style="width: 120px; border-radius: 60px;">';
} else {
    $html .= '<div style="width: 120px; height: 120px; background-color: #ccc; border-radius: 60px; margin: 0 auto;"></div>';
}

$html .= '
            </td>
            <td width="70%" style="vertical-align: top;">
                <div class="info-block">
                    <span class="info-label">Email:</span> ' . $candidato['email'] . '
                </div>';

if (!empty($candidato['telefono'])) {
    $html .= '
                <div class="info-block">
                    <span class="info-label">Teléfono:</span> ' . $candidato['telefono'] . '
                </div>';
}

if (!empty($candidato['ubicacion'])) {
    $html .= '
                <div class="info-block">
                    <span class="info-label">Ubicación:</span> ' . $candidato['ubicacion'] . '
                </div>';
}

if (!empty($candidato['experiencia_general'])) {
    $html .= '
                <div class="info-block">
                    <span class="info-label">Experiencia:</span> ' . str_replace('-', ' a ', $candidato['experiencia_general']) . ' años
                </div>';
}

if (!empty($candidato['salario_esperado'])) {
    $html .= '
                <div class="info-block">
                    <span class="info-label">Salario esperado:</span> RD$' . number_format($candidato['salario_esperado'], 0, '.', ',') . '
                </div>';
}

if (!empty($candidato['disponibilidad'])) {
    $html .= '
                <div class="info-block">
                    <span class="info-label">Disponibilidad:</span> ' . str_replace('-', ' ', $candidato['disponibilidad']) . '
                </div>';
}

$html .= '
            </td>
        </tr>
    </table>
</div>';

// Resumen Profesional
if (!empty($candidato['resumen_profesional'])) {
    $html .= '
<div class="section">
    <h2>Resumen Profesional</h2>
    <p>' . nl2br($candidato['resumen_profesional']) . '</p>
</div>';
}

// Experiencia Laboral (versión resumida para el email)
if (!empty($experiencias)) {
    $html .= '
<div class="section">
    <h2>Experiencia Laboral</h2>';
    
    // Limitar a las 2 experiencias más recientes
    $experienciasRecientes = array_slice($experiencias, 0, 2);
    foreach ($experienciasRecientes as $experiencia) {
        $html .= '
    <div class="experience-item">
        <div class="experience-title">' . $experiencia['cargo'] . '</div>
        <div class="experience-company">' . $experiencia['empresa'] . '</div>
        <div class="experience-date">' . 
            date('M Y', strtotime($experiencia['fecha_inicio'])) . ' - ' . 
            ($experiencia['actual'] ? 'Actualidad' : date('M Y', strtotime($experiencia['fecha_fin']))) . 
        '</div>';
        
        if (!empty($experiencia['descripcion'])) {
            $html .= '
        <p>' . nl2br(substr($experiencia['descripcion'], 0, 200)) . (strlen($experiencia['descripcion']) > 200 ? '...' : '') . '</p>';
        }
        
        $html .= '
    </div>';
    }
    
    $html .= '
</div>';
}

// Habilidades (versión resumida)
if (!empty($candidato['habilidades_destacadas'])) {
    $html .= '
<div class="section">
    <h2>Habilidades Destacadas</h2>
    <div>';
    
    $habilidadesArray = explode(',', $candidato['habilidades_destacadas']);
    foreach ($habilidadesArray as $habilidad) {
        $html .= '
        <span class="skill-item">' . trim($habilidad) . '</span>';
    }
    
    $html .= '
    </div>
</div>';
}

// Evaluaciones Psicométricas (versión resumida)
if ($hasTestManager && !empty($indicesCompuestos)) {
    $html .= '
<div class="section">
    <h2>Evaluaciones Psicométricas</h2>
    <p>Este candidato ha completado evaluaciones psicométricas. Los resultados detallados están disponibles en el archivo PDF adjunto.</p>
</div>';
}

// Pie de página
$html .= '
<div class="footer">
    <p>Para ver el perfil completo, consulte el archivo PDF adjunto.</p>
    <p>Documento generado por SolFis - Sistema de Reclutamiento el ' . date('d/m/Y H:i:s') . '</p>
</div>';

// Escribir el HTML al PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Guardar el PDF en un archivo temporal
$pdfPath = tempnam(sys_get_temp_dir(), 'profile_') . '.pdf';
$pdf->Output($pdfPath, 'F');

// Enviar el email con el PDF adjunto
$mail = new PHPMailer(true);

try {
    // Obtener configuración de email desde la base de datos
    $query = "SELECT * FROM email_settings WHERE id = 1 LIMIT 1";
    $result = $mysqli->query($query);
    $emailSettings = $result->fetch_assoc();
    
    // Servidor SMTP
    $mail->isSMTP();
    $mail->Host = $emailSettings['smtp_host'];
    $mail->SMTPAuth = $emailSettings['smtp_auth'];
    $mail->Username = $emailSettings['smtp_username'];
    $mail->Password = $emailSettings['smtp_password'];
    $mail->SMTPSecure = $emailSettings['smtp_secure'];
    $mail->Port = $emailSettings['smtp_port'];
    
    // Remitente y destinatarios
    $mail->setFrom($emailSettings['from_email'], $emailSettings['from_name']);
    $mail->addAddress($destinatario);
    if (!empty($emailSettings['reply_to'])) {
        $mail->addReplyTo($emailSettings['reply_to']);
    }
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = $asunto;
    
    // Cuerpo del mensaje
    $mailBody = '<p>Adjunto encontrará el perfil del candidato ' . $candidato['nombre'] . ' ' . $candidato['apellido'] . '.</p>';
    
    if (!empty($mensaje)) {
        $mailBody .= '<p>' . nl2br($mensaje) . '</p>';
    }
    
    $mailBody .= '<p>Datos de contacto del candidato:<br>
                 Email: ' . $candidato['email'] . '<br>
                 Teléfono: ' . $candidato['telefono'] . '</p>';
    
    $mailBody .= '<p>Este correo ha sido enviado automáticamente desde el Sistema de Reclutamiento de SolFis.</p>';
    
    $mail->Body = $mailBody;
    
    // Adjuntar el PDF del perfil
    $mail->addAttachment($pdfPath, 'Perfil_' . $candidato['nombre'] . '_' . $candidato['apellido'] . '.pdf');
    
    // Adjuntar el CV si está disponible y se ha solicitado
    if ($incluir_cv && !empty($candidato['cv_path']) && file_exists('../../' . $candidato['cv_path'])) {
        $mail->addAttachment('../../' . $candidato['cv_path'], 'CV_' . $candidato['nombre'] . '_' . $candidato['apellido'] . '.pdf');
    }
    
    // Enviar el correo
    $mail->send();
    
    // Eliminar el archivo temporal
    unlink($pdfPath);
    
    // Registrar el envío en la base de datos
    $usuario_id = $_SESSION['user_id'];
    $destinatario_email = $destinatario;
    $fecha_envio = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO notificaciones_vacantes (tipo, titulo, mensaje, destinatario_id, candidato_id, leida, created_at, updated_at) 
            VALUES ('otro', 'Envío de perfil por email', 'Perfil enviado a: $destinatario_email', $usuario_id, $candidato_id, 1, '$fecha_envio', '$fecha_envio')";
    $mysqli->query($sql);
    
    // Redirigir con mensaje de éxito
    $_SESSION['success_message'] = "Perfil enviado correctamente a $destinatario";
    header("Location: detalle.php?id=$candidato_id");
    exit;
    
} catch (Exception $e) {
    // Si hay un archivo temporal, eliminarlo
    if (file_exists($pdfPath)) {
        unlink($pdfPath);
    }
    
    // Redirigir con mensaje de error
    $_SESSION['error_message'] = "Error al enviar el correo: " . $mail->ErrorInfo;
    header("Location: detalle.php?id=$candidato_id");
    exit;
}