<?php
include("inc.header.php");

/**************************************************
 * SUBLIM3 THEME CONFIG
 **************************************************/

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

/**************************************************
 * LOAD CURRENT THEME
 **************************************************/
if (file_exists($themeFile)) {
    $lines = @file($themeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos(trim($line), "theme=") === 0) {
                $savedTheme = trim(substr($line, 6));
                if (array_key_exists($savedTheme, $availableThemes)) {
                    $currentTheme = $savedTheme;
                }
            }
        }
    }
}

/**************************************************
 * HANDLE FORM SUBMIT
 **************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["sublim3_theme"])) {

    $selectedTheme = trim($_POST["sublim3_theme"]);

    if (array_key_exists($selectedTheme, $availableThemes)) {

        $configData = "theme=" . $selectedTheme . PHP_EOL;

        if (@file_put_contents($themeFile, $configData) !== false) {

            // Run theme builder
            @shell_exec("bash " . escapeshellarg($themeBuilder) . " >/dev/null 2>&1 &");

            $currentTheme = $selectedTheme;
            $themeMessage = "Theme updated to " . htmlspecialchars($availableThemes[$selectedTheme]) . ".";
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
?>

<div class="container">
  <div class="row">
    <div class="col-lg-12">

      <!-- =========================================
           SUBLIM3 THEME PANEL
           ========================================= -->
      <div class="panel panel-primary">
        <div class="panel-heading">
          <i class="mdi mdi-palette"></i> SubLim3 JukeBox Theme
        </div>

        <div class="panel-body">

          <?php if (!empty($themeMessage)) { ?>
            <div class="alert alert-<?php echo $themeMessageType; ?>">
              <?php echo htmlspecialchars($themeMessage); ?>
            </div>
          <?php } ?>

          <form method="post">

            <div class="form-group">
              <label for="sublim3_theme">Select Theme Color</label>

              <select name="sublim3_theme" id="sublim3_theme" class="form-control">

                <?php foreach ($availableThemes as $value => $label) { ?>
                  <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo ($currentTheme === $value ? 'selected="selected"' : ''); ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php } ?>

              </select>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="mdi mdi-content-save"></i> Save Theme
            </button>

          </form>

        </div>
      </div>

      <!-- =========================================
           KEEP ORIGINAL PHONIEBOX SETTINGS BELOW
           ========================================= -->

      <?php
      /**************************************************
       * ORIGINAL PHONIEBOX SETTINGS CONTENT
       * Leave everything below this line untouched
       **************************************************/
      ?>

    </div>
  </div>
</div>

<?php include("inc.footer.php"); ?>
