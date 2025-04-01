<?php
// Incluir archivos necesarios
require_once '../includes/database.php';
require_once '../includes/jobs-system.php';

// Verificar si se proporcionó un slug
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

$slug = $_GET['slug'];

// Obtener datos de la vacante
$vacantesManager = new VacantesManager();
$vacante = $vacantesManager->getVacanteBySlug($slug);

// Si no existe la vacante, redirigir
if (!$vacante) {
    header('Location: index.php?error=' . urlencode('La vacante no existe o ha sido eliminada'));
    exit;
}

// Incrementar contador de vistas
$vacantesManager->incrementarVistas($vacante['id']);

// Verificar si está cerrada
$estaCerrada = false;
if ($vacante['estado'] !== 'Publicada' || ($vacante['fecha_cierre'] && strtotime($vacante['fecha_cierre']) < time())) {
    $estaCerrada = true;
}

// Obtener vacantes relacionadas
$vacantesRelacionadas = $vacantesManager->getVacantesRelacionadas($vacante['id'], $vacante['categoria_id'], 3);

// Configuración de la página
$site_title = $vacante['titulo'] . " - Vacantes SolFis";
$site_description = substr(strip_tags($vacante['descripcion']), 0, 160);
$base_path = '../sections/';
$assets_path = '../assets/';
$page_canonical = "https://solfis.com.do/vacantes/detalle.php?slug=" . $slug;
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
    <meta property="og:type" content="article">
    
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
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Encabezado de la Vacante -->
        <section class="job-detail-header">
            <div class="container">
                <div class="job-detail-content">
                    <div class="job-detail-meta">
                        <div class="job-detail-category" style="background-color: <?php echo !empty($vacante['color']) ? $vacante['color'] : '#0066CC'; ?>">
                            <?php echo htmlspecialchars($vacante['categoria_nombre']); ?>
                        </div>
                        <div class="job-detail-date">
                            <i class="far fa-calendar-alt"></i>
                            <span>Publicada: <?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                        </div>
                        <?php if ($vacante['fecha_cierre']): ?>
                        <div class="job-detail-date">
                            <i class="far fa-calendar-times"></i>
                            <span>Cierra: <?php echo date('d/m/Y', strtotime($vacante['fecha_cierre'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1><?php echo htmlspecialchars($vacante['titulo']); ?></h1>
                    
                    <div class="job-detail-company">
                        <img src="../img/logo-icon.png" alt="SolFis">
                        <div class="job-detail-company-info">
                            <h3>SolFis</h3>
                            <p><?php echo htmlspecialchars($vacante['ubicacion']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenido Principal -->
        <section class="job-detail-main">
            <div class="container">
                <div class="job-detail-grid">
                    <!-- Columna Principal -->
                    <div class="job-detail-description">
                        <?php if ($estaCerrada): ?>
                        <div class="job-closed-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Esta vacante ya no está disponible. Por favor, consulta nuestras <a href="buscar.php">otras oportunidades</a> o <a href="aplicar.php?tipo=general">envía tu CV espontáneo</a>.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="job-section">
                            <h2>Descripción</h2>
                            <?php 
                            // Procesar saltos de línea en la descripción
                            echo nl2br(htmlspecialchars($vacante['descripcion'])); 
                            ?>
                        </div>
                        
                        <div class="job-section">
                            <h2>Requisitos</h2>
                            <?php 
                            // Procesar saltos de línea en los requisitos
                            echo nl2br(htmlspecialchars($vacante['requisitos'])); 
                            ?>
                        </div>
                        
                        <?php if (!empty($vacante['responsabilidades'])): ?>
                        <div class="job-section">
                            <h2>Responsabilidades</h2>
                            <?php echo nl2br(htmlspecialchars($vacante['responsabilidades'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($vacante['beneficios'])): ?>
                        <div class="job-section">
                            <h2>Beneficios</h2>
                            <?php echo nl2br(htmlspecialchars($vacante['beneficios'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Compartir y Aplicar (versión móvil) -->
                        <div class="job-section d-md-none">
                            <?php if (!$estaCerrada): ?>
                            <a href="aplicar.php?vacante=<?php echo $vacante['id']; ?>" class="apply-btn">
                                <i class="fas fa-paper-plane"></i> Aplicar Ahora
                            </a>
                            <?php endif; ?>
                            
                            <div class="share-buttons mt-4">
                                <p class="share-text">Compartir esta vacante:</p>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($page_canonical); ?>" target="_blank" class="share-btn facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($page_canonical); ?>&text=<?php echo urlencode($vacante['titulo'] . ' - Vacante en SolFis'); ?>" target="_blank" class="share-btn twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($page_canonical); ?>" target="_blank" class="share-btn linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($vacante['titulo'] . ' - Vacante en SolFis: ' . $page_canonical); ?>" target="_blank" class="share-btn whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (count($vacantesRelacionadas) > 0): ?>
                        <div class="job-section">
                            <h2>Vacantes Relacionadas</h2>
                            <div class="related-jobs">
                                <?php foreach ($vacantesRelacionadas as $rel): ?>
                                <div class="related-job-item">
                                    <h3><a href="detalle.php?slug=<?php echo $rel['slug']; ?>"><?php echo htmlspecialchars($rel['titulo']); ?></a></h3>
                                    <div class="related-job-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($rel['ubicacion']); ?></span>
                                        <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($rel['modalidad']); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="job-sidebar">
                        <!-- Resumen y botón de aplicar -->
                        <div class="job-sidebar-card">
                            <?php if (!$estaCerrada): ?>
                            <a href="aplicar.php?vacante=<?php echo $vacante['id']; ?>" class="apply-btn">
                                <i class="fas fa-paper-plane"></i> Aplicar Ahora
                            </a>
                            
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($page_canonical); ?>" target="_blank" class="share-btn facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($page_canonical); ?>&text=<?php echo urlencode($vacante['titulo'] . ' - Vacante en SolFis'); ?>" target="_blank" class="share-btn twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($page_canonical); ?>" target="_blank" class="share-btn linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($vacante['titulo'] . ' - Vacante en SolFis: ' . $page_canonical); ?>" target="_blank" class="share-btn whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="job-closed-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>Esta vacante ya no está disponible</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Detalles de la vacante -->
                        <div class="job-sidebar-card">
                            <h3>Detalles de la Vacante</h3>
                            <ul class="job-overview-list">
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Ubicación</h4>
                                        <p><?php echo htmlspecialchars($vacante['ubicacion']); ?></p>
                                    </div>
                                </li>
                                
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Modalidad</h4>
                                        <p><?php echo htmlspecialchars($vacante['modalidad']); ?></p>
                                    </div>
                                </li>
                                
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Jornada</h4>
                                        <p><?php echo htmlspecialchars($vacante['jornada']); ?></p>
                                    </div>
                                </li>
                                
                                <?php if (!empty($vacante['departamento'])): ?>
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Departamento</h4>
                                        <p><?php echo htmlspecialchars($vacante['departamento']); ?></p>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Nivel</h4>
                                        <p><?php echo htmlspecialchars($vacante['nivel']); ?></p>
                                    </div>
                                </li>
                                
                                <?php if ($vacante['mostrar_salario'] && (!empty($vacante['salario_min']) || !empty($vacante['salario_max']))): ?>
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Salario</h4>
                                        <p>
                                        <?php 
                                        if (!empty($vacante['salario_min']) && !empty($vacante['salario_max'])) {
                                            echo number_format($vacante['salario_min'], 0, '.', ',') . ' - ' . number_format($vacante['salario_max'], 0, '.', ',') . ' RD;
                                        } elseif (!empty($vacante['salario_min'])) {
                                            echo 'Desde ' . number_format($vacante['salario_min'], 0, '.', ',') . ' RD;
                                        } elseif (!empty($vacante['salario_max'])) {
                                            echo 'Hasta ' . number_format($vacante['salario_max'], 0, '.', ',') . ' RD;
                                        }
                                        ?>
                                        </p>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <li class="job-overview-item">
                                    <div class="job-overview-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <div class="job-overview-detail">
                                        <h4>Vistas</h4>
                                        <p><?php echo number_format($vacante['vistas'], 0, '.', ','); ?></p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Etiquetas/Habilidades -->
                        <?php
                        // Obtener habilidades/etiquetas si las hay
                        $habilidades = $vacantesManager->getVacanteHabilidades($vacante['id']);
                        if (count($habilidades) > 0):
                        ?>
                        <div class="job-sidebar-card">
                            <h3>Habilidades Requeridas</h3>
                            <div class="job-skills">
                                <?php foreach ($habilidades as $habilidad): ?>
                                <span class="job-skill"><?php echo htmlspecialchars($habilidad['nombre']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Otras Vacantes -->
                        <div class="job-sidebar-card">
                            <h3>Explora Más Vacantes</h3>
                            <p>¿No es lo que buscabas? Revisa todas nuestras oportunidades disponibles o envía tu CV para futuras vacantes.</p>
                            <div class="d-grid gap-2 mt-3">
                                <a href="buscar.php" class="btn-secondary">
                                    <i class="fas fa-search"></i> Ver Todas las Vacantes
                                </a>
                                <a href="aplicar.php?tipo=general" class="btn-outline">
                                    <i class="fas fa-file-alt"></i> Enviar CV Espontáneo
                                </a>
                            </div>
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
        
        // Incrementar contador de vistas de forma asíncrona
        fetch('ajax/incrementar-vista.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'vacante_id=<?php echo $vacante['id']; ?>'
        })
        .then(response => response.json())
        .then(data => console.log('Vista registrada'))
        .catch(error => console.error('Error:', error));
    </script>
</body>
</html>