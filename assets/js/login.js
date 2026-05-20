const form = document.getElementById("login-form");
const message = document.getElementById("login-message");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  message.textContent = "Iniciando sesión...";

  try {
    const response = await fetch("/colesterol_game/backend/users/login_user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ email, password })
    });

    const result = await response.json();

    if (result.success) {
    if (result.role === "super_admin" || result.role === "teacher") {
        window.location.href = "/colesterol_game/pages/admin_dashboard.php";
    } else {
        window.location.href = "/colesterol_game/pages/game.php";
    }
  }
  } catch (error) {
    console.error(error);
    message.textContent = "Error de conexión";
  }
});