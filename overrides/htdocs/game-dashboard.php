<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? "";
$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$gamePath = $baseDir . "/" . basename($gameId);
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !file_exists($gameFile)) {
    echo '<div class="container"><div class="alert alert-danger">Game not found.</div><a class="btn btn-default" href="game.php">Back</a></div>';
    include("inc.footer.php");
    exit;
}

$game = json_decode(file_get_contents($gameFile), true);

if (!file_exists($charactersFile)) {
    file_put_contents($charactersFile, json_encode([], JSON_PRETTY_PRINT));
}

$characters = json_decode(file_get_contents($charactersFile), true);
if (!is_array($characters)) {
    $characters = [];
}
?>

<div class="container">

    <h1><?= htmlspecialchars($game["game_name"] ?? $gameId) ?></h1>
    <p>Game Dashboard</p>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Characters</strong></div>
        <div class="panel-body">

            <?php if (empty($characters)): ?>
                <p>No characters added yet.</p>
            <?php else: ?>
                <table class="table table-striped">
                    <tr>
                        <th>Player</th>
                        <th>Character</th>
                        <th>HP</th>
                        <th>Temp HP</th>
                        <th>Cube</th>
                    </tr>

                    <?php foreach ($characters as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c["player_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars($c["character_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars(($c["hp"] ?? 0) . "/" . ($c["max_hp"] ?? 0)) ?></td>
                            <td><?= htmlspecialchars($c["temp_hp"] ?? 0) ?></td>
                            <td><?= htmlspecialchars($c["cube_id"] ?: "Not assigned") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <a class="btn btn-success" href="game-character-add.php?game_id=<?= urlencode($gameId) ?>">
                Add Character
            </a>

        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading"><strong>Cube Registration</strong></div>
        <div class="panel-body">
            <p>Assign Player Cubes to characters.</p>
            <a class="btn btn-primary" href="game-cube-register.php?game_id=<?= urlencode($gameId) ?>">
                Register Cubes
            </a>
        </div>
    </div>

    <a class="btn btn-default" href="game.php">Back to Game Mode</a>

</div>

<?php
include("inc.footer.php");
?>
