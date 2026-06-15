<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$baseDir = "/home/pi/RPi-Jukebox-RFID/shared/dnd-game";
$lastScanFile = $baseDir . "/last-scan";

function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0775, true);
}

$action = $_GET["action"] ?? $_POST["action"] ?? "";

if ($action === "clear") {
    if (file_exists($lastScanFile)) {
        unlink($lastScanFile);
    }

    jsonResponse([
        "success" => true,
        "message" => "Last scan cleared."
    ]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $cardId = trim($data["card_id"] ?? "");

    if ($cardId === "") {
        jsonResponse([
            "success" => false,
            "message" => "Missing card_id."
        ]);
    }

    $scanData = [
        "card_id" => $cardId,
        "scanned_at" => date("Y-m-d H:i:s"),
        "timestamp" => time()
    ];

    file_put_contents(
        $lastScanFile,
        json_encode($scanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    jsonResponse([
        "success" => true,
        "message" => "Last scan saved.",
        "scan" => $scanData
    ]);
}

if (!file_exists($lastScanFile)) {
    jsonResponse([
        "success" => false,
        "message" => "No card has been scanned yet."
    ]);
}

$scanData = json_decode(file_get_contents($lastScanFile), true);

if (!is_array($scanData)) {
    jsonResponse([
        "success" => false,
        "message" => "Last scan file is invalid."
    ]);
}

jsonResponse([
    "success" => true,
    "scan" => [
        "card_id" => $scanData["card_id"] ?? "",
        "scanned_at" => $scanData["scanned_at"] ?? "",
        "timestamp" => intval($scanData["timestamp"] ?? 0)
    ]
]);
?>
