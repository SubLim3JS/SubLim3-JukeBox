#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$HOME/SubLim3-JukeBox"
TARGET_DIR="$HOME/RPi-Jukebox-RFID"
BRANCH="main"

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
    echo " SubLim3 Update Utility"
    echo "========================================"
    echo
}

log_info() {
    echo "[INFO] $1"
}

log_ok() {
    echo "[ OK ] $1"
}

log_fail() {
    echo "[FAIL] $1"
}

require_commands() {
    local missing=0

    for cmd in git rsync; do
        if command -v "$cmd" >/dev/null 2>&1; then
            log_ok "$cmd is installed"
        else
            log_fail "$cmd is not installed"
            missing=1
        fi
    done

    if [[ "$missing" -ne 0 ]]; then
        echo
        echo "Install missing packages with:"
        echo "  sudo apt update && sudo apt install -y git rsync"
        exit 1
    fi
}

check_paths() {
    if [[ -d "$REPO_DIR/.git" ]]; then
        log_ok "Repo found at $REPO_DIR"
    else
        log_fail "Repo not found at $REPO_DIR"
        exit 1
    fi

    if [[ -d "$TARGET_DIR" ]]; then
        log_ok "Target found at $TARGET_DIR"
    else
        log_fail "Target not found at $TARGET_DIR"
        exit 1
    fi
}

abort_merge_if_needed() {
    cd "$REPO_DIR"

    if git rev-parse --verify MERGE_HEAD >/dev/null 2>&1; then
        log_info "Unfinished merge detected. Aborting it..."
        git merge --abort >/dev/null 2>&1 || true
        log_ok "Previous merge aborted"
    else
        log_ok "No unfinished merge detected"
    fi
}

update_repo() {
    cd "$REPO_DIR"

    log_info "Fetching latest changes from origin/$BRANCH..."
    git fetch origin "$BRANCH" --prune
    log_ok "Fetched latest changes"

    log_info "Resetting local repo to origin/$BRANCH..."
    git reset --hard "origin/$BRANCH" >/dev/null
    log_ok "Repo reset to origin/$BRANCH"

    log_info "Removing untracked files from repo..."
    git clean -fd >/dev/null
    log_ok "Untracked repo files removed"
}

deploy_files() {
    log_info "Deploying updated files to $TARGET_DIR..."

    rsync -rlD --delete \
        --exclude ".git" \
        --exclude ".gitignore" \
        --exclude "README.md" \
        --exclude "install-jukebox.sh" \
        --exclude "SubLim3-Audio.sh" \
        --exclude "SubLim3-Updates.sh" \
        "$REPO_DIR/" "$TARGET_DIR/"

    log_ok "Files deployed to $TARGET_DIR"
}

show_revision() {
    cd "$REPO_DIR"

    local branch commit
    branch="$(git branch --show-current 2>/dev/null || true)"
    commit="$(git rev-parse --short HEAD 2>/dev/null || true)"

    if [[ -n "$commit" ]]; then
        log_ok "Current revision: ${branch:-$BRANCH} @ $commit"
    else
        log_fail "Could not determine current revision"
        exit 1
    fi
}

show_summary() {
    echo
    echo "========================================"
    echo " Update Summary"
    echo "========================================"
    echo "[ OK ] Repo updated"
    echo "[ OK ] Files deployed to Phoniebox"
    echo
    echo "Update completed successfully."
}

main() {
    print_banner
    print_header
    require_commands
    check_paths
    abort_merge_if_needed
    update_repo
    deploy_files
    show_revision
    show_summary
}

main
