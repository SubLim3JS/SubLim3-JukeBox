#!/bin/bash

set -u

THEME_FILE="/home/pi/RPi-Jukebox-RFID/settings/theme.conf"
OUT_FILE="/home/pi/RPi-Jukebox-RFID/htdocs/_assets/css/custom-theme-runtime.css"

mkdir -p "$(dirname "$OUT_FILE")"

theme="green"

if [ -f "$THEME_FILE" ]; then
  theme_line="$(grep '^theme=' "$THEME_FILE" 2>/dev/null | head -n1)"
  if [ -n "$theme_line" ]; then
    theme="${theme_line#theme=}"
  fi
fi

case "$theme" in
  green)
    primary="#32CD56"
    hover="#28a745"
    light="#7dff9a"
    text="#ffffff"
    ;;
  blue)
    primary="#2196F3"
    hover="#1976D2"
    light="#64B5F6"
    text="#ffffff"
    ;;
  red)
    primary="#F44336"
    hover="#D32F2F"
    light="#EF9A9A"
    text="#ffffff"
    ;;
  purple)
    primary="#9C27B0"
    hover="#7B1FA2"
    light="#CE93D8"
    text="#ffffff"
    ;;
  orange)
    primary="#FF9800"
    hover="#F57C00"
    light="#FFB74D"
    text="#ffffff"
    ;;
  cyan)
    primary="#00BCD4"
    hover="#0097A7"
    light="#80DEEA"
    text="#ffffff"
    ;;
  white)
    primary="#FFFFFF"
    hover="#E0E0E0"
    light="#F5F5F5"
    text="#222222"
    ;;
  *)
    primary="#32CD56"
    hover="#28a745"
    light="#7dff9a"
    text="#ffffff"
    ;;
esac

cat > "$OUT_FILE" <<EOF
:root {
  --primary-color: $primary;
  --primary-hover: $hover;
  --primary-light: $light;
  --primary-text: $text;
}
EOF

chmod 644 "$OUT_FILE"

echo "[OK] Theme CSS generated: $theme"
