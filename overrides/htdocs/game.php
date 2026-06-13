<?php
include("inc.header.php");
?>

<div class="container">

    <h1>Game Mode</h1>
    <p>Select how you want to begin.</p>

    <div class="row">

        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <strong>New Game</strong>
                </div>
                <div class="panel-body">
                    <p>Create a new DnD game and enter basic character stats.</p>
                    <a class="btn btn-success btn-lg btn-block" href="game-new.php">
                        Start New Game
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <strong>Existing Game</strong>
                </div>
                <div class="panel-body">
                    <p>Load a saved game and begin cube registration.</p>
                    <a class="btn btn-primary btn-lg btn-block" href="game-load.php">
                        Load Existing Game
                    </a>
                </div>
            </div>
        </div>

    </div>

    <a class="btn btn-default" href="index.php">Back to JukeBox Mode</a>

</div>

<?php
include("inc.footer.php");
?>
