<?php
$site_title = "Blog - Solfis";
$site_description = "Manténgase informado sobre las últimas novedades fiscales y financieras en República Dominicana";
$base_path = 'sections/';
$assets_path = 'assets/';

// Esta sección simula la conexión a la base de datos del CMS
// En una implementación real, esta funcionalidad estaría en un archivo separado
function get_posts($category = null, $limit = 6, $offset = 0) {
    // Simulación de posts desde un CMS
    $posts = [
        [
            'id' => 1,
            'title' => 'Principales Cambios Fiscales para 2025',
            'excerpt' => 'Análisis detallado de las nuevas regulaciones fiscales y su impacto en las empresas dominicanas.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post1.jpg',
            'category' => 'fiscal',
            'category_name' => 'Fiscal',
            'author' => 'Juan Pérez',
            'author_image' => 'img/team/author1.jpg',
            'date' => '2025-02-15',
            'slug' => 'cambios-fiscales-2025'
        ],
        [
            'id' => 2,
            'title' => 'Transformación Digital en la Contabilidad Empresarial',
            'excerpt' => 'Descubra cómo la tecnología está revolucionando los procesos contables.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post2.jpg',
            'category' => 'contabilidad',
            'category_name' => 'Contabilidad',
            'author' => 'María Gómez',
            'author_image' => 'img/team/author2.jpg',
            'date' => '2025-02-10',
            'slug' => 'transformacion-digital-contabilidad'
        ],
        [
            'id' => 3,
            'title' => 'Optimización de la Gestión Financiera',
            'excerpt' => 'Estrategias efectivas para mejorar el control financiero de su empresa.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post3.jpg',
            'category' => 'finanzas',
            'category_name' => 'Finanzas',
            'author' => 'Carlos Rodríguez',
            'author_image' => 'img/team/author3.jpg',
            'date' => '2025-02-05',
            'slug' => 'optimizacion-gestion-financiera'
        ],
        [
            'id' => 4,
            'title' => 'Importancia de la Auditoría Preventiva',
            'excerpt' => 'Beneficios de mantener un programa de auditoría constante en su empresa.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post4.jpg',
            'category' => 'auditoria',
            'category_name' => 'Auditoría',
            'author' => 'Laura Martínez',
            'author_image' => 'img/team/author4.jpg',
            'date' => '2025-02-01',
            'slug' => 'importancia-auditoria-preventiva'
        ],
        [
            'id' => 5,
            'title' => 'Ventajas Fiscales de la Ley Fronteriza',
            'excerpt' => 'Guía completa sobre cómo aprovechar los beneficios de la Ley de Desarrollo Fronterizo.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post5.jpg',
            'category' => 'legal',
            'category_name' => 'Legal',
            'author' => 'Juan Pérez',
            'author_image' => 'img/team/author1.jpg',
            'date' => '2025-01-25',
            'slug' => 'ventajas-ley-fronteriza'
        ],
        [
            'id' => 6,
            'title' => 'Tecnologías Emergentes en Finanzas Corporativas',
            'excerpt' => 'Cómo la Inteligencia Artificial y el Blockchain están transformando las finanzas.',
            'content' => 'Contenido completo del artículo...',
            'image' => 'img/blog/post6.jpg',
            'category' => 'tecnologia',
            'category_name' => 'Tecnología',
            'author' => 'María Gómez',
            'author_image' => 'img/team/author2.jpg',
            'date' => '2025-01-20',
            'slug' => 'tecnologias-emergentes-finanzas'
        ],
    ];
    
    // Filtrar por categoría si se especifica
    if ($category) {
        $filtered_posts = array_filter($posts, function($post) use ($category) {
            return $post['category'] === $category;
        });
        $posts = array_values($filtered_posts);
    }
    
    // Aplicar paginación
    $paginated_posts = array_slice($posts, $offset, $limit);
    
    return [
        'posts' => $paginated_posts,
        'total' => count($posts)
    ];
}

function get_categories() {
    return [
        ['id' => 1, 'name' => 'Fiscal', 'slug' => 'fiscal', 'count' => 12],
        ['id' => 2, 'name' => 'Contabilidad', 'slug' => 'contabilidad', 'count' => 8],
        ['id' => 3, 'name' => 'Finanzas', 'slug' => 'finanzas', 'count' => 15],
        ['id' => 4, 'name' => 'Auditoría', 'slug' => 'auditoria', 'count' => 7],
        ['id' => 5, 'name' => 'Legal', 'slug' => 'legal', 'count' => 5],
        ['id' => 6, 'name' => 'Tecnología', 'slug' => 'tecnologia', 'count' => 9],
    ];
}

// Obtener parámetros de URL para filtrado y paginación
$current_category = isset($_GET['categoria']) ? $_GET['categoria'] : null;
$page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$per_page = 4;
$offset = ($page - 1) * $per_page;

// Obtener posts para esta página
$result = get_posts($current_category, $per_page, $offset);
$posts = $result['posts'];
$total_posts = $result['total'];
$total_pages = ceil($total_posts / $per_page);

// Obtener categorías para el filtro
$categories = get_categories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/blog.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/mobile-optimizations.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="blog-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Blog & Actualizaciones</h1>
                    <p>Manténgase informado sobre las últimas novedades fiscales y financieras</p>
                </div>
            </div>
        </section>

        <!-- Blog Content -->
        <section class="blog-main">
            <div class="container">
                <!-- Filtro de Categorías -->
                <div class="blog-categories">
                    <a href="blog.php" class="category-btn <?php echo (!$current_category) ? 'active' : ''; ?>">
                        Todos
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="blog.php?categoria=<?php echo $category['slug']; ?>" 
                       class="category-btn <?php echo ($current_category === $category['slug']) ? 'active' : ''; ?>">
                        <?php echo $category['name']; ?> (<?php echo $category['count']; ?>)
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Featured Post (solo en la primera página sin filtro) -->
                <?php if ($page === 1 && !$current_category && isset($posts[0])): ?>
                <article class="featured-post">
                    <div class="featured-image">
                        <img src="<?php echo $posts[0]['image']; ?>" alt="<?php echo $posts[0]['title']; ?>">
                    </div>
                    <div class="featured-content">
                        <span class="blog-category"><?php echo $posts[0]['category_name']; ?></span>
                        <h2 class="featured-title"><?php echo $posts[0]['title']; ?></h2>
                        <p class="blog-excerpt">
                            <?php echo $posts[0]['excerpt']; ?>
                        </p>
                        <div class="blog-meta">
                            <div class="blog-author">
                                <div class="author-avatar">
                                    <img src="<?php echo $posts[0]['author_image']; ?>" alt="<?php echo $posts[0]['author']; ?>">
                                </div>
                                <span class="author-name"><?php echo $posts[0]['author']; ?></span>
                            </div>
                            <span class="blog-date">
                                <i class="far fa-calendar"></i>
                                <?php echo date('d M Y', strtotime($posts[0]['date'])); ?>
                            </span>
                        </div>
                        <a href="blog/<?php echo $posts[0]['slug']; ?>" class="blog-link">
                            Leer más <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
                
                <!-- Grid de Posts (excluyendo el destacado) -->
                <div class="blog-grid-secondary">
                    <?php for ($i = 1; $i < count($posts); $i++): ?>
                    <article class="blog-card">
                        <div class="blog-image">
                            <img src="<?php echo $posts[$i]['image']; ?>" alt="<?php echo $posts[$i]['title']; ?>">
                            <span class="blog-category"><?php echo $posts[$i]['category_name']; ?></span>
                        </div>
                        <div class="blog-content">
                            <h3 class="blog-title">
                                <a href="blog/<?php echo $posts[$i]['slug']; ?>"><?php echo $posts[$i]['title']; ?></a>
                            </h3>
                            <p class="blog-excerpt">
                                <?php echo $posts[$i]['excerpt']; ?>
                            </p>
                            <div class="blog-meta">
                                <div class="blog-author">
                                    <div class="author-avatar">
                                        <img src="<?php echo $posts[$i]['author_image']; ?>" alt="<?php echo $posts[$i]['author']; ?>">
                                    </div>
                                    <span class="author-name"><?php echo $posts[$i]['author']; ?></span>
                                </div>
                                <span class="blog-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d M Y', strtotime($posts[$i]['date'])); ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endfor; ?>
                </div>
                <?php else: ?>
                <!-- Grid de Posts (todos los posts en caso de filtro o paginación) -->
                <div class="blog-grid-full">
                    <?php foreach ($posts as $post): ?>
                    <article class="blog-card">
                        <div class="blog-image">
                            <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                            <span class="blog-category"><?php echo $post['category_name']; ?></span>
                        </div>
                        <div class="blog-content">
                            <h3 class="blog-title">
                                <a href="blog/<?php echo $post['slug']; ?>"><?php echo $post['title']; ?></a>
                            </h3>
                            <p class="blog-excerpt">
                                <?php echo $post['excerpt']; ?>
                            </p>
                            <div class="blog-meta">
                                <div class="blog-author">
                                    <div class="author-avatar">
                                        <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author']; ?>">
                                    </div>
                                    <span class="author-name"><?php echo $post['author']; ?></span>
                                </div>
                                <span class="blog-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d M Y', strtotime($post['date'])); ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                <div class="blog-pagination">
                    <?php if ($page > 1): ?>
                    <a href="blog.php?<?php echo $current_category ? 'categoria=' . $current_category . '&' : ''; ?>pagina=<?php echo $page - 1; ?>" class="pagination-btn prev">
                        <i class="fas fa-chevron-left"></i>
                        Anterior
                    </a>
                    <?php else: ?>
                    <span class="pagination-btn prev disabled">
                        <i class="fas fa-chevron-left"></i>
                        Anterior
                    </span>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                        <a href="blog.php?<?php echo $current_category ? 'categoria=' . $current_category . '&' : ''; ?>pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="blog.php?<?php echo $current_category ? 'categoria=' . $current_category . '&' : ''; ?>pagina=<?php echo $page + 1; ?>" class="pagination-btn next">
                        Siguiente
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="pagination-btn next disabled">
                        Siguiente
                        <i class="fas fa-chevron-right"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Sidebar -->
                <div class="blog-sidebar">
                    <!-- Búsqueda -->
                    <div class="sidebar-widget search-widget">
                        <h3>Buscar</h3>
                        <form class="search-form">
                            <input type="text" placeholder="Buscar artículos...">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    
                    <!-- Categorías -->
                    <div class="sidebar-widget categories-widget">
                        <h3>Categorías</h3>
                        <ul>
                            <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="blog.php?categoria=<?php echo $category['slug']; ?>">
                                    <?php echo $category['name']; ?>
                                    <span class="count"><?php echo $category['count']; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Posts Recientes -->
                    <div class="sidebar-widget recent-posts-widget">
                        <h3>Artículos Recientes</h3>
                        <div class="recent-posts">
                            <?php foreach (array_slice($posts, 0, 3) as $post): ?>
                            <div class="recent-post">
                                <div class="post-image">
                                    <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                                </div>
                                <div class="post-info">
                                    <h4><a href="blog/<?php echo $post['slug']; ?>"><?php echo $post['title']; ?></a></h4>
                                    <span class="post-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('d M Y', strtotime($post['date'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Tags -->
                    <div class="sidebar-widget tags-widget">
                        <h3>Etiquetas Populares</h3>
                        <div class="tags-cloud">
                            <a href="#" class="tag">Impuestos</a>
                            <a href="#" class="tag">Contabilidad</a>
                            <a href="#" class="tag">Finanzas</a>
                            <a href="#" class="tag">DGII</a>
                            <a href="#" class="tag">Legal</a>
                            <a href="#" class="tag">Tecnología</a>
                            <a href="#" class="tag">Auditoría</a>
                            <a href="#" class="tag">Ley Fronteriza</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter -->
        <section class="blog-newsletter">
            <div class="container">
                <div class="newsletter-content">
                    <h3>Suscríbase a Nuestro Newsletter</h3>
                    <p>Reciba actualizaciones importantes y consejos útiles para su empresa</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input 
                            type="email" 
                            class="newsletter-input" 
                            placeholder="Su correo electrónico"
                            required
                        >
                        <button type="submit" class="btn btn-primary">
                            Suscribirse
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    
    <script>
        // Script para filtros de categoría
        document.addEventListener('DOMContentLoaded', function() {
            const categoryBtns = document.querySelectorAll('.category-btn');
            
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>