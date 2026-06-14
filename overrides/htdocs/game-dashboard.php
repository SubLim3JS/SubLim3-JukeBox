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

    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>
                <i class="mdi mdi-account-group"></i>
                Party Characters
            </strong>
        </div>

        <div class="panel-body">

            <?php if (empty($characters)): ?>

                <div class="alert alert-info">
                    No characters added yet. Add your first player character to begin.
                </div>

            <?php else: ?>

                <div class="table-responsive">
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

                        <tbody>
                            <?php foreach ($characters as $c): ?>
                                <?php
                                $cubeId = $c["cube_id"] ?? "";
                                $cubeText = $cubeId !== "" ? $cubeId : "Not assigned";
                                $deathSuccess = $c["death_success"] ?? 0;
                                $deathFail = $c["death_fail"] ?? 0;
                                ?>

                                <tr>
                                    <td><?= htmlspecialchars($c["player_name"] ?? "") ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($c["character_name"] ?? "") ?></strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(($c["hp"] ?? 0) . "/" . ($c["max_hp"] ?? 0)) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($c["temp_hp"] ?? 0) ?>
                                    </td>

                                    <td>
                                        Success <?= htmlspecialchars($deathSuccess) ?>/3
                                        <br>
                                        Fail <?= htmlspecialchars($deathFail) ?>/3
                                    </td>

                                    <td>
                                        <?php if ($cubeId !== ""): ?>
                                            <span class="label label-success">
                                                <?= htmlspecialchars($cubeText) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="label label-default">
                                                Not assigned
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

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
                    <div style="font-size:42px;font-weight:bold;">
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
                    <div style="font-size:42px;font-weight:bold;">
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

            <a class="btn btn-primary btn-lg" href="game-cube-register.php?game_id=<?= urlencode($gameId) ?>">
                <i class="mdi mdi-cube-send"></i>
                Register Cubes
            </a>
        </div>
    </div>

    <a class="btn btn-default btn-lg" href="game.php">
        <i class="mdi mdi-arrow-left"></i>
        Back to Game Mode
    </a>

</div>

<?php
include("inc.footer.php");
?>
