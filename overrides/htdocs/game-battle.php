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

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-format-list-numbered"></i>
                Attack Order
            </strong>
        </div>

        <div class="panel-body">

            <form method="post" id="orderForm">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="battle_order_json" id="battle_order_json" value="">

                <p class="text-muted">
                    Drag and drop characters or enemies to set the attack order.
                </p>

                <ul id="battleOrderList" class="list-group">

                    <?php
                    $displayed = [];

                    foreach ($battle["order"] as $entry):
                        $type = $entry["type"] ?? "";
                        $id = $entry["id"] ?? "";
                        $key = $type . ":" . $id;

                        $displayName = "";
                        $displaySub = "";
                        $labelClass = "label-default";

                        if ($type === "character" && isset($characterMap[$id])) {
                            $displayName = $characterMap[$id]["character_name"] ?? "Character";
                            $displaySub = "Player: " . ($characterMap[$id]["player_name"] ?? "");
                            $labelClass = "label-primary";
                        }

                        if ($type === "enemy" && isset($enemyMap[$id])) {
                            $displayName = $enemyMap[$id]["name"] ?? "Enemy";
                            $displaySub = "Enemy HP: " . ($enemyMap[$id]["hp"] ?? 0) . "/" . ($enemyMap[$id]["max_hp"] ?? 0);
                            $labelClass = "label-danger";
                        }

                        if ($displayName === "") {
                            continue;
                        }

                        $displayed[$key] = true;
                    ?>

                        <li class="list-group-item"
                            data-type="<?= htmlspecialchars($type) ?>"
                            data-id="<?= htmlspecialchars($id) ?>"
                            style="cursor:move;">
                            <span class="label <?= htmlspecialchars($labelClass) ?>">
                                <?= htmlspecialchars(strtoupper($type)) ?>
                            </span>
                            <strong style="margin-left:10px;">
                                <?= htmlspecialchars($displayName) ?>
                            </strong>
                            <br>
                            <small style="margin-left:85px;">
                                <?= htmlspecialchars($displaySub) ?>
                            </small>
                        </li>

                    <?php endforeach; ?>

                    <?php foreach ($characters as $index => $character): ?>
                        <?php
                        $characterId = $character["character_id"] ?? ("character_" . $index);
                        $key = "character:" . $characterId;

                        if (isset($displayed[$key])) {
                            continue;
                        }
                        ?>

                        <li class="list-group-item"
                            data-type="character"
                            data-id="<?= htmlspecialchars($characterId) ?>"
                            style="cursor:move;">
                            <span class="label label-primary">CHARACTER</span>
                            <strong style="margin-left:10px;">
                                <?= htmlspecialchars($character["character_name"] ?? "Character") ?>
                            </strong>
                            <br>
                            <small style="margin-left:85px;">
                                Player: <?= htmlspecialchars($character["player_name"] ?? "") ?>
                            </small>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($battle["enemies"] as $enemy): ?>
                        <?php
                        $enemyId = $enemy["enemy_id"] ?? "";
                        $key = "enemy:" . $enemyId;

                        if ($enemyId === "" || isset($displayed[$key])) {
                            continue;
                        }
                        ?>

                        <li class="list-group-item"
                            data-type="enemy"
                            data-id="<?= htmlspecialchars($enemyId) ?>"
                            style="cursor:move;">
                            <span class="label label-danger">ENEMY</span>
                            <strong style="margin-left:10px;">
                                <?= htmlspecialchars($enemy["name"] ?? "Enemy") ?>
                            </strong>
                            <br>
                            <small style="margin-left:85px;">
                                Enemy HP: <?= htmlspecialchars(($enemy["hp"] ?? 0) . "/" . ($enemy["max_hp"] ?? 0)) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>

                </ul>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="mdi mdi-content-save"></i>
                    Save Attack Order
                </button>
            </form>

        </div>
    </div>

    <div class="panel panel-danger">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-skull"></i>
                Temporary Enemies
            </strong>
        </div>

        <div class="panel-body">

            <?php if (empty($battle["enemies"])): ?>

                <div class="alert alert-info">
                    No temporary enemies added.
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Enemy</th>
                                <th>HP</th>
                                <th>Adjust HP</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($battle["enemies"] as $enemy): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($enemy["name"] ?? "Enemy") ?></strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(($enemy["hp"] ?? 0) . "/" . ($enemy["max_hp"] ?? 0)) ?>
                                    </td>

                                    <td>
                                        <?php foreach ([-5, -1, 1, 5] as $amount): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                                                <input type="hidden" name="action" value="adjust_enemy_hp">
                                                <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($enemy["enemy_id"] ?? "") ?>">
                                                <input type="hidden" name="change" value="<?= htmlspecialchars($amount) ?>">
                                                <button type="submit" class="btn btn-default btn-sm">
                                                    <?= htmlspecialchars(($amount > 0 ? "+" : "") . $amount) ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    </td>

                                    <td>
                                        <form method="post"
                                              style="display:inline;"
                                              onsubmit="return confirm('Remove this temporary enemy?');">
                                            <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                                            <input type="hidden" name="action" value="delete_enemy">
                                            <input type="hidden" name="enemy_id" value="<?= htmlspecialchars($enemy["enemy_id"] ?? "") ?>">

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="mdi mdi-delete"></i>
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

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

    if (list) {
        Sortable.create(list, {
            animation: 150
        });
    }

    if (form && hiddenInput && list) {
        form.addEventListener("submit", function () {
            var order = [];
            var items = list.querySelectorAll("li");

            items.forEach(function (item) {
                order.push({
                    type: item.getAttribute("data-type"),
                    id: item.getAttribute("data-id")
                });
            });

            hiddenInput.value = JSON.stringify(order);
        });
    }
});
</script>

<?php
include("inc.footer.php");
?>
