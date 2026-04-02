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

<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">

<style>
:root {
    --sublim3-primary: #32CD56;
    --sublim3-primary-dark: #28a745;
    --sublim3-primary-light: #7dff9a;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #eaf8ee;
    --sublim3-page-bg-bottom: #f6fbf7;
    --sublim3-card-bg: #ffffff;
    --sublim3-card-shadow: rgba(0, 0, 0, 0.10);
    --sublim3-note-text: #4b5563;
    --sublim3-ip-bg: #0f172a;
    --sublim3-ip-text: #e5e7eb;
    --sublim3-secondary-btn: #4b5563;
    --sublim3-secondary-btn-dark: #374151;
}

/* Green */
body.sublim3-theme-green {
    --sublim3-primary: #32CD56;
    --sublim3-primary-dark: #28a745;
    --sublim3-primary-light: #7dff9a;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #eaf8ee;
    --sublim3-page-bg-bottom: #f6fbf7;
}

/* Blue */
body.sublim3-theme-blue {
    --sublim3-primary: #3498db;
    --sublim3-primary-dark: #217dbb;
    --sublim3-primary-light: #85c1e9;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #eaf4fb;
    --sublim3-page-bg-bottom: #f4f9fd;
}

/* Red */
body.sublim3-theme-red {
    --sublim3-primary: #e74c3c;
    --sublim3-primary-dark: #c0392b;
    --sublim3-primary-light: #f1948a;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #fdf0ef;
    --sublim3-page-bg-bottom: #fff7f6;
}

/* Purple */
body.sublim3-theme-purple {
    --sublim3-primary: #9b59b6;
    --sublim3-primary-dark: #7d3c98;
    --sublim3-primary-light: #c39bd3;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #f5eef8;
    --sublim3-page-bg-bottom: #fbf7fd;
}

/* Orange */
body.sublim3-theme-orange {
    --sublim3-primary: #f39c12;
    --sublim3-primary-dark: #d68910;
    --sublim3-primary-light: #f8c471;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #fef5e7;
    --sublim3-page-bg-bottom: #fffaf2;
}

/* Cyan */
body.sublim3-theme-cyan {
    --sublim3-primary: #1abc9c;
    --sublim3-primary-dark: #148f77;
    --sublim3-primary-light: #76d7c4;
    --sublim3-text-on-primary: #ffffff;
    --sublim3-page-bg-top: #e8f8f5;
    --sublim3-page-bg-bottom: #f4fcfa;
}

/* White */
body.sublim3-theme-white {
    --sublim3-primary: #ecf0f1;
    --sublim3-primary-dark: #bdc3c7;
    --sublim3-primary-light: #ffffff;
    --sublim3-text-on-primary: #222222;
    --sublim3-page-bg-top: #f4f6f7;
    --sublim3-page-bg-bottom: #ffffff;
}

body {
    margin: 0;
    padding: 30px 15px;
    font-family: Arial, sans-serif;
    background: linear-gradient(180deg, var(--sublim3-page-bg-top) 0%, var(--sublim3-page-bg-bottom) 100%);
    color: #1f2937;
}

.wrapper {
    max-width: 920px;
    margin: 0 auto;
}

.card {
    background: var(--sublim3-card-bg);
    border-radius: 16px;
    box-shadow: 0 8px 28px var(--sublim3-card-shadow);
    overflow: hidden;
}

.card-header {
    background: var(--sublim3-primary);
    color: var(--sublim3-text-on-primary);
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
    color: var(--sublim3-note-text);
    margin-bottom: 24px;
}

.ip-label {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
}

.ip-display {
    background: var(--sublim3-ip-bg);
    color: var(--sublim3-ip-text);
    border-radius: 12px;
    padding: 18px;
    font-family: Consolas, monospace;
    font-size: 30px;
    font-weight: bold;
    margin-bottom: 24px;
}

/* Clickable IP */
.ip-display a {
    color: var(--sublim3-ip-text);
    text-decoration: none;
}

.ip-display a:hover {
    text-decoration: underline;
    opacity: 0.85;
}

.actions {
    margin-top: 24px;
}

.btn-sublim3,
.btn-secondary {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    margin: 0 8px;
}

.btn-sublim3 {
    background: var(--sublim3-primary);
    color: var(--sublim3-text-on-primary);
}

.btn-sublim3:hover {
    background: var(--sublim3-primary-dark);
    color: var(--sublim3-text-on-primary);
    text-decoration: none;
}

.btn-secondary {
    background: var(--sublim3-secondary-btn);
    color: #ffffff;
}

.btn-secondary:hover {
    background: var(--sublim3-secondary-btn-dark);
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

    .card-body {
        padding: 20px 16px 24px 16px;
    }

    .btn-sublim3,
    .btn-secondary {
        display: block;
        margin: 10px 0;
    }

    .ip-display {
        font-size: 24px;
    }
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
