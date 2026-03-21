#!/bin/bash

set -e

REPO_DIR="/home/pi/SubLim3-JukeBox"
OVERRIDES_DIR="$REPO_DIR/overrides/htdocs"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID/htdocs"
BACKUP_ROOT="$REPO_DIR/backups"
TIMESTAMP="$(date +%Y-%m-%d_%H-%M-%S)"
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"

print_section() {
    echo
    echo "========================================"
    echo " $1"
    echo "========================================"
}

require_path() {
    if [ ! -e "$1" ]; then
        echo "ERROR: Required path not found: $1"
        exit 1
    fi
}

restart_web_server() {
    print_section "Restarting web server"

    if systemctl list-unit-files | grep -q '^apache2.service'; then
        sudo systemctl restart apache2
        echo "Restarted apache2"
    elif systemctl list-unit-files | grep -q '^lighttpd.service'; then
        sudo systemctl restart lighttpd
        echo "Restarted lighttpd"
    elif systemctl list-unit-files | grep -q '^nginx.service'; then
        sudo systemctl restart nginx
        echo "Restarted nginx"
    else
        echo "WARNING: No supported web server service found."
    fi
}

print_section "SubLim3 JukeBox 01 Update"
echo "Repo directory:      $REPO_DIR"
echo "Overrides directory: $OVERRIDES_DIR"
echo "Target directory:    $TARGET_DIR"

require_path "$REPO_DIR"
require_path "$OVERRIDES_DIR"
require_path "$TARGET_DIR"

mkdir -p "$BACKUP_DIR"

print_section "Backing up target files that will be overridden"

cd "$OVERRIDES_DIR"
find . -type f | while read -r relpath; do
    CLEAN_RELPATH="${relpath#./}"
    SRC_FILE="$OVERRIDES_DIR/$CLEAN_RELPATH"
    DST_FILE="$TARGET_DIR/$CLEAN_RELPATH"

    if [ -f "$DST_FILE" ]; then
        mkdir -p "$BACKUP_DIR/$(dirname "$CLEAN_RELPATH")"
        cp -a "$DST_FILE" "$BACKUP_DIR/$CLEAN_RELPATH"
        echo "Backed up: $CLEAN_RELPATH"
    fi
done

FUNC_FILE="$TARGET_DIR/func.php"
if [ -f "$FUNC_FILE" ]; then
    mkdir -p "$BACKUP_DIR"
    cp -a "$FUNC_FILE" "$BACKUP_DIR/func.php"
    echo "Backed up: func.php"
fi

print_section "Deploying overrides"

cd "$OVERRIDES_DIR"
find . -type f | while read -r relpath; do
    CLEAN_RELPATH="${relpath#./}"
    SRC_FILE="$OVERRIDES_DIR/$CLEAN_RELPATH"
    DST_FILE="$TARGET_DIR/$CLEAN_RELPATH"

    mkdir -p "$(dirname "$DST_FILE")"
    cp -a "$SRC_FILE" "$DST_FILE"
    echo "Installed: $CLEAN_RELPATH"
done

print_section "Ensuring custom-sublim3.css is loaded in func.php"

require_path "$FUNC_FILE"

if grep -Fq 'custom-sublim3.css' "$FUNC_FILE"; then
    echo "custom-sublim3.css is already referenced in func.php"
else
    sed -i '/collapsible\.css/a\        <link rel=\\"stylesheet\\" href=\\"".$url_absolute."_assets/css/custom-sublim3.css\\">' "$FUNC_FILE"
    echo "Added custom-sublim3.css include to func.php"
fi

print_section "Protecting local machine-specific files"

if [ ! -f "$TARGET_DIR/config.php" ] && [ -f "$TARGET_DIR/config.php.sample" ]; then
    cp "$TARGET_DIR/config.php.sample" "$TARGET_DIR/config.php"
    echo "Recreated missing config.php from config.php.sample"
fi

print_section "Setting ownership and permissions"

sudo chown -R pi:www-data /home/pi/RPi-Jukebox-RFID
sudo chmod -R 775 "$TARGET_DIR"

restart_web_server

print_section "Update complete"
echo "Backup saved to: $BACKUP_DIR"
echo "Hard refresh your browser with Ctrl+F5"
