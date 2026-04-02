<?php

include("inc.header.php");

/*******************************************
* SubLim3 Theme Settings
*******************************************/
$sublim3AllowedThemes = array(
    'green'  => 'Green',
    'blue'   => 'Blue',
    'red'    => 'Red',
    'purple' => 'Purple',
    'orange' => 'Orange',
    'cyan'   => 'Cyan',
    'white'  => 'White'
);

$sublim3ThemeFile = $conf['settings_abs'].'/theme-color';
$sublim3ThemeMessage = '';

if (isset($_POST['theme_color'])) {
    $newTheme = trim(strtolower($_POST['theme_color']));

    if (array_key_exists($newTheme, $sublim3AllowedThemes)) {
        $writeOk = @file_put_contents($sublim3ThemeFile, $newTheme . PHP_EOL);

        if ($writeOk !== false) {
            $sublim3Theme = $newTheme;
            $sublim3ThemeClass = 'sublim3-theme-' . $sublim3Theme;
            $currentTheme = $newTheme;
            $sublim3ThemeMessage = '<div class="alert alert-success"><i class="mdi mdi-check-circle"></i> Web UI color scheme saved.</div>';
        } else {
            $sublim3ThemeMessage = '<div class="alert alert-danger"><i class="mdi mdi-alert-circle"></i> Failed to save Web UI color scheme. Check write permissions on '.$sublim3ThemeFile.'.</div>';
        }
    } else {
        $sublim3ThemeMessage = '<div class="alert alert-danger"><i class="mdi mdi-alert-circle"></i> Invalid color scheme selected.</div>';
    }
}

$currentTheme = 'green';
if (isset($sublim3Theme) && array_key_exists($sublim3Theme, $sublim3AllowedThemes)) {
    $currentTheme = $sublim3Theme;
} elseif (file_exists($sublim3ThemeFile)) {
    $savedTheme = trim(strtolower(file_get_contents($sublim3ThemeFile)));
    if (array_key_exists($savedTheme, $sublim3AllowedThemes)) {
        $currentTheme = $savedTheme;
    }
}

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en","Settings | SubLim3 JukeBox",$conf['base_url']);

?>
<body class="<?php print htmlspecialchars(isset($sublim3ThemeClass) ? $sublim3ThemeClass : 'sublim3-theme-green'); ?>">
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
      <i class='mdi mdi-palette'></i> Web UI Theme
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
    <a href="#wlanIpRead" class="xbtn xbtn-default ">
      <i class='mdi mdi-wifi'></i> <?php print $lang['settingsWlanReadNav']; ?>
    </a> |
    <a href="#webInterface" class="xbtn xbtn-default ">
      <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsWebInterface']; ?>
    </a> |
    <a href="#externalInterfaces" class="xbtn xbtn-default ">
      <i class='mdi mdi-usb'></i> <?php print $lang['globalExternalInterfaces']; ?>
    </a> |
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
        <i class='mdi mdi-palette'></i> SubLim3 Web UI Theme
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12">
          <?php print $sublim3ThemeMessage; ?>
          <form method="post" action="">
            <div class="form-group">
              <label for="theme_color">Color Scheme</label>
              <select name="theme_color" id="theme_color" class="form-control">
<?php foreach ($sublim3AllowedThemes as $themeValue => $themeLabel) { ?>
                <option value="<?php print htmlspecialchars($themeValue); ?>" <?php if ($currentTheme === $themeValue) { print 'selected="selected"'; } ?>>
                  <?php print htmlspecialchars($themeLabel); ?>
                </option>
<?php } ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class='mdi mdi-content-save'></i> Save Theme
            </button>
          </form>
        </div>
      </div><!-- /.row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFID"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['indexManageFilesChips']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
        <div class="col-lg-12">
          <a href="cardRegisterNew.php" class="btn btn-primary btn">
            <i class='mdi mdi-cards-outline'></i> <?php print $lang['globalRegisterCard']; ?>
          </a>
        </div><!-- / .col-lg-12 -->
      </div><!-- /.row -->
    </div><!-- /.panel-body -->

  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="language"></a>
        <i class='mdi mdi-emoticon'></i> <?php print $lang['globalLanguageSettings']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
<?php
include("inc.setLanguage.php");
?>
      </div><!-- / .row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <i class='mdi mdi-emoticon'></i> <?php print $lang['settingsPlayoutBehaviourCard']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
<?php
include("inc.setPlayerBehaviourRFID.php");
?>
      </div><!-- / .row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="volume"></a>
        <i class='mdi mdi-volume-high'></i> <?php print $lang['globalVolumeSettings']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
      <div class="row">
<?php
include("inc.setVolume.php");
include("inc.setMaxVolume.php");
include("inc.setVolumeStep.php");
include("inc.setStartupVolume.php");
include("inc.setBootVolume.php");
?>
      </div><!-- / .row -->
    </div><!-- /.panel-body -->

  </div><!-- /.panel -->
</div><!-- /.panel-group -->

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
    </div><!-- /.panel-heading -->
    <div class="panel-body">
      <div class="row">
<?php
include("inc.setStoptimer.php");
include("inc.setSleeptimer.php");
include("inc.setShutdownVolumeReduction.php");
include("inc.setIdleShutdown.php");
?>
      </div><!-- / .row -->
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="wifi"></a>
        <i class='mdi mdi-wifi'></i> <?php print $lang['globalWifiSettings']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
<?php
include("inc.setWifi.php");
?>
    </div><!-- /.panel-body -->

  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<?php
include("inc.setWlanIpRead.php");
?>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="webInterface"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsWebInterface']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
<?php
include("inc.setWebUI.php");
?>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="externalInterfaces"></a>
        <i class='mdi mdi-usb'></i> <?php print $lang['globalExternalInterfaces']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
<?php
include("inc.setInputDevices.php");
?>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="secondSwipe"></a>
        <i class='mdi mdi-cards-outline'></i> <?php print $lang['settingsSecondSwipe']; ?>
      </h4>
    </div><!-- /.panel-heading -->

    <div class="panel-body">
<?php
include("inc.setSecondSwipe.php");
include("inc.setSecondSwipePause.php");
include("inc.setSecondSwipePauseControls.php");
?>
    </div><!-- /.panel-body -->

  </div><!-- /.panel -->
</div><!-- /.panel-group -->

<?php include("inc.setDebugLogConf.php"); ?>

</div><!-- /.container -->

</body>
<script src="js/jukebox.js"></script>
</html>
