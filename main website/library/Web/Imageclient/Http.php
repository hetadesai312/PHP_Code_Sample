<?php
class Web_Imageclient_Http implements Web_Imageclient{

	protected $_source_path;

	protected $_destination_path;

	protected $_client;

	/**
	 * @param $source_path
	 * @param $destination_path
	 */
	public function __construct(){
		$this->_client = new Zend_Http_Client();
	}
	/**
	 * Upload image to image server
	 * @param $source_path
	 * @param $destination_path
	 * @param $file_name
	 * @return string
	 */
	public function addImage($source_path, $destination_path, $file_name){

		$this->_client->setUri(Zend_Registry::get("configuration")->images->url . "/image_upload_handler.php");
		$this->_client->setParameterPost('handler', "add");
		$this->_client->setParameterPost('destination_path', $destination_path);
		$this->_client->setParameterPost('file_name', $file_name);

		$this->_client->setFileUpload($source_path, 'UploadedImage');
		$response = $this->_client->request('POST');
		if($response->getStatus() == 200){
			return $response->getBody();
		}else{
			return json_encode(array("code" => 0, "msg" => $response->getStatus() . " : " . $response->getMessage()));
		}
	}
	/**
	 * delete image from image server
	 * @param $path - path for delete
	 * @return string
	 */
	public function deleteImage($path){
		$this->_client->setUri(Zend_Registry::get("configuration")->images->url . "/image_upload_handler.php");
		$this->_client->setParameterPost('handler', "delete");
		$this->_client->setParameterPost('path', $path);

		$response = $this->_client->request('POST');
		if($response->getStatus() == 200){
			return $response->getBody();
		}else{
			return json_encode(array("code" => 0, "msg" => $response->getStatus() . " : " . $response->getMessage()));
		}
	}
	
}