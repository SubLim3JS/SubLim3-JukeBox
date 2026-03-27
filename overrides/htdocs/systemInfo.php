<?php

include("inc.header.php");

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en","System Info | SubLim3 JukeBox",$conf['base_url']);

?>
<body>
  <div class="container">

<?php
include("inc.navigation.php");

/**
 * Get wireless interface name
 */
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

/**
 * Get WiFi connection details
 */
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

/**
 * RFID detection (NEW)
 */
function getRfidStatus() {
    return file_exists("/dev/spidev0.0");
}

// get System Information and parse into variables
$exec = "lsb_release -a";
if($debug == "true") {
    print "Command: ".$exec;
}
exec($exec, $res);
$distributor = substr($res[0], strpos($res[0], ":") + 1, strlen($res[0]) - strpos($res[0], ":"));
$description = substr($res[1], strpos($res[1], ":") + 1, strlen($res[1]) - strpos($res[1], ":"));
$release = substr($res[2], strpos($res[2], ":") + 1, strlen($res[2]) - strpos($res[2], ":"));
$codename = substr($res[3], strpos($res[3], ":") + 1, strlen($res[3]) - strpos($res[3], ":"));
$rpi_temperature = explode("=", exec("sudo vcgencmd measure_temp"))[1];

// WiFi details
$wifi = getWifiDetails();
$wifi_interface = $wifi["interface"];
$wifi_connected = $wifi["connected"] ? "Connected" : "Disconnected";
$wifi_ip = $wifi["ip"];
$wifi_ssid = $wifi["ssid"];

// RFID (NEW)
$rfid_detected = getRfidStatus();

// check RPis throttling state
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
$rpi_throttle = checkRpiThrottle();

?>
<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
         <i class='mdi mdi-settings'></i> <?php print $lang['globalSystem']; ?>
      </h4>
    </div>

    <div class="panel-body">

        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['infoOsDistrib']; ?></label>
          <div class="col-md-6"><?php echo trim($distributor); ?></div>
        </div>
        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['globalDescription']; ?></label>
          <div class="col-md-6"><?php echo trim($description); ?></div>
        </div>
        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['globalRelease']; ?></label>
          <div class="col-md-6"><?php echo trim($release); ?></div>
        </div>
        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['infoOsCodename']; ?></label>
          <div class="col-md-6"><?php echo trim($codename); ?></div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label">WiFi Interface</label>
          <div class="col-md-6"><?php echo !empty($wifi_interface) ? htmlspecialchars($wifi_interface) : "Not found"; ?></div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label">WiFi Status</label>
          <div class="col-md-6">
            <?php if ($wifi["connected"]) { ?>
              <span class="label label-success"><?php echo htmlspecialchars($wifi_connected); ?></span>
            <?php } else { ?>
              <span class="label label-danger"><?php echo htmlspecialchars($wifi_connected); ?></span>
            <?php } ?>
          </div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label">WiFi SSID</label>
          <div class="col-md-6"><?php echo htmlspecialchars($wifi_ssid); ?></div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label">IP Address</label>
          <div class="col-md-6"><?php echo htmlspecialchars($wifi_ip); ?></div>
        </div>

        <!-- RFID STATUS (ONLY ADDITION) -->
        <div class="row">
          <label class="col-md-4 control-label">RFID Status</label>
          <div class="col-md-6">
            <?php if ($rfid_detected) { ?>
              <span class="label label-success">Detected</span>
            <?php } else { ?>
              <span class="label label-danger">Not Detected</span>
            <?php } ?>
          </div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['infoOsThrottle']; ?></label>
          <div class="col-md-6"><?php echo trim($rpi_throttle); ?></div>
        </div>

        <div class="row">
          <label class="col-md-4 control-label"><?php print $lang['infoOsTemperature']; ?></label>
          <div class="col-md-6"><?php echo trim($rpi_temperature); ?></div>
        </div>

    </div>
  </div>
</div>

<!-- EVERYTHING BELOW REMAINS UNCHANGED (version, debug log, update button, etc.) -->

<?php
include("inc.addSystemInfo.php");
?>

</div>
</body>
</html>
