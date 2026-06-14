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
        $character["character_id"] = "character_" . $index;
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

    if ($action === "save_order") {
        $orderJson = $_POST["battle_order_json"] ?? "";
        $decodedOrder = json_decode($orderJson, true);

        $newOrder = [];

        if (is_array($decodedOrder)) {
            foreach ($decodedOrder as $entry) {
                $type = $entry["type"] ?? "";
                $id = $entry["id"] ?? "";

                if (($type === "character" || $type === "enemy") && $id !== "") {
                    $newOrder[] = [
                        "type" => $type,
                        "id" => $id
                    ];
                }
            }
        }

        $battle["order"] = $newOrder;
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
    $characterId = $character["character_id"] ?? ("character_" . $index);
    $characterMap[$characterId] = $character;
}

$enemyMap = [];
foreach ($battle["enemies"] as $enemy) {
    $enemyId = $enemy["enemy_id"] ?? "";
    if ($enemyId !== "") {
        $enemyMap[$enemyId] = $enemy;
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
        </div>

        <div class="panel-body">

            <form method="post" id="orderForm" style="display:none;">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="battle_order_json" id="battle_order_json" value="">
            </form>

            <p class="text-muted">
                Drag and drop characters or enemies to change the attack order. Changes save automatically.
            </p>

            <ul id="battleOrderList" class="list-group">

                <?php
                $displayed = [];

                foreach ($battle["order"] as $entry):
                    $type = $entry["type"] ?? "";
                    $id = $entry["id"] ?? "";
                    $key = $type . ":" . $id;

                    if ($type === "character" && isset($characterMap[$id])):
                        $c = $characterMap[$id];

                        $characterName = $c["character_name"] ?? "Character";
                        $playerName = $c["player_name"] ?? "";
                        $hp = $c["hp"] ?? 0;
                        $maxHp = $c["max_hp"] ?? 0;
                        $tempHp = $c["temp_hp"] ?? 0;
                        $deathSuccess = $c["death_success"] ?? 0;
                        $deathFail = $c["death_fail"] ?? 0;
                        $cubeId = $c["cube_id"] ?? "";

                        $displayed[$key] = true;
                ?>

                    <li class="list-group-item"
                        data-type="character"
                        data-id="<?= htmlspecialchars($id) ?>"
                        style="cursor:move;">
                        <span class="label label-primary">CHARACTER</span>

                        <strong style="margin-left:10px;">
                            <?= htmlspecialchars($characterName) ?>
                        </strong>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-sm-2">
                                <strong>Player</strong><br>
                                <?= htmlspecialchars($playerName) ?>
                            </div>

                            <div class="col-sm-2">
                                <strong>HP</strong><br>
                                <?= htmlspecialchars($hp) ?>/<?= htmlspecialchars($maxHp) ?>
                            </div>

                            <div class="col-sm-2">
                                <strong>Temp HP</strong><br>
                                <?= htmlspecialchars($tempHp) ?>
                            </div>

                            <div class="col-sm-3">
                                <strong>Death Saves</strong><br>
                                Success <?= htmlspecialchars($deathSuccess) ?>/3<br>
                                Fail <?= htmlspecialchars($deathFail) ?>/3
                            </div>

                            <div class="col-sm-3">
                                <strong>Cube</strong><br>
                                <?php if ($cubeId !== ""): ?>
                                    <span class="label label-success">
                                        <?= htmlspecialchars($cubeId) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="label label-default">
                                        Not assigned
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>

                <?php
                    endif;

                    if ($type === "enemy" && isset($enemyMap[$id])):
                        $enemy = $enemyMap[$id];

                        $enemyName = $enemy["name"] ?? "Enemy";
                        $enemyHp = $enemy["hp"] ?? 0;
                        $enemyMaxHp = $enemy["max_hp"] ?? 0;

                        $displayed[$key] = true;
                ?>

                    <li class="list-group-item"
                        data-type="enemy"
                        data-id="<?= htmlspecialchars($id) ?>"
                        style="cursor:move;">
                        <span class="label label-danger">ENEMY</span>

                        <strong style="margin-left:10px;">
                            <?= htmlspecialchars($enemyName) ?>
                        </strong>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-sm-3">
                                <strong>HP</strong><br>
                                <?= htmlspecialchars($enemyHp) ?>/<?= htmlspecialchars($enemyMaxHp) ?>
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
                    </li>

                <?php
                    endif;
                endforeach;
                ?>

                <?php foreach ($characters as $index => $c): ?>
                    <?php
                    $characterId = $c["character_id"] ?? ("character_" . $index);
                    $key = "character:" . $characterId;

                    if (isset($displayed[$key])) {
                        continue;
                    }

                    $characterName = $c["character_name"] ?? "Character";
                    $playerName = $c["player_name"] ?? "";
                    $hp = $c["hp"] ?? 0;
                    $maxHp = $c["max_hp"] ?? 0;
                    $tempHp = $c["temp_hp"] ?? 0;
                    $deathSuccess = $c["death_success"] ?? 0;
                    $deathFail = $c["death_fail"] ?? 0;
                    $cubeId = $c["cube_id"] ?? "";
                    ?>

                    <li class="list-group-item"
                        data-type="character"
                        data-id="<?= htmlspecialchars($characterId) ?>"
                        style="cursor:move;">
                        <span class="label label-primary">CHARACTER</span>

                        <strong style="margin-left:10px;">
                            <?= htmlspecialchars($characterName) ?>
                        </strong>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-sm-2">
                                <strong>Player</strong><br>
                                <?= htmlspecialchars($playerName) ?>
                            </div>

                            <div class="col-sm-2">
                                <strong>HP</strong><br>
                                <?= htmlspecialchars($hp) ?>/<?= htmlspecialchars($maxHp) ?>
                            </div>

                            <div class="col-sm-2">
                                <strong>Temp HP</strong><br>
                                <?= htmlspecialchars($tempHp) ?>
                            </div>

                            <div class="col-sm-3">
                                <strong>Death Saves</strong><br>
                                Success <?= htmlspecialchars($deathSuccess) ?>/3<br>
                                Fail <?= htmlspecialchars($deathFail) ?>/3
                            </div>

                            <div class="col-sm-3">
                                <strong>Cube</strong><br>
                                <?php if ($cubeId !== ""): ?>
                                    <span class="label label-success">
                                        <?= htmlspecialchars($cubeId) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="label label-default">
                                        Not assigned
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>

                <?php foreach ($battle["enemies"] as $enemy): ?>
                    <?php
                    $enemyId = $enemy["enemy_id"] ?? "";
                    $key = "enemy:" . $enemyId;

                    if ($enemyId === "" || isset($displayed[$key])) {
                        continue;
                    }

                    $enemyName = $enemy["name"] ?? "Enemy";
                    $enemyHp = $enemy["hp"] ?? 0;
                    $enemyMaxHp = $enemy["max_hp"] ?? 0;
                    ?>

                    <li class="list-group-item"
                        data-type="enemy"
                        data-id="<?= htmlspecialchars($enemyId) ?>"
                        style="cursor:move;">
                        <span class="label label-danger">ENEMY</span>

                        <strong style="margin-left:10px;">
                            <?= htmlspecialchars($enemyName) ?>
                        </strong>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-sm-3">
                                <strong>HP</strong><br>
                                <?= htmlspecialchars($enemyHp) ?>/<?= htmlspecialchars($enemyMaxHp) ?>
                            </div>

                            <div class="col-sm-9">
                                <strong>Adjust HP</strong><br>

                                <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                                        <input type="hidden" name="action" value="adjust_enemy_hp">
                                        <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($enemyId) ?>">
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
                                    <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($enemyId) ?>">

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="mdi mdi-delete"></i>
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>

            </ul>

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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var list = document.getElementById("battleOrderList");
    var form = document.getElementById("orderForm");
    var hiddenInput = document.getElementById("battle_order_json");

    function saveBattleOrder() {
        if (!list || !form || !hiddenInput) {
            return;
        }

        var order = [];
        var items = list.querySelectorAll("li");

        items.forEach(function (item) {
            order.push({
                type: item.getAttribute("data-type"),
                id: item.getAttribute("data-id")
            });
        });

        hiddenInput.value = JSON.stringify(order);
        form.submit();
    }

    if (list) {
        Sortable.create(list, {
            animation: 150,
            filter: "button, input, form",
            preventOnFilter: false,
            onEnd: function () {
                saveBattleOrder();
            }
        });
    }
});
</script>

<script>
setTimeout(function () {
    window.location.reload();
}, 5000);
</script>

<?php
include("inc.footer.php");
?>
