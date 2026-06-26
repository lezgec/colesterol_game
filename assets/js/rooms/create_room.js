const form = document.getElementById("create-room-form");
const message = document.getElementById("create-room-message");
const languageSelect = document.getElementById("room_language");

function setRoomMessage(text = "", type = "") {
    message.textContent = text;
    message.classList.remove("is-info", "is-success", "is-error", "is-warning");

    if (type) {
        message.classList.add(`is-${type}`);
    }
}

form.addEventListener("submit", async event => {
    event.preventDefault();

    const name = document.getElementById("room_name").value.trim();
    const language = languageSelect.value;

    if (!name) {
        setRoomMessage(CREATE_ROOM_I18N.error, "warning");
        return;
    }

    setRoomMessage(CREATE_ROOM_I18N.loading, "info");

    try {
        const response = await fetch(appUrl("backend/rooms/create_room.php"), {
            method: "POST",
            headers: csrfHeaders({
                "Content-Type": "application/json"
            }),
            body: JSON.stringify({
                name,
                language
            })
        });

        const text = await response.text();
        let result;

        try {
            result = JSON.parse(text);
        } catch (error) {
            console.error("JSON parse error:", error, text);
            setRoomMessage("El servidor devolvio una respuesta invalida.", "error");
            return;
        }

        if (!result.success) {
            setRoomMessage(result.error || result.message || CREATE_ROOM_I18N.error, "error");
            return;
        }

        window.location.href =
            appUrl(`pages/rooms/lobby_admin.php?code=${encodeURIComponent(result.room_code)}`);
    } catch (error) {
        console.error("Create room request error:", error);
        setRoomMessage("Error del servidor. Revisa la consola.", "error");
    }
});
