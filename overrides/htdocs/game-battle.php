<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$gamePath = $baseDir . "/" . basename($gameId);

$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";
$battleFile = $gamePath . "/battle.json";

function cleanInt($value, $default = 0) {
    return is_numeric($value) ? intval($value) : $default;
}

function saveBattle($battleFile, $battle) {
    file_put_contents($battleFile, json_encode($battle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getCharacterId($character, $index = 0) {
    return $character["character_id"] ?? $character["id"] ?? $character["code"] ?? ("character_" . $index);
}

function getEntryRoll($entry, $default = 0) {
    return cleanInt($entry["roll"] ?? $default, $default);
}

if ($gameId === "" || !file_exists($gameFile)) {
    html_bootstrap3_createHeader("en", "Battle Mode | Game Not Found", $conf['base_url']);
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

$game = json_decode(file_get_contents($gameFile), true);
if (!is_array($game)) {
    $game = [];
}

if (!file_exists($charactersFile)) {
    file_put_contents($charactersFile, json_encode([], JSON_PRETTY_PRINT));
}

$characters = json_decode(file_get_contents($charactersFile), true);
if (!is_array($characters)) {
    $characters = [];
}

foreach ($characters as $index => &$character) {
    if (!isset($character["character_id"]) || $character["character_id"] === "") {
        $character["character_id"] = getCharacterId($character, $index);
    }
}
unset($character);

file_put_contents($charactersFile, json_encode($characters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if (!file_exists($battleFile)) {
    file_put_contents($battleFile, json_encode([
        "enemies" => [],
        "order" => []
    ], JSON_PRETTY_PRINT));
}

$battle = json_decode(file_get_contents($battleFile), true);
if (!is_array($battle)) {
    $battle = [
        "enemies" => [],
        "order" => []
    ];
}

if (!isset($battle["enemies"]) || !is_array($battle["enemies"])) {
    $battle["enemies"] = [];
}

if (!isset($battle["order"]) || !is_array($battle["order"])) {
    $battle["order"] = [];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "next_turn") {
        if (count($battle["order"]) > 1) {
            $first = array_shift($battle["order"]);
            $battle["order"][] = $first;
        }
    }

    if ($action === "add_enemy") {
        $enemyName = trim($_POST["enemy_name"] ?? "");
        $enemyHp = cleanInt($_POST["enemy_hp"] ?? 1, 1);

        if ($enemyName !== "") {
            $enemyId = "enemy_" . time() . "_" . rand(1000, 9999);

            $battle["enemies"][] = [
                "enemy_id" => $enemyId,
                "name" => $enemyName,
                "hp" => $enemyHp,
                "max_hp" => $enemyHp
            ];

            $battle["order"][] = [
                "type" => "enemy",
                "id" => $enemyId,
                "roll" => 0
            ];
        }
    }

    if ($action === "adjust_enemy_hp") {
        $enemyId = $_POST["enemy_id"] ?? "";
        $change = cleanInt($_POST["change"] ?? 0, 0);

        foreach ($battle["enemies"] as &$enemy) {
            if (($enemy["enemy_id"] ?? "") === $enemyId) {
                $enemy["hp"] = cleanInt($enemy["hp"] ?? 0) + $change;

                if ($enemy["hp"] < 0) {
                    $enemy["hp"] = 0;
                }

                if ($enemy["hp"] > cleanInt($enemy["max_hp"] ?? 0)) {
                    $enemy["hp"] = cleanInt($enemy["max_hp"] ?? 0);
                }

                break;
            }
        }
        unset($enemy);
    }

    if ($action === "delete_enemy") {
        $enemyId = $_POST["enemy_id"] ?? "";

        $battle["enemies"] = array_values(array_filter($battle["enemies"], function($enemy) use ($enemyId) {
            return ($enemy["enemy_id"] ?? "") !== $enemyId;
        }));

        $battle["order"] = array_values(array_filter($battle["order"], function($entry) use ($enemyId) {
            return !(($entry["type"] ?? "") === "enemy" && ($entry["id"] ?? "") === $enemyId);
        }));
    }

    if ($action === "sort_order") {
        $orderValues = $_POST["order_value"] ?? [];
        $newOrder = [];

        foreach ($characters as $index => $character) {
            $characterId = getCharacterId($character, $index);
            $key = "character:" . $characterId;

            $roll = cleanInt($orderValues[$key] ?? 0, 0);
            $roll = max(0, min($roll, 99));

            $newOrder[] = [
                "type" => "character",
                "id" => $characterId,
                "roll" => $roll,
                "tie_breaker" => rand(1, 1000000)
            ];
        }

        foreach ($battle["enemies"] as $enemy) {
            $enemyId = $enemy["enemy_id"] ?? "";

            if ($enemyId === "") {
                continue;
            }

            $key = "enemy:" . $enemyId;

            $roll = cleanInt($orderValues[$key] ?? 0, 0);
            $roll = max(0, min($roll, 99));

            $newOrder[] = [
                "type" => "enemy",
                "id" => $enemyId,
                "roll" => $roll,
                "tie_breaker" => rand(1, 1000000)
            ];
        }

        usort($newOrder, function($a, $b) {
            if ($a["roll"] === $b["roll"]) {
                return $a["tie_breaker"] <=> $b["tie_breaker"];
            }

            return $b["roll"] <=> $a["roll"];
        });

        $battle["order"] = array_map(function($entry) {
            return [
                "type" => $entry["type"],
                "id" => $entry["id"],
                "roll" => $entry["roll"]
            ];
        }, $newOrder);
    }

    if ($action === "clear_battle") {
        $battle = [
            "enemies" => [],
            "order" => []
        ];
    }

    saveBattle($battleFile, $battle);

    header("Location: game-battle.php?game_id=" . urlencode($gameId));
    exit;
}

$gameName = $game["game_name"] ?? $gameId;

html_bootstrap3_createHeader(
    "en",
    "Battle Mode | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-sword-cross"></i>
        Battle Mode
    </h1>

    <p class="lead">
        Campaign:
        <strong><?= htmlspecialchars($gameName) ?></strong>
    </p>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-format-list-numbered"></i>
                Attack Order
            </strong>
            <span id="liveStatus" class="pull-right small">Live</span>
        </div>

        <div class="panel-body">
            <p class="text-muted">
                Enter D20 initiative rolls, then click Sort Attack Order. Higher rolls go first. Ties are randomized.
            </p>

            <form method="post" id="sortOrderForm">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="sort_order">

                <ul id="battleOrderList" class="list-group"></ul>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="mdi mdi-dice-d20"></i>
                    Sort Attack Order
                </button>
            </form>

            <form method="post" style="display:inline;">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="next_turn">

                <button type="submit" class="btn btn-success btn-lg">
                    <i class="mdi mdi-skip-next"></i>
                    Next
                </button>
            </form>
        </div>
    </div>

    <div class="panel panel-danger">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-plus-circle"></i>
                Add Temporary Enemy
            </strong>
        </div>

        <div class="panel-body">
            <form method="post" class="form-inline">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="add_enemy">

                <div class="form-group">
                    <label>Enemy Name</label>
                    <input type="text" name="enemy_name" class="form-control" placeholder="Goblin" required>
                </div>

                <div class="form-group">
                    <label>HP</label>
                    <input type="number" name="enemy_hp" class="form-control" value="10" min="1" required>
                </div>

                <button type="submit" class="btn btn-danger">
                    <i class="mdi mdi-plus"></i>
                    Add Enemy
                </button>
            </form>
        </div>
    </div>

    <form method="post"
          onsubmit="return confirm('Clear battle mode? This removes all temporary enemies and attack order.');"
          style="display:inline;">
        <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
        <input type="hidden" name="action" value="clear_battle">

        <button type="submit" class="btn btn-warning btn-lg">
            <i class="mdi mdi-broom"></i>
            Clear Battle
        </button>
    </form>

    <a class="btn btn-default btn-lg" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
        <i class="mdi mdi-arrow-left"></i>
        Back to Dashboard
    </a>

</div>

<script>
var GAME_ID = <?= json_encode(basename($gameId)) ?>;
var battleFormDirty = false;
var battleFormSubmitting = false;

document.addEventListener("input", function(e) {
    if (e.target && e.target.closest && e.target.closest("#battleOrderList")) {
        battleFormDirty = true;

        var liveStatus = document.getElementById("liveStatus");
        if (liveStatus) {
            liveStatus.textContent = "Editing";
        }
    }
});

document.addEventListener("submit", function(e) {
    if (e.target && e.target.id === "sortOrderForm") {
        battleFormSubmitting = true;
    }
});

function escapeHtml(value) {
    value = value === null || value === undefined ? "" : String(value);

    return value
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getNumber(obj, keys, fallback) {
    for (var i = 0; i < keys.length; i++) {
        if (
            obj[keys[i]] !== undefined &&
            obj[keys[i]] !== null &&
            obj[keys[i]] !== ""
        ) {
            return parseInt(obj[keys[i]], 10) || 0;
        }
    }

    return fallback || 0;
}

function normalizeCharacters(characters) {
    if (!characters) {
        return [];
    }

    if (Array.isArray(characters)) {
        return characters;
    }

    var list = [];

    for (var id in characters) {
        if (characters.hasOwnProperty(id)) {
            var character = characters[id];

            if (character && typeof character === "object") {
                if (!character.character_id) {
                    character.character_id = id;
                }

                list.push(character);
            }
        }
    }

    return list;
}

function getCharacterId(character, index) {
    return character.character_id || character.id || character.code || ("character_" + index);
}

function getCharacterName(character) {
    return character.character_name || character.name || character.code || character.character_id || "Character";
}

function getCurrentRollValues() {
    var values = {};
    var inputs = document.querySelectorAll("#battleOrderList input[name^='order_value']");

    for (var i = 0; i < inputs.length; i++) {
        values[inputs[i].name] = inputs[i].value;
    }

    return values;
}

function isTypingInBattleList() {
    var active = document.activeElement;

    if (!active) {
        return false;
    }

    return active.closest && active.closest("#battleOrderList");
}

function buildMaps(characters, enemies) {
    var characterMap = {};
    var enemyMap = {};

    for (var i = 0; i < characters.length; i++) {
        var characterId = getCharacterId(characters[i], i);
        characterMap[characterId] = characters[i];
    }

    for (var e = 0; e < enemies.length; e++) {
        var enemyId = enemies[e].enemy_id || "";
        if (enemyId !== "") {
            enemyMap[enemyId] = enemies[e];
        }
    }

    return {
        characterMap: characterMap,
        enemyMap: enemyMap
    };
}

function buildDisplayEntries(characters, enemies, order) {
    var maps = buildMaps(characters, enemies);
    var characterMap = maps.characterMap;
    var enemyMap = maps.enemyMap;
    var displayEntries = [];
    var displayed = {};

    order = Array.isArray(order) ? order : [];

    for (var i = 0; i < order.length; i++) {
        var entry = order[i] || {};
        var type = entry.type || "";
        var id = entry.id || "";
        var roll = parseInt(entry.roll, 10);

        if (isNaN(roll)) {
            roll = 0;
        }

        var key = type + ":" + id;

        if (type === "character" && characterMap[id]) {
            displayEntries.push({
                type: "character",
                id: id,
                roll: roll
            });
            displayed[key] = true;
        }

        if (type === "enemy" && enemyMap[id]) {
            displayEntries.push({
                type: "enemy",
                id: id,
                roll: roll
            });
            displayed[key] = true;
        }
    }

    for (var c = 0; c < characters.length; c++) {
        var characterId = getCharacterId(characters[c], c);
        var characterKey = "character:" + characterId;

        if (!displayed[characterKey]) {
            displayEntries.push({
                type: "character",
                id: characterId,
                roll: 0
            });
            displayed[characterKey] = true;
        }
    }

    for (var e = 0; e < enemies.length; e++) {
        var enemyId = enemies[e].enemy_id || "";
        var enemyKey = "enemy:" + enemyId;

        if (enemyId !== "" && !displayed[enemyKey]) {
            displayEntries.push({
                type: "enemy",
                id: enemyId,
                roll: 0
            });
            displayed[enemyKey] = true;
        }
    }

    return {
        entries: displayEntries,
        characterMap: characterMap,
        enemyMap: enemyMap
    };
}

function getSavedRollValue(inputName, savedRolls, storedRoll) {
    if (savedRolls[inputName] !== undefined) {
        return savedRolls[inputName];
    }

    storedRoll = parseInt(storedRoll, 10);

    if (isNaN(storedRoll)) {
        storedRoll = 0;
    }

    return storedRoll;
}

function renderCharacterCard(character, id, position, savedRolls, storedRoll) {
    var key = "character:" + id;
    var inputName = "order_value[" + key + "]";
    var rollValue = getSavedRollValue(inputName, savedRolls, storedRoll);

    var characterName = getCharacterName(character);
    var playerName = character.player_name || "";
    var hp = getNumber(character, ["hp", "current_hp"], 0);
    var maxHp = getNumber(character, ["max_hp"], 0);
    var tempHp = getNumber(character, ["temp_hp"], 0);
    var deathSuccess = getNumber(character, ["death_success", "death_saves_success"], 0);
    var deathFail = getNumber(character, ["death_fail", "death_saves_fail"], 0);
    var cubeId = character.cube_id || "";

    var cubeHtml = cubeId !== ""
        ? '<span class="label label-success">' + escapeHtml(cubeId) + '</span>'
        : '<span class="label label-default">Not assigned</span>';

    var html = "";

    html += '<li class="list-group-item" data-type="character" data-id="' + escapeHtml(id) + '">';
    html += '<div class="row">';
    html += '<div class="col-sm-2">';
    html += '<label>D20 Roll</label>';
    html += '<input type="number" class="form-control input-lg" name="' + escapeHtml(inputName) + '" value="' + escapeHtml(rollValue) + '" min="0" max="99">';
    html += '</div>';

    html += '<div class="col-sm-10">';
    html += '<span class="label label-primary">CHARACTER</span>';
    html += '<strong style="margin-left:10px;">' + escapeHtml(characterName) + '</strong>';

    html += '<div class="row" style="margin-top:10px;">';
    html += '<div class="col-sm-2"><strong>Player</strong><br>' + escapeHtml(playerName) + '</div>';
    html += '<div class="col-sm-2"><strong>HP</strong><br>' + escapeHtml(hp + "/" + maxHp) + '</div>';
    html += '<div class="col-sm-2"><strong>Temp HP</strong><br>' + escapeHtml(tempHp) + '</div>';
    html += '<div class="col-sm-3"><strong>Death Saves</strong><br>Success ' + escapeHtml(deathSuccess) + '/3<br>Fail ' + escapeHtml(deathFail) + '/3</div>';
    html += '<div class="col-sm-3"><strong>Cube</strong><br>' + cubeHtml + '</div>';
    html += '</div>';

    html += '</div>';
    html += '</div>';
    html += '</li>';

    return html;
}

function renderEnemyCard(enemy, id, position, savedRolls, storedRoll) {
    var key = "enemy:" + id;
    var inputName = "order_value[" + key + "]";
    var rollValue = getSavedRollValue(inputName, savedRolls, storedRoll);

    var enemyName = enemy.name || "Enemy";
    var hp = getNumber(enemy, ["hp"], 0);
    var maxHp = getNumber(enemy, ["max_hp"], 0);

    var html = "";

    html += '<li class="list-group-item" data-type="enemy" data-id="' + escapeHtml(id) + '">';
    html += '<div class="row">';
    html += '<div class="col-sm-2">';
    html += '<label>D20 Roll</label>';
    html += '<input type="number" class="form-control input-lg" name="' + escapeHtml(inputName) + '" value="' + escapeHtml(rollValue) + '" min="0" max="99">';
    html += '</div>';

    html += '<div class="col-sm-10">';
    html += '<span class="label label-danger">ENEMY</span>';
    html += '<strong style="margin-left:10px;">' + escapeHtml(enemyName) + '</strong>';

    html += '<div class="row" style="margin-top:10px;">';
    html += '<div class="col-sm-3"><strong>HP</strong><br>' + escapeHtml(hp + "/" + maxHp) + '</div>';

    html += '<div class="col-sm-9">';
    html += '<strong>Adjust HP</strong><br>';

    [-5, -1, 1, 5].forEach(function(amount) {
        html += '<button type="button" class="btn btn-default btn-sm" onclick="submitEnemyHp(\'' + escapeHtml(id) + '\', ' + amount + ')">';
        html += escapeHtml((amount > 0 ? "+" : "") + amount);
        html += '</button> ';
    });

    html += '<button type="button" class="btn btn-danger btn-sm" onclick="submitDeleteEnemy(\'' + escapeHtml(id) + '\')">';
    html += '<i class="mdi mdi-delete"></i> Remove';
    html += '</button>';

    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</li>';

    return html;
}

function submitHiddenPost(fields) {
    var form = document.createElement("form");
    form.method = "post";
    form.style.display = "none";

    for (var key in fields) {
        if (fields.hasOwnProperty(key)) {
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

function submitEnemyHp(enemyId, change) {
    submitHiddenPost({
        game_id: GAME_ID,
        action: "adjust_enemy_hp",
        enemy_id: enemyId,
        change: change
    });
}

function submitDeleteEnemy(enemyId) {
    if (!confirm("Remove this temporary enemy?")) {
        return;
    }

    submitHiddenPost({
        game_id: GAME_ID,
        action: "delete_enemy",
        enemy_id: enemyId
    });
}

function renderBattle(data) {
    var list = document.getElementById("battleOrderList");

    if (!list) {
        return;
    }

    if (isTypingInBattleList() || battleFormDirty || battleFormSubmitting) {
        return;
    }

    var savedRolls = getCurrentRollValues();

    var characters = normalizeCharacters(data.characters || []);
    var battle = data.battle || {};
    var enemies = Array.isArray(battle.enemies) ? battle.enemies : [];
    var order = Array.isArray(battle.order) ? battle.order : [];

    var display = buildDisplayEntries(characters, enemies, order);
    var entries = display.entries;
    var characterMap = display.characterMap;
    var enemyMap = display.enemyMap;

    var html = "";

    if (!entries.length) {
        html = '<li class="list-group-item text-muted">No characters or enemies available.</li>';
    }

    for (var i = 0; i < entries.length; i++) {
        var entry = entries[i];

        if (entry.type === "character" && characterMap[entry.id]) {
            html += renderCharacterCard(characterMap[entry.id], entry.id, i, savedRolls, entry.roll);
        }

        if (entry.type === "enemy" && enemyMap[entry.id]) {
            html += renderEnemyCard(enemyMap[entry.id], entry.id, i, savedRolls, entry.roll);
        }
    }

    list.innerHTML = html;
}

function refreshBattle() {
    var liveStatus = document.getElementById("liveStatus");

    fetch("/api/dnd/game-state.php?_=" + Date.now(), {
        cache: "no-store"
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (!data.success) {
            if (liveStatus) {
                liveStatus.textContent = data.error || "No Live Data";
            }
            return;
        }

        if (data.game_id && data.game_id !== GAME_ID) {
            if (liveStatus) {
                liveStatus.textContent = "Active game mismatch";
            }
            return;
        }

        renderBattle(data);

        if (liveStatus && !battleFormDirty && !battleFormSubmitting) {
            liveStatus.textContent = "Updated " + new Date().toLocaleTimeString();
        }
    })
    .catch(function() {
        if (liveStatus) {
            liveStatus.textContent = "Offline";
        }
    });
}

refreshBattle();
setInterval(refreshBattle, 3000);
</script>

<?php
include("inc.footer.php");
?>
