#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
OVERRIDES_HTDOCS="$SOURCE_DIR/overrides/htdocs"
TARGET_HTDOCS="$TARGET_DIR/htdocs"

ERRORS=0

print_header() {
  printf "\n========================================\n"
  printf " SubLim3 JukeBox 02 Update\n"
  printf "========================================\n\n"
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

print_header

echo "Deploying override files..."
echo

copy_file "$OVERRIDES_HTDOCS/_assets/css/custom-sublim3.css" "$TARGET_HTDOCS/_assets/css/custom-sublim3.css"
copy_file "$OVERRIDES_HTDOCS/lang/lang-en-UK.php" "$TARGET_HTDOCS/lang/lang-en-UK.php"
copy_file "$OVERRIDES_HTDOCS/systemInfo.php" "$TARGET_HTDOCS/func.php"
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

echo
if [ "$ERRORS" -eq 0 ]; then
  echo "Update complete with no copy errors."
  exit 0
else
  echo "Update finished with $ERRORS error(s)."
  exit 1
fi
