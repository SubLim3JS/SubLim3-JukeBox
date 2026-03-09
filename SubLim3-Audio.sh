#!/bin/bash

AUDIO_REPO_URL="https://github.com/SubLim3JS/SubLim3-Audio.git"
AUDIO_REPO_DIR="/home/pi/SubLim3-Audio"
AUDIO_SOURCE_DIR="$AUDIO_REPO_DIR/audiofolders"
PHONIEBOX_AUDIO_DIR="/home/pi/RPi-Jukebox-RFID/shared/audiofolders"
BACKUP_DIR="/home/pi/RPi-Jukebox-RFID/shared/audiofolders-BACKUP"


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

# Clone or update repo
if [ ! -d "$AUDIO_REPO_DIR/.git" ]; then
    echo "Audio repo not found. Cloning..."
    git clone "$AUDIO_REPO_URL" "$AUDIO_REPO_DIR" || {
        echo "ERROR: Failed to clone audio repository."
        exit 1
    }
else
    echo "Audio repo found. Pulling latest changes..."
    cd "$AUDIO_REPO_DIR" || {
        echo "ERROR: Cannot access $AUDIO_REPO_DIR"
        exit 1
    }

    git pull -q origin main || {
        echo "ERROR: Failed to pull latest audio repository changes."
        exit 1
    }
fi

echo "Repository update complete."
echo ""

# Validate source folder
if [ ! -d "$AUDIO_SOURCE_DIR" ]; then
    echo "ERROR: Source audio folder not found:"
    echo "       $AUDIO_SOURCE_DIR"
    exit 1
fi

# Backup existing Phoniebox audio folder
if [ -d "$PHONIEBOX_AUDIO_DIR" ]; then
    echo "Backing up existing audiofolders..."
    rm -rf "$BACKUP_DIR"
    mv "$PHONIEBOX_AUDIO_DIR" "$BACKUP_DIR" || {
        echo "ERROR: Failed to back up existing audiofolders."
        exit 1
    }
fi

# Recreate destination
mkdir -p "$PHONIEBOX_AUDIO_DIR" || {
    echo "ERROR: Failed to create destination folder."
    exit 1
}

# Copy new audio files
echo "Copying updated audiofolders into Phoniebox..."
cp -a "$AUDIO_SOURCE_DIR"/. "$PHONIEBOX_AUDIO_DIR"/ || {
    echo "ERROR: Failed to copy audio folders."
    exit 1
}

echo ""
echo "========================================"
echo " SubLim3 audio update completed"
echo "========================================"
exit 0
