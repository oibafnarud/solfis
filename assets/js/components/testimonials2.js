// testimonials.js
document.addEventListener('DOMContentLoaded', function() {
    const slider = {
        container: document.querySelector('.slider-container'),
        slides: document.querySelectorAll('.testimonial-card'),
        dots: document.querySelectorAll('.dot'),
        prevBtn: document.querySelector('.slider-btn.prev'),
        nextBtn: document.querySelector('.slider-btn.next'),
        currentSlide: 0,
        interval: null,
        autoplayDelay: 5000, // 5 segundos entre slides

        init() {
            this.setupEventListeners();
            this.startAutoplay();
            this.updateSlider();
        },

        setupEventListeners() {
            this.prevBtn?.addEventListener('click', () => this.prevSlide());
            this.nextBtn?.addEventListener('click', () => this.nextSlide());
            
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });

            // Pausar autoplay al hover
            this.container?.addEventListener('mouseenter', () => this.stopAutoplay());
            this.container?.addEventListener('mouseleave', () => this.startAutoplay());

            // Soporte para swipe en móviles
            let touchStartX = 0;
            let touchEndX = 0;

            this.container?.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            });

            this.container?.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].clientX;
                this.handleSwipe();
            });

            this.handleSwipe = () => {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        this.nextSlide();
                    } else {
                        this.prevSlide();
                    }
                }
            };
        },

        updateSlider() {
            // Actualizar slides
            this.slides.forEach((slide, index) => {
                slide.classList.remove('active');
                if (index === this.currentSlide) {
                    slide.classList.add('active');
                }
            });

            // Actualizar dots
            this.dots.forEach((dot, index) => {
                dot.classList.remove('active');
                if (index === this.currentSlide) {
                    dot.classList.add('active');
                }
            });

            // Animar el slide actual
            const activeSlide = this.slides[this.currentSlide];
            activeSlide.style.animation = 'none';
            activeSlide.offsetHeight; // Trigger reflow
            activeSlide.style.animation = 'fadeSlide 0.6s ease forwards';
        },

        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
            this.updateSlider();
        },

        prevSlide() {
            this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
            this.updateSlider();
        },

        goToSlide(index) {
            this.currentSlide = index;
            this.updateSlider();
        },

        startAutoplay() {
            this.interval = setInterval(() => this.nextSlide(), this.autoplayDelay);
        },

        stopAutoplay() {
            clearInterval(this.interval);
        }
    };

    // Inicializar slider
    slider.init();

    // Animación de logos de clientes
    const clientLogos = document.querySelectorAll('.client-logo');
    
    const animateLogos = () => {
        clientLogos.forEach((logo, index) => {
            setTimeout(() => {
                logo.style.opacity = '1';
                logo.style.transform = 'translateY(0)';
            }, index * 200);
        });
    };

    // Observer para logos
    const logoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateLogos();
                logoObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    const logosContainer = document.querySelector('.client-logos');
    if (logosContainer) {
        logoObserver.observe(logosContainer);
    }
});