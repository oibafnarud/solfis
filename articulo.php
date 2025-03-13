<?php
/**
 * Página de artículo individual (articulo.php)
 */

// Configuración básica
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Verificar que se proporcionó un slug
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header('Location: blog.php');
    exit;
}

// Definir constantes si no existen
if (!defined('ENABLE_COMMENTS')) {
    define('ENABLE_COMMENTS', true);
}
if (!defined('REQUIRE_COMMENT_APPROVAL')) {
    define('REQUIRE_COMMENT_APPROVAL', true);
}

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();
$comment = new Comment();

// Obtener el artículo por su slug
$post = $blogPost->getPostBySlug($slug);
if (!$post) {
    header('Location: blog.php');
    exit;
}

// Obtener los comentarios aprobados
$comments = $comment->getPostComments($post['id']);

// Obtener artículos relacionados
$relatedPosts = $blogPost->getRelatedPosts($post['id'], $post['category_id'], 3);

// Título y descripción de la página
$site_title = $post['title'] . " - Blog SolFis";
$site_description = $post['excerpt'];

// Procesar formulario de comentarios
$commentSuccess = false;
$commentError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    // Procesamiento de comentarios...
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    
    <!-- CSS Componentes -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/blog.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/text-contrast-fixes.css">
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>
    
    <main>
        <!-- Migas de pan -->
        <div class="breadcrumbs">
            <div class="container">
                <a href="index.php">Inicio</a>
                <span class="separator">/</span>
                <a href="blog.php">Blog</a>
                <span class="separator">/</span>
                <a href="blog.php?categoria=<?php echo $post['category_slug']; ?>"><?php echo $post['category_name']; ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo $post['title']; ?></span>
            </div>
        </div>
        
        <!-- Buscador sólo para móvil -->
        <div class="container">
            <div class="mobile-search-filter">
                <div class="search-form-container">
                    <form action="blog-buscar.php" method="get" class="search-form">
                        <input type="text" name="q" placeholder="Buscar artículos..." required>
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <a href="blog.php" class="filter-toggle">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <!-- Contenido del artículo -->
        <section class="blog-section">
            <div class="container">
                <div class="blog-content">
                    <!-- Artículo principal -->
                    <div class="article-container">
                        <article class="article">
                            <!-- Encabezado -->
                            <header class="article-header">
                                <div class="article-meta">
                                    <span class="article-category"><?php echo $post['category_name']; ?></span>
                                    <time class="article-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                                    </time>
                                </div>
                                
                                <h1 class="article-title"><?php echo $post['title']; ?></h1>
                                
                                <div class="article-author">
                                    <?php if (!empty($post['author_image'])): ?>
                                    <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="author-avatar">
                                    <?php else: ?>
                                    <img src="img/default-avatar.jpg" alt="<?php echo $post['author_name']; ?>" class="author-avatar">
                                    <?php endif; ?>
                                    <span class="author-name">Por <strong><?php echo $post['author_name']; ?></strong></span>
                                </div>
                            </header>
                            
                            <!-- Imagen destacada -->
                            <?php if (!empty($post['image'])): ?>
                            <div class="article-featured-image">
                                <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                            </div>
                            <?php endif; ?>
                            
                            <!-- Contenido del artículo -->
                            <div class="article-content">
                                <?php echo $post['content']; ?>
                            </div>
                            
                            <!-- Compartir en redes -->
                            <div class="article-share">
                                <h3>Compartir este artículo</h3>
                                <div class="share-buttons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(Helpers::getCurrentUrl()); ?>" target="_blank" class="share-button" title="Compartir en Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode(Helpers::getCurrentUrl()); ?>" target="_blank" class="share-button" title="Compartir en Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(Helpers::getCurrentUrl()); ?>&title=<?php echo urlencode($post['title']); ?>" target="_blank" class="share-button" title="Compartir en LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' ' . Helpers::getCurrentUrl()); ?>" target="_blank" class="share-button" title="Compartir por WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Autor biografía -->
                            <?php if (!empty($post['author_bio'])): ?>
                            <div class="article-author-bio">
                                <div class="author-image">
                                    <?php if (!empty($post['author_image'])): ?>
                                    <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>">
                                    <?php else: ?>
                                    <img src="img/default-avatar.jpg" alt="<?php echo $post['author_name']; ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="author-info">
                                    <h3>Acerca del autor</h3>
                                    <h4 class="author-name"><?php echo $post['author_name']; ?></h4>
                                    <p class="author-bio"><?php echo $post['author_bio']; ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Artículos relacionados -->
                            <?php if (!empty($relatedPosts)): ?>
                            <div class="related-articles">
                                <h3>Artículos Relacionados</h3>
                                <div class="related-articles-grid">
                                    <?php foreach ($relatedPosts as $relatedPost): ?>
                                    <div class="article-card">
                                        <div class="article-image">
                                            <?php if (!empty($relatedPost['image'])): ?>
                                            <img src="<?php echo $relatedPost['image']; ?>" alt="<?php echo $relatedPost['title']; ?>">
                                            <?php else: ?>
                                            <img src="img/blog/default.jpg" alt="<?php echo $relatedPost['title']; ?>">
                                            <?php endif; ?>
                                            <span class="article-category"><?php echo $relatedPost['category_name']; ?></span>
                                        </div>
                                        <div class="article-content">
                                            <h3 class="article-title">
                                                <a href="articulo.php?slug=<?php echo $relatedPost['slug']; ?>"><?php echo $relatedPost['title']; ?></a>
                                            </h3>
                                            <div class="article-date">
                                                <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($relatedPost['published_at'])); ?>
                                            </div>
                                            <a href="articulo.php?slug=<?php echo $relatedPost['slug']; ?>" class="read-more">Leer más →</a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Comentarios -->
                            <?php if (ENABLE_COMMENTS): ?>
                            <div class="comments-section">
                                <h3>Comentarios (<?php echo count($comments); ?>)</h3>
                                
                                <?php if (!empty($comments)): ?>
                                <div class="comments-list">
                                    <?php foreach ($comments as $commentItem): ?>
                                    <div class="comment">
                                        <!-- Contenido del comentario -->
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="notification notification-info">
                                    <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Formulario de comentarios -->
                                <div class="comment-form">
                                    <!-- Formulario de comentarios -->
                                </div>
                            </div>
                            <?php endif; ?>
                        </article>
                    </div>
                    
                    <!-- Sidebar simplificado para desktop -->
                    <div class="blog-sidebar">
                        <!-- Solo Newsletter -->
                        <div class="sidebar-section newsletter-section">
                            <h3 class="sidebar-title">Suscríbete al Newsletter</h3>
                            <p>Recibe las últimas actualizaciones y consejos directamente en tu correo.</p>
                            <form action="suscribir.php" method="post" class="newsletter-form-sidebar">
                                <div class="form-group">
                                    <input type="text" class="newsletter-input" placeholder="Tu nombre (opcional)" name="name">
                                </div>
                                <div class="form-group">
                                    <input type="email" class="newsletter-input" placeholder="Tu correo electrónico" name="email" required>
                                </div>
                                <button type="submit" class="subscribe-btn">
                                    Suscribirme
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Newsletter para móvil (al final) -->
                <div class="mobile-newsletter">
                    <div class="sidebar-section newsletter-section">
                        <h3 class="sidebar-title">Suscríbete al Newsletter</h3>
                        <p>Recibe las últimas actualizaciones y consejos directamente en tu correo.</p>
                        <form action="suscribir.php" method="post" class="newsletter-form-sidebar">
                            <div class="form-group">
                                <input type="text" class="newsletter-input" placeholder="Tu nombre (opcional)" name="name">
                            </div>
                            <div class="form-group">
                                <input type="email" class="newsletter-input" placeholder="Tu correo electrónico" name="email" required>
                            </div>
                            <button type="submit" class="subscribe-btn">
                                Suscribirme
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>
    
    <!-- Scripts -->
    <script src="<?php echo $assets_path; ?>js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    
    <!-- AOS Inicialización -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Inicialización de AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            disable: window.innerWidth < 768
        });
    </script>
</body>
</html>