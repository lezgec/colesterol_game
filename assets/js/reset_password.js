const resetPasswordForm = document.getElementById("reset-password-form");
const resetPasswordMessage = document.getElementById("reset-password-message");

bindPasswordPolicy("password", "password-policy-hint");

if (resetPasswordForm) {
    resetPasswordForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const token = document.getElementById("token").value;
        const password = document.getElementById("password").value;
        const passwordConfirm = document.getElementById("password-confirm").value;

        if (!isPasswordPolicyValid(password)) {
            resetPasswordMessage.textContent = "La contraseña debe tener mínimo 10 caracteres e incluir mayúscula, minúscula, número y símbolo.";
            return;
        }

        if (password !== passwordConfirm) {
            resetPasswordMessage.textContent = "Las contraseñas no coinciden.";
            return;
        }

        resetPasswordMessage.textContent = "Actualizando contraseña...";

        try {
            const response = await fetch(appUrl("backend/users/reset_password.php"), {
                method: "POST",
                headers: csrfHeaders({
                    "Content-Type": "application/json"
                }),
                body: JSON.stringify({
                    token,
                    password,
                    password_confirm: passwordConfirm
                })
            });

            const result = await response.json();
            resetPasswordMessage.textContent = result.message || "Solicitud procesada.";

            if (result.success) {
                resetPasswordForm.reset();

                setTimeout(() => {
                    window.location.href = appUrl("pages/login.php");
                }, 1200);
            }
        } catch (error) {
            console.error(error);
            resetPasswordMessage.textContent = "Error de conexión.";
        }
    });
}
