<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("inc.header.php");

/* NO CHANGES BENEATH THIS LINE ***********/

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
$conf['url_abs'] = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

/*******************************************
* ACCESS CONTROL FUNCTIONS
*******************************************/
function getCardRegisterAccessConfig($filePath) {
    $config = array('enabled' => true,'expires' => null);

    if (!file_exists($filePath)) return $config;

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return $config;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            if ($key === 'enabled') {
                $config['enabled'] = in_array(strtolower($value), ['1','true','yes','enabled'], true);
            }
            if ($key === 'expires') {
                $config['expires'] = $value;
            }
        }
    }
    return $config;
}

function isCardRegisterAccessEnabled($filePath) {
    $config = getCardRegisterAccessConfig($filePath);
    if (!$config['enabled']) return false;

    if (!empty($config['expires'])) {
        $expiresTs = strtotime($config['expires']);
        if ($expiresTs && time() > $expiresTs) return false;
    }
    return true;
}

function formatTimeRemaining($seconds) {
    $seconds = max(0, (int)$seconds);
    return gmdate("H:i:s", $seconds);
}

$cardRegisterAccessFile = realpath(getcwd() . '/../settings/') . '/cardRegisterAccess';
$config = getCardRegisterAccessConfig($cardRegisterAccessFile);
$enabled = isCardRegisterAccessEnabled($cardRegisterAccessFile);

$expiresTs = strtotime($config['expires']);
$remaining = $expiresTs ? ($expiresTs - time()) : null;

html_bootstrap3_createHeader("en", "RFID Card | SubLim3 JukeBox", $conf['base_url']);
?>

<body class="sublim3-theme-green">
<div class="container">

<?php include("inc.navigation.php"); ?>

<?php if ($enabled && $remaining > 0): ?>

<div class="row">
  <div class="col-lg-12">
    <div class="alert alert-success">
      <strong>Temporary Access Active</strong><br>
      Expires in <?php echo formatTimeRemaining($remaining); ?>
    </div>
  </div>
</div>

<!-- ✅ WORKING BUTTON (VISIBLE) -->
<div class="row" style="margin-top:15px; margin-bottom:15px;">
  <div class="col-sm-4">
    <a href="manageFilesFolders.php"
       style="
         display:block;
         width:100%;
         padding:14px 18px;
         text-align:center;
         text-decoration:none !important;
         background:rgba(255,255,255,0.08);
         border:2px solid #FFFFFF;
         border-radius:6px;
         color:#FFFFFF !important;
         font-size:18px;
         font-weight:bold;
       ">
      <i class="mdi mdi-folder-multiple"></i> Upload Files
    </a>
  </div>
</div>

<?php else: ?>

<div class="alert alert-danger">
  Card Registration Disabled
</div>

<?php exit; endif; ?>

<!-- ORIGINAL CONTENT CONTINUES -->

<div class="row">
  <div class="col-lg-12">
    <strong>Jump to:</strong>
    <a href="#RFIDinteractive">Add new card</a> |
    <a href="#RFIDexport">Export</a> |
    <a href="#RFIDimport">Import</a>
  </div>
</div>

<br>

<div class="panel panel-default">
  <div class="panel-heading">
    <h4>Add new card</h4>
  </div>
  <div class="panel-body">

<?php include("inc.formCardEdit.php"); ?>

  </div>
</div>

</div>
</body>
</html>
