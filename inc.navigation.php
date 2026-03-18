<?php
// ----------------------------------------------------
// Card Register Toggle (same logic as cardRegisterNew.php)
// ----------------------------------------------------
$toggleFile = '/home/pi/RPi-Jukebox-RFID/settings/reg-toggle';
$cardRegisterEnabled = false;

if (file_exists($toggleFile)) {
    $lines = file($toggleFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = array();

    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
    }

    $isEnabled = isset($config['enabled']) && $config['enabled'] === '1';
    $expires = isset($config['expires']) ? $config['expires'] : '';

    if ($isEnabled) {
        if ($expires === '') {
            $cardRegisterEnabled = true;
        } else {
            $expiresTs = strtotime($expires);
            if ($expiresTs !== false && time() <= $expiresTs) {
                $cardRegisterEnabled = true;
            }
        }
    }
}
?>

<style>
#phonieboxinfomessage {
    display: none;
    position: fixed;
    width: 50%;
    height: 100px;
    left: 25%;
    top: 40%;
    z-index: 7000;
}
</style>

<div id="phonieboxinfomessage" class="alert-messages"></div>

<nav class="navbar navbar-default" style="position: -webkit-sticky; position: sticky; top: 0; z-index: 1000;">
  <div class="container-fluid">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>

      <a class="navbar-brand" style="padding: 19.5px 15px 0px; height: 0px;" href="index.php">
        SubLim3 JukeBox
      </a><br>

      <div class="navbar-brand" style="padding: 0px 15px 0px; margin-top: 19.5px; height: 0px; font-size: 13px; color: white;">
        Classic
      </div>
    </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">

      <!-- Main Menu -->
<ul class="nav navbar-nav">
    <li><a href='index.php' class='mainMenu'><i class='mdi mdi-play-circle'></i> Player</a></li>

    <?php if ($cardRegisterEnabled): ?>
    <li style="background:#00bc8c;">
        <a href="cardRegisterNew.php" class="mainMenu" style="color:#fff !important; font-weight:bold;">
            <i class='mdi mdi-cards-outline'></i> Card ID
        </a>
    </li>
    <?php endif; ?>

    <li><a href='search.php' class='mainMenu'><i class='mdi mdi-magnify'></i> Search</a></li>
    <li><a href='settings.php' class='mainMenu'><i class='mdi mdi-settings'></i> Settings</a></li>
    <li><a href='systemInfo.php' class='mainMenu'><i class='mdi mdi-information-outline'></i> Info</a></li>
    <li><a href='manageFilesFolders.php' class='mainMenu'><i class='mdi mdi-folder-upload'></i> Folders &amp; Files</a></li>

    <li><a href="#" class="mainMenu">DEBUG=<?php echo $cardRegisterEnabled ? 'true' : 'false'; ?></a></li>
</ul>

      <!-- Right Menu -->
      <ul class="nav navbar-nav navbar-right">
        <li><a href='index.php?shutdown=true' class='mainMenu'><i class='mdi mdi-power'></i> Shutdown</a></li>
        <li><a href='index.php?reboot=true' class='mainMenu'><i class='mdi mdi-refresh'></i> Reboot</a></li>
      </ul>

    </div>
  </div>
</nav>
