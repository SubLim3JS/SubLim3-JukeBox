<?php
$updateScript = "/home/pi/SubLim3-JukeBox/scripts/SubLim3-Jukebox-Update.sh";
$feedbackScript = "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh";

// Play "update started" as pi
if (file_exists($feedbackScript)) {
    shell_exec("sudo -u pi nohup bash " . escapeshellarg($feedbackScript) . " update >/dev/null 2>&1 &");
}

$output = array();
$returnCode = 1;

// Run the canonical update script only
if (file_exists($updateScript)) {
    $cmd = "sudo -u pi bash " . escapeshellarg($updateScript) . " 2>&1";
    exec($cmd, $output, $returnCode);
} else {
    $output[] = "[ERROR] Update script not found: " . $updateScript;
    $returnCode = 1;
}

// Determine result
$isSuccess = ($returnCode === 0);

// Play completion sound as pi
if (file_exists($feedbackScript)) {
    if ($isSuccess) {
        shell_exec("sudo -u pi nohup bash " . escapeshellarg($feedbackScript) . " success >/dev/null 2>&1 &");
    } else {
        shell_exec("sudo -u pi nohup bash " . escapeshellarg($feedbackScript) . " error >/dev/null 2>&1 &");
    }
}

// UI text
$statusTitle = $isSuccess ? "Update Completed" : "Update Failed";
$statusNote = $isSuccess
    ? "The SubLim3 JukeBox update completed successfully."
    : "The update failed or completed with errors.";

$statusClass = $isSuccess ? "success" : "error";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update | SubLim3 JukeBox</title>
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

        .output-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #111827;
        }

        .output {
            text-align: left;
            background: #0f172a;
            color: #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-x: auto;
            max-height: 520px;
            box-shadow: inset 0 1px 4px rgba(0,0,0,0.20);
        }

        .actions {
            text-align: center;
            margin-top: 24px;
        }

        .btn {
            display: inline-block;
            margin: 0 8px;
            padding: 12px 20px;
            background: #32CD56;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #28a745;
        }

        .btn.secondary {
            background: #4b5563;
        }

        .btn.secondary:hover {
            background: #374151;
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

            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">
    <div class="wrapper">
        <div class="card">
            <div class="card-header">
                <h1>SubLim3 JukeBox Update</h1>
                <p>Deploying the latest SubLim3 override files and scripts</p>
            </div>

            <div class="card-body">
                <div class="status-banner <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($statusTitle); ?>
                </div>

                <div class="note">
                    <?php echo htmlspecialchars($statusNote); ?>
                </div>

                <div class="output-title">Update Output</div>
                <div class="output"><?php echo htmlspecialchars(implode("\n", $output)); ?></div>

                <div class="actions">
                    <a class="btn" href="index.php">Back to Home</a>
                    <a class="btn secondary" href="systemInfo.php">System Info</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
