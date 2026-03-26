#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

SOUNDS_DIR="$TARGET_DIR/shared/sounds"
FEEDBACK_SCRIPT_TARGET="$TARGET_DIR/scripts/sublim3-feedback.sh"
RFID_TRIGGER_TARGET="$TARGET_DIR/scripts/rfid_trigger_play.sh"

print_header() {
  printf "\n====================================\n"
  printf "====== SubLim3 JukeBox Update ======\n"
  printf "====================================\n\n"
}

print_section() {
  printf -- "\n------------------------------------\n"
  printf "%s\n" "$1"
  printf -- "------------------------------------\n\n"
}

copy_with_backup() {
  local src="$1"
  local dst="$2"
  local label="$3"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    cp -f "$dst" "${dst}${BACKUP_SUFFIX}" || {
      echo "[ERROR] Failed to back up $label"
      ERRORS=$((ERRORS + 1))
      return 1
    }
  fi

  if cp -f "$src" "$dst"; then
    echo "[OK] $label -> $dst"
  else
    echo "[ERROR] Failed to copy $label"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

ensure_sox() {
  print_section "Checking SoX"

  if command -v sox >/dev/null 2>&1; then
    echo "[OK] SoX already installed"
    return 0
  fi

  echo "[INFO] Installing SoX..."
  if sudo apt update && sudo apt install -y sox; then
    echo "[OK] SoX installed"
  else
    echo "[ERROR] Failed to install SoX"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

ensure_sound_dir() {
  print_section "Preparing sound directory"

  mkdir -p "$SOUNDS_DIR" || {
    echo "[ERROR] Could not create $SOUNDS_DIR"
    ERRORS=$((ERRORS + 1))
    return 1
  }

  echo "[OK] Sound directory ready: $SOUNDS_DIR"
}

generate_sound() {
  local file="$1"
  shift

  if sox -n "$file" "$@" >/dev/null 2>&1; then
    echo "[OK] Generated $(basename "$file")"
  else
    echo "[ERROR] Failed to generate $(basename "$file")"
    ERRORS=$((ERRORS + 1))
  fi
}

generate_sounds() {
  print_section "Generating SubLim3 feedback sounds"

  ensure_sound_dir

  generate_sound "$SOUNDS_DIR/update.wav"  -r 22050 -b 16 -c 1 synth 0.20 sine 750 vol 0.9
  generate_sound "$SOUNDS_DIR/success.wav" -r 22050 -b 16 -c 1 synth 0.35 sine 600:1200 vol 0.9
  generate_sound "$SOUNDS_DIR/error.wav"   -r 22050 -b 16 -c 1 synth 0.40 sine 220 vol 0.9
  generate_sound "$SOUNDS_DIR/wifi.wav"    -r 22050 -b 16 -c 1 synth 0.18 sine 880 vol 0.9
  generate_sound "$SOUNDS_DIR/rfid.wav"    -r 22050 -b 16 -c 1 synth 0.12 sine 1000 vol 0.9

  cp -f "$SOUNDS_DIR/rfid.wav" "$SOUNDS_DIR/card-scan.wav" 2>/dev/null && \
    echo "[OK] Generated compatibility alias: card-scan.wav"
}

write_feedback_script() {
  print_section "Writing sublim3-feedback.sh"

  cat > "$FEEDBACK_SCRIPT_TARGET" <<'EOF'
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
EOF

  if chmod 755 "$FEEDBACK_SCRIPT_TARGET"; then
    echo "[OK] Installed $FEEDBACK_SCRIPT_TARGET"
  else
    echo "[ERROR] Failed to set permissions on $FEEDBACK_SCRIPT_TARGET"
    ERRORS=$((ERRORS + 1))
  fi
}

patch_rfid_trigger() {
  print_section "Patching rfid_trigger_play.sh"

  if [ ! -f "$RFID_TRIGGER_TARGET" ]; then
    echo "[ERROR] Missing $RFID_TRIGGER_TARGET"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  cp -f "$RFID_TRIGGER_TARGET" "${RFID_TRIGGER_TARGET}${BACKUP_SUFFIX}" || {
    echo "[ERROR] Failed to back up rfid_trigger_play.sh"
    ERRORS=$((ERRORS + 1))
    return 1
  }

  if grep -q 'sublim3-feedback.sh' "$RFID_TRIGGER_TARGET"; then
    echo "[OK] RFID feedback hook already present"
    return 0
  fi

  python3 - <<'PY'
from pathlib import Path

path = Path("/home/pi/RPi-Jukebox-RFID/scripts/rfid_trigger_play.sh")
text = path.read_text()

old = 'if [ "$CARDID" ]; then\n'
new = 'if [ "$CARDID" ]; then\n    bash "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh" rfid >/dev/null 2>&1 &\n'

if old in text:
    text = text.replace(old, new, 1)
    path.write_text(text)
else:
    raise SystemExit("Could not find CARDID block to patch.")
PY

  if [ $? -eq 0 ]; then
    echo "[OK] RFID feedback hook added"
  else
    echo "[ERROR] Failed to patch rfid_trigger_play.sh"
    ERRORS=$((ERRORS + 1))
  fi
}

set_analog_audio() {
  print_section "Setting analog audio output"

  if command -v amixer >/dev/null 2>&1; then
    if amixer cset numid=3 1 >/dev/null 2>&1; then
      echo "[OK] Analog audio output selected"
    else
      echo "[WARN] Could not set analog audio output automatically"
    fi
  else
    echo "[WARN] amixer not available"
  fi
}

fix_permissions() {
  print_section "Fixing permissions"

  chmod +x "$TARGET_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/settings/"*.py 2>/dev/null
  chmod +x "$TARGET_DIR/settings/reg-toggle" 2>/dev/null

  echo "[OK] Script permissions updated"
}

main() {
  print_header

  print_section "Deploying SubLim3 files"

  copy_with_backup "$SOURCE_DIR/func.php"            "$TARGET_DIR/htdocs/func.php"                      "func.php"
  copy_with_backup "$SOURCE_DIR/custom-green.css"   "$TARGET_DIR/htdocs/_assets/css/custom-green.css" "custom-green.css"
  copy_with_backup "$SOURCE_DIR/circle.css"         "$TARGET_DIR/htdocs/_assets/css/circle.css"       "circle.css"
  copy_with_backup "$SOURCE_DIR/index.php"          "$TARGET_DIR/htdocs/index.php"                     "index.php"
  copy_with_backup "$SOURCE_DIR/lang-en-UK.php"     "$TARGET_DIR/htdocs/lang/lang-en-UK.php"          "lang-en-UK.php"
  copy_with_backup "$SOURCE_DIR/readIP.php"         "$TARGET_DIR/htdocs/readIP.php"                    "readIP.php"
  copy_with_backup "$SOURCE_DIR/search.php"         "$TARGET_DIR/htdocs/search.php"                    "search.php"
  copy_with_backup "$SOURCE_DIR/settings.php"       "$TARGET_DIR/htdocs/settings.php"                  "settings.php"
  copy_with_backup "$SOURCE_DIR/systemInfo.php"     "$TARGET_DIR/htdocs/systemInfo.php"                "systemInfo.php"
  copy_with_backup "$SOURCE_DIR/update.php"         "$TARGET_DIR/htdocs/update.php"                    "update.php"
  copy_with_backup "$SOURCE_DIR/inc.navigation.php" "$TARGET_DIR/htdocs/inc.navigation.php"            "inc.navigation.php"
  copy_with_backup "$SOURCE_DIR/gpio-buttons.py"    "$TARGET_DIR/settings/gpio-buttons.py"             "gpio-buttons.py"
  copy_with_backup "$SOURCE_DIR/version-number"     "$TARGET_DIR/settings/version-number"              "version-number"
  copy_with_backup "$SOURCE_DIR/reg-toggle"         "$TARGET_DIR/settings/reg-toggle"                  "reg-toggle"

  print_section "Deploying icons"

  copy_with_backup "$SOURCE_DIR/Lidarr-Icon.jpg"    "$TARGET_DIR/htdocs/_assets/icons/Lidarr-Icon.jpg"   "Lidarr-Icon.jpg"
  copy_with_backup "$SOURCE_DIR/favicon-16x16.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-16x16.png" "favicon-16x16.png"
  copy_with_backup "$SOURCE_DIR/favicon-32x32.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-32x32.png" "favicon-32x32.png"
  copy_with_backup "$SOURCE_DIR/favicon-96x96.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-96x96.png" "favicon-96x96.png"

  ensure_sox
  generate_sounds
  write_feedback_script
  patch_rfid_trigger
  set_analog_audio
  fix_permissions

  printf "\n====================================\n"
  if [ "$ERRORS" -eq 0 ]; then
    echo "[OK] SubLim3 update completed successfully"
    [ -x "$FEEDBACK_SCRIPT_TARGET" ] && bash "$FEEDBACK_SCRIPT_TARGET" success >/dev/null 2>&1 &
    printf "====================================\n\n"
    exit 0
  else
    echo "[WARN] SubLim3 update completed with $ERRORS error(s)"
    [ -x "$FEEDBACK_SCRIPT_TARGET" ] && bash "$FEEDBACK_SCRIPT_TARGET" error >/dev/null 2>&1 &
    printf "====================================\n\n"
    exit 1
  fi
}

main
