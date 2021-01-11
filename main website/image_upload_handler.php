<?php
/*
Script to handle image uploading/deleting on image server
*/
include("cls_image_handler.php");
define( "SERVER_PATH", "/var/www/html/public" );
$response = array();
try{
$create_path = SERVER_PATH;
$objImageHandler = new cls_image_handler();
switch($_POST['handler']){
	case "add":
		$destination_path = $_POST['destination_path'];
	    $file_name = $_POST['file_name'];
	    $response = $objImageHandler->uploadImage($destination_path, $file_name , $create_path);
	    echo $response;
	    break;
	case "delete":
		$path = $_POST['path'];
		$response = $objImageHandler->deleteImage($path, $create_path);
        echo $response;
        break;
}
}catch(Exception $e){
	ob_clean();
	$response['code'] = 0;
	$response['msg'] = $e->getMessage();
	echo json_encode($response);
	exit();
}
?>