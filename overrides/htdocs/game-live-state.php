<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

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

clearstatcache(true, $charactersFile);
clearstatcache(true, $battleFile);

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
    "timestamp" => time(),
    "characters" => $characters,
    "battle" => $battle
]);
