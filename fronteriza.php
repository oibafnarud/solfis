<?php
$site_title = "Servicios Ley Fronteriza - Solfis";
$site_description = "Acompañamiento especializado para empresas que quieren aprovechar los beneficios de la Ley Fronteriza en República Dominicana";
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
    
    <!-- CSS -->

	<link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/contact-section.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/text-contrast-fixes.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/service-detail.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/fronteriza.css">
	
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="service-hero fronteriza-hero">
            <div class="container">
                <div class="hero-content">
                    <span class="special-badge">Servicio Especial</span>
                    <h1>Servicios Ley Fronteriza</h1>
                    <p>Asistencia integral para que su empresa aproveche los beneficios y exenciones fiscales de la Ley de Desarrollo Fronterizo</p>
                    <a href="/contacto.php" class="btn-primary pulse-btn">Consultar Ahora</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/fronteriza-hero.jpg" alt="Ley Fronteriza Dominicana Solfis">
                </div>
            </div>
        </section>

		<!-- SECCIÓN DE BENEFICIOS FORMATO CARDS -->
		<section class="law-info">
			<div class="container">
				<div class="law-header">
					<h2>Ley 28-01 de Desarrollo Fronterizo</h2>
					<p>Una oportunidad única para potenciar su inversión con importantes beneficios fiscales</p>
				</div>
				
				<div class="law-benefits">
					<!-- Card 1 -->
					<div class="benefit-card">
						<div class="benefit-card-header">
							<div class="benefit-icon-wrapper">
								<i class="fas fa-percentage"></i>
							</div>
							<h3>Exención 100% de Impuestos</h3>
						</div>
						<div class="benefit-card-body">
							<p>Exención total de impuestos sobre la renta, ITBIS, aranceles aduaneros y otros impuestos por hasta 20 años, generando un ahorro fiscal sustancial para su empresa.</p>
						</div>
					</div>
					
					<!-- Card 2 -->
					<div class="benefit-card">
						<div class="benefit-card-header">
							<div class="benefit-icon-wrapper">
								<i class="fas fa-industry"></i>
							</div>
							<h3>Múltiples Sectores</h3>
						</div>
						<div class="benefit-card-body">
							<p>Aplicable a empresas agroindustriales, industriales, metalmecánicas, de zona franca, turísticas, metalúrgicas y energéticas, entre otras.</p>
						</div>
					</div>
					
					<!-- Card 3 -->
					<div class="benefit-card">
						<div class="benefit-card-header">
							<div class="benefit-icon-wrapper">
								<i class="fas fa-map-marked-alt"></i>
							</div>
							<h3>Zonas Elegibles</h3>
						</div>
						<div class="benefit-card-body">
							<p>Provincias fronterizas como Pedernales, Independencia, Elías Piña, Dajabón, Montecristi, Santiago Rodríguez y Bahoruco.</p>
						</div>
					</div>
				</div>
			</div>
		</section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestro servicio de asesoría especializada en Ley Fronteriza está diseñado para guiar a su empresa en todo el proceso de certificación y aprovechamiento de los beneficios fiscales que ofrece esta ley. Contamos con un equipo de expertos en derecho fiscal y trámites gubernamentales para maximizar sus oportunidades.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Evaluación de Elegibilidad</h4>
                                        <p>Análisis detallado para determinar si su empresa califica para los beneficios de la ley.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Gestión de Certificación</h4>
                                        <p>Tramitación completa de la documentación necesaria para obtener la certificación.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Planificación Estratégica</h4>
                                        <p>Asesoría para estructurar sus operaciones y maximizar los beneficios de la ley.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Cumplimiento Continuo</h4>
                                        <p>Seguimiento permanente para garantizar el mantenimiento de los beneficios obtenidos.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="service-benefits">
                        <h3>Beneficios de Nuestro Servicio</h3>
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h4>Ahorro de Tiempo</h4>
                                <p>Reducción significativa en el tiempo de obtención de la certificación.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <h4>Seguridad Jurídica</h4>
                                <p>Garantía de cumplimiento con todos los requisitos legales vigentes.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <h4>Maximización de Ahorros</h4>
                                <p>Estrategias para aprovechar al máximo los beneficios fiscales disponibles.</p>
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
                        <h3>Consulta Inicial</h3>
                        <p>Evaluación de su caso y análisis de factibilidad.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Planificación</h3>
                        <p>Diseño de estrategia y preparación de documentación.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Gestión</h3>
                        <p>Tramitación ante el Consejo de Coordinación de la Zona Especial.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Implementación</h3>
                        <p>Acompañamiento en la aplicación de los beneficios obtenidos.</p>
                    </div>
                </div>
            </div>
        </section>

		<!-- SECCIÓN DE CASOS DE ÉXITO FORMATO CARDS -->
		<section class="success-stories">
			<div class="container">
				<h2>Casos de Éxito</h2>
				<div class="success-cards">
					<!-- Card 1 -->
					<div class="success-card">
						<div class="success-card-header">
							<div class="success-logo">
								<img src="../img/cases/agroindustria-logo.png" alt="Agroindustria XYZ">
							</div>
							<div class="success-title">
								<h3>Agroindustria XYZ</h3>
								<span class="success-badge">Sector Agroindustrial</span>
							</div>
						</div>
						<div class="success-card-body">
							<p class="success-description">Empresa de procesamiento de frutas y vegetales que logró establecer una planta de producción en Dajabón, obteniendo un ahorro fiscal de más de RD$15 millones en su primer año de operación bajo la Ley Fronteriza.</p>
							<div class="success-metrics">
								<div class="metric-box">
									<span class="metric-value">100%</span>
									<span class="metric-label">Exención Fiscal</span>
								</div>
								<div class="metric-box">
									<span class="metric-value">45</span>
									<span class="metric-label">Días Proceso</span>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Card 2 -->
					<div class="success-card">
						<div class="success-card-header">
							<div class="success-logo">
								<img src="../img/cases/industrial-logo.png" alt="Industrial ABC">
							</div>
							<div class="success-title">
								<h3>Industrial ABC</h3>
								<span class="success-badge">Sector Industrial</span>
							</div>
						</div>
						<div class="success-card-body">
							<p class="success-description">Manufactura de productos plásticos que trasladó sus operaciones a Montecristi, logrando reducir sus costos operativos en un 35% gracias a los beneficios de la Ley Fronteriza y nuestra asesoría especializada.</p>
							<div class="success-metrics">
								<div class="metric-box">
									<span class="metric-value">35%</span>
									<span class="metric-label">Reducción Costos</span>
								</div>
								<div class="metric-box">
									<span class="metric-value">60</span>
									<span class="metric-label">Días Proceso</span>
								</div>
							</div>
						</div>
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
                        <h3>¿Cuáles empresas pueden acogerse a la Ley Fronteriza?</h3>
                        <p>Pueden acogerse empresas industriales, agroindustriales, metalmecánicas, de zonas francas, turísticas, metalúrgicas y energéticas, que se instalen en las provincias fronterizas de República Dominicana.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Cuál es la duración de los beneficios?</h3>
                        <p>La ley otorga beneficios por un periodo de 20 años a partir de la fecha de la certificación emitida por el Consejo de Coordinación de la Zona Especial.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Cuánto tiempo toma el proceso de certificación?</h3>
                        <p>Con nuestro acompañamiento, el proceso de certificación puede completarse en un promedio de 45-60 días, dependiendo de la complejidad del caso y la disponibilidad de documentación.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué sucede si ya tengo una empresa establecida y quiero trasladarla?</h3>
                        <p>Es posible reubicar una empresa existente a la zona fronteriza para aprovechar los beneficios. Nuestro equipo le asesorará sobre la estrategia más adecuada para este proceso.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta special-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para impulsar su negocio con beneficios fiscales excepcionales?</h2>
                    <p>Solicite una consulta especializada y descubra cómo aprovechar la Ley Fronteriza</p>
                    <a href="../contacto.php" class="btn-primary pulse-btn">
                        Solicitar Asesoría Especializada
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