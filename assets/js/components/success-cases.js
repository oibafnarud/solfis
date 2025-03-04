// success-cases.js
document.addEventListener('DOMContentLoaded', function() {
    // Efecto parallax en las tarjetas
    const successCards = document.querySelectorAll('.success-card');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        successCards.forEach((card, index) => {
            const offset = scrolled * (0.1 + index * 0.01);
            card.style.transform = `translateY(${-offset}px)`;
        });
    });

    // Animación de métricas
    const metrics = document.querySelectorAll('.metric-number');
    
    const animateMetric = (metric) => {
        const value = metric.textContent;
        const isPercentage = value.includes('%');
        const target = parseInt(value);
        let count = 0;
        
        const updateMetric = () => {
            if (count < target) {
                count += 1;
                metric.textContent = count + (isPercentage ? '%' : 'X');
                requestAnimationFrame(updateMetric);
            }
        };
        
        updateMetric();
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateMetric(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    metrics.forEach(metric => observer.observe(metric));
});