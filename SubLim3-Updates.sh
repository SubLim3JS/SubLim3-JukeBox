#!/bin/bash

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

SOUNDS_DIR="$TARGET_DIR/shared/sounds"
SCRIPTS_DIR="$TARGET_DIR/scripts"
SETTINGS_DIR="$TARGET_DIR/settings"
SHORTCUTS_DIR="$TARGET_DIR/shared/shortcuts"
AUDIOFOLDERS_DIR="$TARGET_DIR/shared/audiofolders"
FEEDBACK_SCRIPT="$SCRIPTS_DIR/sublim3-feedback.sh"
BOOT_CONFIG="/boot/config.txt"

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

print_section() {
    local title="$1"
    printf "********************************************************\n"
    printf "*** %s ***\n" "$title"
    printf "********************************************************\n\n"
}

copy_with_backup() {
    local source_file="$1"
    local target_file="$2"
    local label="$3"

    print_section "Updating $label"

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
    print_section "Checking SoX installation"

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

    print_section "Generating $label"

    mkdir -p "$SOUNDS_DIR"

    if sox -n -r 44100 -c 1 "$output_file" "$@"; then
        printf " - %s created successfully. - \n\n\n" "$label"
    else
        printf " - ERROR: Failed to generate %s - \n\n\n" "$label"
        ERRORS=$((ERRORS+1))
    fi
}

generate_sounds() {
    print_section "Creating custom system sounds"

    mkdir -p "$SOUNDS_DIR"

    if ! command -v sox >/dev/null 2>&1; then
        printf " - ERROR: SoX is not installed, cannot generate sounds. - \n\n\n"
        ERRORS=$((ERRORS+1))
        return
    fi

    generate_sound_file "$SOUNDS_DIR/card-scan.wav" "card-scan.wav" \
        synth 0.15 sine 880 synth 0.15 sine 1760 \
        fade 0.01 0.15 0.05 reverb 20

    generate_sound_file "$SOUNDS_DIR/success.wav" "success.wav" \
        synth 0.2 sine 523 synth 0.2 sine 659 synth 0.2 sine 784 \
        fade 0.01 0.6 0.05 reverb 30

    generate_sound_file "$SOUNDS_DIR/error.wav" "error.wav" \
        synth 0.4 sine 110 synth 0.4 sine 90 \
        fade 0.01 0.4 0.05 reverb 40

    generate_sound_file "$SOUNDS_DIR/wifi.wav" "wifi.wav" \
        synth 0.1 sine 1200 synth 0.2 sine 900 synth 0.2 sine 1400 \
        fade 0.01 0.5 0.05 reverb 35

    generate_sound_file "$SOUNDS_DIR/update.wav" "update.wav" \
        synth 0.15 sine 400 synth 0.15 sine 600 synth 0.15 sine 800 \
        fade 0.01 0.45 0.05 reverb 45

    generate_sound_file "$SOUNDS_DIR/import.wav" "import.wav" \
        synth 0.25 sine 440 synth 0.25 sine 660 \
        fade 0.01 0.4 0.05 reverb 35
}

fix_permissions() {
    print_section "Fixing script permissions"

    chmod +x "$SCRIPTS_DIR/"*.sh 2>/dev/null
    chmod +x "$SETTINGS_DIR/"*.py 2>/dev/null

    printf " - Script permissions updated. - \n\n\n"
}

fix_ownership() {
    print_section "Fixing ownership and folder permissions"

    sudo chown -R pi:www-data "$TARGET_DIR"
    sudo chmod -R 775 "$SHORTCUTS_DIR" 2>/dev/null
    sudo chmod -R 775 "$AUDIOFOLDERS_DIR" 2>/dev/null
    sudo chmod -R 775 "$SETTINGS_DIR" 2>/dev/null
    sudo chmod -R 775 "$SCRIPTS_DIR" 2>/dev/null

    printf " - Ownership and folder permissions updated. - \n\n\n"
}

ensure_boot_audio_enabled() {
    print_section "Ensuring Raspberry Pi audio is enabled"

    if [ ! -f "$BOOT_CONFIG" ]; then
        printf " - ERROR: Boot config not found at %s - \n\n\n" "$BOOT_CONFIG"
        ERRORS=$((ERRORS+1))
        return
    fi

    if grep -q "^dtparam=audio=on" "$BOOT_CONFIG"; then
        printf " - dtparam=audio=on already present. - \n\n\n"
    elif grep -q "^dtparam=audio=" "$BOOT_CONFIG"; then
        if sudo sed -i 's/^dtparam=audio=.*/dtparam=audio=on/' "$BOOT_CONFIG"; then
            printf " - Updated existing dtparam=audio line to ON. - \n\n\n"
        else
            printf " - ERROR: Failed to update dtparam=audio in boot config. - \n\n\n"
            ERRORS=$((ERRORS+1))
        fi
    else
        if printf "\n# SubLim3 JukeBox audio\ndtparam=audio=on\n" | sudo tee -a "$BOOT_CONFIG" >/dev/null; then
            printf " - Added dtparam=audio=on to boot config. - \n\n\n"
        else
            printf " - ERROR: Failed to add dtparam=audio=on to boot config. - \n\n\n"
            ERRORS=$((ERRORS+1))
        fi
    fi
}

kill_pulseaudio() {
    print_section "Stopping PulseAudio to prevent MPD conflicts"

    sudo killall pulseaudio 2>/dev/null
    printf " - PulseAudio stop command issued. - \n\n\n"
}

repair_alsa_audio() {
    print_section "Repairing ALSA analog audio output"

    if amixer cset numid=3 1 >/dev/null 2>&1; then
        printf " - Forced output to analog headphones. - \n\n"
    else
        printf " - WARNING: Could not force analog output with numid=3. - \n\n"
    fi

    if amixer set Master 100% unmute >/dev/null 2>&1; then
        printf " - Master volume unmuted and set to 100%%. - \n\n"
    else
        printf " - WARNING: Master control not available. - \n\n"
    fi

    if amixer set PCM 100% unmute >/dev/null 2>&1; then
        printf " - PCM volume unmuted and set to 100%%. - \n\n"
    else
        printf " - WARNING: PCM control not available. - \n\n"
    fi

    printf "\n"
}

restart_mpd() {
    print_section "Restarting MPD"

    if sudo systemctl restart mpd; then
        printf " - MPD restarted successfully. - \n\n\n"
    else
        printf " - ERROR: Failed to restart MPD. - \n\n\n"
        ERRORS=$((ERRORS+1))
    fi
}

# ------------------------------------------------
# System/UI files
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

copy_with_backup "$SOURCE_DIR/mpd.conf" \
"/etc/mpd.conf" \
"mpd.conf"

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
# Dependency + sound generation
# ------------------------------------------------

install_sox_if_needed
generate_sounds

# ------------------------------------------------
# Repair / self-heal
# ------------------------------------------------

fix_permissions
fix_ownership
ensure_boot_audio_enabled
kill_pulseaudio
repair_alsa_audio
restart_mpd

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
