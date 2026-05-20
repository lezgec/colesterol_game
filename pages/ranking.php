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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking global</title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <h1 style="margin:0;">🏆 Ranking global</h1>
        <div>
            <a href="/colesterol_game/pages/game.php" class="logout-btn" style="background:#222;">Volver al juego</a>
        </div>
    </div>

    <p>Se muestran los 10 mejores jugadores según su mejor puntaje registrado.</p>

    <table id="rankingTable" width="100%" border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>Jugador</th>
                <th>Mejor puntaje</th>
                <th>Total de partidas</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
fetch("/colesterol_game/backend/get_ranking.php")
  .then(res => res.json())
  .then(data => {
    const tbody = document.querySelector("#rankingTable tbody");

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = "<tr><td colspan='4'>No hay datos de ranking disponibles</td></tr>";
      return;
    }

    data.forEach((player, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${index + 1}</td>
        <td>${player.name}</td>
        <td>${player.best_score}</td>
        <td>${player.total_games}</td>
      `;
      tbody.appendChild(row);
    });
  })
  .catch(error => {
    console.error(error);
    const tbody = document.querySelector("#rankingTable tbody");
    tbody.innerHTML = "<tr><td colspan='4'>Error al cargar el ranking</td></tr>";
  });
</script>

</body>
</html>