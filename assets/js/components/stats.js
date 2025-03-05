// fixed-stats.js - Script optimizado para asegurar funcionamiento de contadores
document.addEventListener('DOMContentLoaded', function() {
    // Buscar todos los counters en la página
    initializeCounters();
    
    // También inicializar counters cuando se carga después con AJAX
    document.addEventListener('DOMNodeInserted', function(e) {
        if (e.target.querySelectorAll) {
            const newCounters = e.target.querySelectorAll('.counter');
            if (newCounters.length > 0) {
                initializeCounters();
            }
        }
    });
    
    function initializeCounters() {
        // Seleccionar todos los elementos con clase .counter
        const counters = document.querySelectorAll('.counter');
        
        if (counters.length === 0) {
            console.log('No se encontraron elementos con clase .counter');
            return;
        }
        
        console.log(`Inicializando ${counters.length} contadores`);
        
        counters.forEach(counter => {
            // Verificar si ya está inicializado para evitar reiniciar contadores en proceso
            if (counter.dataset.initialized === 'true') return;
            
            // Marcar como inicializado
            counter.dataset.initialized = 'true';
            
            // El valor objetivo viene del atributo data-target
            const targetValue = parseInt(counter.getAttribute('data-target'));
            if (isNaN(targetValue)) {
                console.log('Counter sin data-target válido:', counter);
                return;
            }
            
            // Configurar la animación
            let startValue = 0;
            const duration = 2000; // 2 segundos
            
            // Función que actualiza el contador
            function updateCounter() {
                if (startValue < targetValue) {
                    // Cálculo del incremento con curva de aceleración
                    const increment = Math.ceil((targetValue - startValue) / 20);
                    startValue += increment;
                    
                    // Asegurar que no sobrepasamos el objetivo
                    if (startValue > targetValue) {
                        startValue = targetValue;
                    }
                    
                    // Actualizar el texto del counter
                    counter.textContent = startValue;
                    
                    // Continuar actualizando si no hemos llegado al objetivo
                    if (startValue < targetValue) {
                        setTimeout(updateCounter, 50);
                    }
                }
            }
            
            // Iniciar cuando el elemento es visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Elemento visible, iniciar animación
                        counter.textContent = '0'; // Iniciar desde cero
                        setTimeout(updateCounter, 200); // Pequeño retraso para efecto visual
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(counter);
        });
    }
    
    // También inicializar las barras de progreso si existen
    initializeProgressBars();
    
    function initializeProgressBars() {
        const progressBars = document.querySelectorAll('.stat-progress .progress-bar');
        
        progressBars.forEach(bar => {
            // Animación de las barras cuando son visibles
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Guardar el ancho original
                        const targetWidth = bar.style.width;
                        
                        // Resetear a cero y luego animar
                        bar.style.transition = 'none';
                        bar.style.width = '0%';
                        
                        // Forzar un reflow para que tome el ancho cero
                        void bar.offsetWidth;
                        
                        // Animar al ancho objetivo
                        bar.style.transition = 'width 1.5s ease-out';
                        setTimeout(() => {
                            bar.style.width = targetWidth;
                        }, 100);
                        
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(bar);
        });
    }
});