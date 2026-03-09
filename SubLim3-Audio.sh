#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPT_DIR="$HOME/SubLim3-JukeBox"
WORKDIR="$SCRIPT_DIR/.audio-temp"
REPO_DIR="$WORKDIR/repo"

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

#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPT_DIR="$HOME/SubLim3-JukeBox"
WORKDIR="$SCRIPT_DIR/.audio-temp"
REPO_DIR="$WORKDIR/repo"

print_header() {
    echo
    echo "========================================"
    echo " SubLim3 Audio Sync Utility"
    echo "========================================"
    echo
}

require_commands() {
    local missing=0

    for cmd in git rsync; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            echo "ERROR: Required command not found: $cmd"
            missing=1
        fi
    done

    if [ "$missing" -ne 0 ]; then
        echo
        echo "Install missing tools, then run again."
        echo "Example:"
        echo "  sudo apt update && sudo apt install -y git rsync"
        exit 1
    fi
}

prepare_dest() {
    mkdir -p "$DEST_BASE"
}

fresh_clone_audio_repo() {
    echo "Refreshing audio repository..."

    rm -rf "$WORKDIR"
    mkdir -p "$WORKDIR"

    git clone --filter=blob:none --no-checkout "$REPO_URL" "$REPO_DIR"
    cd "$REPO_DIR"

    git sparse-checkout init --cone
    git sparse-checkout set "audiofolders"
    git checkout "$BRANCH"
}

show_preview() {
    echo
    echo "Source repo:"
    echo "  $REPO_URL"
    echo "Branch:"
    echo "  $BRANCH"
    echo "Source folder:"
    echo "  $REPO_DIR/audiofolders"
    echo "Destination folder:"
    echo "  $DEST_BASE"
    echo
}

sync_missing_only() {
    if [ ! -d "$REPO_DIR/audiofolders" ]; then
        echo "ERROR: audiofolders was not found after clone."
        exit 1
    fi

    echo "Copying only missing files and folders..."
    echo

    rsync -av --ignore-existing \
        "$REPO_DIR/audiofolders/" \
        "$DEST_BASE/"

    echo
    echo "Sync complete."
    echo "Existing files were preserved."
}

cleanup() {
    rm -rf "$WORKDIR"
}

main() {
    trap cleanup EXIT

    print_header
    require_commands
    prepare_dest
    fresh_clone_audio_repo
    show_preview
    sync_missing_only
}

main
