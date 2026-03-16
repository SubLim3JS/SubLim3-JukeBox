#!/bin/bash

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
AUDIO_TARGET_DIR="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

printf "
.
.
.
.    ___      _    _    _       ____     _      _       ___
.   / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
.   \\__ \\ || | '_ \\ |__| | '  \\ |_ \\ | || | || | / / -_) _ \\/ _ \\ \\ /
.   |___/\\_,_|_.__/____|_|_|_|_|___/  \\__/ \\_,_|_\\_\\___|___/\\___/_\\_\\
.
.
.
"

sleep 1

copy_with_backup() {

    local source_file="$1"
    local target_file="$2"
    local label="$3"

    printf "********************************************************\n"
    printf "*** Updating %s ***\n" "$label"
    printf "********************************************************\n\n"

    mkdir -p "$(dirname "$target_file")"

    if [ -f "$target_file" ]; then
        mv -f "$target_file" "${target_file}${BACKUP_SUFFIX}"
        printf " - Existing %s archived as %s%s - \n\n" "$label" "$label" "$BACKUP_SUFFIX"
    else
        printf " - Target %s did not exist yet. - \n\n" "$label"
    fi

    if [ ! -f "$source_file" ]; then
        printf " - ERROR: Source %s not found at %s - \n\n\n" "$label" "$source_file"
        ERRORS=$((ERRORS+1))
        return
    fi

    if [ ! -s "$source_file" ]; then
        printf " - ERROR: Source %s exists but is empty at %s - \n\n\n" "$label" "$source_file"
        ERRORS=$((ERRORS+1))
        return
    fi

    if cp -f "$source_file" "$target_file"; then
        printf " - Custom %s copied successfully. - \n\n\n" "$label"
    else
        printf " - ERROR: Failed to copy %s - \n\n\n" "$label"
        ERRORS=$((ERRORS+1))
    fi
}

sync_usb_audio_if_present() {

    local usb_found=0
    local copied_any=0

    printf "********************************************************\n"
    printf "*** Checking USB drive for audio files ***\n"
    printf "********************************************************\n\n"

    mkdir -p "$AUDIO_TARGET_DIR"

    for mount_root in /media/pi /mnt; do
        [ -d "$mount_root" ] || continue

        for usb_path in "$mount_root"/*; do
            [ -e "$usb_path" ] || continue
            [ -d "$usb_path" ] || continue

            usb_found=1
            printf " - USB drive detected at: %s - \n\n" "$usb_path"

            #
            # Option 1:
            # If the USB has an audiofolders directory, copy from there
            #
            if [ -d "$usb_path/audiofolders" ]; then
                printf " - Found audiofolders directory on USB. - \n\n"

                if rsync -rlD --ignore-existing --info=name0 \
                    "$usb_path/audiofolders/" \
                    "$AUDIO_TARGET_DIR/"; then
                    printf " - Missing audio files/folders copied from USB. Existing files ignored. - \n\n"
                    copied_any=1
                else
                    printf " - ERROR: Failed syncing from %s/audiofolders - \n\n" "$usb_path"
                    ERRORS=$((ERRORS+1))
                fi
            fi

            #
            # Option 2:
            # If the USB itself contains folders/files directly, copy them too
            #
            if [ ! -d "$usb_path/audiofolders" ]; then
                if find "$usb_path" -mindepth 1 -maxdepth 1 | grep -q .; then
                    printf " - No audiofolders directory found. Trying USB root contents. - \n\n"

                    if rsync -rlD --ignore-existing --info=name0 \
                        "$usb_path/" \
                        "$AUDIO_TARGET_DIR/"; then
                        printf " - Missing audio files/folders copied from USB root. Existing files ignored. - \n\n"
                        copied_any=1
                    else
                        printf " - ERROR: Failed syncing from USB root at %s - \n\n" "$usb_path"
                        ERRORS=$((ERRORS+1))
                    fi
                fi
            fi
        done
    done

    if [ "$usb_found" -eq 0 ]; then
        printf " - No USB drive detected. Skipping USB audio import. - \n\n\n"
    elif [ "$copied_any" -eq 0 ]; then
        printf " - USB drive found, but no new audio files were needed. - \n\n\n"
    else
        printf " - USB audio import completed. - \n\n\n"
    fi
}

# ------------------------------------------------
# System/UI files only (audio excluded intentionally)
# ------------------------------------------------

copy_with_backup "$SOURCE_DIR/func.php" \
"$TARGET_DIR/htdocs/func.php" \
"func.php"

copy_with_backup "$SOURCE_DIR/custom-green.css" \
"$TARGET_DIR/htdocs/_assets/css/custom-green.css" \
"custom-green.css"

copy_with_backup "$SOURCE_DIR/circle.css" \
"$TARGET_DIR/htdocs/_assets/css/circle.css" \
"circle.css"

copy_with_backup "$SOURCE_DIR/index.php" \
"$TARGET_DIR/htdocs/index.php" \
"index.php"

copy_with_backup "$SOURCE_DIR/lang-en-UK.php" \
"$TARGET_DIR/htdocs/lang/lang-en-UK.php" \
"lang-en-UK.php"

copy_with_backup "$SOURCE_DIR/readIP.php" \
"$TARGET_DIR/htdocs/readIP.php" \
"readIP.php"

copy_with_backup "$SOURCE_DIR/search.php" \
"$TARGET_DIR/htdocs/search.php" \
"search.php"

copy_with_backup "$SOURCE_DIR/settings.php" \
"$TARGET_DIR/htdocs/settings.php" \
"settings.php"

copy_with_backup "$SOURCE_DIR/systemInfo.php" \
"$TARGET_DIR/htdocs/systemInfo.php" \
"systemInfo.php"

copy_with_backup "$SOURCE_DIR/update.php" \
"$TARGET_DIR/htdocs/update.php" \
"update.php"

copy_with_backup "$SOURCE_DIR/gpio-buttons.py" \
"$TARGET_DIR/settings/gpio-buttons.py" \
"gpio-buttons.py"

copy_with_backup "$SOURCE_DIR/version-number" \
"$TARGET_DIR/settings/version-number" \
"version-number"

# ------------------------------------------------
# Icons
# ------------------------------------------------

copy_with_backup "$SOURCE_DIR/Lidarr-Icon.jpg" \
"$TARGET_DIR/htdocs/_assets/icons/Lidarr-Icon.jpg" \
"Lidarr-Icon.jpg"

copy_with_backup "$SOURCE_DIR/favicon-16x16.png" \
"$TARGET_DIR/htdocs/_assets/icons/favicon-16x16.png" \
"favicon-16x16.png"

copy_with_backup "$SOURCE_DIR/favicon-32x32.png" \
"$TARGET_DIR/htdocs/_assets/icons/favicon-32x32.png" \
"favicon-32x32.png"

copy_with_backup "$SOURCE_DIR/favicon-96x96.png" \
"$TARGET_DIR/htdocs/_assets/icons/favicon-96x96.png" \
"favicon-96x96.png"

# ------------------------------------------------
# Optional USB audio import
# ------------------------------------------------

sync_usb_audio_if_present

printf "***************************************************\n"

if [ "$ERRORS" -eq 0 ]; then
    printf "***  - All operations completed successfully. - ***\n"
    printf "***************************************************\n\n"
    exit 0
else
    printf "***  - Completed with %d error(s). -            ***\n" "$ERRORS"
    printf "***************************************************\n\n"
    exit 1
fi
