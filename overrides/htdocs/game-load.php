<?php
include("inc.header.php");

$gameId = $_GET["game_id"] ?? "";

if ($gameId !== "") {

    $dndDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";

    if (!is_dir($dndDir)) {
        mkdir($dndDir, 0775, true);
    }

    file_put_contents(
        $dndDir . "/active-game",
        $gameId
    );

    file_put_contents(
        $dndDir . "/last-game",
        $gameId
    );
}
