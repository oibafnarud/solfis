<?php
/**
 * Página de búsqueda de artículos del blog
 * Esta página muestra los resultados de búsqueda para el blog
 */

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Verificar que se proporcionó un término de búsqueda
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (empty($query)) {
    header('Location: blog.php');
    exit;
}

// Instanciar clases necesarias
$blogPost = new BlogPost();
$category = new Category();

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = POSTS_PER_PAGE;

// Realizar búsqueda en la base de datos
$db = Database::getInstance();
$searchTerm = $db->escape("%{$query}%");

$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name, u.image as author_image 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.status = 'published' 
        AND (p.title LIKE '$searchTerm' OR p.content LIKE '$searchTerm' OR p.excerpt LIKE '$searchTerm')
        ORDER BY p.published_at DESC 
        LIMIT " . (($page - 1) * $per_page) . ", $per_page";

$result = $db->query($sql);
$posts = [];

while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

// Contar total para paginación
$countSql = "SELECT COUNT(*) as total 
             FROM posts p 
             WHERE p.status = 'published' 
             AND (p.title LIKE '$searchTerm' OR p.content LIKE '$searchTerm' OR p.excerpt LIKE '$searchTerm')";

$countResult = $db->query($countSql);
$totalPosts = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalPosts / $per_page);

// Obtener todas las categorías para el menú lateral
$categories = $category->getCategories();

// Título de la página
$pageTitle = 'Resultados de búsqueda para: ' . $query;
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
    
    <!-- Sección de banner de búsqueda -->
    <section class="search-banner bg-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="h3 mb-3">Resultados de búsqueda</h1>
                    <form action="blog-buscar.php" method="get">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-lg" placeholder="Buscar artículos..." name="q" value="<?php echo htmlspecialchars($query); ?>" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                    <p class="text-muted">Se encontraron <?php echo $totalPosts; ?> resultados para "<?php echo htmlspecialchars($query); ?>"</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Sección principal de resultados -->
    <section class="search-results py-5">
        <div class="container">
            <div class="row">
                <!-- Listado de resultados -->
                <div class="col-lg-8">
                    <?php if (empty($posts)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No se encontraron artículos que coincidan con tu búsqueda. Intenta con otros términos.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="row g-0">
                                <?php if (!empty($post['image'])): ?>
                                <div class="col-md-4">
                                    <img src="<?php echo $post['image']; ?>" class="img-fluid rounded-start h-100 object-fit-cover" alt="<?php echo $post['title']; ?>">
                                </div>
                                <div class="col-md-8">
                                <?php else: ?>
                                <div class="col-md-12">
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
                                        <p class="card-text"><?php echo Helpers::truncate($post['excerpt'], 200); ?></p>
                                        <div class="d-flex align-items-center mt-3">
                                            <?php if (!empty($post['author_image'])): ?>
                                            <img src="<?php echo $post['author_image']; ?>" alt="<?php echo $post['author_name']; ?>" class="rounded-circle me-2" width="30" height="30">
                                            <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Por <?php echo $post['author_name']; ?></small>
                                        </div>
                                        <a href="blog/<?php echo $post['slug']; ?>" class="btn btn-outline-primary mt-3">Leer más</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginación de resultados" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>">
                                        &laquo; Anterior
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>">
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
                    <!-- Categorías -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Categorías</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="blog.php" class="text-decoration-none">Todas</a>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="blog.php?categoria=<?php echo $cat['slug']; ?>" class="text-decoration-none">
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