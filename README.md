# SubLim3 JukeBox
SubLim3 JukeBox Files - This will create a folder with SubLim3's custom configs. They will need to be moved to their respective folders within the 'RPi-Jukebox-RFID' folders.

## Development Notes
<p>This is based on <a href="https://github.com/MiczFlor/RPi-Jukebox-RFID">PhonieBox</a>.</p>

_____________________________________________________________________________________________________________________________________________________________________

## OS Update Code
Execute at the root folder.
```bash
sudo apt-get update && sudo apt-get upgrade
```
_____________________________________________________________________________________________________________________________________________________________________

## MagicMirror Installation Script
Execute at the root folder.
```bash
bash -c  "$(curl -sL https://github.com/SubLim3JS/SubLim3-JukeBox/blob/main/SubLim3-Updates.sh)"
```
_____________________________________________________________________________________________________________________________________________________________________

## SubLim3 Installation Code
Execute at the root folder.
```bash
cd ~
git clone https://github.com/SubLim3JS/SubLim3-JukeBox
cd SubLim3-JukeBox
. SubLim3-Updates.sh
```

_____________________________________________________________________________________________________________________________________________________________________

# Experiment Modules

<a href="href="https://github.com/MiczFlor/RPi-Jukebox-RFID">Future Links</a>
