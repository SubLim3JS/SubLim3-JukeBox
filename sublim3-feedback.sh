#!/bin/bash

SOUND_DIR="/home/pi/RPi-Jukebox-RFID/shared/sounds"
PLAYER=""

find_player() {
    if command -v aplay >/dev/null 2>&1; then
        PLAYER="aplay -q"
        return 0
    fi

    if command -v mpg123 >/dev/null 2>&1; then
        PLAYER="mpg123 -q"
        return 0
    fi

    return 1
}

play_sound() {
    local file="$1"

    if [ ! -f "$file" ]; then
        exit 1
    fi

    if ! find_player; then
        exit 1
    fi

    $PLAYER "$file" >/dev/null 2>&1
}

case "${1:-}" in
    card-scan)
        play_sound "$SOUND_DIR/card-scan.wav"
        ;;
    success)
        play_sound "$SOUND_DIR/success.wav"
        ;;
    error)
        play_sound "$SOUND_DIR/error.wav"
        ;;
    wifi)
        play_sound "$SOUND_DIR/wifi.wav"
        ;;
    update)
        play_sound "$SOUND_DIR/update.wav"
        ;;
    import)
        play_sound "$SOUND_DIR/import.wav"
        ;;
    *)
        exit 1
        ;;
esac
