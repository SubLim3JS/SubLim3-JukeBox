<?php
header("Content-Type: application/json");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";

$gameId = basename($_GET["game_id"] ?? "");
$gamePath = $gamesDir . "/" . $gameId;

$charactersFile = $gamePath . "/characters.json";
$battleFile = $gamePath . "/battle.json";

if ($gameId === "" || !is_dir($gamePath)) {
    echo json_encode([
        "success" => false,
        "error" => "Game not found.",
        "characters" => [],
        "battle" => [
            "enemies" => [],
            "order" => []
        ]
    ]);
    exit;
}

$characters = [];

if (file_exists($charactersFile)) {
    $characters = json_decode(file_get_contents($charactersFile), true);
}

if (!is_array($characters)) {
    $characters = [];
}

$battle = [
    "enemies" => [],
    "order" => []
];

if (file_exists($battleFile)) {
    $decodedBattle = json_decode(file_get_contents($battleFile), true);

    if (is_array($decodedBattle)) {
        $battle = $decodedBattle;
    }
}

if (!isset($battle["enemies"]) || !is_array($battle["enemies"])) {
    $battle["enemies"] = [];
}

if (!isset($battle["order"]) || !is_array($battle["order"])) {
    $battle["order"] = [];
}

echo json_encode([
    "success" => true,
    "game_id" => $gameId,
    "characters" => $characters,
    "battle" => $battle
]);
