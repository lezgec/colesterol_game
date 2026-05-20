<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /colesterol_game/pages/login.php");
    exit;
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial</title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">
    <h1>📊 Historial de partidas</h1>

    <table border="1" width="100%" id="historyTable">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Puntaje</th>
                <th>Correctas</th>
                <th>Total</th>
                <th>Vidas</th>
                <th>Dificultad</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <br>
    <a href="/colesterol_game/pages/game.php">Volver al juego</a>
</div>

<script>
fetch("/colesterol_game/backend/get_user_results.php")
.then(res => res.json())
.then(data => {
    const tbody = document.querySelector("#historyTable tbody");

    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = "<tr><td colspan='6'>No hay partidas registradas</td></tr>";
        return;
    }

    data.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.played_at}</td>
            <td>${item.score}</td>
            <td>${item.correct_answers}</td>
            <td>${item.total_questions}</td>
            <td>${item.lives_remaining}</td>
            <td>${item.difficulty}</td>
        `;

        tbody.appendChild(row);
    });
});
</script>

</body>
</html>