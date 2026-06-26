(function () {
    const requirements = [
        {
            id: "length",
            label: "Mínimo 10 caracteres",
            test: password => password.length >= 10
        },
        {
            id: "lowercase",
            label: "Una minúscula",
            test: password => /[a-z]/.test(password)
        },
        {
            id: "uppercase",
            label: "Una mayúscula",
            test: password => /[A-Z]/.test(password)
        },
        {
            id: "number",
            label: "Un número",
            test: password => /\d/.test(password)
        },
        {
            id: "symbol",
            label: "Un símbolo",
            test: password => /[^a-zA-Z\d]/.test(password)
        }
    ];

    window.getPasswordPolicyErrors = function (password) {
        return requirements
            .filter(requirement => !requirement.test(password))
            .map(requirement => requirement.label.toLowerCase());
    };

    window.isPasswordPolicyValid = function (password) {
        return window.getPasswordPolicyErrors(password).length === 0;
    };

    window.bindPasswordPolicy = function (inputId, hintId) {
        const input = document.getElementById(inputId);
        const hint = document.getElementById(hintId);

        if (!input || !hint) {
            return;
        }

        hint.classList.add("password-policy-list");
        hint.innerHTML = requirements
            .map(requirement => `
                <li data-requirement="${requirement.id}">
                    <span class="password-policy-check" aria-hidden="true">&#10003;</span>
                    <span>${requirement.label}</span>
                </li>
            `)
            .join("");

        const render = () => {
            let passed = 0;

            requirements.forEach(requirement => {
                const item = hint.querySelector(`[data-requirement="${requirement.id}"]`);
                const isMet = requirement.test(input.value);

                if (!item) {
                    return;
                }

                item.classList.toggle("is-met", isMet);

                if (isMet) {
                    passed++;
                }
            });

            hint.classList.toggle("is-valid", passed === requirements.length);
        };

        input.addEventListener("input", render);
        render();
    };
})();
