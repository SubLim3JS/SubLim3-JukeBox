#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPT_DIR="$HOME/SubLim3-JukeBox"
WORKDIR="$SCRIPT_DIR/.audio-temp"

mkdir -p "$DEST_BASE"

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

print_header() {
    echo
    echo "========================================"
    echo " SubLim3 Audio Update Utility"
    echo "========================================"
    echo
}

check_status() {
    local num="$1"
    local label="$2"
    local path="$3"

    if [ -d "$DEST_BASE/$path" ]; then
        echo "$num) $label  [INSTALLED]"
    else
        echo "$num) $label  [AVAILABLE]"
    fi
}

show_menu() {
    echo "Audio Packages"
    echo
    check_status 1 "Battle Music" "Battle Music"
    check_status 2 "Town Music" "Town Music"
    check_status 3 "Travelers Themes" "Travelers Themes"
    check_status 4 "Radio-Stations" "Radio-Stations/z_Radio Stations"
    echo
    echo "A) Install ALL missing"
    echo "Q) Quit"
    echo
}

prepare_repo() {
    if [ -d "$WORKDIR/.git" ]; then
        return
    fi

    echo
    echo "Preparing audio repository..."

    rm -rf "$WORKDIR"
    git clone --filter=blob:none --no-checkout "$REPO_URL" "$WORKDIR"
    cd "$WORKDIR"

    git sparse-checkout init --cone
    git checkout "$BRANCH"
}

install_folder() {
    local remote="$1"
    local local_dest="$2"
    local display_name="$3"

    if [ -d "$DEST_BASE/$local_dest" ]; then
        echo
        echo "Skipping $display_name (already installed)"
        return
    fi

    prepare_repo

    echo
    echo "Installing $display_name..."

    cd "$WORKDIR"
    git sparse-checkout set "audiofolders/$remote"
    git checkout "$BRANCH"

    if [ ! -d "$WORKDIR/audiofolders/$remote" ]; then
        echo "ERROR: Folder not found in repo:"
        echo "  audiofolders/$remote"
        return 1
    fi

    mkdir -p "$(dirname "$DEST_BASE/$local_dest")"
    cp -r "$WORKDIR/audiofolders/$remote" "$DEST_BASE/$local_dest"

    echo "$display_name installed successfully."
}

install_all_missing() {
    install_folder "Battle Music" "Battle Music" "Battle Music"
    install_folder "Town Music" "Town Music" "Town Music"
    install_folder "Travelers Themes" "Travelers Themes" "Travelers Themes"
    install_folder "z_Radio Stations" "livestreams/z_Radio Stations" "LiveStreams"
}

while true; do
    print_header
    show_menu

    read -rp "Select option: " choice

    case "$choice" in
        1)
            install_folder "Battle Music" "Battle Music" "Battle Music"
            ;;
        2)
            install_folder "Town Music" "Town Music" "Town Music"
            ;;
        3)
            install_folder "Travelers Themes" "Travelers Themes" "Travelers Themes"
            ;;
        4)
            install_folder "z_Radio Stations" "livestreams/z_Radio Stations" "LiveStreams"
            ;;
        A|a)
            install_all_missing
            ;;
        Q|q)
            echo
            echo "Exiting."
            exit 0
            ;;
        *)
            echo
            echo "Invalid selection."
            ;;
    esac

    echo
    read -rp "Press Enter to continue..."
done
