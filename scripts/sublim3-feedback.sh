#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
LOG_DIR="/home/pi/RPi-Jukebox-RFID/shared/logs"
LOG_FILE="$LOG_DIR/sublim3-feedback.log"

mkdir -p "$LOG_DIR" 2>/dev/null

log_msg() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE" 2>/dev/null
}

case "${1:-}" in
    update)  FILE="$SOUND_DIR/update.wav" ;;
    success) FILE="$SOUND_DIR/success.wav" ;;
    error)   FILE="$SOUND_DIR/error.wav" ;;
    wifi)    FILE="$SOUND_DIR/wifi.wav" ;;
    rfid)    FILE="$SOUND_DIR/rfid.wav" ;;
    *)
        log_msg "Invalid sound name: ${1:-<empty>}"
        exit 1
        ;;
esac

[ -f "$FILE" ] || exit 1

mpc stop >/dev/null 2>&1
mpc clear >/dev/null 2>&1
mpc add "$FILE" >/dev/null 2>&1
mpc play >/dev/null 2>&1

log_msg "Played sound: $FILE"
exit 0
