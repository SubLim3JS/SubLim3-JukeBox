#!/bin/bash

printf "
.
.
.
.    ___      _    _    _       ____     _      _       ___          
.   / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
.   \__ \ || | '_ \ |__| | '  \ |_ \ | || | || | / / -_) _ \/ _ \ \ /
.   |___/\_,_|_.__/____|_|_|_|_|___/  \__/ \_,_|_\_\___|___/\___/_\_\
                                                                  
.
.
.
"

sleep 2

printf ""
printf ""
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

# Check and move custom-green.css
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

# Check and copy the custom custom-green.css
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

sleep 3

printf "*********************************************************\n"
printf "*** Rename index.php so the custom file can be added. ***\n"
printf "*********************************************************\n\n"

sleep 3

# Check and move index.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/index.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/index.php ~/RPi-Jukebox-RFID/htdocs/index.php-BACKUP
    printf ""
    printf " - Default index.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File index.php not found in ~/RPi-Jukebox-RFID/htdocs/ - \n\n\n"
    printf ""
fi

printf "************************************************************\n"
printf "**** Move SubLim3 custom index.php to the htdocs folder. ****\n"
printf "************************************************************\n\n"

sleep 3

# Check and copy the custom index.php
if [ -f ~/SubLim3-JukeBox/index.php ]; then
    cp -f ~/SubLim3-JukeBox/index.php ~/RPi-Jukebox-RFID/htdocs/index.php
    printf ""
    printf " - SubLim3 index.php has been moved to the JukeBox htdocs folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File index.php not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi

sleep 3

printf "**************************************************************\n"
printf "*** Rename lang-en-UK.php so the custom file can be added. ***\n"
printf "**************************************************************\n\n"

sleep 3

# Check and move lang-en-UK.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/lang/lang-en-UK.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/lang/lang-en-UK.php ~/RPi-Jukebox-RFID/htdocs/lang/lang-en-UK.php-BACKUP
    printf ""
    printf " - Default lang-en-UK.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File lang-en-UK.php not found in ~/RPi-Jukebox-RFID/htdocs/lang/ - \n\n\n"
    printf ""
fi

printf "************************************************************\n"
printf "**** Move SubLim3 custom lang-en-UK.php to the htdocs folder. ****\n"
printf "************************************************************\n\n"

sleep 3

# Check and copy the custom lang-en-UK.php
if [ -f ~/SubLim3-JukeBox/lang-en-UK.php ]; then
    cp -f ~/SubLim3-JukeBox/lang-en-UK.php ~/RPi-Jukebox-RFID/htdocs/lang/lang-en-UK.php
    printf ""
    printf " - SubLim3 lang-en-UK.php has been moved to the JukeBox lang folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File lang-en-UK.php not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi



sleep 3

printf "********************************************************\n"
printf "*** Rename search.php so the custom file can be added. ***\n"
printf "********************************************************\n\n"

sleep 3

# Check and move search.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/search.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/search.php ~/RPi-Jukebox-RFID/htdocs/search.php-BACKUP
    printf ""
    printf " - Default search.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File search.php not found in ~/RPi-Jukebox-RFID/htdocs/ - \n\n\n"
    printf ""
fi

printf "**************************************************************\n"
printf "**** Move SubLim3 custom search.php to the htdocs folder. ****\n"
printf "**************************************************************\n\n"

sleep 3

# Check and copy the custom search.php
if [ -f ~/SubLim3-JukeBox/search.php ]; then
    cp -f ~/SubLim3-JukeBox/search.php ~/RPi-Jukebox-RFID/htdocs/search.php
    printf ""
    printf " - SubLim3 search.php has been moved to the JukeBox htdocs folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File search.php not found in ~/SubLim3-JukeBox/htdocs/ \n\n\n"
    printf ""
fi



sleep 3

printf "************************************************************\n"
printf "*** Rename settings.php so the custom file can be added. ***\n"
printf "************************************************************\n\n"

sleep 3

# Check and move settings.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/settings.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/settings.php ~/RPi-Jukebox-RFID/htdocs/settings.php-BACKUP
    printf ""
    printf " - Default settings.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File settings.php not found in ~/RPi-Jukebox-RFID/htdocs/ - \n\n\n"
    printf ""
fi

printf "****************************************************************\n"
printf "**** Move SubLim3 custom settings.php to the htdocs folder. ****\n"
printf "****************************************************************\n\n"

sleep 3

# Check and copy the custom settings.php
if [ -f ~/SubLim3-JukeBox/settings.php ]; then
    cp -f ~/SubLim3-JukeBox/settings.php ~/RPi-Jukebox-RFID/htdocs/settings.php
    printf ""
    printf " - SubLim3 settings.php has been moved to the JukeBox htdocs folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File settings.php not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi



sleep 3

printf "**************************************************************\n"
printf "*** Rename systemInfo.php so the custom file can be added. ***\n"
printf "**************************************************************\n\n"

sleep 3

# Check and move systemInfo.php
if [ -f ~/RPi-Jukebox-RFID/htdocs/systemInfo.php ]; then
    mv -f ~/RPi-Jukebox-RFID/htdocs/systemInfo.php ~/RPi-Jukebox-RFID/htdocs/systemInfo.php-BACKUP
    printf ""
    printf " - Default systemInfo.php has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File systemInfo.php not found in ~/RPi-Jukebox-RFID/htdocs/ - \n\n\n"
    printf ""
fi

printf "******************************************************************\n"
printf "**** Move SubLim3 custom systemInfo.php to the htdocs folder. ****\n"
printf "******************************************************************\n\n"

sleep 3

# Check and copy the custom systemInfo.php
if [ -f ~/SubLim3-JukeBox/systemInfo.php ]; then
    cp -f ~/SubLim3-JukeBox/systemInfo.php ~/RPi-Jukebox-RFID/htdocs/systemInfo.php
    printf ""
    printf " - SubLim3 systemInfo.php has been moved to the JukeBox htdocs folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File systemInfo.php not found in ~/SubLim3-JukeBox/ \n\n\n"
    printf ""
fi

sleep 3

printf "**************************************************************\n"
printf "*** Rename version-number so the custom file can be added. ***\n"
printf "**************************************************************\n\n"

sleep 3

# Check and move version-number
if [ -f ~/RPi-Jukebox-RFID/settings/version-number ]; then
    mv -f ~/RPi-Jukebox-RFID/settings/version-number ~/RPi-Jukebox-RFID/settings/version-number-BACKUP
    printf ""
    printf " - Default version-number has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File version-number not found in ~/RPi-Jukebox-RFID/settings/ - \n\n\n"
    printf ""
fi

printf "********************************************************************\n"
printf "**** Move SubLim3 custom version-number to the settings folder. ****\n"
printf "********************************************************************\n\n"

sleep 3

# Check and copy the custom version-number
if [ -f ~/SubLim3-JukeBox/version-number ]; then
    cp -f ~/SubLim3-JukeBox/version-number ~/RPi-Jukebox-RFID/settings/version-number
    printf ""
    printf " - SubLim3 version-number has been moved to the JukeBox settings folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File version-number not found in ~/SubLim3-JukeBox/version-number \n\n\n"
    printf ""
fi


printf "***************************************************\n"
printf "***  - All operations completed successfully. - ***\n"
printf "***************************************************\n\n"

sleep 1
