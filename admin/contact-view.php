<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/contact-view.php - Ver mensaje de contacto (versión mejorada)
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

// Instanciar clase Contact
$contact = new Contact();

// Obtener datos del mensaje
$message = $contact->getMessageById($id);

// Verificar si el mensaje existe
if (!$message) {
    header('Location: contact.php?status=error&message=Mensaje+no+encontrado');
    exit;
}

// Si el mensaje es nuevo, marcarlo como leído
if ($message['status'] == 'new') {
    $contact->updateStatus($id, 'read');
    $message['status'] = 'read';
}

// Obtener respuestas a este mensaje
$replies = [];
if (method_exists($contact, 'getMessageReplies')) {
    $replies = $contact->getMessageReplies($id);
} elseif (class_exists('EmailSender')) {
    // Si la clase Contact no tiene el método, intentar con EmailSender
    $emailSender = new EmailSender();
    if (method_exists($emailSender, 'getMessageReplies')) {
        $replies = $emailSender->getMessageReplies($id);
    }
}

// Título de la página
$pageTitle = 'Ver Mensaje de Contacto';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Ver Mensaje de Contacto</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="contact.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la lista
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalles del Mensaje</h5>
                        <div>
                            <?php if ($message['status'] == 'new'): ?>
                                <span class="badge bg-danger">Nuevo</span>
                            <?php elseif ($message['status'] == 'read'): ?>
                                <span class="badge bg-warning text-dark">Leído</span>
                            <?php elseif ($message['status'] == 'replied'): ?>
                                <span class="badge bg-success">Respondido</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Archivado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
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
                                <tr>
                                    <th class="bg-light">IP</th>
                                    <td><?php echo htmlspecialchars($message['ip_address']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
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
                                <tr>
                                    <th class="bg-light">Estado</th>
                                    <td>
                                        <?php if ($message['status'] == 'new'): ?>
                                            <span class="badge bg-danger">Nuevo</span>
                                        <?php elseif ($message['status'] == 'read'): ?>
                                            <span class="badge bg-warning text-dark">Leído</span>
                                        <?php elseif ($message['status'] == 'replied'): ?>
                                            <span class="badge bg-success">Respondido</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Archivado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Última actualización</th>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($message['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Contenido del Mensaje</h6>
                            <div class="message-content p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group">
                        <?php if ($message['status'] != 'replied' && $message['status'] != 'archived'): ?>
                        <a href="contact-reply.php?id=<?php echo $message['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-reply"></i> Responder
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($message['status'] == 'archived'): ?>
                        <a href="contact.php?action=unarchive&id=<?php echo $message['id']; ?>" class="btn btn-info" onclick="return confirm('¿Está seguro que desea desarchivar este mensaje?')">
                            <i class="fas fa-archive"></i> Desarchivar
                        </a>
                        <?php else: ?>
                        <a href="contact.php?action=archive&id=<?php echo $message['id']; ?>" class="btn btn-warning" onclick="return confirm('¿Está seguro que desea archivar este mensaje?')">
                            <i class="fas fa-archive"></i> Archivar
                        </a>
                        <?php endif; ?>
                        
                        <a href="contact.php?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro que desea eliminar este mensaje? Esta acción no se puede deshacer.')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($replies)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-reply-all"></i> Respuestas Anteriores</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($replies as $reply): ?>
                    <div class="reply-item mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="fas fa-reply"></i> 
                                Respuesta enviada: 
                                <?php 
                                    if (isset($reply['created_at'])) {
                                        echo date('d/m/Y H:i:s', strtotime($reply['created_at']));
                                    } elseif (isset($reply['reply_date'])) {
                                        echo date('d/m/Y H:i:s', strtotime($reply['reply_date']));
                                    } else {
                                        echo 'Fecha desconocida';
                                    }
                                ?>
                            </h6>
                            <?php if (isset($reply['reply_sent'])): ?>
                                <?php if ($reply['reply_sent']): ?>
                                    <span class="badge bg-success">Enviado por email</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Guardado sin enviar</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <?php 
                                if (isset($reply['reply_content'])) {
                                    echo nl2br(htmlspecialchars($reply['reply_content']));
                                } elseif (isset($reply['reply_message'])) {
                                    echo nl2br(htmlspecialchars($reply['reply_message']));
                                }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>