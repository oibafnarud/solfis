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
    
    <!-- Estilos adicionales para mejorar la estructura y experiencia móvil -->
    <style>
        /* Estilos para mejorar la estructura */
        .blog-hero {
            background-color: #f8f9fa;
            padding: 50px 0 30px;
            margin-bottom: 0;
        }
        
        /* Nuevo menú de filtro con toggle */
        .filter-container {
            background-color: #f1f1f1;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .filter-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .filter-title i {
            margin-right: 5px;
        }
        
        .filter-toggle {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .filter-toggle i {
            margin-left: 5px;
            transition: transform 0.3s;
        }
        
        .filter-toggle.active i {
            transform: rotate(180deg);
        }
        
        .filter-content {
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease;
        }
        
        .filter-content.show {
            max-height: 500px;
        }
        
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .filter-btn {
            margin-bottom: 0;
        }
        
        /* Búsqueda en la parte superior para móvil */
        .mobile-search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: stretch;
        }
        
        .mobile-search-filter .search-form-container {
            flex-grow: 1;
        }
        
        .mobile-search-filter .filter-toggle {
            height: auto;
            white-space: nowrap;
        }
        
        .mobile-search-filter .search-form {
            height: 100%;
        }
        
        .mobile-search-filter .search-form input,
        .mobile-search-filter .search-btn {
            height: 100%;
        }
        
        /* Reorganizar para móvil */
        @media (max-width: 991px) {
            .blog-content {
                display: block;
            }
            
            .desktop-filters {
                display: none;
            }
            
            .blog-sidebar {
                display: none; /* Ocultar sidebar completo en móvil */
            }
            
            .mobile-newsletter {
                margin-top: 40px;
                margin-bottom: 20px;
            }
        }
        
        @media (min-width: 992px) {
            .mobile-search-filter,
            .mobile-newsletter {
                display: none;
            }
            
            .filter-toggle {
                display: none;
            }
            
            .filter-content {
                max-height: none;
            }
        }
    </style>
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
        
        <!-- Filtros de Categorías (ahora pegadizos con toggle) -->
        <div class="filter-container desktop-filters">
            <div class="container">
                <div class="filter-header">
                    <h2 class="filter-title"><i class="fas fa-filter"></i> Categorías</h2>
                </div>
                <div class="filter-content show">
                    <div class="filter-buttons">
                        <a href="blog.php" class="filter-btn <?php echo !$categorySlug ? 'active' : ''; ?>">Todos</a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="?categoria=<?php echo $cat['slug']; ?>" class="filter-btn <?php echo $categorySlug === $cat['slug'] ? 'active' : ''; ?>">
                            <?php echo $cat['name']; ?> (<?php echo $cat['post_count']; ?>)
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Buscador y filtro para móvil -->
        <div class="container">
            <div class="mobile-search-filter">
                <div class="search-form-container">
                    <form action="blog-buscar.php" method="get" class="search-form">
                        <input type="text" name="q" placeholder="Buscar artículos..." required>
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <button type="button" class="filter-toggle" id="filterToggle">
                    Filtro <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <!-- Menú desplegable de filtros para móvil -->
            <div class="filter-content" id="filterContent">
                <div class="filter-buttons">
                    <a href="blog.php" class="filter-btn <?php echo !$categorySlug ? 'active' : ''; ?>">Todos</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="?categoria=<?php echo $cat['slug']; ?>" class="filter-btn <?php echo $categorySlug === $cat['slug'] ? 'active' : ''; ?>">
                        <?php echo $cat['name']; ?> (<?php echo $cat['post_count']; ?>)
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
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
                    
                    <!-- Sidebar (Desktop) -->
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
        
        // Script para controlar el desplegable de filtros
        document.addEventListener('DOMContentLoaded', function() {
            const filterToggle = document.getElementById('filterToggle');
            const filterContent = document.getElementById('filterContent');
            
            if (filterToggle && filterContent) {
                filterToggle.addEventListener('click', function() {
                    filterContent.classList.toggle('show');
                    filterToggle.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>