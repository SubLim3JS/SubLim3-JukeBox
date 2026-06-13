<?php
include("inc.header.php");

html_bootstrap3_createHeader(
    "en",
    "Load Game | SubLim3 JukeBox",
    $conf['base_url']
);

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

function deleteDirectory($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = array_diff(scandir($dir), ['.', '..']);

    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_game"])) {
    $gameId = basename($_POST["delete_game"]);
    $gamePath = $gameDir . "/" . $gameId;

    if (is_dir($gamePath)) {
        deleteDirectory($gamePath);
    }

    header("Location: game-load.php");
    exit;
}

$games = glob($gameDir . "/*/game.json");

usort($games, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <div class="row">
        <div class="col-lg-12">
            <h1>
                <i class="glyphicon glyphicon-folder-open"></i>
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
            <i class="glyphicon glyphicon-plus"></i>
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

                    $gameId = $game["game_id"] ?? basename(dirname($gameFile));
                    $gameName = $game["game_name"] ?? $gameId;
                    $created = $game["created"] ?? "";
                    $confirmText = "Delete campaign '" . $gameName . "'?\\n\\nThis cannot be undone.";
                    ?>

                    <div class="list-group-item">

                        <div class="row">

                            <div class="col-xs-8 col-sm-9">

                                <a href="game-cube-register.php?game_id=<?= urlencode($gameId) ?>"
                                   style="display:block;text-decoration:none;color:inherit;">

                                    <h4 class="list-group-item-heading">
                                        <i class="glyphicon glyphicon-book"></i>
                                        <?= htmlspecialchars($gameName) ?>
                                    </h4>

                                    <p class="list-group-item-text">
                                        Created:
                                        <?= htmlspecialchars($created) ?>
                                    </p>

                                </a>

                            </div>

                            <div class="col-xs-4 col-sm-3 text-right">

                                <form method="post"
                                      action="game-load.php"
                                      style="display:inline-block;"
                                      onsubmit="return confirm(<?= json_encode($confirmText) ?>);">

                                    <input type="hidden"
                                           name="delete_game"
                                           value="<?= htmlspecialchars($gameId) ?>">

                                    <button type="submit"
                                            class="btn btn-danger btn-sm">
                                        <i class="glyphicon glyphicon-trash"></i>
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>
        </div>

    <?php endif; ?>

    <a class="btn btn-default btn-lg" href="game.php">
        <i class="glyphicon glyphicon-arrow-left"></i>
        Back
    </a>

</div>

<?php
include("inc.footer.php");
?>
