// stats.js
document.addEventListener('DOMContentLoaded', function() {
    // Animación de contadores
    const counters = document.querySelectorAll('.counter');
    const speed = 200; // Velocidad de la animación

    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        let count = 0;
        
        const updateCount = () => {
            // Calcular el incremento basado en el objetivo
            const increment = target / speed;
            
            // Incrementar el contador
            count += increment;
            
            // Actualizar el contenido
            counter.innerText = Math.ceil(count);
            
            // Continuar la animación hasta alcanzar el objetivo
            if(count < target) {
                requestAnimationFrame(updateCount);
            } else {
                counter.innerText = target;
            }
        };

        updateCount();
    };

    // Observador de Intersección para iniciar la animación cuando sea visible
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                animateCounter(counter);
                observer.unobserve(counter);
            }
        });
    }, observerOptions);

    // Observar cada contador
    counters.forEach(counter => {
        observer.observe(counter);
    });

    // Efecto de paralaje suave en las tarjetas
    const statCards = document.querySelectorAll('.stat-card');
    
    window.addEventListener('mousemove', (e) => {
        statCards.forEach(card => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            card.style.transform = `perspective(1000px) rotateX(${(y - rect.height/2)/20}deg) rotateY(${(x - rect.width/2)/20}deg)`;
        });
    });

    // Restaurar la posición original al salir
    statCards.forEach(card => {
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });
});