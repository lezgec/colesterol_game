const form = document.getElementById("register-form");
const message = document.getElementById("register-message");
const passwordInput = document.getElementById("password");
const passwordConfirmationInput = document.getElementById("password_confirmation");

bindPasswordPolicy("password", "password-policy-hint");

function updatePasswordConfirmationState() {
    if (!passwordInput || !passwordConfirmationInput) {
        return;
    }

    const password = passwordInput.value;
    const confirmation = passwordConfirmationInput.value;

    passwordConfirmationInput.classList.remove(
        "password-confirm-valid",
        "password-confirm-invalid"
    );

    if (confirmation === "") {
        return;
    }

    passwordConfirmationInput.classList.add(
        password === confirmation
            ? "password-confirm-valid"
            : "password-confirm-invalid"
    );
}

passwordInput?.addEventListener("input", updatePasswordConfirmationState);
passwordConfirmationInput?.addEventListener("input", updatePasswordConfirmationState);

form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const first_name = document.getElementById("first_name").value.trim();
    const last_name = document.getElementById("last_name").value.trim();
    const name = `${first_name} ${last_name}`.replace(/\s+/g, " ").trim();
    const email = document.getElementById("email").value.trim();
    const password = passwordInput.value;
    const password_confirmation = passwordConfirmationInput.value;
    const role = document.querySelector("input[name='role']:checked")?.value || "player";
    const avatar_key =
        document.querySelector("input[name='avatar_key']:checked")?.value || "pulse";
    const avatar_file = document.getElementById("avatar_file")?.files?.[0] || null;
    const country = document.getElementById("country")?.value.trim() || "";
    const city = document.getElementById("city")?.value.trim() || "";
    const institution = document.getElementById("institution")?.value.trim() || "";
    const occupation = document.getElementById("occupation")?.value.trim() || "";
    const age = document.getElementById("age")?.value.trim() || "";
    const career = document.getElementById("career")?.value.trim() || "";
    const education_level = document.getElementById("education_level")?.value.trim() || "";

    if (!first_name || !last_name) {
        message.textContent = REGISTER_I18N.missingName;
        return;
    }

    if (!isPasswordPolicyValid(password)) {
        message.textContent = REGISTER_I18N.passwordPolicy;
        return;
    }

    if (password !== password_confirmation) {
        updatePasswordConfirmationState();
        message.textContent = REGISTER_I18N.passwordMismatch;
        return;
    }

    message.textContent = REGISTER_I18N.loading;

    try {
        const payload = new FormData();
        payload.append("name", name);
        payload.append("first_name", first_name);
        payload.append("last_name", last_name);
        payload.append("email", email);
        payload.append("password", password);
        payload.append("password_confirmation", password_confirmation);
        payload.append("role", role);
        payload.append("avatar_key", avatar_file ? "custom" : avatar_key);
        payload.append("country", country);
        payload.append("city", city);
        payload.append("institution", institution);
        payload.append("occupation", occupation);
        payload.append("age", age);
        payload.append("career", career);
        payload.append("education_level", education_level);

        if (avatar_file) {
            payload.append("avatar_file", avatar_file);
        }

        const response = await fetch(appUrl("backend/users/register_user.php"), {
            method: "POST",
            headers: csrfHeaders(),
            body: payload
        });

        const result = await response.json();

        if (result.success) {
            message.textContent = REGISTER_I18N.success;

            setTimeout(() => {
                window.location.href =
                    result.redirect || appUrl("pages/player_dashboard.php");
            }, 700);

            return;
        }

        message.textContent =
            result.message || REGISTER_I18N.failed;
    } catch (error) {
        console.error(error);
        message.textContent = REGISTER_I18N.connectionError;
    }
});
