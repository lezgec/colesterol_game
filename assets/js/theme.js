(function () {
    const storageKey = "colesterol_game_theme";
    const root = document.documentElement;

    function getInitialTheme() {
        const saved = localStorage.getItem(storageKey);

        if (saved === "light" || saved === "dark") {
            return saved;
        }

        return "dark";
    }

    function applyTheme(theme) {
        root.setAttribute("data-theme", theme);
        localStorage.setItem(storageKey, theme);

        document.querySelectorAll(".theme-toggle").forEach(button => {
            const isLight = theme === "light";
            button.classList.toggle("is-light", isLight);
            button.setAttribute(
                "aria-label",
                isLight ? "Cambiar a modo oscuro" : "Cambiar a modo claro"
            );
            button.setAttribute("title", isLight ? "Modo oscuro" : "Modo claro");
        });
    }

    function createToggle() {
        if (document.querySelector(".theme-toggle")) {
            return;
        }

        const button = document.createElement("button");
        button.type = "button";
        button.className = "theme-toggle";
        button.innerHTML = `
            <span class="theme-toggle-track" aria-hidden="true">
                <span class="theme-toggle-icon theme-toggle-dark">
                    <svg class="ui-icon theme-toggle-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 14.5A8 8 0 0 1 9.5 4 7 7 0 1 0 20 14.5Z"/></svg>
                </span>
                <span class="theme-toggle-icon theme-toggle-light">
                    <svg class="ui-icon theme-toggle-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/></svg>
                </span>
                <span class="theme-toggle-thumb"></span>
            </span>
        `;

        button.addEventListener("click", () => {
            const current = root.getAttribute("data-theme") || "dark";
            applyTheme(current === "dark" ? "light" : "dark");
        });

        const topActions = document.querySelector(".top-actions");
        const topLinks = document.querySelector(".top-links");
        const landingTopbar = document.querySelector(".landing-topbar");
        const languagePill = document.querySelector(".language-pill");

        if (topLinks) {
            topLinks.prepend(button);
        } else if (landingTopbar) {
            landingTopbar.appendChild(button);
        } else if (topActions) {
            topActions.appendChild(button);
        } else if (languagePill && languagePill.parentElement) {
            languagePill.parentElement.appendChild(button);
        } else {
            button.classList.add("theme-toggle-fixed");
            document.body.appendChild(button);
        }

        applyTheme(root.getAttribute("data-theme") || getInitialTheme());
    }

    applyTheme(getInitialTheme());

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", createToggle);
    } else {
        createToggle();
    }
})();
