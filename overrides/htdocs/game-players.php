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
    "Manage Players | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

    <h1>
        <i class="mdi mdi-account-group"></i>
        Manage Players
    </h1>

    <p class="lead">
        View players and characters across all saved campaigns.
    </p>

    <?php if (empty($games)): ?>

        <div class="alert alert-info">
            No saved campaigns found.
        </div>

        <a class="btn btn-primary btn-lg" href="game-new.php">
            <i class="mdi mdi-book-plus"></i>
            Create New Campaign
        </a>

    <?php else: ?>

        <?php foreach ($games as $gameFile): ?>

            <?php
            $gamePath = dirname($gameFile);
            $game = json_decode(file_get_contents($gameFile), true);

            if (!is_array($game)) {
                $game = [];
            }

            $gameId = $game["game_id"] ?? basename($gamePath);
            $gameName = $game["game_name"] ?? $gameId;
            $created = $game["created"] ?? "Unknown";

            $charactersFile = $gamePath . "/characters.json";
            $characters = [];

            if (file_exists($charactersFile)) {
                $characters = json_decode(file_get_contents($charactersFile), true);

                if (!is_array($characters)) {
                    $characters = [];
                }
            }
            ?>

            <div class="panel panel-primary">

                <div class="panel-heading">
                    <strong>
                        <i class="mdi mdi-book-open-page-variant"></i>
                        <?= htmlspecialchars($gameName) ?>
                    </strong>
                    <span class="pull-right">
                        Created: <?= htmlspecialchars($created) ?>
                    </span>
                </div>

                <div class="panel-body">

                    <?php if (empty($characters)): ?>

                        <div class="alert alert-info">
                            No players have been added to this campaign yet.
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
                                    <?php foreach ($characters as $characterIndex => $character): ?>

                                        <?php
                                        $playerName = $character["player_name"] ?? "";
                                        $characterName = $character["character_name"] ?? ($character["name"] ?? "Character " . ($characterIndex + 1));
                                        $hp = $character["hp"] ?? 0;
                                        $maxHp = $character["max_hp"] ?? 0;
                                        $tempHp = $character["temp_hp"] ?? 0;
                                        $deathSuccess = $character["death_success"] ?? 0;
                                        $deathFail = $character["death_fail"] ?? 0;
                                        $cubeId = $character["cube_id"] ?? "";
                                        ?>

                                        <tr>
                                            <td><?= htmlspecialchars($playerName) ?></td>

                                            <td>
                                                <a class="btn btn-primary btn-sm"
                                                   href="game-player.php?game_id=<?= urlencode($gameId) ?>&character=<?= urlencode($characterIndex) ?>&dm=1">
                                                    <i class="mdi mdi-account-edit"></i>
                                                    <?= htmlspecialchars($characterName) ?>
                                                </a>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($hp) ?>/<?= htmlspecialchars($maxHp) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($tempHp) ?>
                                            </td>

                                            <td>
                                                Success <?= htmlspecialchars($deathSuccess) ?>/3
                                                <br>
                                                Fail <?= htmlspecialchars($deathFail) ?>/3
                                            </td>

                                            <td>
                                                <?php if ($cubeId !== ""): ?>
                                                    <span class="label label-success">
                                                        <?= htmlspecialchars($cubeId) ?>
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

                    <a class="btn btn-primary"
                       href="game-character-add.php?game_id=<?= urlencode($gameId) ?>">
                        <i class="mdi mdi-account-plus"></i>
                        Add Character
                    </a>

                    <a class="btn btn-default"
                       href="game-dashboard.php?game_id=<?= urlencode($gameId) ?>">
                        <i class="mdi mdi-view-dashboard"></i>
                        Dashboard
                    </a>

                    <a class="btn btn-default"
                       href="game-cube-register.php?game_id=<?= urlencode($gameId) ?>">
                        <i class="mdi mdi-cube-outline"></i>
                        Register Cubes
                    </a>

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
