// hero-cubes.js
document.addEventListener('DOMContentLoaded', function() {
    const cubeGrid = document.querySelector('.cube-grid');
    const gridSize = 3; // 3x3x3 grid
    const spacing = 80; // Espacio entre cubos

    function createCube(x, y, z) {
        const cube = document.createElement('div');
        cube.className = 'cube';
        cube.style.transform = `translate3d(${x * spacing}px, ${y * spacing}px, ${z * spacing}px)`;

        // Crear las caras del cubo
        const faces = ['front', 'back', 'right', 'left', 'top', 'bottom'];
        faces.forEach(face => {
            const cubeFace = document.createElement('div');
            cubeFace.className = `cube-face ${face}`;
            cube.appendChild(cubeFace);
        });

        return cube;
    }

    // Generar cubos en un patrón alternado
    for (let x = 0; x < gridSize; x++) {
        for (let y = 0; y < gridSize; y++) {
            for (let z = 0; z < gridSize; z++) {
                if ((x + y + z) % 2 === 0) { // Patrón alternado
                    const cube = createCube(x, y, z);
                    cubeGrid.appendChild(cube);
                }
            }
        }
    }

    // Animación en scroll
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        cubeGrid.style.transform = `perspective(1000px) rotateX(${30 + scrolled * 0.02}deg) rotateZ(${45 + scrolled * 0.02}deg)`;
    });

    // Animación al mover el mouse
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth - 0.5;
        const mouseY = e.clientY / window.innerHeight - 0.5;
        
        cubeGrid.style.transform = `
            perspective(1000px) 
            rotateX(${30 + mouseY * 10}deg) 
            rotateZ(${45 + mouseX * 10}deg)
        `;
    });
});