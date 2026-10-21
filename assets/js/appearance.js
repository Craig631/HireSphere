(() => {
    'use strict';

    // The HTML attribute controls Bootstrap's built-in color theme.
    const root = document.documentElement;
    const buttons = document.querySelectorAll('[data-hs-theme-toggle]');

    // Keep every theme button synchronized, including the Settings modal.
    function updateThemeControls() {
        const isDark = root.getAttribute('data-bs-theme') === 'dark';

        buttons.forEach((button) => {
            const icon = button.querySelector('[data-hs-theme-icon]');
            const label = button.querySelector('[data-hs-theme-label]');

            button.setAttribute('aria-pressed', String(isDark));

            if (icon) {
                icon.className = isDark
                    ? 'bi bi-sun me-2'
                    : 'bi bi-moon-stars me-2';
            }

            if (label) {
                label.textContent = isDark
                    ? 'Switch to light mode'
                    : 'Switch to dark mode';
            }
        });
    }

    // Toggle the theme without reloading or navigating away from the page.
    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = root.getAttribute('data-bs-theme') === 'dark'
                ? 'light'
                : 'dark';

            root.setAttribute('data-bs-theme', nextTheme);

            // Save the preference so it survives navigation and refreshes.
            try {
                localStorage.setItem('hiresphere-theme', nextTheme);
            } catch (error) {
                // The current page still changes theme if saving is unavailable.
            }

            updateThemeControls();
        });
    });

    // Display the correct labels for the theme loaded by the header.
    updateThemeControls();
})();
