#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

RFID_TRIGGER_TARGET="$TARGET_DIR/scripts/rfid_trigger_play.sh"

print_header() {
  printf "\n====================================\n"
  printf "====== SubLim3 JukeBox Update ======\n"
  printf "====================================\n\n"
}

print_section() {
  printf '\n==================================\n'
  printf '%s\n' "$1"
  printf '====================================\n\n'
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
    echo "[OK] RFID feedback hook added."
  else
    echo "[ERROR] Failed to patch rfid_trigger_play.sh"
    ERRORS=$((ERRORS + 1))
  fi
}

fix_permissions() {
  print_section "Fixing permissions"

  chmod +x "$TARGET_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/settings/"*.py 2>/dev/null
  chmod +x "$TARGET_DIR/settings/cardRegisterAccess" 2>/dev/null

  echo "[OK] Script permissions updated"
}

main() {
  print_header

  print_section "Deploying SubLim3 files"

  copy_with_backup "$SOURCE_DIR/overrides/htdocs/_assets/css/custom-sublim3.css"   "$TARGET_DIR/htdocs/_assets/css/custom-sublim3.css" "custom-sublim3.css"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/lang/lang-en-UK.php"   "$TARGET_DIR/htdocs/lang/lang-en-UK.php"        "lang-en-UK.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/func.php"           "$TARGET_DIR/htdocs/func.php"                      "func.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/index.php"          "$TARGET_DIR/htdocs/index.php"                     "index.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/index-lcd.php"      "$TARGET_DIR/htdocs/index-lcd.php"                 "index-lcd.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/readIP.php"         "$TARGET_DIR/htdocs/readIP.php"                    "readIP.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/search.php"         "$TARGET_DIR/htdocs/search.php"                    "search.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/settings.php"       "$TARGET_DIR/htdocs/settings.php"                  "settings.php"
  copy_with_backup "$SOURCE_DIR/sublim3-feedback.sh"                 "$TARGET_DIR/scripts/sublim3-feedback.sh"          "sublim3-feedback.sh"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/systemInfo.php"     "$TARGET_DIR/htdocs/systemInfo.php"                "systemInfo.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/update.php"         "$TARGET_DIR/htdocs/update.php"                    "update.php"
  copy_with_backup "$SOURCE_DIR/overrides/htdocs/inc.navigation.php" "$TARGET_DIR/htdocs/inc.navigation.php"            "inc.navigation.php"
  copy_with_backup "$SOURCE_DIR/overrides/settings/gpio-buttons.py"    "$TARGET_DIR/settings/gpio-buttons.py"           "gpio-buttons.py"
  copy_with_backup "$SOURCE_DIR/overrides/settings/version-number"     "$TARGET_DIR/settings/version-number"            "version-number"
  copy_with_backup "$SOURCE_DIR/overrides/settings/cardRegisterAccess"   "$TARGET_DIR/settings/cardRegisterAccess"      "cardRegisterAccess"

  print_section "Deploying icons"

  copy_with_backup "$SOURCE_DIR/overrides/icons/favicon-16x16.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-16x16.png" "favicon-16x16.png"
  copy_with_backup "$SOURCE_DIR/overrides/icons/favicon-32x32.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-32x32.png" "favicon-32x32.png"
  copy_with_backup "$SOURCE_DIR/overrides/icons/favicon-96x96.png"  "$TARGET_DIR/htdocs/_assets/icons/favicon-96x96.png" "favicon-96x96.png"

  patch_rfid_trigger
  fix_permissions

  printf "\n====================================\n"
  if [ "$ERRORS" -eq 0 ]; then
    echo "[OK] SubLim3 update completed successfully"
    printf "====================================\n\n"
    exit 0
  else
    echo "[WARN] SubLim3 update completed with $ERRORS error(s)"
    printf "====================================\n\n"
    exit 1
  fi
}

main
