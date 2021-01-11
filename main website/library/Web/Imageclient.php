<?php
interface Web_Imageclient{
	/**
	 * Upload image to image server
	 * @param $source_path
	 * @param $destination_path
	 * @param $file_name
	 * @return boolean|string
	 */
	public function addImage($source_path, $destination_path, $file_name);
	/**
	 * delete image from image server
	 * @param $path - path for delete
	 * @return boolean
	 */
	public function deleteImage($path);
}

?>