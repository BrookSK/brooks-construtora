/**
 * Flatsome Theme JS - Simplified version for Brooks Construtora
 * Implements essential functionality: slider, mobile menu, accordion, back-to-top, bg-loaded
 */

(function($) {
    'use strict';

    // Trigger bg-loaded class for background images
    function loadBackgrounds() {
        document.querySelectorAll('.bg.section-bg').forEach(function(el) {
            el.classList.add('bg-loaded');
        });
    }

    // Accordion
    function initAccordion() {
        document.querySelectorAll('.accordion-title').forEach(function(title) {
            title.addEventListener('click', function(e) {
                e.preventDefault();
                var item = this.closest('.accordion-item');
                var wasActive = item.classList.contains('active');

                // Close all siblings
                var parent = item.parentElement;
                parent.querySelectorAll('.accordion-item').forEach(function(sibling) {
                    sibling.classList.remove('active');
                });

                // Toggle current
                if (!wasActive) {
                    item.classList.add('active');
                }
            });
        });
    }

    // Back to top
    function initBackToTop() {
        var btn = document.getElementById('top-link');
        if (!btn) return;

        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        });

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Mobile menu
    function initMobileMenu() {
        var toggles = document.querySelectorAll('[data-open="#main-menu"]');
        var menu = document.getElementById('main-menu');
        if (!menu) return;

        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('active');
                document.body.classList.toggle('menu-open');
            });
        });

        // Close on overlay click
        document.addEventListener('click', function(e) {
            if (menu.classList.contains('active') && !menu.contains(e.target) && !e.target.closest('[data-open="#main-menu"]')) {
                menu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }

    // Simple slider (Flickity-like behavior with CSS)
    function initSlider() {
        var sliders = document.querySelectorAll('.slider');
        sliders.forEach(function(slider) {
            var slides = slider.querySelectorAll('.section');
            if (slides.length <= 1) return;

            var current = 0;
            var total = slides.length;

            // Hide all except first
            slides.forEach(function(slide, i) {
                slide.style.display = i === 0 ? 'block' : 'none';
            });

            // Auto-play
            var optionsStr = slider.getAttribute('data-flickity-options');
            var autoPlay = 3000;
            if (optionsStr) {
                try {
                    var options = JSON.parse(optionsStr);
                    autoPlay = options.autoPlay || 3000;
                } catch(e) {}
            }

            setInterval(function() {
                slides[current].style.display = 'none';
                current = (current + 1) % total;
                slides[current].style.display = 'block';
            }, autoPlay);
        });
    }

    // Testimonial slider (row-slider)
    function initRowSlider() {
        var rowSliders = document.querySelectorAll('.row-slider');
        rowSliders.forEach(function(slider) {
            // Basic scroll behavior - let CSS handle the layout
            slider.style.overflowX = 'auto';
            slider.style.flexWrap = 'nowrap';
            slider.style.scrollBehavior = 'smooth';
            slider.style.scrollSnapType = 'x mandatory';
            
            var cols = slider.querySelectorAll('.col');
            cols.forEach(function(col) {
                col.style.scrollSnapAlign = 'start';
                col.style.flex = '0 0 auto';
            });
        });
    }

    // Newsletter form AJAX
    function initNewsletter() {
        var forms = document.querySelectorAll('.newsletter-form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var thisForm = this;

                fetch('/newsletter/subscribe', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var msg = data.message || (data.success ? 'Inscrito com sucesso!' : 'Este e-mail já está inscrito.');
                    alert(msg);
                    if (data.success) thisForm.reset();
                })
                .catch(function() {
                    alert('Erro ao processar. Tente novamente.');
                });
            });
        });
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        loadBackgrounds();
        initAccordion();
        initBackToTop();
        initMobileMenu();
        initSlider();
        initRowSlider();
        initNewsletter();
    });

})(window.jQuery || { fn: {} });
