<?php
header("Content-Type: application/json");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";
$activeGameFile = $baseDir . "/active-game";

function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$cardId = trim($_GET["card_id"] ?? "");

if ($cardId === "") {
    jsonResponse([
        "success" => false,
        "message" => "Missing card_id."
    ]);
}

if (!file_exists($activeGameFile)) {
    jsonResponse([
        "success" => false,
        "message" => "No active game is set."
    ]);
}

$gameId = trim(file_get_contents($activeGameFile));
$gameId = basename($gameId);

$gamePath = $gamesDir . "/" . $gameId;
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !is_dir($gamePath) || !file_exists($charactersFile)) {
    jsonResponse([
        "success" => false,
        "message" => "Active game not found.",
        "game_id" => $gameId
    ]);
}

$characters = json_decode(file_get_contents($charactersFile), true);

if (!is_array($characters)) {
    jsonResponse([
        "success" => false,
        "message" => "Characters file is invalid.",
        "game_id" => $gameId
    ]);
}

foreach ($characters as $character) {
    $characterCardId = $character["card_id"] ?? $character["rfid_id"] ?? "";

    if ((string)$characterCardId === (string)$cardId) {
        $characterId =
            $character["id"] ??
            $character["code"] ??
            $character["character_id"] ??
            "";

        $characterName =
            $character["name"] ??
            $character["character_name"] ??
            $character["player_name"] ??
            $character["code"] ??
            "Unknown";

        jsonResponse([
            "success" => true,
            "game_id" => $gameId,
            "character" => [
                "id" => $characterId,
                "name" => $characterName,
                "hp" => intval($character["hp"] ?? $character["current_hp"] ?? 0),
                "max_hp" => intval($character["max_hp"] ?? 0),
                "temp_hp" => intval($character["temp_hp"] ?? 0),
                "death_success" => intval($character["death_success"] ?? $character["death_saves_success"] ?? 0),
                "death_fail" => intval($character["death_fail"] ?? $character["death_saves_fail"] ?? 0),
                "card_id" => $cardId
            ]
        ]);
    }
}

jsonResponse([
    "success" => false,
    "message" => "No character found for this card.",
    "game_id" => $gameId,
    "card_id" => $cardId
]);
?>
