#!/bin/bash

printf "

  ___      _    _    _       ____     _      _       ___          
 / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
 \__ \ || | '_ \ |__| | '  \ |_ \ | || | || | / / -_) _ \/ _ \ \ /
 |___/\_,_|_.__/____|_|_|_|_|___/  \__/ \_,_|_\_\___|___/\___/_\_\
                                                                  
"

sleep 2

printf "**************************************************************\n"
printf "*** Rename compliments.js so the custom file can be added. ***\n"
printf "**************************************************************\n\n"

sleep 3

# Check and move compliments.js
if [ -f ~/MagicMirror/modules/default/compliments/compliments.js ]; then
    mv -f ~/MagicMirror/modules/default/compliments/compliments.js ~/MagicMirror/modules/default/compliments/original_compliments.js
    printf ""
    printf " - Default compliments.js has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File compliments.js not found in ~/MagicMirror/modules/default/compliments/ - \n\n\n"
    printf ""
fi

printf "***********************************************************************\n"
printf "**** Move SubLim3 custom compliments.js to the compliments folder. ****\n"
printf "***********************************************************************\n\n"

sleep 3

# Check and copy the custom compliments.js
if [ -f ~/SubLim3-MagicMirror/compliments.js ]; then
    cp -f ~/SubLim3-MagicMirror/compliments.js ~/MagicMirror/modules/default/compliments/compliments.js
    printf ""
    printf " - SubLim3 compliments.js has been moved to the MagicMirror compliments module folder. - \n\n\n"
    printf ""
else
    printf ""
    printf "File compliments.js not found in ~/SubLim3-MagicMirror/ \n\n\n"
    printf ""
fi

printf "*********************************************************\n"
printf "*** Rename config.js so the custom file can be added. ***\n"
printf "*********************************************************\n\n"

sleep 3

# Check and move config.js
if [ -f ~/MagicMirror/config/config.js ]; then
    mv -f ~/MagicMirror/config/config.js ~/MagicMirror/config/original_config.js
    printf ""
    printf " - Default config.js has been archived. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File config.js not found in ~/MagicMirror/config/ - \n\n\n"
    printf ""
fi

printf "***************************************************************\n"
printf "*** Move the SubLim3 custom config.js to the config folder. ***\n"
printf "***************************************************************\n\n"

sleep 3

# Check and copy the custom config.js
if [ -f ~/SubLim3-MagicMirror/config.js ]; then
    cp -f ~/SubLim3-MagicMirror/config.js ~/MagicMirror/config/config.js
    printf ""
    printf " - SubLim3 config.js has been moved to the MagicMirror config folder. - \n\n\n"
    printf ""
else
    printf ""
    printf " - File SubLim3 config.js not found in ~/SubLim3-MagicMirror/ - \n\n\n"
    printf ""
fi
printf "***************************************************\n"
printf "***  - All operations completed successfully. - ***\n"
printf "***************************************************\n\n"

sleep 1
