<?php

include("inc.header.php");

function getWirelessInterface() {
    $preferred = "wlan0";

    if (is_dir("/sys/class/net/" . $preferred . "/wireless")) {
        return $preferred;
    }

    $interfaces = @scandir("/sys/class/net/");
    if ($interfaces !== false) {
        foreach ($interfaces as $iface) {
            if ($iface === "." || $iface === "..") {
                continue;
            }
            if (is_dir("/sys/class/net/" . $iface . "/wireless")) {
                return $iface;
            }
        }
    }

    return "";
}

function getWifiDetails() {
    $iface = getWirelessInterface();

    $result = array(
        "interface" => $iface,
        "connected" => false,
        "ip" => "Not connected",
        "ssid" => "Unknown"
    );

    if (empty($iface)) {
        $result["ip"] = "No wireless interface found";
        $result["ssid"] = "No wireless interface found";
        return $result;
    }

    $operstate = trim(@file_get_contents("/sys/class/net/" . $iface . "/operstate"));
    $ip = trim(shell_exec("ip -4 addr show " . escapeshellarg($iface) . " | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}' | head -n 1"));
    $ssid = trim(shell_exec("iwgetid -r " . escapeshellarg($iface) . " 2>/dev/null"));

    if (!empty($ip) && ($operstate === "up" || !empty($ssid))) {
        $result["connected"] = true;
        $result["ip"] = $ip;
        $result["ssid"] = !empty($ssid) ? $ssid : "Connected";
    } else {
        $result["connected"] = false;
        $result["ip"] = "Not connected";
        $result["ssid"] = "Not connected";
    }

    return $result;
}

function getRfidStatus() {
    return file_exists("/dev/spidev0.0");
}

function getUsbImportStatus() {
    $statusFile = "/home/pi/RPi-Jukebox-RFID/shared/logs/usb-import-status.json";

    if (!file_exists($statusFile) || !is_readable($statusFile)) {
        return null;
    }

    $json = @file_get_contents($statusFile);
    if ($json === false || trim($json) === "") {
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }

    return $data;
}

function checkRpiThrottle() {
    $codes = array(
        0  => "under-voltage detected",
        1  => "arm frequency capped",
        2  => "currently throttled",
        3  => "soft temperature limit active",
        16 => "under-voltage has occurred",
        17 => "arm frequency capped has occurred",
        18 => "throttling has occurred",
        19 => "soft temperature limit has occurred"
    );

    $getThrottledResult = explode("0x", exec("sudo vcgencmd get_throttled"))[1];

    if ($getThrottledResult == "0") return "OK";

    $result = [];
    $codeHex = str_split($getThrottledResult);
    $codeBinary = "";
    foreach ($codeHex as $fourbits) {
        $codeBinary .= str_pad(base_convert($fourbits, 16, 2), 4, "0", STR_PAD_LEFT);
    }
    $codeBinary = array_reverse(str_split($codeBinary));
    foreach ($codeBinary as $bitNumber => $bitValue) {
        if ($bitValue) $result[] = $codes[$bitNumber];
    }
    return "WARNING: " . implode(", ", $result);
}

$exec = "lsb_release -a";
exec($exec, $res);
$distributor = substr($res[0], strpos($res[0], ":") + 1);
$description = substr($res[1], strpos($res[1], ":") + 1);
$release = substr($res[2], strpos($res[2], ":") + 1);
$codename = substr($res[3], strpos($res[3], ":") + 1);
$rpi_temperature = explode("=", exec("sudo vcgencmd measure_temp"))[1];

$wifi = getWifiDetails();
$wifi_interface = $wifi["interface"];
$wifi_connected = $wifi["connected"] ? "Connected" : "Disconnected";
$wifi_ip = $wifi["ip"];
$wifi_ssid = $wifi["ssid"];

$rfid_detected = getRfidStatus();
$usbImportStatus = getUsbImportStatus();
$rpi_throttle = checkRpiThrottle();

if (!empty($usbImportStatus) && isset($usbImportStatus["state"]) && $usbImportStatus["state"] === "running") {
    ?>
    <div class="alert" style="background-color:#31708f; border-color:#2e6da4; color:#ffffff; margin-bottom:20px;">
      <strong><i class='mdi mdi-usb'></i> USB Import In Progress</strong><br>
      <?php
        if (!empty($usbImportStatus["message"])) {
            echo htmlspecialchars($usbImportStatus["message"], ENT_QUOTES, 'UTF-8');
        } else {
            echo "Importing audio files from USB...";
        }
      ?><br>
      <?php if (!empty($usbImportStatus["updated"])) { ?>
        <small style="color:#d9edf7;">Last updated: <?php echo htmlspecialchars($usbImportStatus["updated"], ENT_QUOTES, 'UTF-8'); ?></small><br>
      <?php } ?>
      Please do not remove the USB drive yet.
    </div>
    <?php
}
?>

<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['infoOsDistrib']; ?></label>
  <div class="col-md-6"><?php echo trim($distributor); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['globalDescription']; ?></label>
  <div class="col-md-6"><?php echo trim($description); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['globalRelease']; ?></label>
  <div class="col-md-6"><?php echo trim($release); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['infoOsCodename']; ?></label>
  <div class="col-md-6"><?php echo trim($codename); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for="">WiFi Interface</label>
  <div class="col-md-6"><?php echo !empty($wifi_interface) ? htmlspecialchars($wifi_interface, ENT_QUOTES, 'UTF-8') : "Not found"; ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for="">WiFi Status</label>
  <div class="col-md-6">
    <?php if ($wifi["connected"]) { ?>
      <span class="label label-success"><?php echo htmlspecialchars($wifi_connected, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php } else { ?>
      <span class="label label-danger"><?php echo htmlspecialchars($wifi_connected, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php } ?>
  </div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for="">WiFi SSID</label>
  <div class="col-md-6"><?php echo htmlspecialchars($wifi_ssid, ENT_QUOTES, 'UTF-8'); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for="">IP Address</label>
  <div class="col-md-6"><?php echo htmlspecialchars($wifi_ip, ENT_QUOTES, 'UTF-8'); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for="">RFID Status</label>
  <div class="col-md-6">
    <?php if ($rfid_detected) { ?>
      <span class="label label-success">Detected</span>
    <?php } else { ?>
      <span class="label label-danger">Not Detected</span>
    <?php } ?>
  </div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['infoOsThrottle']; ?></label>
  <div class="col-md-6"><?php echo trim($rpi_throttle); ?></div>
</div>
<div class="row">
  <label class="col-md-4 control-label" for=""><?php print $lang['infoOsTemperature']; ?></label>
  <div class="col-md-6"><?php echo trim($rpi_temperature); ?></div>
</div>
