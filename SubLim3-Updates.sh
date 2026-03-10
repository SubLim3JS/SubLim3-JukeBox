#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$HOME/SubLim3-JukeBox"

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
    echo " SubLim3 Update Utility"
    echo "========================================"
    echo
}

require_commands() {
    if ! command -v git >/dev/null 2>&1; then
        echo "ERROR: git is not installed."
        echo "Install with:"
        echo "  sudo apt update && sudo apt install -y git"
        exit 1
    fi
}

check_repo() {
    if [[ ! -d "$REPO_DIR/.git" ]]; then
        echo "ERROR: Repo not found at:"
        echo "  $REPO_DIR"
        exit 1
    fi
}

show_menu() {
    echo "1) Update SubLim3-JukeBox repo"
    echo "2) Show git status"
    echo "3) Show current branch"
    echo "Q) Quit"
    echo
}

update_repo() {
    echo
    echo "Updating repo..."
    cd "$REPO_DIR"

    if git rev-parse --verify MERGE_HEAD >/dev/null 2>&1; then
        echo "Merge conflict state detected. Aborting previous merge first..."
        git merge --abort || true
    fi

    git fetch --all --prune
    git pull --ff-only

    echo
    echo "Repo updated successfully."
}

show_status() {
    echo
    cd "$REPO_DIR"
    git status
}

show_branch() {
    echo
    cd "$REPO_DIR"
    git branch --show-current
}

main() {
    require_commands
    check_repo

    while true; do
        print_banner
        print_header
        show_menu

        read -rp "Select option: " choice

        case "$choice" in
            1)
                update_repo
                ;;
            2)
                show_status
                ;;
            3)
                show_branch
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
}

main
