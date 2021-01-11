<?php
/**
 * Image Upload Service for Artist Image(s) processing
 * @author The Orchard Inc.
 */
class Service_ManageImageAssets{

	/**
	 * Path to copy image
	 * @var string
	 */
	protected $_destination_path;

	protected $_http_client_object;

	protected $_filesize;

	protected $_mime_type;

	protected $_mailto;

	/**
	 * class constructor
	 */
	public function __construct(){
		$this->_mailto = array("hdesai.php@gmail.com"=>"Web Dev", "hdesai.php@gmail.com"=>"IT");
		$this->setHttpClientObject();
	}

	/**
	 * add new image
	 *
	 * @param Model_ImageAsset $imageAsset
	 * @param string $filename
	 * @return Exception/string
	 */
	public function addImage(Model_ImageAssets $imageAsset, $filename = null){

		/** Create Path to copy image in image web server */
		$_destination_path = "";
		if(!empty($imageAsset->ImageCategory->path)){
			$image_category_path = $this->checkPath($imageAsset->ImageCategory->path);
			$_destination_path .= $image_category_path;
			if($filename){
				$image_info = getimagesize($imageAsset->path . $filename);
				$this->_mime_type = $image_info['mime'];
				$this->_filesize = filesize($imageAsset->path . $filename);
			}
		}

		/** Generate path category wise */
		$objManageImage = self::getCategoryModel($imageAsset); //$imageAsset->ImageCategory->id);


		$image_category_type_id = $objManageImage->getCategoryIdFromAsset($imageAsset);

		if(empty($image_category_type_id)){
			throw new Exception("Invalid image category");
		}

		$sub_destination_path = $objManageImage->generateImageAssetsPath($image_category_type_id);
		if(empty($sub_destination_path)){
			throw new Exception("Invalid image category path");
		}
		$sub_destination_path = $this->checkPath($sub_destination_path);
		$_destination_path .= $sub_destination_path;

		/**  Create file name */
		if(!empty($imageAsset->id)){
			$file_name = empty($filename) ? $imageAsset->filename : $filename;
			$image_file_name = $imageAsset->id . "." . pathinfo($imageAsset->path . $file_name, PATHINFO_EXTENSION);
		}

		$path_for_remove = $imageAsset->path;
		$_destination_path = str_replace("//", "/", $_destination_path);

		/** Copy iamge file to dedicated web server */
		$objHttp = $this->getHttpClientObject();
		$valReturn = $objHttp->addImage($imageAsset->path . $imageAsset->filename, $_destination_path, $image_file_name);
		$valReturnArr = json_decode($valReturn, true);
		if($valReturnArr['code'] == 1){
			$image_date = date('Y-m-d H:i:s');
			$imageAsset->path = $sub_destination_path;
			$imageAsset->filename = $image_file_name;
			$imageAsset->mime_type = $this->_mime_type;
			$imageAsset->file_size = $this->_filesize;
			$imageAsset->date_added = $image_date;
			$imageAsset->date_modified = $image_date;
			$imageAsset->save();

			/** unlink the /tmp/ file */
			unlink($path_for_remove . $file_name);

			return Zend_Registry::get("configuration")->images->url . $_destination_path . $image_file_name;
		}else{
			$objManageImage = self::getCategoryModel($imageAsset);
			$objManageImage->deleteCategoryAsset($imageAsset);
			$imageAsset->delete();
			$this->sendEmailNotification("Image Service Error", "Copy to image server failed. " . $valReturnArr['msg']);
			throw new Exception("Copy to image server failed. " . $valReturnArr['msg']);
		}
	}

	/**
	 * get image
	 *
	 * @param int $id
	 * @return Exception|string
	 */
	public function getImage($id){

		$path = self::getImagePath($id);
		if(!empty($path)){
			return Zend_Registry::get("configuration")->images->url . $path;
		}else{
			throw new Exception("Invalid path");
		}
	}

	/**
	 * Delete image
	 *
	 * @param Model_ImageAssets $imageAsset
	 * @return Exception|bool
	 */
	public function deleteImage(Model_ImageAssets $imageAsset){

		/** Get the path for deletion */
		$path = self::getImagePath($imageAsset->id);
		if(!empty($path)){
			$objHttp = $this->getHttpClientObject();
			$valReturn = $objHttp->deleteImage($path);
			$valReturnArr = json_decode($valReturn, true);
			if($valReturnArr['code'] == 1){

				/** Delete from database */
				$objManageImage = self::getCategoryModel($imageAsset);
				$objManageImage->deleteCategoryAsset($imageAsset);
				$imageAsset->delete();
				return true;

			}else{

				throw new Exception("delete image from image server failed. " . $valReturnArr['msg']);
			}
		}else{
			throw new Exception("Invalid path for deletion");
		}
	}

	/**
	 * get image path
	 *
	 * @param int $id
	 * @return Exception|string
	 */
	protected function getImagePath($id){
		$imageAsset = Model_ImageAssets::getImageAssetsByID($id);

		/** Check image asset path is set or not */
		if(empty($imageAsset->path)){
			throw new Exception("Invalid image asset path");
		}

		/**Check image category path is set or not */
		$image_category_path = $imageAsset->ImageCategory->path;
		if(empty($image_category_path)){
			throw new Exception("Invalid image category path" . $image_category_path);
		}
		$image_category_path = $this->checkPath($image_category_path);

		/** Check filename is set or not */
		if(empty($imageAsset->filename)){
			throw new Exception("Invalid file name" . " " . $imageAsset->filename);
		}

		/** Generate image path */
		$construct_path = $image_category_path . $imageAsset->path . $imageAsset->filename;
		$construct_path = str_replace("//", "/", $construct_path);
		return $construct_path;
	}

	/**
	 * Get the specific model object instance based on image category ID
	 *
	 * @param int $category_id
	 * @return Web_Imageassets_Interface
	 * @throws Exception if image category not found.
	 */
	protected static function getCategoryModel(Model_ImageAssets $imageAsset){

		switch($imageAsset->category_id){

			/** For Artist category type */
			case 2:
				return $imageAsset->ArtistPhotos;
				break;
			case 3:
				return $imageAsset->ApiProductsScreenshot;
				break;
			case 4:
				return $imageAsset->ApiProductsVersion;
				break;
			case 5:
				return $imageAsset->ArtistThumbnails;
				break;
			case 6:
				return $imageAsset->ArtistWebImages;
				break;

			default:
				throw new Exception("Invalid image category");
				break;
		}
	}

	/**
	 * Check pre and post appended "\" in path
	 * @param $path
	 * @return String
	 */
	protected function checkPath($path){
		$path = trim($path);
		$path = (substr($path, 0, 1) == "/") ? $path : "/" . $path;
		$path = (substr($path, -1) == "/") ? $path : $path . "/";
		return $path;
	}

	protected function setHttpClientObject(){
		$this->_http_client_object = new Web_Imageclient_Http();
	}

	protected function getHttpClientObject(){
		return $this->_http_client_object;
	}

	/**
	 * Email if error occurs on image server
	 * @param $subject
	 * @param $bodyMsg
	 */
	protected function sendEmailNotification($subject, $bodyMsg = null){
		$bodyText = "";
		if($bodyMsg)
			$bodyText .= "\n\n$bodyMsg";

		Web_Mail::sendemail($this->_mailto, $subject, $bodyText, false);
	}
	
}