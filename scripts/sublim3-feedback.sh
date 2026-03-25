#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"

case "${1:-}" in
    update)  FILE="$SOUND_DIR/update.wav" ;;
    success) FILE="$SOUND_DIR/success.wav" ;;
    error)   FILE="$SOUND_DIR/error.wav" ;;
    wifi)    FILE="$SOUND_DIR/wifi.wav" ;;
    rfid)    FILE="$SOUND_DIR/rfid.wav" ;;
    *) exit 1 ;;
esac

[ -f "$FILE" ] || exit 1

mpc stop >/dev/null 2>&1
mpc clear >/dev/null 2>&1
mpc add "$FILE" >/dev/null 2>&1
mpc play >/dev/null 2>&1

exit 0
