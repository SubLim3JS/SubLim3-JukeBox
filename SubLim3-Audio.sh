#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/SubLim3JS/SubLim3-JukeBox-Audio.git"
BRANCH="main"

DEST_BASE="$HOME/RPi-Jukebox-RFID/shared/audiofolders"
WORKDIR="$HOME/.SubLim3-Audio-temp"
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
        echo "sudo apt update && sudo apt install -y git rsync"
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
basename "$1"
}

folder_installed() {
local folder
folder=$(folder_name_from_path "$1")
[[ -d "$DEST_BASE/$folder" ]]
}

radio_installed() {
for r in "${RADIO_SRCS[@]}"; do
    if ! folder_installed "$r"; then
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

echo
echo "M) Add missing to ALL folders"
echo "F) Force update ALL folders"
echo "Q) Quit"
echo
}

show_submenu() {

echo
echo "$1"
echo "A) Add missing only"
echo "F) Force update / overwrite"
echo "B) Back"
echo
}

sync_missing() {

local rel="$1"
local folder
folder=$(folder_name_from_path "$rel")

mkdir -p "$DEST_BASE/$folder"

echo
echo "Adding missing files for: $folder"

rsync -rlD --no-owner --no-group --ignore-existing --info=progress2 \
"$REPO_DIR/$rel/" \
"$DEST_BASE/$folder/"
}

sync_force() {

local rel="$1"
local folder
folder=$(folder_name_from_path "$rel")

mkdir -p "$DEST_BASE/$folder"

echo
echo "Force updating files for: $folder"

rsync -rlD --no-owner --no-group --delete --info=progress2 \
"$REPO_DIR/$rel/" \
"$DEST_BASE/$folder/"
}

checkout_folder() {

cd "$REPO_DIR"

git sparse-checkout set "$1"

git checkout "$BRANCH" >/dev/null 2>&1
}

checkout_multiple() {

cd "$REPO_DIR"

git sparse-checkout set "$@"

git checkout "$BRANCH" >/dev/null 2>&1
}

process_folder() {

local rel="$1"
local mode="$2"

checkout_folder "$rel"

if [[ "$mode" == "A" ]]; then
sync_missing "$rel"
else
sync_force "$rel"
fi
}

process_radio() {

local mode="$1"

checkout_multiple "${RADIO_SRCS[@]}"

for r in "${RADIO_SRCS[@]}"; do
    if [[ "$mode" == "A" ]]; then
        sync_missing "$r"
    else
        sync_force "$r"
    fi
done
}

folder_menu() {

local name="$1"
local rel="$2"

while true; do

print_banner
print_header
show_submenu "$name"

read -rp "Select option: " c

case "$c" in
A|a)
process_folder "$rel" "A"
read -rp "Press Enter to continue..."
return
;;
F|f)
process_folder "$rel" "F"
read -rp "Press Enter to continue..."
return
;;
B|b)
return
;;
*)
echo "Invalid selection"
sleep 1
;;
esac

done
}

radio_menu() {

while true; do

print_banner
print_header
show_submenu "Radio Stations"

read -rp "Select option: " c

case "$c" in
A|a)
process_radio "A"
read -rp "Press Enter to continue..."
return
;;
F|f)
process_radio "F"
read -rp "Press Enter to continue..."
return
;;
B|b)
return
;;
*)
echo "Invalid selection"
sleep 1
;;
esac

done
}

add_missing_all() {

process_folder "$BATTLE_SRC" A
process_folder "$TOWN_SRC" A
process_folder "$TRAVELERS_SRC" A
process_radio A
}

force_update_all() {

process_folder "$BATTLE_SRC" F
process_folder "$TOWN_SRC" F
process_folder "$TRAVELERS_SRC" F
process_radio F
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
folder_menu "Battle Music" "$BATTLE_SRC"
;;
2)
folder_menu "Town Music" "$TOWN_SRC"
;;
3)
folder_menu "Travelers Themes" "$TRAVELERS_SRC"
;;
4)
radio_menu
;;
M|m)
add_missing_all
read -rp "Press Enter to continue..."
;;
F|f)
force_update_all
read -rp "Press Enter to continue..."
;;
Q|q)
exit 0
;;
*)
echo "Invalid selection"
sleep 1
;;
esac

done
}

main
