<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/email-settings.php - Configuración de correo
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Obtener configuración actual
$db = Database::getInstance();

// Obtener configuración actual directamente de la base de datos
$settings = array();
$sql = "SELECT * FROM email_settings LIMIT 1";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Valores por defecto
    $settings = array(
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

// Guardar configuraciones
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $formData = array(
        'smtp_host' => trim($_POST['smtp_host']),
        'smtp_port' => (int)$_POST['smtp_port'],
        'smtp_secure' => $_POST['smtp_secure'],
        'smtp_auth' => isset($_POST['smtp_auth']) ? 1 : 0,
        'smtp_username' => trim($_POST['smtp_username']),
        'smtp_password' => trim($_POST['smtp_password']),
        'from_email' => trim($_POST['from_email']),
        'from_name' => trim($_POST['from_name']),
        'reply_to' => trim($_POST['reply_to']),
        'recipient_email' => trim($_POST['recipient_email'])
    );
    
    // Validar campos obligatorios
    if (empty($formData['smtp_host']) || empty($formData['from_email']) || 
        empty($formData['from_name']) || empty($formData['recipient_email'])) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } 
    // Validar emails
    elseif (!filter_var($formData['from_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico del remitente no es válido.';
    }
    elseif (!filter_var($formData['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico del destinatario no es válido.';
    }
    elseif (!empty($formData['reply_to']) && !filter_var($formData['reply_to'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico de respuesta no es válido.';
    }
    // Validar autenticación SMTP
    elseif ($formData['smtp_auth'] && (empty($formData['smtp_username']) || (empty($formData['smtp_password']) && empty($settings['smtp_password'])))) {
        $error = 'Si habilita la autenticación SMTP, debe proporcionar un nombre de usuario y contraseña.';
    }
    // Todo OK, guardar directamente en la base de datos
    else {
        try {
            // Verificar si ya hay un registro
            $checkSql = "SELECT id FROM email_settings LIMIT 1";
            $result = $db->query($checkSql);
            
            if ($result && $result->num_rows > 0) {
                $id = $result->fetch_assoc()['id'];
                
                // Preparar datos para la consulta
                $smtp_host = $db->escape($formData['smtp_host']);
                $smtp_port = (int)$formData['smtp_port'];
                $smtp_secure = $db->escape($formData['smtp_secure']);
                $smtp_auth = $formData['smtp_auth'] ? 1 : 0;
                $smtp_username = $db->escape($formData['smtp_username']);
                $from_email = $db->escape($formData['from_email']);
                $from_name = $db->escape($formData['from_name']);
                $reply_to = $db->escape($formData['reply_to']);
                $recipient_email = $db->escape($formData['recipient_email']);
                
                // Si la contraseña está vacía, no incluirla en la actualización
                if (empty($formData['smtp_password'])) {
                    $sql = "UPDATE email_settings SET 
                            smtp_host = '$smtp_host', 
                            smtp_port = $smtp_port, 
                            smtp_secure = '$smtp_secure', 
                            smtp_auth = $smtp_auth, 
                            smtp_username = '$smtp_username', 
                            from_email = '$from_email', 
                            from_name = '$from_name', 
                            reply_to = '$reply_to', 
                            recipient_email = '$recipient_email', 
                            updated_at = NOW() 
                            WHERE id = $id";
                } else {
                    $smtp_password = $db->escape($formData['smtp_password']);
                    $sql = "UPDATE email_settings SET 
                            smtp_host = '$smtp_host', 
                            smtp_port = $smtp_port, 
                            smtp_secure = '$smtp_secure', 
                            smtp_auth = $smtp_auth, 
                            smtp_username = '$smtp_username', 
                            smtp_password = '$smtp_password', 
                            from_email = '$from_email', 
                            from_name = '$from_name', 
                            reply_to = '$reply_to', 
                            recipient_email = '$recipient_email', 
                            updated_at = NOW() 
                            WHERE id = $id";
                }
            } else {
                // Si no hay registros, insertar uno nuevo
                $smtp_host = $db->escape($formData['smtp_host']);
                $smtp_port = (int)$formData['smtp_port'];
                $smtp_secure = $db->escape($formData['smtp_secure']);
                $smtp_auth = $formData['smtp_auth'] ? 1 : 0;
                $smtp_username = $db->escape($formData['smtp_username']);
                $smtp_password = $db->escape($formData['smtp_password']);
                $from_email = $db->escape($formData['from_email']);
                $from_name = $db->escape($formData['from_name']);
                $reply_to = $db->escape($formData['reply_to']);
                $recipient_email = $db->escape($formData['recipient_email']);
                
                $sql = "INSERT INTO email_settings (
                        smtp_host, smtp_port, smtp_secure, smtp_auth, 
                        smtp_username, smtp_password, from_email, 
                        from_name, reply_to, recipient_email, updated_at
                    ) VALUES (
                        '$smtp_host', $smtp_port, '$smtp_secure', $smtp_auth, 
                        '$smtp_username', '$smtp_password', '$from_email', 
                        '$from_name', '$reply_to', '$recipient_email', NOW()
                    )";
            }
            
            // Ejecutar consulta
            if ($db->query($sql)) {
                $success = true;
                
                // Recargar configuración
                $result = $db->query("SELECT * FROM email_settings LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $settings = $result->fetch_assoc();
                }
                
                // También actualizar la clase EmailSettings si existe
                if (class_exists('EmailSettings')) {
                    $emailSettings = new EmailSettings();
                    if (method_exists($emailSettings, 'updateSettings')) {
                        $emailSettings->updateSettings($formData);
                    }
                }
            } else {
                $error = 'Hubo un problema al guardar la configuración.';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Título de la página
$pageTitle = 'Configuración de Correo';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Configuración de Correo</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="contact.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a mensajes
                    </a>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Configuración guardada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" action="">
                        <h5 class="mb-3">Configuración del Servidor SMTP</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                                    <div class="form-text">Ej: smtp.gmail.com, smtp.office365.com</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">Puerto SMTP <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo (int)$settings['smtp_port']; ?>" required>
                                    <div class="form-text">Ej: 587 (TLS), 465 (SSL)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_secure" class="form-label">Seguridad <span class="text-danger">*</span></label>
                                    <select class="form-select" id="smtp_secure" name="smtp_secure" required>
                                        <option value="tls" <?php echo $settings['smtp_secure'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $settings['smtp_secure'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                    <div class="form-text">Tipo de encriptación</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="smtp_auth" name="smtp_auth" value="1" <?php echo $settings['smtp_auth'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="smtp_auth">Requiere autenticación</label>
                        </div>
                        
                        <div class="row mb-4 smtp-auth-fields" id="smtp_auth_fields" <?php echo !$settings['smtp_auth'] ? 'style="display: none;"' : ''; ?>>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">Usuario SMTP</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                    <div class="form-text">Generalmente es tu dirección de correo completa</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">Contraseña SMTP</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="<?php echo empty($settings['smtp_password']) ? '' : '••••••••••••'; ?>">
                                    <div class="form-text">Deja en blanco para mantener la contraseña actual</div>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Configuración de Correos</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_email" class="form-label">Correo Remitente <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($settings['from_email']); ?>" required>
                                    <div class="form-text">Dirección desde la que se enviarán los correos</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label">Nombre Remitente <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($settings['from_name']); ?>" required>
                                    <div class="form-text">Nombre que verán los destinatarios</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reply_to" class="form-label">Responder a</label>
                                    <input type="email" class="form-control" id="reply_to" name="reply_to" value="<?php echo htmlspecialchars($settings['reply_to']); ?>">
                                    <div class="form-text">Opcional. Si es diferente al remitente</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="recipient_email" class="form-label">Correo Destinatario <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($settings['recipient_email']); ?>" required>
                                    <div class="form-text">Dirección que recibirá los mensajes de contacto</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Toggle de campos de autenticación
document.addEventListener('DOMContentLoaded', function() {
    const smtpAuthCheckbox = document.getElementById('smtp_auth');
    const smtpAuthFields = document.getElementById('smtp_auth_fields');
    
    smtpAuthCheckbox.addEventListener('change', function() {
        if (this.checked) {
            smtpAuthFields.style.display = 'flex';
        } else {
            smtpAuthFields.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>