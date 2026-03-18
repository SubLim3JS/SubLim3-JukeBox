<?php
$toggleFile = '/home/pi/RPi-Jukebox-RFID/settings/reg-toggle';
$enabled = false;

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
            $enabled = true;
        } else {
            $expiresTs = strtotime($expires);
            if ($expiresTs !== false && time() <= $expiresTs) {
                $enabled = true;
            }
        }
    }
}

if (!$enabled) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>RFID Card | SubLim3 JukeBox</title>
        <link rel="stylesheet" href="_assets/bootstrap-3/css/bootstrap.darkly.css">
        <link rel="stylesheet" href="_assets/css/custom-green.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                color: #222;
                text-align: center;
                padding: 40px 20px;
            }
            .card {
                max-width: 500px;
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
            .note {
                font-size: 16px;
                color: #555;
                margin-top: 20px;
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
                color: white;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Card Registration Disabled</h1>
            <div class="note">This page is currently disabled.</div>
            <a class="btn" href="index.php">Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>RFID Card | SubLim3 JukeBox</title>

        <link rel="stylesheet" href="_assets/bootstrap-3/css/bootstrap.darkly.css">
        <link rel="stylesheet" href="_assets/css/circle.css">
        <link rel="stylesheet" href="_assets/css/collapsible.css">
        <link rel="stylesheet" href="_assets/css/custom-green.css">

        <script src="_assets/js/jquery.1.12.4.min.js"></script>
        <script src="_assets/bootstrap-3/js/bootstrap.min.js"></script>
        <script src="_assets/bootstrap-3/js/collapse.js"></script>
        <script src="_assets/bootstrap-3/js/transition.js"></script>

        <link href='_assets/MaterialDesign-Webfont-master/css/materialdesignicons.min.css' media='all' rel='stylesheet' type='text/css' />

        <!--[if lt IE 9]>
            <script src="_assets/bootstrap-3/js/html5shiv3.7.2.min.js"></script>
            <script src="_assets/bootstrap-3/js/respond1.4.2.min.js"></script>
        <![endif]-->

        <link rel='stylesheet' href='_assets/jQuery-File-Upload-9.22.0/css/jquery.fileupload.css'>
        <link rel='stylesheet' href='_assets/jQuery-File-Upload-9.22.0/css/jquery.fileupload-ui.css'>
        <noscript><link rel='stylesheet' href='_assets/jQuery-File-Upload-9.22.0/css/jquery.fileupload-noscript.css'></noscript>
        <noscript><link rel='stylesheet' href='_assets/jQuery-File-Upload-9.22.0/css/jquery.fileupload-ui-noscript.css'></noscript>

        <link rel="apple-touch-icon" sizes="57x57" href="_assets/icons/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="_assets/icons/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="_assets/icons/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="_assets/icons/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="_assets/icons/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="_assets/icons/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="_assets/icons/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="_assets/icons/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="_assets/icons/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="_assets/icons/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="_assets/icons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="_assets/icons/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="_assets/icons/favicon-16x16.png">
        <link rel="manifest" href="_assets/icons/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="_assets/icons/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">

        <style type='text/css'>
        .playerControls {
            margin-bottom: 1em;
        }
        .controlPlayer {
            margin-right: 1em;
        }
        .btnFolder, .folderContent {
            max-width: 100%;
            overflow: hidden;
        }
        .btn-player-xl {
            padding:4px 0px;
            font-size:38px;
            line-height:1;
            border-radius:6px;
        }
        .btn-player-l {
            padding:0px 0px;
            font-size:30px;
            line-height:1;
            border-radius:6px;
        }
        .btn-player-m {
            padding:15px 16px;
            font-size:18px;
            line-height:1;
            border-radius:6px;
        }
        .btn-player-s {
            padding:15px 5px;
            font-size:11px;
            line-height:1;
            border-radius:6px;
        }
        .playerWrapper,
        .playerWrapperSub {
            display: block!important;
            clear: both;
            height: auto;
            margin: 0 auto;
            text-align: center;
            margin-top: 1em;
        }
        .playerWrapper a {
            color: #00bc8c!important;
        }
        .playerWrapper a:hover {
            color: #008966!important;
        }
        .playerWrapperCover img {
            max-height: 200px;
        }
        .playerWrapperSub a {
            color: #aaa!important;
        }
        .playerWrapperSub a:hover {
            color: #eee!important;
        }
        .table td.text {
            max-width: 100px;
        }
        .table td.text span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 100%;
        }
        .mdi-72px.mdi-set, .mdi-72px.mdi:before {
          font-size: 72px;
        }
        .mdi-60px.mdi-set, .mdi-60px.mdi:before {
          font-size: 60px;
        }
        .hoverGrey:hover {
            color: #999!important;
        }
        .btn-panel-big {
            font-size: 3em!important;
            margin-right: 0.1em;
        }
        .btn-panel-col:hover {
        }
        .img-playlist-item {
            max-width: 100px;
            float: left;
            margin-right: 1em;
            border:1px solid white;
        }
        .img-playlist-item-placeholder {
            display: block;
            background-color: transparent;
            width: 100px;
            height: 50px;
            float: left;
            margin-right: 1em
        }
        .panel-heading a.btn-panel-big:active {
            color: #fff!important;
        }
        .panel-heading a.btn-panel-big {
            cursor: pointer;
        }
        </style>

    </head>
<body>
  <div class="container">

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
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" style="padding: 19.5px 15px 0px; height: 0px;" href="index.php">SubLim3 JukeBox</a><br>
      <div class="navbar-brand" style="padding: 0px 15px 0px; margin-top: 19.5px; height: 0px; font-size: 13px; color: white;">Classic</div>
    </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href='index.php' class='mainMenu'><i class='mdi mdi-play-circle'></i> Player</a></li>
        <li><a href='search.php' class='mainMenu'><i class='mdi mdi-magnify'></i> Search</a></li>
        <li><a href='settings.php' class='mainMenu'><i class='mdi mdi-settings'></i> Settings</a></li>
        <li><a href='systemInfo.php' class='mainMenu'><i class='mdi mdi-information-outline'></i> Info</a></li>
        <li><a href='manageFilesFolders.php' class='mainMenu'><i class='mdi mdi-folder-upload'></i> Folders &amp; Files</a></li>
        <li><a href="cardRegisterNew.php" class="mainMenu"><i class='mdi mdi-cards-outline'></i> Card ID</a></li>
      </ul>

      <ul class="nav navbar-nav navbar-right">
        <li><a href='index.php?shutdown=true' class='mainMenu'><i class='mdi mdi-power'></i> Shutdown</a></li>
        <li><a href='index.php?reboot=true' class='mainMenu'><i class='mdi mdi-refresh'></i> Reboot</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="row">
  <div class="col-lg-12">
  <strong>Jump to:</strong>
        <a href="#RFIDinteractive" class="btn xbtn-info ">
        <i class='mdi mdi-cards-outline'></i> Add new card        </a> |
        <a href="#RFIDexport" class="btn xbtn-info ">
        <i class='mdi mdi-download'></i> Export all RFID links (audio playout and commands)        </a> |
        <a href="#RFIDimport" class="btn xbtn-info ">
        <i class='mdi mdi-plus-circle'></i> Import RFID links from .csv file        </a>
  </div>
</div>
        <br/>
<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDinteractive"></a>
         <i class='mdi mdi-cards-outline'></i> Add new card      </h4>
    </div>

      <div class="panel-body">
    <div class="row ">
      <div class="col-lg-12">
<div class="alert alert-info">The 'Latest Card ID' value in the form is updated on the fly as you swipe a RFID card.<br/>(Requires Javascript in the browser to be enabled.)<p>You can also connect cards to folders manually. The manual explains how to <a href='https://github.com/MiczFlor/RPi-Jukebox-RFID/wiki/MANUAL#connect' target='–blank'>connect to the JukeBox</a> and <a href='https://github.com/MiczFlor/RPi-Jukebox-RFID/wiki/MANUAL#registering-cards-manually-through-samba-without-the-web-app' target='_blank'>register cards</a>.</p></div>

       </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <form name='volume' method='post' action='/cardRegisterNew.php'>

        <fieldset>
        <legend>Card RFID ID</legend>
        <div class="form-group">
          <label class="col-md-4 control-label" for="streamURL">Last used Chip ID</label>
          <div class="col-md-6">

<span id="refresh_id"></span>
          <span class="help-block">This will automatically update as you swipe a RFID card.</span>
          </div>
        </div>
        </fieldset>

        <fieldset>
        <legend>Link RFID to:</legend>

        <div class="form-group">
          <label class="col-md-4 control-label" for="audiofolder">Link card to existing audio folder</label>
          <div class="col-md-6">
            <select id="audiofolder" name="audiofolder" class="form-control">
              <option value="false" selected=selected>None (pulldown to select a folder)</option>
              <option value='101-The-Beat'>101-The-Beat</option>
              <option value='1059-The-Rock'>1059-The-Rock</option>
              <option value='107-The-River'>107-The-River</option>
              <option value='98-The-Big'>98-The-Big</option>
              <option value='Battle Music'>Battle Music</option>
              <option value='Big-Classic-Hits'>Big-Classic-Hits</option>
              <option value='Harry Potter'>Harry Potter</option>
              <option value='Marvel'>Marvel</option>
              <option value='R-and-B-Jams'>R-and-B-Jams</option>
              <option value='Star Wars'>Star Wars</option>
              <option value='Town Music'>Town Music</option>
              <option value='Travelers Themes'>Travelers Themes</option>
            </select>
          <span class="help-block">Containing local files or add YouTube content (specify below).</span>
          </div>
        </div>

        <div class="form-group">
          <label class="col-md-4 control-label" for="audiofolderNew">... or link a new folder</label>
          <div class="col-md-6">
          <input value="" id="audiofolderNew" name="audiofolderNew" placeholder="e.g. 'Artist Name/Album'" class="form-control input-md" type="text">
          <span class="help-block">Always use a new folder for streams (see below) and optionally for YouTube.</span>
          </div>
        </div>

        <div class="form-group">
          <label class="col-md-4 control-label" for="TriggerCommand">... or link to a system command</label>
           <div class="col-md-6">
            <select id="TriggerCommand" name="TriggerCommand" class="form-control">
              <option value="false" selected=selected>Select command to link</option>
<option value="false">-- Audio player</option>
<option value="CMDVOLUP">Volume up by one step (CMDVOLUP RFID: NONE)</option>
<option value="CMDVOLDOWN">Volume down by one step (CMDVOLDOWN RFID: NONE)</option>
<option value="CMDSWITCHAUDIOIFACE">Switch between primary/secondary audio iFace (CMDSWITCHAUDIOIFACE RFID: NONE)</option>
<option value="CMDSTOP">Stop player (CMDSTOP RFID: NONE)</option>
<option value="CMDMUTE">Mute player (CMDMUTE RFID: NONE)</option>
<option value="CMDNEXT">Skip next track (CMDNEXT RFID: 2060335108)</option>
<option value="CMDPREV">Skip previous track (CMDPREV RFID: NONE)</option>
<option value="CMDRANDCARD">Activate random card (CMDRANDCARD RFID: NONE)</option>
<option value="CMDRANDFOLD">Play random folder (CMDRANDFOLD RFID: NONE)</option>
<option value="CMDRANDTRACK">Jump to random track (CMDRANDTRACK RFID: NONE)</option>
<option value="CMDREWIND">Restart the playlist (CMDREWIND RFID: NONE)</option>
<option value="CMDSEEKFORW">Seek ahead 15 sec. (CMDSEEKFORW RFID: NONE)</option>
<option value="CMDSEEKBACK">Seek back 15 sec. (CMDSEEKBACK RFID: NONE)</option>
<option value="CMDPAUSE">Pause player (CMDPAUSE RFID: 168245508)</option>
<option value="CMDPLAY">Resume audio playout (CMDPLAY RFID: NONE)</option>
<option value="CMDBLUETOOTHTOGGLE">Toggle between speakers and bluetooth headphones (CMDBLUETOOTHTOGGLE RFID: NONE)</option><option value="false">-- System</option>
<option value="CMDSHUTDOWN">Shutdown (CMDSHUTDOWN RFID: NONE)</option>
<option value="CMDREBOOT">Reboot (CMDREBOOT RFID: NONE)</option><option value="false">-- Playlist</option>
<option value="CMDSHUFFLE">Shuffle mode toggle for current playlist (random on/off) (CMDSHUFFLE RFID: NONE)</option>
<option value="CMDPLAYCUSTOMPLS">Play custom playlist (CMDPLAYCUSTOMPLS RFID: NONE)</option><option value="false">-- Set volume (in percent)</option>
<option value="CMDVOL30">Volume 30% (CMDVOL30 RFID: NONE)</option>
<option value="CMDVOL50">Volume 50% (CMDVOL50 RFID: NONE)</option>
<option value="CMDVOL75">Volume 75% (CMDVOL75 RFID: NONE)</option>
<option value="CMDVOL80">Volume 80% (CMDVOL80 RFID: NONE)</option>
<option value="CMDVOL85">Volume 85% (CMDVOL85 RFID: NONE)</option>
<option value="CMDVOL90">Volume 90% (CMDVOL90 RFID: NONE)</option>
<option value="CMDVOL95">Volume 95% (CMDVOL95 RFID: NONE)</option>
<option value="CMDVOL100">Volume 100% (CMDVOL100 RFID: NONE)</option><option value="false">-- Set maximum volume (in percent)</option>
<option value="CMDMAXVOL30">Max. Volume 30% (CMDMAXVOL30 RFID: NONE)</option>
<option value="CMDMAXVOL50">Max. Volume 50% (CMDMAXVOL50 RFID: NONE)</option>
<option value="CMDMAXVOL75">Max. Volume 75% (CMDMAXVOL75 RFID: NONE)</option>
<option value="CMDMAXVOL80">Max. Volume 80% (CMDMAXVOL80 RFID: NONE)</option>
<option value="CMDMAXVOL85">Max. Volume 85% (CMDMAXVOL85 RFID: NONE)</option>
<option value="CMDMAXVOL90">Max. Volume 90% (CMDMAXVOL90 RFID: NONE)</option>
<option value="CMDMAXVOL95">Max. Volume 95% (CMDMAXVOL95 RFID: NONE)</option>
<option value="CMDMAXVOL100">Max. Volume 100% (CMDMAXVOL100 RFID: NONE)</option><option value="false">-- Timer: stop player after x minutes</option>
<option value="STOPAFTER5">Stop player after 5 min. (STOPAFTER5 RFID: NONE)</option>
<option value="STOPAFTER15">Stop player after 15 min. (STOPAFTER15 RFID: NONE)</option>
<option value="STOPAFTER30">Stop player after 30 min. (STOPAFTER30 RFID: NONE)</option>
<option value="STOPAFTER60">Stop player after 60 min. (STOPAFTER60 RFID: NONE)</option>
<option value="STOPAFTER120">Stop player after 120 min. (STOPAFTER120 RFID: NONE)</option>
<option value="STOPAFTER180">Stop player after 180 min. (STOPAFTER180 RFID: NONE)</option>
<option value="STOPAFTER240">Stop player after 240 min. (STOPAFTER240 RFID: NONE)</option><option value="false">-- Timer: shutdown after x minutes</option>
<option value="SHUTDOWNAFTER5">Shutdown after 5 min. (SHUTDOWNAFTER5 RFID: NONE)</option>
<option value="SHUTDOWNAFTER15">Shutdown after 15 min. (SHUTDOWNAFTER15 RFID: NONE)</option>
<option value="SHUTDOWNAFTER30">Shutdown after 30 min. (SHUTDOWNAFTER30 RFID: NONE)</option>
<option value="SHUTDOWNAFTER60">Shutdown after 60 min. (SHUTDOWNAFTER60 RFID: NONE)</option>
<option value="SHUTDOWNAFTER120">Shutdown after 120 min. (SHUTDOWNAFTER120 RFID: NONE)</option>
<option value="SHUTDOWNAFTER180">Shutdown after 180 min. (SHUTDOWNAFTER180 RFID: NONE)</option>
<option value="SHUTDOWNAFTER240">Shutdown after 240 min. (SHUTDOWNAFTER240 RFID: NONE)</option><option value="false">-- Shutdown Timer Volume Reduction : reduce volume until shutdown in x minutes</option>
<option value="SHUTDOWNVOLUMEREDUCTION10">Reduce volume and Shutdown after 10 min. (SHUTDOWNVOLUMEREDUCTION10 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION15">Reduce volume and Shutdown after 15 min. (SHUTDOWNVOLUMEREDUCTION15 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION30">Reduce volume and Shutdown after 30 min. (SHUTDOWNVOLUMEREDUCTION30 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION60">Reduce volume and Shutdown after 60 min. (SHUTDOWNVOLUMEREDUCTION60 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION120">Reduce volume and Shutdown after 120 min. (SHUTDOWNVOLUMEREDUCTION120 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION180">Reduce volume and Shutdown after 180 min. (SHUTDOWNVOLUMEREDUCTION180 RFID: NONE)</option>
<option value="SHUTDOWNVOLUMEREDUCTION240">Reduce volume and Shutdown after 240 min. (SHUTDOWNVOLUMEREDUCTION240 RFID: NONE)</option><option value="false">-- Wifi: switch on/off and other</option>
<option value="ENABLEWIFI">Enable Wifi (ENABLEWIFI RFID: NONE)</option>
<option value="DISABLEWIFI">Disable Wifi (DISABLEWIFI RFID: NONE)</option>
<option value="TOGGLEWIFI">Toggle Wifi on/off (TOGGLEWIFI RFID: NONE)</option>
<option value="CMDREADWIFIIP">Read out the Wifi IP over the Sublim3 JukeBox speakers (CMDREADWIFIIP RFID: NONE)</option><option value="false">-- Recording audio commands</option>
<option value="RECORDSTART10">Start recording for 10 sec. duration (RECORDSTART10 RFID: NONE)</option>
<option value="RECORDSTART60">Start recording for 60 sec. duration (RECORDSTART60 RFID: NONE)</option>
<option value="RECORDSTART600">Start recording for 600 sec. duration (RECORDSTART600 RFID: NONE)</option>
<option value="RECORDSTOP">Stop recording (RECORDSTOP RFID: NONE)</option>
<option value="RECORDPLAYBACKLATEST">Replay latest recording (RECORDPLAYBACKLATEST RFID: NONE)</option><option value="false">-- Synchronisation</option>
<option value="SYNCSHAREDFULL">Synchronise all shared files (SYNCSHAREDFULL RFID: NONE)</option>
<option value="SYNCSHAREDONRFIDSCANTOGGLE">Toggle activation of 'sync on RFID scan' (SYNCSHAREDONRFIDSCANTOGGLE RFID: NONE)</option>
            </select>
            <span class="help-block">Select system commands (like 'pause', 'volume up', 'shutdown') from the list of available commands. If a RFID card is already linked to a function, the ID is shown in the pulldown menu.</span>
          </div>
        </div>
        </fieldset>

        <fieldset>
        <legend>Link Stream</legend>

        <div class="form-group">
          <label class="col-md-4 control-label" for="streamURL">Stream URL (always requires new folder above)</label>
          <div class="col-md-6">
          <input value="" id="streamURL" name="streamURL" placeholder="http(...).mp3 / .m3u / .ogg / .rss / .xml / ..." class="form-control input-md" type="text">
          <span class="help-block">Add the URL for spotify, podcast, web radio, stream or other online media</span>
          </div>
        </div>

        <div class="form-group">
          <label class="col-md-4 control-label" for="streamType"></label>
          <div class="col-md-6">
            <select id="streamType" name="streamType" class="form-control">
              <option value="false" selected=selected>Select type</option>
              <option value='podcast'>Podcast</option>
              <option value='livestream'>Web radio / live stream</option>
              <option value='other'>Other</option>
            </select>
            <span class="help-block">Select the type you are adding</span>
          </div>
        </div>

        </fieldset>

        <fieldset>
        <legend>Download YouTube</legend>

        <div class="form-group">
          <label class="col-md-4 control-label" for="YTstreamURL">YouTube URL (single clip or playlist)</label>
          <div class="col-md-6">
          <input value="" id="YTstreamURL" name="YTstreamURL" placeholder="e.g. https://www.youtube.com/watch?v=7GI0VdPehQI" class="form-control input-md" type="text">
          <span class="help-block">Full YouTube-URL of clip or playlist. Will be downloaded in the folder specified above or the new one if specified.</span>
          </div>
        </div>
        </fieldset>

        <div class="form-group">
          <label class="col-md-4 control-label" for="submit"></label>
          <div class="col-md-8">
            <button id="submit" name="submit" class="btn btn-success" value="submit">Submit</button>
            <a href="index.php" id="cancel" name="cancel" class="btn btn-danger">Cancel</a>
            <br clear='all'><br>
          </div>
        </div>

        </form>
              </div>
            </div>
        </div>

    </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDexport"></a>
         <i class='mdi mdi-download'></i> Export all RFID links (audio playout and commands)      </h4>
    </div>

      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
                <a href="rfidExportCsv.php" class="btn btn-primary btn">
                <i class='mdi mdi-download'></i> Create .csv file of available RFID links                </a>
          </div>
        </div>
      </div>

    </div>
</div>

<div class="panel-group">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><a name="RFIDimport"></a>
         <i class='mdi mdi-plus-circle'></i> Import RFID links from .csv file      </h4>
    </div>

      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <form name='upload' enctype='multipart/form-data' method='post' action='/cardRegisterNew.php'>
            <fieldset>
            <div class="form-group">
              <label class="col-md-4 control-label" for="importFileUpload">Select .csv file to create RFID links</label>
              <div class="col-md-6">
                <input type="file" name="importFileUpload" id="importFileUpload"class="form-control input-md" >
              <span class="help-block"> </span>
              </div>
            </div>
            </fieldset>

            <div class="form-group">
              <label class="col-md-4 control-label" for="importFileOverwrite">Select import action</label>
              <div class="col-md-6">
                <select id="audiofolder" name="importFileOverwrite" class="form-control">
                  <option value="all">Overwrite both: audio AND commands</option>
                  <option value="commands">Overwrite ONLY system commands</option>
                  <option value="audio">Overwrite ONLY audio triggers</option>
                </select>
              <span class="help-block">Specify what to do with the uploaded RFID links.</span>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-4 control-label" for="importFileDelete">Delete or keep other RFID links?</label>
              <div class="col-md-6">
                <select id="audiofolder" name="importFileDelete" class="form-control">
                  <option value="none">Keep all existing: audio AND commands</option>
                  <option value="all">Delete both: audio AND commands</option>
                  <option value="commands">Delete ONLY system commands</option>
                  <option value="audio">Delete ONLY audio triggers</option>
                </select>
              <span class="help-block">Which of the existing RFID links should be kept, which deleted?.</span>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-4 control-label" for="submit"></label>
              <div class="col-md-8">
                <button id="submit" name="upload" class="btn btn-success" value="submit">Upload</button>
                <br clear='all'><br>
              </div>
            </div>

            </form>
          </div>
        </div>
      </div>

    </div>
</div>

  </div>

<script>
$(document).ready(function() {
    $('#refresh_id').load('ajax.refresh_id.php');
    var refreshId = setInterval(function() {
        $('#refresh_id').load('ajax.refresh_id.php?' + 1*new Date());
    }, 1000);
});
</script>

</body>
</html>
