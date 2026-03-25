#!/bin/bash

# ----------------------------------------------------
# SubLim3 - RFID Trigger with Audio Feedback
# ----------------------------------------------------

AUDIOFOLDERSPATH="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPTPATH="/home/pi/RPi-Jukebox-RFID/scripts"
FEEDBACK="$SCRIPTPATH/subli-feedback.sh"

# Get RFID UID
CARDID="$1"

# Exit if empty
if [ -z "$CARDID" ]; then
    exit 0
fi

# ----------------------------------------------------
# 🔊 Play scan sound (NON-BLOCKING)
# ----------------------------------------------------
bash "$FEEDBACK" card-scan >/dev/null 2>&1 &

# ----------------------------------------------------
# Find matching card entry
# ----------------------------------------------------
CARD_FILE="/home/pi/RPi-Jukebox-RFID/settings/rfid_trigger_play.csv"

if [ ! -f "$CARD_FILE" ]; then
    exit 1
fi

# Get line matching card
CARD_LINE=$(grep "^$CARDID," "$CARD_FILE")

if [ -z "$CARD_LINE" ]; then
    # ❌ Unknown card → play error sound
    bash "$FEEDBACK" error >/dev/null 2>&1 &
    exit 1
fi

# Extract fields
IFS=',' read -r RFID FOLDER CMD <<< "$CARD_LINE"

# ----------------------------------------------------
# If folder is defined → play music
# ----------------------------------------------------
if [ "$FOLDER" != "" ] && [ "$FOLDER" != "false" ]; then
    bash "$SCRIPTPATH/playout_controls.sh" -c=playerplay -a="$FOLDER" &
    
    # ✅ Success sound
    bash "$FEEDBACK" success >/dev/null 2>&1 &
    exit 0
fi

# ----------------------------------------------------
# If command is defined → run command
# ----------------------------------------------------
if [ "$CMD" != "" ] && [ "$CMD" != "false" ]; then
    bash "$SCRIPTPATH/playout_controls.sh" -c="$CMD" &
    
    # ✅ Success sound
    bash "$FEEDBACK" success >/dev/null 2>&1 &
    exit 0
fi

# ----------------------------------------------------
# If nothing matched → error
# ----------------------------------------------------
bash "$FEEDBACK" error >/dev/null 2>&1 &
exit 1
