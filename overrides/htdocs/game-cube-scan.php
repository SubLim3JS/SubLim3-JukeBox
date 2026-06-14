<?php
include("inc.header.php");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";

$gameId = $_GET["game_id"] ?? $_POST["game_id"] ?? "";

if ($gameId === "") {
    header("Location: game-load.php");
    exit;
}

$gameId = basename($gameId);
$gamePath = $gamesDir . "/" . $gameId;
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

if (!is_dir($gamePath) || !file_exists($gameFile)) {
    die("Game not found.");
}

if (!file_exists($charactersFile)) {
    file_put_contents($charactersFile, json_encode([], JSON_PRETTY_PRINT));
}

$gameData = json_decode(file_get_contents($gameFile), true);
$characters = json_decode(file_get_contents($charactersFile), true);

if (!is_array($characters)) {
    $characters = [];
}

$message = "";
$messageType = "info";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cardId = trim($_POST["card_id"] ?? "");
    $characterId = trim($_POST["character_id"] ?? "");
    $cubeId = trim($_POST["cube_id"] ?? "");

    if ($cardId === "") {
        $message = "Card ID is required.";
        $messageType = "warning";
    } elseif ($characterId === "") {
        $message = "Please select a character.";
        $messageType = "warning";
    } else {
        $updated = false;

        foreach ($characters as &$character) {
            $id = $character["id"] ?? "";

            if ($id === $characterId) {
                $character["rfid_id"] = $cardId;

                if ($cubeId !== "") {
                    $character["cube_id"] = $cubeId;
                }

                $updated = true;
                break;
            }
        }

        unset($character);

        if ($updated) {
            file_put_contents(
                $charactersFile,
                json_encode($characters, JSON_PRETTY_PRINT)
            );

            $message = "Character registration saved.";
            $messageType = "success";
        } else {
            $message = "Selected character was not found.";
            $messageType = "danger";
        }
    }
}

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
        <i class="mdi mdi-nfc"></i>
        Cube Registration
    </h1>

    <p class="lead">
        Assign an RFID character card and optional cube ID to a character.
    </p>

    <p>
        <a class="btn btn-default" href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
            <i class="mdi mdi-arrow-left"></i>
            Back to Dashboard
        </a>
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Register Character Card</strong>
        </div>

        <div class="panel-body">

            <form method="post" action="game-cube-scan.php">

                <input type="hidden" name="game_id" value="<?= htmlspecialchars($gameId) ?>">

                <div class="form-group">
                    <label for="card_id">RFID Card ID</label>
                    <input
                        id="card_id"
                        class="form-control input-lg"
                        type="text"
                        name="card_id"
                        placeholder="Tap card, then enter/paste RFID ID here"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <p class="help-block">
                        For now, this field accepts the RFID/card ID manually. Next we can make this auto-fill from the Book RFID reader.
                    </p>
                </div>

                <div class="form-group">
                    <label for="character_id">Assign to Character</label>
                    <select id="character_id" name="character_id" class="form-control input-lg" required>
                        <option value="">Select Character</option>

                        <?php foreach ($characters as $character): ?>
                            <?php
                                $id = $character["id"] ?? "";
                                $name =
    $character["name"] ??
    $character["character_name"] ??
    $character["player_name"] ??
    $character["code"] ??
    "Unknown";
                                $rfid = $character["rfid_id"] ?? "";
                                $cube = $character["cube_id"] ?? "";
                            ?>

                            <option value="<?= htmlspecialchars($id) ?>">
                                <?= htmlspecialchars($name) ?>
                                <?php if ($rfid !== ""): ?>
                                    — RFID: <?= htmlspecialchars($rfid) ?>
                                <?php endif; ?>
                                <?php if ($cube !== ""): ?>
                                    — Cube: <?= htmlspecialchars($cube) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="cube_id">Cube ID Optional</label>
                    <input
                        id="cube_id"
                        class="form-control input-lg"
                        type="text"
                        name="cube_id"
                        placeholder="Example: cube-001"
                        autocomplete="off"
                    >
                    <p class="help-block">
                        This can be used later by the ESP32 Feather to identify itself.
                    </p>
                </div>

                <button class="btn btn-primary btn-lg" type="submit">
                    <i class="mdi mdi-content-save"></i>
                    Save Registration
                </button>

            </form>

        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Current Registrations</strong>
        </div>

        <div class="panel-body">

            <?php if (count($characters) === 0): ?>
                <div class="alert alert-warning">
                    No characters have been added to this campaign yet.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Character</th>
                                <th>RFID Card ID</th>
                                <th>Cube ID</th>
                                <th>HP</th>
                                <th>Temp HP</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($characters as $character): ?>
                                <tr>
                                    <?= htmlspecialchars(
    $character["name"] ??
    $character["character_name"] ??
    $character["player_name"] ??
    $character["code"] ??
    "Unknown"
) ?>
                                    <td><?= htmlspecialchars($character["rfid_id"] ?? "Not Registered") ?></td>
                                    <td><?= htmlspecialchars($character["cube_id"] ?? "Not Assigned") ?></td>
                                    <td>
                                        <?= htmlspecialchars($character["hp"] ?? "0") ?>
                                        /
                                        <?= htmlspecialchars($character["max_hp"] ?? "0") ?>
                                    </td>
                                    <td><?= htmlspecialchars($character["temp_hp"] ?? "0") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php
include("inc.footer.php");
?>
