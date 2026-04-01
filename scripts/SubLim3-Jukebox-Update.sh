#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"
BACKUP_SUFFIX="-BACKUP"
ERRORS=0

RFID_TRIGGER_TARGET="$TARGET_DIR/scripts/rfid_trigger_play.sh"
FEEDBACK_SCRIPT="$TARGET_DIR/scripts/sublim3-feedback.sh"

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

  echo "[INFO] Current branch:"
  git branch --show-current 2>/dev/null

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
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    cp -f "$dst" "${dst}${BACKUP_SUFFIX}"
  fi

  if cp -f "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
  else
    echo "[ERROR] Failed to copy $(basename "$src")"
    ERRORS=$((ERRORS + 1))
  fi
}

deploy_directory() {
  local src_dir="$1"
  local dst_dir="$2"

  if [ ! -d "$src_dir" ]; then
    echo "[WARN] Missing directory: $src_dir"
    return
  fi

  while IFS= read -r file; do
    local rel="${file#$src_dir/}"
    copy_with_backup "$file" "$dst_dir/$rel"
  done < <(find "$src_dir" -type f | sort)
}

patch_rfid_trigger() {
  print_section "Patching RFID script"

  if [ ! -f "$RFID_TRIGGER_TARGET" ]; then
    echo "[WARN] RFID trigger script not found"
    return
  fi

  if grep -q 'sublim3-feedback.sh' "$RFID_TRIGGER_TARGET"; then
    echo "[OK] RFID patch already applied"
    return
  fi

  cp "$RFID_TRIGGER_TARGET" "${RFID_TRIGGER_TARGET}${BACKUP_SUFFIX}"

  sed -i '/if \[ "\$CARDID" \]; then/a\    bash "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh" rfid >/dev/null 2>&1 &' "$RFID_TRIGGER_TARGET"

  if grep -q 'sublim3-feedback.sh' "$RFID_TRIGGER_TARGET"; then
    echo "[OK] RFID feedback hook added"
  else
    echo "[ERROR] Failed to patch RFID trigger script"
    ERRORS=$((ERRORS + 1))
  fi
}

fix_permissions() {
  print_section "Fixing permissions"

  chmod +x "$TARGET_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/settings/"*.py 2>/dev/null
  chmod +x "$TARGET_DIR/settings/cardRegisterAccess" 2>/dev/null

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
  show_version_check

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
