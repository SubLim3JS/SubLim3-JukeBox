<?php
include("inc.header.php");

function getCardRegisterAccessConfig($filePath) {
    $config = array(
        'enabled' => true,
        'expires' => null
    );

    if (!file_exists($filePath)) {
        return $config;
    }

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $config;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            if ($key === 'enabled') {
                $valueLower = strtolower($value);
                $config['enabled'] = in_array($valueLower, array('1', 'on', 'true', 'yes', 'enable', 'enabled'), true);
            }

            if ($key === 'expires') {
                $config['expires'] = $value;
            }
        } else {
            $valueLower = strtolower($line);
            $config['enabled'] = in_array($valueLower, array('1', 'on', 'true', 'yes', 'enable', 'enabled'), true);
        }
    }

    return $config;
}

function formatTimeRemaining($seconds) {
    $seconds = max(0, (int)$seconds);

    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    $parts = array();

    if ($days > 0) {
        $parts[] = $days . 'd';
    }
    if ($hours > 0 || $days > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0 || $hours > 0 || $days > 0) {
        $parts[] = $minutes . 'm';
    }

    $parts[] = $secs . 's';

    return implode(' ', $parts);
}

$cardRegisterAccessFile = realpath(getcwd() . '/../settings/') . '/cardRegisterAccess';
$cardRegisterAccessConfig = getCardRegisterAccessConfig($cardRegisterAccessFile);

$cardRegisterEnabled = $cardRegisterAccessConfig['enabled'];
$cardRegisterExpiresTs = null;
$cardRegisterExpired = false;
$cardRegisterCountdownText = '';
$cardRegisterBannerClass = 'alert-success';
$cardRegisterStatusText = 'Card registration access is enabled.';

if (!empty($cardRegisterAccessConfig['expires'])) {
    $expiresTs = strtotime($cardRegisterAccessConfig['expires']);

    if ($expiresTs !== false) {
        $cardRegisterExpiresTs = $expiresTs;
        $secondsRemaining = $expiresTs - time();

        if ($secondsRemaining <= 0) {
            $cardRegisterExpired = true;
            $cardRegisterBannerClass = 'alert-danger';
            $cardRegisterStatusText = 'Card registration access has expired.';
        } else {
            $cardRegisterCountdownText = formatTimeRemaining($secondsRemaining);

            if ($secondsRemaining < 600) {
                $cardRegisterBannerClass = 'alert-danger';
            } elseif ($secondsRemaining <= 1800) {
                $cardRegisterBannerClass = 'alert-warning';
            } else {
                $cardRegisterBannerClass = 'alert-success';
            }

            $cardRegisterStatusText = 'Card registration access expires in ' . $cardRegisterCountdownText . '.';
        }
    }
}

if (!$cardRegisterEnabled) {
    $cardRegisterBannerClass = 'alert-danger';
    $cardRegisterStatusText = 'Card registration access is disabled.';
}

$qrPath = "_assets/icons/SubLim3-JukeBox-App-QR.png";
$qrAbsPath = __DIR__ . "/" . $qrPath;
$qrExists = file_exists($qrAbsPath);
?>

<div class="container">
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 col-md-12">
            <div class="panel panel-default" style="margin-top: 20px;">
                <div class="panel-heading" style="background:#32CD56;color:#fff;">
                    <h3 class="panel-title" style="font-size:24px;font-weight:700;">
                        SubLim3 JukeBox Admin Access
                    </h3>
                </div>

                <div class="panel-body">

                    <div class="alert <?php print $cardRegisterBannerClass; ?>" style="font-size:16px;">
                        <strong>Card Register Status:</strong>
                        <?php print htmlspecialchars($cardRegisterStatusText, ENT_QUOTES, 'UTF-8'); ?>

                        <?php if ($cardRegisterExpiresTs !== null) { ?>
                            <br>
                            <strong>Expires at:</strong>
                            <?php print date("Y-m-d H:i:s", $cardRegisterExpiresTs); ?>
                        <?php } ?>

                        <br><br>
                        <strong>Admin page behavior:</strong>
                        This page always shows the Card Register link, even if the timer has expired.
                    </div>

                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>RFID Tools</strong>
                                </div>
                                <div class="panel-body">
                                    <p>Open the card registration page.</p>
                                    <a href="cardRegisterNew.php" class="btn btn-success btn-block">
                                        Open Card Register
                                    </a>
                                    <p style="margin-top:10px;color:#777;">
                                        Note: if the registration timer is expired, the target page may still block access unless that page is also patched.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>File Manager</strong>
                                </div>
                                <div class="panel-body">
                                    <p>Manage folders and upload content.</p>
                                    <a href="manageFilesFolders.php" class="btn btn-success btn-block">
                                        Open Manage Files / Folders
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default" style="margin-top: 20px;">
                        <div class="panel-heading">
                            <strong>SubLim3 JukeBox App QR Code</strong>
                        </div>
                        <div class="panel-body text-center">
                            <?php if ($qrExists) { ?>
                                <img
                                    src="<?php print htmlspecialchars($qrPath, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="SubLim3 JukeBox App QR Code"
                                    style="max-width:100%; width:320px; height:auto; border:1px solid #ddd; padding:10px; background:#fff;"
                                >
                                <p style="margin-top: 15px;">
                                    Scan to open the SubLim3 JukeBox app link.
                                </p>
                            <?php } else { ?>
                                <div class="alert alert-warning" style="margin-bottom:0;">
                                    QR image not found at:
                                    <br>
                                    <code><?php print htmlspecialchars($qrPath, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <br><br>
                                    Add this file to:
                                    <br>
                                    <code>overrides/icons/SubLim3-JukeBox-App-QR.png</code>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
include("inc.footer.php");
?>
