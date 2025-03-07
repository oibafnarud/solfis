<?php
/**
 * Página principal del blog (blog.php)
 * Esta página muestra la lista de artículos del blog con paginación y filtros por categoría
 */

// Incluir archivos necesarios
require_once './admin/config.php'; 
require_once 'includes/blog-system.php';

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
    <?php include './admin/includes/header.php'; ?>
    
    <!-- Sección de banner del blog -->
    <section class="hero-banner bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-4 fw-bold">Blog SolFis</h1>
                    <p class="lead text-secondary">Información actualizada sobre contabilidad, finanzas, impuestos y gestión empresarial para profesionales y empresarios.</p>
                    <?php if ($currentCategory): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="blog.php">Todos los artículos</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $currentCategory['name']; ?></li>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5">
                    <img src="img/blog/blog-banner.svg" alt="Blog SolFis" class="img-fluid">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Sección principal del blog -->
    <section class="blog-section py-5">
        <div class="container">
            <div class="row">
                <!-- Listado de artículos -->
                <div class="col-lg-8">
                    <?php if (empty($posts)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No hay artículos disponibles en este momento.</p>
                    </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($posts as $post): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <?php if (!empty($post['image'])): ?>
                                    <img src="<?php echo $post['image']; ?>" class="card-img-top" alt="<?php echo $post['title']; ?>">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?php echo $post['category_name']; ?></span>
                                            <small class="text-muted ms-2"><?php echo date('d M, Y', strtotime($post['published_at'])); ?></small>
                                        </div>
                                        <h5 class="card-title">
                                            <a href="blog/<?php echo $post['slug']; ?>" class="text-decoration-none text-dark">
                                                <?php echo $post['title']; ?>
                                            </a>
                                        </h5>
                                        <p class="card-text"><?php echo Helpers::truncate($post['excerpt'], 120); ?></p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($post['author_image'])): ?>
                                            <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="rounded-circle me-2" width="30" height="30">
                                            <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Por <?php echo $post['author_name']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginación de artículos" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>">
                                        &laquo; Anterior
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $categorySlug ? '&categoria=' . $categorySlug : ''; ?>">
                                        Siguiente &raquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
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
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="blog.php" class="text-decoration-none <?php echo !$categorySlug ? 'fw-bold' : ''; ?>">Todas</a>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="?categoria=<?php echo $cat['slug']; ?>" class="text-decoration-none <?php echo $categorySlug === $cat['slug'] ? 'fw-bold' : ''; ?>">
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