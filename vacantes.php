<?php
$site_title = "Portal de Empleo - SolFis";
$site_description = "Encuentra las mejores oportunidades laborales en SolFis. Tu carrera profesional comienza aquí.";
$base_path = 'sections/';
$assets_path = 'assets/';

// Incluir el sistema de vacantes
require_once 'includes/jobs-system.php';

// Instanciar gestores
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Obtener vacantes destacadas
$filters = [
    'estado' => 'publicada',
    'destacada' => true
];
$vacantesDestacadas = $vacancyManager->getVacancies(1, 6, $filters)['vacancies'];

// Obtener categorías con conteo de vacantes
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
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-home.css">
    
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
        <!-- Hero Section -->
        <section class="jobs-hero">
            <div class="container">
                <div class="hero-content" data-aos="fade-up">
                    <h1>Forma parte de una empresa</h1>
                    <p>Descubre las oportunidades profesionales que tenemos para ti y da el siguiente paso en tu carrera.</p>
                    
                    <div class="search-container">
                        <form action="listado.php" method="GET" class="search-form">
                            <div class="search-input-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-search"></i>
                                    <input type="text" name="q" placeholder="¿Qué posición buscas?" class="search-input">
                                </div>
                                <div class="input-wrapper">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <select name="ubicacion" class="search-select">
                                        <option value="">Todas las ubicaciones</option>
                                        <option value="Santo Domingo">Santo Domingo</option>
                                        <option value="Santiago">Santiago</option>
                                        <option value="Remoto">Remoto</option>
                                    </select>
                                </div>
                                <div class="input-wrapper">
                                    <i class="fas fa-briefcase"></i>
                                    <select name="categoria" class="search-select">
                                        <option value="">Todas las áreas</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="search-button">Buscar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="hero-pattern"></div>
        </section>

        <!-- Featured Jobs Section -->
        <section class="featured-jobs">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Vacantes Destacadas</h2>
                    <p>Explora nuestras posiciones abiertas y encuentra la que mejor se adapte a tus habilidades y experiencia</p>
                </div>
                
                <div class="jobs-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php if (empty($vacantesDestacadas)): ?>
                        <div class="no-jobs">
                            <p>No hay vacantes destacadas en este momento. Por favor, consulta nuestro listado completo de vacantes disponibles.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($vacantesDestacadas as $vacante): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="job-title">
                                    <h3><a href="detalle.php?id=<?php echo $vacante['id']; ?>"><?php echo htmlspecialchars($vacante['titulo']); ?></a></h3>
                                    <span class="badge-featured">Destacada</span>
                                </div>
                                <div class="job-company">
                                    <img src="img/logo-icon.png" alt="SolFis" class="company-logo">
                                </div>
                            </div>
                            <div class="job-details">
                                <div class="job-meta">
                                    <span class="job-category"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                    <span class="job-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                    <span class="job-type"><i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                </div>
                            </div>
                            <div class="job-footer">
                                <span class="job-date"><i class="far fa-calendar-alt"></i> Publicada: <?php echo date('d M Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                                <a href="detalle.php?id=<?php echo $vacante['id']; ?>" class="btn-apply">Ver Detalles</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="view-all-container" data-aos="fade-up">
                    <a href="listado.php" class="btn-view-all">Ver Todas las Vacantes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="job-categories">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Explora por Categoría</h2>
                    <p>Encuentra vacantes según tu área de especialización</p>
                </div>
                
                <div class="categories-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php foreach ($categorias as $categoria): ?>
                    <a href="listado.php?categoria=<?php echo $categoria['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo $categoria['icono'] ?: 'fas fa-briefcase'; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                        <span class="job-count"><?php echo $categoria['vacantes_count']; ?> vacantes</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Why Join Us Section -->
        <section class="why-join-us">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>¿Por qué unirse a SolFis?</h2>
                    <p>Descubre los beneficios de ser parte de nuestro equipo</p>
                </div>
                
                <div class="benefits-grid" data-aos="fade-up" data-aos-delay="100">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Desarrollo Profesional</h3>
                        <p>Programas de capacitación continua y oportunidades de crecimiento profesional.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Ambiente Colaborativo</h3>
                        <p>Cultura de trabajo en equipo y ambiente laboral positivo y respetuoso.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3>Balance Vida-Trabajo</h3>
                        <p>Políticas que promueven la conciliación entre vida personal y profesional.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3>Reconocimiento</h3>
                        <p>Valoramos y reconocemos el esfuerzo y logros de nuestros colaboradores.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="jobs-cta">
            <div class="container">
                <div class="cta-content" data-aos="fade-up">
                    <h2>¿Listo para dar el siguiente paso en tu carrera?</h2>
                    <p>Explora nuestras vacantes disponibles y encuentra la oportunidad perfecta para ti.</p>
                    <div class="cta-buttons">
                        <a href="listado.php" class="btn-primary">Ver Todas las Vacantes</a>
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
    </script>
</body>
</html>