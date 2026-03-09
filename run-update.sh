#!/bin/bash

REPO="/home/pi/SubLim3-JukeBox"

echo "========================================"
echo " SubLim3 JukeBox Update Utility"
echo "========================================"
echo ""

echo "Checking repository location..."
cd "$REPO" || {
    echo "ERROR: Cannot access $REPO"
    exit 1
}

echo "Repository found."
echo ""

echo "Pulling latest updates from GitHub..."
echo ""

git pull -q origin main

if [ $? -ne 0 ]; then
    echo "ERROR: Git pull failed."
    exit 1
fi

echo "Repository updated successfully."
echo ""

echo "Running SubLim3 update script..."
echo ""

bash "$REPO/SubLim3-Updates.sh"

if [ $? -ne 0 ]; then
    echo ""
    echo "WARNING: Update script completed with errors."
    exit 1
fi

echo ""
echo "========================================"
echo " SubLim3 JukeBox update completed"
echo "========================================"

exit 0
