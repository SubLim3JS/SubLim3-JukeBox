#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

RFID_TRIGGER_TARGET="$TARGET_DIR/scripts/rfid_trigger_play.sh"
FEEDBACK_SCRIPT="$TARGET_DIR/scripts/sublim3-feedback.sh"

UDEV_SOURCE_DIR="$SOURCE_DIR/overrides/udev"
SYSTEMD_SOURCE_DIR="$SOURCE_DIR/overrides/systemd"

UDEV_TARGET_DIR="/etc/udev/rules.d"
SYSTEMD_TARGET_DIR="/etc/systemd/system"

print_header() {
  echo ""
  echo "======================================================"
  echo "=============== SubLim3 JukeBox Update ==============="
  echo "======================================================"
  echo ""
}

print_section() {
  echo ""
  echo "======================================================"
  echo "$1"
  echo "======================================================"
  echo ""
}

play_feedback() {
  local sound="$1"
  if [ -x "$FEEDBACK_SCRIPT" ]; then
    bash "$FEEDBACK_SCRIPT" "$sound" >/dev/null 2>&1
  fi
}

play_feedback_bg() {
  local sound="$1"
  if [ -x "$FEEDBACK_SCRIPT" ]; then
    nohup bash "$FEEDBACK_SCRIPT" "$sound" >/dev/null 2>&1 &
  fi
}

need_sudo() {
  if ! sudo -n true >/dev/null 2>&1; then
    echo "[ERROR] This script needs passwordless sudo for system file deployment."
    echo "[ERROR] Please run it with a user that can sudo, or configure sudo permissions."
    ERRORS=$((ERRORS + 1))
    return 1
  fi
  return 0
}

update_repo() {
  print_section "Updating repository"

  if [ ! -d "$SOURCE_DIR/.git" ]; then
    echo "[ERROR] $SOURCE_DIR is not a git repository."
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  cd "$SOURCE_DIR" || {
    echo "[ERROR] Could not enter $SOURCE_DIR"
    ERRORS=$((ERRORS + 1))
    return 1
  }

  local current_branch
  current_branch="$(git branch --show-current 2>/dev/null)"
  echo "[INFO] Current branch: ${current_branch:-unknown}"

  if ! git rev-parse --verify origin/main >/dev/null 2>&1; then
    echo "[INFO] Fetching origin/main..."
    git fetch origin main >/dev/null 2>&1
  fi

  if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "[WARN] Local changes detected in repo."
    echo "[INFO] Stashing local changes before pull..."
    if git stash push -u -m "SubLim3 auto-stash before update" >/dev/null 2>&1; then
      echo "[OK] Local changes stashed"
    else
      echo "[ERROR] Failed to stash local changes"
      ERRORS=$((ERRORS + 1))
      return 1
    fi
  else
    echo "[OK] Repo clean"
  fi

  echo "[INFO] Fetching latest updates..."
  if git fetch origin main >/dev/null 2>&1; then
    echo "[OK] Fetch complete"
  else
    echo "[ERROR] Git fetch failed"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  echo "[INFO] Resetting local repo to origin/main..."
  if git reset --hard origin/main >/dev/null 2>&1; then
    echo "[OK] Repository updated to latest GitHub version"
  else
    echo "[ERROR] Git reset failed"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  return 0
}

copy_with_backup() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    cp -f "$dst" "${dst}${BACKUP_SUFFIX}"
  fi

  if cp -f "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
    return 0
  else
    echo "[ERROR] Failed to copy $(basename "$src")"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

copy_if_missing() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    echo "[OK] Preserved existing file: $dst"
    return 0
  fi

  if cp -f "$src" "$dst"; then
    echo "[OK] Created default file: $dst"
    return 0
  else
    echo "[ERROR] Failed to create default file: $dst"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

copy_with_backup_sudo() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return 1
  fi

  sudo mkdir -p "$(dirname "$dst")"

  if sudo test -f "$dst"; then
    sudo cp -f "$dst" "${dst}${BACKUP_SUFFIX}"
  fi

  if sudo cp -f "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
    return 0
  else
    echo "[ERROR] Failed to copy $(basename "$src") to $dst"
    ERRORS=$((ERRORS + 1))
    return 1
  fi
}

deploy_directory() {
  local src_dir="$1"
  local dst_dir="$2"

  if [ ! -d "$src_dir" ]; then
    echo "[WARN] Missing directory: $src_dir"
    return 0
  fi

  while IFS= read -r file; do
    local rel="${file#$src_dir/}"

    case "$rel" in
      theme-color)
        copy_if_missing "$file" "$dst_dir/$rel"
        ;;
      *)
        copy_with_backup "$file" "$dst_dir/$rel"
        ;;
    esac
  done < <(find "$src_dir" -type f | sort)
}

deploy_directory_sudo() {
  local src_dir="$1"
  local dst_dir="$2"

  if [ ! -d "$src_dir" ]; then
    echo "[WARN] Missing directory: $src_dir"
    return 0
  fi

  while IFS= read -r file; do
    local rel="${file#$src_dir/}"
    copy_with_backup_sudo "$file" "$dst_dir/$rel"
  done < <(find "$src_dir" -type f | sort)
}

deploy_system_files() {
  print_section "Deploying system files"

  need_sudo || return 1

  deploy_directory_sudo "$UDEV_SOURCE_DIR" "$UDEV_TARGET_DIR"
  deploy_directory_sudo "$SYSTEMD_SOURCE_DIR" "$SYSTEMD_TARGET_DIR"

  echo "[INFO] Reloading udev rules..."
  if sudo udevadm control --reload-rules; then
    echo "[OK] udev rules reloaded"
  else
    echo "[ERROR] Failed to reload udev rules"
    ERRORS=$((ERRORS + 1))
  fi

  echo "[INFO] Reloading systemd daemon..."
  if sudo systemctl daemon-reload; then
    echo "[OK] systemd daemon reloaded"
  else
    echo "[ERROR] Failed to reload systemd daemon"
    ERRORS=$((ERRORS + 1))
  fi
}

patch_rfid_trigger() {
  print_section "Patching RFID script"

  if [ ! -f "$RFID_TRIGGER_TARGET" ]; then
    echo "[WARN] RFID trigger script not found"
    return 0
  fi

  if grep -q 'sublim3-feedback.sh" rfid' "$RFID_TRIGGER_TARGET"; then
    echo "[OK] RFID patch already applied"
    return 0
  fi

  cp "$RFID_TRIGGER_TARGET" "${RFID_TRIGGER_TARGET}${BACKUP_SUFFIX}"

  sed -i '/if \[ "\$CARDID" \]; then/a\    bash "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh" rfid >/dev/null 2>&1 &' "$RFID_TRIGGER_TARGET"

  if grep -q 'sublim3-feedback.sh" rfid' "$RFID_TRIGGER_TARGET"; then
    echo "[OK] RFID feedback hook added"
  else
    echo "[ERROR] Failed to patch RFID trigger script"
    ERRORS=$((ERRORS + 1))
  fi
}

fix_permissions() {
  print_section "Fixing permissions"

  chmod +x "$SOURCE_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/settings/"*.py 2>/dev/null
  chmod +x "$TARGET_DIR/settings/cardRegisterAccess" 2>/dev/null

  if [ -f "$TARGET_DIR/settings/theme-color" ]; then
    chmod 664 "$TARGET_DIR/settings/theme-color" 2>/dev/null
  fi

  if [ -d "$SYSTEMD_SOURCE_DIR" ]; then
    chmod 644 "$SYSTEMD_SOURCE_DIR"/* 2>/dev/null
  fi

  if [ -d "$UDEV_SOURCE_DIR" ]; then
    chmod 644 "$UDEV_SOURCE_DIR"/* 2>/dev/null
  fi

  echo "[OK] Permissions fixed"
}

show_version_check() {
  print_section "Version check"

  local repo_version="$SOURCE_DIR/overrides/settings/version-number"
  local live_version="$TARGET_DIR/settings/version-number"

  if [ -f "$repo_version" ]; then
    echo "[INFO] Repo version: $(cat "$repo_version")"
  else
    echo "[WARN] Repo version file missing"
  fi

  if [ -f "$live_version" ]; then
    echo "[INFO] Live version: $(cat "$live_version")"
  else
    echo "[WARN] Live version file missing"
  fi
}

show_system_file_check() {
  print_section "System file check"

  if sudo test -f "$UDEV_TARGET_DIR/99-sublim3-usb-autoimport.rules"; then
    echo "[OK] udev rule installed: $UDEV_TARGET_DIR/99-sublim3-usb-autoimport.rules"
  else
    echo "[WARN] udev rule not found in $UDEV_TARGET_DIR"
    ERRORS=$((ERRORS + 1))
  fi

  if sudo test -f "$SYSTEMD_TARGET_DIR/sublim3-usb-import@.service"; then
    echo "[OK] systemd service installed: $SYSTEMD_TARGET_DIR/sublim3-usb-import@.service"
  else
    echo "[WARN] systemd service not found in $SYSTEMD_TARGET_DIR"
    ERRORS=$((ERRORS + 1))
  fi
}

main() {
  print_header

  update_repo

  print_section "Deploying overrides"
  deploy_directory "$SOURCE_DIR/overrides/htdocs" "$TARGET_DIR/htdocs"
  deploy_directory "$SOURCE_DIR/overrides/settings" "$TARGET_DIR/settings"
  deploy_directory "$SOURCE_DIR/overrides/icons" "$TARGET_DIR/htdocs/_assets/icons"

  print_section "Deploying scripts"
  copy_with_backup "$SOURCE_DIR/scripts/sublim3-feedback.sh" "$TARGET_DIR/scripts/sublim3-feedback.sh"

  play_feedback_bg update

  patch_rfid_trigger
  fix_permissions
  deploy_system_files
  show_version_check
  show_system_file_check

  echo ""
  echo "======================================================"
  if [ "$ERRORS" -eq 0 ]; then
    play_feedback success
    echo "[OK] SubLim3 update completed successfully"
    echo "======================================================"
    exit 0
  else
    play_feedback error
    echo "[WARN] Completed with $ERRORS error(s)"
    echo "======================================================"
    exit 1
  fi
}

main
