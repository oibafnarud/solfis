<?php
$site_title = "Detalle de Vacante - SolFis";
$site_description = "Información detallada sobre la vacante y proceso de aplicación en SolFis";
$base_path = '../sections/';
$assets_path = '../assets/';

// Incluir el sistema de vacantes
require_once '../includes/jobs-system.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// Obtener ID de la vacante
$id = (int)$_GET['id'];

// Instanciar gestores
$vacancyManager = new VacancyManager();

// Obtener vacante por ID
$vacante = $vacancyManager->getVacancyById($id);

// Si la vacante no existe o no está publicada, redirigir
if (!$vacante || ($vacante['estado'] !== 'publicada' && !isset($_GET['preview']))) {
    header('Location: index.php');
    exit;
}

// Obtener vacantes similares
$filters = [
    'estado' => 'publicada',
    'categoria' => $vacante['categoria_id'],
    'excluir_id' => $id
];
$vacantes_similares = $vacancyManager->getVacancies(1, 3, $filters)['vacancies'];

// Formatear descripción, requisitos, etc.
$requisitos = explode("\n", $vacante['requisitos']);
$responsabilidades = explode("\n", $vacante['responsabilidades']);
$beneficios = $vacante['beneficios'] ? explode("\n", $vacante['beneficios']) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vacante['titulo']) . ' - ' . $site_title; ?></title>
    <meta name="description" content="<?php echo VacancyUtils::truncate(strip_tags($vacante['descripcion']), 160); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($vacante['titulo']); ?> - SolFis">
    <meta property="og:description" content="<?php echo VacancyUtils::truncate(strip_tags($vacante['descripcion']), 160); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/img/logo.png'; ?>">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Job Detail Section -->
        <section class="job-detail">
            <div class="container">
                <div class="breadcrumbs" data-aos="fade-up">
                    <a href="../index.php">Inicio</a> <span class="separator">/</span>
                    <a href="index.php">Vacantes</a> <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars($vacante['titulo']); ?></span>
                </div>
                
                <div class="job-detail-layout" data-aos="fade-up">
                    <div class="job-detail-main">
                        <div class="job-detail-card">
                            <?php if ($vacante['estado'] === 'cerrada'): ?>
                            <div class="job-closed-alert">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Esta vacante ya no está disponible.</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="job-detail-header">
                                <h1 class="job-detail-title"><?php echo htmlspecialchars($vacante['titulo']); ?></h1>
                                
                                <div class="job-detail-meta">
                                    <span class="job-detail-meta-item">
                                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?>
                                    </span>
                                    <span class="job-detail-meta-item">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?>
                                    </span>
                                    <span class="job-detail-meta-item">
                                        <i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?>
                                    </span>
                                    <span class="job-detail-meta-item">
                                        <i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?>
                                    </span>
                                </div>
                                
                                <div class="job-detail-company">
                                    <img src="../img/logo-icon.png" alt="SolFis" class="job-detail-company-logo">
                                    <div class="job-detail-company-info">
                                        <h3>SolFis</h3>
                                        <p>Soluciones Fiscales y Contables</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-detail-section">
                                <h3>Descripción de la Posición</h3>
                                <div class="job-detail-description">
                                    <?php echo nl2br(htmlspecialchars($vacante['descripcion'])); ?>
                                </div>
                            </div>
                            
                            <div class="job-detail-section">
                                <h3>Responsabilidades</h3>
                                <ul class="job-detail-list">
                                    <?php foreach ($responsabilidades as $resp): ?>
                                        <?php if (trim($resp)): ?>
                                        <li><?php echo htmlspecialchars($resp); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="job-detail-section">
                                <h3>Requisitos</h3>
                                <ul class="job-detail-list">
                                    <?php foreach ($requisitos as $req): ?>
                                        <?php if (trim($req)): ?>
                                        <li><?php echo htmlspecialchars($req); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <?php if (!empty($beneficios) && trim($beneficios[0])): ?>
                            <div class="job-detail-section">
                                <h3>Beneficios</h3>
                                <ul class="job-detail-list benefits-list">
                                    <?php foreach ($beneficios as $ben): ?>
                                        <?php if (trim($ben)): ?>
                                        <li><?php echo htmlspecialchars($ben); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="job-detail-actions">
                                <?php if ($vacante['estado'] === 'publicada'): ?>
                                <a href="aplicar.php?id=<?php echo $vacante['id']; ?>" class="btn-apply-now">
                                    <i class="fas fa-paper-plane"></i> Aplicar Ahora
                                </a>
                                <button class="btn-save-job" data-job-id="<?php echo $vacante['id']; ?>">
                                    <i class="far fa-bookmark"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn-job-closed" disabled>
                                    <i class="fas fa-times-circle"></i> Vacante Cerrada
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="job-detail-share">
                            <h3>Compartir esta vacante</h3>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share facebook" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis'); ?>" class="btn-share twitter" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($vacante['titulo']); ?>&summary=<?php echo urlencode(VacancyUtils::truncate(strip_tags($vacante['descripcion']), 100)); ?>" class="btn-share linkedin" target="_blank" rel="noopener noreferrer" aria-label="Compartir en LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="https://wa.me/?text=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis - ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share whatsapp" target="_blank" rel="noopener noreferrer" aria-label="Compartir por WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode('Vacante: ' . $vacante['titulo'] . ' en SolFis'); ?>&body=<?php echo urlencode("He encontrado esta vacante que podría interesarte:\n\n" . $vacante['titulo'] . "\n\n" . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn-share email" aria-label="Compartir por Email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="job-detail-sidebar">
                        <div class="job-sidebar-card">
                            <h3>Resumen de la Vacante</h3>
                            <div class="job-summary">
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Fecha de Publicación</span>
                                    <span class="job-summary-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Categoría</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Ubicación</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Modalidad</span>
                                    <span class="job-summary-value"><?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Tipo de Contrato</span>
                                    <span class="job-summary-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                                </div>
                                
                                <?php if (!empty($vacante['experiencia'])): ?>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Experiencia</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['experiencia']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($vacante['mostrar_salario'] && ($vacante['salario_min'] > 0 || $vacante['salario_max'] > 0)): ?>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Salario</span>
                                    <span class="job-summary-value">
                                        <?php 
                                        if ($vacante['salario_min'] > 0 && $vacante['salario_max'] > 0) {
                                            echo 'RD$ ' . number_format($vacante['salario_min'], 0, '.', ',') . ' - ' . number_format($vacante['salario_max'], 0, '.', ',');
                                        } elseif ($vacante['salario_min'] > 0) {
                                            echo 'Desde RD$ ' . number_format($vacante['salario_min'], 0, '.', ',');
                                        } elseif ($vacante['salario_max'] > 0) {
                                            echo 'Hasta RD$ ' . number_format($vacante['salario_max'], 0, '.', ',');
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($vacante['fecha_cierre']): ?>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Fecha de Cierre</span>
                                    <span class="job-summary-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_cierre'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($vacantes_similares)): ?>
                        <div class="job-sidebar-card">
                            <h3>Vacantes Similares</h3>
                            <div class="similar-jobs-list">
                                <?php foreach ($vacantes_similares as $similar): ?>
                                <div class="similar-job-item">
                                    <img src="../img/logo-icon.png" alt="SolFis" class="similar-job-logo">
                                    <div class="similar-job-info">
                                        <h4><a href="detalle.php?id=<?php echo $similar['id']; ?>"><?php echo htmlspecialchars($similar['titulo']); ?></a></h4>
                                        <div class="similar-job-meta">
                                            <span><?php echo htmlspecialchars($similar['categoria_nombre']); ?></span>
                                            <span><?php echo htmlspecialchars($similar['ubicacion']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
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
                    <h2>¿Te interesa esta oportunidad?</h2>
                    <p>Da el siguiente paso en tu carrera profesional y únete a nuestro equipo.</p>
                    <div class="cta-buttons">
                        <?php if ($vacante['estado'] === 'publicada'): ?>
                        <a href="aplicar.php?id=<?php echo $vacante['id']; ?>" class="btn-primary">Aplicar Ahora</a>
                        <a href="listado.php" class="btn-secondary">Explorar Más Vacantes</a>
                        <?php else: ?>
                        <a href="listado.php" class="btn-primary">Ver Otras Vacantes</a>
                        <?php endif; ?>
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