<?php
include("inc.header.php");

/**************************************************
* VARIABLES
* No changes required if you stuck to the
* INSTALL.md instructions.
* If you want to change the paths, edit config.php
***************************************************/

/* NO CHANGES BENEATH THIS LINE ***********/

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
$conf['url_abs'] = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; // URL to PHP_SELF

/*******************************************
* SUBLIM3 CARD REGISTER ACCESS CONTROL
*******************************************/
function getCardRegisterAccessConfig($filePath) {
    $config = array(
        'enabled' => true,
        'expires' => null
    );

    // Default to enabled if the file is missing
    if (!file_exists($filePath)) {
        return $config;
    }

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $config;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
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
            // Backward compatibility for old single-value files like "enabled"
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
        } else {
            $cardRegisterCountdownText = formatTimeRemaining($cardRegisterSecondsRemaining);

            if ($cardRegisterSecondsRemaining < 600) {
                $cardRegisterBannerClass = 'alert-danger';
            } elseif ($cardRegisterSecondsRemaining <= 1800) {
                $cardRegisterBannerClass = 'alert-warning';
            } else {
                $cardRegisterBannerClass = 'alert-success';
            }
        }
    }
}

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en", "RFID Card | SubLim3 JukeBox", $conf['base_url']);

?>
<body>
  <div class="container">

<?php
include("inc.navigation.php");

// path to script folder from github repo on RPi
$conf['shared_abs'] = realpath(getcwd() . '/../shared/');

/*******************************************
* ACCESS DENIED VIEW
*******************************************/
if (!$cardRegisterAccessEnabled) {
    ?>
    <div class="row">
      <div class="col-lg-12">
        <div class="alert alert-warning">
          <strong>Card Registration Disabled</strong><br>
          <?php if ($cardRegisterExpired) { ?>
            This page is currently disabled because access has expired.
          <?php } else { ?>
            This page is currently disabled on SubLim3 JukeBox.
          <?php } ?>
        </div>
      </div>
    </div>
  </div><!-- /.container -->
</body>
</html>
<?php
    exit;
}

/*******************************************
* ACTIONS
*******************************************/

// check stuff to be done
include("inc.processCheckCardEditRegister.php");

/*
* FILE UPLOAD
*/
if (!empty($_FILES['importFileUpload'])) {
    $conf['upload_abs'] = realpath(getcwd() . '/../temp/');
    $uploadFileType = strtolower(pathinfo(basename($_FILES['importFileUpload']['name']), PATHINFO_EXTENSION));

    if ($debug == "true") {
        print "file upload";
        print "<pre>";
        print $conf['upload_abs'];
        print "\n";
        print $uploadFileType;
        print "\n";
        print "</pre>";
    }

    // check if csv
    if ($uploadFileType == "csv") {
        if (move_uploaded_file($_FILES['importFileUpload']['tmp_name'], $conf['upload_abs'] . "./rfidImport.csv")) {
            $messageSuccess .= "<p>" . $lang['cardImportFileSuccessUpload'] . basename($_FILES['importFileUpload']['name']) . "</p>";

            /*
            * read RFID into array
            */
            $rfidPostedAudio = array();
            $rfidPostedCommands = array();
            $fn = fopen($conf['upload_abs'] . "./rfidImport.csv", "r");
            while (!feof($fn)) {
                $pair = explode("\",\"", fgets($fn));

                // ignore header and empty lines
                if (
                    (count($pair) != 2)     // empty line
                    || ($pair[0] == "\"id") // CSV header
                ) {
                } else {
                    // system commands?
                    if (startsWith(trim($pair[1]), "%")) {
                        // yes, system command
                        $rfidPostedCommands[ltrim($pair[0], '"')] = rtrim(trim($pair[1]), '"');
                    } else {
                        // no, audio link
                        $rfidPostedAudio[ltrim($pair[0], '"')] = rtrim(trim($pair[1]), '"');
                    }
                }
            }
            fclose($fn);

            if ($debug == "true") {
                print "<pre>rfidPostedCommands: \n";
                print_r($rfidPostedCommands);
                print "\nrfidPostedAudio: \n";
                print_r($rfidPostedAudio);
                print "\nshortcuts: \n";
                print_r($shortcuts);
                print "</pre>";
            }

            /*
            * do we delete any files?
            */
            if ($_POST['importFileDelete'] == "all" || $_POST['importFileDelete'] == "commands") {
                // delete commands => create array of available commands with not array of used commands
                $fillRfidArrAvailWithUsed = fillRfidArrAvailWithUsed($rfidAvailArr);
                if ($debug == "true") {
                    print "<pre>delete commands - fillRfidArrAvailWithUsed:\n";
                    print_r($fillRfidArrAvailWithUsed);
                    print "</pre>";
                }
                $messageSuccess .= $lang['cardImportFileDeleteMessageCommands'];
            } else {
                // keep commands
                if ($debug == "true") {
                    print "<pre>keep commands - fillRfidArrAvailWithUsed:\n";
                    print_r($fillRfidArrAvailWithUsed);
                    print "</pre>";
                }
            }

            if ($_POST['importFileDelete'] == "all" || $_POST['importFileDelete'] == "audio") {
                // delete audio
                if ($debug == "true") {
                    print "<pre>delete audio - shortcuts:\n";
                    print_r($shortcuts);
                    print "</pre>";
                }
                foreach ($shortcuts as $shortcut => $value) {
                    if ($shortcut != "placeholder") {
                        $exec = "rm ../shared/shortcuts/" . $shortcut;
                        $result = exec($exec);
                    }
                }
                $messageSuccess .= $lang['cardImportFileDeleteMessageAudio'];
            }

            // which files to replace?
            if ($_POST['importFileOverwrite'] != "audio") {
                // create commands
                // fill available commands with posted commands
                foreach ($rfidPostedCommands as $key => $value) {
                    if ($debug == "true") {
                        print "<p>fillRfidArrAvailWithUsed[" . trim($value, '%') . "] = " . $key . "</p>";
                    }
                    $fillRfidArrAvailWithUsed[trim($value, '%')] = $key;
                }

                /******************************************
                * Create new conf file based on posted values
                ******************************************/
                // copy sample file to conf file
                exec("cp ../settings/rfid_trigger_play.conf.sample ../settings/rfid_trigger_play.conf; chmod 777 ../settings/rfid_trigger_play.conf");

                // replace posted values in new conf file
                foreach ($fillRfidArrAvailWithUsed as $key => $val) {
                    // only change those with values in the form (not empty)
                    if ($val != "") {
                        exec("sed -i 's/%" . $key . "%/" . $val . "/' '../settings/rfid_trigger_play.conf'");
                    }
                }
                $messageSuccess .= $lang['cardImportFileOverwriteMessageCommands'];
            }

            if ($_POST['importFileOverwrite'] != "commands") {
                // create audio files
                foreach ($rfidPostedAudio as $shortcutId => $shortcutFolder) {
                    $exec = "echo \"" . $shortcutFolder . "\" > ../shared/shortcuts/" . $shortcutId;
                    exec($exec);
                }
                exec("chmod 777 ../shared/shortcuts/*");
                $messageSuccess .= $lang['cardImportFileOverwriteMessageAudio'];
            }
        } else {
            $messageError .= $lang['cardImportFileErrorUpload'];
        }
    } else {
        $messageError .= $lang['cardImportFileErrorFiletype'];
    }
} else {
    if ($debug == "true") {
        print "no file upload";
    }
}
?>

<?php if ($cardRegisterExpiresTs !== null && !$cardRegisterExpired) { ?>
<div class="row">
  <div class="col-lg-12">
    <div id="cardRegisterCountdownBanner" class="alert <?php print $cardRegisterBannerClass; ?>">
      <strong>Temporary Access Active</strong><br>
      Card registration access expires in
      <span id="cardRegisterCountdown"
            data-expire-ts="<?php print $cardRegisterExpiresTs; ?>">
        <?php print htmlspecialchars($cardRegisterCountdownText); ?>
      </span>
      <br>
      <small>Expires at: <?php print htmlspecialchars($cardRegisterAccessConfig['expires']); ?></small>
    </div>
  </div>
</div>
<?php } ?>

<div class="row">
  <div class="col-lg-12">
    <strong><?php print $lang['globalJumpTo']; ?>:</strong>
    <a href="#RFIDinteractive" class="btn xbtn-info ">
      <i class='mdi mdi-cards-outline'></i> <?php print $lang['cardRegisterTitle']; ?>
    </a> |
    <a href="#RFIDexport" class="btn xbtn-info ">
      <i class='mdi mdi-download'></i> <?php print $lang['cardExportAnchorLink']; ?>
    </a> |
    <a href="#RFIDimport" class="btn xbtn-info ">
      <i class='mdi mdi-plus-circle'></i> <?php print $lang['cardImportAnchorLink']; ?>
    </a>
  </div>
</div>
<br/>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDinteractive"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['cardRegisterTitle']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row ">
        <div class="col-lg-12">
<?php
/*
* Do we need to voice a warning here?
*/
if ($messageAction == "" && $messageError == "") {
    $messageAction = $lang['cardRegisterMessageDefault'] . $lang['cardRegisterManualLinks'];
}
if (isset($messageSuccess) && $messageSuccess != "") {
    print '<div class="alert alert-success">' . $messageSuccess . '<p>' . $lang['cardRegisterMessageSwipeNew'] . '</p></div>';
    unset($post);
} else {
    if (isset($warning)) {
        print '<div class="alert alert-warning">WARNING: ' . $warning . '</div>';
    }
    if (isset($messageError) && $messageError != "") {
        print '<div class="alert alert-danger">ERROR: ' . $messageError . '</div>';
    }
    if (isset($messageAction) && $messageAction != "") {
        print '<div class="alert alert-info">' . $messageAction . '</div>';
    }
}
?>

<?php
if ($debug == "true") {
    print "<pre>";
    print_r($_POST);
    print_r($post);
    print_r($conf);
    print "</pre>";
}
?>

        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
<?php
/*
* pass on some variables to the form.
* Doing this so I can reuse the form in other places to edit or register cards.
*/
$fdata = array(
    "streamURL_ajax" => "true",
    "streamURL_label" => $lang['globalLastUsedCard'],
    "streamURL_help" => $lang['cardRegisterSwipeUpdates'],
);
$fpost = $post;
include("inc.formCardEdit.php");
?>
        </div><!-- / .col-lg-12 -->
      </div><!-- /.row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDexport"></a>
        <i class='mdi mdi-download'></i> <?php print $lang['cardExportAnchorLink']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12">
          <a href="rfidExportCsv.php" class="btn btn-primary btn">
            <i class='mdi mdi-download'></i> <?php print $lang['cardExportButtonLink']; ?>
          </a>
        </div><!-- / .col-lg-12 -->
      </div><!-- /.row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDimport"></a>
        <i class='mdi mdi-plus-circle'></i> <?php print $lang['cardImportAnchorLink']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12">
          <form name='upload' enctype='multipart/form-data' method='post' action='<?php print $_SERVER['PHP_SELF']; ?>'>
            <fieldset>
              <div class="form-group">
                <label class="col-md-4 control-label" for="importFileUpload"><?php print $lang['cardImportFileLabel']; ?></label>
                <div class="col-md-6">
                  <input type="file" name="importFileUpload" id="importFileUpload" class="form-control input-md">
                  <span class="help-block"> </span>
                </div>
              </div>
            </fieldset>

            <div class="form-group">
              <label class="col-md-4 control-label" for="importFileOverwrite"><?php print $lang['cardImportFormOverwriteLabel']; ?></label>
              <div class="col-md-6">
                <select id="audiofolder" name="importFileOverwrite" class="form-control">
                  <option value="all"><?php print $lang['cardImportFormOverwriteAll']; ?></option>
                  <option value="commands"><?php print $lang['cardImportFormOverwriteCommands']; ?></option>
                  <option value="audio"><?php print $lang['cardImportFormOverwriteAudio']; ?></option>
                </select>
                <span class="help-block"><?php print $lang['cardImportFormOverwriteHelp']; ?></span>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-4 control-label" for="importFileDelete"><?php print $lang['cardImportFormDeleteLabel']; ?></label>
              <div class="col-md-6">
                <select id="audiofolder" name="importFileDelete" class="form-control">
                  <option value="none"><?php print $lang['cardImportFormDeleteNone']; ?></option>
                  <option value="all"><?php print $lang['cardImportFormDeleteAll']; ?></option>
                  <option value="commands"><?php print $lang['cardImportFormDeleteCommands']; ?></option>
                  <option value="audio"><?php print $lang['cardImportFormDeleteAudio']; ?></option>
                </select>
                <span class="help-block"><?php print $lang['cardImportFormDeleteHelp']; ?></span>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-4 control-label" for="submit"></label>
              <div class="col-md-8">
                <button id="submit" name="upload" class="btn btn-success" value="submit"><?php print $lang['globalUpload']; ?></button>
                <br clear='all'><br>
              </div>
            </div>
          </form>
        </div><!-- / .col-lg-12 -->
      </div><!-- /.row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

  </div><!-- /.container -->

<script>
$(document).ready(function() {
    $('#refresh_id').load('ajax.refresh_id.php');
    var refreshId = setInterval(function() {
        $('#refresh_id').load('ajax.refresh_id.php?' + 1 * new Date());
    }, 1000);
});
</script>

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

        if (days > 0) {
            parts.push(days + 'd');
        }
        if (hours > 0 || days > 0) {
            parts.push(hours + 'h');
        }
        if (minutes > 0 || hours > 0 || days > 0) {
            parts.push(minutes + 'm');
        }
        parts.push(secs + 's');

        return parts.join(' ');
    }

    function updateCountdown() {
        var now = Math.floor(Date.now() / 1000);
        var remaining = expireTs - now;

        if (remaining <= 0) {
            countdownEl.textContent = '0s';
            bannerEl.className = 'alert alert-danger';
            bannerEl.innerHTML = '<strong>Card Registration Expired</strong><br>This page has expired and should be refreshed.';
            return;
        }

        countdownEl.textContent = formatRemaining(remaining);

        bannerEl.className = 'alert';
        if (remaining < 600) {
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

</body>
</html>
