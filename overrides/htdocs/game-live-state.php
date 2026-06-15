<?php
header("Content-Type: application/json");

$gameId = $_GET["game_id"] ?? "";

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gameDir = $baseDir . "/games/" . basename($gameId);
$charactersFile = $gameDir . "/characters.json";

if ($gameId === "" || !file_exists($charactersFile)) {
    echo json_encode([
        "success" => false,
        "error" => "Game or characters file not found",
        "characters" => []
    ]);
    exit;
}

$characters = json_decode(file_get_contents($charactersFile), true);

if (!is_array($characters)) {
    $characters = [];
}

echo json_encode([
    "success" => true,
    "game_id" => $gameId,
    "characters" => $characters
]);
