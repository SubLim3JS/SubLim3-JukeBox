<?php
include("inc.header.php");

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

$games = glob($gameDir . "/*/game.json");
?>

<div class="container">

    <h1>Existing Game</h1>

    <?php if (empty($games)): ?>
        <div class="alert alert-info">No saved games found.</div>
        <a class="btn btn-success" href="game-new.php">Create New Game</a>
    <?php else: ?>

        <div class="list-group">
            <?php foreach ($games as $gameFile): ?>
                <?php
                $game = json_decode(file_get_contents($gameFile), true);
                $gameId = $game["game_id"] ?? basename(dirname($gameFile));
                $gameName = $game["game_name"] ?? $gameId;
                $created = $game["created"] ?? "";
                ?>
                <a class="list-group-item" href="game-cube-register.php?game_id=<?= urlencode($gameId) ?>">
                    <h4 class="list-group-item-heading"><?= htmlspecialchars($gameName) ?></h4>
                    <p class="list-group-item-text">Created: <?= htmlspecialchars($created) ?></p>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <a class="btn btn-default" href="game.php">Back</a>

</div>

<?php
include("inc.footer.php");
?>
