<?php
header('Content-Type: application/json');

$gameDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$cubesFile = $gameDir . "/cubes.json";

if (!is_dir($gameDir)) {
    mkdir($gameDir, 0775, true);
}

if (!file_exists($cubesFile)) {
    file_put_contents($cubesFile, json_encode([], JSON_PRETTY_PRINT));
}

$cubeId = $_POST["cube_id"] ?? $_GET["cube_id"] ?? "";

if ($cubeId === "") {
    echo json_encode([
        "success" => false,
        "error" => "Missing cube_id"
    ]);
    exit;
}

$cubes = json_decode(file_get_contents($cubesFile), true);
if (!is_array($cubes)) {
    $cubes = [];
}

$cubes[$cubeId] = [
    "cube_id" => $cubeId,
    "status" => "waiting_for_card",
    "assigned_character_id" => null,
    "last_seen" => date("Y-m-d H:i:s")
];

file_put_contents($cubesFile, json_encode($cubes, JSON_PRETTY_PRINT));

echo json_encode([
    "success" => true,
    "cube_id" => $cubeId,
    "status" => "waiting_for_card",
    "message" => "Tap Character Card on DnD Book"
]);
