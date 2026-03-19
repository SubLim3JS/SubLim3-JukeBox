#!/bin/bash

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0
SOUNDS_DIR="$TARGET_DIR/shared/sounds"
FEEDBACK_SCRIPT="$TARGET_DIR/scripts/sublim3-feedback.sh"

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

install_sox_if_needed() {
    printf "********************************************************\n"
    printf "*** Checking SoX installation ***\n"
    printf "********************************************************\n\n"

    if command -v sox >/dev/null 2>&1; then
        printf " - SoX already installed. - \n\n\n"
    else
        printf " - SoX not found. Installing... - \n\n"
        if sudo apt update && sudo apt install -y sox; then
            printf " - SoX installed successfully. - \n\n\n"
        else
            printf " - ERROR: Failed to install SoX. - \n\n\n"
            ERRORS=$((ERRORS+1))
        fi
    fi
}

generate_sound_file() {
    local output_file="$1"
    local label="$2"
    shift 2

    printf "********************************************************\n"
    printf "*** Generating %s ***\n" "$label"
    printf "********************************************************\n\n"

    mkdir -p "$SOUNDS_DIR"

    if sox -n -r 44100 -c 1 "$output_file" "$@"; then
        printf " - %s created successfully. - \n\n\n" "$label"
    else
        printf " - ERROR: Failed to generate %s - \n\n\n" "$label"
        ERRORS=$((ERRORS+1))
    fi
}

generate_sounds() {
    printf "********************************************************\n"
    printf "*** Creating custom system sounds ***\n"
    printf "********************************************************\n\n"

    mkdir -p "$SOUNDS_DIR"

    if ! command -v sox >/dev/null 2>&1; then
        printf " - ERROR: SoX is not installed, cannot generate sounds. - \n\n\n"
        ERRORS=$((ERRORS+1))
        return
    fi

    generate_sound_file "$SOUNDS_DIR/card-scan.wav" "card-scan.wav" \
        synth 0.15 sine 880 synth 0.15 sine 1760 \
        fade 0.01 0.15 0.1 reverb 20

    generate_sound_file "$SOUNDS_DIR/success.wav" "success.wav" \
        synth 0.2 sine 523 synth 0.2 sine 659 synth 0.2 sine 784 \
        fade 0.01 0.6 0.2 reverb 30

    generate_sound_file "$SOUNDS_DIR/error.wav" "error.wav" \
        synth 0.4 sine 110 synth 0.4 sine 90 \
        fade 0.01 0.4 0.2 reverb 40

    generate_sound_file "$SOUNDS_DIR/wifi.wav" "wifi.wav" \
        synth 0.1 sine 1200 synth 0.2 sine 900 synth 0.2 sine 1400 \
        fade 0.01 0.5 0.2 reverb 35

    generate_sound_file "$SOUNDS_DIR/update.wav" "update.wav" \
        synth 0.15 sine 400 synth 0.15 sine 600 synth 0.15 sine 800 \
        fade 0.01 0.5 0.2 reverb 45

    generate_sound_file "$SOUNDS_DIR/import.wav" "import.wav" \
        synth 0.25 sine 440 synth 0.25 sine 660 \
        fade 0.01 0.4 0.2 reverb 35
}

set_volume() {
    printf "********************************************************\n"
    printf "*** Setting system volume ***\n"
    printf "********************************************************\n\n"

    if amixer set Master 70% >/dev/null 2>&1; then
        printf " - Master volume set to 70%%. - \n\n\n"
    else
        printf " - ERROR: Failed to set Master volume. - \n\n\n"
        ERRORS=$((ERRORS+1))
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

copy_with_backup "$SOURCE_DIR/sublim3-feedback.sh" \
"$TARGET_DIR/scripts/sublim3-feedback.sh" \
"sublim3-feedback.sh"

copy_with_backup "$SOURCE_DIR/systemInfo.php" \
"$TARGET_DIR/htdocs/systemInfo.php" \
"systemInfo.php"

copy_with_backup "$SOURCE_DIR/update.php" \
"$TARGET_DIR/htdocs/update.php" \
"update.php"

copy_with_backup "$SOURCE_DIR/inc.navigation.php" \
"$TARGET_DIR/htdocs/inc.navigation.php" \
"inc.navigation.php"

copy_with_backup "$SOURCE_DIR/cardRegisterNew.php" \
"$TARGET_DIR/htdocs/cardRegisterNew.php" \
"cardRegisterNew.php"

copy_with_backup "$SOURCE_DIR/reg-toggle" \
"$TARGET_DIR/settings/reg-toggle" \
"reg-toggle"

copy_with_backup "$SOURCE_DIR/rfid_trigger_play.sh" \
"$TARGET_DIR/scripts/rfid_trigger_play.sh" \
"rfid_trigger_play.sh"

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
# Install dependencies and create sounds
# ------------------------------------------------

install_sox_if_needed
generate_sounds
set_volume

printf "***************************************************\n"

if [ "$ERRORS" -eq 0 ]; then
    bash "$FEEDBACK_SCRIPT" success >/dev/null 2>&1 &
    printf "***  - All operations completed successfully. - ***\n"
    printf "***************************************************\n\n"
    exit 0
else
    bash "$FEEDBACK_SCRIPT" error >/dev/null 2>&1 &
    printf "***  - Completed with %d error(s). -            ***\n" "$ERRORS"
    printf "***************************************************\n\n"
    exit 1
fi
