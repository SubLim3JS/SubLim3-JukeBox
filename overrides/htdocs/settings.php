<?php

include("inc.header.php");

/*******************************************
 * SUBLIM3 THEME CONFIG
 *******************************************/
$themeFile = "/home/pi/RPi-Jukebox-RFID/settings/theme.conf";
$themeBuilder = "/home/pi/RPi-Jukebox-RFID/scripts/build-theme-css.sh";

$availableThemes = array(
    "green"  => "Green",
    "blue"   => "Blue",
    "red"    => "Red",
    "purple" => "Purple",
    "orange" => "Orange",
    "cyan"   => "Cyan",
    "white"  => "White"
);

$currentTheme = "green";
$themeMessage = "";
$themeMessageType = "success";

if (file_exists($themeFile)) {
    $lines = @file($themeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, "theme=") === 0) {
                $savedTheme = trim(substr($line, 6));
                if (array_key_exists($savedTheme, $availableThemes)) {
                    $currentTheme = $savedTheme;
                }
                break;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["sublim3_theme_save"])) {
    $selectedTheme = isset($_POST["sublim3_theme"]) ? trim($_POST["sublim3_theme"]) : "";

    if (array_key_exists($selectedTheme, $availableThemes)) {
        $configData = "theme=" . $selectedTheme . PHP_EOL;

        if (@file_put_contents($themeFile, $configData) !== false) {
            @shell_exec("bash " . escapeshellarg($themeBuilder) . " 2>&1");
            $currentTheme = $selectedTheme;
            $themeMessage = "Theme updated to " . $availableThemes[$selectedTheme] . ".";
            $themeMessageType = "success";
        } else {
            $themeMessage = "Failed to save theme setting.";
            $themeMessageType = "danger";
        }
    } else {
        $themeMessage = "Invalid theme selection.";
        $themeMessageType = "danger";
    }
}

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en","Settings | SubLim3 JukeBox",$conf['base_url']);

?>
<body>
  <div class="container">

<?php
include("inc.navigation.php");

if($debug == "true") {
    print "<pre>";
    print "_POST:\n";
    print_r($_POST);
    print "</pre>";
}

?>

<div class="row">
  <div class="col-lg-12">
  <strong><?php print $lang['globalJumpTo']; ?>:</strong>
        <a href="#theme" class="xbtn xbtn-default ">
        <i class='mdi mdi-palette'></i> SubLim3 Theme
        </a> |
        <a href="#RFID" class="xbtn xbtn-default ">
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['globalRFIDCards']; ?>
        </a> |
        <a href="#language" class="xbtn xbtn-default ">
        <i class='mdi mdi-emoticon'></i> <?php print $lang['globalLanguageSettings']; ?>
        </a> |
        <a href="#volume" class="xbtn xbtn-default ">
        <i class='mdi mdi-volume-high'></i> <?php print $lang['globalVolumeSettings']; ?>
        </a> |
        <a href="#autoShutdown" class="xbtn xbtn-default ">
        <i class='mdi mdi-clock-end'></i> <?php print $lang['globalIdleShutdown']." / ".$lang['globalSleepTimer']; ?>
        </a> |
        <a href="#wifi" class="xbtn xbtn-default ">
        <i class='mdi mdi-wifi'></i> <?php print $lang['globalWifiSettings']; ?>
        </a> |
        <!--a href="#wlanIpEmail" class="xbtn xbtn-default ">
        <i class='mdi mdi-wifi'></i> <?php print $lang['settingsWlanSendNav']; ?>
        </a>  |-->
        <a href="#wlanIpRead" class="xbtn xbtn-default ">
        <i class='mdi mdi-wifi'></i> <?php print $lang['settingsWlanReadNav']; ?>
        </a>  |
        <a href="#webInterface" class="xbtn xbtn-default ">
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsWebInterface']; ?>
        </a>  |
        <a href="#externalInterfaces" class="xbtn xbtn-default ">
        <i class='mdi mdi-usb'></i> <?php print $lang['globalExternalInterfaces']; ?>
        </a>  |
        <a href="#secondSwipe" class="xbtn xbtn-default ">
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsSecondSwipe']; ?>
        </a> |
        <a href="#DebugLogSettings" class="xbtn xbtn-default ">
        <i class='mdi mdi-text'></i> <?php print $lang['infoDebugLogSettings']; ?>
        </a>
  </div>
</div>
<br/>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="theme"></a>
         <i class='mdi mdi-palette'></i> SubLim3 Theme
      </h4>
    </div>

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12">

          <?php if (!empty($themeMessage)) { ?>
            <div class="alert alert-<?php echo htmlspecialchars($themeMessageType); ?>">
              <?php echo htmlspecialchars($themeMessage); ?>
            </div>
          <?php } ?>

          <form method="post" class="form-inline">
            <div class="form-group" style="margin-right: 15px; min-width: 260px;">
              <label for="sublim3_theme" style="display:block; margin-bottom:6px;">Select Theme Color</label>
              <select name="sublim3_theme" id="sublim3_theme" class="form-control">
                <?php foreach ($availableThemes as $value => $label) { ?>
                  <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($currentTheme === $value ? 'selected="selected"' : ''); ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <button type="submit" name="sublim3_theme_save" value="1" class="btn btn-primary" style="margin-top: 24px;">
              <i class='mdi mdi-content-save'></i> Save Theme
            </button>
          </form>

        </div>
      </div>
    </div>

  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFID"></a>
         <i class='mdi mdi-cards-outline'></i> <?php print $lang['indexManageFilesChips']; ?>
      </h4>
    </div>

      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
                <a href="cardRegisterNew.php" class="btn btn-primary btn">
                <i class='mdi mdi-cards-outline'></i> <?php print $lang['globalRegisterCard']; ?>
                </a>
          </div>
        </div>
      </div>

    </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="language"></a>
         <i class='mdi mdi-emoticon'></i> <?php print $lang['globalLanguageSettings']; ?>
      </h4>
    </div>

    <div class="panel-body">
      <div class="row">
<?php
include("inc.setLanguage.php");
?>
      </div>
    </div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="language"></a>
         <i class='mdi mdi-emoticon'></i> <?php print $lang['settingsPlayoutBehaviourCard']; ?>
      </h4>
    </div>
    
    <div class="panel-body">
      <div class="row">
<?php
include("inc.setPlayerBehaviourRFID.php");
?>
      </div>
    </div>

  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="volume"></a>
         <i class='mdi mdi-volume-high'></i> <?php print $lang['globalVolumeSettings']; ?>
      </h4>
    </div>

    <div class="panel-body">
      <div class="row">
<?php
include("inc.setVolume.php");
include("inc.setMaxVolume.php");
include("inc.setVolumeStep.php");
include("inc.setStartupVolume.php");
include("inc.setBootVolume.php");
?>
      </div>
    </div>

  </div>
</div>

<?php
$filename = $conf['settings_abs'].'/bluetooth-sink-switch';
if (file_exists($filename)) {
   if (strcmp(strtolower(trim(file_get_contents($filename))), "enabled") === 0) {
      include('inc.bluetooth.php');
   }
}
?>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="autoShutdown"></a>
        <i class='mdi mdi-clock-end'></i> <?php print $lang['globalAutoShutdown']." ".$lang['globalSettings']; ?>
      </h4>
    </div>
    <div class="panel-body">

        <div class="row">

<?php
include("inc.setStoptimer.php");
include("inc.setSleeptimer.php");
include("inc.setShutdownVolumeReduction.php");
include("inc.setIdleShutdown.php");
?>
        </div>

    </div>

  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="wifi"></a>
        <i class='mdi mdi-wifi'></i> <?php print $lang['globalWifiSettings']; ?>
      </h4>
    </div>

      <div class="panel-body">
<?php
include("inc.setWifi.php");
?>
      </div>

  </div>
</div>

<?php
include("inc.setWlanIpRead.php");
?>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="webInterface"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsWebInterface']; ?>
      </h4>
    </div>

      <div class="panel-body">

<?php
include("inc.setWebUI.php");
?>

      </div>
  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="externalInterfaces"></a>
        <i class='mdi mdi-usb'></i> <?php print $lang['globalExternalInterfaces']; ?>
      </h4>
    </div>

      <div class="panel-body">
<?php
include("inc.setInputDevices.php");
?>
      </div>
  </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="secondSwipe"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsSecondSwipe']; ?>
      </h4>
    </div>

      <div class="panel-body">
<?php
include("inc.setSecondSwipe.php");
include("inc.setSecondSwipePause.php");
include("inc.setSecondSwipePauseControls.php");
?>
      </div>

  </div>
</div>

<?php include("inc.setDebugLogConf.php"); ?>

</div>

</body>
<script src="js/jukebox.js">
</script>
</html>
