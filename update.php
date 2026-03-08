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

$cmd = "sudo -u pi /home/pi/SubLim3-JukeBox/run-update.sh 2>&1";

exec($cmd, $output, $returnCode);

echo "<pre>";

foreach ($output as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "</pre>";

if ($returnCode === 0) {
    echo "<div class='alert alert-success'>Update completed successfully.</div>";
} else {
    echo "<div class='alert alert-danger'>Update completed with errors.</div>";
}

?>

    </div>
  </div>
</div>

</div>
</body>
</html>
