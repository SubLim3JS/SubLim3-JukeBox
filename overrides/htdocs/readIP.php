<?php
function getWifiIp() {
    $ip = trim(shell_exec("ip -4 addr show wlan0 | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}' | head -n 1"));

    if (!empty($ip)) {
        return $ip;
    }

    $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));

    if (!empty($ip)) {
        return $ip;
    }

    return "Not connected";
}

$ipAddress = getWifiIp();

shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker >/dev/null 2>&1 &");
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh wifi >/dev/null 2>&1 &");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read IP Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
            text-align: center;
            padding: 40px 20px;
        }
        .card {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
        }
        h1 {
            margin-top: 0;
            font-size: 28px;
        }
        .ip {
            font-size: 32px;
            font-weight: bold;
            color: #2e7d32;
            margin: 20px 0;
            word-break: break-word;
        }
        .note {
            font-size: 16px;
            color: #555;
        }
        .btn {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 20px;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
        .btn:hover {
            background: #256628;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>SubLim3 JukeBox IP Address</h1>
        <div class="ip"><?php echo htmlspecialchars($ipAddress); ?></div>
        <div class="note">The jukebox is now speaking this IP address out loud.</div>
        <a class="btn" href="index.php">Back to Home</a>
    </div>
</body>
</html>
