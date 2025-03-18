<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/contact.php - Gestión de mensajes de contacto (versión mejorada)
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Instanciar clase Contact
$contact = new Contact();

// Procesar acciones
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'mark-read':
            $contact->updateStatus($id, 'read');
            header('Location: contact.php?status=success&message=Mensaje+marcado+como+leído');
            exit;
            break;
            
        case 'mark-replied':
            $contact->updateStatus($id, 'replied');
            header('Location: contact.php?status=success&message=Mensaje+marcado+como+respondido');
            exit;
            break;
            
        case 'archive':
            $contact->updateStatus($id, 'archived');
            header('Location: contact.php?status=success&message=Mensaje+archivado');
            exit;
            break;
            
        case 'unarchive':
            // Verificar si existe el método unarchiveMessage
            if (method_exists($contact, 'unarchiveMessage')) {
                $contact->unarchiveMessage($id);
            } else {
                // Si no existe, usar updateStatus
                $contact->updateStatus($id, 'read');
            }
            header('Location: contact.php?status=success&message=Mensaje+desarchivado');
            exit;
            break;
            
        case 'delete':
            $contact->deleteMessage($id);
            header('Location: contact.php?status=success&message=Mensaje+eliminado');
            exit;
            break;
    }
}

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['filter']) ? $_GET['filter'] : null;

// Obtener mensajes
$messagesData = $contact->getMessages($page, 10, $status);
$messages = $messagesData['messages'];
$totalPages = $messagesData['pages'];

// Contar mensajes por estado para las estadísticas
$newCount = $contact->getMessages(1, 1, 'new')['total'];
$readCount = $contact->getMessages(1, 1, 'read')['total'];
$repliedCount = $contact->getMessages(1, 1, 'replied')['total'];
$archivedCount = $contact->getMessages(1, 1, 'archived')['total'];

// Título de la página
$pageTitle = 'Gestión de Mensajes de Contacto';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mensajes de Contacto</h1>
            </div>
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0">Nuevos</h5>
                                    <h2 class="mt-2 mb-0"><?php echo $newCount; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-envelope fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <a href="?filter=new" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0">Leídos</h5>
                                    <h2 class="mt-2 mb-0"><?php echo $readCount; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-envelope-open fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <a href="?filter=read" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0">Respondidos</h5>
                                    <h2 class="mt-2 mb-0"><?php echo $repliedCount; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-reply fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <a href="?filter=replied" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0">Archivados</h5>
                                    <h2 class="mt-2 mb-0"><?php echo $archivedCount; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-archive fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <a href="?filter=archived" class="text-white mt-3 d-block small">Ver todos <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="btn-group" role="group">
                                <a href="contact.php" class="btn <?php echo !isset($_GET['filter']) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Todos
                                </a>
                                <a href="?filter=new" class="btn <?php echo isset($_GET['filter']) && $_GET['filter'] == 'new' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Nuevos
                                    <?php if ($newCount > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $newCount; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="?filter=read" class="btn <?php echo isset($_GET['filter']) && $_GET['filter'] == 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Leídos
                                </a>
                                <a href="?filter=replied" class="btn <?php echo isset($_GET['filter']) && $_GET['filter'] == 'replied' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Respondidos
                                </a>
                                <a href="?filter=archived" class="btn <?php echo isset($_GET['filter']) && $_GET['filter'] == 'archived' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Archivados
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="test-email.php" class="btn btn-primary me-2">
                                <i class="fas fa-paper-plane"></i> Probar Correo
                            </a>
                            <a href="email-settings.php" class="btn btn-success">
                                <i class="fas fa-cog"></i> Configuración
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de mensajes -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Asunto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No hay mensajes <?php echo isset($_GET['filter']) ? 'con el filtro seleccionado' : ''; ?>.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                    <tr <?php echo $message['status'] == 'new' ? 'class="table-warning fw-bold"' : ''; ?>>
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></td>
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
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="contact-view.php?id=<?php echo $message['id']; ?>" class="btn btn-primary" title="Ver mensaje">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($message['status'] == 'new'): ?>
                                                <a href="?action=mark-read&id=<?php echo $message['id']; ?>" class="btn btn-info" title="Marcar como leído">
                                                    <i class="fas fa-envelope-open"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($message['status'] != 'replied' && $message['status'] != 'archived'): ?>
                                                <a href="contact-reply.php?id=<?php echo $message['id']; ?>" class="btn btn-success" title="Responder">
                                                    <i class="fas fa-reply"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($message['status'] == 'archived'): ?>
                                                <a href="?action=unarchive&id=<?php echo $message['id']; ?>" class="btn btn-info" title="Desarchivar" onclick="return confirm('¿Está seguro que desea desarchivar este mensaje?')">
                                                    <i class="fas fa-box-open"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="?action=archive&id=<?php echo $message['id']; ?>" class="btn btn-warning" title="Archivar" onclick="return confirm('¿Está seguro que desea archivar este mensaje?')">
                                                    <i class="fas fa-archive"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro que desea eliminar este mensaje? Esta acción no se puede deshacer.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">
                                    <i class="fas fa-angle-left"></i> Anterior
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">
                                    Siguiente <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>