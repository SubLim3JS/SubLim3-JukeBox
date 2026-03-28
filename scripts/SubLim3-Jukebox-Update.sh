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

#########################################
# SAFE GIT UPDATE
#########################################
update_repo() {
  print_section "Checking repository state"

  if [ ! -d "$SOURCE_DIR/.git" ]; then
    echo "[WARN] Not a git repository. Skipping pull."
    return
  fi

  cd "$SOURCE_DIR" || return

  if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "[WARN] Local changes detected."
    echo "[WARN] Skipping git pull and using local files."
    return
  fi

  echo "[OK] Repo clean. Pulling latest updates..."
  git pull -q origin main

  if [ $? -eq 0 ]; then
    echo "[OK] Repository updated"
  else
    echo "[ERROR] Git pull failed"
    ERRORS=$((ERRORS + 1))
  fi
}

#########################################
# COPY WITH BACKUP
#########################################
copy_with_backup() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing: $src"
    ERRORS=$((ERRORS + 1))
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    cp -f "$dst" "${dst}${BACKUP_SUFFIX}"
  fi

  if cp -f "$src" "$dst"; then
    echo "[OK] $(basename "$src")"
  else
    echo "[ERROR] Failed: $(basename "$src")"
    ERRORS=$((ERRORS + 1))
  fi
}

#########################################
# DEPLOY DIRECTORY
#########################################
deploy_directory() {
  local src_dir="$1"
  local dst_dir="$2"

  if [ ! -d "$src_dir" ]; then
    echo "[WARN] Missing directory: $src_dir"
    return
  fi

  while IFS= read -r file; do
    rel="${file#$src_dir/}"
    copy_with_backup "$file" "$dst_dir/$rel"
  done < <(find "$src_dir" -type f)
}

#########################################
# PATCH RFID SCRIPT
#########################################
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

  sed -i '/if \[ "\$CARDID" \]; then/a\
    bash "/home/pi/RPi-Jukebox-RFID/scripts/sublim3-feedback.sh" rfid >/dev/null 2>&1 &' \
    "$RFID_TRIGGER_TARGET"

  echo "[OK] RFID feedback hook added"
}

#########################################
# FIX PERMISSIONS
#########################################
fix_permissions() {
  print_section "Fixing permissions"

  chmod +x "$TARGET_DIR/scripts/"*.sh 2>/dev/null
  chmod +x "$TARGET_DIR/settings/"*.py 2>/dev/null
  chmod +x "$TARGET_DIR/settings/cardRegisterAccess" 2>/dev/null

  echo "[OK] Permissions fixed"
}

#########################################
# MAIN
#########################################
main() {
  print_header

  update_repo

  print_section "Deploying overrides"

  deploy_directory "$SOURCE_DIR/overrides/htdocs" "$TARGET_DIR/htdocs"
  deploy_directory "$SOURCE_DIR/overrides/settings" "$TARGET_DIR/settings"
  deploy_directory "$SOURCE_DIR/overrides/icons" "$TARGET_DIR/htdocs/_assets/icons"

  print_section "Deploying scripts"

  copy_with_backup "$SOURCE_DIR/scripts/sublim3-feedback.sh" "$TARGET_DIR/scripts/sublim3-feedback.sh"

  # Play "update started" after feedback script is deployed
  play_feedback_bg update

  patch_rfid_trigger
  fix_permissions

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
