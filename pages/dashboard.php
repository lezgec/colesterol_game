<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    header("Location: /colesterol_game/pages/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de desempeño</title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">
    <div style="text-align:right; margin-bottom:10px;">
        <a href="?lang=es">ES</a> |
        <a href="?lang=en">EN</a>
    </div>
    <div class="top-actions">
        <h1>📈 Dashboard de desempeño</h1>
        <div class="top-links">
            <a href="/colesterol_game/pages/game.php" class="logout-btn secondary-btn">Volver al juego</a>
            <a href="/colesterol_game/logout.php" class="logout-btn">Cerrar sesión</a>
        </div>
    </div>

    <p>Resumen general del rendimiento del usuario en el serious game.</p>

    <div id="dashboard-cards" class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Total de partidas</h3>
            <p id="total-games">...</p>
        </div>

        <div class="dashboard-card">
            <h3>Promedio de puntaje</h3>
            <p id="avg-score">...</p>
        </div>

        <div class="dashboard-card">
            <h3>Mejor puntaje</h3>
            <p id="best-score">...</p>
        </div>

        <div class="dashboard-card">
            <h3>Porcentaje de aciertos</h3>
            <p id="accuracy">...</p>
        </div>

        <div class="dashboard-card">
            <h3>Dificultad más jugada</h3>
            <p id="favorite-difficulty">...</p>
        </div>
    </div>

    <div class="recent-games-section">
        <h2>Últimas partidas</h2>
        <table id="recentGamesTable" width="100%" border="1">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Puntaje</th>
                    <th>Dificultad</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
function formatDifficulty(value) {
    if (value === "easy") return "Fácil";
    if (value === "medium") return "Medio";
    if (value === "hard") return "Difícil";
    return value;
}

fetch("/colesterol_game/backend/get_dashboard.php")
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            document.getElementById("dashboard-cards").innerHTML = "<p>No se pudo cargar el dashboard.</p>";
            return;
        }

        document.getElementById("total-games").textContent = data.total_games;
        document.getElementById("avg-score").textContent = data.avg_score;
        document.getElementById("best-score").textContent = data.best_score;
        document.getElementById("accuracy").textContent = data.accuracy + "%";
        document.getElementById("favorite-difficulty").textContent = formatDifficulty(data.favorite_difficulty);

        const tbody = document.querySelector("#recentGamesTable tbody");

        if (!Array.isArray(data.recent_games) || data.recent_games.length === 0) {
            tbody.innerHTML = "<tr><td colspan='3'>No hay partidas registradas</td></tr>";
            return;
        }

        data.recent_games.forEach(game => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${game.played_at}</td>
                <td>${game.score}</td>
                <td>${formatDifficulty(game.difficulty)}</td>
            `;
            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(error);
        document.getElementById("dashboard-cards").innerHTML = "<p>Error al cargar la información del dashboard.</p>";
    });
</script>

</body>
</html>
