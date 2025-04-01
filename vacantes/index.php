<?php
$site_title = "Portal de Empleos - Solfis";
$site_description = "Encuentra tu próxima oportunidad laboral en SolFis. Explora nuestras vacantes y forma parte de nuestro equipo.";

// Ajustar las rutas para que sean relativas a la raíz del sitio
$base_path = '../sections/';
$assets_path = '../assets/';
$page_canonical = "https://solfis.com.do/vacantes/";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    <link rel="canonical" href="<?php echo $page_canonical; ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $site_title; ?>">
    <meta property="og:description" content="<?php echo $site_description; ?>">
    <meta property="og:url" content="<?php echo $page_canonical; ?>">
    <meta property="og:type" content="website">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/text-contrast-fixes.css">
    <link rel="stylesheet" href="assets/css/vacantes.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <!-- Tiny Slider para testimonios -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/tiny-slider.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="vacantes-hero">
            <div class="container">
                <div class="hero-content" data-aos="fade-up">
                    <h1>Encuentra tu lugar en SolFis</h1>
                    <p>Explora las oportunidades para unirte a nuestro equipo y desarrollar tu carrera profesional</p>
                    <?php if (isset($_SESSION['candidato_id'])): ?>
                    <a href="perfil/" class="btn-primary">Mi Panel</a>
                    <?php else: ?>
                    <a href="aplicar.php" class="btn-primary">Ver Vacantes Disponibles</a>
                    <a href="perfil/registro.php" class="btn-secondary">Crear Perfil</a>
                    <?php endif; ?>
                </div>
                <div class="hero-image" data-aos="fade-left" data-aos-delay="200">
                    <img src="assets/img/vacantes-hero.svg" alt="Oportunidades laborales en SolFis">
                </div>
            </div>
            <div class="hero-wave">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                    <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
        </section>

        <!-- Buscador de Vacantes -->
        <section class="search-section">
            <div class="container">
                <div class="search-container" data-aos="fade-up">
                    <h2>Encuentra tu próxima oportunidad</h2>
                    <form action="buscar.php" method="get" class="search-form">
                        <div class="search-fields">
                            <div class="search-field">
                                <span class="field-icon"><i class="fas fa-search"></i></span>
                                <input type="text" name="keyword" placeholder="Título, palabra clave o departamento">
                            </div>
                            
                            <div class="search-field">
                                <span class="field-icon"><i class="fas fa-map-marker-alt"></i></span>
                                <select name="ubicacion">
                                    <option value="">Todas las ubicaciones</option>
                                    <option value="Santo Domingo">Santo Domingo</option>
                                    <option value="Santiago">Santiago</option>
                                    <option value="Remoto">Remoto</option>
                                </select>
                            </div>
                            
                            <div class="search-field">
                                <span class="field-icon"><i class="fas fa-briefcase"></i></span>
                                <select name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php
                                    // Obtener categorías desde la base de datos
                                    require_once '../includes/database.php';
                                    $db = Database::getInstance();
                                    $query = "SELECT id, nombre FROM vacantes_categorias ORDER BY nombre";
                                    $result = $db->query($query);
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="search-btn">Buscar</button>
                        </div>
                        
                        <div class="advanced-search-toggle">
                            <a href="#" id="toggleAdvanced">Búsqueda avanzada <i class="fas fa-chevron-down"></i></a>
                        </div>
                        
                        <div class="advanced-search-fields" id="advancedFields">
                            <div class="row">
                                <div class="col">
                                    <label for="modalidad">Modalidad</label>
                                    <select name="modalidad" id="modalidad">
                                        <option value="">Todas</option>
                                        <option value="Presencial">Presencial</option>
                                        <option value="Remoto">Remoto</option>
                                        <option value="Híbrido">Híbrido</option>
                                    </select>
                                </div>
                                
                                <div class="col">
                                    <label for="jornada">Jornada</label>
                                    <select name="jornada" id="jornada">
                                        <option value="">Todas</option>
                                        <option value="Tiempo completo">Tiempo completo</option>
                                        <option value="Medio tiempo">Medio tiempo</option>
                                        <option value="Por horas">Por horas</option>
                                    </select>
                                </div>
                                
                                <div class="col">
                                    <label for="nivel">Nivel</label>
                                    <select name="nivel" id="nivel">
                                        <option value="">Todos</option>
                                        <option value="Pasante">Pasante</option>
                                        <option value="Junior">Junior</option>
                                        <option value="Semi-senior">Semi-senior</option>
                                        <option value="Senior">Senior</option>
                                        <option value="Gerencial">Gerencial</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Vacantes Destacadas -->
        <section class="featured-jobs">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Vacantes Destacadas</h2>
                    <p>Posiciones clave que actualmente estamos buscando cubrir</p>
                </div>
                
                <div class="jobs-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php
                    // Obtener vacantes destacadas
                    $query = "SELECT v.id, v.titulo, v.slug, v.descripcion, v.ubicacion, v.modalidad, v.jornada, 
                             v.fecha_publicacion, v.fecha_cierre, v.destacada,
                             vc.nombre as categoria, vc.color
                             FROM vacantes v
                             LEFT JOIN vacantes_categorias vc ON v.categoria_id = vc.id
                             WHERE v.estado = 'Publicada' AND v.destacada = 1
                             AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= NOW())
                             ORDER BY v.fecha_publicacion DESC
                             LIMIT 6";
                    
                    if (isset($db) && $db) {
                        $result = $db->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($vacante = $result->fetch_assoc()) {
                                // Calcular tiempo desde publicación
                                $publicacion = new DateTime($vacante['fecha_publicacion']);
                                $ahora = new DateTime();
                                $intervalo = $publicacion->diff($ahora);
                                
                                if ($intervalo->d < 1) {
                                    $tiempo = "Hoy";
                                } elseif ($intervalo->d == 1) {
                                    $tiempo = "Ayer";
                                } elseif ($intervalo->d <= 7) {
                                    $tiempo = "Hace " . $intervalo->d . " días";
                                } else {
                                    $tiempo = $publicacion->format('d/m/Y');
                                }
                                
                                // Color para la categoría, usar default si no tiene
                                $catColor = !empty($vacante['color']) ? $vacante['color'] : '#0066CC';
                    ?>
                    <div class="job-card">
                        <?php if ($vacante['destacada']): ?>
                        <div class="job-tag featured">Destacada</div>
                        <?php endif; ?>
                        
                        <div class="job-header">
                            <div class="job-company-logo">
                                <img src="../img/logo-icon.png" alt="Solfis">
                            </div>
                            <div class="job-title-container">
                                <h3 class="job-title"><a href="detalle.php?slug=<?php echo $vacante['slug']; ?>"><?php echo $vacante['titulo']; ?></a></h3>
                                <span class="job-company">SolFis</span>
                            </div>
                        </div>
                        
                        <div class="job-body">
                            <div class="job-category" style="background-color: <?php echo $catColor; ?>">
                                <?php echo $vacante['categoria']; ?>
                            </div>
                            
                            <div class="job-details">
                                <div class="job-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo $vacante['ubicacion']; ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo $vacante['modalidad']; ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $vacante['jornada']; ?></span>
                                </div>
                            </div>
                            
                            <div class="job-description">
                                <?php 
                                // Limitar descripción a 120 caracteres
                                echo strlen($vacante['descripcion']) > 120 ? 
                                     substr($vacante['descripcion'], 0, 120) . '...' : 
                                     $vacante['descripcion']; 
                                ?>
                            </div>
                        </div>
                        
                        <div class="job-footer">
                            <div class="job-date">
                                <i class="far fa-calendar-alt"></i> 
                                <span><?php echo $tiempo; ?></span>
                            </div>
                            <a href="detalle.php?slug=<?php echo $vacante['slug']; ?>" class="btn-apply">Ver detalles</a>
                        </div>
                    </div>
                    <?php
                            }
                        } else {
                            echo '<div class="no-jobs">No hay vacantes destacadas actualmente.</div>';
                        }
                    } else {
                        echo '<div class="no-jobs">Error al conectar con la base de datos.</div>';
                    }
                    ?>
                </div>
                
                <div class="section-footer" data-aos="fade-up">
                    <a href="buscar.php" class="btn-primary">Ver todas las vacantes</a>
                </div>
            </div>
        </section>

        <!-- Categorías -->
        <section class="job-categories">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Explora por Categorías</h2>
                    <p>Encuentra vacantes agrupadas por áreas de especialización</p>
                </div>
                
                <div class="categories-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php
                    // Obtener categorías con conteo de vacantes activas
                    if (isset($db) && $db) {
                        $query = "SELECT c.id, c.nombre, c.slug, c.icono, c.color, 
                                 COUNT(v.id) as vacantes_count
                                 FROM vacantes_categorias c
                                 LEFT JOIN vacantes v ON c.id = v.categoria_id AND v.estado = 'Publicada'
                                     AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= NOW())
                                 GROUP BY c.id
                                 HAVING vacantes_count > 0
                                 ORDER BY vacantes_count DESC, c.nombre
                                 LIMIT 8";
                        
                        $result = $db->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($categoria = $result->fetch_assoc()) {
                                // Preparar icono o usar default
                                $icono = !empty($categoria['icono']) ? $categoria['icono'] : 'fas fa-briefcase';
                                $color = !empty($categoria['color']) ? $categoria['color'] : '#0066CC';
                    ?>
                    <a href="categorias.php?slug=<?php echo $categoria['slug']; ?>" class="category-card">
                        <div class="category-icon" style="background-color: <?php echo $color; ?>">
                            <i class="<?php echo $icono; ?>"></i>
                        </div>
                        <h3><?php echo $categoria['nombre']; ?></h3>
                        <span class="category-count"><?php echo $categoria['vacantes_count']; ?> vacantes</span>
                    </a>
                    <?php
                            }
                        } else {
                            // Si no hay categorías con vacantes activas, mostrar categorías sin conteo
                            $query = "SELECT id, nombre, slug, icono, color FROM vacantes_categorias ORDER BY nombre LIMIT 8";
                            $result = $db->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($categoria = $result->fetch_assoc()) {
                                    $icono = !empty($categoria['icono']) ? $categoria['icono'] : 'fas fa-briefcase';
                                    $color = !empty($categoria['color']) ? $categoria['color'] : '#0066CC';
                    ?>
                    <a href="categorias.php?slug=<?php echo $categoria['slug']; ?>" class="category-card">
                        <div class="category-icon" style="background-color: <?php echo $color; ?>">
                            <i class="<?php echo $icono; ?>"></i>
                        </div>
                        <h3><?php echo $categoria['nombre']; ?></h3>
                        <span class="category-count">0 vacantes</span>
                    </a>
                    <?php
                                }
                            } else {
                                echo '<div class="no-categories">No hay categorías disponibles.</div>';
                            }
                        }
                    } else {
                        echo '<div class="no-categories">Error al conectar con la base de datos.</div>';
                    }
                    ?>
                </div>
                
                <div class="section-footer" data-aos="fade-up">
                    <a href="categorias.php" class="btn-secondary">Ver todas las categorías</a>
                </div>
            </div>
        </section>

        <!-- Por qué trabajar con nosotros -->
        <section class="why-work-with-us">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Por Qué Trabajar Con Nosotros</h2>
                    <p>En SolFis creemos que nuestro equipo es nuestro activo más valioso</p>
                </div>
                
                <div class="benefits-grid" data-aos="fade-up" data-aos-delay="100">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Crecimiento Profesional</h3>
                        <p>Fomentamos el desarrollo continuo con programas de capacitación y oportunidades de avance.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3>Balance Vida-Trabajo</h3>
                        <p>Valoramos el bienestar de nuestros colaboradores con políticas flexibles y respetuosas.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h3>Ambiente Colaborativo</h3>
                        <p>Promovemos un entorno de trabajo en equipo, respeto mutuo y colaboración constante.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3>Reconocimiento</h3>
                        <p>Celebramos los logros individuales y colectivos con programas de reconocimiento.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonios -->
        <section class="testimonials">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Lo Que Dice Nuestro Equipo</h2>
                    <p>Conoce las experiencias de quienes forman parte de la familia SolFis</p>
                </div>
                
                <div class="testimonials-slider" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-item">
                        <div class="testimonial-content">
                            <p>"Llevo 5 años en SolFis y cada día es una nueva oportunidad de aprendizaje. El ambiente de trabajo y la cultura organizacional me han permitido crecer tanto profesional como personalmente."</p>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/img/testimonial-1.jpg" alt="María Rodriguez">
                            <div class="author-info">
                                <h4>María Rodriguez</h4>
                                <span>Gerente de Contabilidad</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-item">
                        <div class="testimonial-content">
                            <p>"Empecé como asistente y ahora lidero un equipo. SolFis realmente valora el talento interno y brinda las herramientas necesarias para que cada colaborador alcance su máximo potencial."</p>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/img/testimonial-2.jpg" alt="Carlos Jiménez">
                            <div class="author-info">
                                <h4>Carlos Jiménez</h4>
                                <span>Coordinador de Auditoría</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-item">
                        <div class="testimonial-content">
                            <p>"Lo que más valoro de trabajar en SolFis es el balance vida-trabajo. La empresa realmente se preocupa por nuestro bienestar y eso se refleja en la calidad del servicio que ofrecemos a nuestros clientes."</p>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/img/testimonial-3.jpg" alt="Laura Mendez">
                            <div class="author-info">
                                <h4>Laura Mendez</h4>
                                <span>Analista de Impuestos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proceso de Selección -->
        <section class="hiring-process">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Nuestro Proceso de Selección</h2>
                    <p>Conoce los pasos para unirte a nuestro equipo</p>
                </div>
                
                <div class="process-steps" data-aos="fade-up" data-aos-delay="100">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Aplicación</h3>
                            <p>Envía tu solicitud a través de nuestro portal de empleos junto con tu CV actualizado.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Revisión</h3>
                            <p>Nuestro equipo de RRHH revisará tu perfil y experiencia para evaluar tu adecuación al puesto.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Entrevistas</h3>
                            <p>Si tu perfil es seleccionado, pasarás por entrevistas con RRHH y el departamento correspondiente.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Evaluación</h3>
                            <p>Realizarás pruebas técnicas y/o psicométricas según el puesto al que apliques.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h3>Oferta</h3>
                            <p>Si eres seleccionado, recibirás una oferta formal detallando condiciones y beneficios.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content" data-aos="fade-up">
                    <h2>¿No encuentras el puesto ideal?</h2>
                    <p>Envíanos tu CV para futuras oportunidades. Revisamos todos los perfiles y te contactaremos cuando surja una posición acorde a tu experiencia.</p>
                    <div class="cta-buttons">
                        <a href="aplicar.php?tipo=general" class="btn-primary">Enviar CV Espontáneo</a>
                        <a href="perfil/registro.php" class="btn-secondary">Crear Perfil</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="faq-section">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Preguntas Frecuentes</h2>
                    <p>Respuestas a las dudas más comunes sobre nuestro proceso de selección</p>
                </div>
                
                <div class="faq-container" data-aos="fade-up" data-aos-delay="100">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Cómo puedo saber el estado de mi postulación?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Puedes revisar el estado de tus postulaciones en tu perfil de candidato. También te notificaremos por correo electrónico cuando haya cambios significativos en tu proceso.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Cuánto tiempo dura el proceso de selección?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>El proceso puede durar entre 2 y 4 semanas desde la primera entrevista hasta la decisión final, dependiendo de la posición y el número de candidatos.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Qué debo preparar para mi entrevista?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Te recomendamos investigar sobre SolFis, preparar ejemplos concretos de tu experiencia relacionados con el puesto, y tener listos tus documentos de respaldo (certificados, títulos, etc.)</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Ofrecen puestos para pasantes o recién graduados?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Sí, contamos con programas de pasantías y posiciones junior para jóvenes talentos. Estas oportunidades se publican regularmente en nuestro portal de empleos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/min/tiny-slider.js"></script>
    <script src="../js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="assets/js/vacantes.js"></script>
    
    <script>
        // Inicializar AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Toggle búsqueda avanzada
        const toggleAdvanced = document.getElementById('toggleAdvanced');
        const advancedFields = document.getElementById('advancedFields');
        
        if (toggleAdvanced && advancedFields) {
            toggleAdvanced.addEventListener('click', function(e) {
                e.preventDefault();
                advancedFields.classList.toggle('show');
                this.querySelector('i').classList.toggle('fa-chevron-down');
                this.querySelector('i').classList.toggle('fa-chevron-up');
            });
        }
        
        // Inicializar slider de testimonios
        if (document.querySelector('.testimonials-slider')) {
            let slider = tns({
                container: '.testimonials-slider',
                items: 1,
                slideBy: 1,
                autoplay: true,
                autoplayButtonOutput: false,
                controls: false,
                nav: true,
                navPosition: 'bottom',
                responsive: {
                    768: {
                        items: 2,
                        gutter: 20
                    },
                    992: {
                        items: 3,
                        gutter: 30
                    }
                }
            });
        }
        
        // Toggle FAQ
        const faqItems = document.querySelectorAll('.faq-question');
        
        faqItems.forEach(item => {
            item.addEventListener('click', () => {
                item.parentElement.classList.toggle('active');
                const icon = item.querySelector('.faq-toggle i');
                icon.classList.toggle('fa-plus');
                icon.classList.toggle('fa-minus');
            });
        });
    </script>
</body>
</html>