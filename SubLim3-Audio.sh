#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPT_DIR="$HOME/SubLim3-JukeBox"
WORKDIR="$SCRIPT_DIR/.audio-temp"
REPO_DIR="$WORKDIR/repo"

BATTLE_SRC="audiofolders/Battle Music"
TOWN_SRC="audiofolders/Town Music"
TRAVELERS_SRC="audiofolders/Travelers Themes"

print_banner() {
    clear
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
}

print_header() {
    echo
    echo "========================================"
    echo " SubLim3 Audio Sync Utility"
    echo "========================================"
    echo
}

require_commands() {
    for cmd in git rsync; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            echo "ERROR: Missing required command: $cmd"
            echo "Install with:"
            echo "sudo apt install git rsync"
            exit 1
        fi
    done
}

prepare_dest() {
    mkdir -p "$DEST_BASE"
}

prepare_repo() {
    echo
    echo "Refreshing audio repository..."

    rm -rf "$WORKDIR"
    mkdir -p "$WORKDIR"

    git clone --filter=blob:none --no-checkout "$REPO_URL" "$REPO_DIR"
    cd "$REPO_DIR"

    git sparse-checkout init --cone
    git checkout "$BRANCH" >/dev/null 2>&1
}

folder_installed() {
    local rel_path="$1"
    local folder_name
    folder_name="$(basename "$rel_path")"

    [[ -d "$DEST_BASE/$folder_name" ]]
}

show_menu() {
    echo "Audio Packages"
    echo

    if folder_installed "$BATTLE_SRC"; then
        echo "1) Battle Music      [INSTALLED]"
    else
        echo "1) Battle Music      [AVAILABLE]"
    fi

    if folder_installed "$TOWN_SRC"; then
        echo "2) Town Music        [INSTALLED]"
    else
        echo "2) Town Music        [AVAILABLE]"
    fi

    if folder_installed "$TRAVELERS_SRC"; then
        echo "3) Travelers Themes  [INSTALLED]"
    else
        echo "3) Travelers Themes  [AVAILABLE]"
    fi

    echo
    echo "A) Install ALL missing"
    echo "Q) Quit"
    echo
}

sync_folder_missing_only() {
    local rel_path="$1"
    local folder_name
    folder_name="$(basename "$rel_path")"

    echo
    echo "Syncing missing files for: $folder_name"

    rsync -a --ignore-existing --info=progress2 \
        "$REPO_DIR/$rel_path/" \
        "$DEST_BASE/$folder_name/"

    echo
}

install_battle_music() {
    cd "$REPO_DIR"
    git sparse-checkout set "$BATTLE_SRC"
    git checkout "$BRANCH" >/dev/null 2>&1
    sync_folder_missing_only "$BATTLE_SRC"
}

install_town_music() {
    cd "$REPO_DIR"
    git sparse-checkout set "$TOWN_SRC"
    git checkout "$BRANCH" >/dev/null 2>&1
    sync_folder_missing_only "$TOWN_SRC"
}

install_travelers_themes() {
    cd "$REPO_DIR"
    git sparse-checkout set "$TRAVELERS_SRC"
    git checkout "$BRANCH" >/dev/null 2>&1
    sync_folder_missing_only "$TRAVELERS_SRC"
}

install_all_missing() {
    if ! folder_installed "$BATTLE_SRC"; then
        install_battle_music
    else
        echo "Skipping Battle Music (already installed)"
    fi

    if ! folder_installed "$TOWN_SRC"; then
        install_town_music
    else
        echo "Skipping Town Music (already installed)"
    fi

    if ! folder_installed "$TRAVELERS_SRC"; then
        install_travelers_themes
    else
        echo "Skipping Travelers Themes (already installed)"
    fi
}

main() {
    trap 'rm -rf "$WORKDIR"' EXIT

    print_banner
    print_header
    require_commands
    prepare_dest
    prepare_repo

    while true; do
        show_menu

        read -rp "Select option: " choice

        case "$choice" in
            1)
                install_battle_music
                ;;
            2)
                install_town_music
                ;;
            3)
                install_travelers_themes
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
        print_banner
        print_header
    done
}

main

