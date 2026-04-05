<?php
include("inc.header.php");

// ==============================
// SubLim3: Card Register Access Control
// ==============================
$accessFile = "/home/pi/RPi-Jukebox-RFID/settings/cardRegisterAccess";
$enabled = false;
$expires = null;

if (file_exists($accessFile)) {
    $lines = file($accessFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, "enabled=") === 0) {
            $enabled = trim(explode("=", $line)[1]) == "1";
        }
        if (strpos($line, "expires=") === 0) {
            $expires = trim(explode("=", $line)[1]);
        }
    }
}

// Determine access state
$accessGranted = false;
$timeRemaining = 0;

if ($enabled && $expires) {
    $now = time();
    $expireTime = strtotime($expires);
    if ($expireTime > $now) {
        $accessGranted = true;
        $timeRemaining = $expireTime - $now;
    }
}

// ==============================
// PAGE OUTPUT
// ==============================
?>

<div class="container">
  <div class="row">
    <div class="col-md-12">

      <div class="panel panel-primary">
        <div class="panel-heading">
          <h1 class="panel-title">
            <i class="mdi mdi-card-account-details"></i> SubLim3 JukeBox Card Registration
          </h1>
        </div>
        <div class="panel-body">

<?php if (!$accessGranted): ?>

          <div class="alert alert-danger">
            <strong>Card Registration Disabled</strong><br>
            This page is currently disabled or has expired.
          </div>

<?php else: ?>

          <!-- Countdown Banner -->
<?php
$minutes = floor($timeRemaining / 60);
$colorClass = "alert-success";
if ($minutes <= 30) $colorClass = "alert-warning";
if ($minutes <= 10) $colorClass = "alert-danger";
?>

          <div class="alert <?php echo $colorClass; ?>">
            <strong>Time Remaining:</strong> <?php echo $minutes; ?> minutes
          </div>

          <!-- Manage Files Button -->
          <div style="margin-bottom:15px;">
            <a href="manageFilesFolders.php" class="btn btn-primary btn-block">
              <i class="mdi mdi-folder-multiple"></i> Manage Audio Files (Upload / Organize)
            </a>
          </div>

          <!-- ORIGINAL CARD REGISTER CONTENT STARTS HERE -->
          <!-- KEEP EXISTING PHONIEBOX FUNCTIONALITY -->

<?php
// ==============================
// ORIGINAL PHONIEBOX CONTENT
// (UNCHANGED EXCEPT YOUTUBE REMOVED)
// ==============================

$filename = "settings/rfid_trigger_play.conf";
$file = fopen($filename, "r") or die("Unable to open file!");
$content = fread($file, filesize($filename));
fclose($file);

// Remove YouTube section (SubLim3 override)
$content = preg_replace('/## START DOWNLOAD YOUTUBE ##.*?## END DOWNLOAD YOUTUBE ##/s', '', $content);

// Output cleaned content
echo "<pre>$content</pre>";
?>

<?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</div>

<?php
include("inc.footer.php");
?>
