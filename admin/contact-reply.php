<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/contact-reply.php - Responder a mensaje de contacto
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';
require_once '../includes/email-sender.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar ID del mensaje
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: contact.php');
    exit;
}

$id = (int)$_GET['id'];

// Instanciar clases necesarias
$contact = new Contact();

// Obtener datos del mensaje
$message = $contact->getMessageById($id);

// Verificar si el mensaje existe
if (!$message) {
    header('Location: contact.php?status=error&message=Mensaje+no+encontrado');
    exit;
}

// Procesar el formulario de respuesta
$success = false;
$error = null;
$replyMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $replyMessage = trim($_POST['reply_message']);
    
    // Validar
    if (empty($replyMessage)) {
        $error = 'El mensaje de respuesta no puede estar vacío.';
    } else {
        try {
            // Enviar email de respuesta
            $emailSender = new EmailSender();
            $result = $emailSender->sendReplyToContact($message, $replyMessage);
            
            if ($result === true) {
                // Actualizar estado del mensaje
                $contact->updateStatus($id, 'replied');
                
                $success = true;
                $replyMessage = ''; // Limpiar después de enviar
            } else {
                $error = $result; // Mostrar el error de PHPMailer
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Título de la página
$pageTitle = 'Responder a Mensaje de Contacto';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Responder a Mensaje de Contacto</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="contact.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la lista
                    </a>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>¡Éxito!</strong> La respuesta ha sido enviada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <div class="mt-3 mb-4">
                <a href="contact.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de mensajes
                </a>
                <a href="contact-view.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> Ver mensaje original
                </a>
            </div>
            <?php else: ?>
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Mensaje original -->
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Mensaje Original</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted">Información del Remitente</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th class="bg-light" style="width: 30%;">Nombre</th>
                                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Email</th>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>">
                                                    <?php echo htmlspecialchars($message['email']); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php if (!empty($message['phone'])): ?>
                                        <tr>
                                            <th class="bg-light">Teléfono</th>
                                            <td>
                                                <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>">
                                                    <?php echo htmlspecialchars($message['phone']); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted">Información del Mensaje</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th class="bg-light" style="width: 30%;">Fecha</th>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($message['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Asunto</th>
                                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <hr>
                                
                                <div>
                                    <h6 class="text-muted">Contenido del Mensaje</h6>
                                    <div class="message-content p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulario de respuesta -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Responder a <?php echo htmlspecialchars($message['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="reply_to" class="form-label">Destinatario</label>
                                        <input type="text" class="form-control" id="reply_to" value="<?php echo htmlspecialchars($message['name']); ?> <<?php echo htmlspecialchars($message['email']); ?>>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reply_subject" class="form-label">Asunto</label>
                                        <input type="text" class="form-control" id="reply_subject" value="Re: <?php echo htmlspecialchars($message['subject']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reply_message" class="form-label">Mensaje de respuesta</label>
                                        <textarea class="form-control" id="reply_message" name="reply_message" rows="10" required><?php echo $replyMessage; ?></textarea>
                                        <div class="form-text">
                                            Escriba su respuesta al mensaje. Se agregará automáticamente el mensaje original como referencia.
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Enviar Respuesta
                                        </button>
                                        <a href="contact-view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                                            Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>