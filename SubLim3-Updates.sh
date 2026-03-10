cat > ~/SubLim3-JukeBox/SubLim3-Updates.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$HOME/SubLim3-JukeBox"
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

require_git() {
    if command -v git >/dev/null 2>&1; then
        log_ok "git is installed"
    else
        log_fail "git is not installed"
        echo "Install with:"
        echo "  sudo apt update && sudo apt install -y git"
        exit 1
    fi
}

check_repo() {
    if [[ -d "$REPO_DIR/.git" ]]; then
        log_ok "Git repo found at $REPO_DIR"
    else
        log_fail "Git repo not found at $REPO_DIR"
        exit 1
    fi
}

abort_merge_if_needed() {
    cd "$REPO_DIR"

    if git rev-parse --verify MERGE_HEAD >/dev/null 2>&1; then
        log_info "Unfinished merge detected. Aborting it..."
        if git merge --abort >/dev/null 2>&1; then
            log_ok "Previous merge aborted"
        else
            log_fail "Could not abort previous merge"
            exit 1
        fi
    else
        log_ok "No unfinished merge detected"
    fi
}

fetch_latest() {
    cd "$REPO_DIR"
    log_info "Fetching latest changes from origin/$BRANCH..."
    git fetch origin "$BRANCH" --prune
    log_ok "Fetched latest changes"
}

reset_to_origin() {
    cd "$REPO_DIR"
    log_info "Force resetting local repo to origin/$BRANCH..."
    git reset --hard "origin/$BRANCH"
    log_ok "Local repo reset to origin/$BRANCH"
}

clean_repo() {
    cd "$REPO_DIR"
    log_info "Removing untracked files and folders..."
    git clean -fd
    log_ok "Untracked files removed"
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
    echo "[ OK ] Update completed successfully."
}

main() {
    print_banner
    print_header
    require_git
    check_repo
    abort_merge_if_needed
    fetch_latest
    reset_to_origin
    clean_repo
    show_revision
    show_summary
}

main
EOF
