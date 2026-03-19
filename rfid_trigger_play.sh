#!/bin/bash

# Reads the card ID or the folder name with audio files
# from the command line (see Usage).
# Then attempts to get the folder name from the card ID
# or play audio folder content directly
#
# Usage for card ID
# ./rfid_trigger_play.sh -i=1234567890
# or
# ./rfid_trigger_play.sh --cardid=1234567890
#
# For folder names:
# ./rfid_trigger_play.sh -d='foldername'
# or
# ./rfid_trigger_play.sh --dir='foldername'
#
# or for recursive play of subfolders
# ./rfid_trigger_play.sh -d='foldername' -v=recursive

# ADD / EDIT RFID CARDS TO CONTROL THE PHONIEBOX
# All controls are assigned to RFID cards in this
# file:
# settings/rfid_trigger_play.conf
# Please consult this file for more information.
# Do NOT edit anything in this file.

NOW=$(date +%Y-%m-%d.%H:%M:%S)

# The absolute path to the folder which contains all the scripts.
PATHDATA="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE_DIR="/home/pi/RPi-Jukebox-RFID"
FEEDBACK_SCRIPT="$BASE_DIR/scripts/sublim3-feedback.sh"

#############################################################
# $DEBUG TRUE|FALSE
# Read debug logging configuration file
. "$PATHDATA/../settings/debugLogging.conf"

if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
    echo "########### SCRIPT rfid_trigger_play.sh ($NOW) ##" >> "$PATHDATA/../logs/debug.log"
fi

# create the configuration file from sample - if it does not exist
if [ ! -f "$PATHDATA/../settings/rfid_trigger_play.conf" ]; then
    cp "$PATHDATA/../settings/rfid_trigger_play.conf.sample" "$PATHDATA/../settings/rfid_trigger_play.conf"
    sudo chown -R pi:www-data "$PATHDATA/../settings/rfid_trigger_play.conf"
    sudo chmod -R 775 "$PATHDATA/../settings/rfid_trigger_play.conf"
fi

###########################################################
# Read global configuration file (and create if not exists)
if [ ! -f "$PATHDATA/../settings/global.conf" ]; then
    . "$PATHDATA/inc.writeGlobalConfig.sh"
fi
. "$PATHDATA/../settings/global.conf"

# Read configuration file
. "$PATHDATA/../settings/rfid_trigger_play.conf"

# Get args from command line (see Usage above)
. "$PATHDATA/inc.readArgsFromCommandLine.sh"

#######################
# Activation status of component sync-shared-from-server
SYNCSHAREDENABLED="FALSE"
if [ -f "$PATHDATA/../settings/sync-shared-enabled" ]; then
    SYNCSHAREDENABLED=$(cat "$PATHDATA/../settings/sync-shared-enabled")
fi

if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
    echo "Sync: SYNCSHAREDENABLED=$SYNCSHAREDENABLED" >> "$PATHDATA/../logs/debug.log"
fi

##################################################################
# Check if we got the card ID or the audio folder from the prompt.
if [ "$CARDID" ]; then
    echo "Card ID '$CARDID' was used at '$NOW'." > "$PATHDATA/../shared/latestID.txt"
    echo "$CARDID" > "$PATHDATA/../settings/Latest_RFID"

    if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
        echo "Card ID '$CARDID' was used" >> "$PATHDATA/../logs/debug.log"
    fi

    # Play scan sound in background
    bash "$FEEDBACK_SCRIPT" card >/dev/null 2>&1 &

    # If the input is of 'special' use, don't treat it like a trigger to play audio.
    case $CARDID in
        $CMDSHUFFLE)
            $PATHDATA/playout_controls.sh -c=playershuffle
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL30)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=30
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL50)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=50
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL75)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=75
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL80)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=80
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL85)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=85
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL90)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=90
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL95)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=95
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMAXVOL100)
            $PATHDATA/playout_controls.sh -c=setmaxvolume -v=100
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDMUTE)
            $PATHDATA/playout_controls.sh -c=mute
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL30)
            $PATHDATA/playout_controls.sh -c=setvolume -v=30
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL50)
            $PATHDATA/playout_controls.sh -c=setvolume -v=50
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL75)
            $PATHDATA/playout_controls.sh -c=setvolume -v=75
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL80)
            $PATHDATA/playout_controls.sh -c=setvolume -v=80
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL85)
            $PATHDATA/playout_controls.sh -c=setvolume -v=85
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL90)
            $PATHDATA/playout_controls.sh -c=setvolume -v=90
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL95)
            $PATHDATA/playout_controls.sh -c=setvolume -v=95
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOL100)
            $PATHDATA/playout_controls.sh -c=setvolume -v=100
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOLUP)
            $PATHDATA/playout_controls.sh -c=volumeup
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDVOLDOWN)
            $PATHDATA/playout_controls.sh -c=volumedown
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDSWITCHAUDIOIFACE)
            $PATHDATA/playout_controls.sh -c=switchaudioiface
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDSTOP)
            $PATHDATA/playout_controls.sh -c=playerstop
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDSHUTDOWN)
            $PATHDATA/playout_controls.sh -c=shutdown
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDREBOOT)
            $PATHDATA/playout_controls.sh -c=reboot
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDNEXT)
            $PATHDATA/playout_controls.sh -c=playernext
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDPREV)
            sudo "$PATHDATA/playout_controls.sh" -c=playerprev
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDRANDCARD)
            $PATHDATA/playout_controls.sh -c=randomcard
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDRANDFOLD)
            $PATHDATA/playout_controls.sh -c=randomfolder
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDRANDTRACK)
            $PATHDATA/playout_controls.sh -c=randomtrack
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDREWIND)
            sudo "$PATHDATA/playout_controls.sh" -c=playerrewind
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDSEEKFORW)
            $PATHDATA/playout_controls.sh -c=playerseek -v=+15
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDSEEKBACK)
            $PATHDATA/playout_controls.sh -c=playerseek -v=-15
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDPAUSE)
            $PATHDATA/playout_controls.sh -c=playerpause
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDPLAY)
            $PATHDATA/playout_controls.sh -c=playerplay
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER5)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=5
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER15)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=15
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER30)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=30
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER60)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=60
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER120)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=120
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER180)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=180
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $STOPAFTER240)
            $PATHDATA/playout_controls.sh -c=playerstopafter -v=240
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER5)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=5
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER15)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=15
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER30)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=30
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER60)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=60
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER120)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=120
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER180)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=180
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNAFTER240)
            $PATHDATA/playout_controls.sh -c=shutdownafter -v=240
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION10)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=10
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION15)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=15
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION30)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=30
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION60)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=60
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION120)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=120
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION180)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=180
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SHUTDOWNVOLUMEREDUCTION240)
            $PATHDATA/playout_controls.sh -c=shutdownvolumereduction -v=240
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $ENABLEWIFI)
            $PATHDATA/playout_controls.sh -c=enablewifi
            bash "$FEEDBACK_SCRIPT" wifi >/dev/null 2>&1 &
            exit 0
            ;;
        $DISABLEWIFI)
            $PATHDATA/playout_controls.sh -c=disablewifi
            bash "$FEEDBACK_SCRIPT" wifi >/dev/null 2>&1 &
            exit 0
            ;;
        $TOGGLEWIFI)
            $PATHDATA/playout_controls.sh -c=togglewifi
            bash "$FEEDBACK_SCRIPT" wifi >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDPLAYCUSTOMPLS)
            $PATHDATA/playout_controls.sh -c=playlistaddplay -v="PhonieCustomPLS" -d="PhonieCustomPLS"
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $RECORDSTART600)
            $PATHDATA/playout_controls.sh -c=recordstart -v=600
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $RECORDSTART60)
            $PATHDATA/playout_controls.sh -c=recordstart -v=60
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $RECORDSTART10)
            $PATHDATA/playout_controls.sh -c=recordstart -v=10
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $RECORDSTOP)
            $PATHDATA/playout_controls.sh -c=recordstop
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $RECORDPLAYBACKLATEST)
            $PATHDATA/playout_controls.sh -c=recordplaylatest
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDREADWIFIIP)
            $PATHDATA/playout_controls.sh -c=readwifiipoverspeaker
            bash "$FEEDBACK_SCRIPT" wifi >/dev/null 2>&1 &
            exit 0
            ;;
        $CMDBLUETOOTHTOGGLE)
            $PATHDATA/playout_controls.sh -c=bluetoothtoggle -v=toggle
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        $SYNCSHAREDFULL)
            $PATHDATA/playout_controls.sh -c=sharedsyncfull
            bash "$FEEDBACK_SCRIPT" import >/dev/null 2>&1 &
            exit 0
            ;;
        $SYNCSHAREDONRFIDSCANTOGGLE)
            $PATHDATA/playout_controls.sh -c=sharedsyncchangeonrfidscan -v=toggle
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
            exit 0
            ;;
        *)
            if [ "${SYNCSHAREDENABLED}" == "TRUE" ]; then
                $PATHDATA/../components/synchronisation/sync-shared/sync-shared.sh -c=shortcuts -i="$CARDID"
            fi

            if [ -f "$PATHDATA/../shared/shortcuts/$CARDID" ]; then
                FOLDER=$(cat "$PATHDATA/../shared/shortcuts/$CARDID")
                echo "This ID has been used before." >> "$PATHDATA/../shared/latestID.txt"
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "This ID has been used before." >> "$PATHDATA/../logs/debug.log"
                fi
            else
                echo "$CARDID" > "$PATHDATA/../shared/shortcuts/$CARDID"
                FOLDER=$CARDID
                echo "This ID was used for the first time." >> "$PATHDATA/../shared/latestID.txt"
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "This ID was used for the first time." >> "$PATHDATA/../logs/debug.log"
                fi
            fi

            echo "The shortcut points to audiofolder '$FOLDER'." >> "$PATHDATA/../shared/latestID.txt"
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "The shortcut points to audiofolder '$FOLDER'." >> "$PATHDATA/../logs/debug.log"
            fi
            ;;
    esac
fi

##############################################################
# We should now have a folder name with the audio files.
if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
    echo "# Attempting to play: $AUDIOFOLDERSPATH/$FOLDER" >> "$PATHDATA/../logs/debug.log"
    echo "# Type of play \$VALUE: $VALUE" >> "$PATHDATA/../logs/debug.log"
fi

if [ ! -z "$FOLDER" ]; then

    if [ "${SYNCSHAREDENABLED}" == "TRUE" ]; then
        $PATHDATA/../components/synchronisation/sync-shared/sync-shared.sh -c=audiofolders -d="$FOLDER"
    fi

    if [ -d "${AUDIOFOLDERSPATH}/${FOLDER}" ]; then
        if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
            echo "\$FOLDER not empty and dir exists: ${AUDIOFOLDERSPATH}/${FOLDER}" >> "$PATHDATA/../logs/debug.log"
        fi

        if [ ! -f "${AUDIOFOLDERSPATH}/${FOLDER}/folder.conf" ]; then
            . "$PATHDATA/inc.writeFolderConfig.sh" -c=createDefaultFolderConf -d="${FOLDER}"
        fi

        LASTFOLDER=$(cat "$PATHDATA/../settings/Latest_Folder_Played")
        LASTPLAYLIST=$(cat "$PATHDATA/../settings/Latest_Playlist_Played")

        if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
            echo "  Var \$LASTFOLDER: $LASTFOLDER" >> "$PATHDATA/../logs/debug.log"
            echo "  Var \$LASTPLAYLIST: $LASTPLAYLIST" >> "$PATHDATA/../logs/debug.log"
            echo "Checking 'recursive' list? VAR \$VALUE: $VALUE" >> "$PATHDATA/../logs/debug.log"
        fi

        if [ "$VALUE" == "recursive" ]; then
            PLAYLISTPATH="${PLAYLISTSFOLDERPATH}/${FOLDER//\//\ %\ }-%RCRSV%.m3u"
            PLAYLISTNAME="${FOLDER//\//\ %\ }-%RCRSV%"
            $PATHDATA/playlist_recursive_by_folder.php --folder "${FOLDER}" --list 'recursive' > "${PLAYLISTPATH}"
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "recursive? YES" >> "$PATHDATA/../logs/debug.log"
                echo "$PATHDATA/playlist_recursive_by_folder.php --folder \"${FOLDER}\" --list 'recursive' > \"${PLAYLISTPATH}\"" >> "$PATHDATA/../logs/debug.log"
            fi
        else
            PLAYLISTPATH="${PLAYLISTSFOLDERPATH}/${FOLDER//\//\ %\ }.m3u"
            PLAYLISTNAME="${FOLDER//\//\ %\ }"
            $PATHDATA/playlist_recursive_by_folder.php --folder "${FOLDER}" > "${PLAYLISTPATH}"
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "recursive? NO" >> "$PATHDATA/../logs/debug.log"
                echo "$PATHDATA/playlist_recursive_by_folder.php --folder \"${FOLDER}\" > \"${PLAYLISTPATH}\"" >> "$PATHDATA/../logs/debug.log"
            fi
        fi

        if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
            echo "  Var \$SECONDSWIPE: ${SECONDSWIPE}" >> "$PATHDATA/../logs/debug.log"
            echo "  Var \$PLAYLISTNAME: ${PLAYLISTNAME}" >> "$PATHDATA/../logs/debug.log"
            echo "  Var \$LASTPLAYLIST: ${LASTPLAYLIST}" >> "$PATHDATA/../logs/debug.log"
        fi

        PLAYPLAYLIST=yes

        if [ "$LASTPLAYLIST" == "$PLAYLISTNAME" ]; then
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "  Second Swipe DID happen: \$LASTPLAYLIST == \$PLAYLISTNAME" >> "$PATHDATA/../logs/debug.log"
            fi

            PLLENGTH=$(echo -e "status\nclose" | nc -w 1 localhost 6600 | grep -o -P '(?<=playlistlength: ).*')

            if [ "$PLLENGTH" -eq 0 ]; then
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "  Take second wipe as first after fresh boot" >> "$PATHDATA/../logs/debug.log"
                fi
            elif [ "$SECONDSWIPE" == "PAUSE" -a "$PLLENGTH" -gt 0 ]; then
                PLAYPLAYLIST=no
                STATE=$(echo -e "status\nclose" | nc -w 1 localhost 6600 | grep -o -P '(?<=state: ).*')
                if [ "$STATE" == "play" ]; then
                    if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                        echo "  MPD playing, pausing the player" >> "$PATHDATA/../logs/debug.log"
                    fi
                    sudo "$PATHDATA/playout_controls.sh" -c=playerpause &>/dev/null
                else
                    if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                        echo "MPD not playing, start playing" >> "$PATHDATA/../logs/debug.log"
                    fi
                    sudo "$PATHDATA/playout_controls.sh" -c=playerplay &>/dev/null
                fi
                bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "  Completed: toggle pause/play" >> "$PATHDATA/../logs/debug.log"
                fi
            elif [ "$SECONDSWIPE" == "PLAY" -a "$PLLENGTH" -gt 0 ]; then
                PLAYPLAYLIST=no
                sudo "$PATHDATA/playout_controls.sh" -c=playerplay &>/dev/null
                bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "  Completed: Resume playback" >> "$PATHDATA/../logs/debug.log"
                fi
            elif [ "$SECONDSWIPE" == "NOAUDIOPLAY" ]; then
                PLAYPLAYLIST=no
                currentSong=$(mpc current)
                if [[ -z "$currentSong" ]]; then
                    PLAYPLAYLIST=yes
                fi
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "  Completed: do nothing" >> "$PATHDATA/../logs/debug.log"
                fi
            elif [ "$SECONDSWIPE" == "SKIPNEXT" ]; then
                PLAYPLAYLIST=skipnext
                if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                    echo "  Completed: skip next track" >> "$PATHDATA/../logs/debug.log"
                fi
            fi
        fi

        if [ "$PLAYPLAYLIST" == "yes" ]; then
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "We must play the playlist no matter what: \$PLAYPLAYLIST == yes" >> "$PATHDATA/../logs/debug.log"
                echo "  VAR FOLDER: $FOLDER" >> "$PATHDATA/../logs/debug.log"
                echo "  VAR PLAYLISTPATH: $PLAYLISTPATH" >> "$PATHDATA/../logs/debug.log"
            fi

            $PATHDATA/playout_controls.sh -c=playerstop
            $PATHDATA/playout_controls.sh -c=playlistaddplay -v="${PLAYLISTNAME}" -d="${FOLDER}"

            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "  Command: $PATHDATA/playout_controls.sh -c=playlistaddplay -v=\"${PLAYLISTNAME}\" -d=\"${FOLDER}\"" >> "$PATHDATA/../logs/debug.log"
            fi

            sudo sh -c "echo '${PLAYLISTNAME}' > '$PATHDATA/../settings/Latest_Playlist_Played'"
            sudo chown pi:www-data "$PATHDATA/../settings/Latest_Playlist_Played"
            sudo chmod 777 "$PATHDATA/../settings/Latest_Playlist_Played"

            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
        fi

        if [ "$PLAYPLAYLIST" == "skipnext" ]; then
            if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
                echo "Skip to the next track in the playlist: \$PLAYPLAYLIST == skipnext" >> "$PATHDATA/../logs/debug.log"
                echo "  VAR FOLDER: $FOLDER" >> "$PATHDATA/../logs/debug.log"
                echo "  VAR PLAYLISTPATH: $PLAYLISTPATH" >> "$PATHDATA/../logs/debug.log"
                echo "  Command: $PATHDATA/playout_controls.sh -c=playernext" >> "$PATHDATA/../logs/debug.log"
            fi

            $PATHDATA/playout_controls.sh -c=playernext
            bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
        fi
    else
        if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
            echo "Path not found $AUDIOFOLDERSPATH/$FOLDER" >> "$PATHDATA/../logs/debug.log"
        fi
        bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
        exit 1
    fi
else
    if [ "${DEBUG_rfid_trigger_play_sh}" == "TRUE" ]; then
        echo "var FOLDER empty" >> "$PATHDATA/../logs/debug.log"
    fi
    bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
    exit 1
fi

exit 0
