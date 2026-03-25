#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
LOG_FILE="/tmp/sublim3-feedback.log"

log_msg() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

case "$1" in
    update)
        FILE="$SOUND_DIR/update.wav"
        ;;
    success)
        FILE="$SOUND_DIR/success.wav"
        ;;
    error)
        FILE="$SOUND_DIR/error.wav"
        ;;
    wifi)
        FILE="$SOUND_DIR/wifi.wav"
        ;;
    rfid)
        FILE="$SOUND_DIR/rfid.wav"
        ;;
    *)
        log_msg "Invalid sound name: $1"
        exit 1
        ;;
esac

# Check file exists
if [ ! -f "$FILE" ]; then
    log_msg "Missing sound file: $FILE"
    exit 1
fi

# Play via MPD
mpc clear >/dev/null 2>&1
mpc add "$FILE" >/dev/null 2>&1
mpc play >/dev/null 2>&1

log_msg "Played sound: $FILE"

exit 0
