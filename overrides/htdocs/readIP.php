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

// Run audio feedback in background without blocking page load
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
        line-height: 1.2;
    }

    .card-header p {
        margin: 10px 0 0 0;
        font-size: 15px;
        opacity: 0.95;
    }

    .card-body {
        padding: 28px 24px 30px 24px;
        text-align: center;
    }

    .status-banner {
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 18px;
        font-size: 24px;
        font-weight: bold;
        text-align: center;
    }

    .status-banner.success {
        background: #e8f7ec;
        color: #1f7a33;
        border: 1px solid #b9e7c3;
    }

    .status-banner.error {
        background: #fdecec;
        color: #b42318;
        border: 1px solid #f5b5b0;
    }

    .note {
        text-align: center;
        font-size: 16px;
        color: #4b5563;
        margin-bottom: 24px;
    }

    .ip-label {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #111827;
    }

    .ip-display {
        background: #0f172a;
        color: #e5e7eb;
        border-radius: 12px;
        padding: 18px;
        font-family: Consolas, "Courier New", monospace;
        font-size: 30px;
        font-weight: bold;
        line-height: 1.4;
        word-break: break-word;
        box-shadow: inset 0 1px 4px rgba(0,0,0,0.20);
        margin-bottom: 24px;
    }

    .actions {
        text-align: center;
        margin-top: 24px;
    }

    .btn-sublim3 {
        display: inline-block;
        margin: 0 8px;
        padding: 12px 20px;
        background: #32CD56;
        color: #ffffff;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
        transition: background 0.2s ease;
        border: none;
    }

    .btn-sublim3:hover,
    .btn-sublim3:focus {
        background: #28a745;
        color: #ffffff;
        text-decoration: none;
    }

    .btn-secondary {
        display: inline-block;
        margin: 0 8px;
        padding: 12px 20px;
        background: #4b5563;
        color: #ffffff;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
        transition: background 0.2s ease;
        border: none;
    }

    .btn-secondary:hover,
    .btn-secondary:focus {
        background: #374151;
        color: #ffffff;
        text-decoration: none;
    }

    @media (max-width: 640px) {
        .card-header h1 {
            font-size: 24px;
        }

        .status-banner {
            font-size: 20px;
        }

        .ip-display {
            font-size: 22px;
        }

        .card-body {
            padding: 20px 16px 24px 16px;
        }

        .btn-sublim3,
        .btn-secondary {
            display: block;
            margin: 10px 0;
        }
    }
</style>

<div class="wrapper">
    <div class="card">
        <div class="card-header">
            <h1>SubLim3 JukeBox IP Address</h1>
            <p>Reads the current device IP address and speaks it aloud</p>
        </div>

        <div class="card-body">
            <div class="status-banner <?php echo $isConnected ? 'success' : 'error'; ?>">
                <?php echo $isConnected ? 'WiFi Connected' : 'WiFi Not Connected'; ?>
            </div>

            <div class="note">
                <?php
                echo $isConnected
                    ? 'The jukebox is now speaking this IP address out loud.'
                    : 'No active IP address was found.';
                ?>
            </div>

            <div class="ip-label">Current Device IP</div>
            <div class="ip-display">
                <?php echo htmlspecialchars($ipAddress); ?>
            </div>

            <div class="actions">
                <a href="index.php" class="btn-sublim3">Back to Home</a>
                <a href="systemInfo.php" class="btn-secondary">System Info</a>
            </div>
        </div>
    </div>
</div>

<?php
include("inc.footer.php");
?>
