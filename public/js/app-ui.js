(() => {
    document.documentElement.classList.add('js-enabled');

    const reveal = () => {
        const nodes = document.querySelectorAll('.reveal');
        if (!nodes.length) return;
        if (!('IntersectionObserver' in window)) {
            nodes.forEach(node => node.classList.add('is-visible'));
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0, rootMargin: '0px 0px -20px 0px' });
        nodes.forEach(node => observer.observe(node));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', reveal, { once: true });
    } else {
        reveal();
    }
})();
