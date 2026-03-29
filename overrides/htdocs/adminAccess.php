<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("inc.header.php");

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
$conf['url_abs'] = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

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

function isCardRegisterAccessEnabled($filePath) {
    $config = getCardRegisterAccessConfig($filePath);

    if (!$config['enabled']) {
        return false;
    }

    if (!empty($config['expires'])) {
        $expiresTs = strtotime($config['expires']);
        if ($expiresTs !== false && time() > $expiresTs) {
            return false;
        }
    }

    return true;
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
$cardRegisterAccessEnabled = isCardRegisterAccessEnabled($cardRegisterAccessFile);
$cardRegisterExpired = false;
$cardRegisterExpiresTs = null;
$cardRegisterSecondsRemaining = null;
$cardRegisterCountdownText = '';
$cardRegisterBannerClass = 'alert-success';

if (!empty($cardRegisterAccessConfig['expires'])) {
    $expiresTs = strtotime($cardRegisterAccessConfig['expires']);
    if ($expiresTs !== false) {
        $cardRegisterExpiresTs = $expiresTs;
        $cardRegisterSecondsRemaining = $expiresTs - time();

        if ($cardRegisterSecondsRemaining <= 0) {
            $cardRegisterExpired = true;
            $cardRegisterBannerClass = 'alert-danger';
        } else {
            $cardRegisterCountdownText = formatTimeRemaining($cardRegisterSecondsRemaining);

            if ($cardRegisterSecondsRemaining <= 300) {
                $cardRegisterBannerClass = 'alert-danger';
            } elseif ($cardRegisterSecondsRemaining <= 1800) {
                $cardRegisterBannerClass = 'alert-warning';
            } else {
                $cardRegisterBannerClass = 'alert-success';
            }
        }
    }
}

$overrideDuration = 1800; // 30 minutes

if (isset($_GET['enableAdminOverride']) && $_GET['enableAdminOverride'] == '1') {
    $_SESSION['sublim3_admin_override_until'] = time() + $overrideDuration;
    header("Location: adminAccess.php");
    exit;
}

if (isset($_GET['disableAdminOverride']) && $_GET['disableAdminOverride'] == '1') {
    unset($_SESSION['sublim3_admin_override_until']);
    header("Location: adminAccess.php");
    exit;
}

$adminOverrideEnabled = false;
$adminOverrideUntil = null;
$adminOverrideRemaining = 0;
$adminOverrideBannerClass = 'alert-warning';
$adminOverrideCountdownText = '';

if (
    isset($_SESSION['sublim3_admin_override_until']) &&
    is_numeric($_SESSION['sublim3_admin_override_until']) &&
    $_SESSION['sublim3_admin_override_until'] > time()
) {
    $adminOverrideEnabled = true;
    $adminOverrideUntil = (int) $_SESSION['sublim3_admin_override_until'];
    $adminOverrideRemaining = $adminOverrideUntil - time();
    $adminOverrideCountdownText = formatTimeRemaining($adminOverrideRemaining);

    if ($adminOverrideRemaining <= 300) {
        $adminOverrideBannerClass = 'alert-danger';
    } elseif ($adminOverrideRemaining <= 900) {
        $adminOverrideBannerClass = 'alert-warning';
    } else {
        $adminOverrideBannerClass = 'alert-success';
    }
} else {
    unset($_SESSION['sublim3_admin_override_until']);
}

html_bootstrap3_createHeader("en", "Admin Access | SubLim3 JukeBox", $conf['base_url']);
?>
<body>
  <div class="container">

<?php include("inc.navigation.php"); ?>

<?php if ($cardRegisterExpiresTs !== null && !$cardRegisterExpired) { ?>
<div class="row">
  <div class="col-lg-12">
    <div id="cardRegisterCountdownBanner" class="alert <?php print $cardRegisterBannerClass; ?>">
      <strong>Card Register Access File Status</strong><br>
      Access file expires in
      <span id="cardRegisterCountdown" data-expire-ts="<?php print $cardRegisterExpiresTs; ?>">
        <?php print htmlspecialchars($cardRegisterCountdownText); ?>
      </span>
      <br>
      <small>Expires at: <?php print htmlspecialchars($cardRegisterAccessConfig['expires']); ?></small>
    </div>
  </div>
</div>
<?php } elseif (!$cardRegisterAccessEnabled) { ?>
<div class="row">
  <div class="col-lg-12">
    <div class="alert alert-danger">
      <strong>Card Register Access File Status</strong><br>
      <?php if ($cardRegisterExpired) { ?>
        The normal card register page is currently expired by the access file.
      <?php } else { ?>
        The normal card register page is currently disabled by the access file.
      <?php } ?>
    </div>
  </div>
</div>
<?php } ?>

<div class="row">
  <div class="col-lg-12">
    <div id="adminOverrideBanner" class="alert <?php print $adminOverrideEnabled ? $adminOverrideBannerClass : 'alert-warning'; ?>">
      <?php if ($adminOverrideEnabled) { ?>
        <strong>Admin Override Active</strong><br>
        cardRegisterNew.php override access remains enabled for
        <span id="adminOverrideCountdown" data-expire-ts="<?php print $adminOverrideUntil; ?>">
          <?php print htmlspecialchars($adminOverrideCountdownText); ?>
        </span>
        <br>
        <small>Override expires at: <?php print date("Y-m-d H:i:s", $adminOverrideUntil); ?></small>
      <?php } else { ?>
        <strong>Admin Override Inactive</strong><br>
        Enable admin override to bypass the normal card register expiration/disable logic.
      <?php } ?>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-12">
    <strong>Jump To:</strong>
    <a href="#AdminTools" class="btn xbtn-info ">
      <i class='mdi mdi-shield-account'></i> Admin Tools
    </a> |
    <a href="#AdminQRCode" class="btn xbtn-info ">
      <i class='mdi mdi-qrcode'></i> App QR Code
    </a>
  </div>
</div>
<br/>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="AdminTools"></a>
        <i class='mdi mdi-shield-account'></i> SubLim3 JukeBox Admin Tools
      </h4>
    </div>

<div style="
  background:#007bff !important;
  border:1px solid #007bff !important;
  color:#ffffff !important;
  padding:15px;
  border-radius:4px;
  margin-bottom:15px;
">
  <strong>Admin Tools Overview</strong><br>
  Use this page to:
  <ul style="margin-bottom:0; padding-left:20px;">
    <li>Bypass RFID card registration restrictions</li>
    <li>Access the Folder Management page</li>
    <li>Download the SubLim3 JukeBox app via QR code</li>
  </ul>
</div>

          <div class="row">
            <div class="col-sm-4" style="margin-bottom:15px;">
              <a href="adminAccess.php?enableAdminOverride=1"
                 class="btn btn-success btn-lg btn-block"
                 style="display:block !important; visibility:visible !important;">
                <i class='mdi mdi-lock-open-variant'></i> Enable Admin Override
              </a>
            </div>

            <div class="col-sm-4" style="margin-bottom:15px;">
              <button type="button"
                      class="btn btn-warning btn-lg btn-block"
                      style="display:block !important; visibility:visible !important; width:100% !important;"
                      onclick="window.location.href='manageFilesFolders.php';">
                <i class='mdi mdi-folder-multiple'></i> Open Folder Manager
              </button>
            </div>

            <div class="col-sm-4" style="margin-bottom:15px;">
              <a href="adminAccess.php?disableAdminOverride=1"
                 class="btn btn-danger btn-lg btn-block"
                 style="display:block !important; visibility:visible !important;">
                <i class='mdi mdi-lock'></i> Disable Admin Override
              </a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="AdminQRCode"></a>
        <i class='mdi mdi-qrcode'></i> SubLim3 JukeBox App QR Code
      </h4>
    </div>

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12 text-center">
          <img
            src="_assets/icons/SubLim3-JukeBox-App-QR.png"
            alt="SubLim3 JukeBox App QR Code"
            style="max-width:100%; width:320px; height:auto; border:1px solid #ddd; padding:10px; background:#fff;"
          >
          <br><br>
          <p>Scan this QR code to access the SubLim3 JukeBox app.</p>
        </div>
      </div>
    </div>
  </div>
</div>

  </div><!-- /.container -->

<script>
(function() {
    var countdownEl = document.getElementById('cardRegisterCountdown');
    var bannerEl = document.getElementById('cardRegisterCountdownBanner');

    if (!countdownEl || !bannerEl) {
        return;
    }

    var expireTs = parseInt(countdownEl.getAttribute('data-expire-ts'), 10);

    function formatRemaining(seconds) {
        seconds = Math.max(0, seconds);

        var days = Math.floor(seconds / 86400);
        var hours = Math.floor((seconds % 86400) / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;

        var parts = [];
        if (days > 0) parts.push(days + 'd');
        if (hours > 0 || days > 0) parts.push(hours + 'h');
        if (minutes > 0 || hours > 0 || days > 0) parts.push(minutes + 'm');
        parts.push(secs + 's');

        return parts.join(' ');
    }

    function updateCountdown() {
        var now = Math.floor(Date.now() / 1000);
        var remaining = expireTs - now;

        if (remaining <= 0) {
            countdownEl.textContent = '0s';
            bannerEl.className = 'alert alert-danger';
            bannerEl.innerHTML = '<strong>Card Register Access Expired</strong><br>The normal access window has expired.';
            return;
        }

        countdownEl.textContent = formatRemaining(remaining);

        bannerEl.className = 'alert';
        if (remaining <= 300) {
            bannerEl.className += ' alert-danger';
        } else if (remaining <= 1800) {
            bannerEl.className += ' alert-warning';
        } else {
            bannerEl.className += ' alert-success';
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>

<script>
(function() {
    var countdownEl = document.getElementById('adminOverrideCountdown');
    var bannerEl = document.getElementById('adminOverrideBanner');

    if (!countdownEl || !bannerEl) {
        return;
    }

    var expireTs = parseInt(countdownEl.getAttribute('data-expire-ts'), 10);

    function formatRemaining(seconds) {
        seconds = Math.max(0, seconds);

        var days = Math.floor(seconds / 86400);
        var hours = Math.floor((seconds % 86400) / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;

        var parts = [];
        if (days > 0) parts.push(days + 'd');
        if (hours > 0 || days > 0) parts.push(hours + 'h');
        if (minutes > 0 || hours > 0 || days > 0) parts.push(minutes + 'm');
        parts.push(secs + 's');

        return parts.join(' ');
    }

    function updateCountdown() {
        var now = Math.floor(Date.now() / 1000);
        var remaining = expireTs - now;

        if (remaining <= 0) {
            countdownEl.textContent = '0s';
            bannerEl.className = 'alert alert-danger';
            bannerEl.innerHTML = '<strong>Admin Override Expired</strong><br>Refresh the page to re-enable override.';
            return;
        }

        countdownEl.textContent = formatRemaining(remaining);

        bannerEl.className = 'alert';
        if (remaining <= 300) {
            bannerEl.className += ' alert-danger';
        } else if (remaining <= 900) {
            bannerEl.className += ' alert-warning';
        } else {
            bannerEl.className += ' alert-success';
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>

</body>
</html>
