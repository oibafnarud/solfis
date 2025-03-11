<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/post-edit.php - Página para crear/editar artículos
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

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();
$user = new User();
$media = new Media();

// Determinar si estamos editando o creando
$isEditing = isset($_GET['id']) && !empty($_GET['id']);
$postId = $isEditing ? (int)$_GET['id'] : null;
$post = $isEditing ? $blogPost->getPostById($postId) : null;

// Si estamos editando y el post no existe, redirigir
if ($isEditing && !$post) {
    header('Location: posts.php?message=post-not-found');
    exit;
}

// Procesar el formulario de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $title = $_POST['title'];
    $slug = empty($_POST['slug']) ? Helpers::slugify($title) : $_POST['slug'];
    $content = $_POST['content'];
    $excerpt = $_POST['excerpt'];
    $categoryId = (int)$_POST['category_id'];
    $status = $_POST['status'];
    $image = $_POST['featured_image'];
    
    // Asegurar que el slug sea único si estamos creando o cambiando el slug
    if (!$isEditing || ($isEditing && $slug !== $post['slug'])) {
        $count = 1;
        $originalSlug = $slug;
        
        // Validar que el slug sea único (sencillo en este caso)
        $db = Database::getInstance();
        
        do {
            $sql = "SELECT COUNT(*) as count FROM posts WHERE slug = '$slug'";
            if ($isEditing) {
                $sql .= " AND id != $postId";
            }
            
            $result = $db->query($sql);
            $slugExists = $result->fetch_assoc()['count'] > 0;
            
            if ($slugExists) {
                $slug = $originalSlug . '-' . $count++;
            }
        } while ($slugExists);
    }
    
    // Preparar datos para guardar
    $postData = [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'excerpt' => $excerpt,
        'category_id' => $categoryId,
        'status' => $status,
        'image' => $image
    ];
    
    // Si es un nuevo post, agregar autor y fecha
    if (!$isEditing) {
        $postData['author_id'] = $auth->getUserId();
        $postData['published_at'] = $status === 'published' ? date('Y-m-d H:i:s') : null;
    } 
    // Si estamos publicando un borrador, actualizar fecha de publicación
    else if ($post['status'] !== 'published' && $status === 'published') {
        $postData['published_at'] = date('Y-m-d H:i:s');
    }
    
    // Guardar el post
    if ($isEditing) {
        if ($blogPost->updatePost($postId, $postData)) {
            header('Location: posts.php?message=post-updated');
            exit;
        }
    } else {
        if ($newPostId = $blogPost->createPost($postData)) {
            header('Location: post-edit.php?id=' . $newPostId . '&message=post-created');
            exit;
        }
    }
}

// Obtener categorías para el formulario
$categories = $category->getCategories();

// Obtener imágenes para la galería
$mediaItems = $media->getImages(1, 50)['images'];

// Título de la página
$pageTitle = $isEditing ? 'Editar Artículo - Panel de Administración' : 'Nuevo Artículo - Panel de Administración';

// Mensajes de notificación
$messages = [
    'post-created' => ['type' => 'success', 'text' => 'Artículo creado correctamente.'],
    'media-uploaded' => ['type' => 'success', 'text' => 'Imagen subida correctamente.'],
];

$notification = null;
if (isset($_GET['message']) && array_key_exists($_GET['message'], $messages)) {
    $notification = $messages[$_GET['message']];
}
?>

<?php include 'includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $isEditing ? 'Editar Artículo' : 'Nuevo Artículo'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="posts.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Artículos
                            </a>
                            <?php if ($isEditing && $post['status'] === 'published'): ?>
                            <a href="../blog/<?php echo $post['slug']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver Artículo
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
                
                <form action="<?php echo $_SERVER['PHP_SELF'] . ($isEditing ? '?id=' . $postId : ''); ?>" method="post" id="post-form">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $isEditing ? $post['title'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="slug" class="form-label">URL Amigable (Slug)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">/blog/</span>
                                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $isEditing ? $post['slug'] : ''; ?>" placeholder="Se generará automáticamente desde el título">
                                        </div>
                                        <div class="form-text">Deje en blanco para generar automáticamente desde el título.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="excerpt" class="form-label">Extracto</label>
                                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo $isEditing ? $post['excerpt'] : ''; ?></textarea>
                                        <div class="form-text">Breve resumen del artículo que aparecerá en listados.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="content" class="form-label">Contenido</label>
                                        <textarea class="form-control editor" id="content" name="content" rows="15"><?php echo $isEditing ? $post['content'] : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Panel de Publicación -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Publicación</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Estado</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo $isEditing && $post['status'] === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                            <option value="published" <?php echo $isEditing && $post['status'] === 'published' ? 'selected' : ''; ?>>Publicado</option>
                                            <option value="archived" <?php echo $isEditing && $post['status'] === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                                        </select>
                                    </div>
                                    
                                    <?php if ($isEditing): ?>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Fecha de Creación:</strong></p>
                                        <p><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                                        
                                        <?php if ($post['published_at']): ?>
                                        <p class="mb-1"><strong>Fecha de Publicación:</strong></p>
                                        <p><?php echo date('d/m/Y H:i', strtotime($post['published_at'])); ?></p>
                                        <?php endif; ?>
                                        
                                        <p class="mb-1"><strong>Última actualización:</strong></p>
                                        <p><?php echo date('d/m/Y H:i', strtotime($post['updated_at'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <?php echo $isEditing ? 'Actualizar Artículo' : 'Publicar Artículo'; ?>
                                        </button>
                                        
                                        <?php if ($isEditing): ?>
                                        <a href="post-delete.php?id=<?php echo $postId; ?>" class="btn btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar este artículo?');">
                                            Eliminar Artículo
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Panel de Categoría -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Categoría</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <select class="form-select" id="category_id" name="category_id">
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $isEditing && $post['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo $cat['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <a href="category-new.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus-circle"></i> Añadir Nueva Categoría
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Panel de Imagen Destacada -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Imagen Destacada</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div id="featured-image-preview" class="text-center mb-3">
                                            <?php if ($isEditing && !empty($post['image'])): ?>
                                            <img src="<?php echo '../' . $post['image']; ?>" alt="Imagen destacada" class="img-fluid mb-2">
                                            <?php else: ?>
                                            <p class="text-muted">Sin imagen destacada</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <input type="hidden" id="featured_image" name="featured_image" value="<?php echo $isEditing ? $post['image'] : ''; ?>">
                                        
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mediaModal">
                                                <i class="fas fa-image"></i> Seleccionar Imagen
                                            </button>
                                            
                                            <?php if ($isEditing && !empty($post['image'])): ?>
                                            <button type="button" class="btn btn-outline-danger" id="remove-featured-image">
                                                <i class="fas fa-trash"></i> Eliminar Imagen
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
</div>

<!-- Modal de Selección de Medios -->
<div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaModalLabel">Biblioteca de Medios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Subida de imágenes -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Subir Nueva Imagen</h6>
                        <form action="media-upload.php" method="post" enctype="multipart/form-data" id="upload-form">
                            <div class="mb-3">
                                <input class="form-control" type="file" id="image_upload" name="image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Subir Imagen</button>
                        </form>
                        <div id="upload-status" class="mt-2"></div>
                    </div>
                </div>
                
                <!-- Galería de imágenes -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Imágenes Disponibles</h6>
                        <div class="row" id="media-gallery">
                            <?php foreach ($mediaItems as $item): ?>
                            <div class="col-md-2 mb-3">
                                <div class="card h-100">
                                    <img src="<?php echo '../' . $item['path']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                                    <div class="card-body p-2 text-center">
                                        <button type="button" class="btn btn-sm btn-primary select-media" data-path="<?php echo $item['path']; ?>">
                                            Seleccionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/xe58s62knh4hnh0usw77zym9kjn2hfqh9ufueaiyof57ts4w/tinymce/5/tinymce.min.js"></script>
<script>
// Inicializar el editor TinyMCE
tinymce.init({
    selector: '.editor',
    height: 500,
    menubar: true,
    plugins: [
	  // Core editing features
	  'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
	  // Your account includes a free trial of TinyMCE premium features
	  // Try the most popular premium features until Mar 25, 2025:
	  'checklist', 'mediaembed', 'casechange', 'export', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
	],
	toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
	tinycomments_mode: 'embedded',
	tinycomments_author: 'Author name',
	mergetags_list: [
	  { value: 'First.Name', title: 'First Name' },
	  { value: 'Email', title: 'Email' },
	],
	ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
});

// Generar slug automáticamente desde el título
document.getElementById('title').addEventListener('blur', function() {
    const slugField = document.getElementById('slug');
    if (slugField.value === '') {
        const title = this.value;
        const slug = title.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugField.value = slug;
    }
});

// Seleccionar imagen de la galería
document.querySelectorAll('.select-media').forEach(function(button) {
    button.addEventListener('click', function() {
        const path = this.getAttribute('data-path');
        document.getElementById('featured_image').value = path;
        
        // Actualizar vista previa
        const preview = document.getElementById('featured-image-preview');
        preview.innerHTML = `<img src="../${path}" alt="Imagen destacada" class="img-fluid mb-2">`;
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
        modal.hide();
    });
});

// Remover imagen destacada
const removeButton = document.getElementById('remove-featured-image');
if (removeButton) {
    removeButton.addEventListener('click', function() {
        document.getElementById('featured_image').value = '';
        document.getElementById('featured-image-preview').innerHTML = '<p class="text-muted">Sin imagen destacada</p>';
    });
}

// Subida de imágenes mediante AJAX
document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const statusDiv = document.getElementById('upload-status');
    
    statusDiv.innerHTML = '<div class="alert alert-info">Subiendo imagen...</div>';
    
    fetch('media-upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = '<div class="alert alert-success">Imagen subida correctamente.</div>';
            
            // Agregar la nueva imagen a la galería
            const gallery = document.getElementById('media-gallery');
            const newItem = document.createElement('div');
            newItem.className = 'col-md-2 mb-3';
            newItem.innerHTML = `
                <div class="card h-100">
                    <img src="../${data.file}" class="card-img-top" alt="Nueva imagen">
                    <div class="card-body p-2 text-center">
                        <button type="button" class="btn btn-sm btn-primary select-media" data-path="${data.file}">
                            Seleccionar
                        </button>
                    </div>
                </div>
            `;
            gallery.prepend(newItem);
            
            // Actualizar evento para el nuevo botón
            newItem.querySelector('.select-media').addEventListener('click', function() {
                const path = this.getAttribute('data-path');
                document.getElementById('featured_image').value = path;
                
                // Actualizar vista previa
                const preview = document.getElementById('featured-image-preview');
                preview.innerHTML = `<img src="../${path}" alt="Imagen destacada" class="img-fluid mb-2">`;
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
                modal.hide();
            });
            
            // Limpiar el campo de archivo
            document.getElementById('image_upload').value = '';
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        statusDiv.innerHTML = '<div class="alert alert-danger">Error al subir la imagen.</div>';
        console.error('Error:', error);
    });
});
</script>

<?php include 'includes/footer.php'; ?>