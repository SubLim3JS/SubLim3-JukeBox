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
$isConnected = ($ipAddress !== "Not connected");

// Run audio feedback
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker >/dev/null 2>&1 &");
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh wifi >/dev/null 2>&1 &");
?>

<style>
body {
    margin: 0;
    padding: 30px 15px;
    font-family: Arial, sans-serif;
    background: linear-gradient(180deg, #eaf8ee 0%, #f6fbf7 100%);
    color: #1f2937;
}

.wrapper {
    max-width: 920px;
    margin: 0 auto;
}

.card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.10);
    overflow: hidden;
}

.card-header {
    background: #32CD56;
    color: #ffffff;
    text-align: center;
    padding: 24px 20px;
}

.card-header h1 {
    margin: 0;
    font-size: 30px;
}

.card-body {
    padding: 28px 24px 30px 24px;
    text-align: center;
}

.status-banner {
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 18px;
    font-size: 24px;
    font-weight: bold;
}

.status-banner.success {
    background: #e8f7ec;
    color: #1f7a33;
}

.status-banner.error {
    background: #fdecec;
    color: #b42318;
}

.note {
    font-size: 16px;
    color: #4b5563;
    margin-bottom: 24px;
}

.ip-label {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
}

.ip-display {
    background: #0f172a;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 18px;
    font-family: Consolas, monospace;
    font-size: 30px;
    font-weight: bold;
    margin-bottom: 24px;
}

/* NEW: clean clickable style */
.ip-display a {
    color: #e5e7eb;
    text-decoration: none;
}

.ip-display a:hover {
    text-decoration: underline;
    opacity: 0.85;
}

.actions {
    margin-top: 24px;
}

.btn-sublim3 {
    padding: 12px 20px;
    background: #32CD56;
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
}

.btn-secondary {
    padding: 12px 20px;
    background: #4b5563;
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
}
</style>

<div class="wrapper">
    <div class="card">
        <div class="card-header">
            <h1>SubLim3 JukeBox IP Address</h1>
        </div>

        <div class="card-body">
            <div class="status-banner <?php echo $isConnected ? 'success' : 'error'; ?>">
                <?php echo $isConnected ? 'WiFi Connected' : 'WiFi Not Connected'; ?>
            </div>

            <div class="note">
                <?php echo $isConnected
                    ? 'The jukebox is now speaking this IP address.'
                    : 'No active IP address found.'; ?>
            </div>

            <div class="ip-label">Current Device IP</div>

            <div class="ip-display">
                <?php if ($isConnected) { ?>
                    <a href="http://<?php echo htmlspecialchars($ipAddress); ?>" target="_blank">
                        <?php echo htmlspecialchars($ipAddress); ?>
                    </a>
                <?php } else { ?>
                    <?php echo htmlspecialchars($ipAddress); ?>
                <?php } ?>
            </div>

            <div class="actions">
                <a href="index.php" class="btn-sublim3">Back to Home</a>
                <a href="systemInfo.php" class="btn-secondary">System Info</a>
            </div>
        </div>
    </div>
</div>

<?php include("inc.footer.php"); ?>
