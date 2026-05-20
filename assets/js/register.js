const form = document.getElementById("register-form");
const message = document.getElementById("register-message");

form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  message.textContent = "Registrando usuario...";

  try {
    const response = await fetch("/colesterol_game/backend/register_user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        name: name,
        email: email,
        password: password
      })
    });

    const result = await response.json();

    if (result.success) {
      message.textContent = "✅ Registro exitoso. Redirigiendo al juego...";
      setTimeout(() => {
        window.location.href = "/colesterol_game/pages/game.php";
      }, 1000);
    } else {
      message.textContent = "❌ " + result.message;
    }
  } catch (error) {
    console.error(error);
    message.textContent = "❌ Error al registrar usuario";
  }
});