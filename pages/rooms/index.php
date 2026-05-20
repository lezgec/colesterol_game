<?php require_once __DIR__ . '/../../lang/translate.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("rooms_title"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <h1><?php echo t("rooms_title"); ?></h1>

    <a href="/colesterol_game/pages/rooms/create.php" class="primary-btn" style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        <?php echo t("create_room"); ?>
    </a>

    <a href="/colesterol_game/pages/rooms/join.php" class="primary-btn" style="display:block; text-align:center; text-decoration:none; background:#222;">
        <?php echo t("join_room"); ?>
    </a>

    <br>

    <a href="/colesterol_game/pages/game.php">
        <?php echo t("back_to_game"); ?>
    </a>
</div>

</body>
</html>