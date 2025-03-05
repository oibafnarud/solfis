// why-us-fixed.js
document.addEventListener('DOMContentLoaded', function() {
    // Efecto de parallax en la imagen
    initImageParallax();
    
    // Efectos para las tarjetas de características
    initFeatureCards();
    
    // Animación del badge de experiencia
    initExperienceBadge();
    
    // Función para el efecto parallax en la imagen
    function initImageParallax() {
        const whyUsSection = document.querySelector('.why-us');
        const whyUsImage = document.querySelector('.why-us-image img');
        
        if (!whyUsSection || !whyUsImage) return;
        
        window.addEventListener('scroll', () => {
            // Solo aplicar si estamos en viewport para mejor rendimiento
            const rect = whyUsSection.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                const scrolled = window.pageYOffset;
                const sectionTop = whyUsSection.offsetTop;
                const sectionHeight = whyUsSection.offsetHeight;
                
                // Calcular la posición relativa dentro de la sección
                const relativeScroll = scrolled - sectionTop + window.innerHeight;
                const scrollPercentage = Math.min(Math.max(relativeScroll / (sectionHeight + window.innerHeight), 0), 1);
                
                // Aplicar efecto parallax sutil
                const moveAmount = 30; // pixels totales que se moverá
                const translateY = scrollPercentage * moveAmount - (moveAmount / 2);
                
                if (window.innerWidth > 992) { // Solo en pantallas grandes
                    whyUsImage.style.transform = `translateY(${translateY}px)`;
                } else {
                    whyUsImage.style.transform = 'none'; // Reset en móvil
                }
            }
        });
    }
    
    // Función para los efectos de las tarjetas
    function initFeatureCards() {
        const featureCards = document.querySelectorAll('.feature-card');
        
        featureCards.forEach((card, index) => {
            // Retardo escalonado en la aparición
            const delay = index * 100;
            card.style.transition = `all var(--transition-normal) ${delay}ms`;
            
            // Efecto 3D al hover
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left; // posición X relativa dentro de la tarjeta
                const y = e.clientY - rect.top;  // posición Y relativa dentro de la tarjeta
                
                // Convertir a coordenadas normalizadas (-1 a 1)
                const xNorm = (x / rect.width) * 2 - 1;
                const yNorm = (y / rect.height) * 2 - 1;
                
                // Calcular ángulos de rotación (limitar a ±5 grados)
                const rotateX = yNorm * -5;
                const rotateY = xNorm * 5;
                
                // Aplicar transformación 3D
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
                
                // También rotar el ícono ligeramente
                const icon = card.querySelector('.feature-icon');
                if (icon) {
                    icon.style.transform = `rotateY(${xNorm * 15}deg)`;
                }
            });
            
            // Restaurar al salir
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                
                const icon = card.querySelector('.feature-icon');
                if (icon) {
                    icon.style.transform = '';
                }
            });
            
            // Observar para animar al entrar en viewport
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                        observer.unobserve(card);
                    }
                });
            }, { threshold: 0.1 });
            
            // Iniciar como invisible y elevado
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            observer.observe(card);
        });
    }
    
    // Función para animar el badge de experiencia
    function initExperienceBadge() {
        const badge = document.querySelector('.experience-badge');
        
        if (!badge) return;
        
        // Animación flotante continua
        const floatAnimation = () => {
            badge.animate([
                { transform: 'translateY(0px)' },
                { transform: 'translateY(-10px)' },
                { transform: 'translateY(0px)' }
            ], {
                duration: 3000,
                iterations: Infinity,
                easing: 'ease-in-out'
            });
        };
        
        // Iniciar cuando el badge es visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    floatAnimation();
                    observer.unobserve(badge);
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(badge);
        
        // Contador en el badge
        const counter = badge.querySelector('span:first-child');
        
        if (counter && counter.textContent.includes('+')) {
            const targetValue = parseInt(counter.textContent);
            if (!isNaN(targetValue)) {
                // Animación del contador
                let currentValue = 0;
                const duration = 2000; // 2 segundos
                const increment = targetValue / (duration / 50); // Actualizar cada 50ms
                
                counter.textContent = '0+';
                
                const updateCounter = () => {
                    currentValue += increment;
                    
                    if (currentValue >= targetValue) {
                        currentValue = targetValue;
                        counter.textContent = targetValue + '+';
                        return;
                    }
                    
                    counter.textContent = Math.floor(currentValue) + '+';
                    requestAnimationFrame(updateCounter);
                };
                
                // Iniciar contador cuando sea visible
                const counterObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            setTimeout(updateCounter, 500);
                            counterObserver.unobserve(counter);
                        }
                    });
                }, { threshold: 0.1 });
                
                counterObserver.observe(badge);
            }
        }
    }
});