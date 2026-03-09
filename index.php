<?php

include("inc.header.php");

/*******************************************
* START HTML
*******************************************/

html_bootstrap3_createHeader("en","SubLim3 JukeBox",$conf['base_url']);

?>
<style>
.filterDiv {
  display: none;
}

.filtershow {
  display: block;
}

.filtercontainer {
  margin-top: 20px;
  overflow: hidden;
}

/* Style the buttons */
.filterbtn {
  border: none;
  outline: none;
  padding: 12px 16px;
  margin-bottom: 3px;
  background-color: #464545;
  color: white;
  cursor: pointer;
  border-top-right-radius: 4px;
  border-top-left-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius: 4px;
}

.filterbtn:hover {
  background-color: #f1f1f1;
  color: black;
}

.filterbtn.active {
  background-color: #0ce3ac;
  color: white;
}
</style>
<body>
  <div class="container">

<?php
include("inc.navigation.php");

if($debug == "true") {
    print "<pre>";
    print "_POST: \n";
    print_r($_POST);
    print "</pre>";
}

?>

    <div class="row playerControls">
      <div class="col-lg-12">
<?php
/*
* Do we need to voice a warning here?
*/
if(isset($warning)) {
    print '<div class="alert alert-warning">'.$warning.'</div>';
}
print '<div id="api-alert" class="alert alert-warning" style="display: none"></div>';
include("inc.controlPlayer.php");
?>
      </div><!-- / .col-lg-12 -->
    </div><!-- /.row -->
<?php
// show currently played track

    print '
    <div class="row">
        <div class="col-lg-12">';
include("inc.loadedPlaylist.php");
    print '
        </div><!-- / .col-lg-12 -->
    </div><!-- /.row -->';
?>
    <div class="row">
      <div class="col-lg-12">
<?php
include("inc.setVolume.php");
?>      
    </div><!-- ./col-lg-12 -->
    </div><!-- ./row -->

    <div class="row">
      <div class="col-lg-12">
        <h3><?php print $lang['indexAvailAudio']; ?></h3>
			<div id="myfilterBtnContainer">
				  <button class="filterbtn active" onclick="filterSelection('all')"> <?php print $lang['filterall']; ?></button>
				  <button class="filterbtn" onclick="filterSelection('file')"> <?php print $lang['filterfile']; ?></button>
				  <button class="filterbtn" onclick="filterSelection('livestream')"> <?php print $lang['filterlivestream']; ?></button>
			</div>
      <div class="filtercontainer row">
<?php

// read the shortcuts used
$shortcutstemp = array_filter(glob($conf['base_path'].'/shared/shortcuts/*'), 'is_file');
$shortcuts = array(); // the array with pairs of ID => foldername
// read files' content into array
foreach ($shortcutstemp as $shortcuttemp) {
    $shortcuts[basename($shortcuttemp)] = trim(file_get_contents($shortcuttemp));
}
//print "<pre>"; print_r($shortcutstemp); print "</pre>"; //???
//print "<pre>"; print_r($shortcuts); print "</pre>"; //???

// read the subfolders of $Audio_Folders_Path
$audiofolders = array_filter(glob($Audio_Folders_Path.'/*'), 'is_dir');
usort($audiofolders, 'strcasecmp');

// counter for ID of each folder, increased when used (within inc.viewFolderTree.php)
$idcounter = 0;

// go through all folders
foreach($audiofolders as $audiofolder) {
    include('inc.viewFolderTree.php');
}

?>

      </div><!-- / .col-lg-12 -->
	<script>
		filterSelection("all")
		function filterSelection(c) {
		  var x, i;
		  x = document.getElementsByClassName("filterDiv");
		  if (c == "all") c = "";
		  for (i = 0; i < x.length; i++) {
			w3RemoveClass(x[i], "filtershow");
			if (x[i].className.indexOf(c) > -1) w3AddClass(x[i], "filtershow");
		  }
		}

		function w3AddClass(element, name) {
		  var i, arr1, arr2;
		  arr1 = element.className.split(" ");
		  arr2 = name.split(" ");
		  for (i = 0; i < arr2.length; i++) {
			if (arr1.indexOf(arr2[i]) == -1) {element.className += " " + arr2[i];}
		  }
		}

		function w3RemoveClass(element, name) {
		  var i, arr1, arr2;
		  arr1 = element.className.split(" ");
		  arr2 = name.split(" ");
		  for (i = 0; i < arr2.length; i++) {
			while (arr1.indexOf(arr2[i]) > -1) {
			  arr1.splice(arr1.indexOf(arr2[i]), 1);     
			}
		  }
		  element.className = arr1.join(" ");
		}

		// Add active class to the current button (highlight it)
		var filterbtnContainer = document.getElementById("myfilterBtnContainer");
		var btns = filterbtnContainer.getElementsByClassName("filterbtn");
		for (var i = 0; i < btns.length; i++) {
		  btns[i].addEventListener("click", function(){
			var current = document.getElementsByClassName("active");
			current[0].className = current[0].className.replace(" active", "");
			this.className += " active";
		  });
		}
	</script>
</body>

<script src="js/jukebox.js">
</script>
<script>
	JUKEBOX.lang = <?php echo json_encode($lang );?>
</script>
</html>
