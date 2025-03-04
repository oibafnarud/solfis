// testimonials.js
document.addEventListener('DOMContentLoaded', function() {
    const slider = {
        container: document.querySelector('.testimonials-slider'),
        slides: document.querySelectorAll('.testimonial-card'),
        dots: document.querySelectorAll('.dot'),
        prevBtn: document.querySelector('.slider-btn.prev'),
        nextBtn: document.querySelector('.slider-btn.next'),
        currentSlide: 0,
        interval: null,
        autoplayDelay: 5000, // 5 segundos entre slides

        init() {
            if (!this.container || this.slides.length === 0) return;
            
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
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        this.nextSlide();
                    } else {
                        this.prevSlide();
                    }
                }
            });
        },

        updateSlider() {
            this.slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === this.currentSlide);
                if (index === this.currentSlide) {
                    slide.style.animation = 'fadeIn 0.5s ease forwards';
                } else {
                    slide.style.animation = 'fadeOut 0.5s ease forwards';
                }
            });

            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentSlide);
            });
        },

        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
            this.updateSlider();
            this.resetAutoplay();
        },

        prevSlide() {
            this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
            this.updateSlider();
            this.resetAutoplay();
        },

        goToSlide(index) {
            this.currentSlide = index;
            this.updateSlider();
            this.resetAutoplay();
        },

        startAutoplay() {
            this.interval = setInterval(() => this.nextSlide(), this.autoplayDelay);
        },

        stopAutoplay() {
            clearInterval(this.interval);
        },

        resetAutoplay() {
            this.stopAutoplay();
            this.startAutoplay();
        }
    };

    // Inicializar slider
    slider.init();
});