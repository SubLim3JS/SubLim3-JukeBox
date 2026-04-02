#!/bin/bash

# ============================================================
# SubLim3 USB Auto Import for Phoniebox
# ------------------------------------------------------------
# Imports supported audio files from a USB drive directly into:
#   /home/pi/RPi-Jukebox-RFID/shared/audiofolders
#
# It preserves the USB's internal folder structure, does NOT
# create an outer wrapper folder like sda1/, plays a success
# or error sound, then unmounts/ejects the USB.
#
# Usage:
#   sudo /home/pi/SubLim3-JukeBox/scripts/SubLim3-USB-AutoImport.sh /dev/sda1
# ============================================================

set -uo pipefail

DEVICE="${1:-}"

PI_USER="pi"
PI_GROUP="www-data"
AUDIO_USER="pi"

DEST_ROOT="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"

LOG_DIR="/home/pi/RPi-Jukebox-RFID/shared/logs"
LOG_FILE="$LOG_DIR/usb-auto-import.log"
STATUS_FILE="$LOG_DIR/usb-import-status.json"

LOCK_DIR="/tmp"
LOCK_FILE="$LOCK_DIR/sublim3-usb-auto-import.lock"

SUCCESS_SOUND="/home/pi/RPi-Jukebox-RFID/shared/sounds/success.wav"
ERROR_SOUND="/home/pi/RPi-Jukebox-RFID/shared/sounds/error.wav"

TEMP_MOUNT_BASE="/mnt"
TEMP_MOUNTPOINT=""
SCRIPT_MOUNTED_DEVICE=0

mkdir -p "$LOG_DIR"
mkdir -p "$DEST_ROOT"
mkdir -p "$LOCK_DIR"
touch "$LOG_FILE"
chmod 664 "$LOG_FILE"

clear_status() {
    rm -f "$STATUS_FILE"
}

write_status() {
    local state="$1"
    local message="$2"

    cat > "$STATUS_FILE" <<EOF
{
  "state": "$state",
  "message": "$message",
  "device": "${DEVICE:-}",
  "mountpoint": "${MOUNTPOINT:-}",
  "updated": "$(date '+%Y-%m-%d %H:%M:%S')"
}
EOF

    chown "$PI_USER:$PI_GROUP" "$STATUS_FILE" 2>/dev/null || true
    chmod 664 "$STATUS_FILE" 2>/dev/null || true
}

cleanup() {
    clear_status

    if [[ "$SCRIPT_MOUNTED_DEVICE" -eq 1 && -n "${TEMP_MOUNTPOINT:-}" ]]; then
        if mountpoint -q "$TEMP_MOUNTPOINT"; then
            umount "$TEMP_MOUNTPOINT" >> "$LOG_FILE" 2>&1 || true
        fi
        rmdir "$TEMP_MOUNTPOINT" >> "$LOG_FILE" 2>&1 || true
    fi
}

trap cleanup EXIT

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

play_sound() {
    local sound_file="$1"

    if [[ ! -f "$sound_file" ]]; then
        log "Sound file not found: $sound_file"
        return 1
    fi

    log "Attempting to play sound: $sound_file"

    sudo -u "$AUDIO_USER" /usr/bin/aplay "$sound_file" >> "$LOG_FILE" 2>&1 && return 0

    if command -v paplay >/dev/null 2>&1; then
        sudo -u "$AUDIO_USER" /usr/bin/paplay "$sound_file" >> "$LOG_FILE" 2>&1 && return 0
        /usr/bin/paplay "$sound_file" >> "$LOG_FILE" 2>&1 && return 0
    fi

    /usr/bin/aplay "$sound_file" >> "$LOG_FILE" 2>&1 && return 0

    log "Audio playback failed for: $sound_file"
    return 1
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

count_audio_files() {
    find "$1" -type f \( \
        -iname "*.mp3" -o \
        -iname "*.wav" -o \
        -iname "*.ogg" -o \
        -iname "*.flac" -o \
        -iname "*.m4a" -o \
        -iname "*.aac" -o \
        -iname "*.opus" -o \
        -iname "*.webm" \
    \) | wc -l
}

import_audio() {
    local src="$1"
    local dest="$2"
    local imported=0
    local rel_path
    local target_file
    local target_dir
    local file

    while IFS= read -r -d '' file; do
        rel_path="${file#$src/}"
        target_file="$dest/$rel_path"
        target_dir="$(dirname "$target_file")"

        mkdir -p "$target_dir"

        if cp -p "$file" "$target_file" >> "$LOG_FILE" 2>&1; then
            imported=$((imported + 1))
            log "Imported: $rel_path"
        else
            log "Failed to import: $rel_path"
        fi
    done < <(find "$src" -type f \( \
        -iname "*.mp3" -o \
        -iname "*.wav" -o \
        -iname "*.ogg" -o \
        -iname "*.flac" -o \
        -iname "*.m4a" -o \
        -iname "*.aac" -o \
        -iname "*.opus" -o \
        -iname "*.webm" \
    \) -print0)

    printf '%s\n' "$imported"
}

attempt_mount() {
    local device="$1"
    local fs_type
    local mount_dir
    local mount_opts

    fs_type="$(blkid -o value -s TYPE "$device" 2>/dev/null | head -n 1)"
    mount_dir="$TEMP_MOUNT_BASE/sublim3-$(basename "$device")"

    mkdir -p "$mount_dir"

    log "No existing mountpoint found. Attempting manual mount of $device"
    log "Detected filesystem type: ${fs_type:-unknown}"

    case "$fs_type" in
        vfat|fat|msdos)
            mount_opts="uid=$(id -u "$PI_USER"),gid=$(getent group "$PI_GROUP" | cut -d: -f3),utf8=1,umask=002"
            mount -t vfat -o "$mount_opts" "$device" "$mount_dir" >> "$LOG_FILE" 2>&1
            ;;
        exfat)
            mount_opts="uid=$(id -u "$PI_USER"),gid=$(getent group "$PI_GROUP" | cut -d: -f3),umask=002"
            mount -t exfat -o "$mount_opts" "$device" "$mount_dir" >> "$LOG_FILE" 2>&1
            ;;
        ntfs|ntfs3)
            mount_opts="uid=$(id -u "$PI_USER"),gid=$(getent group "$PI_GROUP" | cut -d: -f3),umask=002"
            mount -t ntfs3 -o "$mount_opts" "$device" "$mount_dir" >> "$LOG_FILE" 2>&1 \
              || mount -t ntfs -o "$mount_opts" "$device" "$mount_dir" >> "$LOG_FILE" 2>&1
            ;;
        ext2|ext3|ext4)
            mount "$device" "$mount_dir" >> "$LOG_FILE" 2>&1
            ;;
        *)
            mount "$device" "$mount_dir" >> "$LOG_FILE" 2>&1
            ;;
    esac

    if mountpoint -q "$mount_dir"; then
        TEMP_MOUNTPOINT="$mount_dir"
        SCRIPT_MOUNTED_DEVICE=1
        echo "$mount_dir"
        return 0
    fi

    rmdir "$mount_dir" >> "$LOG_FILE" 2>&1 || true
    return 1
}

safe_unmount_and_eject() {
    local device="$1"
    local mountpoint_path="$2"
    local base_disk="$3"

    sync
    sleep 1

    if mountpoint -q "$mountpoint_path"; then
        log "Unmounting $mountpoint_path"
        umount "$mountpoint_path" >> "$LOG_FILE" 2>&1 || log "Warning: failed to unmount $mountpoint_path"
        sleep 1
    fi

    if [[ "$SCRIPT_MOUNTED_DEVICE" -eq 1 && -n "${TEMP_MOUNTPOINT:-}" ]]; then
        rmdir "$TEMP_MOUNTPOINT" >> "$LOG_FILE" 2>&1 || true
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

if [[ -z "$DEVICE" ]]; then
    log "No block device argument supplied."
    error_beep
    exit 1
fi

if [[ "$DEVICE" != /dev/* ]]; then
    DEVICE="/dev/$DEVICE"
fi

for _ in {1..10}; do
    if [[ -b "$DEVICE" ]]; then
        break
    fi
    sleep 1
done

if [[ ! -b "$DEVICE" ]]; then
    log "Block device not available: $DEVICE"
    error_beep
    exit 1
fi

log "============================================================"
log "USB auto-import triggered for device: $DEVICE"

MOUNTPOINT=""
for _ in {1..8}; do
    MOUNTPOINT=$(get_mountpoint "$DEVICE")
    if [[ -n "$MOUNTPOINT" && -d "$MOUNTPOINT" ]]; then
        break
    fi
    sleep 1
done

if [[ -z "$MOUNTPOINT" || ! -d "$MOUNTPOINT" ]]; then
    MOUNTPOINT="$(attempt_mount "$DEVICE" || true)"
fi

if [[ -z "$MOUNTPOINT" || ! -d "$MOUNTPOINT" ]]; then
    log "Mountpoint not found and manual mount failed for $DEVICE"
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

BEFORE_COUNT=$(count_audio_files "$DEST_DIR")
log "Existing audio files in destination: $BEFORE_COUNT"

write_status "running" "Copying audio files from USB..."

IMPORTED_COUNT="$(import_audio "$MOUNTPOINT" "$DEST_DIR")"

chown -R "$PI_USER:$PI_GROUP" "$DEST_DIR" >> "$LOG_FILE" 2>&1 || log "Warning: chown failed on $DEST_DIR"
find "$DEST_DIR" -type d -exec chmod 775 {} \; >> "$LOG_FILE" 2>&1
find "$DEST_DIR" -type f -exec chmod 664 {} \; >> "$LOG_FILE" 2>&1

AFTER_COUNT=$(count_audio_files "$DEST_DIR")
log "Audio files now in destination: $AFTER_COUNT"
log "Newly imported files this run: $IMPORTED_COUNT"

if [[ "$IMPORTED_COUNT" =~ ^[0-9]+$ ]] && [[ "$IMPORTED_COUNT" -gt 0 ]]; then
    log "Import completed successfully."
    success_beep || log "Success sound failed to play."
else
    log "No supported audio files found to import."
    error_beep || log "Error sound failed to play."
fi

safe_unmount_and_eject "$DEVICE" "$MOUNTPOINT" "$PARENT_DISK"

log "USB import workflow finished for $DEVICE"
exit 0
