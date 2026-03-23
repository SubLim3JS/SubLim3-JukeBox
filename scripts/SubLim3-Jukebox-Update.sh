#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"

OVERRIDES_HTDOCS="$SOURCE_DIR/overrides/htdocs"
OVERRIDES_SETTINGS="$SOURCE_DIR/overrides/settings"
OVERRIDES_ICONS="$SOURCE_DIR/overrides/icons"

TARGET_HTDOCS="$TARGET_DIR/htdocs"
TARGET_SETTINGS="$TARGET_DIR/settings"
TARGET_ICONS="$TARGET_HTDOCS/_assets/icons"
SOUNDS_DIR="$TARGET_DIR/shared/sounds"

SCRIPT_DIR="$SOURCE_DIR/scripts"
SCRIPT_NAME="SubLim3-Jukebox-Update.sh"

ERRORS=0

print_header() {
  printf "\n====================================\n"
  printf "====== SubLim3 JukeBox Update ======\n"
  printf "====================================\n\n"
}

print_section() {
  local title="$1"
  printf "\n------------------------------------\n"
  printf "%s\n" "$title"
  printf "------------------------------------\n\n"
}

copy_file() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if cp "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
  else
    echo "[ERROR] Failed to copy $src -> $dst"
    ERRORS=$((ERRORS + 1))
  fi
}

set_permissions() {
  local file="$1"
  local mode="$2"

  if [ -e "$file" ]; then
    if chmod "$mode" "$file"; then
      echo "[OK] chmod $mode $file"
    else
      echo "[WARN] Failed to chmod $mode $file"
      ERRORS=$((ERRORS + 1))
    fi
  fi
}

update_repo() {
  echo "Updating SubLim3-JukeBox repository..."
  echo

  if [ ! -d "$SOURCE_DIR/.git" ]; then
    echo "[ERROR] $SOURCE_DIR is not a git repository."
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  if git -C "$SOURCE_DIR" pull --ff-only origin main; then
    echo
    echo "[OK] Repository updated successfully."
    return 0
  else
    echo
    echo "[ERROR] git pull failed."
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

refresh_this_script() {
  local latest_script="$SCRIPT_DIR/$SCRIPT_NAME"

  if [ -f "$latest_script" ]; then
    chmod +x "$latest_script" 2>/dev/null || true
    echo "[OK] Latest update script is present: $latest_script"
  else
    echo "[WARN] Latest update script not found at: $latest_script"
    ERRORS=$((ERRORS + 1))
  fi
}

install_sox_if_needed() {
  print_section "Checking SoX installation"

  if command -v sox >/dev/null 2>&1; then
    printf " - SoX already installed. - \n\n"
    return 0
  fi

  printf " - SoX not found. Installing... - \n\n"
  if sudo apt update && sudo apt install -y sox; then
    printf " - SoX installed successfully. - \n\n"
    return 0
  else
    printf " - ERROR: Failed to install SoX. - \n\n"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

generate_sound_file() {
  local output_file="$1"
  local label="$2"
  shift 2

  print_section "Generating $label"
  mkdir -p "$SOUNDS_DIR"

  if sox -n -r 44100 -c 1 "$output_file" "$@"; then
    printf " - %s created successfully. - \n\n" "$label"
  else
    printf " - ERROR: Failed to generate %s - \n\n" "$label"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

generate_sounds() {
  print_section "Creating custom system sounds"

  mkdir -p "$SOUNDS_DIR"

  if ! command -v sox >/dev/null 2>&1; then
    printf " - ERROR: SoX is not installed, cannot generate sounds. - \n\n"
    ERRORS=$((ERRORS + 1))
    return 1
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

print_header

update_repo
echo
refresh_this_script
echo

print_section "Deploying override files"

# --- CSS ---
copy_file "$OVERRIDES_HTDOCS/_assets/css/custom-sublim3.css" "$TARGET_HTDOCS/_assets/css/custom-sublim3.css"

# --- ICONS ---
copy_file "$OVERRIDES_ICONS/favicon-16x16.png" "$TARGET_ICONS/favicon-16x16.png"
copy_file "$OVERRIDES_ICONS/favicon-32x32.png" "$TARGET_ICONS/favicon-32x32.png"
copy_file "$OVERRIDES_ICONS/favicon-96x96.png" "$TARGET_ICONS/favicon-96x96.png"

# --- NAVIGATION ---
copy_file "$OVERRIDES_HTDOCS/inc.navigation.php" "$TARGET_HTDOCS/inc.navigation.php"

# --- LANGUAGE & PHP FILES ---
copy_file "$OVERRIDES_HTDOCS/lang/lang-en-UK.php" "$TARGET_HTDOCS/lang/lang-en-UK.php"
copy_file "$OVERRIDES_HTDOCS/systemInfo.php" "$TARGET_HTDOCS/systemInfo.php"
copy_file "$OVERRIDES_HTDOCS/settings.php" "$TARGET_HTDOCS/settings.php"
copy_file "$OVERRIDES_HTDOCS/cardRegisterNew.php" "$TARGET_HTDOCS/cardRegisterNew.php"
copy_file "$OVERRIDES_HTDOCS/manageFilesFolders.php" "$TARGET_HTDOCS/manageFilesFolders.php"
copy_file "$OVERRIDES_HTDOCS/search.php" "$TARGET_HTDOCS/search.php"
copy_file "$OVERRIDES_HTDOCS/cardEdit.php" "$TARGET_HTDOCS/cardEdit.php"
copy_file "$OVERRIDES_HTDOCS/index-lcd.php" "$TARGET_HTDOCS/index-lcd.php"
copy_file "$OVERRIDES_HTDOCS/trackEdit.php" "$TARGET_HTDOCS/trackEdit.php"
copy_file "$OVERRIDES_HTDOCS/userScripts.php" "$TARGET_HTDOCS/userScripts.php"
copy_file "$OVERRIDES_HTDOCS/rfidExportCsv.php" "$TARGET_HTDOCS/rfidExportCsv.php"
copy_file "$OVERRIDES_HTDOCS/func.php" "$TARGET_HTDOCS/func.php"
copy_file "$OVERRIDES_HTDOCS/update.php" "$TARGET_HTDOCS/update.php"
copy_file "$OVERRIDES_HTDOCS/readIP.php" "$TARGET_HTDOCS/readIP.php"

# --- SETTINGS ---
copy_file "$OVERRIDES_SETTINGS/version-number" "$TARGET_SETTINGS/version-number"

# --- GPIO SCRIPT ---
copy_file "$SCRIPT_DIR/gpio-buttons.py" "$TARGET_SETTINGS/gpio-buttons.py"

# --- OPTIONAL CUSTOM SCRIPTS ---
copy_file "$SCRIPT_DIR/SubLim3-USB-AutoImport.sh" "$TARGET_DIR/scripts/SubLim3-USB-AutoImport.sh"
copy_file "$SCRIPT_DIR/sublim3-feedback.sh" "$TARGET_DIR/scripts/sublim3-feedback.sh"

# --- PERMISSIONS ---
set_permissions "$TARGET_SETTINGS/gpio-buttons.py" 755
set_permissions "$TARGET_DIR/scripts/SubLim3-USB-AutoImport.sh" 755
set_permissions "$TARGET_DIR/scripts/sublim3-feedback.sh" 755
set_permissions "$TARGET_HTDOCS/update.php" 644
set_permissions "$TARGET_HTDOCS/readIP.php" 644

# --- SYSTEM SOUNDS ---
install_sox_if_needed
generate_sounds

echo
if [ "$ERRORS" -eq 0 ]; then
  echo "Update complete with no copy errors."
  exit 0
else
  echo "Update finished with $ERRORS error(s)."
  exit 1
fi
