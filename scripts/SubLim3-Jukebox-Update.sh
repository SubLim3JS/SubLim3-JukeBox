#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"

OVERRIDES_HTDOCS="$SOURCE_DIR/overrides/htdocs"
OVERRIDES_SETTINGS="$SOURCE_DIR/overrides/settings"
OVERRIDES_ICONS="$SOURCE_DIR/overrides/icons"
SOURCE_CSS="$SOURCE_DIR/htdocs/_assets/css/custom-sublim3.css"
SCRIPT_DIR="$SOURCE_DIR/scripts"

TARGET_HTDOCS="$TARGET_DIR/htdocs"
TARGET_SETTINGS="$TARGET_DIR/settings"
TARGET_ICONS="$TARGET_HTDOCS/_assets/icons"
TARGET_CSS="$TARGET_HTDOCS/_assets/css"
TARGET_SCRIPTS="$TARGET_DIR/scripts"

ERRORS=0

print_header() {
    printf "\n====================================\n"
    printf "====== SubLim3 JukeBox Update ======\n"
    printf "====================================\n\n"
}

play_feedback() {
    local sound_name="$1"
    if [ -x "$TARGET_SCRIPTS/sublim3-feedback.sh" ]; then
        bash "$TARGET_SCRIPTS/sublim3-feedback.sh" "$sound_name" >/dev/null 2>&1 &
    fi
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
        echo "[ERROR] Failed to copy $(basename "$src") -> $dst"
        ERRORS=$((ERRORS + 1))
    fi
}

set_permissions() {
    local path="$1"
    local mode="$2"

    if [ -e "$path" ]; then
        if chmod "$mode" "$path"; then
            echo "[OK] chmod $mode $path"
        else
            echo "[ERROR] Failed chmod $mode $path"
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo "[WARN] Cannot chmod missing file: $path"
        ERRORS=$((ERRORS + 1))
    fi
}

update_repo() {
    echo "Updating SubLim3-JukeBox repository..."
    echo

    cd "$SOURCE_DIR" || {
        echo "[ERROR] Cannot access $SOURCE_DIR"
        ERRORS=$((ERRORS + 1))
        return
    }

    if [ ! -d "$SOURCE_DIR/.git" ]; then
        echo "[WARN] $SOURCE_DIR is not a git repository."
        echo "[WARN] Skipping git pull and using local files."
        echo
        return
    fi

    if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
        echo "[WARN] Local repo has uncommitted changes."
        echo "[WARN] Skipping git pull and using local files."
        echo
        return
    fi

    if git pull -q origin main; then
        echo "[OK] Repository updated from origin/main"
    else
        echo "[ERROR] git pull failed"
        ERRORS=$((ERRORS + 1))
    fi

    echo
}

deploy_files() {
    echo
    echo "------------------------------------"
    echo "Deploying override files"
    echo "------------------------------------"
    echo

    mkdir -p "$TARGET_HTDOCS" "$TARGET_SETTINGS" "$TARGET_ICONS" "$TARGET_CSS" "$TARGET_SCRIPTS"

    # CSS from repo htdocs path
    copy_file "$SOURCE_CSS" "$TARGET_CSS/custom-sublim3.css"

    # Icons
    copy_file "$OVERRIDES_ICONS/favicon-16x16.png" "$TARGET_ICONS/favicon-16x16.png"
    copy_file "$OVERRIDES_ICONS/favicon-32x32.png" "$TARGET_ICONS/favicon-32x32.png"
    copy_file "$OVERRIDES_ICONS/favicon-96x96.png" "$TARGET_ICONS/favicon-96x96.png"

    # htdocs overrides
    copy_file "$OVERRIDES_HTDOCS/inc.navigation.php" "$TARGET_HTDOCS/inc.navigation.php"
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

    # settings overrides
    copy_file "$OVERRIDES_SETTINGS/version-number" "$TARGET_SETTINGS/version-number"
    copy_file "$OVERRIDES_SETTINGS/gpio-buttons.py" "$TARGET_SETTINGS/gpio-buttons.py"

    # script overrides
    copy_file "$SCRIPT_DIR/SubLim3-USB-AutoImport.sh" "$TARGET_SCRIPTS/SubLim3-USB-AutoImport.sh"
    copy_file "$SCRIPT_DIR/sublim3-feedback.sh" "$TARGET_SCRIPTS/sublim3-feedback.sh"
}

apply_permissions() {
    echo

    set_permissions "$TARGET_SETTINGS/gpio-buttons.py" 755
    set_permissions "$TARGET_SCRIPTS/SubLim3-USB-AutoImport.sh" 755
    set_permissions "$TARGET_SCRIPTS/sublim3-feedback.sh" 755
    set_permissions "$TARGET_HTDOCS/update.php" 644
    set_permissions "$TARGET_HTDOCS/readIP.php" 644
    set_permissions "$TARGET_CSS/custom-sublim3.css" 644
}

print_result() {
    echo

    if [ "$ERRORS" -eq 0 ]; then
        echo "Update complete with no copy errors."
        play_feedback success
        exit 0
    else
        echo "Update completed with $ERRORS error(s)."
        play_feedback error
        exit 1
    fi
}

main() {
    print_header
    play_feedback update
    update_repo
    deploy_files
    apply_permissions
    print_result
}

main
