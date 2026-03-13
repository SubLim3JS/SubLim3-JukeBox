<?php

require_once("func.php");

function getWifiIp() {

    $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));

    if (!empty($ip)) {
        return $ip;
    }

    $ip = trim(shell_exec("ip -4 addr show wlan0 | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}' | head -n 1"));

    if (!empty($ip)) {
        return $ip;
    }

    return "Not connected";
}

$ipAddress = getWifiIp();

shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker >/dev/null 2>&1 &");

include("header.php");
?>

<div class="container">

    <h2>SubLim3 JukeBox IP Address</h2>

    <p>The jukebox is speaking the IP address out loud.</p>

    <div style="font-size:32px;font-weight:bold;margin-top:20px;">
        <?php echo htmlspecialchars($ipAddress); ?>
    </div>

    <br><br>

    <a class="btn btn-primary" href="index.php">Return to Home</a>

</div>

<?php
include("footer.php");
?>
<body>
<div class="container">

<?php include("inc.navigation.php"); ?>

<div class="panel-group">
  <div class="panel panel-default">

    <div class="panel-heading">
      <h4 class="panel-title">
        <i class='mdi mdi-update'></i> SubLim3-JukeBox Update
      </h4>
    </div>

    <div class="panel-body">

<?php

$repoDir = "/home/pi/SubLim3-JukeBox";
$cmd = "sudo -u pi bash -c 'cd $repoDir && git pull -q origin main && bash SubLim3-Updates.sh' 2>&1";

exec($cmd, $output, $returnCode);

echo "<pre>";
foreach ($output as $line) {
    echo htmlspecialchars($line) . "\n";
}
echo "</pre>";

if ($returnCode === 0) {
    echo "<div class='alert alert-success'>Update completed successfully.</div>";
} else {
    echo "<div class='alert alert-danger'>Update failed or completed with errors.</div>";
}

?>

    </div>
  </div>
</div>

</div>
</body>
</html>
