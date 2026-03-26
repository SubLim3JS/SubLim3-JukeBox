#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
PLAYER="/usr/bin/aplay"
DEVICE="plughw:CARD=Headphones,DEV=0"
EVENT="$1"

case "$EVENT" in
  update)    FILE="$SOUND_DIR/update.wav" ;;
  success)   FILE="$SOUND_DIR/success.wav" ;;
  error)     FILE="$SOUND_DIR/error.wav" ;;
  wifi)      FILE="$SOUND_DIR/wifi.wav" ;;
  rfid)      FILE="$SOUND_DIR/rfid.wav" ;;
  card|card-scan|scan) FILE="$SOUND_DIR/rfid.wav" ;;
  import)    FILE="$SOUND_DIR/success.wav" ;;
  *) exit 1 ;;
esac

[ -f "$FILE" ] || exit 1
"$PLAYER" -D "$DEVICE" "$FILE" >/dev/null 2>&1 &
exit 0
