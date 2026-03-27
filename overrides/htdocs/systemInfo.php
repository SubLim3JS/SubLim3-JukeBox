<?php

include("inc.header.php");

html_bootstrap3_createHeader("en","System Info | SubLim3 JukeBox",$conf['base_url']);

?>
<body>
<div class="container">

<?php include("inc.navigation.php"); ?>

<?php

/**************************************************
* WIFI FUNCTIONS
**************************************************/

function getWirelessInterface() {
    if (is_dir("/sys/class/net/wlan0/wireless")) {
        return "wlan0";
    }

    $interfaces = @scandir("/sys/class/net/");
    if ($interfaces !== false) {
        foreach ($interfaces as $iface) {
            if ($iface === "." || $iface === "..") continue;
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
        "ssid" => "Not connected"
    );

    if (empty($iface)) {
        $result["ip"] = "No wireless interface";
        return $result;
    }

    $operstate = trim(@file_get_contents("/sys/class/net/$iface/operstate"));
    $ip = trim(shell_exec("ip -4 addr show $iface | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}' | head -n 1"));
    $ssid = trim(shell_exec("iwgetid -r $iface 2>/dev/null"));

    if (!empty($ip) && ($operstate === "up" || !empty($ssid))) {
        $result["connected"] = true;
        $result["ip"] = $ip;
        $result["ssid"] = $ssid ?: "Connected";
    }

    return $result;
}

/**************************************************
* RFID FUNCTION
**************************************************/

function getRfidStatus() {
    $spiDevice = "/dev/spidev0.0";

    if (file_exists($spiDevice)) {
        return array(
            "status" => true,
            "message" => "RFID Reader Detected (SPI active)"
        );
    }

    return array(
        "status" => false,
        "message" => "RFID Reader Not Detected"
    );
}

/**************************************************
* SYSTEM INFO
**************************************************/

exec("lsb_release -a", $res);

$distributor = trim(substr($res[0], strpos($res[0], ":") + 1));
$description = trim(substr($res[1], strpos($res[1], ":") + 1));
$release = trim(substr($res[2], strpos($res[2], ":") + 1));
$codename = trim(substr($res[3], strpos($res[3], ":") + 1));

$rpi_temperature = explode("=", exec("sudo vcgencmd measure_temp"))[1];

/**************************************************
* LOAD STATUS DATA
**************************************************/

$wifi = getWifiDetails();
$rfid = getRfidStatus();

/**************************************************
* THROTTLE CHECK
**************************************************/

function checkRpiThrottle() {
    $codes = array(
        0=>"under-voltage detected",
        1=>"arm frequency capped",
        2=>"currently throttled",
        3=>"soft temperature limit active",
        16=>"under-voltage has occurred",
        17=>"arm frequency capped has occurred",
        18=>"throttling has occurred",
        19=>"soft temperature limit has occurred"
    );

    $getThrottledResult = explode("0x", exec("sudo vcgencmd get_throttled"))[1];

    if ($getThrottledResult == "0") return "OK";

    $result = [];
    $codeBinary = "";
    foreach (str_split($getThrottledResult) as $hex) {
        $codeBinary .= str_pad(base_convert($hex, 16, 2), 4, "0", STR_PAD_LEFT);
    }

    $codeBinary = array_reverse(str_split($codeBinary));

    foreach ($codeBinary as $bit => $val) {
        if ($val) $result[] = $codes[$bit];
    }

    return "WARNING: " . implode(", ", $result);
}

$rpi_throttle = checkRpiThrottle();

?>

<div class="panel panel-default">
<div class="panel-heading">
    <h4 class="panel-title"><i class='mdi mdi-settings'></i> System</h4>
</div>

<div class="panel-body">

<div class="row">
  <label class="col-md-4 control-label">OS</label>
  <div class="col-md-6"><?php echo $description; ?></div>
</div>

<div class="row">
  <label class="col-md-4 control-label">Release</label>
  <div class="col-md-6"><?php echo $release; ?></div>
</div>

<div class="row">
  <label class="col-md-4 control-label">Codename</label>
  <div class="col-md-6"><?php echo $codename; ?></div>
</div>

<hr>

<!-- WIFI -->

<div class="row">
  <label class="col-md-4 control-label">WiFi Interface</label>
  <div class="col-md-6"><?php echo $wifi["interface"] ?: "Not found"; ?></div>
</div>

<div class="row">
  <label class="col-md-4 control-label">WiFi Status</label>
  <div class="col-md-6">
    <?php if ($wifi["connected"]) { ?>
      <span class="label label-success">Connected</span>
    <?php } else { ?>
      <span class="label label-danger">Disconnected</span>
    <?php } ?>
  </div>
</div>

<div class="row">
  <label class="col-md-4 control-label">SSID</label>
  <div class="col-md-6"><?php echo htmlspecialchars($wifi["ssid"]); ?></div>
</div>

<div class="row">
  <label class="col-md-4 control-label">IP Address</label>
  <div class="col-md-6"><?php echo htmlspecialchars($wifi["ip"]); ?></div>
</div>

<hr>

<!-- RFID -->

<div class="row">
  <label class="col-md-4 control-label">RFID Status</label>
  <div class="col-md-6">
    <?php if ($rfid["status"]) { ?>
      <span class="label label-success">
        <?php echo htmlspecialchars($rfid["message"]); ?>
      </span>
    <?php } else { ?>
      <span class="label label-danger">
        <?php echo htmlspecialchars($rfid["message"]); ?>
      </span>
    <?php } ?>
  </div>
</div>

<hr>

<div class="row">
  <label class="col-md-4 control-label">Throttle</label>
  <div class="col-md-6"><?php echo $rpi_throttle; ?></div>
</div>

<div class="row">
  <label class="col-md-4 control-label">Temperature</label>
  <div class="col-md-6"><?php echo $rpi_temperature; ?></div>
</div>

</div>
</div>

</div>
</body>
</html>
