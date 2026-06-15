<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$activeGameFile = $baseDir . "/active-game";
$gamesDir = $baseDir . "/games";

function respond($success, $data = []) {
    echo json_encode(array_merge([
        "success" => $success
    ], $data), JSON_PRETTY_PRINT);
    exit;
}

function readJsonFile($file, $fallback = []) {
    if (!file_exists($file)) {
        return $fallback;
    }

    $raw = file_get_contents($file);
    $json = json_decode($raw, true);

    return is_array($json) ? $json : $fallback;
}

if (!file_exists($activeGameFile)) {
    respond(false, [
        "error" => "No active game selected.",
        "game_id" => "",
        "game" => [],
        "characters" => [],
        "battle" => []
    ]);
}

$gameId = basename(trim(file_get_contents($activeGameFile)));

if ($gameId === "") {
    respond(false, [
        "error" => "Active game file is empty.",
        "game_id" => "",
        "game" => [],
        "characters" => [],
        "battle" => []
    ]);
}

$gameDir = $gamesDir . "/" . $gameId;
$gameFile = $gameDir . "/game.json";
$charactersFile = $gameDir . "/characters.json";
$battleFile = $gameDir . "/battle.json";

if (!is_dir($gameDir) || !file_exists($gameFile)) {
    respond(false, [
        "error" => "Active game not found.",
        "game_id" => $gameId,
        "game" => [],
        "characters" => [],
        "battle" => []
    ]);
}

$game = readJsonFile($gameFile, []);
$characters = readJsonFile($charactersFile, []);
$battle = readJsonFile($battleFile, []);

respond(true, [
    "game_id" => $gameId,
    "game" => $game,
    "characters" => $characters,
    "battle" => $battle,
    "updated" => date("Y-m-d H:i:s"),
    "mtime" => [
        "game" => file_exists($gameFile) ? filemtime($gameFile) : 0,
        "characters" => file_exists($charactersFile) ? filemtime($charactersFile) : 0,
        "battle" => file_exists($battleFile) ? filemtime($battleFile) : 0
    ]
]);
?>
