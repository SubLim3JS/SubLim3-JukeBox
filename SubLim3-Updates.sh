#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$HOME/SubLim3-JukeBox"
BRANCH="main"

print_banner() {
    if [[ -t 1 ]] && [[ -n "${TERM:-}" ]] && command -v tput >/dev/null 2>&1; then
        tput clear 2>/dev/null || true
    fi

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
        echo "ERROR: Git repo not found at:"
        echo "  $REPO_DIR"
        exit 1
    fi
}

force_update_repo() {
    echo "Updating repo at:"
    echo "  $REPO_DIR"
    echo

    cd "$REPO_DIR"

    if git rev-parse --verify MERGE_HEAD >/dev/null 2>&1; then
        echo "Aborting unfinished merge..."
        git merge --abort || true
        echo
    fi

    echo "Fetching latest changes..."
    git fetch origin "$BRANCH" --prune

    echo
    echo "Force resetting local repo to origin/$BRANCH..."
    git reset --hard "origin/$BRANCH"

    echo
    echo "Removing untracked files and folders..."
    git clean -fd

    echo
    echo "Update complete."
    echo "Repo is now synced to origin/$BRANCH."
}

main() {
    print_banner
    print_header
    require_commands
    check_repo
    force_update_repo
}

main
