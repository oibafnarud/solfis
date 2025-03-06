
<?php
/**
 * Página de artículo individual (articulo.php)
 * Esta página muestra un artículo específico del blog con sus comentarios
 */

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Verificar que se proporcionó un slug
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header('Location: blog.php');
    exit;
}

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();
$comment = new Comment();

// Obtener el artículo por su slug
$post = $blogPost->getPostBySlug($slug);
if (!$post) {
    // Artículo no encontrado, redirigir a la página principal del blog
    header('Location: blog.php');
    exit;
}

// Obtener los comentarios aprobados para este artículo
$comments = $comment->getPostComments($post['id']);

// Obtener artículos relacionados
$relatedPosts = $blogPost->getRelatedPosts($post['id'], $post['category_id'], 3);

// Obtener todas las categorías para el menú lateral
$categories = $category->getCategories();

// Procesar formulario de comentarios
$commentSuccess = false;
$commentError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($name) || empty($email) || empty($content)) {
        $commentError = 'Por favor complete todos los campos.';
    } elseif (!Helpers::validateEmail($email)) {
        $commentError = 'Por favor ingrese un correo electrónico válido.';
    } else {
        // Crear nuevo comentario
        $commentData = [
            'post_id' => $post['id'],
            'name' => $name,
            'email' => $email,
            'content' => $content,
            'status' => REQUIRE_COMMENT_APPROVAL ? 'pending' : 'approved'
        ];
        
        if ($comment->createComment($commentData)) {
            $commentSuccess = true;
        } else {
            $commentError = 'Hubo un error al enviar su comentario. Por favor intente de nuevo.';
        }
    }
}

// Título de la página
$pageTitle = $post['title'];
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SolFis</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <!-- Incluir el encabezado del sitio -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Sección principal del artículo -->
    <section class="article-section py-5">
        <div class="container">
            <div class="row">
                <!-- Contenido del artículo -->
                <div class="col-lg-8">
                    <!-- Migas de pan -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
                            <li class="breadcrumb-item"><a href="?categoria=<?php echo $post['category_slug']; ?>"><?php echo $post['category_name']; ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $post['title']; ?></li>
                        </ol>
                    </nav>
                    
                    <!-- Artículo -->
                    <article class="blog-post">
                        <h1 class="blog-post-title mb-3"><?php echo $post['title']; ?></h1>
                        
                        <div class="blog-post-meta d-flex align-items-center mb-4">
                            <div class="d-flex align-items-center me-4">
                                <?php if (!empty($post['author_image'])): ?>
                                <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="rounded-circle me-2" width="40" height="40">
                                <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php endif; ?>
                                <span>Por <strong><?php echo $post['author_name']; ?></strong></span>
                            </div>
                            
                            <div class="d-flex align-items-center me-4">
                                <i class="far fa-calendar-alt me-1"></i>
                                <span><?php echo date('d M, Y', strtotime($post['published_at'])); ?></span>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <i class="far fa-folder me-1"></i>
                                <a href="?categoria=<?php echo $post['category_slug']; ?>" class="text-decoration-none"><?php echo $post['category_name']; ?></a>
                            </div>
                        </div>
                        
                        <?php if (!empty($post['image'])): ?>
                        <div class="blog-post-img mb-4">
                            <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>" class="img-fluid rounded">
                        </div>
                        <?php endif; ?>
                        
                        <div class="blog-post-content">
                            <?php echo $post['content']; ?>
                        </div>
                        
                        <!-- Compartir -->
                        <div class="blog-post-share mt-5">
                            <h5>Compartir este artículo</h5>
                            <div class="d-flex mt-3">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(Helpers::getCurrentUrl()); ?>" target="_blank" class="btn btn-outline-primary me-2">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode(Helpers::getCurrentUrl()); ?>" target="_blank" class="btn btn-outline-info me-2">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(Helpers::getCurrentUrl()); ?>&title=<?php echo urlencode($post['title']); ?>" target="_blank" class="btn btn-outline-secondary me-2">
                                    <i class="fab fa-linkedin-in"></i> LinkedIn
                                </a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' ' . Helpers::getCurrentUrl()); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                        
                        <!-- Autor -->
                        <?php if (!empty($post['author_bio'])): ?>
                        <div class="blog-post-author bg-light p-4 rounded mt-5">
                            <div class="d-flex">
                                <?php if (!empty($post['author_image'])): ?>
                                <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="rounded-circle me-3" width="70" height="70">
                                <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 70px; height: 70px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <h5>Acerca del autor</h5>
                                    <h6 class="fw-bold"><?php echo $post['author_name']; ?></h6>
                                    <p class="mb-0"><?php echo $post['author_bio']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Artículos relacionados -->
                        <?php if (!empty($relatedPosts)): ?>
                        <div class="blog-related-posts mt-5">
                            <h3 class="section-title">Artículos Relacionados</h3>
                            <div class="row mt-4">
                                <?php foreach ($relatedPosts as $relatedPost): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <?php if (!empty($relatedPost['image'])): ?>
                                        <img src="<?php echo $relatedPost['image']; ?>" class="card-img-top" alt="<?php echo $relatedPost['title']; ?>">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="blog/<?php echo $relatedPost['slug']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo $relatedPost['title']; ?>
                                                </a>
                                            </h5>
                                            <p class="card-text small text-muted"><?php echo date('d M, Y', strtotime($relatedPost['published_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Comentarios -->
                        <?php if (ENABLE_COMMENTS): ?>
                        <div class="blog-comments mt-5">
                            <h3 class="section-title">Comentarios (<?php echo count($comments); ?>)</h3>
                            
                            <?php if (!empty($comments)): ?>
                            <div class="comments-list mt-4">
                                <?php foreach ($comments as $comment): ?>
                                <div class="comment-item bg-light p-3 rounded mb-3">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="fw-bold"><?php echo $comment['name']; ?></h5>
                                        <small class="text-muted"><?php echo date('d M, Y H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo $comment['content']; ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mt-4">
                                <p class="mb-0">No hay comentarios aún. ¡Sé el primero en comentar!</p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Formulario de comentarios -->
                            <div class="comment-form mt-4">
                                <h4>Deja un comentario</h4>
                                
                                <?php if ($commentSuccess): ?>
                                <div class="alert alert-success">
                                    <?php if (REQUIRE_COMMENT_APPROVAL): ?>
                                    <p class="mb-0">Gracias por tu comentario. Será publicado una vez que sea aprobado por nuestro equipo.</p>
                                    <?php else: ?>
                                    <p class="mb-0">Gracias por tu comentario. Ha sido publicado correctamente.</p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($commentError): ?>
                                <div class="alert alert-danger">
                                    <p class="mb-0"><?php echo $commentError; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <form action="" method="post">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Nombre *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="content" class="form-label">Comentario *</label>
                                        <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" name="comment_submit" class="btn btn-primary">Enviar Comentario</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </article>
                </div>
                
                <!-- Barra lateral -->
                <div class="col-lg-4">
                    <!-- Buscador -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Buscar</h5>
                            <form action="blog-buscar.php" method="get">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Buscar artículos..." name="q" required>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categorías -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Categorías</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($categories as $cat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="?categoria=<?php echo $cat['slug']; ?>" class="text-decoration-none <?php echo $post['category_id'] == $cat['id'] ? 'fw-bold' : ''; ?>">
                                        <?php echo $cat['name']; ?>
                                    </a>
                                    <span class="badge bg-primary rounded-pill"><?php echo $cat['post_count']; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Suscripción al Newsletter -->
                    <div class="card bg-primary text-white shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Suscríbete a nuestro Newsletter</h5>
                            <p class="card-text">Recibe las últimas actualizaciones y consejos directamente en tu correo.</p>
                            <form action="suscribir.php" method="post">
                                <div class="mb-3">
                                    <input type="email" class="form-control" placeholder="Tu correo electrónico" name="email" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-light">Suscribirme</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Incluir el pie de página del sitio -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>