#!/bin/bash
set -euo pipefail

REPO_DIR="/home/pi/SubLim3-JukeBox"
OVERRIDES_DIR="$REPO_DIR/overrides/htdocs"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID/htdocs"
FUNC_FILE="$TARGET_DIR/func.php"
BACKUP_ROOT="/home/pi/SubLim3-JukeBox-Backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"

print_step() {
    echo
    echo "========================================"
    echo " $1"
    echo "========================================"
}

ensure_path() {
    local p="$1"
    if [ ! -e "$p" ]; then
        echo "ERROR: Required path not found: $p"
        exit 1
    fi
}

backup_file_if_exists() {
    local rel="$1"
    local src="$TARGET_DIR/$rel"
    local dst="$BACKUP_DIR/$rel"

    if [ -f "$src" ]; then
        mkdir -p "$(dirname "$dst")"
        cp -a "$src" "$dst"
        echo "Backed up: $rel"
    fi
}

print_step "SubLim3 JukeBox 01 Update"

echo "Repo directory:      $REPO_DIR"
echo "Overrides directory: $OVERRIDES_DIR"
echo "Target directory:    $TARGET_DIR"

ensure_path "$REPO_DIR"
ensure_path "$OVERRIDES_DIR"
ensure_path "$TARGET_DIR"
mkdir -p "$BACKUP_DIR"

print_step "Backing up target files that will be overridden"

if command -v rsync >/dev/null 2>&1; then
    while IFS= read -r rel; do
        [ -z "$rel" ] && continue
        backup_file_if_exists "$rel"
    done < <(cd "$OVERRIDES_DIR" && find . -type f | sed 's#^\./##' | sort)
else
    echo "WARNING: rsync not found. Falling back to cp for deployment later."
    while IFS= read -r rel; do
        [ -z "$rel" ] && continue
        backup_file_if_exists "$rel"
    done < <(cd "$OVERRIDES_DIR" && find . -type f | sed 's#^\./##' | sort)
fi

if [ -f "$FUNC_FILE" ]; then
    backup_file_if_exists "func.php"
fi

print_step "Deploying overrides"

if command -v rsync >/dev/null 2>&1; then
    rsync -av --delete-delay "$OVERRIDES_DIR/" "$TARGET_DIR/"
else
    cp -a "$OVERRIDES_DIR/." "$TARGET_DIR/"
fi

print_step "Ensuring custom-sublim3.css is loaded in func.php"

ensure_path "$FUNC_FILE"

if grep -q 'custom-sublim3.css' "$FUNC_FILE"; then
    echo "func.php already includes custom-sublim3.css"
else
    python3 - "$FUNC_FILE" <<'PY'
import sys
from pathlib import Path

func_path = Path(sys.argv[1])
text = func_path.read_text()
needle = '<link rel=\\"stylesheet\\" href=\\"".$url_absolute."_assets/css/collapsible.css\\">'
insert = needle + '\n        <link rel=\\"stylesheet\\" href=\\"".$url_absolute."_assets/css/custom-sublim3.css\\">'
if needle in text:
    text = text.replace(needle, insert, 1)
else:
    raise SystemExit('Could not find collapsible.css include in func.php')
func_path.write_text(text)
PY
    echo "Added custom-sublim3.css include to func.php"
fi

print_step "Setting ownership and permissions"

sudo chown -R pi:www-data /home/pi/RPi-Jukebox-RFID
find /home/pi/RPi-Jukebox-RFID/htdocs -type d -exec chmod 755 {} \;
find /home/pi/RPi-Jukebox-RFID/htdocs -type f -exec chmod 644 {} \;

print_step "Restarting Apache"
sudo systemctl restart apache2

print_step "Remaining visible 'Phoniebox' matches in htdocs"
grep -Rni "Phoniebox" "$TARGET_DIR" || true

echo
echo "Update complete."
echo "Backup stored at: $BACKUP_DIR"
echo "Hard refresh your browser with Ctrl+F5."
