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
    <meta name="description" content="<?php echo $site_description; ?>">
    <title><?php echo $site_title; ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/services-page.css">
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
        <section class="services-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Soluciones Integrales para su Empresa</h1>
                    <p>Transformamos la gestión financiera y contable de su negocio con servicios profesionales de alta calidad</p>
                </div>
                <div class="highlight-stats">
                    <div class="stat-item">
                        <div class="stat-number">15+</div>
                        <div class="stat-label">Años de Experiencia</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1500+</div>
                        <div class="stat-label">Clientes Satisfechos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Tasa de Retención</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Beneficios Generales -->
        <section class="benefits-section">
            <div class="container">
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Crecimiento Sostenible</h3>
                        <p>Estrategias probadas para el crecimiento y optimización de su empresa</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Seguridad y Confianza</h3>
                        <p>Soluciones respaldadas por años de experiencia y profesionalismo</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Tecnología Avanzada</h3>
                        <p>Herramientas de última generación para una gestión eficiente</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Servicios Principales -->
		<section class="main-services">
			<div class="container">
				<!-- Contabilidad -->
				<div class="service-detailed">
					<div class="service-content">
						<div class="service-header">
							<div class="service-icon">
								<i class="fas fa-calculator"></i>
							</div>
							<h2>Contabilidad Integrada</h2>
						</div>
						<p class="service-description">
							Sistema integral de gestión contable que permite mantener el control total de sus operaciones financieras.
							Nuestro enfoque moderno combina experiencia profesional con tecnología de punta.
						</p>
						<div class="service-features-grid">
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Estados Financieros</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Registros Contables</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Conciliaciones Bancarias</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Nóminas y Prestaciones</span>
							</div>
						</div>
						<a href="servicios/contabilidad.php" class="service-cta">
							Conocer más sobre Contabilidad
							<i class="fas fa-arrow-right"></i>
						</a>
					</div>
					<div class="service-image">
						<img src="img/services/contabilidad.jpg" alt="Contabilidad Integrada">
					</div>
				</div>

				<!-- Auditoría -->
				<div class="service-detailed reverse">
					<div class="service-content">
						<div class="service-header">
							<div class="service-icon">
								<i class="fas fa-search-dollar"></i>
							</div>
							<h2>Auditoría de Empresas</h2>
						</div>
						<p class="service-description">
							Evaluación exhaustiva de sus procesos financieros y operativos para identificar áreas de mejora
							y garantizar el cumplimiento normativo. Nuestro enfoque preventivo le ayuda a anticipar riesgos.
						</p>
						<div class="service-features-grid">
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Auditoría Operacional</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Auditoría Fiscal</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Auditoría Financiera</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Auditoría Integral</span>
							</div>
						</div>
						<a href="servicios/auditoria.php" class="service-cta">
							Conocer más sobre Auditoría
							<i class="fas fa-arrow-right"></i>
						</a>
					</div>
					<div class="service-image">
						<img src="img/services/auditoria.jpg" alt="Auditoría de Empresas">
					</div>
				</div>

				<!-- Servicios Fiscales -->
				<div class="service-detailed">
					<div class="service-content">
						<div class="service-header">
							<div class="service-icon">
								<i class="fas fa-file-invoice-dollar"></i>
							</div>
							<h2>Servicios Fiscales</h2>
						</div>
						<p class="service-description">
							Asesoría fiscal especializada para optimizar sus obligaciones tributarias y mantener
							el cumplimiento con las regulaciones vigentes. Maximice sus beneficios fiscales legalmente.
						</p>
						<div class="service-features-grid">
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Declaraciones Juradas</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Reportes Fiscales</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Planificación Fiscal</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Cumplimiento Tributario</span>
							</div>
						</div>
						<a href="servicios/fiscal.php" class="service-cta">
							Conocer más sobre Servicios Fiscales
							<i class="fas fa-arrow-right"></i>
						</a>
					</div>
					<div class="service-image">
						<img src="img/services/fiscal.jpg" alt="Servicios Fiscales">
					</div>
				</div>

				<!-- Sistemas de Gestión -->
				<div class="service-detailed reverse">
					<div class="service-content">
						<div class="service-header">
							<div class="service-icon">
								<i class="fas fa-laptop-code"></i>
							</div>
							<h2>Sistemas de Gestión</h2>
						</div>
						<p class="service-description">
							Soluciones tecnológicas integrales para la automatización y control de sus procesos empresariales.
							Optimice sus operaciones con herramientas modernas y eficientes.
						</p>
						<div class="service-features-grid">
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Facturación Electrónica</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Control de Inventario</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Reportes Financieros</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Integración Contable</span>
							</div>
						</div>
						<a href="servicios/sistemas.php" class="service-cta">
							Conocer más sobre Sistemas
							<i class="fas fa-arrow-right"></i>
						</a>
					</div>
					<div class="service-image">
						<img src="img/services/sistemas.jpg" alt="Sistemas de Gestión">
					</div>
				</div>

				<!-- Servicios Empresariales -->
				<div class="service-detailed">
					<div class="service-content">
						<div class="service-header">
							<div class="service-icon">
								<i class="fas fa-building"></i>
							</div>
							<h2>Servicios Empresariales</h2>
						</div>
						<p class="service-description">
							Acompañamiento integral en todos los aspectos legales y administrativos de su empresa.
							Desde la constitución hasta la gestión de permisos y registros especiales.
						</p>
						<div class="service-features-grid">
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Constitución de Empresas</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Registro Comercial</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Gestión de RPE</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check"></i>
								<span>Asesoría Legal</span>
							</div>
						</div>
						<a href="servicios/empresarial.php" class="service-cta">
							Conocer más sobre Servicios Empresariales
							<i class="fas fa-arrow-right"></i>
						</a>
					</div>
					<div class="service-image">
						<img src="assets/img/services/empresarial.jpg" alt="Servicios Empresariales">
					</div>
				</div>
			</div>
		</section>

        <!-- Por Qué Elegirnos -->
        <section class="why-choose-us">
            <div class="container">
                <h2>¿Por qué elegir Solfis?</h2>
                <div class="reasons-grid">
                    <div class="reason-card">
                        <div class="reason-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3>Experiencia Comprobada</h3>
                        <p>Más de 15 años brindando servicios de excelencia a empresas de diversos sectores.</p>
                    </div>
                    <div class="reason-card">
                        <div class="reason-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3>Tecnología Avanzada</h3>
                        <p>Utilizamos las últimas herramientas y software para garantizar precisión y eficiencia.</p>
                    </div>
                    <div class="reason-card">
                        <div class="reason-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Atención Personalizada</h3>
                        <p>Cada cliente recibe un servicio adaptado a sus necesidades específicas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="final-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para optimizar su empresa?</h2>
                    <p>Descubra cómo nuestros servicios pueden ayudarle a alcanzar sus objetivos</p>
                    <a href="contacto.php" class="btn btn-primary">
                        Solicitar Consulta Gratuita
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