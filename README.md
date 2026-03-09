# SubLim3 JukeBox
SubLim3 JukeBox Files - This will create a folder with SubLim3's custom configs. They will need to be moved to their respective folders within the 'RPi-Jukebox-RFID' folders.

## Development Notes
<p>This is based on <a href="https://github.com/MiczFlor/RPi-Jukebox-RFID">PhonieBox</a>.</p>

## SubLim3 Custom Updates

- Applies the **SubLim3 green theme (#32CD56)** to replace the default Phoniebox blue UI  
- Converts **player control buttons to transparent with green icons** and green hover highlight  
- Updates **navigation tabs, progress bars, WiFi SSID banner, sliders, and buttons** to match the SubLim3 theme  
- Adds a **Run SubLim3 Update button** to the System Info page for one-click updates from the web interface  
- Installs an **automated update system** using `update.php`, `run-update.sh`, and `SubLim3-Updates.sh`  
- Enables **GitHub-based updates** using `git pull` to retrieve the latest SubLim3 repository changes  
- Automatically **backs up existing Phoniebox files** before replacing them (`filename-BACKUP`)  
- Updates core UI files including `func.php`, `index.php`, `search.php`, `settings.php`, and `systemInfo.php`  
- Installs custom styling files `custom-green.css` and `circle.css` into the Phoniebox CSS directory  
- Adds a **custom language file** to modify UI text such as *SubLim3-JukeBox Setup*  
- Installs a **version tracking file** so the current SubLim3 build version is displayed on the Info page

___________________________________________________________________________________________________________________

## SubLim3 Update Code
Executes at the root folder.
```bash
cd /home/pi
git clone https://github.com/SubLim3JS/SubLim3-JukeBox.git
cd SubLim3-JukeBox
bash SubLim3-Updates.sh
```

___________________________________________________________________________________________________________________

## SubLim3 Update Code
Executes at the root folder.
```bash
cd /home/pi/SubLim3-JukeBox
git pull -q origin main
bash SubLim3-Updates.sh
```

_____________________________________________________________________________________________________________________________________________________________________

# Experiment Modules

<a href="https://drive.google.com/file/d/1kt7sL9atEKT0TiiqjVMpSjOLNFIgqLb4/view?usp=sharing">Download the Android App.</a>
