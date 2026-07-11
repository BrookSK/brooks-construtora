/**
 * Brooks Construtora - Main JS
 * Handles: header scroll, mobile menu, reveals, counters, chat widget
 */

(function () {
    'use strict';

    // === HEADER SCROLL EFFECT ===
    const header = document.getElementById('site-header');
    let lastScroll = 0;

    function handleHeaderScroll() {
        const currentScroll = window.scrollY;
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleHeaderScroll, { passive: true });

    // === MOBILE MENU ===
    const mobileToggle = document.getElementById('mobile-toggle');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const mobileClose = document.getElementById('mobile-close');

    function openMobileMenu() {
        mobileNav.classList.add('active');
        mobileOverlay.classList.add('active');
        mobileToggle.classList.add('active');
        mobileToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileNav.classList.remove('active');
        mobileOverlay.classList.remove('active');
        mobileToggle.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

    // === SCROLL REVEAL ===
    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');

    const revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(function (el) {
        revealObserver.observe(el);
    });

    // === COUNTER ANIMATION ===
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'), 10);
        const suffix = element.getAttribute('data-suffix') || '';
        const prefix = element.getAttribute('data-prefix') || '';
        const duration = 2000;
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(target * eased);
            element.textContent = prefix + current.toLocaleString('pt-BR') + suffix;
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }

        requestAnimationFrame(update);
    }

    const counterElements = document.querySelectorAll('[data-counter]');
    const counterObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counterElements.forEach(function (el) {
        counterObserver.observe(el);
    });

    // === CHAT WIDGET ===
    const chatToggle = document.getElementById('chat-toggle');
    const chatWidget = document.getElementById('chat-widget');
    const chatClose = document.getElementById('chat-close');

    if (chatToggle && chatWidget) {
        chatToggle.addEventListener('click', function () {
            chatWidget.classList.toggle('active');
            chatWidget.setAttribute('aria-hidden', !chatWidget.classList.contains('active'));
        });
    }

    if (chatClose && chatWidget) {
        chatClose.addEventListener('click', function () {
            chatWidget.classList.remove('active');
            chatWidget.setAttribute('aria-hidden', 'true');
        });
    }

    // === BACK TO TOP ===
    const backToTop = document.getElementById('back-to-top');

    if (backToTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 600) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }, { passive: true });

        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // === SMOOTH SCROLL FOR ANCHOR LINKS ===
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // === PARALLAX LIGHT ===
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    if (parallaxElements.length > 0) {
        window.addEventListener('scroll', function () {
            const scrolled = window.scrollY;
            parallaxElements.forEach(function (el) {
                const speed = parseFloat(el.getAttribute('data-parallax')) || 0.3;
                const rect = el.getBoundingClientRect();
                const offset = (rect.top + scrolled) * speed;
                el.style.transform = 'translateY(' + (scrolled - offset) * 0.15 + 'px)';
            });
        }, { passive: true });
    }

    // === NEWSLETTER FORM (AJAX) ===
    const newsletterForms = document.querySelectorAll('#footer-newsletter-form, .newsletter-form-ajax');
    newsletterForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            var btn = this.querySelector('button[type="submit"]');
            var originalText = btn.textContent;
            btn.textContent = 'Enviando...';
            btn.disabled = true;

            fetch('/newsletter/subscribe', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.textContent = d.success ? '✓ Inscrito!' : 'Erro';
                if (d.success) form.reset();
                setTimeout(function () {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 3000);
            })
            .catch(function () {
                btn.textContent = 'Erro';
                setTimeout(function () {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 3000);
            });
        });
    });

    // === LAZY LOAD IMAGES ===
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    if (img.getAttribute('data-srcset')) {
                        img.srcset = img.getAttribute('data-srcset');
                    }
                    img.removeAttribute('data-src');
                    img.removeAttribute('data-srcset');
                    imageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });

        lazyImages.forEach(function (img) {
            imageObserver.observe(img);
        });
    }

})();
