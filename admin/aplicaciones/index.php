<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/index.php - Gestión de aplicaciones a vacantes
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

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();
$vacancyManager = new VacancyManager();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$vacante_id = isset($_GET['vacante_id']) ? (int)$_GET['vacante_id'] : 0;
$per_page = 10;

// Obtener aplicaciones con paginación y filtros
$filters = [
    'estado' => $estado,
    'vacante_id' => $vacante_id
];

$aplicacionesData = $applicationManager->getApplications($page, $per_page, $filters);
$aplicaciones = $aplicacionesData['applications'];
$totalPages = $aplicacionesData['pages'];

// Obtener vacantes para filtro
$vacantes = $vacancyManager->getVacancies(1, 100)['vacancies'];

// Título de la página
$pageTitle = 'Gestión de Aplicaciones - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Aplicaciones</h1>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter"></i> Filtros
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="vacante_id" class="form-label">Vacante</label>
                                <select name="vacante_id" id="vacante_id" class="form-select">
                                    <option value="">Todas las vacantes</option>
                                    <?php foreach ($vacantes as $vacante): ?>
                                    <option value="<?php echo $vacante['id']; ?>" <?php echo $vacante_id === (int)$vacante['id'] ? 'selected' : ''; ?>>
                                        <?php echo $vacante['titulo']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="recibida" <?php echo $estado === 'recibida' ? 'selected' : ''; ?>>Recibida</option>
                                    <option value="revision" <?php echo $estado === 'revision' ? 'selected' : ''; ?>>En Revisión</option>
                                    <option value="entrevista" <?php echo $estado === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                                    <option value="prueba" <?php echo $estado === 'prueba' ? 'selected' : ''; ?>>Prueba</option>
                                    <option value="oferta" <?php echo $estado === 'oferta' ? 'selected' : ''; ?>>Oferta</option>
                                    <option value="contratado" <?php echo $estado === 'contratado' ? 'selected' : ''; ?>>Contratado</option>
                                    <option value="rechazado" <?php echo $estado === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <?php if ($estado || $vacante_id): ?>
                                <a href="index.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de Aplicaciones -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Candidato</th>
                                        <th>Vacante</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($aplicaciones)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No hay aplicaciones que coincidan con los filtros aplicados.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($aplicaciones as $aplicacion): ?>
                                        <tr>
                                            <td>
                                                <a href="../candidatos/detalle.php?id=<?php echo $aplicacion['candidato_id']; ?>" class="text-decoration-none">
                                                    <?php echo $aplicacion['candidato_nombre'] . ' ' . $aplicacion['candidato_apellido']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="../vacantes/vacante-editar.php?id=<?php echo $aplicacion['vacante_id']; ?>" class="text-decoration-none">
                                                    <?php echo $aplicacion['vacante_titulo']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                            <td>
                                                <?php echo VacancyUtils::getApplicationStatusBadge($aplicacion['estado']); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="detalle.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
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
                        <nav aria-label="Paginación de aplicaciones">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $vacante_id ? '&vacante_id=' . $vacante_id : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $vacante_id ? '&vacante_id=' . $vacante_id : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $vacante_id ? '&vacante_id=' . $vacante_id : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-chevron-right"></i></span>
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
</div>

<?php include '../includes/footer.php'; ?>