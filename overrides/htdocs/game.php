<?php
include("inc.header.php");
html_bootstrap3_createHeader("en", "Game Mode | SubLim3 JukeBox", $conf['base_url']);
?>

<style>
.game-mode-hero {
    text-align: center;
    margin: 25px 0 30px 0;
}

.game-mode-hero h1 {
    font-weight: bold;
    color: var(--sublim3-primary);
}

.game-mode-panel {
    border-color: var(--sublim3-primary);
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
}

.game-mode-panel .panel-heading {
    background: var(--sublim3-primary);
    color: var(--sublim3-text-on-primary);
    border-color: var(--sublim3-primary);
}

.game-mode-btn {
    margin-bottom: 15px;
    padding: 18px;
    text-align: left;
    border-radius: 8px;
}

.game-mode-btn strong {
    display: block;
    font-size: 20px;
}

.game-mode-btn span {
    display: block;
    font-size: 13px;
    opacity: 0.9;
}

.game-mode-footer {
    text-align: center;
    margin-top: 25px;
}
</style>

<div class="container">

    <div class="game-mode-hero">
        <h1>
            <i class="glyphicon glyphicon-tower"></i>
            D&amp;D Game Mode
        </h1>
        <p class="lead">
            Start a campaign, load a saved game, or manage player cubes.
        </p>
    </div>

    <div class="panel panel-primary game-mode-panel">
        <div class="panel-heading">
            <h3 class="panel-title">
                Game Mode Menu
            </h3>
        </div>

        <div class="panel-body">

            <a class="btn btn-primary btn-lg btn-block game-mode-btn" href="game-new.php">
                <strong>Start New Campaign</strong>
                <span>Create a new game and enter character stats.</span>
            </a>

            <a class="btn btn-primary btn-lg btn-block game-mode-btn" href="game-load.php">
                <strong>Load Campaign</strong>
                <span>Continue a saved game and begin cube registration.</span>
            </a>

            <a class="btn btn-primary btn-lg btn-block game-mode-btn" href="game-players.php">
                <strong>Manage Players</strong>
                <span>Add, edit, or reconnect D&amp;D Player Cubes.</span>
            </a>

            <a class="btn btn-primary btn-lg btn-block game-mode-btn" href="game-settings.php">
                <strong>Campaign Settings</strong>
                <span>Adjust game defaults, campaign name, and options.</span>
            </a>

        </div>
    </div>

    <div class="game-mode-footer">
        <a class="btn btn-default btn-lg" href="index.php">
            <i class="glyphicon glyphicon-music"></i>
            Return to JukeBox Mode
        </a>
    </div>

</div>

<?php
include("inc.footer.php");
?>
