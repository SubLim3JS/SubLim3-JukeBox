#!/bin/bash

set -u

SOURCE_DIR="/home/pi/SubLim3-JukeBox"
TARGET_DIR="/home/pi/RPi-Jukebox-RFID"

OVERRIDES_HTDOCS="$SOURCE_DIR/overrides/htdocs"
OVERRIDES_SETTINGS="$SOURCE_DIR/overrides/settings"
OVERRIDES_ICONS="$SOURCE_DIR/overrides/icons"
OVERRIDES_CSS="$SOURCE_DIR/overrides/css"

TARGET_HTDOCS="$TARGET_DIR/htdocs"
TARGET_SETTINGS="$TARGET_DIR/settings"
TARGET_ICONS="$TARGET_HTDOCS/_assets/icons"
TARGET_CSS="$TARGET_HTDOCS/_assets/css"

SCRIPT_DIR="$SOURCE_DIR/scripts"
SCRIPT_NAME="SubLim3-Jukebox-Update.sh"

ERRORS=0

print_header() {
  printf "\n====================================\n"
  printf "====== SubLim3 JukeBox Update ======\n"
  printf "====================================\n\n"
}

print_section() {
  printf "\n------------------------------------\n"
  printf "%s\n" "$1"
  printf "------------------------------------\n"
}

copy_file() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if cp "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
  else
    echo "[WARN] Failed to copy: $src -> $dst"
    ERRORS=$((ERRORS + 1))
  fi
}

copy_file_with_backup() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ] && [ ! -f "${dst}-BACKUP" ]; then
    if cp "$dst" "${dst}-BACKUP"; then
      echo "[OK] Backup created: ${dst}-BACKUP"
    else
      echo "[WARN] Failed to create backup: ${dst}-BACKUP"
      ERRORS=$((ERRORS + 1))
    fi
  fi

  if cp "$src" "$dst"; then
    echo "[OK] $(basename "$src") -> $dst"
  else
    echo "[WARN] Failed to copy: $src -> $dst"
    ERRORS=$((ERRORS + 1))
  fi
}

copy_if_missing() {
  local src="$1"
  local dst="$2"

  if [ ! -f "$src" ]; then
    echo "[WARN] Missing source file: $src"
    ERRORS=$((ERRORS + 1))
    return
  fi

  mkdir -p "$(dirname "$dst")"

  if [ -f "$dst" ]; then
    echo "[OK] Preserving existing file: $dst"
  else
    if cp "$src" "$dst"; then
      echo "[OK] Created default file: $dst"
    else
      echo "[WARN] Failed to create default file: $dst"
      ERRORS=$((ERRORS + 1))
    fi
  fi
}

ensure_script_is_latest() {
  local latest_script="$SCRIPT_DIR/$SCRIPT_NAME"

  if [ -f "$latest_script" ]; then
    echo "[OK] Latest update script is present: $latest_script"
  else
    echo "[WARN] Update script not found: $latest_script"
    ERRORS=$((ERRORS + 1))
  fi
}

update_repo() {
  print_section "Updating SubLim3-JukeBox repository"

  cd "$SOURCE_DIR" || {
    echo "[WARN] Could not change directory to $SOURCE_DIR"
    ERRORS=$((ERRORS + 1))
    return
  }

  if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "[WARN] $SOURCE_DIR is not a git repository."
    ERRORS=$((ERRORS + 1))
    return
  fi

  if [ -n "$(git status --porcelain)" ]; then
    echo "[WARN] Local repo has uncommitted changes."
    echo "[WARN] Skipping git pull and using local files."
    return
  fi

  if git pull -q origin main; then
    echo "[OK] Repository updated successfully."
  else
    echo "[WARN] git pull failed."
    ERRORS=$((ERRORS + 1))
  fi
}

deploy_overrides() {
  print_section "Deploying override files"

  # htdocs overrides
  if [ -d "$OVERRIDES_HTDOCS" ]; then
    while IFS= read -r -d '' file; do
      rel_path="${file#$OVERRIDES_HTDOCS/}"
      copy_file_with_backup "$file" "$TARGET_HTDOCS/$rel_path"
    done < <(find "$OVERRIDES_HTDOCS" -type f -print0)
  else
    echo "[WARN] Missing directory: $OVERRIDES_HTDOCS"
    ERRORS=$((ERRORS + 1))
  fi

  # css overrides
  if [ -d "$OVERRIDES_CSS" ]; then
    while IFS= read -r -d '' file; do
      rel_path="${file#$OVERRIDES_CSS/}"
      copy_file_with_backup "$file" "$TARGET_CSS/$rel_path"
    done < <(find "$OVERRIDES_CSS" -type f -print0)
  else
    echo "[WARN] Missing directory: $OVERRIDES_CSS"
    ERRORS=$((ERRORS + 1))
  fi

  # icon overrides
  if [ -d "$OVERRIDES_ICONS" ]; then
    while IFS= read -r -d '' file; do
      rel_path="${file#$OVERRIDES_ICONS/}"
      copy_file_with_backup "$file" "$TARGET_ICONS/$rel_path"
    done < <(find "$OVERRIDES_ICONS" -type f -print0)
  else
    echo "[WARN] Missing directory: $OVERRIDES_ICONS"
    ERRORS=$((ERRORS + 1))
  fi

  # settings overrides
  if [ -d "$OVERRIDES_SETTINGS" ]; then
    while IFS= read -r -d '' file; do
      rel_path="${file#$OVERRIDES_SETTINGS/}"

      case "$rel_path" in
        theme-color|cardRegisterAccess)
          copy_if_missing "$file" "$TARGET_SETTINGS/$rel_path"
          ;;
        *)
          copy_file_with_backup "$file" "$TARGET_SETTINGS/$rel_path"
          ;;
      esac
    done < <(find "$OVERRIDES_SETTINGS" -type f -print0)
  else
    echo "[WARN] Missing directory: $OVERRIDES_SETTINGS"
    ERRORS=$((ERRORS + 1))
  fi
}

set_permissions() {
  print_section "Setting permissions"

  chmod +x "$SCRIPT_DIR/$SCRIPT_NAME" 2>/dev/null || true

  if [ -d "$TARGET_HTDOCS" ]; then
    sudo chgrp -R www-data "$TARGET_HTDOCS" 2>/dev/null || true
    sudo chmod -R 775 "$TARGET_HTDOCS" 2>/dev/null || true
    echo "[OK] Permissions updated for $TARGET_HTDOCS"
  fi

  if [ -d "$TARGET_SETTINGS" ]; then
    sudo chgrp -R www-data "$TARGET_SETTINGS" 2>/dev/null || true
    sudo chmod -R 775 "$TARGET_SETTINGS" 2>/dev/null || true

    if [ -f "$TARGET_SETTINGS/theme-color" ]; then
      sudo chmod 664 "$TARGET_SETTINGS/theme-color" 2>/dev/null || true
    fi

    if [ -f "$TARGET_SETTINGS/cardRegisterAccess" ]; then
      sudo chmod 664 "$TARGET_SETTINGS/cardRegisterAccess" 2>/dev/null || true
    fi

    echo "[OK] Permissions updated for $TARGET_SETTINGS"
  fi

  if [ -d "$TARGET_ICONS" ]; then
    sudo chgrp -R www-data "$TARGET_ICONS" 2>/dev/null || true
    sudo chmod -R 775 "$TARGET_ICONS" 2>/dev/null || true
    echo "[OK] Permissions updated for $TARGET_ICONS"
  fi

  if [ -d "$TARGET_CSS" ]; then
    sudo chgrp -R www-data "$TARGET_CSS" 2>/dev/null || true
    sudo chmod -R 775 "$TARGET_CSS" 2>/dev/null || true
    echo "[OK] Permissions updated for $TARGET_CSS"
  fi
}

restart_services() {
  print_section "Restarting services"

  if sudo systemctl restart lighttpd; then
    echo "[OK] lighttpd restarted"
  else
    echo "[WARN] Failed to restart lighttpd"
    ERRORS=$((ERRORS + 1))
  fi
}

print_summary() {
  print_section "Update complete"

  if [ "$ERRORS" -eq 0 ]; then
    echo "[OK] SubLim3 JukeBox update completed successfully."
    return 0
  else
    echo "[WARN] SubLim3 JukeBox update completed with $ERRORS issue(s)."
    return 1
  fi
}

main() {
  print_header
  update_repo
  ensure_script_is_latest
  deploy_overrides
  set_permissions
  restart_services
  print_summary
}

main
exit $?
