<?php
/**
 * Página de búsqueda del blog (blog-buscar.php)
 * Esta página muestra los resultados de búsqueda de artículos
 */

// Configuración básica
$site_title = "Resultados de búsqueda - Solfis";
$site_description = "Resultados de búsqueda en el blog de SolFis";
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Definir constante si no existe
if (!defined('POSTS_PER_PAGE')) {
    define('POSTS_PER_PAGE', 6);
}

// Parámetros de búsqueda y paginación
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Verificar que se proporcionó un término de búsqueda
if (empty($query)) {
    header('Location: blog.php');
    exit;
}

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();

// Función de búsqueda (a implementar en la clase BlogPost)
// Por ahora, simulamos resultados
$postsData = $blogPost->searchPosts($query, $page, POSTS_PER_PAGE);
$posts = $postsData['posts'];
$totalPages = $postsData['pages'];

// Obtener todas las categorías para el menú lateral
$categories = $category->getCategories();

// Título de la página
$pageTitle = 'Resultados de búsqueda: ' . $query;
$site_title = $pageTitle . ' - Solfis';
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
        <!-- Hero de Búsqueda -->
        <section class="blog-hero">
            <div class="container">
                <h1>Resultados de búsqueda</h1>
                <p>Mostrando resultados para: <strong><?php echo htmlspecialchars($query); ?></strong></p>
            </div>
        </section>
        
        <!-- Buscador y filtro para móvil -->
        <div class="container">
            <div class="mobile-search-filter">
                <div class="search-form-container">
                    <form action="blog-buscar.php" method="get" class="search-form">
                        <input type="text" name="q" placeholder="Buscar artículos..." value="<?php echo htmlspecialchars($query); ?>" required>
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <a href="blog.php" class="filter-toggle">
                    Volver <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <section class="blog-section">
            <div class="container">
                <!-- Contenido principal y sidebar -->
                <div class="blog-content">
                    <!-- Lista de artículos -->
                    <div class="articles-list">
                        <?php if (empty($posts)): ?>
                        <div class="notification notification-info">
                            <p>No se encontraron resultados para "<strong><?php echo htmlspecialchars($query); ?></strong>". Intenta con otros términos de búsqueda.</p>
                            <p><a href="blog.php" class="btn btn-primary mt-3">Volver al Blog</a></p>
                        </div>
                        <?php else: ?>
                            <!-- Grid de artículos -->
                            <div class="articles-grid">
                                <?php foreach ($posts as $post): ?>
                                <div class="article-card">
                                    <div class="article-image">
                                        <?php if (!empty($post['image'])): ?>
                                        <img src="<?php echo $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php else: ?>
                                        <img src="img/blog/default.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php endif; ?>
                                        <span class="article-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                    </div>
                                    <div class="article-content">
                                        <h3 class="article-title">
                                            <a href="articulo.php?slug=<?php echo urlencode($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                        </h3>
                                        <p class="article-excerpt"><?php echo htmlspecialchars(Helpers::truncate($post['excerpt'], 120)); ?></p>
                                        <div class="article-meta">
                                            <div class="article-author">
                                                <?php if (!empty($post['author_image'])): ?>
                                                <img src="<?php echo $post['author_image']; ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="author-avatar">
                                                <?php endif; ?>
                                                <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                            </div>
                                            <div class="article-date">
                                                <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                                            </div>
                                        </div>
                                        <a href="articulo.php?slug=<?php echo urlencode($post['slug']); ?>" class="read-more">Leer más →</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i === $page): ?>
                                    <span class="current-page"><?php echo $i; ?></span>
                                    <?php else: ?>
                                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>" class="page-number">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sidebar (Desktop) -->
                    <div class="blog-sidebar">
                        <!-- Búsqueda -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Refinar búsqueda</h3>
                            <div class="search-form-container">
                                <form action="blog-buscar.php" method="get" class="search-form">
                                    <input type="text" name="q" placeholder="Buscar artículos..." value="<?php echo htmlspecialchars($query); ?>" required>
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
                                    <a href="blog.php?categoria=<?php echo urlencode($cat['slug']); ?>" class="category-link">
                                        <?php echo htmlspecialchars($cat['name']); ?>
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
                
                <!-- Sección de Newsletter para móvil (al final) -->
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
            disable: window.innerWidth < 768 // Desactivar AOS en móvil para mejor rendimiento
        });
    </script>
</body>
</html>