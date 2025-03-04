<?php
$site_title = "Servicios - Solfis";
$site_description = "Servicios contables, financieros y empresariales en República Dominicana";
$base_path = 'sections/';
$assets_path = 'assets/';
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
	<link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/service-detail.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/text-contrast-fixes.css">
		<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/dropdown-menu.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
   <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="service-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Contabilidad Integrada</h1>
                    <p>Sistema integral de gestión contable que permite mantener el control total de sus operaciones financieras</p>
                    <a href="../contacto" class="btn-primary">Solicitar Servicio</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/contabilidad-hero.jpg" alt="Contabilidad Integrada Solfis">
                </div>
            </div>
        </section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestro servicio de contabilidad integrada es una solución completa diseñada para empresas que buscan precisión, eficiencia y cumplimiento en sus procesos contables. Combinamos experiencia profesional con tecnología avanzada para garantizar resultados óptimos.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Estados Financieros</h4>
                                        <p>Generación y análisis mensual de estados financieros completos y precisos.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Registros Contables</h4>
                                        <p>Mantenimiento actualizado y organizado de todos sus registros contables.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Conciliaciones Bancarias</h4>
                                        <p>Control y seguimiento detallado de todas sus cuentas bancarias.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Nóminas y Prestaciones</h4>
                                        <p>Gestión completa de nóminas y prestaciones laborales.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="service-benefits">
                        <h3>Beneficios</h3>
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h4>Mayor Control</h4>
                                <p>Visibilidad completa de sus operaciones financieras en tiempo real.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h4>Ahorro de Tiempo</h4>
                                <p>Automatización de procesos contables repetitivos.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h4>Cumplimiento</h4>
                                <p>Garantía de cumplimiento con normativas vigentes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proceso de Trabajo -->
        <section class="work-process">
            <div class="container">
                <h2>Nuestro Proceso</h2>
                <div class="process-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Evaluación Inicial</h3>
                        <p>Análisis detallado de sus necesidades y procesos actuales.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Implementación</h3>
                        <p>Configuración de sistemas y procesos personalizados.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Ejecución</h3>
                        <p>Gestión contable continua y generación de reportes.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Seguimiento</h3>
                        <p>Monitoreo constante y ajustes según necesidades.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQs -->
        <section class="service-faqs">
            <div class="container">
                <h2>Preguntas Frecuentes</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>¿Con qué frecuencia se entregan los estados financieros?</h3>
                        <p>Los estados financieros se entregan mensualmente, aunque se puede acordar una frecuencia diferente según sus necesidades.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué software contable utilizan?</h3>
                        <p>Trabajamos con los sistemas contables más modernos y confiables del mercado, adaptándonos a las necesidades de cada cliente.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Ofrecen soporte continuo?</h3>
                        <p>Sí, brindamos soporte continuo y asesoría permanente a todos nuestros clientes.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para optimizar su contabilidad?</h2>
                    <p>Solicite una consulta gratuita y descubra cómo podemos ayudarle</p>
                    <a href="../contacto" class="btn-primary">
                        Contactar Ahora
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="/js/main.js"></script>
	<script src="<?php echo $assets_path; ?>/js/components/nav.js"></script>
	<script src="<?php echo $assets_path; ?>/js/components/footer.js"></script>
</body>
</html>