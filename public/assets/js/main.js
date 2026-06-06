/**
 * Brooks Construtora - Main JS
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('show');
        });
    }

    // Header scroll effect
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Newsletter form AJAX
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageEl = document.getElementById('newsletter-message');

            fetch('/newsletter/subscribe', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    messageEl.innerHTML = '<span class="text-success">' + data.message + '</span>';
                    newsletterForm.reset();
                } else {
                    messageEl.innerHTML = '<span class="text-warning">' + data.message + '</span>';
                }
                setTimeout(() => { messageEl.innerHTML = ''; }, 5000);
            })
            .catch(() => {
                messageEl.innerHTML = '<span class="text-danger">Erro ao processar. Tente novamente.</span>';
            });
        });
    }
});
