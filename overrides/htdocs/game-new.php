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
        $safeGameId = trim($safeGameId, "-");

        if ($safeGameId === "") {
            $message = "Game name must contain at least one letter or number.";
        } else {
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

            file_put_contents(
                $gamePath . "/game.json",
                json_encode($gameData, JSON_PRETTY_PRINT)
            );

            header("Location: game-dashboard.php?game_id=" . urlencode($safeGameId));
            exit;
        }
    }
}

html_bootstrap3_createHeader(
    "en",
    "New Game | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-book-plus"></i>
        New Game
    </h1>

    <p class="lead">
        Create a new D&amp;D campaign.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Campaign Details</strong>
        </div>

        <div class="panel-body">

            <form method="post" action="game-new.php">

                <div class="form-group">
                    <label for="game_name">Game Name</label>
                    <input
                        id="game_name"
                        class="form-control input-lg"
                        type="text"
                        name="game_name"
                        placeholder="Example: Friday Night Campaign"
                        autocomplete="off"
                        required
                    >
                </div>

                <button class="btn btn-primary btn-lg" type="submit">
                    <i class="mdi mdi-check"></i>
                    Create Game
                </button>

                <a class="btn btn-default btn-lg" href="game.php">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php
include("inc.footer.php");
?>
