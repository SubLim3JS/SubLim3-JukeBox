<?php
include("inc.header.php");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";
$activeGameFile = $baseDir . "/active-game";

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";
$characterId = $_GET["character_id"] ?? $_POST["character_id"] ?? "";
$isDm = (($_GET["dm"] ?? $_POST["dm"] ?? "") === "1");

if ($gameId === "" && file_exists($activeGameFile)) {
    $gameId = trim(file_get_contents($activeGameFile));
}

$gameId = basename($gameId);
$characterId = trim($characterId);

if ($gameId === "") {
    die("No active game selected.");
}

$gamePath = $gamesDir . "/" . $gameId;
$charactersFile = $gamePath . "/characters.json";

if (!is_dir($gamePath) || !file_exists($charactersFile)) {
    die("Game not found.");
}

$characters = json_decode(file_get_contents($charactersFile), true);

if (!is_array($characters)) {
    $characters = [];
}

function getCharacterId($character) {
    return $character["id"]
        ?? $character["code"]
        ?? $character["character_id"]
        ?? "";
}

function getCharacterName($character) {
    return $character["name"]
        ?? $character["character_name"]
        ?? $character["player_name"]
        ?? $character["code"]
        ?? "Unknown";
}

function saveCharacters($charactersFile, $characters) {
    file_put_contents(
        $charactersFile,
        json_encode($characters, JSON_PRETTY_PRINT)
    );
}

$selectedIndex = null;

foreach ($characters as $index => $character) {
    if ($characterId !== "" && getCharacterId($character) === $characterId) {
        $selectedIndex = $index;
        break;
    }
}

if ($selectedIndex === null && count($characters) > 0) {
    $selectedIndex = 0;
    $characterId = getCharacterId($characters[0]);
}

if ($selectedIndex === null) {
    die("No characters found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    $hp = intval($_POST["hp"] ?? 0);
    $tempHp = intval($_POST["temp_hp"] ?? 0);
    $deathSuccess = intval($_POST["death_success"] ?? 0);
    $deathFail = intval($_POST["death_fail"] ?? 0);

    $maxHp = intval($characters[$selectedIndex]["max_hp"] ?? 0);

    if ($action === "long_rest") {
        $hp = $maxHp;
        $tempHp = 0;
        $deathSuccess = 0;
        $deathFail = 0;
    }

    if ($maxHp > 0) {
        $hp = max(0, min($hp, $maxHp));
    } else {
        $hp = max(0, $hp);
    }

    $tempHp = max(0, $tempHp);
    $deathSuccess = max(0, min($deathSuccess, 3));
    $deathFail = max(0, min($deathFail, 3));

    $characters[$selectedIndex]["hp"] = $hp;
    $characters[$selectedIndex]["current_hp"] = $hp;
    $characters[$selectedIndex]["temp_hp"] = $tempHp;
    $characters[$selectedIndex]["death_success"] = $deathSuccess;
    $characters[$selectedIndex]["death_saves_success"] = $deathSuccess;
    $characters[$selectedIndex]["death_fail"] = $deathFail;
    $characters[$selectedIndex]["death_saves_fail"] = $deathFail;
    $characters[$selectedIndex]["updated"] = date("Y-m-d H:i:s");

    saveCharacters($charactersFile, $characters);

    $redirectUrl = "game-player.php?game_id=" . urlencode($gameId) .
        "&character_id=" . urlencode($characterId);

    if ($isDm) {
        $redirectUrl .= "&dm=1";
    }

    header("Location: " . $redirectUrl);
    exit;
}

$character = $characters[$selectedIndex];

$name = getCharacterName($character);
$hp = intval($character["hp"] ?? $character["current_hp"] ?? 0);
$maxHp = intval($character["max_hp"] ?? 0);
$tempHp = intval($character["temp_hp"] ?? 0);
$deathSuccess = intval($character["death_success"] ?? $character["death_saves_success"] ?? 0);
$deathFail = intval($character["death_fail"] ?? $character["death_saves_fail"] ?? 0);

$formAction = "game-player.php?game_id=" . urlencode($gameId) .
    "&character_id=" . urlencode($characterId);

if ($isDm) {
    $formAction .= "&dm=1";
}

html_bootstrap3_createHeader(
    "en",
    "Player Tracker | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php if ($isDm): ?>
    <?php include("inc.navigation.php"); ?>
<?php endif; ?>

    <h1>
        <i class="mdi mdi-account-heart"></i>
        <?= htmlspecialchars($name) ?>
    </h1>

    <p class="lead">
        Player Tracker
    </p>

    <?php if ($isDm): ?>
        <p>
            <a class="btn btn-default" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
                <i class="mdi mdi-arrow-left"></i>
                Back to Dashboard
            </a>

            <a class="btn btn-default" href="game-players.php?game_id=<?= urlencode($gameId) ?>">
                <i class="mdi mdi-account-group"></i>
                Back to Manage Players
            </a>
        </p>
    <?php endif; ?>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Stats</strong>
        </div>

        <div class="panel-body">

            <form method="post" action="<?= htmlspecialchars($formAction) ?>">

                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="character_id" value="<?= htmlspecialchars($characterId) ?>">

                <?php if ($isDm): ?>
                    <input type="hidden" name="dm" value="1">
                <?php endif; ?>

                <div class="form-group">
                    <label for="hp">HP</label>
                    <input
                        id="hp"
                        class="form-control input-lg"
                        type="number"
                        name="hp"
                        value="<?= htmlspecialchars($hp) ?>"
                        min="0"
                        max="<?= htmlspecialchars($maxHp) ?>"
                    >
                    <p class="help-block">
                        Max HP: <?= htmlspecialchars($maxHp) ?>
                    </p>
                </div>

                <div class="form-group">
                    <label for="temp_hp">Temp HP</label>
                    <input
                        id="temp_hp"
                        class="form-control input-lg"
                        type="number"
                        name="temp_hp"
                        value="<?= htmlspecialchars($tempHp) ?>"
                        min="0"
                    >
                </div>

                <div class="form-group">
                    <label for="death_success">Death Saves - Success</label>
                    <input
                        id="death_success"
                        class="form-control input-lg"
                        type="number"
                        name="death_success"
                        value="<?= htmlspecialchars($deathSuccess) ?>"
                        min="0"
                        max="3"
                    >
                </div>

                <div class="form-group">
                    <label for="death_fail">Death Saves - Fail</label>
                    <input
                        id="death_fail"
                        class="form-control input-lg"
                        type="number"
                        name="death_fail"
                        value="<?= htmlspecialchars($deathFail) ?>"
                        min="0"
                        max="3"
                    >
                </div>

                <button class="btn btn-primary btn-lg" type="submit" name="action" value="save">
                    <i class="mdi mdi-content-save"></i>
                    Save Stats
                </button>

                <button class="btn btn-success btn-lg" type="submit" name="action" value="long_rest">
                    <i class="mdi mdi-weather-night"></i>
                    Long Rest
                </button>

            </form>

        </div>
    </div>

</div>

<script>
let isEditing = false;

document.querySelectorAll("input, select, textarea").forEach(function(el) {
    el.addEventListener("focus", function() {
        isEditing = true;
    });

    el.addEventListener("blur", function() {
        isEditing = false;
    });
});

setInterval(function() {
    if (!isEditing) {
        location.reload();
    }
}, 5000);
</script>

<?php
include("inc.footer.php");
?>
