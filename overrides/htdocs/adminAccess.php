<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("inc.header.php");

$overrideDuration = 1800;

// Enable override
if (isset($_GET['enableAdminOverride']) && $_GET['enableAdminOverride'] == '1') {
    $_SESSION['sublim3_admin_override_until'] = time() + $overrideDuration;
    header("Location: adminAccess.php");
    exit;
}

// Disable override
if (isset($_GET['disableAdminOverride']) && $_GET['disableAdminOverride'] == '1') {
    unset($_SESSION['sublim3_admin_override_until']);
    header("Location: adminAccess.php");
    exit;
}

$adminOverrideEnabled = false;
$adminOverrideRemaining = 0;

if (
    isset($_SESSION['sublim3_admin_override_until']) &&
    $_SESSION['sublim3_admin_override_until'] > time()
) {
    $adminOverrideEnabled = true;
    $adminOverrideRemaining = $_SESSION['sublim3_admin_override_until'] - time();
} else {
    unset($_SESSION['sublim3_admin_override_until']);
}

html_bootstrap3_createHeader("en", "Admin Access | SubLim3 JukeBox", $conf['base_url']);
?>
<body>
<div class="container">

<?php include("inc.navigation.php"); ?>

<!-- ADMIN STATUS -->
<div class="row">
  <div class="col-lg-12">
    <div class="alert <?php echo $adminOverrideEnabled ? 'alert-success' : 'alert-warning'; ?>">
      <?php if ($adminOverrideEnabled) { ?>
        <strong>Admin Override Active</strong><br>
        Remaining: <?php echo $adminOverrideRemaining; ?> seconds
      <?php } else { ?>
        <strong>Admin Override Inactive</strong>
      <?php } ?>
    </div>
  </div>
</div>

<!-- ADMIN TOOLS -->
<div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title">
      <i class="mdi mdi-shield-account"></i> SubLim3 JukeBox Admin Tools
    </h4>
  </div>

  <div class="panel-body">

    <!-- BLUE INFO BOX -->
    <div style="
      background:#007bff;
      color:#fff;
      padding:15px;
      border-radius:4px;
      margin-bottom:20px;
    ">
      <strong>Admin Tools Overview</strong>
      <ul style="margin:10px 0 0 20px;">
        <li>Bypass RFID card restrictions</li>
        <li>Access Folder Management</li>
        <li>Download app via QR code</li>
      </ul>
    </div>

    <!-- MAIN BUTTON ROW -->
    <div class="row">

      <div class="col-sm-4">
        <a href="adminAccess.php?enableAdminOverride=1"
           class="btn btn-success btn-lg btn-block">
          <i class="mdi mdi-lock-open"></i> Enable Admin Override
        </a>
      </div>

      <div class="col-sm-4">
        <a href="manageFilesFolders.php"
           class="btn btn-warning btn-lg btn-block">
          <i class="mdi mdi-folder"></i> Open Folder Manager
        </a>
      </div>

      <div class="col-sm-4">
        <a href="adminAccess.php?disableAdminOverride=1"
           class="btn btn-danger btn-lg btn-block">
          <i class="mdi mdi-lock"></i> Disable Admin Override
        </a>
      </div>

    </div>

    <!-- READ IP BUTTON (FULL WIDTH MATCHING SIZE) -->
    <div class="row" style="margin-top:15px;">
      <div class="col-sm-4">
        <a href="readIP.php"
           class="btn btn-lg btn-block"
           style="
             background:#2196F3 !important;
             border-color:#2196F3 !important;
             color:#ffffff !important;
           ">
          <i class="mdi mdi-wifi"></i> Read IP Address
        </a>
      </div>
    </div>

  </div>
</div>

<!-- QR CODE PANEL (MATCH WIDTH) -->
<div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title">
      <i class="mdi mdi-qrcode"></i> SubLim3 JukeBox App QR Code
    </h4>
  </div>

  <div class="panel-body text-center">
    <img src="_assets/icons/SubLim3-JukeBox-App-QR.png"
         style="max-width:300px; width:100%;">
    <br><br>
    <p>Scan to access the SubLim3 JukeBox app</p>
  </div>
</div>

</div>
</body>
</html>
