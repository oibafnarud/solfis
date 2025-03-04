// hero-building-cubes.js
document.addEventListener('DOMContentLoaded', function() {
    const cubes = document.querySelectorAll('.floating-cube');
    
    // Agregar clase active para iniciar flotación después de la construcción
    cubes.forEach((cube, index) => {
        setTimeout(() => {
            cube.classList.add('active');
        }, (index + 1) * 1000 + 1000); // Esperar a que termine la animación de construcción
    });

    // Reiniciar animación al hacer scroll hacia arriba
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', () => {
        const st = window.pageYOffset || document.documentElement.scrollTop;
        if (st < lastScrollTop) {
            // Scrolling up
            cubes.forEach(cube => {
                cube.style.animation = 'none';
                cube.offsetHeight; // Trigger reflow
                cube.style.animation = null;
            });
        }
        lastScrollTop = st <= 0 ? 0 : st;
    }, false);
});