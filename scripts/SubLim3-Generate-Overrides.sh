#!/bin/bash
set -euo pipefail

REPO_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID/htdocs"
OVERRIDES_DIR="$REPO_DIR/overrides/htdocs"

FILES=(
    "inc.navigation.php"
    "lang/lang-en-UK.php"
    "cardRegisterNew.php"
    "manageFilesFolders.php"
    "systemInfo.php"
    "cardEdit.php"
    "search.php"
    "index-lcd.php"
    "settings.php"
    "trackEdit.php"
    "userScripts.php"
    "rfidExportCsv.php"
)

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

copy_source_files() {
    local rel
    for rel in "${FILES[@]}"; do
        mkdir -p "$OVERRIDES_DIR/$(dirname "$rel")"
        cp "$TARGET_DIR/$rel" "$OVERRIDES_DIR/$rel"
        echo "Copied: $rel"
    done
}

apply_replacements() {
python3 - "$OVERRIDES_DIR" <<'PY'
import sys
from pathlib import Path

overrides = Path(sys.argv[1])

replacements = {
    'lang/lang-en-UK.php': [
        ("$lang['navBrand'] = \"Phoniebox\";", "$lang['navBrand'] = \"SubLim3 JukeBox\";"),
        ("connect to the phoniebox", "connect to the SubLim3 JukeBox"),
        ("commit your changes to the Phoniebox code", "commit your changes to the SubLim3 JukeBox code"),
        ("hook your Phoniebox into a new Wlan network with dynamic IP", "hook your SubLim3 JukeBox into a new Wlan network with dynamic IP"),
        ("hook your Phoniebox into a new wlan network with dynamic IP", "hook your SubLim3 JukeBox into a new wlan network with dynamic IP"),
    ],
    'cardRegisterNew.php': [
        ('RFID Card | Phoniebox', 'RFID Card | SubLim3 JukeBox'),
    ],
    'manageFilesFolders.php': [
        ('Files and Folders | Phoniebox', 'Files and Folders | SubLim3 JukeBox'),
    ],
    'systemInfo.php': [
        ('System Info | Phoniebox', 'System Info | SubLim3 JukeBox'),
        ('Phoniebox Setup', 'SubLim3 JukeBox Setup'),
    ],
    'cardEdit.php': [
        ('"Phoniebox"', '"SubLim3 JukeBox"'),
    ],
    'search.php': [
        ('Search | Phoniebox', 'Search | SubLim3 JukeBox'),
    ],
    'index-lcd.php': [
        ('"Phoniebox"', '"SubLim3 JukeBox"'),
    ],
    'settings.php': [
        ('Settings | Phoniebox', 'Settings | SubLim3 JukeBox'),
        ('Phoniebox could send you the IP address over email.', 'SubLim3 JukeBox could send you the IP address over email.'),
        ('Useful if you move your Phoniebox into a new Wifi which', 'Useful if you move your SubLim3 JukeBox into a new WiFi network which'),
    ],
    'trackEdit.php': [
        ('mainly German speaking Phoniebox tinkerers.', 'mainly German speaking SubLim3 JukeBox tinkerers.'),
        ('"Phoniebox"', '"SubLim3 JukeBox"'),
    ],
    'userScripts.php': [
        ('"Phoniebox"', '"SubLim3 JukeBox"'),
    ],
    'rfidExportCsv.php': [
        ('PhonieboxRFID-', 'SubLim3-JukeBox-RFID-'),
    ],
}

for rel, pairs in replacements.items():
    path = overrides / rel
    text = path.read_text()
    original = text
    for old, new in pairs:
        text = text.replace(old, new)
    if text != original:
        path.write_text(text)
        print(f'Updated: {rel}')
    else:
        print(f'No changes made: {rel}')
PY
}

print_step "Generating SubLim3 override files"
ensure_path "$TARGET_DIR"
mkdir -p "$OVERRIDES_DIR"
copy_source_files
apply_replacements

echo
echo "Override generation complete."
echo "Review the files under: $OVERRIDES_DIR"
