<?php
include("inc.header.php");

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $gameName = trim($_POST["game_name"] ?? "");

    if ($gameName === "") {
        $message = "Game name is required.";
    } else {
        $safeGameId = strtolower(preg_replace("/[^a-zA-Z0-9_-]/", "-", $gameName));
        $gamePath = $gameDir . "/" . $safeGameId;

        if (!is_dir($gamePath)) {
            mkdir($gamePath, 0775, true);
        }

        $gameData = [
            "game_id" => $safeGameId,
            "game_name" => $gameName,
            "created" => date("Y-m-d H:i:s"),
            "characters" => []
        ];

        file_put_contents($gamePath . "/game.json", json_encode($gameData, JSON_PRETTY_PRINT));

        header("Location: game-dashboard.php?game_id=" . urlencode($safeGameId));
        exit;
    }
}
?>

<div class="container">

    <h1>New Game</h1>

    <?php if ($message !== ""): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Game Name</label>
            <input class="form-control" type="text" name="game_name" placeholder="Example: Friday Night Campaign">
        </div>

        <button class="btn btn-success" type="submit">Create Game</button>
        <a class="btn btn-default" href="game.php">Cancel</a>
    </form>

</div>

<?php
include("inc.footer.php");
?>
