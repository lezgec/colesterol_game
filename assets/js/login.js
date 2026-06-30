const form = document.getElementById("login-form");
const message = document.getElementById("login-message");

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    message.textContent = LOGIN_I18N.loading;

    try {

        const response = await fetch(appUrl("backend/users/login_user.php"), {
            method: "POST",
            headers: csrfHeaders({
                "Content-Type": "application/json"
            }),
            body: JSON.stringify({
                email,
                password
            })
        });

        const result = await response.json();

        if (result.success) {

            message.textContent = LOGIN_I18N.success;

            setTimeout(() => {
                window.location.href =
                    result.redirect || appUrl("pages/player_dashboard.php");
            }, 500);

        } else {

            message.textContent =
                result.message || LOGIN_I18N.failed;

        }

    } catch (error) {

        console.error(error);

        message.textContent = LOGIN_I18N.connectionError;

    }
});
