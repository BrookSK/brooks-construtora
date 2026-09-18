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

    // === MEGA MENU ===
    var megaItem = document.querySelector('.header-nav__item--mega');
    var megaMenu = megaItem ? megaItem.querySelector('.mega-menu') : null;
    var megaCloseTimer = null;

    function openMega() {
        clearTimeout(megaCloseTimer);
        if (megaItem) megaItem.classList.add('mega-open');
    }

    function closeMegaDelayed() {
        megaCloseTimer = setTimeout(function () {
            if (megaItem) megaItem.classList.remove('mega-open');
        }, 250);
    }

    if (megaItem) {
        megaItem.addEventListener('mouseenter', openMega);
        megaItem.addEventListener('mouseleave', closeMegaDelayed);
    }

    if (megaMenu) {
        megaMenu.addEventListener('mouseenter', openMega);
        megaMenu.addEventListener('mouseleave', closeMegaDelayed);
    }

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

    // FAQ question buttons
    var chatBody = document.getElementById('chat-body');
    var chatQuestions = document.querySelectorAll('.chat-widget__question-btn');
    
    chatQuestions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var question = this.textContent;
            var answer = this.getAttribute('data-answer');
            
            var questionsContainer = document.getElementById('chat-questions');
            
            // Show question as sent message (before the questions container)
            var questionEl = document.createElement('div');
            questionEl.className = 'chat-widget__question-sent';
            questionEl.textContent = question;
            chatBody.insertBefore(questionEl, questionsContainer);
            
            // Show answer (before the questions container)
            var answerEl = document.createElement('div');
            answerEl.className = 'chat-widget__answer';
            answerEl.textContent = answer;
            chatBody.insertBefore(answerEl, questionsContainer);
            
            // Scroll so the sent question is at the top of the visible area
            setTimeout(function() {
                questionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        });
    });

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
        // Mostra uma mensagem amigável abaixo do formulário (sucesso ou aviso),
        // usando o texto que o servidor retorna (ex.: "Este e-mail já está
        // inscrito."), em vez de só escrever "Erro" no botão.
        function showMsg(text, ok) {
            var box = form.querySelector('.newsletter-msg');
            if (!box) {
                box = document.createElement('div');
                box.className = 'newsletter-msg';
                box.style.cssText = 'width:100%;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.4;text-align:center;';
                form.appendChild(box);
            }
            box.textContent = text;
            box.style.background = ok ? 'rgba(40,167,69,0.15)' : 'rgba(230,57,70,0.15)';
            box.style.color = ok ? '#28a745' : '#e63946';
            box.style.border = '1px solid ' + (ok ? 'rgba(40,167,69,0.4)' : 'rgba(230,57,70,0.4)');
        }

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
                var msg = (d && d.message)
                    ? d.message
                    : (d && d.success ? 'Inscrição realizada com sucesso!' : 'Não foi possível concluir. Tente novamente.');
                showMsg(msg, !!(d && d.success));
                if (d && d.success) form.reset();
                btn.textContent = originalText;
                btn.disabled = false;
            })
            .catch(function () {
                showMsg('Erro de conexão. Tente novamente em instantes.', false);
                btn.textContent = originalText;
                btn.disabled = false;
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
