<?php
include("inc.header.php");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$activeGameFile = $baseDir . "/active-game";

function cleanInt($value, $default = 0) {
    return is_numeric($value) ? intval($value) : $default;
}

function getCharacterName($character, $index) {
    return $character["character_name"] ?? $character["name"] ?? ("Character " . ($index + 1));
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

function findCharacterIndex($characters, $characterId, $fallbackIndex = 0) {
    if ($characterId !== "") {
        foreach ($characters as $index => $character) {
            if (($character["character_id"] ?? "") === $characterId) {
                return $index;
            }
        }
    }

    if ($fallbackIndex >= 0 && $fallbackIndex < count($characters)) {
        return $fallbackIndex;
    }

    return 0;
}

$gameId = basename($_GET["game_id"] ?? $_POST["game_id"] ?? "");

if ($gameId === "") {
    if (!file_exists($activeGameFile)) {
        die("No active game selected.");
    }

    $gameId = trim(file_get_contents($activeGameFile));
}

$gameId = basename($gameId);
$gameDir = $baseDir . "/games/" . $gameId;

$gameFile = $gameDir . "/game.json";
$charactersFile = $gameDir . "/characters.json";

if ($gameId === "" || !file_exists($gameFile) || !file_exists($charactersFile)) {
    die("Game not found or missing character data.");
}

$gameData = json_decode(file_get_contents($gameFile), true);
$charactersDataRaw = json_decode(file_get_contents($charactersFile), true);

list($charactersData, $characters, $usesWrapper) = normalizeCharactersData($charactersDataRaw);

if (!is_array($characters) || count($characters) === 0) {
    die("No characters found for this game.");
}

$characterId = $_GET["character_id"] ?? $_POST["character_id"] ?? "";
$fallbackIndex = cleanInt($_GET["character"] ?? $_POST["character"] ?? 0, 0);
$selectedIndex = findCharacterIndex($characters, $characterId, $fallbackIndex);

if (!isset($characters[$selectedIndex]["character_id"]) || $characters[$selectedIndex]["character_id"] === "") {
    $characters[$selectedIndex]["character_id"] = "char_" . time() . "_" . $selectedIndex;
    saveCharactersData($charactersFile, $charactersData, $characters, $usesWrapper);
}

$characterId = $characters[$selectedIndex]["character_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $field = $_POST["field"] ?? "";
    $change = cleanInt($_POST["change"] ?? 0, 0);

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

            if ($characters[$selectedIndex]["hp"] > cleanInt($characters[$selectedIndex]["max_hp"])) {
                $characters[$selectedIndex]["hp"] = cleanInt($characters[$selectedIndex]["max_hp"]);
            }
            break;

        case "max_hp":
            $characters[$selectedIndex]["max_hp"] = cleanInt($characters[$selectedIndex]["max_hp"]) + $change;

            if ($characters[$selectedIndex]["max_hp"] < 1) {
                $characters[$selectedIndex]["max_hp"] = 1;
            }

            if (cleanInt($characters[$selectedIndex]["hp"]) > cleanInt($characters[$selectedIndex]["max_hp"])) {
                $characters[$selectedIndex]["hp"] = cleanInt($characters[$selectedIndex]["max_hp"]);
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

    header("Location: game-player.php?game_id=" . urlencode($gameId) . "&character_id=" . urlencode($characterId));
    exit;
}

$character = $characters[$selectedIndex];

$name = getCharacterName($character, $selectedIndex);
$playerName = $character["player_name"] ?? "";
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

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

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
        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">

        <div class="form-group">
            <label class="col-sm-2 control-label">Character</label>
            <div class="col-sm-6">
                <select name="character_id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($characters as $index => $char): ?>
                        <?php
                        $optionCharacterId = $char["character_id"] ?? "";
                        if ($optionCharacterId === "") {
                            $optionCharacterId = "index_" . $index;
                        }
                        ?>
                        <option value="<?php print htmlspecialchars($optionCharacterId); ?>" <?php if ($index === $selectedIndex) print "selected"; ?>>
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
            <h3 class="panel-title">
                <?php print htmlspecialchars($name); ?>
                <?php if ($playerName !== ""): ?>
                    <small style="color:#fff;"> — <?php print htmlspecialchars($playerName); ?></small>
                <?php endif; ?>
            </h3>
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
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="hp">
                        <input type="hidden" name="change" value="<?php print $amount; ?>">
                        <button class="btn btn-default btn-lg" type="submit">
                            <?php print ($amount > 0 ? "+" : "") . $amount; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <h3>Max HP: <?php print htmlspecialchars($maxHp); ?></h3>

            <div class="btn-group" style="margin-bottom: 20px;">
                <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="max_hp">
                        <input type="hidden" name="change" value="<?php print $amount; ?>">
                        <button class="btn btn-default btn-lg" type="submit">
                            <?php print ($amount > 0 ? "+" : "") . $amount; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <h3>Temp HP: <?php print htmlspecialchars($tempHp); ?></h3>

            <div class="btn-group" style="margin-bottom: 20px;">
                <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
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
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="death_success">
                        <input type="hidden" name="change" value="-1">
                        <button class="btn btn-warning btn-lg" type="submit">-</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="death_success">
                        <input type="hidden" name="change" value="1">
                        <button class="btn btn-success btn-lg" type="submit">+</button>
                    </form>
                </div>

                <div class="col-sm-6">
                    <h4>Failure: <?php print htmlspecialchars($deathFail); ?> / 3</h4>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="death_fail">
                        <input type="hidden" name="change" value="-1">
                        <button class="btn btn-warning btn-lg" type="submit">-</button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="game_id" value="<?php print htmlspecialchars($gameId); ?>">
                        <input type="hidden" name="character_id" value="<?php print htmlspecialchars($characterId); ?>">
                        <input type="hidden" name="field" value="death_fail">
                        <input type="hidden" name="change" value="1">
                        <button class="btn btn-danger btn-lg" type="submit">+</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <?php if (isset($_GET["dm"])): ?>
        <p>
            <a href="game-dashboard.php?game_id=<?php print urlencode($gameId); ?>" class="btn btn-primary">
                Back to DM Dashboard
            </a>

            <a href="game-players.php" class="btn btn-default">
                Back to Manage Players
            </a>
        </p>
    <?php endif; ?>

</div>

        <script>
setTimeout(function () {
    window.location.reload();
}, 5000);
</script>
    
<?php
include("inc.footer.php");
?>
