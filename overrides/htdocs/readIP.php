<?php
include("inc.header.php");

function getWifiIp() {
    // Prefer wlan0 IPv4
    $ip = trim(shell_exec("ip -4 addr show wlan0 2>/dev/null | awk '/inet / {print \$2}' | cut -d/ -f1 | head -n 1"));

    if (!empty($ip)) {
        return $ip;
    }

    // Fallback to first available IPv4
    $ip = trim(shell_exec("hostname -I 2>/dev/null | awk '{print \$1}'"));

    if (!empty($ip)) {
        return $ip;
    }

    return "Not connected";
}

$ipAddress = getWifiIp();

// Run audio feedback in background without blocking page load
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker >/dev/null 2>&1 &");
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh wifi >/dev/null 2>&1 &");
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
            <div class="panel panel-default" style="margin-top: 30px; border-radius: 14px; overflow: hidden;">
                <div class="panel-heading text-center" style="background: #32CD56; color: #fff; padding: 18px;">
                    <h2 style="margin: 0;">SubLim3 JukeBox IP Address</h2>
                </div>
                <div class="panel-body text-center" style="padding: 35px 25px;">
                    <p style="font-size: 18px; margin-bottom: 10px;">Current device IP:</p>

                    <div style="
                        font-size: 34px;
                        font-weight: bold;
                        color: #32CD56;
                        margin: 20px 0 25px 0;
                        word-break: break-word;
                    ">
                        <?php echo htmlspecialchars($ipAddress); ?>
                    </div>

                    <p style="font-size: 16px; color: #666; margin-bottom: 25px;">
                        The jukebox is now speaking this IP address out loud.
                    </p>

                    <a href="index.php" class="btn btn-success btn-lg" style="background: #32CD56; border-color: #32CD56;">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include("inc.footer.php");
?>
