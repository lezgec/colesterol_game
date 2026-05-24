const form = document.getElementById("register-form");
const message = document.getElementById("register-message");

form.addEventListener("submit", async function (e) {

    e.preventDefault();

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    message.textContent = "Registrando usuario...";

    try {

        const response = await fetch("/colesterol_game/backend/users/register_user.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name,
                email,
                password
            })
        });

        const result = await response.json();

        console.log("REGISTER RESULT:", result);

        if (result.success) {

            message.textContent = "✅ Registro exitoso";

            setTimeout(() => {

                window.location.href =
                    result.redirect || "/colesterol_game/pages/player_dashboard.php";

            }, 700);

        } else {

            message.textContent =
                "❌ " + (result.message || "No se pudo registrar el usuario");

        }

    } catch (error) {

        console.error(error);

        message.textContent = "❌ Error al registrar usuario";

    }

});