<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";
$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$gamePath = $baseDir . "/" . basename($gameId);
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !file_exists($gameFile)) {
    html_bootstrap3_createHeader("en", "Game Not Found | SubLim3 JukeBox", $conf['base_url']);
    ?>
    <body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">
    <div class="container">
        <?php include("inc.navigation.php"); ?>
        <div class="alert alert-danger">Game not found.</div>
        <a class="btn btn-default" href="game.php">Back</a>
    </div>
    <?php
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
        $characterId = trim($characterId, "-");

        if ($characterId === "") {
            $message = "Character name must contain at least one letter or number.";
        } else {
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
}

html_bootstrap3_createHeader(
    "en",
    "Add Character | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-account-plus"></i>
        Add Character
    </h1>

    <p class="lead">
        Create a player character for this campaign.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="game-character-add.php">

        <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>
                    <i class="mdi mdi-account"></i>
                    Character Details
                </strong>
            </div>

            <div class="panel-body">

                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="player_name">Player Name</label>
                            <input
                                id="player_name"
                                class="form-control input-lg"
                                type="text"
                                name="player_name"
                                placeholder="Example: Jason"
                                required
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="character_name">Character Name</label>
                            <input
                                id="character_name"
                                class="form-control input-lg"
                                type="text"
                                name="character_name"
                                placeholder="Example: Thorne Ironfist"
                                required
                            >
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>
                    <i class="mdi mdi-heart-pulse"></i>
                    Health
                </strong>
            </div>

            <div class="panel-body">

                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="max_hp">Max HP</label>
                            <input id="max_hp" class="form-control input-lg" type="number" name="max_hp" value="100" min="0">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="hp">Current HP</label>
                            <input id="hp" class="form-control input-lg" type="number" name="hp" value="100" min="0">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="temp_hp">Temp HP</label>
                            <input id="temp_hp" class="form-control input-lg" type="number" name="temp_hp" value="0" min="0">
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>
                    <i class="mdi mdi-auto-fix"></i>
                    Spell Slots
                </strong>
            </div>

            <div class="panel-body">

                <div class="row">

                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="col-xs-6 col-sm-4 col-md-2">
                            <div class="form-group">
                                <label for="spell<?= $i ?>">Level <?= $i ?></label>
                                <input
                                    id="spell<?= $i ?>"
                                    class="form-control"
                                    type="number"
                                    name="spell<?= $i ?>"
                                    value="0"
                                    min="0"
                                >
                            </div>
                        </div>
                    <?php endfor; ?>

                </div>

            </div>
        </div>

        <button class="btn btn-primary btn-lg" type="submit">
            <i class="mdi mdi-content-save"></i>
            Save Character
        </button>

        <a class="btn btn-default btn-lg" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
            <i class="mdi mdi-arrow-left"></i>
            Cancel
        </a>

    </form>

</div>

<?php
include("inc.footer.php");
?>
