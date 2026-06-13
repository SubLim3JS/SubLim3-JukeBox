<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? "";

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gameDir = $baseDir . "/games/" . basename($gameId);

$gameFile = $gameDir . "/game.json";
$charactersFile = $gameDir . "/characters.json";

if ($gameId === "" || !file_exists($gameFile)) {
    html_bootstrap3_createHeader(
        "en",
        "Campaign Not Found",
        $conf['base_url']
    );
    ?>

    <body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

    <div class="container">
        <?php include("inc.navigation.php"); ?>

        <div class="alert alert-danger">
            Campaign not found.
        </div>

        <a class="btn btn-default" href="game-load.php">
            Back
        </a>
    </div>

    <?php
    include("inc.footer.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Active / Last Played
|--------------------------------------------------------------------------
*/

file_put_contents(
    $baseDir . "/active-game",
    $gameId
);

file_put_contents(
    $baseDir . "/last-game",
    $gameId
);

/*
|--------------------------------------------------------------------------
| Load Data
|--------------------------------------------------------------------------
*/

$game = json_decode(file_get_contents($gameFile), true);

if (!is_array($game)) {
    $game = [];
}

$characters = [];

if (file_exists($charactersFile)) {
    $characters = json_decode(file_get_contents($charactersFile), true);

    if (!is_array($characters)) {
        $characters = [];
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
        <i class="mdi mdi-cube-outline"></i>
        Cube Registration
    </h1>

    <p class="lead">
        <?= htmlspecialchars($game["game_name"] ?? $gameId) ?>
    </p>

    <div class="panel panel-success">

        <div class="panel-heading">
            <strong>
                Active Campaign
            </strong>
        </div>

        <div class="panel-body">
            <?= htmlspecialchars($game["game_name"] ?? $gameId) ?>
        </div>

    </div>

    <div class="panel panel-primary">

        <div class="panel-heading">
            <strong>
                Player Characters
            </strong>
        </div>

        <div class="panel-body">

            <?php if (empty($characters)): ?>

                <div class="alert alert-info">
                    No characters have been added yet.
                </div>

                <a class="btn btn-primary"
                   href="game-character-add.php?game_id=<?= urlencode($gameId) ?>">
                    <i class="mdi mdi-account-plus"></i>
                    Add Character
                </a>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-striped table-hover">

                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Character</th>
                                <th>Cube</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($characters as $character): ?>

                            <?php
                            $characterId = $character["character_id"] ?? "";
                            $playerName = $character["player_name"] ?? "";
                            $characterName = $character["character_name"] ?? "";
                            $cubeId = $character["cube_id"] ?? "";
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($playerName) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($characterName) ?>
                                    </strong>
                                </td>

                                <td>

                                    <?php if ($cubeId !== ""): ?>

                                        <span class="label label-success">
                                            <?= htmlspecialchars($cubeId) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="label label-default">
                                            Not Assigned
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <a class="btn btn-primary btn-sm"
                                       href="game-cube-scan.php?game_id=<?= urlencode($gameId) ?>&character_id=<?= urlencode($characterId) ?>">

                                        <i class="mdi mdi-cube-send"></i>

                                        <?php if ($cubeId === ""): ?>
                                            Register Cube
                                        <?php else: ?>
                                            Replace Cube
                                        <?php endif; ?>

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <a class="btn btn-primary"
       href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">

        <i class="mdi mdi-view-dashboard"></i>
        Dashboard

    </a>

    <a class="btn btn-default"
       href="game-load.php">

        <i class="mdi mdi-arrow-left"></i>
        Back

    </a>

</div>

<?php
include("inc.footer.php");
?>
