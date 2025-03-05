// hero-banner.js - Interactividad y animaciones para el nuevo banner

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const hero = document.querySelector('.hero');
    const heroGrid = document.querySelector('.hero-grid');
    const mainCube = document.querySelector('.main-cube');
    const floatingCubes = document.querySelectorAll('.floating-cube');
    const scrollIndicator = document.querySelector('.scroll-indicator');
    
    // Efecto parallax en movimiento del mouse
    hero.addEventListener('mousemove', function(e) {
        if (window.innerWidth < 992) return; // Desactivar en móviles
        
        const mouseX = e.clientX / window.innerWidth - 0.5;
        const mouseY = e.clientY / window.innerHeight - 0.5;
        
        // Parallax para la grid
        if (heroGrid) {
            heroGrid.style.transform = `perspective(1000px) rotateX(${60 + mouseY * 5}deg) scale(2.5) translateY(${mouseY * 20}px)`;
        }
        
        // Parallax para el cubo principal
        if (mainCube) {
            mainCube.style.transform = `translate(-50%, -50%) rotateX(${30 + mouseY * 10}deg) rotateY(${45 + mouseX * 20}deg)`;
        }
        
        // Parallax para los cubos flotantes
        floatingCubes.forEach((cube, index) => {
            const factor = (index + 1) * 0.1;
            cube.style.transform = `translateY(0) rotateX(${30 + mouseY * 15 * factor}deg) rotateY(${45 + mouseX * 30 * factor}deg)`;
        });
    });
    
    // Devolver elementos a su posición original al salir
    hero.addEventListener('mouseleave', function() {
        if (heroGrid) {
            heroGrid.style.transform = 'perspective(1000px) rotateX(60deg) scale(2.5)';
        }
        
        if (mainCube) {
            mainCube.style.transform = 'translate(-50%, -50%) rotateX(30deg) rotateY(45deg)';
            // Reiniciar animación
            void mainCube.offsetWidth; // Trigger reflow
            mainCube.style.animation = 'rotate-slow 20s linear infinite';
        }
        
        floatingCubes.forEach(cube => {
            cube.style.transform = 'translateY(0) rotateX(30deg) rotateY(45deg)';
            // Reiniciar animación
            void cube.offsetWidth; // Trigger reflow
            cube.style.animation = cube.dataset.originalAnimation || 'float-cube 8s ease-in-out infinite';
        });
    });
    
    // Efecto parallax en scroll
    window.addEventListener('scroll', function() {
        const scrolled = window.scrollY;
        
        // Ocultar indicador de scroll
        if (scrolled > 100 && scrollIndicator) {
            scrollIndicator.style.opacity = '0';
        } else if (scrollIndicator) {
            scrollIndicator.style.opacity = '0.8';
        }
        
        // Parallax para elementos al hacer scroll
        if (heroGrid) {
            heroGrid.style.transform = `perspective(1000px) rotateX(60deg) scale(2.5) translateY(${scrolled * 0.1}px)`;
        }
        
        // Efecto de desvanecimiento para el hero
        if (hero) {
            // Calcular opacidad basada en scroll (desvanecimiento gradual)
            const heroHeight = hero.offsetHeight;
            const fadeStart = heroHeight * 0.4; // Comenzar a desvanecer a partir del 40% de la altura
            const fadeEnd = heroHeight * 0.8;  // Completar desvanecimiento al 80%
            const opacity = 1 - Math.min(1, Math.max(0, (scrolled - fadeStart) / (fadeEnd - fadeStart)));
            
            // Aplicar transformación y opacidad
            hero.style.transform = `translateY(${scrolled * 0.4}px)`;
            // No aplicamos opacidad al contenedor completo para evitar desaparecer demasiado pronto
            // Solo aplicamos a elementos decorativos
            document.querySelectorAll('.hero-shape, .floating-dots').forEach(element => {
                element.style.opacity = opacity * 0.5; // Mantener la opacidad base
            });
        }
    });
    
    // Guardar animaciones originales para poder resetearlas
    floatingCubes.forEach(cube => {
        cube.dataset.originalAnimation = window.getComputedStyle(cube).animation;
    });
    
    // Pequeña animación para el indicador de scroll
    if (scrollIndicator) {
        setTimeout(() => {
            scrollIndicator.style.animation = 'fadeIn 1s ease forwards';
        }, 2000);
    }
    
    // Detección de dispositivos táctiles para optimizar la experiencia
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (isTouchDevice) {
        // Simplificar animaciones en dispositivos táctiles
        document.querySelectorAll('.hero-shape, .floating-cube, .floating-dots').forEach(element => {
            element.style.animation = 'none';
        });
        
        if (mainCube) {
            mainCube.style.animation = 'rotate-slow 30s linear infinite'; // Más lento en móvil
        }
        
        if (heroGrid) {
            heroGrid.style.opacity = '0.3'; // Menos prominente en móvil
        }
    }
    
    // Animaciones de entrada
    function animateHeroElements() {
        // Animación del título con retardo escalonado
        const heroTitle = document.querySelector('.hero-title');
        if (heroTitle) {
            heroTitle.style.opacity = '0';
            heroTitle.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroTitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                heroTitle.style.opacity = '1';
                heroTitle.style.transform = 'translateY(0)';
            }, 300);
        }
        
        // Animación de la descripción
        const heroDescription = document.querySelector('.hero-description');
        if (heroDescription) {
            heroDescription.style.opacity = '0';
            heroDescription.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroDescription.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                heroDescription.style.opacity = '1';
                heroDescription.style.transform = 'translateY(0)';
            }, 500);
        }
        
        // Animación de los CTA
        const heroCta = document.querySelector('.hero-cta');
        if (heroCta) {
            heroCta.style.opacity = '0';
            heroCta.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroCta.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                heroCta.style.opacity = '1';
                heroCta.style.transform = 'translateY(0)';
            }, 700);
        }
        
        // Animación de los stats
        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            heroStats.style.opacity = '0';
            heroStats.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroStats.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                heroStats.style.opacity = '1';
                heroStats.style.transform = 'translateY(0)';
            }, 900);
        }
        
        // Animación del elemento visual
        const heroVisual = document.querySelector('.hero-visual');
        if (heroVisual) {
            heroVisual.style.opacity = '0';
            heroVisual.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                heroVisual.style.transition = 'opacity 1s ease, transform 1s ease';
                heroVisual.style.opacity = '1';
                heroVisual.style.transform = 'scale(1)';
            }, 400);
        }
    }
    
    // Iniciar animaciones después de un breve retardo
    setTimeout(animateHeroElements, 200);
    
    // Animación de conteo para las estadísticas
    function animateStats() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach(stat => {
            const targetValue = parseFloat(stat.textContent);
            const suffix = stat.textContent.replace(/[0-9.]/g, '');
            let startValue = 0;
            const duration = 2000;
            const frameDuration = 1000/60;
            const totalFrames = Math.round(duration / frameDuration);
            const easeOutQuad = t => t * (2 - t);
            
            let frame = 0;
            
            const animate = () => {
                frame++;
                const progress = easeOutQuad(frame / totalFrames);
                const currentValue = startValue + (targetValue - startValue) * progress;
                
                if (Number.isInteger(targetValue)) {
                    stat.textContent = Math.floor(currentValue) + suffix;
                } else {
                    stat.textContent = currentValue.toFixed(1) + suffix;
                }
                
                if (frame < totalFrames) {
                    requestAnimationFrame(animate);
                }
            };
            
            // Iniciar animación cuando el elemento sea visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        requestAnimationFrame(animate);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            observer.observe(stat);
        });
    }
    
    // Activar animación de stats cuando estén visibles
    animateStats();
    
    // Añadir efectos al hover en botones
    const primaryBtn = document.querySelector('.hero .btn-primary');
    if (primaryBtn) {
        primaryBtn.addEventListener('mouseenter', () => {
            const icon = primaryBtn.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
                icon.style.transform = 'translateX(5px)';
            }
        });
        
        primaryBtn.addEventListener('mouseleave', () => {
            const icon = primaryBtn.querySelector('i');
            if (icon) {
                icon.style.transform = 'translateX(0)';
            }
        });
    }
    
    // Optimización para dispositivos móviles
    function optimizeForMobile() {
        if (window.innerWidth < 768) {
            // Simplificar animaciones en dispositivos móviles
            document.querySelectorAll('.cube-glow, .floating-dots').forEach(el => {
                el.style.display = 'none';
            });
            
            // Reducir tamaño de los cubos
            if (mainCube) {
                mainCube.style.transform = 'translate(-50%, -50%) rotateX(30deg) rotateY(45deg) scale(0.8)';
            }
            
            // Ocultar indicador de scroll
            if (scrollIndicator) {
                scrollIndicator.style.display = 'none';
            }
        }
    }
    
    // Llamar a optimización para móviles
    optimizeForMobile();
    
    // Recalcular en cambio de orientación
    window.addEventListener('resize', () => {
        optimizeForMobile();
    });
});