// footer.js
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del Footer
    const Footer = {
        init() {
            this.initScrollToTop();
            this.initCertificationAnimations();
            this.initSocialLinksHover();
        },

        // Botón Scroll to Top
        initScrollToTop() {
            const scrollBtn = document.querySelector('.scroll-top-btn');
            
            // Mostrar/ocultar botón basado en el scroll
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 500) {
                    scrollBtn?.classList.add('visible');
                } else {
                    scrollBtn?.classList.remove('visible');
                }
            });

            // Evento de click para scroll suave hacia arriba
            scrollBtn?.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },

        // Animaciones de certificaciones
        initCertificationAnimations() {
            const certifications = document.querySelectorAll('.certification-item');
            
            // Observador para animaciones al scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.5
            });

            // Inicializar estado de certificaciones
            certifications.forEach(cert => {
                cert.style.opacity = '0';
                cert.style.transform = 'translateY(20px)';
                cert.style.transition = 'all 0.6s ease';
                observer.observe(cert);
            });
        },

        // Efectos hover en redes sociales
        initSocialLinksHover() {
            const socialLinks = document.querySelectorAll('.social-link');
            
            socialLinks.forEach(link => {
                link.addEventListener('mouseenter', (e) => {
                    // Crear efecto de partículas
                    this.createParticleEffect(e.target);
                });
            });
        },

        // Efecto de partículas para redes sociales
        createParticleEffect(element) {
            const particles = 5;
            const colors = ['#00B1EB', '#ffffff', '#0081AB'];

            for (let i = 0; i < particles; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Estilos base de partículas
                Object.assign(particle.style, {
                    position: 'absolute',
                    width: '4px',
                    height: '4px',
                    borderRadius: '50%',
                    background: colors[Math.floor(Math.random() * colors.length)],
                    pointerEvents: 'none',
                    left: '50%',
                    top: '50%',
                    transform: 'translate(-50%, -50%)'
                });

                // Añadir partícula al elemento
                element.appendChild(particle);

                // Animación de la partícula
                const angle = (360 / particles) * i;
                const velocity = 50 + Math.random() * 50;

                gsap.to(particle, {
                    duration: 0.6 + Math.random() * 0.4,
                    x: Math.cos(angle * Math.PI / 180) * velocity,
                    y: Math.sin(angle * Math.PI / 180) * velocity,
                    opacity: 0,
                    ease: 'power2.out',
                    onComplete: () => particle.remove()
                });
            }
        },

        // Actualizar año del copyright automáticamente
        updateCopyright() {
            const copyrightYear = document.querySelector('.copyright span');
            if (copyrightYear) {
                copyrightYear.textContent = new Date().getFullYear();
            }
        }
    };

    // Inicializar el Footer
    Footer.init();
});

// Smooth Scroll para todos los enlaces del footer
document.querySelectorAll('.footer a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Lazy Loading para imágenes de certificaciones
document.addEventListener('DOMContentLoaded', function() {
    const certImages = document.querySelectorAll('.certification-item img');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });

    certImages.forEach(img => {
        imageObserver.observe(img);
    });
});

// Añadir interactividad a los enlaces legales
document.querySelectorAll('.legal-links a').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
    });

    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});