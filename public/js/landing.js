(() => {
    const header = document.querySelector('[data-header]');
    const menuButton = document.querySelector('[data-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');

    const updateHeader = () => {
        header?.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    const closeMenu = () => {
        menuButton?.setAttribute('aria-expanded', 'false');
        mobileMenu?.classList.remove('is-open');
    };

    menuButton?.addEventListener('click', () => {
        const willOpen = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(willOpen));
        mobileMenu?.classList.toggle('is-open', willOpen);
    });

    mobileMenu?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    window.addEventListener('scroll', updateHeader, { passive: true });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 920) closeMenu();
    });
    updateHeader();

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealItems = document.querySelectorAll('.reveal');

    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                currentObserver.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        revealItems.forEach((item) => observer.observe(item));
    }

    document.querySelectorAll('.faq-list details').forEach((detail) => {
        detail.addEventListener('toggle', () => {
            if (!detail.open) return;
            document.querySelectorAll('.faq-list details[open]').forEach((other) => {
                if (other !== detail) other.removeAttribute('open');
            });
        });
    });
})();
