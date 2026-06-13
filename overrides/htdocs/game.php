<?php
include("inc.header.php");

$sublim3ThemeClass = 'sublim3-theme-dnd-book';

html_bootstrap3_createHeader("en", "Game Mode | SubLim3 JukeBox", $conf['base_url']);
?>

<body class="<?php print htmlspecialchars($sublim3ThemeClass); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

<style>
body.sublim3-theme-dnd-book {
    --sublim3-primary: #7a4a24;
    --sublim3-primary-dark: #4b2d16;
    --sublim3-primary-light: #c89b5f;
    --sublim3-text-on-primary: #fff4dc;
    --sublim3-hover-text: #fff4dc;
    --sublim3-alert-link: #ffe6b3;

    background: #d8b46f;
    color: #2b1a0e;
}

.game-mode-hero {
    text-align: center;
    margin: 25px 0 30px 0;
}

.game-mode-hero h1 {
    font-weight: bold;
    color: var(--sublim3-primary-dark);
}

.game-mode-panel {
    border-color: var(--sublim3-primary-dark);
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
}

.game-mode-panel > .panel-heading {
    background-color: var(--sublim3-primary) !important;
    border-color: var(--sublim3-primary-dark) !important;
    color: var(--sublim3-text-on-primary) !important;
}

.game-mode-btn {
    margin-bottom: 15px;
    padding: 18px;
    text-align: left;
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
</style>

<div class="game-mode-hero">
    <h1>
        <i class="glyphicon glyphicon-book"></i>
        D&amp;D Game Mode
    </h1>
    <p class="lead">
        Open the campaign book and choose your next adventure.
    </p>
</div>

<div class="panel panel-primary game-mode-panel">
    <div class="panel-heading">
        <h3 class="panel-title">Campaign Menu</h3>
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

<div class="text-center">
    <a class="btn btn-default btn-lg" href="index.php">
        <i class="glyphicon glyphicon-music"></i>
        Return to JukeBox Mode
    </a>
</div>

</div>

<?php include("inc.footer.php"); ?>
