#!/bin/bash

# Path setup
BASE_DIR="/home/pi/RPi-Jukebox-RFID"
FEEDBACK_SCRIPT="$BASE_DIR/scripts/sublim3-feedback.sh"

CARDID="$1"

# ------------------------------------------------
# Validate input
# ------------------------------------------------
if [ -z "$CARDID" ]; then
    echo "No Card ID provided"
    bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
    exit 1
fi

echo "Card detected: $CARDID"

# ------------------------------------------------
# Play immediate scan sound
# ------------------------------------------------
bash "$FEEDBACK_SCRIPT" card >/dev/null 2>&1 &

# ------------------------------------------------
# Find assigned folder/command for this card
# ------------------------------------------------
CARD_FILE="$BASE_DIR/shared/cards/$CARDID"

if [ ! -f "$CARD_FILE" ]; then
    echo "No assignment found for card: $CARDID"
    bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
    exit 1
fi

TARGET=$(cat "$CARD_FILE")

echo "Target: $TARGET"

# ------------------------------------------------
# Execute action (Phoniebox typical behavior)
# ------------------------------------------------
if [ -d "$BASE_DIR/shared/audiofolders/$TARGET" ]; then
    echo "Playing folder: $TARGET"

    # Trigger playback (Phoniebox standard)
    mpc clear
    mpc add "$TARGET"
    mpc play

    bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
    exit 0

elif [[ "$TARGET" == "CMD:"* ]]; then
    CMD="${TARGET#CMD:}"
    echo "Executing command: $CMD"

    eval "$CMD"

    bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
    exit 0

else
    echo "Invalid target for card: $CARDID"
    bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
    exit 1
fi
