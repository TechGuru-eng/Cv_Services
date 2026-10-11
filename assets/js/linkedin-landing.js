(() => {
    'use strict';
    function placeLanguageControl() {
        const slot = document.querySelector('.li-language-slot');
        const control = document.querySelector('.site-language');
        if (slot && control) slot.appendChild(control);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', placeLanguageControl, { once: true });
    else placeLanguageControl();
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const targets = document.querySelectorAll('.li-hero-copy,.li-editorial-copy,.li-section-heading,.li-services article,.li-process li,.li-package,.li-faq-list details,.li-comparison,.li-final');
    if (!reduced && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('li-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.06 });
        targets.forEach((element, index) => {
            element.classList.add('li-reveal');
            element.style.setProperty('--reveal-delay', `${index % 3 * 65}ms`);
            observer.observe(element);
        });
    }
    if (!reduced && matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.querySelectorAll('[data-tilt]').forEach(panel => {
            let frame;
            panel.addEventListener('pointermove', event => {
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => {
                    const box = panel.getBoundingClientRect();
                    panel.style.setProperty('--tilt-x', `${(event.clientY - box.top - box.height / 2) / box.height * -5}deg`);
                    panel.style.setProperty('--tilt-y', `${(event.clientX - box.left - box.width / 2) / box.width * 5}deg`);
                });
            });
            panel.addEventListener('pointerleave', () => {
                cancelAnimationFrame(frame);
                panel.style.setProperty('--tilt-x', '0deg');
                panel.style.setProperty('--tilt-y', '0deg');
            });
        });
    }
})();
