<?php

include("inc.header.php");

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en","Update | SubLim3-JukeBox",$conf['base_url']);

?>
<body>
<div class="container">

<?php include("inc.navigation.php"); ?>

<div class="panel-group">
  <div class="panel panel-default">

    <div class="panel-heading">
      <h4 class="panel-title">
        <i class='mdi mdi-update'></i> SubLim3-JukeBox Update
      </h4>
    </div>

    <div class="panel-body">

<?php

$script = "/home/pi/SubLim3-JukeBox/SubLim3-Updates.sh";

if (!file_exists($script)) {

    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> Update script not found at $script";
    echo "</div>";

} else {

    echo "<pre>";

    $cmd = "sudo -u pi bash $script 2>&1";

    exec($cmd, $output, $returnCode);

    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }

    echo "</pre>";

    if ($returnCode === 0) {

        echo "<div class='alert alert-success'>";
        echo "Update completed successfully.";
        echo "</div>";

    } else {

        echo "<div class='alert alert-danger'>";
        echo "Update completed with errors.";
        echo "</div>";
    }
}

?>

    </div>
  </div>
</div>

</div>
</body>
</html>
