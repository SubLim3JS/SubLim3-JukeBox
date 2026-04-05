<?php
include("inc.header.php");

$audioBaseDir = realpath('/home/pi/RPi-Jukebox-RFID/shared/audiofolders');
$allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac', 'opus'];
$maxFileSize = 1024 * 1024 * 500; // 500 MB per file

$messages = [];
$errors = [];

function sanitizeRelativePath($path) {
    $path = trim((string)$path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#/+#', '/', $path);
    $path = trim($path, '/');

    if ($path === '') {
        return '';
    }

    $parts = explode('/', $path);
    $cleanParts = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '' || $part === '.' || $part === '..') {
            continue;
        }

        // Keep spaces, dashes, underscores, parentheses, apostrophes, ampersands
        $part = preg_replace('/[^A-Za-z0-9 _\-\(\)\&\'.]/', '', $part);
        $part = preg_replace('/\s+/', ' ', $part);
        $part = trim($part);

        if ($part !== '') {
            $cleanParts[] = $part;
        }
    }

    return implode('/', $cleanParts);
}

function sanitizeFileName($filename) {
    $filename = trim((string)$filename);
    $filename = basename($filename);

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);

    $name = preg_replace('/[^A-Za-z0-9 _\-\(\)\&\'.]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name, " .");

    if ($name === '') {
        $name = 'audiofile';
    }

    if ($ext !== '') {
        return $name . '.' . $ext;
    }

    return $name;
}

function getAllAudioFolders($baseDir) {
    $folders = [];

    if (!$baseDir || !is_dir($baseDir)) {
        return $folders;
    }

    $folders[] = '.';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $fullPath = $item->getPathname();
            $relative = substr($fullPath, strlen($baseDir));
            $relative = str_replace('\\', '/', $relative);
            $relative = trim($relative, '/');

            if ($relative !== '') {
                $folders[] = $relative;
            }
        }
    }

    natcasesort($folders);
    return array_values(array_unique($folders));
}

function makeUniqueFilePath($dir, $filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);

    $candidate = $dir . '/' . $filename;
    $counter = 1;

    while (file_exists($candidate)) {
        if ($ext !== '') {
            $candidate = $dir . '/' . $name . ' (' . $counter . ').' . $ext;
        } else {
            $candidate = $dir . '/' . $name . ' (' . $counter . ')';
        }
        $counter++;
    }

    return $candidate;
}

$existingFolders = getAllAudioFolders($audioBaseDir);

$selectedExistingFolder = isset($_POST['existing_folder']) ? sanitizeRelativePath($_POST['existing_folder']) : '.';
$newFolderInput = isset($_POST['new_folder']) ? sanitizeRelativePath($_POST['new_folder']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$audioBaseDir || !is_dir($audioBaseDir)) {
        $errors[] = "Audio base folder was not found.";
    } else {
        $targetRelativeFolder = '.';

        if ($newFolderInput !== '') {
            $targetRelativeFolder = $newFolderInput;
        } elseif ($selectedExistingFolder !== '' && $selectedExistingFolder !== '.') {
            $targetRelativeFolder = $selectedExistingFolder;
        }

        $targetDir = $audioBaseDir;
        if ($targetRelativeFolder !== '.' && $targetRelativeFolder !== '') {
            $targetDir = $audioBaseDir . '/' . $targetRelativeFolder;
        }

        $normalizedTargetDir = str_replace('\\', '/', $targetDir);
        $normalizedBaseDir = str_replace('\\', '/', $audioBaseDir);

        if (strpos($normalizedTargetDir, $normalizedBaseDir) !== 0) {
            $errors[] = "Invalid destination folder.";
        } else {
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0775, true)) {
                    $errors[] = "Failed to create destination folder: " . htmlspecialchars($targetRelativeFolder);
                }
            }

            if (!is_writable($targetDir)) {
                $errors[] = "Destination folder is not writable: " . htmlspecialchars($targetRelativeFolder === '.' ? '/' : $targetRelativeFolder);
            }
        }

        if (!isset($_FILES['audio_files'])) {
            $errors[] = "No files were uploaded.";
        } else {
            $fileNames = $_FILES['audio_files']['name'];
            $fileTmpNames = $_FILES['audio_files']['tmp_name'];
            $fileErrors = $_FILES['audio_files']['error'];
            $fileSizes = $_FILES['audio_files']['size'];

            if (!is_array($fileNames) || count($fileNames) === 0) {
                $errors[] = "No files were selected.";
            } else {
                $uploadedCount = 0;

                for ($i = 0; $i < count($fileNames); $i++) {
                    $originalName = $fileNames[$i];

                    if ($originalName === '') {
                        continue;
                    }

                    $errorCode = $fileErrors[$i];
                    $tmpName = $fileTmpNames[$i];
                    $size = (int)$fileSizes[$i];

                    if ($errorCode !== UPLOAD_ERR_OK) {
                        switch ($errorCode) {
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                $errors[] = htmlspecialchars($originalName) . ": file is too large.";
                                break;
                            case UPLOAD_ERR_PARTIAL:
                                $errors[] = htmlspecialchars($originalName) . ": upload was incomplete.";
                                break;
                            case UPLOAD_ERR_NO_FILE:
                                $errors[] = htmlspecialchars($originalName) . ": no file received.";
                                break;
                            default:
                                $errors[] = htmlspecialchars($originalName) . ": upload failed.";
                                break;
                        }
                        continue;
                    }

                    if ($size <= 0) {
                        $errors[] = htmlspecialchars($originalName) . ": file is empty.";
                        continue;
                    }

                    if ($size > $maxFileSize) {
                        $errors[] = htmlspecialchars($originalName) . ": exceeds 500 MB limit.";
                        continue;
                    }

                    $safeFileName = sanitizeFileName($originalName);
                    $extension = strtolower(pathinfo($safeFileName, PATHINFO_EXTENSION));

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $errors[] = htmlspecialchars($originalName) . ": file type not allowed.";
                        continue;
                    }

                    $destinationPath = makeUniqueFilePath($targetDir, $safeFileName);

                    if (!move_uploaded_file($tmpName, $destinationPath)) {
                        $errors[] = htmlspecialchars($originalName) . ": failed to move uploaded file.";
                        continue;
                    }

                    @chmod($destinationPath, 0664);

                    $relativeDisplayPath = ($targetRelativeFolder === '.' || $targetRelativeFolder === '')
                        ? basename($destinationPath)
                        : $targetRelativeFolder . '/' . basename($destinationPath);

                    $messages[] = "Uploaded: " . htmlspecialchars($relativeDisplayPath);
                    $uploadedCount++;
                }

                if ($uploadedCount > 0) {
                    $messages[] = "Upload complete. Added {$uploadedCount} file(s).";
                    $existingFolders = getAllAudioFolders($audioBaseDir);
                }
            }
        }
    }
}
?>

<div class="container">
  <div class="row">
    <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1">
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title">
            <i class="mdi mdi-upload"></i> Audio Upload
          </h3>
        </div>
        <div class="panel-body">

          <div class="alert alert-info">
            Upload audio files directly into your SubLim3 JukeBox library.
            You can choose an existing folder or create a new one.
          </div>

          <?php if (!empty($messages)) : ?>
            <div class="alert alert-success">
              <?php foreach ($messages as $msg) : ?>
                <div><?php echo $msg; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
              <?php foreach ($errors as $err) : ?>
                <div><?php echo $err; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="" method="post" enctype="multipart/form-data" class="form-horizontal">

            <div class="form-group">
              <label class="col-sm-3 control-label" for="existing_folder">Existing Folder</label>
              <div class="col-sm-9">
                <select class="form-control" name="existing_folder" id="existing_folder">
                  <option value="." <?php echo ($selectedExistingFolder === '.') ? 'selected' : ''; ?>>/ (audiofolders root)</option>
                  <?php foreach ($existingFolders as $folder) : ?>
                    <?php if ($folder === '.') continue; ?>
                    <option value="<?php echo htmlspecialchars($folder); ?>" <?php echo ($selectedExistingFolder === $folder) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($folder); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="help-block">
                  Pick an existing folder, or leave this and create a new folder below.
                </p>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label" for="new_folder">New Folder</label>
              <div class="col-sm-9">
                <input
                  type="text"
                  class="form-control"
                  name="new_folder"
                  id="new_folder"
                  value="<?php echo htmlspecialchars($newFolderInput); ?>"
                  placeholder="Example: Harry Potter/Book 1"
                >
                <p class="help-block">
                  Optional. If filled in, this new folder path will be used instead of the existing folder selection.
                </p>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label" for="audio_files">Audio Files</label>
              <div class="col-sm-9">
                <input
                  type="file"
                  class="form-control"
                  name="audio_files[]"
                  id="audio_files"
                  multiple
                  accept=".mp3,.wav,.ogg,.m4a,.flac,.aac,.opus,audio/*"
                >
                <p class="help-block">
                  Allowed: mp3, wav, ogg, m4a, flac, aac, opus
                </p>
              </div>
            </div>

            <div class="form-group">
              <div class="col-sm-9 col-sm-offset-3">
                <button type="submit" class="btn btn-primary">
                  <i class="mdi mdi-upload"></i> Upload Audio
                </button>
              </div>
            </div>

          </form>
        </div>
      </div>

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">
            <i class="mdi mdi-information-outline"></i> Notes
          </h3>
        </div>
        <div class="panel-body">
          <ul style="margin-bottom:0;">
            <li>Uploaded files are stored in <code>shared/audiofolders</code>.</li>
            <li>If a filename already exists, this page will auto-rename the new file instead of overwriting it.</li>
            <li>If uploads fail, check permissions on <code>/home/pi/RPi-Jukebox-RFID/shared/audiofolders</code>.</li>
            <li>Large uploads may also require PHP upload size limits to be increased.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include("inc.footer.php");
?>
