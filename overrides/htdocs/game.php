<?php
include("inc.header.php");
?>

<style>
.game-mode-wrap {
    max-width: 760px;
    margin: 35px auto;
}

.game-mode-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
    text-align: center;
}

.game-mode-title {
    font-size: 34px;
    font-weight: bold;
    margin-bottom: 8px;
}

.game-mode-subtitle {
    font-size: 18px;
    color: #666;
    margin-bottom: 28px;
}

.game-mode-btn {
    display: block;
    width: 100%;
    margin-bottom: 16px;
    padding: 18px;
    font-size: 20px;
    font-weight: bold;
    border-radius: 10px;
}

.game-mode-btn small {
    display: block;
    font-size: 13px;
    font-weight: normal;
    margin-top: 5px;
    opacity: 0.85;
}

.game-mode-footer {
    margin-top: 24px;
}
</style>

<div class="container game-mode-wrap">

    <div class="game-mode-card">

        <div class="game-mode-title">
            D&amp;D Game Mode
        </div>

        <div class="game-mode-subtitle">
            Choose how you want to begin your adventure.
        </div>

        <a class="btn btn-success btn-lg game-mode-btn" href="game-new.php">
            Start New Campaign
            <small>Create a new game and enter character stats.</small>
        </a>

        <a class="btn btn-primary btn-lg game-mode-btn" href="game-load.php">
            Load Campaign
            <small>Continue a saved game and register player cubes.</small>
        </a>

        <a class="btn btn-warning btn-lg game-mode-btn" href="game-players.php">
            Manage Players
            <small>Add, edit, or reconnect player cubes.</small>
        </a>

        <a class="btn btn-info btn-lg game-mode-btn" href="game-settings.php">
            Campaign Settings
            <small>Adjust game details, defaults, and options.</small>
        </a>

        <div class="game-mode-footer">
            <a class="btn btn-default btn-lg" href="index.php">
                Return to JukeBox Mode
            </a>
        </div>

    </div>

</div>

<?php
include("inc.footer.php");
?>
