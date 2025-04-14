<?php
$site_title = "Listado de Vacantes - SolFis";
$site_description = "Explora todas nuestras vacantes disponibles en SolFis y encuentra la que mejor se adapte a tu perfil.";
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir el sistema de vacantes
require_once 'includes/jobs-system.php';

// Instanciar gestores
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Obtener parámetros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$q = isset($_GET['q']) ? $_GET['q'] : '';
$ubicacion = isset($_GET['ubicacion']) ? $_GET['ubicacion'] : '';
$categoriaId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$modalidad = isset($_GET['modalidad']) ? $_GET['modalidad'] : '';
$orderBy = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_desc';

// Preparar filtros
$filters = [
    'estado' => 'publicada',
    'busqueda' => $q,
    'ubicacion' => $ubicacion,
    'categoria' => $categoriaId,
    'modalidad' => $modalidad,
    'orden' => $orderBy
];

// Obtener vacantes
$vacantesData = $vacancyManager->getVacancies($page, 9, $filters);
$vacantes = $vacantesData['vacancies'];
$totalVacantes = $vacantesData['total'];
$totalPages = $vacantesData['pages'];

// Obtener categorías para filtro
$categorias = $categoryManager->getCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-base.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-listado.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section (smaller) -->
        <section class="jobs-list-hero">
            <div class="container">
                <div class="hero-content" data-aos="fade-up">
                    <h1>Explora Nuestras Vacantes</h1>
                    <p>Descubre todas las oportunidades laborales disponibles en SolFis</p>
                </div>
            </div>
        </section>

        <!-- Jobs Listing Section -->
        <section class="jobs-listing">
            <div class="container">
                <div class="job-listing-layout" data-aos="fade-up">
                    <!-- Sidebar con filtros -->
                    <div class="filter-sidebar">
                        <div class="filter-header">
                            <h3>Filtros</h3>
                            <button type="button" class="filter-reset" id="resetFilters">Limpiar filtros</button>
                        </div>
                        
                        <form action="listado.php" method="GET" id="filterForm">
                            <!-- Buscador -->
                            <div class="filter-group">
                                <label for="q" class="filter-label">Palabra clave</label>
                                <div class="filter-search">
                                    <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar..." class="form-control">
                                    <button type="submit" class="filter-search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Categoría -->
                            <div class="filter-group">
                                <h3>Categoría</h3>
                                <div class="filter-options">
                                    <?php foreach ($categorias as $categoria): ?>
                                    <div class="filter-checkbox">
                                        <input type="radio" id="categoria-<?php echo $categoria['id']; ?>" name="categoria" value="<?php echo $categoria['id']; ?>" <?php echo $categoriaId == $categoria['id'] ? 'checked' : ''; ?>>
                                        <label for="categoria-<?php echo $categoria['id']; ?>">
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            <span class="filter-count">(<?php echo $categoria['vacantes_count']; ?>)</span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Ubicación -->
                            <div class="filter-group">
                                <h3>Ubicación</h3>
                                <div class="filter-options">
                                    <div class="filter-checkbox">
                                        <input type="radio" id="ubicacion-sd" name="ubicacion" value="Santo Domingo" <?php echo $ubicacion === 'Santo Domingo' ? 'checked' : ''; ?>>
                                        <label for="ubicacion-sd">Santo Domingo</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="radio" id="ubicacion-stgo" name="ubicacion" value="Santiago" <?php echo $ubicacion === 'Santiago' ? 'checked' : ''; ?>>
                                        <label for="ubicacion-stgo">Santiago</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="radio" id="ubicacion-remoto" name="ubicacion" value="Remoto" <?php echo $ubicacion === 'Remoto' ? 'checked' : ''; ?>>
                                        <label for="ubicacion-remoto">Remoto</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modalidad -->
                            <div class="filter-group">
                                <h3>Modalidad</h3>
                                <div class="filter-options">
                                    <div class="filter-checkbox">
                                        <input type="radio" id="modalidad-presencial" name="modalidad" value="presencial" <?php echo $modalidad === 'presencial' ? 'checked' : ''; ?>>
                                        <label for="modalidad-presencial">Presencial</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="radio" id="modalidad-remoto" name="modalidad" value="remoto" <?php echo $modalidad === 'remoto' ? 'checked' : ''; ?>>
                                        <label for="modalidad-remoto">Remoto</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="radio" id="modalidad-hibrido" name="modalidad" value="hibrido" <?php echo $modalidad === 'hibrido' ? 'checked' : ''; ?>>
                                        <label for="modalidad-hibrido">Híbrido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ordenamiento -->
                            <div class="filter-group">
                                <h3>Ordenar por</h3>
                                <select name="orden" class="form-control">
                                    <option value="fecha_desc" <?php echo $orderBy === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes primero</option>
                                    <option value="fecha_asc" <?php echo $orderBy === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos primero</option>
                                    <option value="titulo_asc" <?php echo $orderBy === 'titulo_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
                                    <option value="titulo_desc" <?php echo $orderBy === 'titulo_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
                                </select>
                            </div>
                            
                            <input type="hidden" name="page" value="1">
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter-apply">Aplicar Filtros</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Listado principal -->
                    <div class="jobs-main">
                        <div class="listing-header">
                            <div class="results-count">
                                <strong><?php echo $totalVacantes; ?></strong> vacantes encontradas
                            </div>
                            <div class="mobile-filter-toggle" id="mobileFilterToggle">
                                <i class="fas fa-filter"></i> Filtrar
                            </div>
                        </div>
                        
                        <!-- Lista de vacantes -->
                        <div class="jobs-list">
                            <?php if (empty($vacantes)): ?>
                                <div class="no-jobs-found">
                                    <i class="fas fa-search"></i>
                                    <h3>No se encontraron vacantes</h3>
                                    <p>Intenta ajustar los filtros o realiza una nueva búsqueda.</p>
                                    <a href="listado.php" class="btn-primary">Ver todas las vacantes</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($vacantes as $vacante): ?>
                                <div class="job-list-card">
                                    <div class="job-list-logo">
                                        <img src="img/logo-icon.png" alt="SolFis" class="company-logo">
                                    </div>
                                    <div class="job-list-content">
                                        <div class="job-list-title">
                                            <h3><a href="detalle.php?id=<?php echo $vacante['id']; ?>"><?php echo htmlspecialchars($vacante['titulo']); ?></a></h3>
                                            <?php if ($vacante['destacada']): ?>
                                            <span class="badge-featured">Destacada</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job-list-meta">
                                            <span class="job-meta-item"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                            <span class="job-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                            <span class="job-meta-item"><i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                            <span class="job-meta-item"><i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                                        </div>
                                        <div class="job-list-description">
                                            <?php echo VacancyUtils::truncate(htmlspecialchars($vacante['descripcion']), 150); ?>
                                        </div>
                                        <div class="job-list-footer">
                                            <span class="job-date"><i class="far fa-calendar-alt"></i> Publicada: <?php echo date('d M Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                                            <a href="detalle.php?id=<?php echo $vacante['id']; ?>" class="btn-view-job">Ver Vacante</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="pagination-link prev" href="<?php echo '?page=' . ($page - 1) . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item">
                                    <a class="pagination-link number <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo '?page=' . $i . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="pagination-link next" href="<?php echo '?page=' . ($page + 1) . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="jobs-cta">
            <div class="container">
                <div class="cta-content" data-aos="fade-up">
                    <h2>¿No encontraste la vacante ideal?</h2>
                    <p>Envíanos tu curriculum y te contactaremos cuando surja una oportunidad que se ajuste a tu perfil.</p>
                    <div class="cta-buttons">
                        <a href="contacto.php" class="btn-primary">Enviar CV</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    <script src="assets/js/vacantes.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Toggle filtros en móvil
        document.getElementById('mobileFilterToggle')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.filter-sidebar');
            sidebar.classList.toggle('active');
        });
        
        // Resetear filtros
        document.getElementById('resetFilters')?.addEventListener('click', function() {
            window.location.href = 'listado.php';
        });
        
        // Autosubmit al cambiar filtros de radio
        const radioFilters = document.querySelectorAll('.filter-checkbox input[type="radio"]');
        radioFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        
        // Autosubmit al cambiar ordenamiento
        document.querySelector('select[name="orden"]')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    </script>
</body>
</html>