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

function saveBattle($battleFile, $battle) {
    file_put_contents(
        $battleFile,
        json_encode($battle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
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
        $orderRaw = trim($_POST["battle_order"] ?? "");
        $newOrder = [];

        if ($orderRaw !== "") {
            $lines = preg_split("/\r\n|\n|\r/", $orderRaw);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line !== "") {
                    $parts = explode(":", $line, 2);

                    if (count($parts) === 2) {
                        $type = trim($parts[0]);
                        $id = trim($parts[1]);

                        if (($type === "character" || $type === "enemy") && $id !== "") {
                            $newOrder[] = [
                                "type" => $type,
                                "id" => $id
                            ];
                        }
                    }
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

            <?php if (empty($battle["order"])): ?>

                <div class="alert alert-info">
                    No attack order has been set yet.
                </div>

            <?php else: ?>

                <ol class="list-group">
                    <?php foreach ($battle["order"] as $entry): ?>
                        <?php
                        $type = $entry["type"] ?? "";
                        $id = $entry["id"] ?? "";

                        $displayName = "";
                        $displaySub = "";

                        if ($type === "character" && isset($characterMap[$id])) {
                            $displayName = $characterMap[$id]["character_name"] ?? "Character";
                            $displaySub = "Player: " . ($characterMap[$id]["player_name"] ?? "");
                        }

                        if ($type === "enemy" && isset($enemyMap[$id])) {
                            $displayName = $enemyMap[$id]["name"] ?? "Enemy";
                            $displaySub = "Enemy HP: " . ($enemyMap[$id]["hp"] ?? 0) . "/" . ($enemyMap[$id]["max_hp"] ?? 0);
                        }

                        if ($displayName === "") {
                            continue;
                        }
                        ?>

                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($displayName) ?></strong>
                            <br>
                            <small><?= htmlspecialchars($displaySub) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ol>

            <?php endif; ?>

            <hr>

            <form method="post">
                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">
                <input type="hidden" name="action" value="save_order">

                <p>
                    Edit the attack order below. One entry per line.
                </p>

                <textarea name="battle_order" class="form-control" rows="10"><?php
foreach ($battle["order"] as $entry) {
    echo htmlspecialchars(($entry["type"] ?? "") . ":" . ($entry["id"] ?? "")) . "\n";
}
?></textarea>

                <br>

                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save"></i>
                    Save Attack Order
                </button>
            </form>

            <hr>

            <h4>Available Entries</h4>

            <div class="row">
                <div class="col-sm-6">
                    <h4>Characters</h4>
                    <ul>
                        <?php foreach ($characters as $index => $character): ?>
                            <?php $characterId = $character["character_id"] ?? ("character_" . $index); ?>
                            <li>
                                <code>character:<?= htmlspecialchars($characterId) ?></code>
                                —
                                <?= htmlspecialchars($character["character_name"] ?? "Character") ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-sm-6">
                    <h4>Enemies</h4>
                    <ul>
                        <?php foreach ($battle["enemies"] as $enemy): ?>
                            <li>
                                <code>enemy:<?= htmlspecialchars($enemy["enemy_id"] ?? "") ?></code>
                                —
                                <?= htmlspecialchars($enemy["name"] ?? "Enemy") ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

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

<?php
include("inc.footer.php");
?>
