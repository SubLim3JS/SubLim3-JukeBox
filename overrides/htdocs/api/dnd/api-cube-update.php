<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$gamesDir = $baseDir . "/games";
$activeGameFile = $baseDir . "/active-game";

function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function getInputData() {
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true);

    if (is_array($jsonData)) {
        return $jsonData;
    }

    return $_POST;
}

function cleanId($value) {
    return preg_replace("/[^a-zA-Z0-9\-_]/", "", trim($value));
}

function getCharacterId($character) {
    return $character["id"]
        ?? $character["code"]
        ?? $character["character_id"]
        ?? "";
}

function getCharacterName($character) {
    return $character["name"]
        ?? $character["character_name"]
        ?? $character["player_name"]
        ?? $character["code"]
        ?? "Unknown";
}

$data = getInputData();

$cardId = trim($data["card_id"] ?? "");
$characterId = trim($data["character_id"] ?? "");
$cubeId = cleanId($data["cube_id"] ?? "");

if ($cardId === "" && $characterId === "" && $cubeId === "") {
    jsonResponse([
        "success" => false,
        "message" => "Missing card_id, character_id, or cube_id."
    ]);
}

if (!file_exists($activeGameFile)) {
    jsonResponse([
        "success" => false,
        "message" => "No active game is set."
    ]);
}

$gameId = basename(trim(file_get_contents($activeGameFile)));

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

$updated = false;
$updatedCharacter = null;

foreach ($characters as &$character) {
    $thisCharacterId = getCharacterId($character);
    $thisCardId = $character["card_id"] ?? $character["rfid_id"] ?? "";
    $thisCubeId = $character["cube_id"] ?? "";

    $matchesCard = ($cardId !== "" && (string)$thisCardId === (string)$cardId);
    $matchesCharacter = ($characterId !== "" && (string)$thisCharacterId === (string)$characterId);
    $matchesCube = ($cubeId !== "" && (string)$thisCubeId === (string)$cubeId);

    if ($matchesCard || $matchesCharacter || $matchesCube) {
        if (isset($data["hp"])) {
            $hp = intval($data["hp"]);
            $maxHp = intval($character["max_hp"] ?? 0);

            if ($maxHp > 0) {
                $hp = max(0, min($hp, $maxHp));
            } else {
                $hp = max(0, $hp);
            }

            $character["hp"] = $hp;
            $character["current_hp"] = $hp;
        }

        if (isset($data["temp_hp"])) {
            $tempHp = intval($data["temp_hp"]);
            $character["temp_hp"] = max(0, $tempHp);
        }

        if (isset($data["death_success"])) {
            $deathSuccess = intval($data["death_success"]);
            $deathSuccess = max(0, min($deathSuccess, 3));

            $character["death_success"] = $deathSuccess;
            $character["death_saves_success"] = $deathSuccess;
        }

        if (isset($data["death_fail"])) {
            $deathFail = intval($data["death_fail"]);
            $deathFail = max(0, min($deathFail, 3));

            $character["death_fail"] = $deathFail;
            $character["death_saves_fail"] = $deathFail;
        }

        $character["updated"] = date("Y-m-d H:i:s");

        $updated = true;
        $updatedCharacter = $character;
        break;
    }
}

unset($character);

if (!$updated) {
    jsonResponse([
        "success" => false,
        "message" => "Character not found.",
        "game_id" => $gameId,
        "card_id" => $cardId,
        "character_id" => $characterId,
        "cube_id" => $cubeId
    ]);
}

file_put_contents(
    $charactersFile,
    json_encode($characters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

jsonResponse([
    "success" => true,
    "message" => "Character updated.",
    "game_id" => $gameId,
    "character" => [
        "id" => getCharacterId($updatedCharacter),
        "character_id" => getCharacterId($updatedCharacter),
        "name" => getCharacterName($updatedCharacter),
        "character_name" => getCharacterName($updatedCharacter),
        "player_name" => $updatedCharacter["player_name"] ?? "",
        "hp" => intval($updatedCharacter["hp"] ?? $updatedCharacter["current_hp"] ?? 0),
        "current_hp" => intval($updatedCharacter["hp"] ?? $updatedCharacter["current_hp"] ?? 0),
        "max_hp" => intval($updatedCharacter["max_hp"] ?? 0),
        "temp_hp" => intval($updatedCharacter["temp_hp"] ?? 0),
        "death_success" => intval($updatedCharacter["death_success"] ?? $updatedCharacter["death_saves_success"] ?? 0),
        "death_fail" => intval($updatedCharacter["death_fail"] ?? $updatedCharacter["death_saves_fail"] ?? 0),
        "card_id" => $updatedCharacter["card_id"] ?? $updatedCharacter["rfid_id"] ?? "",
        "cube_id" => $updatedCharacter["cube_id"] ?? ""
    ]
]);
?>
