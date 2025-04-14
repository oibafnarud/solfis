<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/detalle.php - Ver detalles de una aplicación
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
$applicationManager = new ApplicationManager();
$vacancyManager = new VacancyManager();
$candidateManager = new CandidateManager();

// Obtener aplicación por ID
$aplicacion = $applicationManager->getApplicationById($id);

// Si no existe la aplicación, redirigir
if (!$aplicacion) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener datos relacionados
$vacante = $vacancyManager->getVacancyById($aplicacion['vacante_id']);
$candidato = $candidateManager->getCandidateById($aplicacion['candidato_id']);

// Obtener historial de estados si existe el método
$historial = [];
if (method_exists($applicationManager, 'getApplicationHistory')) {
    $historial = $applicationManager->getApplicationHistory($id);
}

// Obtener notas de la aplicación si existe el método
$notas = [];
if (method_exists($applicationManager, 'getApplicationNotes')) {
    $notas = $applicationManager->getApplicationNotes($id);
}

// Obtener entrevistas programadas si existe el método
$entrevistas = [];
if (method_exists($applicationManager, 'getScheduledInterviews')) {
    $entrevistas = $applicationManager->getScheduledInterviews($id);
}

// Título de la página
$pageTitle = 'Detalles de Aplicación - Panel de Administración';

// Mensajes de notificación
$notification = null;
if (isset($_GET['message'])) {
    $messages = [
        'status-updated' => ['type' => 'success', 'text' => 'Estado actualizado correctamente.'],
        'interview-scheduled' => ['type' => 'success', 'text' => 'Entrevista programada correctamente.'],
        'interview-updated' => ['type' => 'success', 'text' => 'Estado de entrevista actualizado correctamente.'],
        'note-added' => ['type' => 'success', 'text' => 'Nota añadida correctamente.'],
    ];

    if (array_key_exists($_GET['message'], $messages)) {
        $notification = $messages[$_GET['message']];
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles de Aplicación</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Aplicaciones
                            </a>
                            <a href="../candidatos/detalle.php?id=<?php echo $candidato['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-user"></i> Ver Candidato
                            </a>
                            <a href="../vacantes/vacante-editar.php?id=<?php echo $vacante['id']; ?>" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-briefcase"></i> Ver Vacante
                            </a>
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
                    <div class="col-md-8">
                        <!-- Resumen de Aplicación -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Información de la Aplicación</h5>
                                <span class="badge bg-<?php echo getStatusColor($aplicacion['estado']); ?>">
                                    <?php echo getStatusText($aplicacion['estado']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Candidato:</strong></p>
                                        <h5><?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></h5>
                                        <p class="text-muted">
                                            <a href="mailto:<?php echo $candidato['email']; ?>"><?php echo $candidato['email']; ?></a><br>
                                            <?php echo $candidato['telefono'] ?: 'Sin teléfono'; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Vacante:</strong></p>
                                        <h5><?php echo htmlspecialchars($vacante['titulo']); ?></h5>
                                        <p class="text-muted">
                                            <?php echo $vacante['categoria_nombre']; ?><br>
                                            <?php echo $vacante['ubicacion']; ?> - <?php echo ucfirst($vacante['modalidad']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Fecha de aplicación:</strong> <?php echo date('d/m/Y H:i', strtotime($aplicacion['fecha_aplicacion'])); ?></p>
                                        <p><strong>Experiencia:</strong> <?php echo formatExperience($aplicacion['experiencia'] ?? ''); ?></p>
                                        <p><strong>Empresa actual:</strong> <?php echo $aplicacion['empresa_actual'] ?? 'No especificada'; ?></p>
                                        <p><strong>Cargo actual:</strong> <?php echo $aplicacion['cargo_actual'] ?? 'No especificado'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Salario esperado:</strong> <?php echo $aplicacion['salario_esperado'] ?? 'No especificado'; ?></p>
                                        <p><strong>Disponibilidad:</strong> <?php echo formatAvailability($aplicacion['disponibilidad'] ?? ''); ?></p>
                                        <p><strong>Fuente:</strong> <?php echo formatSource($aplicacion['fuente'] ?? ''); ?></p>
                                        <?php if (!empty($aplicacion['fecha_entrevista'])): ?>
                                        <p><strong>Entrevista programada:</strong> <?php echo date('d/m/Y H:i', strtotime($aplicacion['fecha_entrevista'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($aplicacion['carta_presentacion'])): ?>
                                <hr>
                                <div class="mb-3">
                                    <h6>Carta de Presentación:</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($aplicacion['carta_presentacion'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                        <i class="fas fa-exchange-alt"></i> Cambiar Estado
                                    </button>
                                    <div>
                                        <button type="button" class="btn btn-outline-success" 
                                           onclick="abrirModalEntrevista(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)">
                                           <i class="fas fa-calendar-alt"></i> Programar Entrevista
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                           onclick="abrirModalNotaAplicacion(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)">
                                           <i class="fas fa-sticky-note"></i> Agregar Nota
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestañas para Historial, Notas y Entrevistas -->
                        <ul class="nav nav-tabs" id="applicationDetailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="true">
                                    Historial de Estados
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="false">
                                    Notas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="interviews-tab" data-bs-toggle="tab" data-bs-target="#interviews" type="button" role="tab" aria-controls="interviews" aria-selected="false">
                                    Entrevistas
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom mb-4" id="applicationDetailTabContent">
                            <!-- Pestaña de Historial -->
                            <div class="tab-pane fade show active" id="history" role="tabpanel" aria-labelledby="history-tab">
                                <?php if (empty($historial)): ?>
                                <div class="alert alert-info">
                                    No hay registros de cambios de estado para esta aplicación.
                                </div>
                                <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($historial as $item): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">
                                                <?php if (!empty($item['estado_anterior'])): ?>
                                                Cambio de estado: <?php echo getStatusText($item['estado_anterior']); ?> → <?php echo getStatusText($item['estado_nuevo']); ?>
                                                <?php else: ?>
                                                Estado inicial: <?php echo getStatusText($item['estado_nuevo']); ?>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($item['fecha_cambio'])); ?></p>
                                            <?php if (!empty($item['comentario'])): ?>
                                            <p><?php echo htmlspecialchars($item['comentario']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($item['usuario_nombre'])): ?>
                                            <p class="text-muted">Por: <?php echo htmlspecialchars($item['usuario_nombre']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Pestaña de Notas -->
                            <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Notas de la aplicación</h5>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalNotaAplicacion(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)">
                                        <i class="fas fa-plus"></i> Añadir Nota</button>
                                </div>
                                
								<!-- Listado de notas -->
								<?php if (empty($notas)): ?>
								<div class="alert alert-info">
									No hay notas registradas para este candidato.
								</div>
								<?php else: ?>
								<div class="list-group">
									<?php foreach ($notas as $nota): ?>
									<div class="list-group-item">
										<div class="d-flex w-100 justify-content-between">
											<h5 class="mb-1"><?php echo htmlspecialchars($nota['etapa']); ?></h5>
											<small><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></small>
										</div>
										<p class="mb-1"><?php echo nl2br(htmlspecialchars($nota['notas'])); ?></p>
										<?php if (!empty($nota['usuario_nombre'])): ?>
										<small class="text-muted">Por: <?php echo htmlspecialchars($nota['usuario_nombre']); ?></small>
										<?php endif; ?>
										
										<!-- Agregar botones de acción -->
										<div class="mt-2">
											<a href="../aplicaciones/editar-nota.php?id=<?php echo $nota['id']; ?><?php echo isset($candidato) ? '&from=candidato&candidato_id=' . $candidato['id'] : ''; ?>" class="btn btn-sm btn-outline-primary">
												<i class="fas fa-edit"></i> Editar
											</a>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
								<?php endif; ?>
                            </div>
                            
                            <!-- Pestaña de Entrevistas -->
                            <div class="tab-pane fade" id="interviews" role="tabpanel" aria-labelledby="interviews-tab">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Entrevistas programadas</h5>
                                    <button type="button" class="btn btn-sm btn-success" onclick="abrirModalEntrevista(<?php echo $aplicacion['id']; ?>, <?php echo $candidato['id']; ?>)">
                                        <i class="fas fa-calendar-plus"></i> Programar Entrevista
                                    </button>
                                </div>
                                
                                <?php if (empty($entrevistas)): ?>
                                <div class="alert alert-info">
                                    No hay entrevistas programadas para esta aplicación.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Fecha y Hora</th>
                                                <th>Lugar/Enlace</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($entrevistas as $entrevista): 
                                                $entrevistaInfo = parseInterviewInfo($entrevista['notas']);
                                            ?>
                                            <tr>
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
                                                    <span class="badge bg-<?php echo $entrevista['estado'] === 'pendiente' ? 'warning' : ($entrevista['estado'] === 'completada' ? 'success' : 'secondary'); ?>">
                                                        <?php echo ucfirst($entrevista['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($entrevista['estado'] === 'pendiente'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" onclick="marcarEntrevistaCompletada(<?php echo $entrevista['id']; ?>)">
                                                        <i class="fas fa-check"></i> Completada
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="cancelarEntrevista(<?php echo $entrevista['id']; ?>)">
                                                        <i class="fas fa-times"></i> Cancelar
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
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Currículum del Candidato -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Currículum Vitae</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($candidato['cv_path'])): ?>
                                <div class="mb-3">
                                    <div class="d-grid">
                                        <a href="../../uploads/resumes/<?php echo $candidato['cv_path']; ?>" target="_blank" class="btn btn-primary">
                                            <i class="fas fa-file-pdf"></i> Ver CV
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($candidato['drive_view_link'])): ?>
                                <div class="mb-3">
                                    <div class="d-grid">
                                        <a href="<?php echo $candidato['drive_view_link']; ?>" target="_blank" class="btn btn-success">
                                            <i class="fab fa-google-drive"></i> Ver en Google Drive
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (empty($candidato['cv_path']) && empty($candidato['drive_view_link'])): ?>
                                <div class="alert alert-warning">
                                    No se ha encontrado el currículum de este candidato.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Acciones Rápidas -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="mailto:<?php echo $candidato['email']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope"></i> Enviar Email
                                    </a>
                                    
                                    <?php if (!empty($candidato['telefono'])): ?>
                                    <a href="tel:<?php echo $candidato['telefono']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-phone"></i> Llamar
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="../candidatos/detalle.php?id=<?php echo $candidato['id']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-user"></i> Ver Perfil Completo
                                    </a>
                                    
                                    <?php if ($aplicacion['estado'] === 'recibida'): ?>
                                    <button type="button" class="btn btn-success" onclick="changeStatus('revision')">
                                        <i class="fas fa-check"></i> Marcar En Revisión
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($aplicacion['estado'] === 'revision'): ?>
                                    <button type="button" class="btn btn-success" onclick="changeStatus('entrevista')">
                                        <i class="fas fa-calendar-check"></i> Pasar a Entrevista
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($aplicacion['estado'] === 'entrevista'): ?>
                                    <button type="button" class="btn btn-success" onclick="changeStatus('oferta')">
                                        <i class="fas fa-file-signature"></i> Preparar Oferta
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalles de la Vacante -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Detalles de la Vacante</h5>
                            </div>
                            <div class="card-body">
                                <div class="vacancy-details">
                                    <div class="info-item">
                                        <span class="info-label">Categoría:</span>
                                        <span class="info-value"><?php echo $vacante['categoria_nombre']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Ubicación:</span>
                                        <span class="info-value"><?php echo $vacante['ubicacion']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Modalidad:</span>
                                        <span class="info-value"><?php echo ucfirst($vacante['modalidad']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Tipo de Contrato:</span>
                                        <span class="info-value"><?php echo formatContractType($vacante['tipo_contrato']); ?></span>
                                    </div>
                                    <?php if (!empty($vacante['experiencia'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Experiencia Requerida:</span>
                                        <span class="info-value"><?php echo $vacante['experiencia']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($vacante['mostrar_salario'] && ($vacante['salario_min'] > 0 || $vacante['salario_max'] > 0)): ?>
                                    <div class="info-item">
                                        <span class="info-label">Rango Salarial:</span>
                                        <span class="info-value">
                                            <?php 
                                            if ($vacante['salario_min'] > 0 && $vacante['salario_max'] > 0) {
                                                echo 'RD$ ' . number_format($vacante['salario_min'], 0, '.', ',') . ' - ' . number_format($vacante['salario_max'], 0, '.', ',');
                                            } elseif ($vacante['salario_min'] > 0) {
                                                echo 'Desde RD$ ' . number_format($vacante['salario_min'], 0, '.', ',');
                                            } elseif ($vacante['salario_max'] > 0) {
                                                echo 'Hasta RD$ ' . number_format($vacante['salario_max'], 0, '.', ',');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="info-item">
                                        <span class="info-label">Estado de la Vacante:</span>
                                        <span class="info-value">
                                            <span class="badge bg-<?php echo getVacancyStatusColor($vacante['estado']); ?>">
                                                <?php echo ucfirst($vacante['estado']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    <?php if (!empty($vacante['empresa_contratante']) && $vacante['mostrar_empresa']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Empresa Contratante:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($vacante['empresa_contratante']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-3">
                                    <a href="../vacantes/vacante-editar.php?id=<?php echo $vacante['id']; ?>" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="fas fa-briefcase"></i> Ver Detalles Completos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal para Actualizar Estado -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Actualizar Estado de Aplicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="cambiar-estado.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="redirect" value="detalle.php?id=<?php echo $id; ?>">
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="recibida" <?php echo $aplicacion['estado'] === 'recibida' ? 'selected' : ''; ?>>Recibida</option>
                            <option value="revision" <?php echo $aplicacion['estado'] === 'revision' ? 'selected' : ''; ?>>En Revisión</option>
                            <option value="entrevista" <?php echo $aplicacion['estado'] === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                            <option value="prueba" <?php echo $aplicacion['estado'] === 'prueba' ? 'selected' : ''; ?>>Prueba</option>
                            <option value="oferta" <?php echo $aplicacion['estado'] === 'oferta' ? 'selected' : ''; ?>>Oferta</option>
                            <option value="contratado" <?php echo $aplicacion['estado'] === 'contratado' ? 'selected' : ''; ?>>Contratado</option>
                            <option value="rechazado" <?php echo $aplicacion['estado'] === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" id="comentario" name="notas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
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
      <form id="formProgramarEntrevista" action="programar-entrevista.php" method="post">
        <div class="modal-body">
          <input type="hidden" id="entrevista_aplicacion_id" name="aplicacion_id" value="<?php echo $id; ?>">
          <input type="hidden" id="entrevista_candidato_id" name="candidato_id" value="<?php echo $candidato['id']; ?>">
          
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
      <form id="formAgregarNotaAplicacion" action="agregar-nota.php" method="post">
        <div class="modal-body">
          <input type="hidden" id="nota_aplicacion_id" name="aplicacion_id" value="<?php echo $id; ?>">
          <input type="hidden" id="nota_candidato_id" name="candidato_id" value="<?php echo $candidato['id']; ?>">
          
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

<script>
// Función para cambiar estado directamente
function changeStatus(newStatus) {
    document.getElementById('estado').value = newStatus;
    document.getElementById('comentario').value = 'Cambio de estado automático desde acciones rápidas.';
    
    // Simular envío del formulario
    const form = document.querySelector('#updateStatusModal form');
    form.submit();
}

// Función para abrir el modal de entrevista
window.abrirModalEntrevista = function(aplicacionId, candidatoId) {
    // Establecer fecha predeterminada (mañana)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const formattedDate = tomorrow.toISOString().split('T')[0];
    document.getElementById('fecha_entrevista').value = formattedDate;
    
    // Establecer hora predeterminada (10:00 AM)
    document.getElementById('hora_entrevista').value = '10:00';
    
    // Establecer los valores en el formulario (ya configurados como values en los inputs)
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('programarEntrevistaModal'));
    modal.show();
};

// Función para abrir el modal de agregar nota
window.abrirModalNotaAplicacion = function(aplicacionId, candidatoId) {
    // Establecer los valores en el formulario (ya configurados como values en los inputs)
    
    // Establecer fecha predeterminada (hoy)
    document.getElementById('fecha_nota').value = new Date().toISOString().split('T')[0];
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('agregarNotaAplicacionModal'));
    modal.show();
};

// Funciones para actualizar entrevistas
function marcarEntrevistaCompletada(entrevistaId) {
    if (confirm('¿Marcar esta entrevista como completada?')) {
        window.location.href = 'actualizar-entrevista.php?id=' + entrevistaId + 
                             '&estado=completada&redirect=' + encodeURIComponent(window.location.href);
    }
}

function cancelarEntrevista(entrevistaId) {
    if (confirm('¿Está seguro de cancelar esta entrevista?')) {
        window.location.href = 'actualizar-entrevista.php?id=' + entrevistaId + 
                             '&estado=cancelada&redirect=' + encodeURIComponent(window.location.href);
    }
}

// Activar pestaña según parámetro en URL
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay un parámetro 'tab' en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam) {
        // Activar la pestaña correspondiente
        const tabElement = document.querySelector('#' + tabParam + '-tab');
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
});
</script>

<style>
/* Estilos para información de la vacante */
.vacancy-details {
    margin-bottom: 15px;
}

.info-item {
    display: flex;
    margin-bottom: 10px;
}

.info-label {
    font-weight: 600;
    width: 50%;
}

.info-value {
    width: 50%;
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
</style>

<?php
/**
 * Analiza el texto de notas de entrevista para extraer información estructurada
 * 
 * @param string $notesText Texto de notas de la entrevista
 * @return array Información estructurada de la entrevista
 */
function parseInterviewInfo($notesText) {
    $info = [
        'tipo' => null,
        'fecha' => null,
        'lugar' => null
    ];
    
    // Extraer tipo
    if (preg_match('/Tipo:\s*([^\n]+)/i', $notesText, $matches)) {
        $info['tipo'] = trim($matches[1]);
    }
    
    // Extraer fecha
    if (preg_match('/Fecha y hora:\s*([^\n]+)/i', $notesText, $matches)) {
        $info['fecha'] = trim($matches[1]);
    }
    
    // Extraer lugar/enlace
    if (preg_match('/Lugar\/Enlace:\s*([^\n]+)/i', $notesText, $matches)) {
        $info['lugar'] = trim($matches[1]);
    }
    
    return $info;
}

// Funciones auxiliares para formateo
function getStatusColor($status) {
    $colors = [
        'recibida' => 'info',
        'revision' => 'primary',
        'entrevista' => 'warning',
        'prueba' => 'warning',
        'oferta' => 'success',
        'contratado' => 'success',
        'rechazado' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

function getStatusText($status) {
    $texts = [
        'recibida' => 'Recibida',
        'revision' => 'En Revisión',
        'entrevista' => 'Entrevista',
        'prueba' => 'Prueba',
        'oferta' => 'Oferta',
        'contratado' => 'Contratado',
        'rechazado' => 'Rechazado'
    ];
    
    return $texts[$status] ?? ucfirst($status);
}

function getVacancyStatusColor($status) {
    $colors = [
        'borrador' => 'secondary',
        'publicada' => 'success',
        'cerrada' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

function formatExperience($experience) {
    $experiences = [
        '' => 'No especificada',
        'menos-1' => 'Menos de 1 año',
        '1-3' => '1-3 años',
        '3-5' => '3-5 años',
        '5-10' => '5-10 años',
        'mas-10' => 'Más de 10 años'
    ];
    
    return $experiences[$experience] ?? $experience;
}

function formatAvailability($availability) {
    $availabilities = [
        '' => 'No especificada',
        'inmediata' => 'Inmediata',
        '2-semanas' => '2 semanas',
        '1-mes' => '1 mes',
        'mas-1-mes' => 'Más de 1 mes'
    ];
    
    return $availabilities[$availability] ?? $availability;
}

function formatSource($source){
    $sources = [
        '' => 'No especificada',
        'web' => 'Sitio web de SolFis',
        'linkedin' => 'LinkedIn',
        'referencia' => 'Referencia de un empleado',
        'otro' => 'Otro'
    ];
    
    return $sources[$source] ?? $source;
}

function formatContractType($type) {
    $types = [
        'tiempo_completo' => 'Tiempo Completo',
        'tiempo_parcial' => 'Tiempo Parcial',
        'proyecto' => 'Por Proyecto',
        'temporal' => 'Temporal'
    ];
    
    return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
}
?>

<?php include '../includes/footer.php'; ?>