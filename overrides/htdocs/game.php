<?php
include("inc.header.php");

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$playersFile = $gameDir . "/players.json";
$cubesFile = $gameDir . "/cubes.json";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

if (!file_exists($playersFile)) {
    file_put_contents($playersFile, json_encode([], JSON_PRETTY_PRINT));
}

if (!file_exists($cubesFile)) {
    file_put_contents($cubesFile, json_encode([], JSON_PRETTY_PRINT));
}

$players = json_decode(file_get_contents($playersFile), true);
$cubes = json_decode(file_get_contents($cubesFile), true);
?>

<div class="container">

    <h1>Game Mode</h1>
    <p>DnD Book is now acting as the DM game system.</p>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Players</strong>
        </div>
        <div class="panel-body">
            <?php if (empty($players)): ?>
                <p>No players created yet.</p>
            <?php else: ?>
                <table class="table table-striped">
                    <tr>
                        <th>Player</th>
                        <th>Character</th>
                        <th>HP</th>
                        <th>Temp HP</th>
                        <th>Cube</th>
                    </tr>
                    <?php foreach ($players as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p["player_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars($p["character_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars(($p["hp"] ?? 0) . "/" . ($p["max_hp"] ?? 0)) ?></td>
                            <td><?= htmlspecialchars($p["temp_hp"] ?? 0) ?></td>
                            <td><?= htmlspecialchars($p["cube_id"] ?? "Not assigned") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Waiting Cubes</strong>
        </div>
        <div class="panel-body">
            <?php if (empty($cubes)): ?>
                <p>No cubes waiting.</p>
            <?php else: ?>
                <table class="table table-striped">
                    <tr>
                        <th>Cube ID</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                    </tr>
                    <?php foreach ($cubes as $cube): ?>
                        <tr>
                            <td><?= htmlspecialchars($cube["cube_id"] ?? "") ?></td>
                            <td><?= htmlspecialchars($cube["status"] ?? "") ?></td>
                            <td><?= htmlspecialchars($cube["last_seen"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <a class="btn btn-primary" href="index.php">Back to Book</a>

</div>

<?php
include("inc.footer.php");
?>
