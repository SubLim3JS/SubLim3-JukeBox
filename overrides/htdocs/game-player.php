<?php
include("inc.header.php");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$activeGameFile = $baseDir . "/active-game";

function cleanInt($value, $default = 0) {
    return is_numeric($value) ? intval($value) : $default;
}

function getCharacterName($character, $index) {
    return $character["name"] ?? $character["character_name"] ?? ("Character " . ($index + 1));
}

function normalizeCharactersData($data) {
    if (isset($data["characters"]) && is_array($data["characters"])) {
        return [$data, $data["characters"], true];
    }

    if (is_array($data)) {
        return [$data, $data, false];
    }

    return [[], [], false];
}

function saveCharactersData($charactersFile, $originalData, $characters, $usesWrapper) {
    if ($usesWrapper) {
        $originalData["characters"] = $characters;
        $saveData = $originalData;
    } else {
        $saveData = $characters;
    }

    file_put_contents(
        $charactersFile,
        json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

if (!file_exists($activeGameFile)) {
    die("No active game selected.");
}

$gameId = trim(file_get_contents($activeGameFile));
$gameDir = $baseDir . "/games/" . basename($gameId);

$gameFile = $gameDir . "/game.json";
$charactersFile = $gameDir . "/characters.json";

if ($gameId === "" || !file_exists($gameFile) || !file_exists($charactersFile)) {
    die("Active game not found or missing character data.");
}

$gameData = json_decode(file_get_contents($gameFile), true);
$charactersDataRaw = json_decode(file_get_contents($charactersFile), true);

list($charactersData, $characters, $usesWrapper) = normalizeCharactersData($charactersDataRaw);

if (!is_array($characters) || count($characters) === 0) {
    die("No characters found for this game.");
}

$selectedIndex = cleanInt($_GET["character"] ?? 0, 0);

if ($selectedIndex < 0 || $selectedIndex >= count($characters)) {
    $selectedIndex = 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedIndex = cleanInt($_POST["character"] ?? 0, 0);
    $field = $_POST["field"] ?? "";
    $change = cleanInt($_POST["change"] ?? 0, 0);

    if ($selectedIndex >= 0 && $selectedIndex < count($characters)) {
        if (!isset($characters[$selectedIndex]["hp"])) {
            $characters[$selectedIndex]["hp"] = 0;
        }

        if (!isset($characters[$selectedIndex]["max_hp"])) {
            $characters[$selectedIndex]["max_hp"] = $characters[$selectedIndex]["hp"];
        }

        if (!isset($characters[$selectedIndex]["temp_hp"])) {
            $characters[$selectedIndex]["temp_hp"] = 0;
        }

        if (!isset($characters[$selectedIndex]["death_success"])) {
            $characters[$selectedIndex]["death_success"] = 0;
        }

        if (!isset($characters[$selectedIndex]["death_fail"])) {
            $characters[$selectedIndex]["death_fail"] = 0;
        }

        switch ($field) {
            case "hp":
                $characters[$selectedIndex]["hp"] = cleanInt($characters[$selectedIndex]["hp"]) + $change;
                if ($characters[$selectedIndex]["hp"] < 0) {
                    $characters[$selectedIndex]["hp"] = 0;
                }
                break;

            case "temp_hp":
                $characters[$selectedIndex]["temp_hp"] = cleanInt($characters[$selectedIndex]["temp_hp"]) + $change;
                if ($characters[$selectedIndex]["temp_hp"] < 0) {
                    $characters[$selectedIndex]["temp_hp"] = 0;
                }
                break;

            case "death_success":
                $characters[$selectedIndex]["death_success"] = cleanInt($characters[$selectedIndex]["death_success"]) + $change;
                if ($characters[$selectedIndex]["death_success"] < 0) {
                    $characters[$selectedIndex]["death_success"] = 0;
                }
                if ($characters[$selectedIndex]["death_success"] > 3) {
                    $characters[$selectedIndex]["death_success"] = 3;
                }
                break;

            case "death_fail":
                $characters[$selectedIndex]["death_fail"] = cleanInt($characters[$selectedIndex]["death_fail"]) + $change;
                if ($characters[$selectedIndex]["death_fail"] < 0) {
                    $characters[$selectedIndex]["death_fail"] = 0;
                }
                if ($characters[$selectedIndex]["death_fail"] > 3) {
                    $characters[$selectedIndex]["death_fail"] = 3;
                }
                break;
        }

        saveCharactersData($charactersFile, $charactersData, $characters, $usesWrapper);
    }

    header("Location: game-player.php?character=" . urlencode($selectedIndex));
    exit;
}

$character = $characters[$selectedIndex];

$name = getCharacterName($character, $selectedIndex);
$gameName = $gameData["name"] ?? $gameData["game_name"] ?? $gameId;

$hp = cleanInt($character["hp"] ?? 0);
$maxHp = cleanInt($character["max_hp"] ?? $hp);
$tempHp = cleanInt($character["temp_hp"] ?? 0);
$deathSuccess = cleanInt($character["death_success"] ?? 0);
$deathFail = cleanInt($character["death_fail"] ?? 0);

html_bootstrap3_createHeader(
    "en",
    "DnD Player Tracker",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars($conf['theme'] ?? ''); ?>">

<div class="container">

    <div class="row">
        <div class="col-lg-12">
            <h1>DnD Player Tracker</h1>
            <p class="lead">
                Active Game:
                <strong><?php print htmlspecialchars($gameName); ?></strong>
            </p>
        </div>
    </div>

    <form method="get" action="game-player.php" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Character</label>
            <div class="col-sm-6">
                <select name="character" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($characters as $index => $char): ?>
                        <option value="<?php print $index; ?>" <?php if ($index === $selectedIndex) print "selected"; ?>>
                            <?php print htmlspecialchars(getCharacterName($char, $index)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <hr>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php print htmlspecialchars($name); ?></h3>
        </div>

        <div class="panel-body">

            <h2>
                HP:
                <?php print htmlspecialchars($hp); ?>
                /
                <?php print htmlspecialchars($maxHp); ?>
            </h2>

            <div class="btn-group" style="margin-bottom: 20px;">
                <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="hp">
                        <input type="hidden" name="change" value="<?php print $amount; ?>">
                        <button class="btn btn-default btn-lg" type="submit">
                            <?php print ($amount > 0 ? "+" : "") . $amount; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <h3>
                Temp HP:
                <?php print htmlspecialchars($tempHp); ?>
            </h3>

            <div class="btn-group" style="margin-bottom: 20px;">
                <?php foreach ([-1, 1, 5] as $amount): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="temp_hp">
                        <input type="hidden" name="change" value="<?php print $amount; ?>">
                        <button class="btn btn-default btn-lg" type="submit">
                            <?php print ($amount > 0 ? "+" : "") . $amount; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <hr>

            <h3>Death Saves</h3>

            <div class="row">
                <div class="col-sm-6">
                    <h4>Success: <?php print htmlspecialchars($deathSuccess); ?> / 3</h4>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="death_success">
                        <input type="hidden" name="change" value="-1">
                        <button class="btn btn-warning btn-lg" type="submit">-</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="death_success">
                        <input type="hidden" name="change" value="1">
                        <button class="btn btn-success btn-lg" type="submit">+</button>
                    </form>
                </div>

                <div class="col-sm-6">
                    <h4>Failure: <?php print htmlspecialchars($deathFail); ?> / 3</h4>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="death_fail">
                        <input type="hidden" name="change" value="-1">
                        <button class="btn btn-warning btn-lg" type="submit">-</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="character" value="<?php print $selectedIndex; ?>">
                        <input type="hidden" name="field" value="death_fail">
                        <input type="hidden" name="change" value="1">
                        <button class="btn btn-danger btn-lg" type="submit">+</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <p>
        <a href="game-dashboard.php?game_id=<?php print urlencode($gameId); ?>" class="btn btn-primary">
            Back to DM Dashboard
        </a>
    </p>

</div>

</body>
</html>
