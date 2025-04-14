<?php
/**
 * Clase para enviar correos electrónicos del sistema de pruebas psicométricas
 */
class EmailSender {
    private $db;
    private $settings;
    
    public function __construct() {
        // Inicializar base de datos
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
        
        // Cargar configuración de correo
        $this->loadEmailSettings();
    }
    
    /**
     * Carga la configuración de correo desde la base de datos
     */
    private function loadEmailSettings() {
        $sql = "SELECT * FROM email_settings LIMIT 1";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $this->settings = $result->fetch_assoc();
        } else {
            // Configuración predeterminada
            $this->settings = [
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_secure' => 'tls',
                'smtp_auth' => 1,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => 'rrhh@solfis.com.do',
                'from_name' => 'SolFis Recursos Humanos',
                'reply_to' => 'rrhh@solfis.com.do',
                'recipient_email' => 'rrhh@solfis.com.do'
            ];
        }
    }
    
    /**
     * Envía un correo electrónico
     */
    public function sendEmail($to, $subject, $message, $attachments = []) {
        // Si está instalado PHPMailer, usarlo
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $message, $attachments);
        }
        
        // Fallback a la función mail() nativa
        return $this->sendWithMail($to, $subject, $message);
    }
    
    /**
     * Envía un correo usando PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $message, $attachments = []) {
        try {
            // Incluir PHPMailer
            require_once 'vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'];
            $mail->Port = $this->settings['smtp_port'];
            
            // Configurar seguridad
            if ($this->settings['smtp_secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->settings['smtp_secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            
            // Configurar autenticación
            if ($this->settings['smtp_auth']) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->settings['smtp_username'];
                $mail->Password = $this->settings['smtp_password'];
            }
            
            // Remitente
            $mail->setFrom($this->settings['from_email'], $this->settings['from_name']);
            if (!empty($this->settings['reply_to'])) {
                $mail->addReplyTo($this->settings['reply_to']);
            }
            
            // Destinatario
            $mail->addAddress($to);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            
            // Añadir adjuntos
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
            
            // Enviar
            return $mail->send();
        } catch (Exception $e) {
            error_log('Error al enviar correo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía un correo usando la función mail() nativa
     */
    private function sendWithMail($to, $subject, $message) {
        // Cabeceras
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->settings['from_name'] . " <" . $this->settings['from_email'] . ">" . "\r\n";
        
        if (!empty($this->settings['reply_to'])) {
            $headers .= "Reply-To: " . $this->settings['reply_to'] . "\r\n";
        }
        
        // Enviar correo
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Envía credenciales a un candidato
     */
    public function sendCredentials($candidato, $password, $vacante = null) {
        $subject = "Credenciales de acceso - SolFis Talentos";
        
        // Construir mensaje
        $message = $this->getCredentialsEmailTemplate($candidato, $password, $vacante);
        
        // Enviar correo
        return $this->sendEmail($candidato['email'], $subject, $message);
    }
    
    /**
     * Envía notificación de prueba pendiente
     */
    public function sendTestReminder($candidato, $prueba) {
        $subject = "Prueba pendiente: " . $prueba['titulo'] . " - SolFis Talentos";
        
        // Construir mensaje
        $message = $this->getTestReminderEmailTemplate($candidato, $prueba);
        
        // Enviar correo
        return $this->sendEmail($candidato['email'], $subject, $message);
    }
    
    /**
     * Envía notificación a RR.HH. sobre prueba completada
     */
    public function sendTestCompletionNotification($candidato, $sesion, $prueba) {
        $subject = "Prueba completada: " . $candidato['nombre'] . " " . $candidato['apellido'];
        
        // Construir mensaje
        $message = $this->getTestCompletionEmailTemplate($candidato, $sesion, $prueba);
        
        // Enviar correo a RR.HH.
        return $this->sendEmail($this->settings['recipient_email'], $subject, $message);
    }
    
    /**
     * Envía notificación de coincidencia con vacante
     */
    public function sendVacancyMatchNotification($candidato, $vacante, $porcentaje_match) {
        $subject = "Oportunidad laboral: " . $vacante['titulo'] . " - SolFis Talentos";
        
        // Construir mensaje
        $message = $this->getVacancyMatchEmailTemplate($candidato, $vacante, $porcentaje_match);
        
        // Enviar correo
        return $this->sendEmail($candidato['email'], $subject, $message);
    }
    
    /**
     * Construye la plantilla para el correo de credenciales
     */
    private function getCredentialsEmailTemplate($candidato, $password, $vacante = null) {
        $nombre = $candidato['nombre'];
        $portalUrl = "https://solfis.com.do/candidato/login.php";
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Credenciales de acceso - SolFis Talentos</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #002C6B; color: white; padding: 15px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
                .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777; }
                .credentials { background-color: #e9f7fe; border-left: 4px solid #00B1EB; padding: 15px; margin: 20px 0; }
                .btn { display: inline-block; background-color: #00B1EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SolFis Talentos</h1>
                </div>
                <div class="content">
                    <h2>¡Bienvenido/a a nuestra plataforma de talentos!</h2>
                    <p>Hola ' . $nombre . ',</p>';
        
        if ($vacante) {
            $html .= '<p>Gracias por aplicar a la vacante de <strong>' . $vacante['titulo'] . '</strong> en SolFis.</p>';
        } else {
            $html .= '<p>Gracias por registrarte en nuestra plataforma de talentos.</p>';
        }
        
        $html .= '
                    <p>Hemos creado una cuenta para que puedas dar seguimiento a tu aplicación y completar nuestras evaluaciones psicométricas. Estas evaluaciones te ayudarán a destacar tus habilidades y competencias.</p>
                    
                    <div class="credentials">
                        <h3>Tus credenciales de acceso</h3>
                        <p><strong>Email:</strong> ' . $candidato['email'] . '</p>
                        <p><strong>Contraseña:</strong> ' . $password . '</p>
                    </div>
                    
                    <p>Te recomendamos cambiar tu contraseña una vez accedas al sistema.</p>
                    
                    <p style="text-align:center; margin-top: 30px;">
                        <a href="' . $portalUrl . '" class="btn">Acceder a mi cuenta</a>
                    </p>
                    
                    <p><strong>¿Qué sigue?</strong></p>
                    <ol>
                        <li>Inicia sesión en tu cuenta</li>
                        <li>Completa tu perfil profesional</li>
                        <li>Realiza nuestras evaluaciones psicométricas</li>
                        <li>Explora las vacantes disponibles</li>
                    </ol>
                    
                    <p>Al realizar nuestras evaluaciones, podrás conocer mejor tus fortalezas y áreas de desarrollo. Además, nuestro equipo de selección tendrá más información para identificar las oportunidades que mejor se adapten a tu perfil.</p>
                </div>
                <div class="footer">
                    <p>Si tienes alguna pregunta, contáctanos a rrhh@solfis.com.do</p>
                    <p>&copy; ' . date('Y') . ' SolFis. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Construye la plantilla para recordatorio de prueba pendiente
     */
    private function getTestReminderEmailTemplate($candidato, $prueba) {
        $nombre = $candidato['nombre'];
        $portalUrl = "https://solfis.com.do/candidato/login.php";
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Prueba pendiente - SolFis Talentos</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #002C6B; color: white; padding: 15px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
                .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777; }
                .test-info { background-color: #f1f8e9; border-left: 4px solid #8bc34a; padding: 15px; margin: 20px 0; }
                .btn { display: inline-block; background-color: #00B1EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SolFis Talentos</h1>
                </div>
                <div class="content">
                    <h2>Tienes una evaluación pendiente</h2>
                    <p>Hola ' . $nombre . ',</p>
                    <p>Te recordamos que tienes una prueba psicométrica pendiente por completar en nuestra plataforma.</p>
                    
                    <div class="test-info">
                        <h3>' . $prueba['titulo'] . '</h3>
                        <p>' . $prueba['descripcion'] . '</p>
                        <p><strong>Tiempo estimado:</strong> ' . $prueba['tiempo_estimado'] . ' minutos</p>
                    </div>
                    
                    <p>Completar esta evaluación es importante para que podamos identificar las oportunidades laborales que mejor se adapten a tu perfil profesional.</p>
                    
                    <p style="text-align:center; margin-top: 30px;">
                        <a href="' . $portalUrl . '" class="btn">Iniciar sesión y completar la prueba</a>
                    </p>
                    
                    <p>Recuerda que para obtener los mejores resultados debes:</p>
                    <ul>
                        <li>Estar en un lugar tranquilo y sin interrupciones</li>
                        <li>Contar con al menos ' . $prueba['tiempo_estimado'] . ' minutos disponibles</li>
                        <li>Responder con sinceridad, no hay respuestas "correctas" o "incorrectas"</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>Si tienes alguna pregunta, contáctanos a rrhh@solfis.com.do</p>
                    <p>&copy; ' . date('Y') . ' SolFis. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Construye la plantilla para notificación de prueba completada (a RRHH)
     */
    private function getTestCompletionEmailTemplate($candidato, $sesion, $prueba) {
        $nombre_completo = $candidato['nombre'] . ' ' . $candidato['apellido'];
        $panel_url = "https://solfis.com.do/admin/candidatos/detalle.php?id=" . $candidato['id'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Prueba completada - SolFis Talentos</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #002C6B; color: white; padding: 15px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
                .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777; }
                .candidate-info { background-color: #e8eaf6; border-left: 4px solid #3f51b5; padding: 15px; margin: 20px 0; }
                .btn { display: inline-block; background-color: #00B1EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Notificación de Prueba Completada</h1>
                </div>
                <div class="content">
                    <h2>Evaluación finalizada</h2>
                    <p>Un candidato ha completado una prueba psicométrica en la plataforma.</p>
                    
                    <div class="candidate-info">
                        <h3>Información del candidato</h3>
                        <p><strong>Nombre:</strong> ' . $nombre_completo . '</p>
                        <p><strong>Email:</strong> ' . $candidato['email'] . '</p>
                        <p><strong>Teléfono:</strong> ' . $candidato['telefono'] . '</p>
                    </div>
                    
                    <h3>Detalles de la prueba</h3>
                    <p><strong>Prueba:</strong> ' . $prueba['titulo'] . '</p>
                    <p><strong>Fecha de realización:</strong> ' . date('d/m/Y H:i', strtotime($sesion['fecha_fin'])) . '</p>
                    
                    <p style="text-align:center; margin-top: 30px;">
                        <a href="' . $panel_url . '" class="btn">Ver perfil del candidato</a>
                    </p>
                    
                    <p>Los resultados de la evaluación están disponibles en el panel de administración. Recuerda considerarlos junto con otros factores en el proceso de selección.</p>
                </div>
                <div class="footer">
                    <p>Este correo es automático, por favor no responda a esta dirección.</p>
                    <p>&copy; ' . date('Y') . ' SolFis Talentos. Sistema de Evaluación Psicométrica.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Construye la plantilla para notificación de coincidencia con vacante
     */
    private function getVacancyMatchEmailTemplate($candidato, $vacante, $porcentaje_match) {
        $nombre = $candidato['nombre'];
        $portalUrl = "https://solfis.com.do/candidato/login.php";
        $vacante_url = "https://solfis.com.do/vacantes/detalle.php?id=" . $vacante['id'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Oportunidad laboral - SolFis Talentos</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #002C6B; color: white; padding: 15px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
                .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777; }
                .job-info { background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; }
                .match { background-color: #FFF8E1; border-radius: 4px; padding: 10px; margin: 20px 0; text-align: center; }
                .match-high { color: #2E7D32; }
                .match-medium { color: #F57F17; }
                .btn { display: inline-block; background-color: #00B1EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SolFis Talentos</h1>
                </div>
                <div class="content">
                    <h2>¡Hemos encontrado una oportunidad para ti!</h2>
                    <p>Hola ' . $nombre . ',</p>
                    <p>Basado en tu perfil y los resultados de tus evaluaciones, hemos identificado una vacante que podría interesarte.</p>
                    
                    <div class="job-info">
                        <h3>' . $vacante['titulo'] . '</h3>
                        <p>' . substr($vacante['descripcion'], 0, 150) . '...</p>
                        <p><strong>Ubicación:</strong> ' . $vacante['ubicacion'] . '</p>
                        <p><strong>Modalidad:</strong> ' . ucfirst($vacante['modalidad']) . '</p>
                    </div>
                    
                    <div class="match">
                        <h3 class="' . ($porcentaje_match >= 75 ? 'match-high' : 'match-medium') . '">
                            Compatibilidad con tu perfil: ' . $porcentaje_match . '%
                        </h3>
                        <p>Este porcentaje se basa en la comparación de tus resultados con el perfil ideal para esta posición.</p>
                    </div>
                    
                    <p>Para conocer más detalles sobre esta vacante y completar tu aplicación, haz clic en el siguiente botón:</p>
                    
                    <p style="text-align:center; margin-top: 30px;">
                        <a href="' . $vacante_url . '" class="btn">Ver detalles de la vacante</a>
                    </p>
                    
                    <p>Si quieres mejorar tu perfil profesional y recibir asesoramiento personalizado sobre tus resultados, contáctanos para conocer nuestros servicios premium de desarrollo profesional.</p>
                </div>
                <div class="footer">
                    <p>Si tienes alguna pregunta, contáctanos a rrhh@solfis.com.do</p>
                    <p>&copy; ' . date('Y') . ' SolFis. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}