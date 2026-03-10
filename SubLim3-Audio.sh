#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
SCRIPT_DIR="$HOME"
WORKDIR="$SCRIPT_DIR/.SubLim3-Audio-temp"
REPO_DIR="$WORKDIR/repo"

BATTLE_SRC="audiofolders/Battle Music"
TOWN_SRC="audiofolders/Town Music"
TRAVELERS_SRC="audiofolders/Travelers Themes"

RADIO_SRCS=(
    "audiofolders/101-The-Beat"
    "audiofolders/1059-The-Rock"
    "audiofolders/107-The-River"
    "audiofolders/98-The-Big"
    "audiofolders/Big-Classic-Hits"
    "audiofolders/R-and-B-Jams"
)

print_banner() {
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
            echo "  sudo apt update && sudo apt install -y git rsync"
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

folder_name_from_path() {
    local rel_path="$1"
    basename "$rel_path"
}

folder_installed() {
    local rel_path="$1"
    local folder_name
    folder_name="$(folder_name_from_path "$rel_path")"
    [[ -d "$DEST_BASE/$folder_name" ]]
}

radio_installed() {
    local rel_path
    for rel_path in "${RADIO_SRCS[@]}"; do
        if ! folder_installed "$rel_path"; then
            return 1
        fi
    done
    return 0
}

show_main_menu() {
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

    if radio_installed; then
        echo "4) Radio Stations    [INSTALLED]"
    else
        echo "4) Radio Stations    [AVAILABLE]"
    fi

    if test1_installed; then
        echo "4) TEST1    [INSTALLED]"
    else
        echo "4) TEST1    [AVAILABLE]"
    fi

    if test2_installed; then
        echo "4) Test2    [INSTALLED]"
    else
        echo "4) Test2    [AVAILABLE]"
    fi

    echo
    echo "M) Add missing to ALL folders"
    echo "F) Force update ALL folders"
    echo "Q) Quit"
    echo
}

show_submenu() {
    local label="$1"

    echo
    echo "$label"
    echo "A) Add missing only"
    echo "F) Force update / overwrite"
    echo "B) Back"
    echo
}

sync_folder_missing_only() {
    local rel_path="$1"
    local folder_name
    folder_name="$(folder_name_from_path "$rel_path")"

    if [[ ! -d "$REPO_DIR/$rel_path" ]]; then
        echo "ERROR: Source folder not found:"
        echo "  $REPO_DIR/$rel_path"
        return 1
    fi

    mkdir -p "$DEST_BASE/$folder_name"

    echo
    echo "Adding missing files for: $folder_name"
    echo "From: $REPO_DIR/$rel_path"
    echo "To:   $DEST_BASE/$folder_name"
    echo

    rsync -a --ignore-existing --info=progress2 \
        "$REPO_DIR/$rel_path/" \
        "$DEST_BASE/$folder_name/"

    echo
    echo "Done syncing: $folder_name"
}

sync_folder_force_update() {
    local rel_path="$1"
    local folder_name
    folder_name="$(folder_name_from_path "$rel_path")"

    if [[ ! -d "$REPO_DIR/$rel_path" ]]; then
        echo "ERROR: Source folder not found:"
        echo "  $REPO_DIR/$rel_path"
        return 1
    fi

    mkdir -p "$DEST_BASE/$folder_name"

    echo
    echo "Force updating files for: $folder_name"
    echo "From: $REPO_DIR/$rel_path"
    echo "To:   $DEST_BASE/$folder_name"
    echo

    rsync -a --delete --info=progress2 \
        "$REPO_DIR/$rel_path/" \
        "$DEST_BASE/$folder_name/"

    echo
    echo "Done force updating: $folder_name"
}

checkout_folder() {
    local rel_path="$1"

    cd "$REPO_DIR"
    git sparse-checkout set "$rel_path"
    git checkout "$BRANCH" >/dev/null 2>&1
}

checkout_multiple_folders() {
    cd "$REPO_DIR"
    git sparse-checkout set "$@"
    git checkout "$BRANCH" >/dev/null 2>&1
}

process_folder_action() {
    local rel_path="$1"
    local action="$2"

    checkout_folder "$rel_path"

    case "$action" in
        A|a)
            sync_folder_missing_only "$rel_path"
            ;;
        F|f)
            sync_folder_force_update "$rel_path"
            ;;
        *)
            echo "Invalid action."
            return 1
            ;;
    esac
}

process_radio_action() {
    local action="$1"
    local rel_path

    checkout_multiple_folders "${RADIO_SRCS[@]}"

    for rel_path in "${RADIO_SRCS[@]}"; do
        case "$action" in
            A|a)
                sync_folder_missing_only "$rel_path"
                ;;
            F|f)
                sync_folder_force_update "$rel_path"
                ;;
            *)
                echo "Invalid action."
                return 1
                ;;
        esac
    done
}

folder_submenu_loop() {
    local label="$1"
    local rel_path="$2"

    while true; do
        print_banner
        print_header
        show_submenu "$label"

        read -rp "Select option: " subchoice

        case "$subchoice" in
            A|a)
                process_folder_action "$rel_path" "A"
                echo
                read -rp "Press Enter to continue..."
                return
                ;;
            F|f)
                process_folder_action "$rel_path" "F"
                echo
                read -rp "Press Enter to continue..."
                return
                ;;
            B|b)
                return
                ;;
            *)
                echo
                echo "Invalid selection."
                read -rp "Press Enter to continue..."
                ;;
        esac
    done
}

radio_submenu_loop() {
    while true; do
        print_banner
        print_header
        show_submenu "Radio Stations"

        read -rp "Select option: " subchoice

        case "$subchoice" in
            A|a)
                process_radio_action "A"
                echo
                read -rp "Press Enter to continue..."
                return
                ;;
            F|f)
                process_radio_action "F"
                echo
                read -rp "Press Enter to continue..."
                return
                ;;
            B|b)
                return
                ;;
            *)
                echo
                echo "Invalid selection."
                read -rp "Press Enter to continue..."
                ;;
        esac
    done
}

add_missing_all() {
    process_folder_action "$BATTLE_SRC" "A"
    process_folder_action "$TOWN_SRC" "A"
    process_folder_action "$TRAVELERS_SRC" "A"
    process_radio_action "A"
}

force_update_all() {
    process_folder_action "$BATTLE_SRC" "F"
    process_folder_action "$TOWN_SRC" "F"
    process_folder_action "$TRAVELERS_SRC" "F"
    process_radio_action "F"
}

main() {
    trap 'rm -rf "$WORKDIR"' EXIT

    require_commands
    prepare_dest
    prepare_repo

    while true; do
        print_banner
        print_header
        show_main_menu

        read -rp "Select option: " choice

        case "$choice" in
            1)
                folder_submenu_loop "Battle Music" "$BATTLE_SRC"
                ;;
            2)
                folder_submenu_loop "Town Music" "$TOWN_SRC"
                ;;
            3)
                folder_submenu_loop "Travelers Themes" "$TRAVELERS_SRC"
                ;;
            4)
                radio_submenu_loop
                ;;
            M|m)
                add_missing_all
                echo
                read -rp "Press Enter to continue..."
                ;;
            F|f)
                force_update_all
                echo
                read -rp "Press Enter to continue..."
                ;;
            Q|q)
                echo
                echo "Exiting."
                exit 0
                ;;
            *)
                echo
                echo "Invalid selection."
                read -rp "Press Enter to continue..."
                ;;
        esac
    done
}

main
