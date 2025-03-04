// why-us.js
document.addEventListener('DOMContentLoaded', function() {
    // Efecto de parallax en la imagen
    const whyUsImage = document.querySelector('.why-us-image');
    const experienceBadge = document.querySelector('.experience-badge');

    window.addEventListener('scroll', () => {
        if (whyUsImage) {
            const scrolled = window.pageYOffset;
            const rate = scrolled * 0.05;
            whyUsImage.style.transform = `translateY(${rate}px)`;
            
            if (experienceBadge) {
                experienceBadge.style.transform = `rotate(${rate}deg)`;
            }
        }
    });

    // Efecto de hover 3D en las tarjetas
    const featureCards = document.querySelectorAll('.feature-card');
    
    featureCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        });
    });

    // Animación de los íconos
    const featureIcons = document.querySelectorAll('.feature-icon');
    
    const animateIcon = (icon) => {
        icon.style.transform = 'scale(1.2)';
        setTimeout(() => {
            icon.style.transform = 'scale(1)';
        }, 200);
    };

    featureIcons.forEach(icon => {
        setInterval(() => {
            animateIcon(icon);
        }, 3000 + Math.random() * 2000); // Animación aleatoria cada 3-5 segundos
    });

    // Efecto de desplazamiento suave para el badge
    const updateBadgePosition = () => {
        if (experienceBadge) {
            const scrolled = window.pageYOffset;
            const rate = Math.sin(scrolled * 0.002) * 20;
            experienceBadge.style.transform = `translateY(${rate}px)`;
        }
    };

    window.addEventListener('scroll', updateBadgePosition);
});