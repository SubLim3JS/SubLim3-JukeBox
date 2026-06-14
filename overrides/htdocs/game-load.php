<?php
include("inc.header.php");

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

$games = glob($gameDir . "/*/game.json");

if ($games === false) {
    $games = [];
}

usort($games, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

html_bootstrap3_createHeader(
    "en",
    "Load Game | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <div class="row">
        <div class="col-lg-12">
            <h1>
                <i class="mdi mdi-folder-open"></i>
                Existing Games
            </h1>

            <p class="lead">
                Select a campaign to continue.
            </p>
        </div>
    </div>

    <?php if (empty($games)): ?>

        <div class="alert alert-info">
            No saved games found.
        </div>

        <a class="btn btn-primary btn-lg" href="game-new.php">
            <i class="mdi mdi-book-plus"></i>
            Create New Game
        </a>

    <?php else: ?>

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Available Campaigns</strong>
            </div>

            <div class="list-group">

                <?php foreach ($games as $gameFile): ?>

                    <?php
                    $game = json_decode(file_get_contents($gameFile), true);

                    if (!is_array($game)) {
                        $game = [];
                    }

                    $gameId = $game["game_id"] ?? basename(dirname($gameFile));
                    $gameName = $game["game_name"] ?? $gameId;
                    $created = $game["created"] ?? "Unknown";
                    ?>

                    <a class="list-group-item"
                       href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">

                        <h4 class="list-group-item-heading">
                            <i class="mdi mdi-book-open-page-variant"></i>
                            <?= htmlspecialchars($gameName) ?>
                        </h4>

                        <p class="list-group-item-text">
                            Created:
                            <?= htmlspecialchars($created) ?>
                        </p>

                    </a>

                <?php endforeach; ?>

            </div>
        </div>

    <?php endif; ?>

    <a class="btn btn-default btn-lg" href="game.php">
        <i class="mdi mdi-arrow-left"></i>
        Back
    </a>

</div>

<?php
include("inc.footer.php");
?>
