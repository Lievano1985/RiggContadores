<script>
    (() => {
        const applyTheme = (theme) => {
            document.documentElement.dataset.theme = theme;
            document.documentElement.classList.toggle('dark', theme === 'dark');
            localStorage.setItem('app-theme', theme);
            localStorage.setItem('flux.appearance', theme === 'rigg' ? 'light' : theme);

            if (window.Flux?.applyAppearance) {
                window.Flux.applyAppearance(theme === 'rigg' ? 'light' : theme);
            }

            window.dispatchEvent(new CustomEvent('app-theme-changed', { detail: { theme } }));
        };

        const initializeThemeSupport = () => {
            const currentTheme = document.documentElement.dataset.theme || localStorage.getItem('app-theme') || 'dark';
            window.AppTheme = { apply: applyTheme };
            applyTheme(currentTheme);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeThemeSupport, { once: true });
        } else {
            initializeThemeSupport();
        }
    })();
</script>

<style>
    button[type="submit"]:not(.no-prefix-icon) {
        background-color: #78350f !important;
        color: #ffffff !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
    }

    button[type="submit"]:not(.no-prefix-icon):hover:not(:disabled) {
        background-color: #92400e !important;
    }

    button[type="submit"]:not(.no-prefix-icon):disabled {
        background-color: #b45309 !important;
        color: rgba(255, 255, 255, 0.8) !important;
    }
</style>
