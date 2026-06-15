<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";
$activeGameFile = $baseDir . "/active-game";
$availableCubesFile = $baseDir . "/available-cubes.json";

function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function readJson($file, $default = []) {
    if (!file_exists($file)) {
        return $default;
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

function writeJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function cleanId($value) {
    return preg_replace("/[^a-zA-Z0-9\-_]/", "", trim($value));
}

function getCharacterId($character, $index = 0) {
    return $character["id"]
        ?? $character["code"]
        ?? $character["character_id"]
        ?? ("character_" . $index);
}

function getCharacterName($character) {
    return $character["name"]
        ?? $character["character_name"]
        ?? $character["player_name"]
        ?? $character["code"]
        ?? "Unknown";
}

function rememberCube($availableCubesFile, $cubeId) {
    if ($cubeId === "") {
        return;
    }

    $cubes = readJson($availableCubesFile, []);
    $found = false;

    foreach ($cubes as &$cube) {
        if (($cube["cube_id"] ?? "") === $cubeId) {
            $cube["last_seen"] = date("Y-m-d H:i:s");
            $found = true;
            break;
        }
    }
    unset($cube);

    if (!$found) {
        $cubes[] = [
            "cube_id" => $cubeId,
            "name" => $cubeId,
            "last_seen" => date("Y-m-d H:i:s")
        ];
    }

    writeJson($availableCubesFile, $cubes);
}

$cardId = trim($_GET["card_id"] ?? "");
$cubeId = cleanId($_GET["cube_id"] ?? "");

if ($cardId === "" && $cubeId === "") {
    jsonResponse([
        "success" => false,
        "assigned" => false,
        "message" => "Missing card_id or cube_id."
    ]);
}

if (!file_exists($activeGameFile)) {
    jsonResponse([
        "success" => false,
        "assigned" => false,
        "message" => "No active game is set."
    ]);
}

$gameId = basename(trim(file_get_contents($activeGameFile)));
$gamePath = $gamesDir . "/" . $gameId;
$charactersFile = $gamePath . "/characters.json";

if ($gameId === "" || !is_dir($gamePath) || !file_exists($charactersFile)) {
    jsonResponse([
        "success" => false,
        "assigned" => false,
        "message" => "Active game not found.",
        "game_id" => $gameId
    ]);
}

if ($cubeId !== "") {
    rememberCube($availableCubesFile, $cubeId);
}

$characters = readJson($charactersFile, []);

foreach ($characters as $index => $character) {
    $characterCardId = $character["card_id"] ?? $character["rfid_id"] ?? "";
    $characterCubeId = $character["cube_id"] ?? "";

    $matchedByCard = ($cardId !== "" && (string)$characterCardId === (string)$cardId);
    $matchedByCube = ($cubeId !== "" && (string)$characterCubeId === (string)$cubeId);

    if ($matchedByCard || $matchedByCube) {
        $characterId = getCharacterId($character, $index);
        $characterName = getCharacterName($character);

        jsonResponse([
            "success" => true,
            "assigned" => true,
            "game_id" => $gameId,
            "cube_id" => $cubeId,
            "character" => [
                "id" => $characterId,
                "character_id" => $characterId,
                "name" => $characterName,
                "character_name" => $characterName,
                "player_name" => $character["player_name"] ?? "",
                "hp" => intval($character["hp"] ?? $character["current_hp"] ?? 0),
                "current_hp" => intval($character["hp"] ?? $character["current_hp"] ?? 0),
                "max_hp" => intval($character["max_hp"] ?? 0),
                "temp_hp" => intval($character["temp_hp"] ?? 0),
                "death_success" => intval($character["death_success"] ?? $character["death_saves_success"] ?? 0),
                "death_fail" => intval($character["death_fail"] ?? $character["death_saves_fail"] ?? 0),
                "card_id" => $characterCardId,
                "cube_id" => $characterCubeId
            ]
        ]);
    }
}

jsonResponse([
    "success" => true,
    "assigned" => false,
    "message" => "No character assigned yet.",
    "game_id" => $gameId,
    "card_id" => $cardId,
    "cube_id" => $cubeId
]);
?>
