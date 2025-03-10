<?php
/**
 * Página principal del blog (blog.php)
 * Esta página muestra la lista de artículos del blog con paginación y filtros por categoría
 */

// Configuración básica
$site_title = "Blog - Solfis";
$site_description = "Información actualizada sobre contabilidad, finanzas, impuestos y gestión empresarial para profesionales y empresarios";
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Definir constante si no existe
if (!defined('POSTS_PER_PAGE')) {
    define('POSTS_PER_PAGE', 6);
}

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$categorySlug = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// Obtener artículos con paginación y filtros
$postsData = $blogPost->getPosts($page, POSTS_PER_PAGE, $categorySlug);
$posts = $postsData['posts'];
$totalPages = $postsData['pages'];

// Obtener todas las categorías para el menú lateral
$categories = $category->getCategories();

// Obtener información de la categoría actual si hay filtro
$currentCategory = null;
if ($categorySlug) {
    $currentCategory = $category->getCategoryBySlug($categorySlug);
}

// Título de la página
$pageTitle = $currentCategory ? 'Blog - ' . $currentCategory['name'] : 'Blog';
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
        <!-- Hero del Blog -->
        <section class="blog-hero">
            <div class="container">
                <h1>Blog de SolFis</h1>
                <p>Información actualizada sobre contabilidad, finanzas, impuestos y gestión empresarial para profesionales y empresarios</p>
            </div>
        </section>
        
        <!-- Contenido Principal -->
        <section class="blog-section">
            <div class="container">
                <!-- Mostrar mensaje si hay uno -->
                <?php if (isset($_GET['subscription'])): ?>
                    <?php if ($_GET['subscription'] === 'success'): ?>
                        <div class="notification notification-success">
                            <p>¡Gracias por suscribirte! Recibirás nuestras últimas actualizaciones en tu correo.</p>
                        </div>
                    <?php elseif ($_GET['subscription'] === 'invalid-email'): ?>
                        <div class="notification notification-error">
                            <p>Por favor, ingresa un correo electrónico válido.</p>
                        </div>
                    <?php elseif ($_GET['subscription'] === 'error'): ?>
                        <div class="notification notification-error">
                            <p>
                                <?php echo isset($_GET['message']) ? $_GET['message'] : 'Ocurrió un error al procesar tu suscripción. Por favor, intenta nuevamente.'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Filtro de Categorías -->
                <div class="blog-filters">
                    <div class="filter-buttons">
                        <a href="blog.php" class="filter-btn <?php echo !$categorySlug ? 'active' : ''; ?>">Todos</a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="?categoria=<?php echo $cat['slug']; ?>" class="filter-btn <?php echo $categorySlug === $cat['slug'] ? 'active' : ''; ?>">
                            <?php echo $cat['name']; ?> (<?php echo $cat['post_count']; ?>)
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Contenido principal y sidebar -->
                <div class="blog-content">
                    <!-- Lista de artículos -->
                    <div class="articles-list">
                        <?php if (empty($posts)): ?>
                        <div class="notification notification-info">
                            <p>No hay artículos disponibles en este momento.</p>
                        </div>
                        <?php else: ?>
                            <!-- Grid de artículos -->
                            <div class="articles-grid">
                                <?php foreach ($posts as $post): ?>
                                <div class="article-card">
                                    <div class="article-image">
                                        <?php if (!empty($post['image'])): ?>
                                        <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                                        <?php else: ?>
                                        <img src="img/blog/default.jpg" alt="<?php echo $post['title']; ?>">
                                        <?php endif; ?>
                                        <span class="article-category"><?php echo $post['category_name']; ?></span>
                                    </div>
                                    <div class="article-content">
                                        <h3 class="article-title">
                                            <a href="articulo.php?slug=<?php echo $post['slug']; ?>"><?php echo $post['title']; ?></a>
                                        </h3>
                                        <p class="article-excerpt"><?php echo Helpers::truncate($post['excerpt'], 120); ?></p>
                                        <div class="article-meta">
                                            <div class="article-author">
                                                <?php if (!empty($post['author_image'])): ?>
                                                <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="author-avatar">
                                                <?php endif; ?>
                                                <span class="author-name"><?php echo $post['author_name']; ?></span>
                                            </div>
                                            <div class="article-date">
                                                <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                                            </div>
                                        </div>
                                        <a href="articulo.php?slug=<?php echo $post['slug']; ?>" class="read-more">Leer más →</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i === $page): ?>
                                    <span class="current-page"><?php echo $i; ?></span>
                                    <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>" class="page-number">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="blog-sidebar">
                        <!-- Búsqueda -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Buscar</h3>
                            <div class="search-form-container">
                                <form action="blog-buscar.php" method="get" class="search-form">
                                    <input type="text" name="q" placeholder="Buscar artículos..." required>
                                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Categorías -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Categorías</h3>
                            <ul class="categories-list">
                                <?php foreach ($categories as $cat): ?>
                                <li class="category-item">
                                    <a href="?categoria=<?php echo $cat['slug']; ?>" class="category-link">
                                        <?php echo $cat['name']; ?>
                                        <span class="count">(<?php echo $cat['post_count']; ?>)</span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Suscripción al Newsletter -->
                        <div class="sidebar-section newsletter-section">
                            <h3 class="sidebar-title">Suscríbete al Newsletter</h3>
                            <p>Recibe las últimas actualizaciones y consejos directamente en tu correo.</p>
                            <form action="suscribir.php" method="post" class="newsletter-form-sidebar">
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