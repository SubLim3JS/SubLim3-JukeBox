<?php
include("inc.header.php");

html_bootstrap3_createHeader(
    "en",
    "D&D Game Mode | SubLim3 JukeBox",
    $conf['base_url']
);
?>

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<div class="container">

<?php include("inc.navigation.php"); ?>

<div class="row">
    <div class="col-lg-12">
        <h1>
            <i class="mdi mdi-sword-cross"></i>
            D&amp;D Game Mode
        </h1>

        <p class="lead">
            Create a new campaign, continue an existing one, or manage your player cubes.
        </p>
    </div>
</div>

<div class="row">

    <div class="col-md-6">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>New Campaign</strong>
            </div>

            <div class="panel-body">
                <p>
                    Start a brand new campaign and create player characters.
                </p>

                <a class="btn btn-primary btn-lg btn-block"
                   href="game-new.php">
                    <i class="glyphicon glyphicon-plus"></i>
                    Start New Campaign
                </a>
            </div>
        </div>

    </div>

    <div class="col-md-6">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Load Campaign</strong>
            </div>

            <div class="panel-body">
                <p>
                    Continue an existing campaign and reconnect player cubes.
                </p>

                <a class="btn btn-primary btn-lg btn-block"
                   href="game-load.php">
                    <i class="mdi mdi-book-open-variant"></i>
                    Load Campaign
                </a>
            </div>
        </div>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Manage Players</strong>
            </div>

            <div class="panel-body">
                <p>
                    Add, edit, or remove player characters and cube assignments.
                </p>

                <a class="btn btn-primary btn-lg btn-block"
                   href="game-players.php">
                    <i class="mdi mdi-account-multiple"></i>
                    Manage Players
                </a>
            </div>
        </div>

    </div>

    <div class="col-md-6">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Campaign Settings</strong>
            </div>

            <div class="panel-body">
                <p>
                    Configure campaign options and game defaults.
                </p>

                <a class="btn btn-primary btn-lg btn-block"
                   href="game-settings.php">
                    <i class="mdi mdi-settings"></i>
                    Campaign Settings
                </a>
            </div>
        </div>

    </div>

</div>

<hr>

<div class="text-center">
    <a class="btn btn-default btn-lg" href="index.php">
        <i class="mdi mdi-music"></i>
        Return to JukeBox Mode
    </a>
</div>

<br>

</div>

<?php
include("inc.footer.php");
?>
