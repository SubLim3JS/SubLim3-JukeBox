#!/bin/bash

AUDIO_REPO_URL="https://github.com/SubLim3JS/SubLim3-Audio.git"
AUDIO_REPO_DIR="/home/pi/SubLim3-Audio"
SOURCE_AUDIO_DIR="$AUDIO_REPO_DIR/audiofolders"
TARGET_AUDIO_DIR="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"
GIT_BRANCH="main"

ERRORS=0
COPIED=0
SKIPPED=0

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

printf "
========================================
 SubLim3 Audio Sync Utility
========================================

"

# ------------------------------------------------
# Step 1: Clone repo if missing
# ------------------------------------------------
if [ ! -d "$AUDIO_REPO_DIR/.git" ]; then
    echo "Audio repository not found locally."
    echo "Cloning repository..."
    echo ""

    export GIT_TERMINAL_PROMPT=0
    git clone -q "$AUDIO_REPO_URL" "$AUDIO_REPO_DIR"

    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to clone audio repository."
        exit 1
    fi

    echo "Audio repository cloned successfully."
    echo ""
else
    echo "Audio repository already exists."
    echo ""
fi

# ------------------------------------------------
# Step 2: Ensure remote URL is correct
# ------------------------------------------------
cd "$AUDIO_REPO_DIR" || {
    echo "ERROR: Cannot access $AUDIO_REPO_DIR"
    exit 1
}

git remote set-url origin "$AUDIO_REPO_URL" >/dev/null 2>&1

# ------------------------------------------------
# Step 3: Pull latest repo changes without prompting
# ------------------------------------------------
echo "Pulling latest audio updates from GitHub..."

export GIT_TERMINAL_PROMPT=0
git pull -q --ff-only origin "$GIT_BRANCH"

if [ $? -ne 0 ]; then
    echo "ERROR: Could not pull audio repository."
    exit 1
fi

echo "Repository updated successfully."
echo ""

# ------------------------------------------------
# Step 4: Validate source/destination
# ------------------------------------------------
if [ ! -d "$SOURCE_AUDIO_DIR" ]; then
    echo "ERROR: Source audio folder not found:"
    echo "       $SOURCE_AUDIO_DIR"
    exit 1
fi

mkdir -p "$TARGET_AUDIO_DIR"

if [ $? -ne 0 ]; then
    echo "ERROR: Could not create target audio folder:"
    echo "       $TARGET_AUDIO_DIR"
    exit 1
fi

# ------------------------------------------------
# Step 5: Copy only missing files/folders
# ------------------------------------------------
echo "Comparing repository audiofolders with Phoniebox audiofolders..."
echo ""

while IFS= read -r -d '' source_item; do
    relative_path="${source_item#$SOURCE_AUDIO_DIR/}"
    target_item="$TARGET_AUDIO_DIR/$relative_path"

    if [ -d "$source_item" ]; then
        if [ ! -d "$target_item" ]; then
            mkdir -p "$target_item"
            if [ $? -eq 0 ]; then
                echo "Created missing folder: $relative_path"
                COPIED=$((COPIED+1))
            else
                echo "ERROR: Failed to create folder: $relative_path"
                ERRORS=$((ERRORS+1))
            fi
        else
            SKIPPED=$((SKIPPED+1))
        fi

    elif [ -f "$source_item" ]; then
        if [ ! -f "$target_item" ]; then
            mkdir -p "$(dirname "$target_item")"
            cp -a "$source_item" "$target_item"
            if [ $? -eq 0 ]; then
                echo "Copied missing file: $relative_path"
                COPIED=$((COPIED+1))
            else
                echo "ERROR: Failed to copy file: $relative_path"
                ERRORS=$((ERRORS+1))
            fi
        else
            SKIPPED=$((SKIPPED+1))
        fi
    fi
done < <(find "$SOURCE_AUDIO_DIR" -mindepth 1 -print0)

echo ""
echo "========================================"
echo " Audio Sync Summary"
echo "========================================"
echo "Copied/created: $COPIED"
echo "Already present: $SKIPPED"
echo "Errors: $ERRORS"
echo ""

if [ "$ERRORS" -eq 0 ]; then
    echo "SubLim3 audio sync completed successfully."
    exit 0
else
    echo "SubLim3 audio sync completed with errors."
    exit 1
fi
