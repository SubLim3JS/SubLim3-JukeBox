<?php
include("inc.header.php");

function run_cmd($cmd)
{
    return trim(shell_exec($cmd));
}

function esc_html($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getWifiIp()
{
    $ip = trim(shell_exec("ip -4 addr show wlan0 2>/dev/null | awk '/inet / {print \$2}' | cut -d/ -f1 | head -n 1"));
    if (!empty($ip)) {
        return $ip;
    }

    $ip = trim(shell_exec("hostname -I 2>/dev/null | awk '{print \$1}'"));
    if (!empty($ip)) {
        return $ip;
    }

    return "Not connected";
}

function getWifiSsid()
{
    $ssid = trim(shell_exec("iwgetid -r 2>/dev/null"));
    if (!empty($ssid)) {
        return $ssid;
    }

    return "Hidden / Not connected";
}

function getHostnameValue()
{
    $hostname = trim(shell_exec("hostname 2>/dev/null"));
    return !empty($hostname) ? $hostname : "Unknown";
}

function getUptimeValue()
{
    $uptime = trim(shell_exec("uptime -p 2>/dev/null"));
    return !empty($uptime) ? $uptime : "Unknown";
}

function getLoadValue()
{
    $load = trim(shell_exec("cat /proc/loadavg 2>/dev/null | awk '{print \$1, \$2, \$3}'"));
    return !empty($load) ? $load : "Unknown";
}

function getMemoryValue()
{
    $mem = trim(shell_exec("free -h 2>/dev/null | awk '/^Mem:/ {print \$3 \" / \" \$2}'"));
    return !empty($mem) ? $mem : "Unknown";
}

function getDiskValue()
{
    $disk = trim(shell_exec("df -h / 2>/dev/null | awk 'NR==2 {print \$3 \" used / \" \$2 \" total (\" \$5 \")\"}'"));
    return !empty($disk) ? $disk : "Unknown";
}

function getVersionValue()
{
    $versionFile = '/home/pi/RPi-Jukebox-RFID/settings/version-number';
    if (file_exists($versionFile)) {
        $version = trim(file_get_contents($versionFile));
        if (!empty($version)) {
            return $version;
        }
    }
    return "Unknown";
}

function getRfidDetected()
{
    $latestFile = '/home/pi/RPi-Jukebox-RFID/settings/Latest_RFID';
    if (file_exists($latestFile)) {
        $value = trim(file_get_contents($latestFile));
        if (!empty($value)) {
            return $value;
        }
    }
    return "No recent scan";
}

$statusFile = '/home/pi/RPi-Jukebox-RFID/shared/logs/usb-import-status.json';
$usbImportStatus = null;

if (file_exists($statusFile) && is_readable($statusFile)) {
    $json = file_get_contents($statusFile);
    $data = json_decode($json, true);
    if (is_array($data)) {
        $usbImportStatus = $data;
    }
}

$wifiIp = getWifiIp();
$wifiSsid = getWifiSsid();
$hostname = getHostnameValue();
$uptime = getUptimeValue();
$load = getLoadValue();
$memory = getMemoryValue();
$disk = getDiskValue();
$version = getVersionValue();
$rfidDetected = getRfidDetected();

$openUrl = "#";
if ($wifiIp !== "Not connected") {
    $openUrl = "http://" . $wifiIp;
}
?>

<div class="container">
  <div class="row">
    <div class="col-lg-12">

      <div class="panel panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title">
            <i class="mdi mdi-information-outline"></i> SubLim3 JukeBox Info
          </h3>
        </div>

        <div class="panel-body">

          <?php if (!empty($usbImportStatus) && (($usbImportStatus['state'] ?? '') === 'running')): ?>
            <div class="alert alert-info">
              <strong>USB Import In Progress</strong><br>
              <?php echo esc_html($usbImportStatus['message'] ?? 'Importing files from USB...'); ?><br>
              <?php if (!empty($usbImportStatus['updated'])): ?>
                <small>Last updated: <?php echo esc_html($usbImportStatus['updated']); ?></small><br>
              <?php endif; ?>
              Please do not remove the USB drive yet.
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6">
              <h4>System</h4>
              <table class="table table-striped table-condensed">
                <tr>
                  <th style="width: 180px;">Hostname</th>
                  <td><?php echo esc_html($hostname); ?></td>
                </tr>
                <tr>
                  <th>Version</th>
                  <td><?php echo esc_html($version); ?></td>
                </tr>
                <tr>
                  <th>Uptime</th>
                  <td><?php echo esc_html($uptime); ?></td>
                </tr>
                <tr>
                  <th>Load Average</th>
                  <td><?php echo esc_html($load); ?></td>
                </tr>
                <tr>
                  <th>Memory Usage</th>
                  <td><?php echo esc_html($memory); ?></td>
                </tr>
                <tr>
                  <th>Disk Usage</th>
                  <td><?php echo esc_html($disk); ?></td>
                </tr>
              </table>
            </div>

            <div class="col-md-6">
              <h4>Network & RFID</h4>
              <table class="table table-striped table-condensed">
                <tr>
                  <th style="width: 180px;">WiFi SSID</th>
                  <td><?php echo esc_html($wifiSsid); ?></td>
                </tr>
                <tr>
                  <th>WiFi IP</th>
                  <td>
                    <?php if ($wifiIp !== "Not connected"): ?>
                      <a href="<?php echo esc_html($openUrl); ?>" target="_blank"><?php echo esc_html($wifiIp); ?></a>
                    <?php else: ?>
                      <?php echo esc_html($wifiIp); ?>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th>RFID Detected</th>
                  <td><?php echo esc_html($rfidDetected); ?></td>
                </tr>
              </table>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-lg-12 text-center">
              <a href="readIP.php" class="btn btn-info" style="margin: 5px;">
                <i class="mdi mdi-wifi"></i> Read IP Address
              </a>

              <a href="adminAccess.php" class="btn btn-warning" style="margin: 5px;">
                <i class="mdi mdi-shield-key-outline"></i> Admin Tools
              </a>

              <a href="update.php" class="btn btn-success" style="margin: 5px;">
                <i class="mdi mdi-update"></i> Run Update
              </a>
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
