<?php
$repoDir = "/home/pi/SubLim3-JukeBox";

// Play "update started"
shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh update >/dev/null 2>&1 &");

$cmd = "sudo -u pi bash -c 'cd $repoDir && git pull -q origin main && bash scripts/SubLim3-Jukebox-Update.sh' 2>&1";

exec($cmd, $output, $returnCode);

// Determine result FIRST
$isSuccess = ($returnCode === 0);

// Play correct result sound
if ($isSuccess) {
    shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh success >/dev/null 2>&1 &");
} else {
    shell_exec("bash /home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh error >/dev/null 2>&1 &");
}

// UI text
$statusTitle = $isSuccess ? "Update Completed" : "Update Failed";
$statusNote = $isSuccess
    ? "The SubLim3 JukeBox update completed successfully."
    : "The update failed or completed with errors.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update | SubLim3-JukeBox</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
            text-align: center;
            padding: 40px 20px;
        }
        .card {
            max-width: 800px;
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
        .status {
            font-size: 30px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #2e7d32;
        }
        .status.error {
            color: #c62828;
        }
        .note {
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }
        .output {
            text-align: left;
            background: #f0f0f0;
            border-radius: 8px;
            padding: 18px;
            font-family: Consolas, monospace;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-x: auto;
            max-height: 500px;
            box-shadow: inset 0 1px 4px rgba(0,0,0,0.08);
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
        <h1>SubLim3 JukeBox Update</h1>

        <div class="status <?php echo $isSuccess ? '' : 'error'; ?>">
            <?php echo htmlspecialchars($statusTitle); ?>
        </div>

        <div class="note">
            <?php echo htmlspecialchars($statusNote); ?>
        </div>

        <div class="output"><?php echo htmlspecialchars(implode("\n", $output)); ?></div>

        <a class="btn" href="index.php">Back to Home</a>
    </div>
</body>
</html>
