<?php

function getWirelessInterface() {
    if (is_dir("/sys/class/net/wlan0/wireless")) {
        return "wlan0";
    }
    foreach (scandir("/sys/class/net/") as $iface) {
        if ($iface !== "." && $iface !== ".." && is_dir("/sys/class/net/$iface/wireless")) {
            return $iface;
        }
    }
    return "";
}

function getWifiDetails() {
    $iface = getWirelessInterface();

    $ip = trim(shell_exec("ip -4 addr show $iface 2>/dev/null | awk '/inet / {print \$2}' | cut -d/ -f1"));
    $ssid = trim(shell_exec("iwgetid -r $iface 2>/dev/null"));

    return [
        "iface" => $iface,
        "ip" => $ip ?: "Not connected",
        "ssid" => $ssid ?: "Not connected",
        "connected" => !empty($ip)
    ];
}

function getRfidStatus() {
    return file_exists("/dev/spidev0.0");
}

function getUsbImportStatus() {
    $file = "/home/pi/RPi-Jukebox-RFID/shared/logs/usb-import-status.json";
    if (!file_exists($file)) return null;
    return json_decode(file_get_contents($file), true);
}

$wifi = getWifiDetails();
$rfid = getRfidStatus();
$usb = getUsbImportStatus();

/* USB Banner */
if (!empty($usb) && $usb["state"] === "running") {
    echo "<div class='alert' style='background:#31708f;color:white;margin-bottom:15px;'>
    <strong>USB Import In Progress</strong><br>"
    . htmlspecialchars($usb["message"]) . "<br>
    <small>" . htmlspecialchars($usb["updated"]) . "</small>
    </div>";
}

/* WiFi */
echo "<div><strong>WiFi:</strong> ";
echo $wifi["connected"]
    ? "<span style='color:lime;'>Connected</span>"
    : "<span style='color:red;'>Disconnected</span>";
echo "</div>";

echo "<div><strong>SSID:</strong> " . htmlspecialchars($wifi["ssid"]) . "</div>";
echo "<div><strong>IP:</strong> " . htmlspecialchars($wifi["ip"]) . "</div>";

/* RFID */
echo "<div><strong>RFID:</strong> ";
echo $rfid
    ? "<span style='color:lime;'>Detected</span>"
    : "<span style='color:red;'>Not Detected</span>";
echo "</div>";

?>
