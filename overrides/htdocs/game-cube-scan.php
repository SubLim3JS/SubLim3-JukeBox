<?php
include("inc.header.php");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";
$activeGameFile = $baseDir . "/active-game";
$availableCubesFile = $baseDir . "/available-cubes.json";

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";

if ($gameId === "" && file_exists($activeGameFile)) {
    $gameId = trim(file_get_contents($activeGameFile));
}

$gameId = basename($gameId);

$gamePath = $gamesDir . "/" . $gameId;
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

function readJson($file, $default = []) {
    if (!file_exists($file)) {
        return $default;
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

function writeJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getCharacterId($character, $index = 0) {
    return $character["id"]
        ?? $character["code"]
        ?? $character["character_id"]
        ?? ("character_" . $index);
}

function getCharacterName($character) {
    return $character["character_name"]
        ?? $character["name"]
        ?? $character["player_name"]
        ?? $character["code"]
        ?? "Unknown";
}

if ($gameId === "" || !file_exists($gameFile) || !file_exists($charactersFile)) {
    html_bootstrap3_createHeader("en", "Cube Registration | Game Not Found", $conf['base_url']);
    ?>
    <body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">
    <div class="container">
        <?php include("inc.navigation.php"); ?>
        <div class="alert alert-danger">Game not found.</div>
        <a class="btn btn-default btn-lg" href="game.php">Back</a>
    </div>
    <?php
    include("inc.footer.php");
    exit;
}

$game = readJson($gameFile, []);
$characters = readJson($charactersFile, []);
$cubes = readJson($availableCubesFile, []);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cubeId = trim($_POST["cube_id"] ?? "");
    $characterId = trim($_POST["character_id"] ?? "");

    if ($cubeId === "" || $characterId === "") {
        $error = "Please select both a cube and a character.";
    } else {
        $assignedName = "";
        $foundCharacter = false;

        foreach ($characters as $index => &$character) {
            $thisCharacterId = getCharacterId($character, $index);

            if (($character["cube_id"] ?? "") === $cubeId) {
                $character["cube_id"] = "";
            }

            if ($thisCharacterId === $characterId) {
                $character["cube_id"] = $cubeId;
                $character["updated"] = date("Y-m-d H:i:s");
                $assignedName = getCharacterName($character);
                $foundCharacter = true;
            }
        }
        unset($character);

        if ($foundCharacter) {
            writeJson($charactersFile, $characters);
            $message = "Cube " . htmlspecialchars($cubeId) . " assigned to " . htmlspecialchars($assignedName) . ".";
        } else {
            $error = "Selected character was not found.";
        }
    }
}

$gameName = $game["game_name"] ?? $gameId;

html_bootstrap3_createHeader(
    "en",
    "Cube Registration | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-cube-send"></i>
        Cube Registration
    </h1>

    <p class="lead">
        Campaign:
        <strong><?= htmlspecialchars($gameName) ?></strong>
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Assign Cube to Character</strong>
        </div>

        <div class="panel-body">

            <?php if (empty($cubes)): ?>
                <div class="alert alert-info">
                    No cubes have checked in yet. Turn on a cube and wait for it to show Sync Mode.
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">

                <div class="form-group">
                    <label for="cube_id">Available Cube</label>
                    <select id="cube_id" name="cube_id" class="form-control input-lg" required>
                        <option value="">Select Cube</option>

                        <?php foreach ($cubes as $cube): ?>
                            <?php
                            $cubeId = $cube["cube_id"] ?? "";
                            $lastSeen = $cube["last_seen"] ?? "";
                            if ($cubeId === "") {
                                continue;
                            }
                            ?>
                            <option value="<?= htmlspecialchars($cubeId) ?>">
                                <?= htmlspecialchars($cubeId) ?>
                                <?= $lastSeen !== "" ? " - Last Seen: " . htmlspecialchars($lastSeen) : "" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="character_id">Character</label>
                    <select id="character_id" name="character_id" class="form-control input-lg" required>
                        <option value="">Select Character</option>

                        <?php foreach ($characters as $index => $character): ?>
                            <?php
                            $characterId = getCharacterId($character, $index);
                            $characterName = getCharacterName($character);
                            $playerName = $character["player_name"] ?? "";
                            $assignedCube = $character["cube_id"] ?? "";
                            ?>
                            <option value="<?= htmlspecialchars($characterId) ?>">
                                <?= htmlspecialchars($characterName) ?>
                                <?= $playerName !== "" ? " / " . htmlspecialchars($playerName) : "" ?>
                                <?= $assignedCube !== "" ? " - Current Cube: " . htmlspecialchars($assignedCube) : "" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="mdi mdi-content-save"></i>
                    Assign Cube
                </button>

                <a class="btn btn-default btn-lg" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
                    <i class="mdi mdi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </form>

        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Current Assignments</strong>
        </div>

        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Character</th>
                        <th>Player</th>
                        <th>Cube</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($characters as $index => $character): ?>
                        <tr>
                            <td><?= htmlspecialchars(getCharacterName($character)) ?></td>
                            <td><?= htmlspecialchars($character["player_name"] ?? "") ?></td>
                            <td>
                                <?php if (!empty($character["cube_id"])): ?>
                                    <span class="label label-success">
                                        <?= htmlspecialchars($character["cube_id"]) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="label label-default">Not assigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
setInterval(function() {
    fetch("api/dnd/api-cube-character.php?cube_id=registration-ping&_=" + Date.now(), {
        cache: "no-store"
    }).catch(function() {});
}, 15000);
</script>

<?php
include("inc.footer.php");
?>
