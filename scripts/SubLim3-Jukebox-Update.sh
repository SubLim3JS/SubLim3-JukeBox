#!/bin/bash

set -e

SOURCE_DIR="/home/pi/RPi-Jukebox-RFID/htdocs"
OVERRIDES_DIR="/home/pi/SubLim3-JukeBox/overrides/htdocs"

FILES=(
  "lang/lang-en-UK.php"
  "systemInfo.php"
  "settings.php"
  "cardRegisterNew.php"
  "manageFilesFolders.php"
  "search.php"
  "cardEdit.php"
  "index-lcd.php"
  "trackEdit.php"
  "userScripts.php"
  "rfidExportCsv.php"
)

print_section() {
    echo
    echo "========================================"
    echo " $1"
    echo "========================================"
}

require_path() {
    if [ ! -e "$1" ]; then
        echo "ERROR: Required path not found: $1"
        exit 1
    fi
}

copy_file() {
    local rel="$1"
    local src="$SOURCE_DIR/$rel"
    local dst="$OVERRIDES_DIR/$rel"

    require_path "$src"
    mkdir -p "$(dirname "$dst")"
    cp -a "$src" "$dst"
    echo "Copied: $rel"
}

replace_in_file() {
    local file="$1"
    local search="$2"
    local replace="$3"

    if grep -Fq "$search" "$file"; then
        sed -i "s|$search|$replace|g" "$file"
        echo "Updated: $(basename "$file") -> $search"
    else
        echo "Skipped (not found): $(basename "$file") -> $search"
    fi
}

print_section "Generating SubLim3 override files"
echo "Source:    $SOURCE_DIR"
echo "Overrides: $OVERRIDES_DIR"

require_path "$SOURCE_DIR"
mkdir -p "$OVERRIDES_DIR"

print_section "Copying source files"
for rel in "${FILES[@]}"; do
    copy_file "$rel"
done

print_section "Applying SubLim3 branding"

LANG_FILE="$OVERRIDES_DIR/lang/lang-en-UK.php"
SYSTEMINFO_FILE="$OVERRIDES_DIR/systemInfo.php"
SETTINGS_FILE="$OVERRIDES_DIR/settings.php"
CARDREGISTER_FILE="$OVERRIDES_DIR/cardRegisterNew.php"
MANAGE_FILE="$OVERRIDES_DIR/manageFilesFolders.php"
SEARCH_FILE="$OVERRIDES_DIR/search.php"
CARDEDIT_FILE="$OVERRIDES_DIR/cardEdit.php"
INDEXLCD_FILE="$OVERRIDES_DIR/index-lcd.php"
TRACKEDIT_FILE="$OVERRIDES_DIR/trackEdit.php"
USERSCRIPTS_FILE="$OVERRIDES_DIR/userScripts.php"
RFIDEXPORT_FILE="$OVERRIDES_DIR/rfidExportCsv.php"

# lang/lang-en-UK.php
replace_in_file "$LANG_FILE" "\$lang['navBrand'] = \"Phoniebox\";" "\$lang['navBrand'] = \"SubLim3 JukeBox\";"
replace_in_file "$LANG_FILE" "connect to the phoniebox" "connect to the SubLim3 JukeBox"
replace_in_file "$LANG_FILE" "commit your changes to the Phoniebox code :)" "commit your changes to the SubLim3 JukeBox code :)"
replace_in_file "$LANG_FILE" "hook your Phoniebox into a new Wlan network with dynamic IP" "connect your SubLim3 JukeBox to a new Wlan network with dynamic IP"

# systemInfo.php
replace_in_file "$SYSTEMINFO_FILE" "html_bootstrap3_createHeader(\"en\",\"System Info | Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"System Info | SubLim3 JukeBox\",\$conf['base_url']);"
replace_in_file "$SYSTEMINFO_FILE" "Phoniebox Setup" "SubLim3 JukeBox Setup"

# settings.php
replace_in_file "$SETTINGS_FILE" "html_bootstrap3_createHeader(\"en\",\"Settings | Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"Settings | SubLim3 JukeBox\",\$conf['base_url']);"
replace_in_file "$SETTINGS_FILE" "* Phoniebox could send you the IP address over email." "* SubLim3 JukeBox could send you the IP address over email."
replace_in_file "$SETTINGS_FILE" "* Useful if you move your Phoniebox into a new Wifi which" "* Useful if you move your SubLim3 JukeBox into a new WiFi network which"

# cardRegisterNew.php
replace_in_file "$CARDREGISTER_FILE" "html_bootstrap3_createHeader(\"en\",\"RFID Card | Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"RFID Card | SubLim3 JukeBox\",\$conf['base_url']);"

# manageFilesFolders.php
replace_in_file "$MANAGE_FILE" "html_bootstrap3_createHeader(\"en\", \"Files and Folders | Phoniebox\", \$conf['base_url']);" "html_bootstrap3_createHeader(\"en\", \"Files and Folders | SubLim3 JukeBox\", \$conf['base_url']);"

# search.php
replace_in_file "$SEARCH_FILE" "html_bootstrap3_createHeader(\"en\",\"Search | Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"Search | SubLim3 JukeBox\",\$conf['base_url']);"

# cardEdit.php
replace_in_file "$CARDEDIT_FILE" "html_bootstrap3_createHeader(\"en\",\"Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"SubLim3 JukeBox\",\$conf['base_url']);"

# index-lcd.php
replace_in_file "$INDEXLCD_FILE" "html_bootstrap3_createHeader(\"en\",\"Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"SubLim3 JukeBox\",\$conf['base_url']);"

# trackEdit.php
replace_in_file "$TRACKEDIT_FILE" "html_bootstrap3_createHeader(\"en\",\"Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"SubLim3 JukeBox\",\$conf['base_url']);"
replace_in_file "$TRACKEDIT_FILE" "mainly German speaking Phoniebox tinkerers." "mainly German speaking SubLim3 JukeBox tinkerers."

# userScripts.php
replace_in_file "$USERSCRIPTS_FILE" "html_bootstrap3_createHeader(\"en\",\"Phoniebox\",\$conf['base_url']);" "html_bootstrap3_createHeader(\"en\",\"SubLim3 JukeBox\",\$conf['base_url']);"

# rfidExportCsv.php
replace_in_file "$RFIDEXPORT_FILE" "\$filename = \"PhonieboxRFID-\" . date(\"Y-m-d\") . \"_\" . date(\"G-i-s\") . \".csv\";" "\$filename = \"SubLim3-JukeBox-RFID-\" . date(\"Y-m-d\") . \"_\" . date(\"G-i-s\") . \".csv\";"

print_section "Done"
echo "Generated override files in:"
echo "  $OVERRIDES_DIR"
echo
echo "Review them, commit them to GitHub, then deploy with:"
echo "  bash ~/SubLim3-JukeBox/scripts/SubLim3-Jukebox-Update.sh"
