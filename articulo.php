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
    // Procesamiento de comentarios
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $content = $_POST['content'] ?? '';
    $website = $_POST['website'] ?? ''; // Honeypot
    $captcha = isset($_POST['captcha']) ? (int)$_POST['captcha'] : 0;
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validación del formulario
    if (empty($name) || empty($email) || empty($content)) {
        $commentError = 'Por favor complete todos los campos.';
    } 
    elseif (!empty($website)) {
        // El honeypot debería estar vacío - si tiene contenido es probablemente un bot
        $commentError = 'Error de validación. Por favor intente nuevamente.';
    }
    elseif (!isset($_SESSION['captcha_result']) || $captcha !== $_SESSION['captcha_result']) {
        $commentError = 'La respuesta al captcha es incorrecta.';
    }
    elseif (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $commentError = 'Error de validación del formulario. Por favor intente nuevamente.';
    }
    elseif (!Helpers::validateEmail($email)) {
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
            
            // Limpiar datos de sesión
            unset($_SESSION['captcha_result']);
            unset($_SESSION['csrf_token']);
        } else {
            $commentError = 'Hubo un error al enviar su comentario. Por favor intente de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $site_title; ?>">
    <meta property="og:description" content="<?php echo $site_description; ?>">
    <?php if (!empty($post['image'])): ?>
    <meta property="og:image" content="<?php echo Helpers::getCurrentUrl() . '/' . $post['image']; ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?php echo Helpers::getCurrentUrl(); ?>">
    <meta property="og:type" content="article">
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    
    <!-- CSS Componentes -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/articulo.css">
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
        <!-- Hero para el artículo -->
        <section class="article-hero">
            <div class="container">
                <div class="hero-breadcrumbs">
                    <a href="index.php">Inicio</a>
                    <span class="separator">/</span>
                    <a href="blog.php">Blog</a>
                    <span class="separator">/</span>
                    <a href="blog.php?categoria=<?php echo $post['category_slug']; ?>"><?php echo $post['category_name']; ?></a>
                </div>
                <h1><?php echo $post['title']; ?></h1>
                <div class="article-meta-hero">
                    <div class="article-author">
                        <?php if (!empty($post['author_image'])): ?>
                        <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="author-avatar">
                        <?php else: ?>
                        <img src="img/default-avatar.jpg" alt="<?php echo $post['author_name']; ?>" class="author-avatar">
                        <?php endif; ?>
                        <span>Por <strong><?php echo $post['author_name']; ?></strong></span>
                    </div>
                    <div class="article-date">
                        <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contenido del artículo con layout mejorado -->
        <section class="article-section">
            <div class="container">
                <div class="article-layout">
                    <!-- Contenido principal del artículo -->
                    <div class="article-main">
                        <!-- Imagen destacada con tamaño controlado -->
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
                        
                        <!-- Autor bio -->
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
                                <div class="related-article-card">
                                    <div class="related-article-image">
                                        <?php if (!empty($relatedPost['image'])): ?>
                                        <img src="<?php echo $relatedPost['image']; ?>" alt="<?php echo $relatedPost['title']; ?>">
                                        <?php else: ?>
                                        <img src="img/blog/default.jpg" alt="<?php echo $relatedPost['title']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="related-article-content">
                                        <h4>
                                            <a href="articulo.php?slug=<?php echo $relatedPost['slug']; ?>"><?php echo $relatedPost['title']; ?></a>
                                        </h4>
                                        <div class="related-article-meta">
                                            <span class="category"><?php echo $relatedPost['category_name']; ?></span>
                                            <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($relatedPost['published_at'])); ?></span>
                                        </div>
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
                                    <div class="comment-avatar">
                                        <img src="img/default-avatar.jpg" alt="<?php echo $commentItem['name']; ?>">
                                    </div>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <h4 class="comment-author"><?php echo $commentItem['name']; ?></h4>
                                            <time class="comment-date"><?php echo date('d M, Y H:i', strtotime($commentItem['created_at'])); ?></time>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo $commentItem['content']; ?>
                                        </div>
                                    </div>
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
                                <h3>Deja un comentario</h3>
                                
                                <?php if ($commentSuccess): ?>
                                <div class="notification notification-success">
                                    <?php if (REQUIRE_COMMENT_APPROVAL): ?>
                                    <p>Gracias por tu comentario. Será publicado una vez que sea aprobado por nuestro equipo.</p>
                                    <?php else: ?>
                                    <p>Gracias por tu comentario. Ha sido publicado correctamente.</p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($commentError): ?>
                                <div class="notification notification-error">
                                    <p><?php echo $commentError; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <form action="" method="post">
                                    <div class="comment-form-grid">
                                        <div class="form-group">
                                            <label for="name">Nombre *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Campo honeypot anti-spam (invisible) -->
                                    <div class="form-group" style="display:none;">
                                        <label for="website">Sitio web (dejar vacío)</label>
                                        <input type="text" class="form-control" id="website" name="website">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="content">Comentario *</label>
                                        <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                                    </div>
                                    
                                    <!-- Captcha simple -->
                                    <div class="form-group">
                                        <label for="captcha">Verificación anti-spam *</label>
                                        <div class="captcha-container">
                                            <?php $num1 = rand(1, 10); $num2 = rand(1, 10); echo "$num1 + $num2 = ?"; $_SESSION['captcha_result'] = $num1 + $num2; ?>
                                        </div>
                                        <input type="number" class="form-control" id="captcha" name="captcha" required>
                                    </div>
                                    
                                    <!-- Token CSRF -->
                                    <?php $csrf_token = md5(uniqid(rand(), true)); $_SESSION['csrf_token'] = $csrf_token; ?>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <button type="submit" name="comment_submit" class="form-submit">
                                        Enviar Comentario <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sidebar para desktop -->
                    <div class="article-sidebar">
                        <!-- Newsletter -->
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
            disable: window.innerWidth < 768 // Desactivar AOS en móvil para mejor rendimiento
        });
    </script>
</body>
</html>