<?php
$updateScript = "/home/pi/SubLim3-JukeBox/scripts/SubLim3-Jukebox-Update.sh";
$feedbackScript = "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh";

/*
 * SubLim3 Theme Support
 */
$sublim3AllowedThemes = array('green', 'blue', 'red', 'purple', 'orange', 'cyan', 'white');
$sublim3ThemeFile = '/home/pi/RPi-Jukebox-RFID/settings/theme-color';
$sublim3Theme = 'green';

if (file_exists($sublim3ThemeFile)) {
    $savedTheme = trim(file_get_contents($sublim3ThemeFile));
    if (in_array($savedTheme, $sublim3AllowedThemes)) {
        $sublim3Theme = $savedTheme;
    }
}

$sublim3ThemeClass = 'sublim3-theme-' . $sublim3Theme;

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
            --sublim3-output-title: #111827;
            --sublim3-output-bg: #0f172a;
            --sublim3-output-text: #e5e7eb;
            --sublim3-secondary-btn: #4b5563;
            --sublim3-secondary-btn-dark: #374151;
        }

        body.sublim3-theme-green {
            --sublim3-primary: #32CD56;
            --sublim3-primary-dark: #28a745;
            --sublim3-primary-light: #7dff9a;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #eaf8ee;
            --sublim3-page-bg-bottom: #f6fbf7;
        }

        body.sublim3-theme-blue {
            --sublim3-primary: #3498db;
            --sublim3-primary-dark: #217dbb;
            --sublim3-primary-light: #85c1e9;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #eaf4fb;
            --sublim3-page-bg-bottom: #f4f9fd;
        }

        body.sublim3-theme-red {
            --sublim3-primary: #e74c3c;
            --sublim3-primary-dark: #c0392b;
            --sublim3-primary-light: #f1948a;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #fdf0ef;
            --sublim3-page-bg-bottom: #fff7f6;
        }

        body.sublim3-theme-purple {
            --sublim3-primary: #9b59b6;
            --sublim3-primary-dark: #7d3c98;
            --sublim3-primary-light: #c39bd3;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #f5eef8;
            --sublim3-page-bg-bottom: #fbf7fd;
        }

        body.sublim3-theme-orange {
            --sublim3-primary: #f39c12;
            --sublim3-primary-dark: #d68910;
            --sublim3-primary-light: #f8c471;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #fef5e7;
            --sublim3-page-bg-bottom: #fffaf2;
        }

        body.sublim3-theme-cyan {
            --sublim3-primary: #1abc9c;
            --sublim3-primary-dark: #148f77;
            --sublim3-primary-light: #76d7c4;
            --sublim3-text-on-primary: #ffffff;
            --sublim3-page-bg-top: #e8f8f5;
            --sublim3-page-bg-bottom: #f4fcfa;
        }

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
            color: var(--sublim3-note-text);
            margin-bottom: 24px;
        }

        .output-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--sublim3-output-title);
        }

        .output {
            text-align: left;
            background: var(--sublim3-output-bg);
            color: var(--sublim3-output-text);
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
            background: var(--sublim3-primary);
            color: var(--sublim3-text-on-primary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: var(--sublim3-primary-dark);
        }

        .btn.secondary {
            background: var(--sublim3-secondary-btn);
            color: #ffffff;
        }

        .btn.secondary:hover {
            background: var(--sublim3-secondary-btn-dark);
            color: #ffffff;
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
<body class="<?php print htmlspecialchars($sublim3ThemeClass); ?>">
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
