<?php
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$testManager = new TestManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Obtener estadísticas de pruebas completadas
$pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
$totalPruebasCompletadas = count($pruebasCompletadas);

// Título de la página
$pageTitle = 'Servicios Premium - Resultados y Desarrollo Profesional';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SolFis Talentos</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    <link rel="stylesheet" href="css/premium.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="content-header">
                <h1>Servicios Premium</h1>
                <a href="panel.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>
            
            <!-- Banner principal -->
            <div class="premium-hero">
                <div class="hero-content">
                    <h2>Potencia tu desarrollo profesional</h2>
                    <p>Accede a tus resultados detallados y servicios exclusivos de asesoramiento para impulsar tu carrera.</p>
                </div>
                <div class="hero-image">
                    <img src="img/premium-hero.svg" alt="Servicios Premium">
                </div>
            </div>
            
            <!-- Información de evaluaciones -->
            <div class="evaluations-summary">
                <div class="summary-stat">
                    <div class="stat-value"><?php echo $totalPruebasCompletadas; ?></div>
                    <div class="stat-label">Evaluaciones completadas</div>
                </div>
                <div class="summary-message">
                    <?php if ($totalPruebasCompletadas > 0): ?>
                    <p>¡Excelente! Has completado <?php echo $totalPruebasCompletadas; ?> evaluación(es). Ahora puedes acceder a tus resultados detallados y servicios personalizados.</p>
                    <?php else: ?>
                    <p>Aún no has completado ninguna evaluación. Para acceder a los servicios premium, debes completar al menos una evaluación psicométrica.</p>
                    <a href="panel.php" class="btn-primary">Ir a mis evaluaciones</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Planes Premium -->
            <div class="premium-plans">
                <h2>Nuestros planes</h2>
                <p class="section-description">Elige el plan que mejor se adapte a tus necesidades de desarrollo profesional</p>
                
                <div class="plans-grid">
                    <!-- Plan Básico -->
                    <div class="plan-card">
                        <div class="plan-header">
                            <h3>Básico</h3>
                            <div class="plan-price">
                                <span class="currency">RD$</span>
                                <span class="amount">1,500</span>
                            </div>
                            <span class="price-period">pago único</span>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li><i class="fas fa-check"></i> Acceso a resultados detallados</li>
                                <li><i class="fas fa-check"></i> Informe de fortalezas y áreas de desarrollo</li>
                                <li><i class="fas fa-check"></i> Gráficos comparativos</li>
                                <li><i class="fas fa-check"></i> Recomendaciones generales</li>
                                <li class="feature-disabled"><i class="fas fa-times"></i> Asesoramiento personalizado</li>
                                <li class="feature-disabled"><i class="fas fa-times"></i> Plan de desarrollo</li>
                                <li class="feature-disabled"><i class="fas fa-times"></i> Sesiones de coaching</li>
                            </ul>
                        </div>
                        <div class="plan-cta">
                            <a href="checkout.php?plan=basico" class="btn-primary">Seleccionar</a>
                        </div>
                    </div>
                    
                    <!-- Plan Profesional -->
                    <div class="plan-card highlighted">
                        <div class="plan-badge">Recomendado</div>
                        <div class="plan-header">
                            <h3>Profesional</h3>
                            <div class="plan-price">
                                <span class="currency">RD$</span>
                                <span class="amount">2,500</span>
                            </div>
                            <span class="price-period">pago único</span>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li><i class="fas fa-check"></i> Acceso a resultados detallados</li>
                                <li><i class="fas fa-check"></i> Informe de fortalezas y áreas de desarrollo</li>
                                <li><i class="fas fa-check"></i> Gráficos comparativos</li>
                                <li><i class="fas fa-check"></i> Recomendaciones personalizadas</li>
                                <li><i class="fas fa-check"></i> 1 sesión de asesoramiento personalizado (30 min)</li>
                                <li><i class="fas fa-check"></i> Plan de desarrollo personal</li>
                                <li class="feature-disabled"><i class="fas fa-times"></i> Sesiones de coaching</li>
                            </ul>
                        </div>
                        <div class="plan-cta">
                            <a href="checkout.php?plan=profesional" class="btn-primary">Seleccionar</a>
                        </div>
                    </div>
                    
                    <!-- Plan Premium -->
                    <div class="plan-card">
                        <div class="plan-header">
                            <h3>Premium</h3>
                            <div class="plan-price">
                                <span class="currency">RD$</span>
                                <span class="amount">5,000</span>
                            </div>
                            <span class="price-period">pago único</span>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li><i class="fas fa-check"></i> Acceso a resultados detallados</li>
                                <li><i class="fas fa-check"></i> Informe de fortalezas y áreas de desarrollo</li>
                                <li><i class="fas fa-check"></i> Gráficos comparativos</li>
                                <li><i class="fas fa-check"></i> Recomendaciones personalizadas</li>
                                <li><i class="fas fa-check"></i> 3 sesiones de asesoramiento personalizado (45 min)</li>
                                <li><i class="fas fa-check"></i> Plan de desarrollo profesional avanzado</li>
                                <li><i class="fas fa-check"></i> 1 sesión de coaching ejecutivo (1 hora)</li>
                            </ul>
                        </div>
                        <div class="plan-cta">
                            <a href="checkout.php?plan=premium" class="btn-primary">Seleccionar</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Beneficios -->
            <div class="benefits-section">
                <h2>Beneficios de conocer tus resultados</h2>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Autoconocimiento</h3>
                        <p>Comprende a profundidad tus fortalezas, áreas de oportunidad y patrones de comportamiento en el entorno laboral.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Desarrollo profesional</h3>
                        <p>Recibe recomendaciones personalizadas para potenciar tus habilidades y avanzar en tu carrera.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>Mejores oportunidades</h3>
                        <p>Identifica las posiciones y entornos laborales donde puedes destacar naturalmente y alcanzar tu máximo potencial.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Asesoramiento experto</h3>
                        <p>Recibe guía personalizada de profesionales en desarrollo de talento y psicología organizacional.</p>
                    </div>
                </div>
            </div>
            
            <!-- Testimonios -->
            <div class="testimonials-section">
                <h2>Lo que dicen nuestros clientes</h2>
                
                <div class="testimonials-slider">
                    <div class="testimonial">
                        <div class="testimonial-content">
                            <p>"Los resultados de las evaluaciones me ayudaron a comprender por qué algunas posiciones anteriores no me satisfacían. Ahora tengo un trabajo que se alinea perfectamente con mis fortalezas."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="img/testimonial-1.jpg" alt="Testimonial">
                            </div>
                            <div class="author-info">
                                <h4>María Rodríguez</h4>
                                <span>Analista Financiero</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <div class="testimonial-content">
                            <p>"El plan de desarrollo profesional que recibí fue increíblemente detallado y práctico. Las sesiones de asesoramiento me ayudaron a implementarlo efectivamente."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="img/testimonial-2.jpg" alt="Testimonial">
                            </div>
                            <div class="author-info">
                                <h4>Carlos Méndez</h4>
                                <span>Gerente de Proyectos</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <div class="testimonial-content">
                            <p>"Nunca había tenido tanta claridad sobre mis fortalezas y limitaciones. Esta inversión en mi desarrollo personal ha sido una de las mejores decisiones que he tomado."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="img/testimonial-3.jpg" alt="Testimonial">
                            </div>
                            <div class="author-info">
                                <h4>Laura Gómez</h4>
                                <span>Especialista en Marketing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="faq-section">
                <h2>Preguntas frecuentes</h2>
                
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Cómo se generan mis resultados?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Tus resultados se generan mediante algoritmos avanzados que analizan tus respuestas a las evaluaciones psicométricas. Estos algoritmos están basados en modelos científicamente validados y son interpretados por profesionales en psicología organizacional.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Cuánto tiempo tengo acceso a mis resultados?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Una vez adquieras cualquiera de nuestros planes, tendrás acceso permanente a tus resultados y podrás consultarlos en cualquier momento desde tu panel de candidato.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Cómo se programan las sesiones de asesoramiento?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Tras adquirir un plan que incluya sesiones de asesoramiento, recibirás un correo electrónico con instrucciones para programar tus sesiones según la disponibilidad de nuestros consultores. Las sesiones pueden realizarse de manera presencial en nuestras oficinas o virtual mediante videollamada.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Puedo actualizar mi plan después de haberlo adquirido?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Sí, puedes actualizar a un plan superior en cualquier momento pagando la diferencia. Para hacerlo, contacta a nuestro equipo de soporte a través del correo soporte@solfis.com.do o desde la sección de contacto en tu panel.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>¿Mis resultados son confidenciales?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Absolutamente. Tus resultados detallados solo son accesibles para ti una vez que adquieras alguno de nuestros planes. Las empresas que utilizan nuestra plataforma para reclutamiento solo pueden ver una versión resumida de tu perfil si autorizas compartir esta información al aplicar a una vacante.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CTA -->
            <div class="cta-section">
                <div class="cta-content">
                    <h2>¿Listo para potenciar tu carrera?</h2>
                    <p>Descubre tus fortalezas y áreas de mejora con nuestros servicios premium.</p>
                    <a href="#" class="btn-primary scroll-to-plans">Ver planes</a>
                </div>
                <div class="cta-image">
                    <img src="img/cta-illustration.svg" alt="Potencia tu carrera">
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle para preguntas frecuentes
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    item.classList.toggle('active');
                });
            });
            
            // Scroll a planes
            const scrollToPlansBtn = document.querySelector('.scroll-to-plans');
            if (scrollToPlansBtn) {
                scrollToPlansBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const plansSection = document.querySelector('.premium-plans');
                    plansSection.scrollIntoView({ behavior: 'smooth' });
                });
            }
            
            // Resaltar sidebar
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage === 'premium.php') {
                document.querySelector('.sidebar-link[href="premium.php"]').classList.add('active');
            }
        });
    </script>
</body>
</html>