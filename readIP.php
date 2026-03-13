<?php

$output = shell_exec(
    "bash /home/pi/RPi-Jukebox-RFID/scripts/playout_controls.sh -c=readwifiipoverspeaker"
);

echo "<h2>SubLim3 JukeBox</h2>";
echo "<p>Speaking IP address...</p>";

?>
