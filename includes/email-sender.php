<?php
/**
 * Clase EmailSender - Maneja el envío de correos (versión compatible con PHP antiguo)
 * Este archivo debe guardarse como includes/email-sender.php
 */

// Solo si está disponible PHPMailer, intenta incluirlo
$phpmailer_available = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Si usas Composer
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
} elseif (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
    // Si descargaste manualmente PHPMailer
    require_once __DIR__ . '/phpmailer/src/Exception.php';
    require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/src/SMTP.php';
    $phpmailer_available = true;
}

class EmailSender {
    private $settings;
    private $phpmailer_available;
    
    public function __construct() {
        global $phpmailer_available;
        $this->phpmailer_available = $phpmailer_available;
        $this->loadSettings();
    }
    
    /**
     * Cargar configuración de correo desde la base de datos
     */
    private function loadSettings() {
        // Verificar si existe la clase EmailSettings
        if (class_exists('EmailSettings')) {
            $emailSettings = new EmailSettings();
            $this->settings = $emailSettings->getSettings();
        } else {
            // Valores por defecto
            $this->settings = array(
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_secure' => 'tls',
                'smtp_auth' => 1,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => 'info@solfis.com',
                'from_name' => 'SolFis Contacto',
                'reply_to' => 'info@solfis.com',
                'recipient_email' => 'contacto@solfis.com'
            );
        }
    }
    
    /**
     * Enviar un mensaje de contacto
     * @param array $contactData Datos del mensaje
     * @param bool $isTest Indica si es un envío de prueba
     * @return mixed true en caso de éxito, string con el error en caso contrario
     */
    public function sendContactMessage($contactData, $isTest = false) {
        // Si PHPMailer está disponible, intentar enviar con él
        if ($this->phpmailer_available) {
            try {
                // Usar PHPMailer con namespace o sin namespace dependiendo de la versión
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    if (class_exists('PHPMailer\PHPMailer\SMTP')) {
                        $SMTPClass = '\PHPMailer\PHPMailer\SMTP';
                        $ExceptionClass = '\PHPMailer\PHPMailer\Exception';
                    } else {
                        $SMTPClass = 'SMTP';
                        $ExceptionClass = 'Exception';
                    }
                } else {
                    $mail = new \PHPMailer(true);
                    $SMTPClass = 'SMTP';
                    $ExceptionClass = 'Exception';
                }
                
                // Configuración del servidor
                $mail->isSMTP();
                $mail->Host = $this->settings['smtp_host'];
                $mail->Port = $this->settings['smtp_port'];
                
                if ($this->settings['smtp_secure'] === 'tls') {
                    $mail->SMTPSecure = 'tls';
                } elseif ($this->settings['smtp_secure'] === 'ssl') {
                    $mail->SMTPSecure = 'ssl';
                }
                
                if ($this->settings['smtp_auth']) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $this->settings['smtp_username'];
                    $mail->Password = $this->settings['smtp_password'];
                } else {
                    $mail->SMTPAuth = false;
                }
                
                // Activar modo de depuración para pruebas
                if ($isTest) {
                    $mail->SMTPDebug = 2; // Usar constante numérica en lugar de constante SMTP
                    $mail->Debugoutput = function($str, $level) {
                        // Guardar la información de depuración
                        $logFile = __DIR__ . '/../logs/smtp_debug.log';
                        $logDir = dirname($logFile);
                        
                        // Crear directorio de logs si no existe
                        if (!file_exists($logDir)) {
                            mkdir($logDir, 0755, true);
                        }
                        
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [$level] $str\n", FILE_APPEND);
                    };
                }
                
                // Remitentes y destinatarios
                $mail->setFrom($this->settings['from_email'], $this->settings['from_name']);
                
                if (!empty($this->settings['reply_to'])) {
                    $mail->addReplyTo($this->settings['reply_to']);
                }
                
                if ($isTest) {
                    // Si es una prueba, usar la dirección proporcionada
                    $mail->addAddress($contactData['email']);
                } else {
                    // En caso normal, usar la dirección configurada como destinatario
                    $mail->addAddress($this->settings['recipient_email']);
                }
                
                // Contenido
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $isTest ? 'Prueba de configuración de correo' : 'Mensaje de contacto: ' . $contactData['subject'];
                
                // Crear cuerpo del correo
                $body = $this->createContactEmailBody($contactData, $isTest);
                $mail->Body = $body;
                $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
                
                // Enviar
                $mail->send();
                
                // Registrar en log
                $this->logEmail($contactData, true, $isTest);
                
                return true;
            } catch (Exception $e) {
                // Registrar error en log
                $this->logEmail($contactData, false, $isTest, isset($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage());
                
                // Si es modo de prueba, devolver mensaje de error detallado
                if ($isTest) {
                    return 'Error al enviar el correo: ' . (isset($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage());
                }
                
                return 'Error al enviar el correo. Consulta el registro para más detalles.';
            }
        } else {
            // Si PHPMailer no está disponible, registrar en log
            $this->logEmail($contactData, false, $isTest, 'PHPMailer no está instalado');
            
            // Si es modo de prueba, devolver mensaje de error
            if ($isTest) {
                return 'PHPMailer no está instalado. Por favor, instala PHPMailer para poder enviar correos.';
            }
            
            return false;
        }
    }
    
    /**
     * Crear el cuerpo HTML del correo de contacto
     */
    private function createContactEmailBody($data, $isTest = false) {
        if ($isTest) {
            $html = '<h2>Prueba de configuración de correo</h2>';
            $html .= '<p>Este es un correo de prueba para verificar la configuración SMTP.</p>';
            $html .= '<p><strong>Fecha y hora:</strong> ' . date('d/m/Y H:i:s') . '</p>';
            $html .= '<hr>';
            $html .= '<p>Si has recibido este correo, significa que la configuración de correo en tu sitio web está funcionando correctamente.</p>';
            $html .= '<p><strong>Configuración utilizada:</strong></p>';
            $html .= '<ul>';
            $html .= '<li><strong>Servidor SMTP:</strong> ' . htmlspecialchars($this->settings['smtp_host']) . '</li>';
            $html .= '<li><strong>Puerto:</strong> ' . htmlspecialchars($this->settings['smtp_port']) . '</li>';
            $html .= '<li><strong>Seguridad:</strong> ' . htmlspecialchars($this->settings['smtp_secure']) . '</li>';
            $html .= '<li><strong>Autenticación:</strong> ' . ($this->settings['smtp_auth'] ? 'Habilitada' : 'Deshabilitada') . '</li>';
            $html .= '<li><strong>De (email):</strong> ' . htmlspecialchars($this->settings['from_email']) . '</li>';
            $html .= '<li><strong>De (nombre):</strong> ' . htmlspecialchars($this->settings['from_name']) . '</li>';
            $html .= '</ul>';
            return $html;
        } else {
            $html = '<h2>Nuevo mensaje de contacto</h2>';
            $html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>';
            $html .= '<p><strong>Nombre:</strong> ' . htmlspecialchars($data['name']) . '</p>';
            $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($data['email']) . '</p>';
            
            if (!empty($data['phone'])) {
                $html .= '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['phone']) . '</p>';
            }
            
            $html .= '<p><strong>Asunto:</strong> ' . htmlspecialchars($data['subject']) . '</p>';
            $html .= '<h3>Mensaje:</h3>';
            $html .= '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px;">';
            $html .= nl2br(htmlspecialchars($data['message']));
            $html .= '</div>';
            
            $html .= '<hr>';
            $html .= '<p><small>Este mensaje fue enviado desde el formulario de contacto de SolFis.</small></p>';
            $html .= '<p><small>IP: ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Desconocida') . '</small></p>';
            
            return $html;
        }
    }
    
    /**
     * Registrar intento de envío de correo en el log
     */
    private function logEmail($data, $success, $isTest = false, $errorInfo = '') {
        $logFile = __DIR__ . '/../logs/email_log.txt';
        $logDir = dirname($logFile);
        
        // Crear directorio de logs si no existe
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Crear mensaje de log
        $logMessage = date('Y-m-d H:i:s') . " - ";
        $logMessage .= $isTest ? "[PRUEBA] " : "";
        $logMessage .= $success ? "ÉXITO" : "ERROR";
        $logMessage .= " - Correo a: " . ($isTest ? $data['email'] : $this->settings['recipient_email']) . "\n";
        
        if (!$success) {
            $logMessage .= "Error: " . $errorInfo . "\n";
        }
        
        $logMessage .= "Asunto: " . ($isTest ? "Prueba de configuración" : $data['subject']) . "\n";
        $logMessage .= "De: " . $data['name'] . " <" . $data['email'] . ">\n";
        $logMessage .= "IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Desconocida') . "\n\n";
        
        // Añadir al archivo de log (o crearlo si no existe)
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Enviar una respuesta al contacto
     */
    public function sendReplyToContact($contactData, $replyMessage) {
        // Verificar si PHPMailer está disponible
        if ($this->phpmailer_available) {
            try {
                // Usar PHPMailer con namespace o sin namespace dependiendo de la versión
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                } else {
                    $mail = new \PHPMailer(true);
                }
                
                // Configuración del servidor
                $mail->isSMTP();
                $mail->Host = $this->settings['smtp_host'];
                $mail->Port = $this->settings['smtp_port'];
                
                if ($this->settings['smtp_secure'] === 'tls') {
                    $mail->SMTPSecure = 'tls';
                } elseif ($this->settings['smtp_secure'] === 'ssl') {
                    $mail->SMTPSecure = 'ssl';
                }
                
                if ($this->settings['smtp_auth']) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $this->settings['smtp_username'];
                    $mail->Password = $this->settings['smtp_password'];
                } else {
                    $mail->SMTPAuth = false;
                }
                
                // Remitentes y destinatarios
                $mail->setFrom($this->settings['from_email'], $this->settings['from_name']);
                
                if (!empty($this->settings['reply_to'])) {
                    $mail->addReplyTo($this->settings['reply_to']);
                }
                
                // Destinatario (el contacto)
                $mail->addAddress($contactData['email'], $contactData['name']);
                
                // CC para tener una copia
                $mail->addCC($this->settings['recipient_email']);
                
                // Contenido
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Re: ' . $contactData['subject'];
                
                // Crear cuerpo del correo
                $body = $this->createReplyEmailBody($contactData, $replyMessage);
                $mail->Body = $body;
                $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
                
                // Enviar
                $mail->send();
                
                // Guardar la respuesta en la base de datos o en un registro
                $this->saveReply($contactData['id'], $replyMessage);
                
                return true;
            } catch (Exception $e) {
                // Registrar error en log
                $logFile = __DIR__ . '/../logs/email_log.txt';
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR AL ENVIAR RESPUESTA: " . $e->getMessage() . "\n", FILE_APPEND);
                
                return 'Error al enviar la respuesta: ' . (isset($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage());
            }
        } else {
            // Si PHPMailer no está disponible, simular éxito pero registrar en log
            $logFile = __DIR__ . '/../logs/email_log.txt';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - INTENTO DE ENVIAR RESPUESTA SIN PHPMAILER\n", FILE_APPEND);
            
            // Guardar la respuesta en la base de datos o en un registro
            $this->saveReply($contactData['id'], $replyMessage);
            
            return true;
        }
    }
    
    /**
     * Guardar la respuesta en la base de datos o en un registro
     */
    private function saveReply($contactId, $replyMessage) {
        global $phpmailer_available;
        
        // Crear objeto para guardar la respuesta
        $reply = array(
            'contact_id' => $contactId,
            'reply_message' => $replyMessage,
            'reply_date' => date('Y-m-d H:i:s'),
            'reply_sent' => $phpmailer_available
        );
        
        // Guardar en un archivo JSON en logs
        $logFile = __DIR__ . '/../logs/replies.json';
        $logDir = dirname($logFile);
        
        // Crear directorio de logs si no existe
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Cargar respuestas existentes
        $replies = array();
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $replies = $decoded;
                }
            }
        }
        
        // Añadir nueva respuesta
        $replies[] = $reply;
        
        // Guardar archivo
        file_put_contents($logFile, json_encode($replies, JSON_PRETTY_PRINT));
        
        // Si existe la clase Contact y tiene el método saveReply, usar ese método
        if (class_exists('Contact')) {
            $contact = new Contact();
            if (method_exists($contact, 'saveReply')) {
                $contact->saveReply($contactId, $replyMessage);
            }
        }
    }
    
    /**
     * Crear el cuerpo HTML del correo de respuesta
     */
    private function createReplyEmailBody($contactData, $replyMessage) {
        $html = '<h2>Respuesta a su mensaje</h2>';
        $html .= '<p>Estimado/a ' . htmlspecialchars($contactData['name']) . ',</p>';
        $html .= '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        $html .= nl2br(htmlspecialchars($replyMessage));
        $html .= '</div>';
        
        $html .= '<h3>Su mensaje original:</h3>';
        $html .= '<div style="border-left: 4px solid #ccc; padding-left: 15px; margin-top: 10px;">';
        $html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s', strtotime($contactData['created_at'])) . '</p>';
        $html .= '<p><strong>Asunto:</strong> ' . htmlspecialchars($contactData['subject']) . '</p>';
        $html .= '<p>' . nl2br(htmlspecialchars($contactData['message'])) . '</p>';
        $html .= '</div>';
        
        $html .= '<hr>';
        $html .= '<p><small>Atentamente,</small></p>';
        $html .= '<p><small>' . htmlspecialchars($this->settings['from_name']) . '</small></p>';
        
        return $html;
    }
    
    /**
     * Obtener las respuestas a un mensaje específico
     */
    public function getMessageReplies($contactId) {
        $replies = array();
        
        // Verificar si existe el archivo de respuestas
        $logFile = __DIR__ . '/../logs/replies.json';
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                $allReplies = json_decode($content, true);
                if (is_array($allReplies)) {
                    // Filtrar las respuestas para este mensaje
                    foreach ($allReplies as $reply) {
                        if (isset($reply['contact_id']) && $reply['contact_id'] == $contactId) {
                            $replies[] = $reply;
                        }
                    }
                }
            }
        }
        
        // Ordenar por fecha
        usort($replies, function($a, $b) {
            return strtotime($b['reply_date']) - strtotime($a['reply_date']);
        });
        
        return $replies;
    }
}

// Si la clase EmailSettings no existe, crearla
if (!class_exists('EmailSettings')) {
    class EmailSettings {
        private $db;
        
        public function __construct() {
            if (class_exists('Database')) {
                $this->db = Database::getInstance();
                
                // Crear tabla si no existe
                $this->createEmailSettingsTable();
            }
        }
        
        /**
         * Crear la tabla de configuración si no existe
         */
        private function createEmailSettingsTable() {
            if (!$this->db) return;
            
            $sql = "CREATE TABLE IF NOT EXISTS email_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                smtp_host VARCHAR(100) NOT NULL,
                smtp_port INT NOT NULL DEFAULT 587,
                smtp_secure ENUM('tls', 'ssl') NOT NULL DEFAULT 'tls',
                smtp_auth TINYINT(1) NOT NULL DEFAULT 1,
                smtp_username VARCHAR(100) DEFAULT NULL,
                smtp_password VARCHAR(255) DEFAULT NULL,
                from_email VARCHAR(100) NOT NULL,
                from_name VARCHAR(100) NOT NULL,
                reply_to VARCHAR(100) DEFAULT NULL,
                recipient_email VARCHAR(100) NOT NULL,
                updated_at DATETIME NOT NULL
            )";
            
            $this->db->query($sql);
            
            // Insertar configuración por defecto si no hay registros
            $checkSql = "SELECT COUNT(*) as count FROM email_settings";
            $result = $this->db->query($checkSql);
            
            if ($result && $result->fetch_assoc()['count'] == 0) {
                $defaultSql = "INSERT INTO email_settings (
                    smtp_host, 
                    smtp_port, 
                    smtp_secure, 
                    smtp_auth, 
                    smtp_username, 
                    smtp_password, 
                    from_email, 
                    from_name, 
                    reply_to, 
                    recipient_email, 
                    updated_at
                ) VALUES (
                    'smtp.example.com', 
                    587, 
                    'tls', 
                    1, 
                    'user@example.com', 
                    '', 
                    'info@solfis.com', 
                    'SolFis Contacto', 
                    'info@solfis.com', 
                    'contacto@solfis.com', 
                    NOW()
                )";
                
                $this->db->query($defaultSql);
            }
        }
        
        /**
         * Obtener configuración de correo
         */
        public function getSettings() {
            if (!$this->db) {
                return array(
                    'smtp_host' => 'smtp.example.com',
                    'smtp_port' => 587,
                    'smtp_secure' => 'tls',
                    'smtp_auth' => 1,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'from_email' => 'info@solfis.com',
                    'from_name' => 'SolFis Contacto',
                    'reply_to' => 'info@solfis.com',
                    'recipient_email' => 'contacto@solfis.com'
                );
            }
            
            $sql = "SELECT * FROM email_settings LIMIT 1";
            $result = $this->db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            // Si no hay configuración, devolver valores por defecto
            return array(
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_secure' => 'tls',
                'smtp_auth' => 1,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => 'info@solfis.com',
                'from_name' => 'SolFis Contacto',
                'reply_to' => 'info@solfis.com',
                'recipient_email' => 'contacto@solfis.com'
            );
        }
        
		/**
		 * Actualizar configuración de correo
		 */
		public function updateSettings($data) {
			if (!$this->db) return false;
			
			$smtpHost = $this->db->escape($data['smtp_host']);
			$smtpPort = (int)$data['smtp_port'];
			$smtpSecure = $this->db->escape($data['smtp_secure']);
			$smtpAuth = isset($data['smtp_auth']) ? 1 : 0;
			$smtpUsername = $this->db->escape($data['smtp_username']);
			$smtpPassword = $this->db->escape($data['smtp_password']);
			$fromEmail = $this->db->escape($data['from_email']);
			$fromName = $this->db->escape($data['from_name']);
			$replyTo = $this->db->escape($data['reply_to']);
			$recipientEmail = $this->db->escape($data['recipient_email']);
			
			// Verificar si ya hay un registro
			$checkSql = "SELECT id FROM email_settings LIMIT 1";
			$result = $this->db->query($checkSql);
			
			if ($result && $result->num_rows > 0) {
				$id = $result->fetch_assoc()['id'];
				
				// Si la contraseña está vacía, no actualizarla (mantener la existente)
				$sql = "UPDATE email_settings SET 
						smtp_host = '$smtpHost', 
						smtp_port = $smtpPort, 
						smtp_secure = '$smtpSecure', 
						smtp_auth = $smtpAuth, 
						smtp_username = '$smtpUsername', ";
				
				// Solo incluir la contraseña si no está vacía
				if (!empty($smtpPassword)) {
					$sql .= "smtp_password = '$smtpPassword', ";
				}
				
				$sql .= "from_email = '$fromEmail', 
						from_name = '$fromName', 
						reply_to = '$replyTo', 
						recipient_email = '$recipientEmail', 
						updated_at = NOW() 
						WHERE id = $id";
			} else {
				$sql = "INSERT INTO email_settings (
						smtp_host, smtp_port, smtp_secure, smtp_auth, 
						smtp_username, smtp_password, from_email, 
						from_name, reply_to, recipient_email, updated_at
					) VALUES (
						'$smtpHost', $smtpPort, '$smtpSecure', $smtpAuth, 
						'$smtpUsername', '$smtpPassword', '$fromEmail', 
						'$fromName', '$replyTo', '$recipientEmail', NOW()
					)";
			}
			
			return $this->db->query($sql);
		}
    }
}