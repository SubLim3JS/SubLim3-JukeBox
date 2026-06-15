<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? "";

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$gamePath = $baseDir . "/" . basename($gameId);
$gameFile = $gamePath . "/game.json";
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !file_exists($gameFile)) {
    html_bootstrap3_createHeader("en", "Game Not Found | SubLim3 JukeBox", $conf['base_url']);
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

$gameName = $game["game_name"] ?? $gameId;
$created = $game["created"] ?? "Unknown";
$totalCharacters = count($characters);
$assignedCubes = 0;

foreach ($characters as $c) {
    if (!empty($c["cube_id"])) {
        $assignedCubes++;
    }
}

html_bootstrap3_createHeader(
    "en",
    "Campaign Dashboard | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-view-dashboard"></i>
        <?= htmlspecialchars($gameName) ?>
    </h1>

    <p class="lead">Campaign Dashboard</p>

    <a class="btn btn-danger btn-lg" href="game-battle.php?game_id=<?= urlencode($gameId) ?>" style="margin-bottom:20px;">
        <i class="mdi mdi-sword-cross"></i>
        Battle Mode
    </a>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-account-group"></i>
                Party Characters
            </strong>
            <span id="liveStatus" class="pull-right small">Live</span>
        </div>

        <div class="panel-body">

            <div id="noCharactersAlert" class="alert alert-info" style="<?= empty($characters) ? "" : "display:none;" ?>">
                No characters added yet. Add your first player character to begin.
            </div>

            <div id="charactersTableWrap" class="table-responsive" style="<?= empty($characters) ? "display:none;" : "" ?>">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Character</th>
                            <th>HP</th>
                            <th>Temp HP</th>
                            <th>Death Saves</th>
                            <th>Cube</th>
                        </tr>
                    </thead>
                    <tbody id="charactersTableBody">
                        <?php foreach ($characters as $c): ?>
                            <?php
                            $cubeId = $c["cube_id"] ?? "";
                            $deathSuccess = $c["death_success"] ?? $c["death_saves_success"] ?? 0;
                            $deathFail = $c["death_fail"] ?? $c["death_saves_fail"] ?? 0;
                            $hp = $c["hp"] ?? $c["current_hp"] ?? 0;
                            $maxHp = $c["max_hp"] ?? 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($c["player_name"] ?? "") ?></td>
                                <td><strong><?= htmlspecialchars($c["character_name"] ?? $c["name"] ?? "") ?></strong></td>
                                <td><?= htmlspecialchars($hp . "/" . $maxHp) ?></td>
                                <td><?= htmlspecialchars($c["temp_hp"] ?? 0) ?></td>
                                <td>
                                    Success <?= htmlspecialchars($deathSuccess) ?>/3<br>
                                    Fail <?= htmlspecialchars($deathFail) ?>/3
                                </td>
                                <td>
                                    <?php if ($cubeId !== ""): ?>
                                        <span class="label label-success"><?= htmlspecialchars($cubeId) ?></span>
                                    <?php else: ?>
                                        <span class="label label-default">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <a class="btn btn-primary btn-lg" href="game-character-add.php?game_id=<?= urlencode($gameId) ?>">
                <i class="mdi mdi-account-plus"></i>
                Add Character
            </a>

        </div>
    </div>

    <div class="row">

        <div class="col-sm-4">
            <div class="panel panel-primary text-center">
                <div class="panel-heading">
                    <strong>Characters</strong>
                </div>
                <div class="panel-body">
                    <div id="totalCharactersCount" style="font-size:42px;font-weight:bold;">
                        <?= htmlspecialchars($totalCharacters) ?>
                    </div>
                    <div>Total Players</div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="panel panel-primary text-center">
                <div class="panel-heading">
                    <strong>Cubes</strong>
                </div>
                <div class="panel-body">
                    <div id="assignedCubesCount" style="font-size:42px;font-weight:bold;">
                        <?= htmlspecialchars($assignedCubes) ?>/<?= htmlspecialchars($totalCharacters) ?>
                    </div>
                    <div>Assigned</div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="panel panel-primary text-center">
                <div class="panel-heading">
                    <strong>Created</strong>
                </div>
                <div class="panel-body">
                    <div style="font-size:18px;font-weight:bold;margin-top:12px;">
                        <?= htmlspecialchars($created) ?>
                    </div>
                    <div>Campaign Date</div>
                </div>
            </div>
        </div>

    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-cube-outline"></i>
                Cube Registration
            </strong>
        </div>

        <div class="panel-body">
            <p>
                Assign D&amp;D Player Cubes to characters in this campaign.
            </p>

            <a class="btn btn-primary btn-lg" href="game-cube-scan.php?game_id=<?= urlencode($gameId) ?>">
                <i class="mdi mdi-cube-send"></i>
                Scan Cubes
            </a>
        </div>
    </div>

    <a class="btn btn-default btn-lg" href="game.php">
        <i class="mdi mdi-arrow-left"></i>
        Back to Game Mode
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

function getNumber(character, keys, fallback) {
    for (let i = 0; i < keys.length; i++) {
        if (character[keys[i]] !== undefined && character[keys[i]] !== null && character[keys[i]] !== "") {
            return parseInt(character[keys[i]], 10) || 0;
        }
    }

    return fallback || 0;
}

function renderCharacters(characters) {
    const tbody = document.getElementById("charactersTableBody");
    const tableWrap = document.getElementById("charactersTableWrap");
    const noCharactersAlert = document.getElementById("noCharactersAlert");
    const totalCharactersCount = document.getElementById("totalCharactersCount");
    const assignedCubesCount = document.getElementById("assignedCubesCount");

    if (!tbody) {
        return;
    }

    if (!Array.isArray(characters)) {
        characters = [];
    }

    let assignedCubes = 0;
    let html = "";

    characters.forEach(function(character) {
        const playerName = character.player_name || "";
        const characterName = character.character_name || character.name || "";
        const hp = getNumber(character, ["hp", "current_hp"], 0);
        const maxHp = getNumber(character, ["max_hp"], 0);
        const tempHp = getNumber(character, ["temp_hp"], 0);
        const deathSuccess = getNumber(character, ["death_success", "death_saves_success"], 0);
        const deathFail = getNumber(character, ["death_fail", "death_saves_fail"], 0);
        const cubeId = character.cube_id || "";

        if (cubeId !== "") {
            assignedCubes++;
        }

        html += "<tr>";
        html += "<td>" + escapeHtml(playerName) + "</td>";
        html += "<td><strong>" + escapeHtml(characterName) + "</strong></td>";
        html += "<td>" + escapeHtml(hp + "/" + maxHp) + "</td>";
        html += "<td>" + escapeHtml(tempHp) + "</td>";
        html += "<td>Success " + escapeHtml(deathSuccess) + "/3<br>Fail " + escapeHtml(deathFail) + "/3</td>";

        if (cubeId !== "") {
            html += "<td><span class=\"label label-success\">" + escapeHtml(cubeId) + "</span></td>";
        } else {
            html += "<td><span class=\"label label-default\">Not assigned</span></td>";
        }

        html += "</tr>";
    });

    tbody.innerHTML = html;

    if (characters.length === 0) {
        tableWrap.style.display = "none";
        noCharactersAlert.style.display = "";
    } else {
        tableWrap.style.display = "";
        noCharactersAlert.style.display = "none";
    }

    if (totalCharactersCount) {
        totalCharactersCount.textContent = characters.length;
    }

    if (assignedCubesCount) {
        assignedCubesCount.textContent = assignedCubes + "/" + characters.length;
    }
}

function refreshDashboardStats() {
    const liveStatus = document.getElementById("liveStatus");

    fetch("game-live-state.php?game_id=" + encodeURIComponent(GAME_ID) + "&_=" + Date.now(), {
        cache: "no-store"
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (!data.success || !Array.isArray(data.characters)) {
            if (liveStatus) {
                liveStatus.textContent = "Live Error";
            }
            return;
        }

        renderCharacters(data.characters);

        if (liveStatus) {
            liveStatus.textContent = "Live " + new Date().toLocaleTimeString();
        }
    })
    .catch(function(error) {
        console.log("Dashboard live refresh failed:", error);

        if (liveStatus) {
            liveStatus.textContent = "Offline";
        }
    });
}

refreshDashboardStats();
setInterval(refreshDashboardStats, 5000);
</script>

<?php
include("inc.footer.php");
?>
