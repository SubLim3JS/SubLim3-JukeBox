<?php
require_once("inc.header.php");

/*
 * Get WiFi IP address
 */
function getWifiIp() {

    $ip = trim(shell_exec("ip -4 addr show wlan0 2>/dev/null | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}' | head -n 1"));

    if (!empty($ip)) {
        return $ip;
    }

    $ip = trim(shell_exec("hostname -I 2>/dev/null | awk '{print $1}'"));

    if (!empty($ip)) {
        return $ip;
    }

    return "No network connection detected";
}

$ipAddress = getWifiIp();

/*
 * Trigger Phoniebox to read the IP address aloud
 */
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker >/dev/null 2>&1 &");

?>

<div class="container">

<div class="row">
<div class="col-lg-12">

<div class="panel panel-primary">

<div class="panel-heading">
<h3 class="panel-title">
<i class='fa fa-volume-up'></i> Read JukeBox IP Address
</h3>
</div>

<div class="panel-body">

<pre style="font-size:16px">

.
.
.
.    ___      _    _    _       ____     _      _       ___
.   / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
.   \__ \ || | '_ \ |__| | '  \ |_ \ | || | || | / / -_) _ \/ _ \ \ /
.   |___/\_,_|_.__/____|_|_|_|_|___/  \__/ \_,_|_\_\___|___/\___/_\_\
.
.
.

Speaking WiFi IP address through the speaker...

--------------------------------------------------

JukeBox IP Address:

<?php echo $ipAddress; ?>


You can connect to the JukeBox admin interface using:

http://<?php echo $ipAddress; ?>


--------------------------------------------------

IP address has been spoken through the speaker.

</pre>

</div>
</div>
</div>
</div>

</div>

<?php
include("inc.footer.php");
?>
