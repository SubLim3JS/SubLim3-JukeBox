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
    file_put_contents(
        $battleFile,
        json_encode($battle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function getCharacterId($character, $index = 0) {
    return $character["character_id"]
        ?? $character["id"]
        ?? $character["code"]
        ?? ("character_" . $index);
}

function getCharacterName($character) {
    return $character["character_name"]
        ?? $character["name"]
        ?? "Character";
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

file_put_contents(
    $charactersFile,
    json_encode($characters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

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
                "id" => $enemyId
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

            $roll = cleanInt($orderValues[$key] ?? 1, 1);
            $roll = max(1, min($roll, 20));

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

            $roll = cleanInt($orderValues[$key] ?? 1, 1);
            $roll = max(1, min($roll, 20));

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
                "id" => $entry["id"]
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

$characterMap = [];
foreach ($characters as $index => $character) {
    $characterId = getCharacterId($character, $index);
    $characterMap[$characterId] = $character;
}

$enemyMap = [];
foreach ($battle["enemies"] as $enemy) {
    $enemyId = $enemy["enemy_id"] ?? "";
    if ($enemyId !== "") {
        $enemyMap[$enemyId] = $enemy;
    }
}

$displayEntries = [];
$displayed = [];

foreach ($battle["order"] as $entry) {
    $type = $entry["type"] ?? "";
    $id = $entry["id"] ?? "";
    $key = $type . ":" . $id;

    if ($type === "character" && isset($characterMap[$id])) {
        $displayEntries[] = [
            "type" => "character",
            "id" => $id
        ];
        $displayed[$key] = true;
    }

    if ($type === "enemy" && isset($enemyMap[$id])) {
        $displayEntries[] = [
            "type" => "enemy",
            "id" => $id
        ];
        $displayed[$key] = true;
    }
}

foreach ($characters as $index => $character) {
    $characterId = getCharacterId($character, $index);
    $key = "character:" . $characterId;

    if (!isset($displayed[$key])) {
        $displayEntries[] = [
            "type" => "character",
            "id" => $characterId
        ];
    }
}

foreach ($battle["enemies"] as $enemy) {
    $enemyId = $enemy["enemy_id"] ?? "";
    $key = "enemy:" . $enemyId;

    if ($enemyId !== "" && !isset($displayed[$key])) {
        $displayEntries[] = [
            "type" => "enemy",
            "id" => $enemyId
        ];
    }
}

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

                <ul id="battleOrderList" class="list-group">

                    <?php foreach ($displayEntries as $position => $entry): ?>
                        <?php
                        $type = $entry["type"];
                        $id = $entry["id"];
                        $key = $type . ":" . $id;
                        $orderNumber = 20 - $position;

                        if ($orderNumber < 1) {
                            $orderNumber = 1;
                        }
                        ?>

                        <?php if ($type === "character" && isset($characterMap[$id])): ?>
                            <?php
                            $c = $characterMap[$id];

                            $characterName = getCharacterName($c);
                            $playerName = $c["player_name"] ?? "";
                            $hp = $c["hp"] ?? $c["current_hp"] ?? 0;
                            $maxHp = $c["max_hp"] ?? 0;
                            $tempHp = $c["temp_hp"] ?? 0;
                            $deathSuccess = $c["death_success"] ?? $c["death_saves_success"] ?? 0;
                            $deathFail = $c["death_fail"] ?? $c["death_saves_fail"] ?? 0;
                            $cubeId = $c["cube_id"] ?? "";
                            ?>

                            <li class="list-group-item" data-type="character" data-id="<?= htmlspecialchars($id) ?>">
                                <div class="row">
                                    <div class="col-sm-2">
                                        <label>D20 Roll</label>
                                        <input
                                            type="number"
                                            class="form-control input-lg"
                                            name="order_value[<?= htmlspecialchars($key) ?>]"
                                            value="<?= htmlspecialchars($orderNumber) ?>"
                                            min="1"
                                            max="20"
                                        >
                                    </div>

                                    <div class="col-sm-10">
                                        <span class="label label-primary">CHARACTER</span>

                                        <strong style="margin-left:10px;">
                                            <?= htmlspecialchars($characterName) ?>
                                        </strong>

                                        <div class="row" style="margin-top:10px;">
                                            <div class="col-sm-2">
                                                <strong>Player</strong><br>
                                                <span data-live-type="character" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="player_name">
                                                    <?= htmlspecialchars($playerName) ?>
                                                </span>
                                            </div>

                                            <div class="col-sm-2">
                                                <strong>HP</strong><br>
                                                <span data-live-type="character" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="hp">
                                                    <?= htmlspecialchars($hp) ?>/<?= htmlspecialchars($maxHp) ?>
                                                </span>
                                            </div>

                                            <div class="col-sm-2">
                                                <strong>Temp HP</strong><br>
                                                <span data-live-type="character" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="temp_hp">
                                                    <?= htmlspecialchars($tempHp) ?>
                                                </span>
                                            </div>

                                            <div class="col-sm-3">
                                                <strong>Death Saves</strong><br>
                                                <span data-live-type="character" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="death_saves">
                                                    Success <?= htmlspecialchars($deathSuccess) ?>/3<br>
                                                    Fail <?= htmlspecialchars($deathFail) ?>/3
                                                </span>
                                            </div>

                                            <div class="col-sm-3">
                                                <strong>Cube</strong><br>
                                                <span data-live-type="character" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="cube">
                                                    <?php if ($cubeId !== ""): ?>
                                                        <span class="label label-success">
                                                            <?= htmlspecialchars($cubeId) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="label label-default">
                                                            Not assigned
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($type === "enemy" && isset($enemyMap[$id])): ?>
                            <?php
                            $enemy = $enemyMap[$id];

                            $enemyName = $enemy["name"] ?? "Enemy";
                            $enemyHp = $enemy["hp"] ?? 0;
                            $enemyMaxHp = $enemy["max_hp"] ?? 0;
                            ?>

                            <li class="list-group-item" data-type="enemy" data-id="<?= htmlspecialchars($id) ?>">
                                <div class="row">
                                    <div class="col-sm-2">
                                        <label>D20 Roll</label>
                                        <input
                                            type="number"
                                            class="form-control input-lg"
                                            name="order_value[<?= htmlspecialchars($key) ?>]"
                                            value="<?= htmlspecialchars($orderNumber) ?>"
                                            min="1"
                                            max="20"
                                        >
                                    </div>

                                    <div class="col-sm-10">
                                        <span class="label label-danger">ENEMY</span>

                                        <strong style="margin-left:10px;">
                                            <?= htmlspecialchars($enemyName) ?>
                                        </strong>

                                        <div class="row" style="margin-top:10px;">
                                            <div class="col-sm-3">
                                                <strong>HP</strong><br>
                                                <span data-live-type="enemy" data-live-id="<?= htmlspecialchars($id) ?>" data-live-field="hp">
                                                    <?= htmlspecialchars($enemyHp) ?>/<?= htmlspecialchars($enemyMaxHp) ?>
                                                </span>
                                            </div>

                                            <div class="col-sm-9">
                                                <strong>Adjust HP</strong><br>

                                                <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                                                        <input type="hidden" name="action" value="adjust_enemy_hp">
                                                        <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($id) ?>">
                                                        <input type="hidden" name="change" value="<?= htmlspecialchars($amount) ?>">

                                                        <button type="submit" class="btn btn-default btn-sm">
                                                            <?= htmlspecialchars(($amount > 0 ? "+" : "") . $amount) ?>
                                                        </button>
                                                    </form>
                                                <?php endforeach; ?>

                                                <form method="post"
                                                      style="display:inline;"
                                                      onsubmit="return confirm('Remove this temporary enemy?');">
                                                    <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                                                    <input type="hidden" name="action" value="delete_enemy">
                                                    <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($id) ?>">

                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="mdi mdi-delete"></i>
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>

                    <?php endforeach; ?>

                </ul>

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
const GAME_ID = <?= json_encode(basename($gameId)) ?>;

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getCharacterId(character, index) {
    return character.character_id || character.id || character.code || ("character_" + index);
}

function getNumber(obj, keys, fallback) {
    for (let i = 0; i < keys.length; i++) {
        if (obj[keys[i]] !== undefined && obj[keys[i]] !== null && obj[keys[i]] !== "") {
            return parseInt(obj[keys[i]], 10) || 0;
        }
    }

    return fallback || 0;
}

function setLiveHtml(type, id, field, html) {
    const el = document.querySelector(
        '[data-live-type="' + type + '"][data-live-id="' + CSS.escape(id) + '"][data-live-field="' + field + '"]'
    );

    if (el) {
        el.innerHTML = html;
    }
}

function refreshBattleStatsOnly() {
    fetch("game-live-state.php?game_id=" + encodeURIComponent(GAME_ID), {
        cache: "no-store"
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (!data.success) {
            return;
        }

        const characters = data.characters || [];
        const battle = data.battle || { enemies: [], order: [] };
        const enemies = battle.enemies || [];

        characters.forEach(function(character, index) {
            const id = getCharacterId(character, index);
            const playerName = character.player_name || "";
            const hp = getNumber(character, ["hp", "current_hp"], 0);
            const maxHp = getNumber(character, ["max_hp"], 0);
            const tempHp = getNumber(character, ["temp_hp"], 0);
            const deathSuccess = getNumber(character, ["death_success", "death_saves_success"], 0);
            const deathFail = getNumber(character, ["death_fail", "death_saves_fail"], 0);
            const cubeId = character.cube_id || "";

            setLiveHtml("character", id, "player_name", escapeHtml(playerName));
            setLiveHtml("character", id, "hp", escapeHtml(hp + "/" + maxHp));
            setLiveHtml("character", id, "temp_hp", escapeHtml(tempHp));
            setLiveHtml(
                "character",
                id,
                "death_saves",
                "Success " + escapeHtml(deathSuccess) + "/3<br>Fail " + escapeHtml(deathFail) + "/3"
            );

            if (cubeId !== "") {
                setLiveHtml("character", id, "cube", '<span class="label label-success">' + escapeHtml(cubeId) + '</span>');
            } else {
                setLiveHtml("character", id, "cube", '<span class="label label-default">Not assigned</span>');
            }
        });

        enemies.forEach(function(enemy) {
            const id = enemy.enemy_id || "";
            const hp = getNumber(enemy, ["hp"], 0);
            const maxHp = getNumber(enemy, ["max_hp"], 0);

            if (id !== "") {
                setLiveHtml("enemy", id, "hp", escapeHtml(hp + "/" + maxHp));
            }
        });

        const liveStatus = document.getElementById("liveStatus");

        if (liveStatus) {
            liveStatus.textContent = "Live";
        }
    })
    .catch(function() {
        const liveStatus = document.getElementById("liveStatus");

        if (liveStatus) {
            liveStatus.textContent = "Offline";
        }
    });
}

setInterval(refreshBattleStatsOnly, 5000);
</script>

<?php
include("inc.footer.php");
?>
