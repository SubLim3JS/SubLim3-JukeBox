<?php
include("inc.header.php");

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/games";
$activeGameFile = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game/active-game";

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

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["set_active"])) {

        $gameId = basename($_POST["set_active"]);

        file_put_contents($activeGameFile, $gameId);

        header("Location: game-settings.php");
        exit;
    }

    if (isset($_POST["delete_game"])) {

        $gameId = basename($_POST["delete_game"]);
        $gamePath = $gameDir . "/" . $gameId;

        if (is_dir($gamePath)) {
            deleteDirectory($gamePath);
        }

        if (
            file_exists($activeGameFile) &&
            trim(file_get_contents($activeGameFile)) === $gameId
        ) {
            unlink($activeGameFile);
        }

        header("Location: game-settings.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Load Games
|--------------------------------------------------------------------------
*/

$activeGameId = "";

if (file_exists($activeGameFile)) {
    $activeGameId = trim(file_get_contents($activeGameFile));
}

$games = glob($gameDir . "/*/game.json");

if ($games === false) {
    $games = [];
}

usort($games, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

html_bootstrap3_createHeader(
    "en",
    "Campaign Settings | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-cog"></i>
        Campaign Settings
    </h1>

    <p class="lead">
        Manage your D&amp;D campaigns.
    </p>

    <?php if ($activeGameId !== ""): ?>

        <?php
        $activeName = $activeGameId;

        foreach ($games as $gameFile) {
            $game = json_decode(file_get_contents($gameFile), true);

            if (($game["game_id"] ?? "") === $activeGameId) {
                $activeName = $game["game_name"] ?? $activeGameId;
                break;
            }
        }
        ?>

        <div class="alert alert-success">
            <strong>
                <i class="mdi mdi-star"></i>
                Active Campaign:
            </strong>
            <?= htmlspecialchars($activeName) ?>
        </div>

    <?php endif; ?>

    <?php if (empty($games)): ?>

        <div class="alert alert-info">
            No campaigns found.
        </div>

        <a class="btn btn-primary btn-lg" href="game-new.php">
            <i class="mdi mdi-book-plus"></i>
            Create New Campaign
        </a>

    <?php else: ?>

        <?php foreach ($games as $gameFile): ?>

            <?php
            $game = json_decode(file_get_contents($gameFile), true);

            if (!is_array($game)) {
                $game = [];
            }

            $gameId = $game["game_id"] ?? basename(dirname($gameFile));
            $gameName = $game["game_name"] ?? $gameId;
            $created = $game["created"] ?? "Unknown";

            $isActive = ($gameId === $activeGameId);
            ?>

            <div class="panel panel-primary">

                <div class="panel-heading">

                    <strong>
                        <i class="mdi mdi-book-open-page-variant"></i>
                        <?= htmlspecialchars($gameName) ?>
                    </strong>

                    <?php if ($isActive): ?>
                        <span class="label label-success pull-right">
                            ACTIVE
                        </span>
                    <?php endif; ?>

                </div>

                <div class="panel-body">

                    <p>
                        <strong>Created:</strong>
                        <?= htmlspecialchars($created) ?>
                    </p>

                    <a class="btn btn-primary"
                       href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
                        <i class="mdi mdi-view-dashboard"></i>
                        Dashboard
                    </a>

                    <a class="btn btn-default"
                       href="game-rename.php?game_id=<?= urlencode($gameId) ?>">
                        <i class="mdi mdi-pencil"></i>
                        Rename
                    </a>

                    <?php if (!$isActive): ?>

                        <form method="post"
                              action="game-settings.php"
                              style="display:inline-block;">

                            <input type="hidden"
                                   name="set_active"
                                   value="<?= htmlspecialchars($gameId) ?>">

                            <button type="submit"
                                    class="btn btn-success">
                                <i class="mdi mdi-star"></i>
                                Set Active
                            </button>

                        </form>

                    <?php endif; ?>

                    <form method="post"
                          action="game-settings.php"
                          style="display:inline-block;"
                          onsubmit="return confirm('Delete campaign <?= htmlspecialchars(addslashes($gameName)) ?>? This cannot be undone.');">

                        <input type="hidden"
                               name="delete_game"
                               value="<?= htmlspecialchars($gameId) ?>">

                        <button type="submit"
                                class="btn btn-danger">
                            <i class="mdi mdi-trash-can"></i>
                            Delete
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <a class="btn btn-default btn-lg" href="game.php">
        <i class="mdi mdi-arrow-left"></i>
        Back to Game Mode
    </a>

</div>

<?php
include("inc.footer.php");
?>
