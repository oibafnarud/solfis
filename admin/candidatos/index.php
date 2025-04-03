<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/index.php - Gestión de candidatos
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
$candidateManager = new CandidateManager();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$q = isset($_GET['q']) ? $_GET['q'] : '';
$per_page = 10;

// Obtener candidatos con paginación y filtros
$filters = [
    'busqueda' => $q
];

$candidatosData = $candidateManager->getCandidates($page, $per_page, $filters);
$candidatos = $candidatosData['candidates'];
$totalPages = $candidatosData['pages'];

// Título de la página
$pageTitle = 'Gestión de Candidatos - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Candidatos</h1>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter"></i> Buscar Candidatos
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="get" class="row g-3">
                            <div class="col-md-8">
                                <label for="q" class="form-label">Buscar por nombre, apellido o email</label>
                                <input type="text" class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar candidatos...">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Buscar</button>
                                <?php if ($q): ?>
                                <a href="index.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de Candidatos -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Ubicación</th>
                                        <th>Aplicaciones</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($candidatos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No hay candidatos que coincidan con los filtros aplicados.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($candidatos as $candidato): ?>
                                        <tr>
                                            <td>
                                                <a href="detalle.php?id=<?php echo $candidato['id']; ?>" class="fw-bold text-decoration-none">
                                                    <?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $candidato['email']; ?></td>
                                            <td><?php echo $candidato['telefono'] ?: 'No disponible'; ?></td>
                                            <td><?php echo $candidato['ubicacion'] ?: 'No especificada'; ?></td>
                                            <td class="text-center">
                                                <a href="../aplicaciones/index.php?candidato_id=<?php echo $candidato['id']; ?>" class="badge bg-primary">
                                                    <?php echo $candidato['aplicaciones_count'] ?? 0; ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($candidato['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="detalle.php?id=<?php echo $candidato['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($candidato['cv_path']): ?>
                                                    <a href="<?php echo '../../uploads/resumes/' . $candidato['cv_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                    <?php endif; ?>
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
                        <nav aria-label="Paginación de candidatos">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
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