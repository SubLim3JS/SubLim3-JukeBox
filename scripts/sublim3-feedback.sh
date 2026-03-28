#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
PLAYER="aplay -D plughw:CARD=Headphones,DEV=0 -q"

case "$1" in
  rfid|card|card-scan)
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
    exit 0
    ;;
esac

if [ -f "$FILE" ]; then
  $PLAYER "$FILE" >/dev/null 2>&1
fi

exit 0
