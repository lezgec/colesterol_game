const forgotPasswordForm = document.getElementById("forgot-password-form");
const forgotPasswordMessage = document.getElementById("forgot-password-message");

if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const email = document.getElementById("email").value.trim();
        forgotPasswordMessage.textContent = "Enviando solicitud...";

        try {
            const response = await fetch(appUrl("backend/users/request_password_reset.php"), {
                method: "POST",
                headers: csrfHeaders({
                    "Content-Type": "application/json"
                }),
                body: JSON.stringify({ email })
            });

            const result = await response.json();
            forgotPasswordMessage.textContent = result.message || "Solicitud procesada.";
        } catch (error) {
            console.error(error);
            forgotPasswordMessage.textContent = "Error de conexión.";
        }
    });
}
