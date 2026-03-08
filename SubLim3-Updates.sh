#!/bin/bash

printf "
.
.
.
  ___      _    _    _       ____     _      _       ___          
 / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
 \__ \ || | '_ \ |__| | '  \ |_ \ | || | || | / / -_) _ \/ _ \ \ /
 |___/\_,_|_.__/____|_|_|_|_|___/  \__/ \_,_|_\_\___|___/\___/_\_\
                                                                  
.
.
.
"

sleep 2

printf "********************************************************\n"
printf "*** Rename func.php so the custom file can be added. ***\n"
printf "********************************************************\n\n"

sleep 3

# Check and move func.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/func.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/func.php ~/RPi-Jukebox-RFID/htdocs/func.php-BACKUP
    printf ""
    printf " - Default func.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File func.php not found in ~/RPi-Jukebox-RFID/htdocs/ - \n\n\n"
    printf ""
fi

printf "************************************************************\n"
printf "**** Move SubLim3 custom func.php to the htdocs folder. ****\n"
printf "************************************************************\n\n"

sleep 3

# Check and copy the custom func.php
if [ -f ~/SubLim3-JukeBox/func.php ]; then
    cp -f ~/SubLim3-JukeBox/func.php ~/RPi-Jukebox-RFID/htdocs/func.php
    printf ""
    printf " - SubLim3 func.php has been moved to the JukeBox htdocs folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File func.php not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi


printf "****************************************************************\n"
printf "*** Rename custom-green.css so the custom file can be added. ***\n"
printf "****************************************************************\n\n"

sleep 3

# Check and move func.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/_assets/css/custom-green.css ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/_assets/css/custom-green.css ~/RPi-Jukebox-RFID/htdocs/_assets/css/custom-green.css-BACKUP
    printf ""
    printf " - The custom-green.css has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File custom-green.css not found in ~/RPi-Jukebox-RFID/htdocs/_assets/css/ - \n\n\n"
    printf ""
fi

printf "*************************************************************\n"
printf "**** Move SubLim3 custom-green.css to the htdocs folder. ****\n"
printf "*************************************************************\n\n"

sleep 3

# Check and copy the custom func.php
if [ -f ~/SubLim3-JukeBox/custom-green.css ]; then
    cp -f ~/SubLim3-JukeBox/custom-green.css ~/RPi-Jukebox-RFID/htdocs/_assets/css/custom-green.css
    printf ""
    printf " - SubLim3 custom-green.css has been moved to the JukeBox css folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File custom-green.css not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi

printf "***************************************************\n"
printf "***  - All operations completed successfully. - ***\n"
printf "***************************************************\n\n"

sleep 1
