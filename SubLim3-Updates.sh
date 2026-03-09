#!/bin/bash

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

printf "
.
.
.
.    ___      _    _    _       ____     _      _       ___
.   / __|_  _| |__| |  (_)_ __ |__ /  _ | |_  _| |_____| _ ) _____ __
.   \__ \ || | '_ \ |__| | '  \ |_ \ | || | || | / / -_) _ \/ _ \ \ /
.   |___/\_,_|_.__/____|_|_|_|_|___/  \__/ \_,_|_\_\___|___/\___/_\_\
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

copy_with_backup "$SOURCE_DIR/version-number" \
"$TARGET_DIR/settings/version-number" \
"version-number"

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
