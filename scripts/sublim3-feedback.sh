#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
PLAYER="aplay"

case "$1" in
  update)  FILE="$SOUND_DIR/update.wav" ;;
  success) FILE="$SOUND_DIR/success.wav" ;;
  error)   FILE="$SOUND_DIR/error.wav" ;;
  wifi)    FILE="$SOUND_DIR/wifi.wav" ;;
  rfid)    FILE="$SOUND_DIR/rfid.wav" ;;
  *) exit 1 ;;
esac

if [ -f "$FILE" ]; then
  $PLAYER -q "$FILE" >/dev/null 2>&1 &
fi

exit 0
