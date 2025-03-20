<?php
/**
 * Script para probar la configuración de correo electrónico
 * Este archivo debería guardarse como admin/test-email.php
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';
require_once '../includes/email-sender.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Variables para las respuestas
$success = false;
$error = null;
$details = null;

// Procesar la solicitud de prueba
if (isset($_POST['test_email'])) {
    $testEmail = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, introduce un correo electrónico válido para la prueba.';
    } else {
        try {
            // Crear instancia de EmailSender
            $emailSender = new EmailSender();
            
            // Crear datos de prueba
            $testData = [
                'name' => 'Usuario de Prueba',
                'email' => $testEmail,
                'phone' => '555-1234',
                'subject' => 'Correo de prueba de configuración',
                'message' => 'Este es un correo de prueba para verificar la configuración SMTP del sistema. Si recibes este mensaje, la configuración es correcta.'
            ];
            
            // Intentar enviar correo
            $result = $emailSender->sendContactMessage($testData, true);
            
            if ($result === true) {
                $success = true;
                $details = 'El correo de prueba se ha enviado correctamente a ' . $testEmail . '. Por favor, revisa tu bandeja de entrada (y la carpeta de spam si es necesario).';
            } else {
                $error = 'Error al enviar el correo de prueba: ' . $result;
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener la configuración actual
$emailSettings = new EmailSettings();
$settings = $emailSettings->getSettings();

// Título de la página
$pageTitle = 'Probar Configuración de Correo';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Probar Configuración de Correo</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="contact.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a mensajes
                    </a>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="fas fa-check-circle"></i> ¡Prueba exitosa!</h4>
                <p><?php echo $details; ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error en la prueba</h4>
                <p><?php echo $error; ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Enviar Correo de Prueba</h5>
                </div>
                <div class="card-body">
                    <p>Utiliza esta herramienta para verificar si la configuración de correo electrónico funciona correctamente. Se enviará un correo de prueba a la dirección que especifiques.</p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="test_email" class="form-label">Correo electrónico para la prueba</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" value="<?php echo $settings['recipient_email']; ?>" required>
                            <div class="form-text">El correo de prueba se enviará a esta dirección.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar Correo de Prueba
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Configuración Actual</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Configuración del Servidor</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="bg-light" style="width: 30%;">Servidor SMTP</th>
                                    <td><?php echo htmlspecialchars($settings['smtp_host']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Puerto</th>
                                    <td><?php echo (int)$settings['smtp_port']; ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Seguridad</th>
                                    <td><?php echo htmlspecialchars($settings['smtp_secure']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Autenticación</th>
                                    <td><?php echo $settings['smtp_auth'] ? 'Habilitada' : 'Deshabilitada'; ?></td>
                                </tr>
                                <?php if ($settings['smtp_auth']): ?>
                                <tr>
                                    <th class="bg-light">Usuario</th>
                                    <td><?php echo htmlspecialchars($settings['smtp_username']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Contraseña</th>
                                    <td>••••••••</td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Configuración de Correos</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="bg-light" style="width: 30%;">De (email)</th>
                                    <td><?php echo htmlspecialchars($settings['from_email']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">De (nombre)</th>
                                    <td><?php echo htmlspecialchars($settings['from_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Responder a</th>
                                    <td><?php echo htmlspecialchars($settings['reply_to']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Destinatario</th>
                                    <td><?php echo htmlspecialchars($settings['recipient_email']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Problemas Comunes</h5>
                    <ul>
                        <li><strong>Error de conexión:</strong> Verifica que el servidor SMTP y el puerto sean correctos.</li>
                        <li><strong>Error de autenticación:</strong> Revisa el usuario y contraseña.</li>
                        <li><strong>Correo no recibido:</strong> Revisa la carpeta de spam o junk.</li>
                        <li><strong>Problemas con Gmail:</strong> Si estás usando Gmail, asegúrate de habilitar "Acceso de aplicaciones menos seguras" o crear una contraseña de aplicación.</li>
                        <li><strong>Firewall o restricciones:</strong> Algunos servidores web tienen bloqueados los puertos SMTP o restringen el envío de correos.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>