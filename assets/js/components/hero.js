// hero.js
document.addEventListener('DOMContentLoaded', function() {
    // Generar Grid de Cubos
    const cubeGrid = document.querySelector('.cube-grid');
    const gridSize = 3;
    const spacing = 70;

    if (cubeGrid) {
        for (let x = 0; x < gridSize; x++) {
            for (let y = 0; y < gridSize; y++) {
                for (let z = 0; z < gridSize; z++) {
                    if ((x + y + z) % 2 === 0) { // Crear patrón alternado
                        const cube = document.createElement('div');
                        cube.className = 'cube';
                        cube.style.transform = `translate3d(${x * spacing}px, ${y * spacing}px, ${z * spacing}px)`;

                        // Crear caras del cubo
                        const top = document.createElement('div');
                        top.className = 'cube-face top';

                        const front = document.createElement('div');
                        front.className = 'cube-face front';

                        const right = document.createElement('div');
                        right.className = 'cube-face right';

                        cube.appendChild(top);
                        cube.appendChild(front);
                        cube.appendChild(right);

                        cubeGrid.appendChild(cube);

                        // Añadir animación con delay basado en posición
                        cube.style.animation = `appearCube 0.5s ${(x + y + z) * 0.1}s forwards`;
                    }
                }
            }
        }
    }

    // Parallax efecto en hero grid
    const heroGrid = document.querySelector('.hero-grid');
    window.addEventListener('scroll', () => {
        if (heroGrid) {
            const scrolled = window.pageYOffset;
            heroGrid.style.transform = `perspective(1000px) rotateX(60deg) scale(2.5) translateY(${scrolled * 0.1}px)`;
        }
    });
});

// Animación de aparición de cubos
@keyframes appearCube {
    from {
        opacity: 0;
        transform: translate3d(var(--x), var(--y), var(--z)) scale(0);
    }
    to {
        opacity: 1;
        transform: translate3d(var(--x), var(--y), var(--z)) scale(1);
    }
}