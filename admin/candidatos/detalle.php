<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/detalle.php - Ver detalles de un candidato
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

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener ID
$id = (int)$_GET['id'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();
$applicationManager = new ApplicationManager();

// Obtener candidato por ID
$candidato = $candidateManager->getCandidateById($id);

// Si no existe el candidato, redirigir
if (!$candidato) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener aplicaciones del candidato
$aplicaciones = $applicationManager->getApplicationsByCandidate($id);

// Obtener notas del candidato (si la función existe)
$notas = [];
if (method_exists($candidateManager, 'getCandidateNotes')) {
    $notas = $candidateManager->getCandidateNotes($id);
}

// Recopilar todas las entrevistas programadas
$entrevistas = [];
foreach ($aplicaciones as $aplicacion) {
    if (method_exists($applicationManager, 'getScheduledInterviews')) {
        $entrevistasApp = $applicationManager->getScheduledInterviews($aplicacion['id']);
        foreach ($entrevistasApp as $ent) {
            $ent['vacante_titulo'] = $aplicacion['vacante_titulo'];
            $ent['aplicacion_id'] = $aplicacion['id'];
            $entrevistas[] = $ent;
        }
    }
}

// Ordenar entrevistas por fecha
usort($entrevistas, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Definir la pestaña activa basada en el parámetro GET o defaultear a 'applications'
$activeTab = 'applications';
if (isset($_GET['tab']) && in_array($_GET['tab'], ['applications', 'notes', 'interviews', 'history'])) {
    $activeTab = $_GET['tab'];
}

// Mensaje de notificación
$notification = null;
if (isset($_GET['message'])) {
    $messages = [
        'note-added' => ['type' => 'success', 'text' => 'Nota añadida correctamente.'],
        'note-updated' => ['type' => 'success', 'text' => 'Nota actualizada correctamente.'],
        'status-updated' => ['type' => 'success', 'text' => 'Estado actualizado correctamente.'],
        'interview-scheduled' => ['type' => 'success', 'text' => 'Entrevista programada correctamente.'],
        'candidate-updated' => ['type' => 'success', 'text' => 'Información del candidato actualizada correctamente.']
    ];

    if (array_key_exists($_GET['message'], $messages)) {
        $notification = $messages[$_GET['message']];
    }
}

// Título de la página
$pageTitle = 'Detalles del Candidato - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles del Candidato</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Candidatos
                            </a>
                            <?php if (!empty($candidato['email'])): ?>
                            <a href="mailto:<?php echo $candidato['email']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($notification): ?>
                <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $notification['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <!-- Información del Candidato -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 text-center">
                                    <div class="avatar-circle">
                                        <span class="avatar-initials">
                                            <?php 
                                            echo substr($candidato['nombre'], 0, 1) . substr($candidato['apellido'], 0, 1); 
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <h4 class="text-center mb-3">
                                    <?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?>
                                </h4>
                                
                                <div class="candidate-info">
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-envelope"></i></span>
                                        <span class="info-value">
                                            <a href="mailto:<?php echo $candidato['email']; ?>"><?php echo $candidato['email']; ?></a>
                                        </span>
                                    </div>
                                    
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-phone"></i></span>
                                        <span class="info-value">
                                            <?php echo $candidato['telefono'] ?: 'No disponible'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-map-marker-alt"></i></span>
                                        <span class="info-value">
                                            <?php echo $candidato['ubicacion'] ?: 'No especificada'; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($candidato['linkedin'])): ?>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fab fa-linkedin"></i></span>
                                        <span class="info-value">
                                            <a href="<?php echo $candidato['linkedin']; ?>" target="_blank">
                                                Ver perfil de LinkedIn
                                            </a>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-calendar-alt"></i></span>
                                        <span class="info-value">
                                            Registrado: <?php echo date('d/m/Y', strtotime($candidato['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Editar Información
                                </a>
                            </div>
                        </div>
                        
                        <!-- Currículum Vitae -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Currículum Vitae</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($candidato['cv_path'])): ?>
                                    <div class="mb-3">
                                        <strong>CV Local:</strong>
                                        <div class="mt-2">
                                            <a href="../../uploads/resumes/<?php echo $candidato['cv_path']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-pdf"></i> Ver CV local
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($candidato['drive_view_link'])): ?>
                                    <div class="mb-3">
                                        <strong>CV en Google Drive:</strong>
                                        <div class="mt-2">
                                            <a href="<?php echo $candidato['drive_view_link']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                <i class="fab fa-google-drive"></i> Ver en Google Drive
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($candidato['cv_path']) && empty($candidato['drive_view_link'])): ?>
                                    <div class="alert alert-info mb-0">
                                        No se ha cargado ningún currículum para este candidato.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Acciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="mailto:<?php echo $candidato['email']; ?>" class="btn btn-primary">
                                        <i class="fas fa-envelope"></i> Enviar Email
                                    </a>
                                    
                                    <?php if (!empty($candidato['telefono'])): ?>
                                    <a href="tel:<?php echo $candidato['telefono']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-phone"></i> Llamar
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($candidato['drive_view_link'])): ?>
                                    <a href="<?php echo $candidato['drive_view_link']; ?>" target="_blank" class="btn btn-outline-success">
                                        <i class="fab fa-google-drive"></i> Ver Documentos en Drive
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#addNoteForm" aria-expanded="false">
                                        <i class="fas fa-sticky-note"></i> Agregar Nota
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Pestañas para organizar la información -->
                        <ul class="nav nav-tabs" id="candidateDetailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $activeTab === 'applications' ? 'active' : ''; ?>" 
                                        id="applications-tab" data-bs-toggle="tab" data-bs-target="#applications" 
                                        type="button" role="tab" aria-controls="applications" 
                                        aria-selected="<?php echo $activeTab === 'applications' ? 'true' : 'false'; ?>">
                                    Aplicaciones (<?php echo count($aplicaciones); ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $activeTab === 'notes' ? 'active' : ''; ?>" 
                                        id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" 
                                        type="button" role="tab" aria-controls="notes" 
                                        aria-selected="<?php echo $activeTab === 'notes' ? 'true' : 'false'; ?>">
                                    Notas (<?php echo count($notas); ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $activeTab === 'interviews' ? 'active' : ''; ?>" 
                                        id="interviews-tab" data-bs-toggle="tab" data-bs-target="#interviews" 
                                        type="button" role="tab" aria-controls="interviews" 
                                        aria-selected="<?php echo $activeTab === 'interviews' ? 'true' : 'false'; ?>">
                                    Entrevistas (<?php echo count($entrevistas); ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $activeTab === 'history' ? 'active' : ''; ?>" 
                                        id="history-tab" data-bs-toggle="tab" data-bs-target="#history" 
                                        type="button" role="tab" aria-controls="history" 
                                        aria-selected="<?php echo $activeTab === 'history' ? 'true' : 'false'; ?>">
                                    Historial
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom mb-4" id="candidateDetailTabContent">
                            <!-- Formulario colapsable para agregar notas -->
                            <div class="collapse mb-4" id="addNoteForm">
                                <div class="card card-body">
                                    <h5 class="card-title mb-3">Agregar Nueva Nota</h5>
                                    <form action="guardar-nota.php" method="post">
                                        <input type="hidden" name="candidato_id" value="<?php echo $candidato['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="contenido" name="contenido" rows="3" required></textarea>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#addNoteForm">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Guardar Nota</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        
                            <!-- Pestaña de Aplicaciones -->
                            <div class="tab-pane fade <?php echo $activeTab === 'applications' ? 'show active' : ''; ?>" 
                                 id="applications" role="tabpanel" aria-labelledby="applications-tab">
                                <?php if (empty($aplicaciones)): ?>
                                <div class="alert alert-info">
                                    Este candidato no ha aplicado a ninguna vacante.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Vacante</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aplicaciones as $aplicacion): ?>
                                            <tr>
                                                <td>
                                                    <a href="../vacantes/vacante-editar.php?id=<?php echo $aplicacion['vacante_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusStyle = '';
                                                    switch($aplicacion['estado']) {
                                                        case 'recibida': $statusStyle = 'bg-info'; break;
                                                        case 'revision': $statusStyle = 'bg-primary'; break;
                                                        case 'entrevista': $statusStyle = 'bg-warning'; break;
                                                        case 'prueba': $statusStyle = 'bg-warning'; break;
                                                        case 'oferta': $statusStyle = 'bg-success'; break;
                                                        case 'contratado': $statusStyle = 'bg-success'; break;
                                                        case 'rechazado': $statusStyle = 'bg-danger'; break;
                                                        default: $statusStyle = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusStyle; ?>">
                                                        <?php 
                                                        $statuses = [
                                                            'recibida' => 'Recibida',
                                                            'revision' => 'En Revisión',
                                                            'entrevista' => 'Entrevista',
                                                            'prueba' => 'Prueba',
                                                            'oferta' => 'Oferta',
                                                            'contratado' => 'Contratado',
                                                            'rechazado' => 'Rechazado'
                                                        ];
                                                        echo $statuses[$aplicacion['estado']] ?? ucfirst($aplicacion['estado']); 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../aplicaciones/detalle.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                           onclick="abrirModalCambioEstado(
                                                             <?php echo $aplicacion['id']; ?>, 
                                                             <?php echo $candidato['id']; ?>, 
                                                             '<?php echo $aplicacion['estado']; ?>'
                                                           )" 
                                                           title="Cambiar estado">
                                                           <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                           onclick="abrirModalEntrevista(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)" 
                                                           title="Programar entrevista">
                                                           <i class="fas fa-calendar-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                           onclick="abrirModalNotaAplicacion(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)" 
                                                           title="Agregar nota a la aplicación">
                                                           <i class="fas fa-sticky-note"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pestaña de Notas -->
                            <div class="tab-pane fade <?php echo $activeTab === 'notes' ? 'show active' : ''; ?>" 
                                 id="notes" role="tabpanel" aria-labelledby="notes-tab">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Notas del candidato</h5>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addNoteForm" aria-expanded="false">
                                        <i class="fas fa-plus"></i> Añadir Nota
                                    </button>
                                </div>
                                
                                <!-- Listado de notas -->
                                <?php if (empty($notas)): ?>
                                <div class="alert alert-info">
                                    Actualmente no hay notas para este candidato.
                                </div>
                                <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($notas as $nota): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($nota['titulo']); ?></h5>
                                            <small><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($nota['contenido'])); ?></p>
                                        
                                        <!-- Agregar botones de acción -->
                                        <div class="mt-2">
                                            <a href="editar-nota.php?id=<?php echo $nota['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="eliminar-nota.php?id=<?php echo $nota['id']; ?>&candidato_id=<?php echo $candidato['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar esta nota?');">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pestaña de Entrevistas -->
                            <div class="tab-pane fade <?php echo $activeTab === 'interviews' ? 'show active' : ''; ?>" 
                                 id="interviews" role="tabpanel" aria-labelledby="interviews-tab">
                                <?php if (empty($entrevistas)): ?>
                                <div class="alert alert-info">
                                    No hay entrevistas programadas para este candidato.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Vacante</th>
                                                <th>Tipo</th>
                                                <th>Fecha</th>
                                                <th>Lugar/Enlace</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($entrevistas as $entrevista): 
                                                // Extraer info de entrevista del texto de notas
                                                $entrevistaInfo = [
                                                    'tipo' => '',
                                                    'lugar' => ''
                                                ];
                                                
                                                // Tipo
                                                if (preg_match('/Tipo:\s*([^\n]+)/i', $entrevista['notas'], $matches)) {
                                                    $entrevistaInfo['tipo'] = trim($matches[1]);
                                                }
                                                
                                                // Lugar/Enlace
                                                if (preg_match('/Lugar\/Enlace:\s*([^\n]+)/i', $entrevista['notas'], $matches)) {
                                                    $entrevistaInfo['lugar'] = trim($matches[1]);
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($entrevista['vacante_titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($entrevistaInfo['tipo'] ?? 'No especificado'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($entrevista['fecha'])); ?></td>
                                                <td>
                                                    <?php if (!empty($entrevistaInfo['lugar'])): ?>
                                                    <?php if (filter_var($entrevistaInfo['lugar'], FILTER_VALIDATE_URL)): ?>
                                                    <a href="<?php echo $entrevistaInfo['lugar']; ?>" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i> Enlace de reunión
                                                    </a>
                                                    <?php else: ?>
                                                    <?php echo htmlspecialchars($entrevistaInfo['lugar']); ?>
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    No especificado
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $entrevista['estado'] === 'pendiente' ? 'bg-warning' : ($entrevista['estado'] === 'completada' ? 'bg-success' : 'bg-secondary'); ?>">
                                                        <?php echo ucfirst($entrevista['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../aplicaciones/detalle.php?id=<?php echo $entrevista['aplicacion_id']; ?>&tab=interviews" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Ver aplicación
                                                    </a>
                                                    <?php if ($entrevista['estado'] === 'pendiente'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="marcarEntrevistaCompletada(<?php echo $entrevista['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pestaña de Historial -->
                            <div class="tab-pane fade <?php echo $activeTab === 'history' ? 'show active' : ''; ?>" 
                                 id="history" role="tabpanel" aria-labelledby="history-tab">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Registro del candidato</h6>
                                            <p class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($candidato['created_at'])); ?></p>
                                            <p>Candidato registrado en el sistema.</p>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    // Recopilar todas las actividades (aplicaciones, entrevistas, cambios de estado, notas)
                                    $actividades = [];
                                    
                                    // Agregar aplicaciones al historial
                                    foreach ($aplicaciones as $aplicacion) {
                                        $actividades[] = [
                                            'tipo' => 'aplicacion',
                                            'fecha' => $aplicacion['fecha_aplicacion'],
                                            'titulo' => 'Aplicación a vacante',
                                            'descripcion' => 'Aplicó a la vacante <strong>' . htmlspecialchars($aplicacion['vacante_titulo']) . '</strong>.',
                                            'icono' => 'fas fa-file-alt'
                                        ];
                                        
                                        // Agregar cambios de estado si existen
                                        if (method_exists($applicationManager, 'getApplicationHistory')) {
                                            $historialAplicacion = $applicationManager->getApplicationHistory($aplicacion['id']);
                                            foreach ($historialAplicacion as $cambio) {$actividades[] = [
                                                    'tipo' => 'cambio_estado',
                                                    'fecha' => $cambio['fecha_cambio'],
                                                    'titulo' => 'Cambio de estado en ' . htmlspecialchars($aplicacion['vacante_titulo']),
                                                    'descripcion' => 'Estado cambiado a <strong>' . ($cambio['estado_nuevo']) . '</strong>' . 
                                                                (!empty($cambio['comentario']) ? '<br>' . htmlspecialchars($cambio['comentario']) : ''),
                                                    'icono' => 'fas fa-exchange-alt'
                                                ];
                                            }
                                        }
                                    }
                                    
                                    // Agregar notas al historial
                                    foreach ($notas as $nota) {
                                        $actividades[] = [
                                            'tipo' => 'nota',
                                            'fecha' => $nota['created_at'],
                                            'titulo' => 'Nueva nota: ' . htmlspecialchars($nota['titulo']),
                                            'descripcion' => substr(htmlspecialchars($nota['contenido']), 0, 150) . (strlen($nota['contenido']) > 150 ? '...' : ''),
                                            'icono' => 'fas fa-sticky-note'
                                        ];
                                    }
                                    
                                    // Agregar entrevistas al historial
                                    foreach ($entrevistas as $entrevista) {
                                        $actividades[] = [
                                            'tipo' => 'entrevista',
                                            'fecha' => $entrevista['created_at'] ?? $entrevista['fecha'], // Fecha de programación o la que exista
                                            'titulo' => 'Entrevista programada para ' . htmlspecialchars($entrevista['vacante_titulo']),
                                            'descripcion' => 'Programada para: ' . date('d/m/Y H:i', strtotime($entrevista['fecha'])),
                                            'icono' => 'fas fa-calendar-alt'
                                        ];
                                    }
                                    
                                    // Ordenar por fecha (más recientes primero)
                                    usort($actividades, function($a, $b) {
                                        return strtotime($b['fecha']) - strtotime($a['fecha']);
                                    });
                                    
                                    // Mostrar todas las actividades
                                    foreach ($actividades as $actividad):
                                    ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <i class="<?php echo $actividad['icono']; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title"><?php echo $actividad['titulo']; ?></h6>
                                            <p class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?></p>
                                            <p><?php echo $actividad['descripcion']; ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal para cambio de estado -->
<div class="modal fade" id="cambioEstadoModal" tabindex="-1" aria-labelledby="cambioEstadoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cambioEstadoModalLabel">Cambiar Estado de Aplicación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCambioEstado" action="../aplicaciones/cambiar-estado.php" method="post">
        <div class="modal-body">
          <input type="hidden" id="aplicacion_id" name="id" value="">
          <input type="hidden" id="candidato_id" name="candidato_id" value="">
          <input type="hidden" id="from" name="from" value="candidato">
          
          <div class="mb-3">
            <label for="estado_actual" class="form-label">Estado Actual</label>
            <div id="estadoActual" class="py-2">
              <!-- Aquí se mostrará el estado actual -->
            </div>
          </div>
          
          <div class="mb-3">
            <label for="estado" class="form-label">Nuevo Estado <span class="text-danger">*</span></label>
            <select class="form-select" id="estado" name="estado" required>
              <option value="">Seleccionar estado</option>
              <option value="recibida">Recibida</option>
              <option value="revision">En Revisión</option>
              <option value="entrevista">Entrevista</option>
              <option value="prueba">Prueba</option>
              <option value="oferta">Oferta</option>
              <option value="contratado">Contratado</option>
              <option value="rechazado">Rechazado</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="notas" class="form-label">Notas adicionales</label>
            <textarea class="form-control" id="notas" name="notas" rows="4"></textarea>
            <div class="form-text">Estas notas se agregarán al historial de la aplicación.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para programar entrevista -->
<div class="modal fade" id="programarEntrevistaModal" tabindex="-1" aria-labelledby="programarEntrevistaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="programarEntrevistaModalLabel">Programar Entrevista</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formProgramarEntrevista" action="../aplicaciones/programar-entrevista.php" method="post">
        <div class="modal-body">
          <input type="hidden" id="entrevista_aplicacion_id" name="aplicacion_id" value="">
          <input type="hidden" id="entrevista_candidato_id" name="candidato_id" value="">
          
          <div class="mb-3">
            <label for="fecha_entrevista" class="form-label">Fecha <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="fecha_entrevista" name="fecha_entrevista" required>
          </div>
          
          <div class="mb-3">
            <label for="hora_entrevista" class="form-label">Hora <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="hora_entrevista" name="hora_entrevista" required>
          </div>
          
          <div class="mb-3">
            <label for="tipo_entrevista" class="form-label">Tipo de Entrevista <span class="text-danger">*</span></label>
            <select class="form-select" id="tipo_entrevista" name="tipo_entrevista" required>
              <option value="">Seleccionar tipo</option>
              <option value="presencial">Presencial</option>
              <option value="telefonica">Telefónica</option>
              <option value="videollamada">Videollamada</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="lugar_entrevista" class="form-label">Lugar o Enlace</label>
            <input type="text" class="form-control" id="lugar_entrevista" name="lugar_entrevista">
            <div class="form-text">Para entrevistas presenciales, indica la dirección. Para videollamadas, el enlace de la reunión.</div>
          </div>
          
          <div class="mb-3">
            <label for="notas_entrevista" class="form-label">Notas</label>
            <textarea class="form-control" id="notas_entrevista" name="notas_entrevista" rows="3"></textarea>
          </div>
          
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="notificar_candidato" name="notificar_candidato" value="1">
            <label class="form-check-label" for="notificar_candidato">Notificar al candidato por email</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Programar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para agregar nota a aplicación -->
<div class="modal fade" id="agregarNotaAplicacionModal" tabindex="-1" aria-labelledby="agregarNotaAplicacionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="agregarNotaAplicacionModalLabel">Agregar Nota a la Aplicación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAgregarNotaAplicacion" action="../aplicaciones/agregar-nota.php" method="post">
        <div class="modal-body">
          <input type="hidden" id="nota_aplicacion_id" name="aplicacion_id" value="">
          <input type="hidden" id="nota_candidato_id" name="candidato_id" value="">
          
          <div class="mb-3">
            <label for="etapa" class="form-label">Etapa <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="etapa" name="etapa" required>
          </div>
          
          <div class="mb-3">
            <label for="nota_contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
            <textarea class="form-control" id="nota_contenido" name="notas" rows="5" required></textarea>
          </div>
          
          <div class="mb-3">
            <label for="fecha_nota" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="fecha_nota" name="fecha" value="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Nota</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Script para manejar el modal de cambio de estado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Función para abrir el modal con los datos de la aplicación
  window.abrirModalCambioEstado = function(aplicacionId, candidatoId, estadoActual) {
    // Establecer los valores en el formulario
    document.getElementById('aplicacion_id').value = aplicacionId;
    document.getElementById('candidato_id').value = candidatoId;
    
    // Mostrar el estado actual con formato
    let estadoBadgeHtml = '';
    let badgeClass = '';
    
    switch(estadoActual) {
        case 'recibida': badgeClass = 'bg-info'; break;
        case 'revision': badgeClass = 'bg-primary'; break;
        case 'entrevista': badgeClass = 'bg-warning'; break;
        case 'prueba': badgeClass = 'bg-warning'; break;
        case 'oferta': badgeClass = 'bg-success'; break;
        case 'contratado': badgeClass = 'bg-success'; break;
        case 'rechazado': badgeClass = 'bg-danger'; break;
        default: badgeClass = 'bg-secondary';
    }
    
    const statuses = {
        'recibida': 'Recibida',
        'revision': 'En Revisión',
        'entrevista': 'Entrevista',
        'prueba': 'Prueba',
        'oferta': 'Oferta',
        'contratado': 'Contratado',
        'rechazado': 'Rechazado'
    };
    
    estadoBadgeHtml = '<span class="badge ' + badgeClass + '">' + (statuses[estadoActual] || estadoActual) + '</span>';
    document.getElementById('estadoActual').innerHTML = estadoBadgeHtml;
    
    // Seleccionar el estado actual en el dropdown
    const estadoSelect = document.getElementById('estado');
    
    for (let i = 0; i < estadoSelect.options.length; i++) {
      if (estadoSelect.options[i].value === estadoActual) {
        estadoSelect.selectedIndex = i;
        break;
      }
    }
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('cambioEstadoModal'));
    modal.show();
  };
  
  // Función para abrir el modal de programar entrevista
  window.abrirModalEntrevista = function(aplicacionId, candidatoId) {
    // Establecer fecha predeterminada (mañana)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const formattedDate = tomorrow.toISOString().split('T')[0];
    document.getElementById('fecha_entrevista').value = formattedDate;
    
    // Establecer hora predeterminada (10:00 AM)
    document.getElementById('hora_entrevista').value = '10:00';
    
    // Establecer los valores en el formulario
    document.getElementById('entrevista_aplicacion_id').value = aplicacionId;
    document.getElementById('entrevista_candidato_id').value = candidatoId;
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('programarEntrevistaModal'));
    modal.show();
  };
  
  // Función para abrir el modal de agregar nota a aplicación
  window.abrirModalNotaAplicacion = function(aplicacionId, candidatoId) {
    // Establecer los valores en el formulario
    document.getElementById('nota_aplicacion_id').value = aplicacionId;
    document.getElementById('nota_candidato_id').value = candidatoId;
    
    // Establecer fecha predeterminada (hoy)
    document.getElementById('fecha_nota').value = new Date().toISOString().split('T')[0];
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('agregarNotaAplicacionModal'));
    modal.show();
  };
  
  // Funciones para manejar acciones de entrevistas
  window.marcarEntrevistaCompletada = function(entrevistaId) {
    if (confirm('¿Marcar esta entrevista como completada?')) {
      window.location.href = '../aplicaciones/actualizar-entrevista.php?id=' + entrevistaId + 
                           '&estado=completada&redirect=' + encodeURIComponent(window.location.href);
    }
  }
});
</script>

<style>
/* Estilos para avatar */
.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #007bff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
}

.avatar-initials {
    color: white;
    font-size: 2rem;
    font-weight: bold;
    text-transform: uppercase;
}

/* Estilos para información del candidato */
.candidate-info {
    margin-top: 20px;
}

.info-item {
    display: flex;
    margin-bottom: 12px;
}

.info-label {
    width: 30px;
    display: flex;
    justify-content: center;
    color: #6c757d;
}

.info-value {
    flex: 1;
    padding-left: 10px;
}

/* Estilos para timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #007bff;
    display: flex;
    justify-content: center;
    align-items: center;
}

.timeline-marker i {
    color: white;
    font-size: 0.8rem;
}

.timeline-content {
    padding-bottom: 10px;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-date {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 5px;
}

/* Estilos para pestañas */
.nav-tabs .nav-link {
    color: #495057;
}

.nav-tabs .nav-link.active {
    font-weight: 600;
}

.tab-content {
    background-color: #fff;
}

/* Estilos para botones en tabla */
.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>

<?php include '../includes/footer.php'; ?>