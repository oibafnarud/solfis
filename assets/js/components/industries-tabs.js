// industries-tabs.js
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    initTabs();
    
    // Animate counters
    initCounters();
    
    // Parallax effect on industry images
    initParallax();
});

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    if (tabButtons.length === 0 || tabPanels.length === 0) return;
    
    // Set initial active tab
    if (!document.querySelector('.tab-btn.active')) {
        tabButtons[0].classList.add('active');
        const targetId = tabButtons[0].dataset.target;
        document.getElementById(targetId)?.classList.add('active');
    }
    
    // Add click event to each tab button
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and panels
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Show corresponding panel
            const targetId = button.dataset.target;
            const targetPanel = document.getElementById(targetId);
            
            if (targetPanel) {
                targetPanel.classList.add('active');
                
                // Restart counter animations in this panel
                const counters = targetPanel.querySelectorAll('.counter');
                animateCounters(counters);
            }
        });
    });
}

function initCounters() {
    // Find all counters in the document
    const allCounters = document.querySelectorAll('.counter');
    
    // IntersectionObserver to start counter animation when visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counters = entry.target.closest('.tab-panel')?.querySelectorAll('.counter') || 
                                 [entry.target]; // If not in a tab panel, just animate this counter
                
                animateCounters(counters);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    // Observe all counters
    allCounters.forEach(counter => {
        observer.observe(counter);
    });
    
    // Also observe the first tab panel to trigger initial counters
    const firstPanel = document.querySelector('.tab-panel.active');
    if (firstPanel) {
        const firstCounter = firstPanel.querySelector('.counter');
        if (firstCounter) {
            observer.observe(firstCounter);
        }
    }
    
    // Also handle stats section counters (outside tabs)
    const statsCounters = document.querySelectorAll('.stats .counter');
    statsCounters.forEach(counter => {
        observer.observe(counter);
    });
}

function animateCounters(counters) {
    counters.forEach(counter => {
        // Reset counter to zero
        counter.textContent = '0';
        
        // Get target value from data attribute
        const target = parseInt(counter.getAttribute('data-target'));
        if (isNaN(target)) return;
        
        // Animation variables
        const duration = 2000; // 2 seconds
        const frameDuration = 1000 / 60; // 60fps
        const totalFrames = Math.round(duration / frameDuration);
        let frame = 0;
        
        // Easing function for smooth animation
        const easeOutQuad = t => t * (2 - t);
        
        // Animate the counter
        const animate = () => {
            frame++;
            
            // Calculate current count
            const progress = easeOutQuad(Math.min(frame / totalFrames, 1));
            const currentCount = Math.round(progress * target);
            
            // Update counter text
            counter.textContent = currentCount;
            
            // Continue animation until done
            if (frame < totalFrames) {
                requestAnimationFrame(animate);
            } else {
                // Ensure final value is exactly the target
                counter.textContent = target;
            }
        };
        
        // Start animation
        requestAnimationFrame(animate);
    });
}

function initParallax() {
    // Parallax effect on panel images when scrolling
    const panelImages = document.querySelectorAll('.panel-image img');
    
    window.addEventListener('scroll', () => {
        panelImages.forEach(image => {
            const scrollPosition = window.pageYOffset;
            const imagePosition = image.getBoundingClientRect().top + scrollPosition;
            const offset = (scrollPosition - imagePosition) * 0.1;
            
            // Apply parallax effect only when the image is in viewport
            if (Math.abs(window.innerHeight - image.getBoundingClientRect().top) < window.innerHeight * 1.5) {
                image.style.transform = `translateY(${offset}px)`;
            }
        });
    });
    
    // Reset transforms when leaving the section
    const industriesSection = document.querySelector('.industries');
    if (industriesSection) {
        document.addEventListener('scroll', () => {
            const rect = industriesSection.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                panelImages.forEach(image => {
                    image.style.transform = '';
                });
            }
        });
    }
}