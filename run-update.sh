#!/bin/bash

REPO="/home/pi/SubLim3-JukeBox"

echo "Checking for SubLim3 updates..."
echo ""

cd "$REPO" || {
  echo "ERROR: Cannot access repository folder"
  exit 1
}

echo "Pulling latest files from GitHub..."
git pull -q origin main

echo ""
echo "Running SubLim3 update script..."
echo ""

bash "$REPO/SubLim3-Updates.sh"

echo ""
echo "Update process finished."
