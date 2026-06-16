<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gameDir = $baseDir . "/games/" . basename($gameId);

$gameFile = $gameDir . "/game.json";
$charactersFile = $gameDir . "/characters.json";
$battleFile = $gameDir . "/battle.json";

if ($gameId === "" || !file_exists($gameFile)) {
    html_bootstrap3_createHeader("en", "Battle Mode", $conf['base_url']);
    echo "<body><div class='container'><h2>Campaign not found.</h2></div></body></html>";
    exit;
}

if (!file_exists($charactersFile)) {
    file_put_contents($charactersFile, json_encode([], JSON_PRETTY_PRINT));
}

if (!file_exists($battleFile)) {
    file_put_contents($battleFile, json_encode([
        "attack_order" => [],
        "temp_enemies" => []
    ], JSON_PRETTY_PRINT));
}

$game = json_decode(file_get_contents($gameFile), true);
$characters = json_decode(file_get_contents($charactersFile), true);
$battle = json_decode(file_get_contents($battleFile), true);

if (!is_array($characters)) {
    $characters = [];
}

if (!is_array($battle)) {
    $battle = [
        "attack_order" => [],
        "temp_enemies" => []
    ];
}

if (!isset($battle["attack_order"]) || !is_array($battle["attack_order"])) {
    $battle["attack_order"] = [];
}

if (!isset($battle["temp_enemies"]) || !is_array($battle["temp_enemies"])) {
    $battle["temp_enemies"] = [];
}

function saveBattle($battleFile, $battle)
{
    file_put_contents($battleFile, json_encode($battle, JSON_PRETTY_PRINT));
}

function saveCharacters($charactersFile, $characters)
{
    file_put_contents($charactersFile, json_encode($characters, JSON_PRETTY_PRINT));
}

function redirectBattle($gameId)
{
    header("Location: game-battle.php?game_id=" . urlencode($gameId));
    exit;
}

function getCombatantName($id, $characters, $battle)
{
    if (isset($characters[$id])) {
        return $characters[$id]["character_name"] ?? $characters[$id]["player_name"] ?? $id;
    }

    if (isset($battle["temp_enemies"][$id])) {
        return $battle["temp_enemies"][$id]["name"] ?? $id;
    }

    return $id;
}

function getCombatantInitiative($id, $characters, $battle)
{
    if (isset($characters[$id])) {
        return intval($characters[$id]["initiative"] ?? 0);
    }

    if (isset($battle["temp_enemies"][$id])) {
        return intval($battle["temp_enemies"][$id]["initiative"] ?? 0);
    }

    return 0;
}

function getCombatantType($id, $characters, $battle)
{
    if (isset($characters[$id])) {
        return "character";
    }

    if (isset($battle["temp_enemies"][$id])) {
        return "enemy";
    }

    return "unknown";
}

/*
    POST actions
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    /*
        Add temp enemy
    */
    if ($action === "add_enemy") {
        $enemyName = trim($_POST["enemy_name"] ?? "");
        $enemyHp = intval($_POST["enemy_hp"] ?? 0);
        $enemyInitiative = intval($_POST["enemy_initiative"] ?? 0);

        if ($enemyName !== "") {
            $enemyId = "enemy_" . time() . "_" . rand(1000, 9999);

            $battle["temp_enemies"][$enemyId] = [
                "id" => $enemyId,
                "name" => $enemyName,
                "max_hp" => $enemyHp,
                "hp" => $enemyHp,
                "temp_hp" => 0,
                "initiative" => $enemyInitiative
            ];

            if (!in_array($enemyId, $battle["attack_order"], true)) {
                $battle["attack_order"][] = $enemyId;
            }

            saveBattle($battleFile, $battle);
        }

        redirectBattle($gameId);
    }

    /*
        Delete temp enemy
    */
    if ($action === "delete_enemy") {
        $enemyId = $_POST["enemy_id"] ?? "";

        if (isset($battle["temp_enemies"][$enemyId])) {
            unset($battle["temp_enemies"][$enemyId]);
        }

        $battle["attack_order"] = array_values(array_filter(
            $battle["attack_order"],
            function ($id) use ($enemyId) {
                return $id !== $enemyId;
            }
        ));

        saveBattle($battleFile, $battle);
        redirectBattle($gameId);
    }

    /*
        Update enemy HP
    */
    if ($action === "update_enemy_hp") {
        $enemyId = $_POST["enemy_id"] ?? "";
        $amount = intval($_POST["amount"] ?? 0);
        $mode = $_POST["mode"] ?? "";

        if (isset($battle["temp_enemies"][$enemyId])) {
            if ($mode === "add") {
                $battle["temp_enemies"][$enemyId]["hp"] += $amount;
            } elseif ($mode === "subtract") {
                $battle["temp_enemies"][$enemyId]["hp"] -= $amount;
            } elseif ($mode === "set") {
                $battle["temp_enemies"][$enemyId]["hp"] = $amount;
            }

            if ($battle["temp_enemies"][$enemyId]["hp"] < 0) {
                $battle["temp_enemies"][$enemyId]["hp"] = 0;
            }
        }

        saveBattle($battleFile, $battle);
        redirectBattle($gameId);
    }

    /*
        Save attack order from drag/drop
    */
    if ($action === "save_attack_order") {
        $orderJson = $_POST["attack_order"] ?? "[]";
        $newOrder = json_decode($orderJson, true);

        if (is_array($newOrder)) {
            $cleanOrder = [];

            foreach ($newOrder as $id) {
                if (isset($characters[$id]) || isset($battle["temp_enemies"][$id])) {
                    $cleanOrder[] = $id;
                }
            }

            $battle["attack_order"] = $cleanOrder;
            saveBattle($battleFile, $battle);
        }

        echo json_encode(["success" => true]);
        exit;
    }

    /*
        Sort attack order by entered D20 initiative values.

        This is the important fix:
        - It reads the values typed into the page.
        - It saves those values.
        - It sorts by those values.
        - It does NOT reset them to 20, 19, 18, etc.
    */
    if ($action === "sort_attack_order") {
        $initiativeValues = $_POST["initiative"] ?? [];

        foreach ($initiativeValues as $id => $value) {
            $initiative = intval($value);

            if (isset($characters[$id])) {
                $characters[$id]["initiative"] = $initiative;
            }

            if (isset($battle["temp_enemies"][$id])) {
                $battle["temp_enemies"][$id]["initiative"] = $initiative;
            }
        }

        $allCombatantIds = [];

        foreach ($characters as $characterId => $character) {
            $allCombatantIds[] = $characterId;
        }

        foreach ($battle["temp_enemies"] as $enemyId => $enemy) {
            $allCombatantIds[] = $enemyId;
        }

        usort($allCombatantIds, function ($a, $b) use ($characters, $battle) {
            $aInit = getCombatantInitiative($a, $characters, $battle);
            $bInit = getCombatantInitiative($b, $characters, $battle);

            if ($aInit === $bInit) {
                $aName = getCombatantName($a, $characters, $battle);
                $bName = getCombatantName($b, $characters, $battle);
                return strcasecmp($aName, $bName);
            }

            return $bInit <=> $aInit;
        });

        $battle["attack_order"] = $allCombatantIds;

        saveCharacters($charactersFile, $characters);
        saveBattle($battleFile, $battle);

        redirectBattle($gameId);
    }

    /*
        Move current turn to bottom
    */
    if ($action === "next_turn") {
        if (count($battle["attack_order"]) > 1) {
            $current = array_shift($battle["attack_order"]);
            $battle["attack_order"][] = $current;
            saveBattle($battleFile, $battle);
        }

        redirectBattle($gameId);
    }
}

/*
    Ensure attack order includes all current characters and enemies.
    Do not overwrite initiative values.
*/
foreach ($characters as $characterId => $character) {
    if (!in_array($characterId, $battle["attack_order"], true)) {
        $battle["attack_order"][] = $characterId;
    }

    if (!isset($characters[$characterId]["initiative"])) {
        $characters[$characterId]["initiative"] = 0;
    }
}

foreach ($battle["temp_enemies"] as $enemyId => $enemy) {
    if (!in_array($enemyId, $battle["attack_order"], true)) {
        $battle["attack_order"][] = $enemyId;
    }

    if (!isset($battle["temp_enemies"][$enemyId]["initiative"])) {
        $battle["temp_enemies"][$enemyId]["initiative"] = 0;
    }
}

/*
    Remove deleted/missing combatants from attack order.
*/
$battle["attack_order"] = array_values(array_filter(
    $battle["attack_order"],
    function ($id) use ($characters, $battle) {
        return isset($characters[$id]) || isset($battle["temp_enemies"][$id]);
    }
));

saveCharacters($charactersFile, $characters);
saveBattle($battleFile, $battle);

html_bootstrap3_createHeader(
    "en",
    "Battle Mode",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : ""); ?>">

<?php
include("inc.navigation.php");
?>

<div class="container">

    <div class="row">
        <div class="col-lg-12">
            <h1>
                <i class="mdi mdi-sword-cross"></i>
                Battle Mode
            </h1>

            <p>
                Campaign:
                <strong><?php echo htmlspecialchars($game["game_name"] ?? $gameId); ?></strong>
            </p>

            <p>
                <a href="game-dashboard.php?game_id=<?php echo urlencode($gameId); ?>" class="btn btn-default">
                    <i class="mdi mdi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </p>
        </div>
    </div>

    <!-- Attack Order -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-format-list-numbered"></i>
                Attack Order
            </strong>
        </div>

        <div class="panel-body">

            <form method="post" id="initiativeForm">
                <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($gameId); ?>">
                <input type="hidden" name="action" value="sort_attack_order">

                <div id="attackOrderList">

                    <?php foreach ($battle["attack_order"] as $combatantId): ?>

                        <?php
                        $type = getCombatantType($combatantId, $characters, $battle);
                        $name = getCombatantName($combatantId, $characters, $battle);
                        $initiative = getCombatantInitiative($combatantId, $characters, $battle);

                        if ($type === "character") {
                            $c = $characters[$combatantId];

                            $hp = intval($c["hp"] ?? 0);
                            $maxHp = intval($c["max_hp"] ?? 0);
                            $tempHp = intval($c["temp_hp"] ?? 0);
                            $deathSuccess = intval($c["death_success"] ?? 0);
                            $deathFail = intval($c["death_fail"] ?? 0);
                        } elseif ($type === "enemy") {
                            $e = $battle["temp_enemies"][$combatantId];

                            $hp = intval($e["hp"] ?? 0);
                            $maxHp = intval($e["max_hp"] ?? 0);
                            $tempHp = intval($e["temp_hp"] ?? 0);
                            $deathSuccess = 0;
                            $deathFail = 0;
                        } else {
                            continue;
                        }
                        ?>

                        <div class="panel panel-default attack-card" data-id="<?php echo htmlspecialchars($combatantId); ?>">
                            <div class="panel-body">

                                <div class="row">
                                    <div class="col-sm-1 text-center">
                                        <span class="drag-handle" style="cursor: move; font-size: 22px;">
                                            <i class="mdi mdi-drag"></i>
                                        </span>
                                    </div>

                                    <div class="col-sm-3">
                                        <h4 style="margin-top: 5px;">
                                            <?php echo htmlspecialchars($name); ?>

                                            <?php if ($type === "enemy"): ?>
                                                <span class="label label-danger">Enemy</span>
                                            <?php else: ?>
                                                <span class="label label-info">Player</span>
                                            <?php endif; ?>
                                        </h4>
                                    </div>

                                    <div class="col-sm-2">
                                        <label>D20 Roll</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            name="initiative[<?php echo htmlspecialchars($combatantId); ?>]"
                                            value="<?php echo htmlspecialchars($initiative); ?>"
                                        >
                                    </div>

                                    <div class="col-sm-2">
                                        <label>HP</label>
                                        <div>
                                            <strong><?php echo htmlspecialchars($hp); ?></strong>
                                            /
                                            <?php echo htmlspecialchars($maxHp); ?>
                                        </div>
                                    </div>

                                    <div class="col-sm-2">
                                        <label>Temp HP</label>
                                        <div>
                                            <strong><?php echo htmlspecialchars($tempHp); ?></strong>
                                        </div>
                                    </div>

                                    <div class="col-sm-2">
                                        <label>Death Saves</label>
                                        <div>
                                            S:
                                            <strong><?php echo htmlspecialchars($deathSuccess); ?></strong>
                                            /
                                            F:
                                            <strong><?php echo htmlspecialchars($deathFail); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($type === "enemy"): ?>
                                    <hr>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <form method="post" class="form-inline" style="display:inline-block;">
                                                <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($gameId); ?>">
                                                <input type="hidden" name="action" value="update_enemy_hp">
                                                <input type="hidden" name="enemy_id" value="<?php echo htmlspecialchars($combatantId); ?>">

                                                <div class="form-group">
                                                    <input type="number" name="amount" class="form-control" placeholder="HP" style="width: 90px;">
                                                </div>

                                                <button type="submit" name="mode" value="add" class="btn btn-success btn-sm">
                                                    + HP
                                                </button>

                                                <button type="submit" name="mode" value="subtract" class="btn btn-warning btn-sm">
                                                    - HP
                                                </button>

                                                <button type="submit" name="mode" value="set" class="btn btn-info btn-sm">
                                                    Set HP
                                                </button>
                                            </form>

                                            <form method="post" style="display:inline-block; margin-left: 10px;">
                                                <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($gameId); ?>">
                                                <input type="hidden" name="action" value="delete_enemy">
                                                <input type="hidden" name="enemy_id" value="<?php echo htmlspecialchars($combatantId); ?>">

                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this enemy?');">
                                                    Delete Enemy
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-sort-descending"></i>
                    Sort Attack Order
                </button>

                <button
                    type="submit"
                    class="btn btn-success"
                    onclick="document.getElementById('nextTurnAction').name='action'; document.getElementById('nextTurnAction').value='next_turn';"
                >
                    <i class="mdi mdi-skip-next"></i>
                    Next
                </button>

                <input type="hidden" id="nextTurnAction" value="">
            </form>

        </div>
    </div>

    <!-- Add Temp Enemy -->
    <div class="panel panel-danger">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-account-alert"></i>
                Add Temporary Enemy
            </strong>
        </div>

        <div class="panel-body">
            <form method="post">
                <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($gameId); ?>">
                <input type="hidden" name="action" value="add_enemy">

                <div class="row">
                    <div class="col-sm-4">
                        <label>Enemy Name</label>
                        <input type="text" name="enemy_name" class="form-control" required>
                    </div>

                    <div class="col-sm-3">
                        <label>HP</label>
                        <input type="number" name="enemy_hp" class="form-control" value="10">
                    </div>

                    <div class="col-sm-3">
                        <label>D20 Roll</label>
                        <input type="number" name="enemy_initiative" class="form-control" value="0">
                    </div>

                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-danger btn-block">
                            Add Enemy
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(function () {
    $("#attackOrderList").sortable({
        handle: ".drag-handle",
        update: function () {
            var order = [];

            $(".attack-card").each(function () {
                order.push($(this).data("id"));
            });

            $.post("game-battle.php?game_id=<?php echo urlencode($gameId); ?>", {
                action: "save_attack_order",
                game_id: "<?php echo htmlspecialchars($gameId); ?>",
                attack_order: JSON.stringify(order)
            });
        }
    });
});
</script>

</body>
</html>
