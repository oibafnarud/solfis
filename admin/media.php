<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/media.php - Página para gestionar archivos multimedia
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

// Instanciar clase de medios
$media = new Media();

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Obtener imágenes con paginación
$mediaData = $media->getImages($page, $per_page);
$mediaItems = $mediaData['images'];
$totalPages = $mediaData['pages'];

// Mensajes de notificación
$messages = [
    'media-uploaded' => ['type' => 'success', 'text' => 'Imagen subida correctamente.'],
    'media-deleted' => ['type' => 'success', 'text' => 'Imagen eliminada correctamente.'],
    'media-error' => ['type' => 'danger', 'text' => 'Hubo un error al procesar la imagen.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}

// Título de la página
$pageTitle = 'Gestión de Multimedia - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Multimedia</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-upload"></i> Subir Nueva Imagen
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($notification): ?>
                <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $notification['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="row" id="media-gallery">
                            <?php if (empty($mediaItems)): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">No hay imágenes disponibles. Suba algunas para comenzar.</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($mediaItems as $item): ?>
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo '../' . $item['path']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                                        <div class="card-body">
                                            <h6 class="card-title text-truncate"><?php echo $item['name']; ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($item['created_at'])); ?><br>
                                                    <?php echo round($item['size'] / 1024, 2); ?> KB
                                                </small>
                                            </p>
                                            <div class="d-flex justify-content-between">
                                                <button class="btn btn-sm btn-outline-primary copy-url" data-url="<?php echo '../' . $item['path']; ?>">
                                                    <i class="fas fa-copy"></i> Copiar URL
                                                </button>
                                                <a href="media-delete.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar esta imagen?');">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Paginación de multimedia">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                            &laquo;
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                            &raquo;
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal para subir imágenes -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Subir Nueva Imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="media-upload.php" method="post" enctype="multipart/form-data" id="upload-form">
                    <div class="mb-3">
                        <label for="image" class="form-label">Seleccionar Imagen</label>
                        <input class="form-control" type="file" id="image" name="image" accept="image/*" required>
                        <div class="form-text">
                            Formatos permitidos: JPG, JPEG, PNG, GIF. Tamaño máximo: 5MB.
                        </div>
                    </div>
                    <div id="preview-container" class="text-center mb-3" style="display: none;">
                        <h6>Vista previa:</h6>
                        <img id="image-preview" src="#" alt="Vista previa" class="img-fluid mb-2" style="max-height: 200px;">
                    </div>
                    <div id="upload-status"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="upload-button">Subir</button>
            </div>
        </div>
    </div>
</div>

<script>
// Vista previa de la imagen
document.getElementById('image').addEventListener('change', function() {
    const file = this.files[0];
    const previewContainer = document.getElementById('preview-container');
    const preview = document.getElementById('image-preview');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});

// Subida de imagen mediante AJAX
document.getElementById('upload-button').addEventListener('click', function() {
    const form = document.getElementById('upload-form');
    const formData = new FormData(form);
    const statusDiv = document.getElementById('upload-status');
    const uploadButton = this;
    
    uploadButton.disabled = true;
    uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    statusDiv.innerHTML = '<div class="alert alert-info">Subiendo imagen...</div>';
    
    fetch('media-upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = '<div class="alert alert-success">Imagen subida correctamente. Recargando página...</div>';
            setTimeout(() => {
                window.location.href = 'media.php?message=media-uploaded';
            }, 1500);
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            uploadButton.disabled = false;
            uploadButton.innerHTML = 'Subir';
        }
    })
    .catch(error => {
        statusDiv.innerHTML = '<div class="alert alert-danger">Error al subir la imagen.</div>';
        uploadButton.disabled = false;
        uploadButton.innerHTML = 'Subir';
        console.error('Error:', error);
    });
});

// Copiar URL de la imagen
document.querySelectorAll('.copy-url').forEach(button => {
    button.addEventListener('click', function() {
        const url = this.getAttribute('data-url');
        
        // Crear elemento temporal para copiar
        const temp = document.createElement('input');
        document.body.appendChild(temp);
        temp.value = url;
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        
        // Cambiar texto del botón temporalmente
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        
        setTimeout(() => {
            this.innerHTML = originalText;
        }, 2000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>