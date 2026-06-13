<?php
include("inc.header.php");
?>

<div class="container" style="max-width:1000px; margin-top:30px;">

    <div class="text-center" style="margin-bottom:40px;">
        <h1>
            <i class="fa fa-dice-d20"></i> Game Mode
        </h1>
        <p class="lead">
            Launch a new adventure or continue an existing campaign.
        </p>
    </div>

    <div class="row">

        <!-- New Game -->
        <div class="col-md-6">
            <div class="panel panel-success text-center" style="min-height:320px;">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-plus-circle"></i> New Game
                    </h3>
                </div>

                <div class="panel-body" style="padding:30px;">
                    <div style="font-size:72px; color:#5cb85c; margin-bottom:20px;">
                        <i class="fa fa-dragon"></i>
                    </div>

                    <p>
                        Create a new campaign and enter character information,
                        stats, and party details.
                    </p>

                    <br>

                    <a class="btn btn-success btn-lg btn-block"
                       href="game-new.php">
                        <i class="fa fa-play"></i> Start New Game
                    </a>
                </div>
            </div>
        </div>

        <!-- Existing Game -->
        <div class="col-md-6">
            <div class="panel panel-primary text-center" style="min-height:320px;">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-folder-open"></i> Existing Game
                    </h3>
                </div>

                <div class="panel-body" style="padding:30px;">
                    <div style="font-size:72px; color:#337ab7; margin-bottom:20px;">
                        <i class="fa fa-users"></i>
                    </div>

                    <p>
                        Load a saved campaign and register player cubes to
                        reconnect characters.
                    </p>

                    <br>

                    <a class="btn btn-primary btn-lg btn-block"
                       href="game-load.php">
                        <i class="fa fa-sign-in"></i> Load Existing Game
                    </a>
                </div>
            </div>
        </div>

    </div>

    <hr>

    <div class="text-center">
        <a class="btn btn-default btn-lg" href="index.php">
            <i class="fa fa-music"></i> Return to JukeBox Mode
        </a>
    </div>

</div>

<?php
include("inc.footer.php");
?>
