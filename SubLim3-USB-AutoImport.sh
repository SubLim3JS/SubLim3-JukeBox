#!/bin/bash

# ============================================================
# SubLim3 USB Auto Import for Phoniebox
# ------------------------------------------------------------
# Auto-detects a mounted USB partition, imports supported audio
# files into the Phoniebox audiofolders root, plays a completion
# sound, then unmounts/ejects the USB.
#
# Usage:
#   sudo /home/pi/SubLim3-JukeBox/SubLim3-USB-AutoImport.sh /dev/sda1
# ============================================================

set -u

DEVICE="${1:-}"
LOG_FILE="/home/pi/SubLim3-JukeBox/logs/usb-auto-import.log"
LOCK_FILE="/tmp/sublim3-usb-auto-import.lock"
DEST_ROOT="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"

PI_USER="pi"
PI_GROUP="www-data"
AUDIO_USER="pi"

# Existing sound files
SUCCESS_SOUND="/home/pi/RPi-Jukebox-RFID/shared/sounds/success.wav"
ERROR_SOUND="/home/pi/RPi-Jukebox-RFID/shared/sounds/error.wav"

SUPPORTED_EXTENSIONS="mp3|wav|ogg|flac|m4a|aac|opus|webm"

mkdir -p "$(dirname "$LOG_FILE")"
mkdir -p "$DEST_ROOT"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

play_sound() {
    local sound_file="$1"

    if [[ ! -f "$sound_file" ]]; then
        log "Sound file not found: $sound_file"
        return 1
    fi

    sudo -u "$AUDIO_USER" /usr/bin/aplay "$sound_file" >> "$LOG_FILE" 2>&1 \
    || sudo -u "$AUDIO_USER" /usr/bin/paplay "$sound_file" >> "$LOG_FILE" 2>&1 \
    || /usr/bin/aplay "$sound_file" >> "$LOG_FILE" 2>&1 \
    || /usr/bin/paplay "$sound_file" >> "$LOG_FILE" 2>&1 \
    || {
        log "Audio playback failed for: $sound_file"
        return 1
    }

    return 0
}

success_beep() {
    play_sound "$SUCCESS_SOUND"
}

error_beep() {
    play_sound "$ERROR_SOUND"
}

sanitize_name() {
    echo "$1" | sed 's/[^A-Za-z0-9._-]/_/g'
}

get_mountpoint() {
    lsblk -nrpo MOUNTPOINT "$1" 2>/dev/null | head -n 1
}

get_label() {
    lsblk -nrpo LABEL "$1" 2>/dev/null | head -n 1
}

get_parent_disk() {
    local pkname
    pkname=$(lsblk -nro PKNAME "$1" 2>/dev/null | head -n 1)
    if [[ -n "$pkname" ]]; then
        echo "/dev/$pkname"
    else
        echo "$1"
    fi
}

import_audio() {
    local src="$1"
    local dest="$2"

    mkdir -p "$dest"

    rsync -av --prune-empty-dirs \
        --include='*/' \
        --include="*.mp3" \
        --include="*.MP3" \
        --include="*.wav" \
        --include="*.WAV" \
        --include="*.ogg" \
        --include="*.OGG" \
        --include="*.flac" \
        --include="*.FLAC" \
        --include="*.m4a" \
        --include="*.M4A" \
        --include="*.aac" \
        --include="*.AAC" \
        --include="*.opus" \
        --include="*.OPUS" \
        --include="*.webm" \
        --include="*.WEBM" \
        --exclude='*' \
        "$src"/ "$dest"/ >> "$LOG_FILE" 2>&1
}

count_audio_files() {
    find "$1" -type f | grep -Ei "\.($SUPPORTED_EXTENSIONS)$" | wc -l
}

safe_unmount_and_eject() {
    local device="$1"
    local mountpoint="$2"
    local base_disk="$3"

    sync
    sleep 1

    if mountpoint -q "$mountpoint"; then
        log "Unmounting $mountpoint"
        umount "$mountpoint" >> "$LOG_FILE" 2>&1 || log "Warning: failed to unmount $mountpoint"
        sleep 1
    fi

    log "Powering off/ejecting $base_disk"
    udisksctl power-off -b "$base_disk" >> "$LOG_FILE" 2>&1 \
        || eject "$base_disk" >> "$LOG_FILE" 2>&1 \
        || log "Warning: failed to power off/eject $base_disk"
}

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    log "Another USB import is already running. Exiting."
    exit 0
fi

if [[ -z "$DEVICE" || ! -b "$DEVICE" ]]; then
    log "No valid block device supplied."
    error_beep
    exit 1
fi

log "============================================================"
log "USB auto-import triggered for device: $DEVICE"

MOUNTPOINT=""
for _ in {1..30}; do
    MOUNTPOINT=$(get_mountpoint "$DEVICE")
    if [[ -n "$MOUNTPOINT" && -d "$MOUNTPOINT" ]]; then
        break
    fi
    sleep 1
done

if [[ -z "$MOUNTPOINT" || ! -d "$MOUNTPOINT" ]]; then
    log "Mountpoint not found for $DEVICE"
    error_beep
    exit 1
fi

LABEL=$(get_label "$DEVICE")
if [[ -z "$LABEL" ]]; then
    LABEL="$(basename "$DEVICE")"
fi
LABEL="$(sanitize_name "$LABEL")"

DEST_DIR="$DEST_ROOT"
PARENT_DISK=$(get_parent_disk "$DEVICE")

log "Mountpoint: $MOUNTPOINT"
log "Label: $LABEL"
log "Destination: $DEST_DIR"
log "Parent disk: $PARENT_DISK"

mkdir -p "$DEST_DIR"

BEFORE_COUNT=$(count_audio_files "$DEST_DIR")
log "Existing audio files in destination: $BEFORE_COUNT"

import_audio "$MOUNTPOINT" "$DEST_DIR"

chown -R "$PI_USER:$PI_GROUP" "$DEST_DIR" >> "$LOG_FILE" 2>&1 || log "Warning: chown failed on $DEST_DIR"
find "$DEST_DIR" -type d -exec chmod 775 {} \; >> "$LOG_FILE" 2>&1
find "$DEST_DIR" -type f -exec chmod 664 {} \; >> "$LOG_FILE" 2>&1

AFTER_COUNT=$(count_audio_files "$DEST_DIR")
IMPORTED_COUNT=$((AFTER_COUNT - BEFORE_COUNT))

log "Audio files now in destination: $AFTER_COUNT"
log "Newly imported files this run: $IMPORTED_COUNT"

if [[ "$IMPORTED_COUNT" -gt 0 ]]; then
    log "Import completed successfully."
    success_beep || log "Success sound failed to play."
else
    log "No supported audio files found to import."
    error_beep || log "Error sound failed to play."
fi

safe_unmount_and_eject "$DEVICE" "$MOUNTPOINT" "$PARENT_DISK"

log "USB import workflow finished for $DEVICE"
exit 0
