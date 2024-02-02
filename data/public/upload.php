<?php
header("Access-Control-Allow-Origin: *");

if (empty($_FILES) || $_FILES["file"]["error"]) {
	die(null);
}

// $fileName = $_FILES["file"]["name"];
$fileName = $_FILES["file"];
// move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/$fileName");

die( json_encode($fileName));
?>