<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";
$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$gamePath = $baseDir . "/" . basename($gameId);
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !file_exists($gameFile)) {
    echo '<div class="container"><div class="alert alert-danger">Game not found.</div><a class="btn btn-default" href="game.php">Back</a></div>';
    include("inc.footer.php");
    exit;
}

$message = "";

if (!file_exists($charactersFile)) {
    file_put_contents($charactersFile, json_encode([], JSON_PRETTY_PRINT));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $playerName = trim($_POST["player_name"] ?? "");
    $characterName = trim($_POST["character_name"] ?? "");

    if ($playerName === "" || $characterName === "") {
        $message = "Player name and character name are required.";
    } else {
        $characters = json_decode(file_get_contents($charactersFile), true);
        if (!is_array($characters)) {
            $characters = [];
        }

        $characterId = strtolower(preg_replace("/[^a-zA-Z0-9_-]/", "-", $characterName));

        $characters[$characterId] = [
            "character_id" => $characterId,
            "player_name" => $playerName,
            "character_name" => $characterName,
            "rfid_uid" => "",
            "max_hp" => intval($_POST["max_hp"] ?? 0),
            "hp" => intval($_POST["hp"] ?? 0),
            "temp_hp" => intval($_POST["temp_hp"] ?? 0),
            "death_success" => 0,
            "death_fail" => 0,
            "spell1" => intval($_POST["spell1"] ?? 0),
            "spell2" => intval($_POST["spell2"] ?? 0),
            "spell3" => intval($_POST["spell3"] ?? 0),
            "spell4" => intval($_POST["spell4"] ?? 0),
            "spell5" => intval($_POST["spell5"] ?? 0),
            "spell6" => intval($_POST["spell6"] ?? 0),
            "cube_id" => ""
        ];

        file_put_contents($charactersFile, json_encode($characters, JSON_PRETTY_PRINT));

        header("Location: game-dashboard.php?game_id=" . urlencode($gameId));
        exit;
    }
}
?>

<div class="container">

    <h1>Add Character</h1>

    <?php if ($message !== ""): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">

        <div class="form-group">
            <label>Player Name</label>
            <input class="form-control" type="text" name="player_name" required>
        </div>

        <div class="form-group">
            <label>Character Name</label>
            <input class="form-control" type="text" name="character_name" required>
        </div>

        <div class="form-group">
            <label>Max HP</label>
            <input class="form-control" type="number" name="max_hp" value="10">
        </div>

        <div class="form-group">
            <label>Current HP</label>
            <input class="form-control" type="number" name="hp" value="10">
        </div>

        <div class="form-group">
            <label>Temp HP</label>
            <input class="form-control" type="number" name="temp_hp" value="0">
        </div>

        <h3>Spell Slots</h3>

        <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="form-group">
                <label>Spell <?= $i ?></label>
                <input class="form-control" type="number" name="spell<?= $i ?>" value="0">
            </div>
        <?php endfor; ?>

        <button class="btn btn-success" type="submit">Save Character</button>
        <a class="btn btn-default" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">Cancel</a>
    </form>

</div>

<?php
include("inc.footer.php");
?>
