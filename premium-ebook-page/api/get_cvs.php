<?php
$target_dir = __DIR__ . "/../uploads/cvs/";
$cv_files = [];

if(file_exists($target_dir)) {
    $cv_files = array_values(array_diff(scandir($target_dir), array('.', '..')));
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
echo json_encode($cv_files);
?>
