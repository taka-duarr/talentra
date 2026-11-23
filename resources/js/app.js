/**
 * Animated counter used on dashboard numbers: <span x-data="countUp(412)" x-text="display"></span>
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('countUp', (target = 0, decimals = 0, duration = 1200) => ({
        display: (0).toFixed(decimals),
        init() {
            const value = Number(target) || 0;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const format = (number) => number.toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

            if (reduceMotion || value === 0) {
                this.display = format(value);
                return;
            }

            const start = performance.now();
            const step = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.display = format(value * eased);
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        },
    }));
});
