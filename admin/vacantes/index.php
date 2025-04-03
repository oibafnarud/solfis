<?php
/**
 * Panel de Administración para SolFis
 * admin/vacantes/index.php - Página para listar y gestionar vacantes
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
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$q = isset($_GET['q']) ? $_GET['q'] : '';
$per_page = 10;

// Obtener vacantes con paginación y filtros
$filters = [
    'estado' => $estado,
    'categoria' => $categoria,
    'busqueda' => $q
];

$vacantesData = $vacancyManager->getVacancies($page, $per_page, $filters);
$vacantes = $vacantesData['vacancies'];
$totalPages = $vacantesData['pages'];

// Obtener categorías para filtro
$categorias = $categoryManager->getCategories();

// Procesar acciones masivas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['vacante_ids'])) {
    $action = $_POST['action'];
    $vacanteIds = $_POST['vacante_ids'];
    
    switch ($action) {
        case 'publicar':
            foreach ($vacanteIds as $id) {
                $vacancyManager->changeVacancyStatus($id, 'publicada');
            }
            header('Location: index.php?message=vacantes-publicadas');
            exit;
            break;
            
        case 'borrador':
            foreach ($vacanteIds as $id) {
                $vacancyManager->changeVacancyStatus($id, 'borrador');
            }
            header('Location: index.php?message=vacantes-borradores');
            exit;
            break;
            
        case 'cerrar':
            foreach ($vacanteIds as $id) {
                $vacancyManager->changeVacancyStatus($id, 'cerrada');
            }
            header('Location: index.php?message=vacantes-cerradas');
            exit;
            break;
            
        case 'eliminar':
            foreach ($vacanteIds as $id) {
                $vacancyManager->deleteVacancy($id);
            }
            header('Location: index.php?message=vacantes-eliminadas');
            exit;
            break;
    }
}

// Mensajes de notificación
$messages = [
    'vacante-updated' => ['type' => 'success', 'text' => 'Vacante actualizada correctamente.'],
    'vacante-deleted' => ['type' => 'success', 'text' => 'Vacante eliminada correctamente.'],
    'vacantes-publicadas' => ['type' => 'success', 'text' => 'Vacantes publicadas correctamente.'],
    'vacantes-borradores' => ['type' => 'success', 'text' => 'Vacantes cambiadas a borradores.'],
    'vacantes-cerradas' => ['type' => 'success', 'text' => 'Vacantes cerradas correctamente.'],
    'vacantes-eliminadas' => ['type' => 'success', 'text' => 'Vacantes eliminadas correctamente.'],
    'vacante-created' => ['type' => 'success', 'text' => 'Nueva vacante creada correctamente.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Vacantes - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Vacantes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="vacante-nueva.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Nueva Vacante
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
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter"></i> Filtros
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="borrador" <?php echo $estado === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                                    <option value="publicada" <?php echo $estado === 'publicada' ? 'selected' : ''; ?>>Publicada</option>
                                    <option value="cerrada" <?php echo $estado === 'cerrada' ? 'selected' : ''; ?>>Cerrada</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select name="categoria" id="categoria" class="form-select">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoria === (int)$cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['nombre']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="q" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por título...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <?php if ($estado || $categoria || $q): ?>
                                <a href="index.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de Vacantes -->
                <div class="card">
                    <div class="card-body">
                        <form action="index.php" method="post">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input select-all" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Título</th>
                                            <th>Categoría</th>
                                            <th>Ubicación</th>
                                            <th>Publicación</th>
                                            <th>Estado</th>
                                            <th>Aplicaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($vacantes)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No hay vacantes que coincidan con los filtros aplicados.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($vacantes as $vacante): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input vacante-select" type="checkbox" name="vacante_ids[]" value="<?php echo $vacante['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="vacante-editar.php?id=<?php echo $vacante['id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo $vacante['titulo']; ?>
                                                    </a>
                                                    <?php if ($vacante['destacada']): ?>
                                                        <span class="badge bg-warning text-dark">Destacada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $vacante['categoria_nombre'] ?? 'Sin categoría'; ?></td>
                                                <td><?php echo $vacante['ubicacion']; ?></td>
                                                <td><?php echo $vacante['fecha_publicacion'] ? date('d/m/Y', strtotime($vacante['fecha_publicacion'])) : '-'; ?></td>
                                                <td>
                                                    <?php if ($vacante['estado'] === 'publicada'): ?>
                                                        <span class="badge bg-success">Publicada</span>
                                                    <?php elseif ($vacante['estado'] === 'borrador'): ?>
                                                        <span class="badge bg-secondary">Borrador</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Cerrada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $aplicacionesCount = isset($vacante['aplicaciones_count']) ? $vacante['aplicaciones_count'] : 0;
                                                    echo $aplicacionesCount;
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="vacante-editar.php?id=<?php echo $vacante['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="../../vacantes/detalle.php?id=<?php echo $vacante['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="vacante-eliminar.php?id=<?php echo $vacante['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar esta vacante?');">
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
                            
                            <!-- Acciones en lote -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="bulk-actions d-flex align-items-center">
                                    <select name="action" class="form-select me-2">
                                        <option value="">Acciones en lote</option>
                                        <option value="publicar">Publicar</option>
                                        <option value="borrador">Mover a borradores</option>
                                        <option value="cerrar">Cerrar vacantes</option>
                                        <option value="eliminar">Eliminar</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de vacantes">
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $estado ? '&estado=' . $estado : ''; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                                &raquo;
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
// Seleccionar todos los checkboxes
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.vacante-select');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php include '../includes/footer.php'; ?>