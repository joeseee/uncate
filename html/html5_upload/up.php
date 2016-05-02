<?php

$targetFolder = '/mon'; // Relative to the root
$fileFormName = 'fileToUpload';

if (!empty($_FILES)) {
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . $targetFolder;
        $tempFile = $_FILES[$fileFormName]['tmp_name'];
        $targetFile = rtrim($targetPath,'/') . '/' . $_FILES[$fileFormName]['name'];

        // Validate the file type
        //$fileTypes = array('jpg','jpeg','gif','png'); // File extensions
        //$fileParts = pathinfo($_FILES['Filedata']['name']);
        //
        //if (in_array($fileParts['extension'],$fileTypes)) {
        move_uploaded_file($tempFile,$targetFile);
        echo '1';
        //} else {
        //      echo 'Invalid file type.';
        //}
}
?>

