// stats.js - Archivo optimizado para garantizar la animación de contadores
document.addEventListener('DOMContentLoaded', function() {
    initCounters();
    initProgressBars();
    
    // Inicializar contadores
    function initCounters() {
        // Configuración específica para cada contador
        const countersConfig = {
            'counter-experience': {
                target: 15,
                suffix: '+',
                duration: 1500
            },
            'counter-clients': {
                target: 1500,
                suffix: '+', 
                duration: 2000
            },
            'counter-retention': {
                target: 98,
                suffix: '%',
                duration: 1800
            }
            // El contador "24/7" es estático, no necesita animación
        };
        
        // Obtener todos los contadores
        Object.keys(countersConfig).forEach(id => {
            const counterElement = document.getElementById(id);
            if (counterElement) {
                setupCounter(
                    counterElement, 
                    countersConfig[id].target, 
                    countersConfig[id].suffix,
                    countersConfig[id].duration
                );
            }
        });
        
        // Implementación alternativa: buscar por clase
        const genericCounters = document.querySelectorAll('.stat-number');
        genericCounters.forEach(counter => {
            // Solo procesar contadores que no tengan ID específico
            if (!counter.id) {
                const label = counter.closest('.stat-card')?.querySelector('.stat-label')?.textContent.toLowerCase() || '';
                let target = 0;
                let suffix = '';
                
                if (label.includes('experiencia')) {
                    target = 15;
                    suffix = '+';
                } else if (label.includes('clientes') || label.includes('satisfechos')) {
                    target = 1500;
                    suffix = '+';
                } else if (label.includes('retención')) {
                    target = 98;
                    suffix = '%';
                } else if (label.includes('soporte') || label.includes('cliente')) {
                    // No hacer nada para "24/7", es estático
                    return;
                }
                
                if (target > 0) {
                    setupCounter(counter, target, suffix, 2000);
                }
            }
        });
    }
    
    // Configurar un contador individual
    function setupCounter(element, targetValue, suffix, duration) {
        // Establecer valor inicial
        element.textContent = "0" + suffix;
        
        // Observador para iniciar la animación cuando el elemento esté visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(element, targetValue, suffix, duration);
                    observer.unobserve(element);
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(element);
    }
    
    // Animación del contador
    function animateCounter(element, target, suffix, duration) {
        const startTime = performance.now();
        const initialValue = 0;
        
        // Función para la curva de animación (ease-out)
        const easeOutQuad = t => t * (2 - t);
        
        // Función de actualización de la animación
        function updateCounter(currentTime) {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            const easedProgress = easeOutQuad(progress);
            const currentValue = Math.floor(initialValue + (target - initialValue) * easedProgress);
            
            element.textContent = currentValue + suffix;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    // Inicializar barras de progreso
    function initProgressBars() {
        const progressBars = document.querySelectorAll('.stat-progress .progress-bar');
        
        progressBars.forEach(bar => {
            // Guardar el ancho objetivo
            const targetWidth = bar.style.width;
            
            // Iniciar en cero
            bar.style.width = '0%';
            
            // Observador para animar cuando sea visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Animar al ancho objetivo
                        setTimeout(() => {
                            bar.style.transition = 'width 1.5s ease-out';
                            bar.style.width = targetWidth;
                        }, 200);
                        
                        observer.unobserve(bar);
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(bar);
        });
    }
    
    // Solución de respaldo: asegurar que los valores estáticos estén correctos
    function ensureStaticValues() {
        const supportCounter = document.getElementById('counter-support');
        if (supportCounter && supportCounter.textContent !== '24/7') {
            supportCounter.textContent = '24/7';
        }
    }
    
    // Ejecutar después de un breve retraso para asegurar que los DOM están listos
    setTimeout(ensureStaticValues, 100);
});