#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
PLAYER="aplay -D plughw:0,0"
EVENT="$1"

case "$EVENT" in
    card|card-scan|scan)
        FILE="$SOUND_DIR/card-scan.wav"
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
    update)
        FILE="$SOUND_DIR/update.wav"
        ;;
    import)
        FILE="$SOUND_DIR/import.wav"
        ;;
    *)
        exit 1
        ;;
esac

[ -f "$FILE" ] || exit 1
$PLAYER "$FILE" >/dev/null 2>&1 &
exit 0
