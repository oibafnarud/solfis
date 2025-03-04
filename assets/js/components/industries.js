// industries.js
document.addEventListener('DOMContentLoaded', function() {
    // Animación de los íconos al hover
    const industryIcons = document.querySelectorAll('.industry-icon');
    
    industryIcons.forEach(icon => {
        icon.addEventListener('mouseenter', () => {
            icon.style.transform = 'rotateY(180deg)';
        });
        
        icon.addEventListener('mouseleave', () => {
            icon.style.transform = 'rotateY(0)';
        });
    });

    // Animación de contadores
    const counters = document.querySelectorAll('.stat-number');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.textContent);
        let count = 0;
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60fps

        const updateCounter = () => {
            count += increment;
            if (count < target) {
                counter.textContent = Math.ceil(count) + '+';
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target + '+';
            }
        };

        updateCounter();
    };

    // Observer para iniciar animación cuando sea visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                animateCounter(counter);
                observer.unobserve(counter);
            }
        });
    }, {
        threshold: 0.5
    });

    counters.forEach(counter => observer.observe(counter));
});