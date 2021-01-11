<?php
/**
 * main artist photos controller for artist photos artist builder section
 * @author khinds
 */
class Alw_ArtistphotosController extends Web_Controller_Alw_Artist {

	/**
	 * initialize controller with its various preset components
	 */
	public function init() {
		parent::init();
		/* Initialize action controller here */
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('convertartistimage', 'json')->addActionContext('updateprimaryartist', 'json')->addActionContext('editcaption', 'json')->addActionContext('cleanuptempfiles', 'json')->initContext();
	}

	/**
	 * main artist photos section index
	 */
	public function indexAction() {
		$this->checkArtistVendor();
		$this->view->artist = $this->getArtist();
		$artistPhotos = Model_ArtistPhotos::getArtistPhotosByArtistID($this->getArtist()->artist_id);
		$this->view->artistPhotos = $artistPhotos;
		$this->view->image_domain = Zend_Registry::get("configuration")->images->url;
		$this->view->images_per_row = 3;
	}

	/**
	 * modal window action to show the upload multiple images form
	 */
	public function uploadformmodalAction() {
		$this->checkArtistVendor();
		$this->view->artist = $this->getArtist();
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$this->view->modal_window_id = $this->_request->getParam("modalWindowID", "");
		$this->view->placeholder("title")->set($this->view->translate->_("Upload Artist Image(s)"));
	}

	/**
	 * AJAX response call back function for the HTML5 multiple upload plugin with the progress bar
	 */
	public function uploadAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$multipleFileUploader = new Web_Controller_Action_Helper_Multiplefileupload();
		print $multipleFileUploader->uploadMultipleFiles(array('tiff', 'tif', 'jpg', 'jpeg', 'gif', 'png'));
	}

	/**
	 * AJAX call back for converting and processing images to final Orchard storage
	 */
	public function convertartistimageAction() {
		$new_image_url = "";
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);

		$this->checkArtistVendor();

		$fileData = $this->_request->getPost();
		$filename = $fileData['filename'];

		$filename = "/tmp/" . $filename;
		chmod($filename, 0777);

		/** get file information of the asset filename for processing */
		$fileInfo = pathinfo($filename);
		$destinationFile = "/tmp/" . $fileInfo['filename'] . "." . $fileInfo['extension'];

		try {
			if(file_exists($filename)) {
				$finalFileName = $fileInfo['filename'];
				//$imagemagick = new Web_Imagemagick($filename, $destinationFile, "-colorspace RGB -density 72x72 -antialias -quality 100");
				/** Generate thumbnail(s) */
				$thumbnail_prefix = "thumbnail_";
				$thumbnailDestinationFile = "/tmp/" . $thumbnail_prefix . $fileInfo['filename'] . ".jpg";
				$image_size = getimagesize($filename);

				if(!($image_size[0] < 150 && $image_size[1] < 150)){
					if($image_size[0]>$image_size[1]){
						$imagemagickThumbnail = new Web_Imagemagick($filename, $thumbnailDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 150x -background white -flatten");
					}else if ($image_size[0]<$image_size[1]){
						$imagemagickThumbnail = new Web_Imagemagick($filename, $thumbnailDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize x150 -background white -flatten");
					}else {
						$imagemagickThumbnail = new Web_Imagemagick($filename, $thumbnailDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 150x -background white -flatten");
					}
				} else {
					$imagemagickThumbnail = new Web_Imagemagick($filename, $thumbnailDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -background white -flatten");
				}

				/** Generate Web image */
				$web_image_prefix = "web_";
				$webImageDestinationFile = "/tmp/" . $web_image_prefix . $fileInfo['filename'] . ".jpg";

				if(!($image_size[0] < 618 && $image_size[1] < 464)){
					if($image_size[0]>$image_size[1]){
						$imagemagickWebImage = new Web_Imagemagick($filename, $webImageDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 618x -background white -flatten");
					}else if ($image_size[0]<$image_size[1]){
						$imagemagickWebImage = new Web_Imagemagick($filename, $webImageDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize x618 -background white -flatten");
					} else {
						$imagemagickWebImage = new Web_Imagemagick($filename, $webImageDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 618x -background white -flatten");
					}
				}else{
					$imagemagickWebImage = new Web_Imagemagick($filename, $webImageDestinationFile, "-colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -background white -flatten");
				}

				/** convert image and get it's file size and other information */
				$imagemagickThumbnail->convert();
				$imagemagickWebImage->convert();
				$completeImageInfo = getimagesize($destinationFile);
				$completeThumbnailImageInfo = getimagesize($thumbnailDestinationFile);
				$completeWebImageInfo = getimagesize($webImageDestinationFile);
				list($width, $height, $type, $attr) = $completeImageInfo;
				list($thumb_width, $thumb_height, $thumb_type, $thumb_attr) = $completeThumbnailImageInfo;
				list($web_width, $web_height, $web_type, $web_attr) = $completeWebImageInfo;
				$fileSize = filesize($destinationFile);
				$thumb_fileSize = filesize($thumbnailDestinationFile);
				$web_fileSize = filesize($webImageDestinationFile);

				$artistPhoto = new Model_ArtistPhotos();
				$artistPhoto->artist_id = $this->getArtist()->artist_id;
				$artistPhoto->ImageAssets->category_id = 2;
				$artistPhoto->ImageAssets->path = "/tmp/";
				$artistPhoto->ImageAssets->filename = $finalFileName . "." . $fileInfo['extension'];
				$artistPhoto->ImageAssets->height = $height;
				$artistPhoto->ImageAssets->width = $width;
				$artistPhoto->ImageAssets->mime_type = $completeImageInfo['mime'];
				$artistPhoto->ImageAssets->file_size = $fileSize;
				$artistPhoto->ImageAssets->date_added = date("Y-m-d H:i:s");
				$artistPhoto->ImageAssets->date_modified = date("Y-m-d H:i:s");
				$artistPhoto->save();

				$ManageImageAssets = new Service_ManageImageAssets();
				$new_image_url = $ManageImageAssets->addImage($artistPhoto->ImageAssets, $finalFileName . "." . $fileInfo['extension']);
				$this->updateArtistCompletion($this->getArtist()->artist_id, Model_ArtistInfoProfileSections::PHOTOS, 1);

				/** Thumbnail only created if above addimage is successful */
				if($new_image_url) {
					$artistThumbnail = new Model_ArtistThumbnails();
					$artistThumbnail->artist_photo_id = $artistPhoto->artist_photo_id;
					$artistThumbnail->ImageAssets->category_id = 5;
					$artistThumbnail->ImageAssets->path = "/tmp/";
					$artistThumbnail->ImageAssets->filename = $thumbnail_prefix . $finalFileName . ".jpg";
					$artistThumbnail->ImageAssets->height = $thumb_height;
					$artistThumbnail->ImageAssets->width = $thumb_width;
					$artistThumbnail->ImageAssets->mime_type = $completeThumbnailImageInfo['mime'];
					$artistThumbnail->ImageAssets->file_size = $thumb_fileSize;
					$artistThumbnail->ImageAssets->date_added = date("Y-m-d H:i:s");
					$artistThumbnail->ImageAssets->date_modified = date("Y-m-d H:i:s");
					$artistThumbnail->save();

					$ManageThumbnailImageAssets = new Service_ManageImageAssets();
					$ManageThumbnailImageAssets->addImage($artistThumbnail->ImageAssets, $thumbnail_prefix . $finalFileName . ".jpg" );

					$artistWebImage = new Model_ArtistWebImages();
					$artistWebImage->artist_photo_id = $artistPhoto->artist_photo_id;
					$artistWebImage->ImageAssets->category_id = 6;
					$artistWebImage->ImageAssets->path = "/tmp/";
					$artistWebImage->ImageAssets->filename = $web_image_prefix . $finalFileName . ".jpg";
					$artistWebImage->ImageAssets->height = $web_height;
					$artistWebImage->ImageAssets->width = $web_width;
					$artistWebImage->ImageAssets->mime_type = $completeWebImageInfo['mime'];
					$artistWebImage->ImageAssets->file_size = $web_fileSize;
					$artistWebImage->ImageAssets->date_added = date("Y-m-d H:i:s");
					$artistWebImage->ImageAssets->date_modified = date("Y-m-d H:i:s");
					$artistWebImage->save();

					$ManageWebImageAssets = new Service_ManageImageAssets();
					$ManageWebImageAssets->addImage($artistWebImage->ImageAssets, $web_image_prefix . $finalFileName . ".jpg");

					$artist = Model_ArtistInfo::getArtistById($artistPhoto->artist_id);
					$socialProfile = new Service_Socialprofile($artist);
					$socialProfileAction = Service_Socialprofile::ACTION_ADD;
					$photosCollection = new Doctrine_Collection("Model_ArtistPhotos");
					$photosCollection->add($artistPhoto);
					$socialPreferences = $socialProfile->getSocialProfilePreferences(Service_Socialprofile::RESOURCE_PHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE);
					if($socialPreferences->count()){
						//if there is a new album created before, create album id first.
						$facebookConnection = Model_ArtistSocialConnections::findByArtistAndSite($artistPhoto->artist_id,Model_Sites::FACEBOOK);
						$old_new_album_preference = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"new_album_name");
						if($old_new_album_preference){
							$facebooktoken = new Zend_Oauth_Token_Access();
							$token = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"access_token")->preference_value;
							$token_secret = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"access_token_secret")->preference_value;
							$page_id = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"page_id")->preference_value;
							$facebooktoken->setToken($token);
							$facebooktoken->setTokenSecret($token_secret);
							$facebookservice = new Web_Service_Facebook($facebooktoken); // get from `artist_social_connections`
							$album_id = $facebookservice->photoAlbumAdd(array("name" => $old_new_album_preference->preference_value, "privacy" => "EVERYONE"), $page_id);
							$facebookConnection->updatePreferences("album", $album_id);
							//remove the new_album_name from preference
							$old_new_album_preference->delete();
						}
						$myspaceConnection = Model_ArtistSocialConnections::findByArtistAndSite($artistPhoto->artist_id,Model_Sites::MYSPACE);
						$old_new_album_preference = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($myspaceConnection->id,"myspace_new_album_name");
						if($old_new_album_preference){
							$myspacetoken = $myspaceConnection->getAccessToken();
							$config = Zend_Registry::get('configuration')->social_profile->Myspace->toArray();
							$config['siteUrl'] = $config['site_url'];
							$config['consumerKey'] = $config['consumer_key'];
							$config['consumerSecret'] = $config['consumer_secret'];
							$config['accessToken'] = $myspacetoken;
							$myspaceservice = new Web_Service_Myspace($config); // get from `artist_social_connections`
							$album_id = $myspaceservice->albumCreate($old_new_album_preference->preference_value);
							$myspaceConnection->updatePreferences("album", $album_id);
							//remove the new_album_name from preference
							$old_new_album_preference->delete();
						}
						$socialProfile->enqueueForPublish(Service_Socialprofile::RESOURCE_PHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE, $socialPreferences, $photosCollection);
					}
				}
			}

		} catch(Exception $ex) {
			$artistPhoto->delete();
			if($new_image_url) {
				$artistThumbnail->delete();
				$artistWebImage->delete();
			}
			$this->view->file_error = $ex->getMessage();
			$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		}
	}

	/**
	 * update the main artist image from ajax call here
	 */
	public function updateprimaryartistAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$artist_id = $this->_request->getParam('artist_id');
		$artist_photo_id = $this->_request->getParam("artist_photo_id");

		if($artist_photo_id && $artist_id) {
			$artist = Model_ArtistInfo::getArtistById($artist_id);
			$artist->primary_photo_id = $artist_photo_id;
			$artist->save();

			$imgPath = Zend_Registry::get("configuration")->images->url . $artist->ArtistInfoPhotos->ArtistThumbnails->ImageAssets->ImageCategory->path . $artist->ArtistInfoPhotos->ArtistThumbnails->ImageAssets->path .
			$artist->ArtistInfoPhotos->ArtistThumbnails->ImageAssets->filename;
			$pathInfo = pathinfo($imgPath);

			/** get correct aspect ratio for image from image utility */
			$imageStats = Web_ImageUtility::getDimension($pathInfo['dirname'] . "/", $pathInfo['basename']);
			list($width, $height) = Web_ImageUtility::scaleImage($imageStats[0], $imageStats[1], 80, 80);

			$this->view->img_path = $imgPath;
			$this->view->width = $width;
			$this->view->height = $height;

			$artist = Model_ArtistInfo::getArtistById($artist_id);
			$socialProfile = new Service_Socialprofile($artist);
			$socialProfileAction = Service_Socialprofile::ACTION_UPDATE;
			$photosCollection = new Doctrine_Collection("Model_ArtistPhotos");
			$artistPhoto = Model_ArtistPhotos::getArtistPhotoById($artist->primary_photo_id);
			$photosCollection->add($artistPhoto);
			$socialPreferences = $socialProfile->getSocialProfilePreferences(Service_Socialprofile::RESOURCE_PROFILEPHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE);
			if($socialPreferences->count()){
				//if there is a new album created before, create album id first.
				$facebookConnection = Model_ArtistSocialConnections::findByArtistAndSite($artistPhoto->artist_id,Model_Sites::FACEBOOK);
				$old_new_album_preference = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"new_album_name");
				if($old_new_album_preference){
					$facebooktoken = new Zend_Oauth_Token_Access();
					$token = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"access_token")->preference_value;
					$token_secret = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"access_token_secret")->preference_value;
					$page_id = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($facebookConnection->id,"page_id")->preference_value;
					$facebooktoken->setToken($token);
					$facebooktoken->setTokenSecret($token_secret);
					$facebookservice = new Web_Service_Facebook($facebooktoken); // get from `artist_social_connections`
					$album_id = $facebookservice->photoAlbumAdd(array("name" => $old_new_album_preference->preference_value, "privacy" => "EVERYONE"), $page_id);
					$facebookConnection->updatePreferences("album", $album_id);
					//remove the new_album_name from preference
					$old_new_album_preference->delete();
				}
				$myspaceConnection = Model_ArtistSocialConnections::findByArtistAndSite($artistPhoto->artist_id,Model_Sites::MYSPACE);
				$old_new_album_preference = Model_ArtistSocialConnectionPreferences::getPreferenceByConnectionIdAndKey($myspaceConnection->id,"myspace_new_album_name");
				if($old_new_album_preference){
					$myspacetoken = $myspaceConnection->getAccessToken();
					$config = Zend_Registry::get('configuration')->social_profile->Myspace->toArray();
					$config['siteUrl'] = $config['site_url'];
					$config['consumerKey'] = $config['consumer_key'];
					$config['consumerSecret'] = $config['consumer_secret'];
					$config['accessToken'] = $myspacetoken;
					$myspaceservice = new Web_Service_Myspace($config); // get from `artist_social_connections`
					$album_id = $myspaceservice->albumCreate($old_new_album_preference->preference_value);
					$myspaceConnection->updatePreferences("album", $album_id);
					//remove the new_album_name from preference
					$old_new_album_preference->delete();
				}
				$socialProfile->enqueueForPublish(Service_Socialprofile::RESOURCE_PROFILEPHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE, $socialPreferences, $photosCollection);
			}
		}
	}

	/**
	 * delete artist photo via confirmed ajax modal window click
	 */
	public function deleteimagemodalAction() {
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$modal_window_id = $this->_request->getParam("modalWindowID", "");
		$artist_photo_id = $this->_request->getParam("artist_photo_id", "");
		$this->view->modal_window_id = $modal_window_id;
		$this->view->placeholder("buttons")->set(
			"<a class=\"rounded\" id=\"no_button\" onclick=\"$modal_window_id.close();\"><span>" . $this->view->translate->_('No') . "</span></a>
			 <a class=\"rounded\" id=\"yes_button\" href=\"/alw/artistphotos/deleteimage/artist_photo_id/" . $artist_photo_id . "\"><span>" .
		$this->view->translate->_('Yes') . "</span></a>
			 <a class=\"rounded\" id=\"wait_button\" style=\"display:none;\"><span>" . $this->view->translate->_(
					'Please Wait') . "...</span></a>");
	}

	/**
	 * delete image from the DB and from the image server upon user request
	 */
	public function deleteimageAction() {

		try {
			if(!($artist_photo_id = $this->getRequest()->getParam('artist_photo_id')) || !($artistPhoto = Model_ArtistPhotos::getArtistPhotoById(
			$artist_photo_id))) {
				throw new Exception("Couldn't delete image.", 0);
			}
		} catch(Exception $e) {
			$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		}

		$this->setArtist($artistPhoto->artist_id);
		$this->checkArtistVendor();

		$artist = Model_ArtistInfo::getArtistById($artistPhoto->artist_id);
		$socialProfile = new Service_Socialprofile($artist);
		$socialProfileAction = Service_Socialprofile::ACTION_DELETE;
		$photosCollection = new Doctrine_Collection("Model_ArtistPhotos");
		$photosCollection->add($artistPhoto);
		$socialPreferences = $socialProfile->getSocialProfilePreferences(Service_Socialprofile::RESOURCE_PHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE);
		if($socialPreferences->count()){
			$socialProfile->enqueueForPublish(Service_Socialprofile::RESOURCE_PHOTO,$socialProfileAction,Service_Socialprofile::METHOD_SINGLE, $socialPreferences, $photosCollection);
		}

		try {
			$artistThumbnail = Model_ArtistThumbnails::getArtistThumbnailsByArtistPhotoID($artist_photo_id);
			if(is_object($artistThumbnail->ImageAssets)) {
				$manageThumbnailImageAssets = new Service_ManageImageAssets();
				$manageThumbnailImageAssets->deleteImage($artistThumbnail->ImageAssets);
			}

			$artistWebImage = Model_ArtistWebImages::getArtistWebImagesByArtistPhotoID($artist_photo_id);
			$this->view->artistimageassetid =  $artistWebImage->ImageAssets;
			if(is_object($artistWebImage->ImageAssets)) {
				$manageWebImageAssets = new Service_ManageImageAssets();
				$manageWebImageAssets->deleteImage($artistWebImage->ImageAssets);
			}
			$manageImageAssets = new Service_ManageImageAssets();
			$manageImageAssets->deleteImage($artistPhoto->ImageAssets);
			$this->updateArtistCompletion($this->getArtist()->artist_id, Model_ArtistInfoProfileSections::PHOTOS, -1);
		} catch(Exception $e) {
			echo $e->getMessage();
		}
		$this->getResponse()->setRedirect("/alw/artistphotos/index/artist_id/" . $artistPhoto->artist_id)->sendResponse();
	}

	/**
	 * edit caption for picture
	 */
	public function editcaptionAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$artist_photo_id = $this->_request->getParam("artist_photo_id", 0);
		$artist_id = $this->_request->getParam("artist_id", 0);
		$caption = $this->_request->getParam("caption", "");
		$photo = Model_ArtistPhotos::getArtistPhotoById($artist_photo_id);
		if (($photo !== false) && ($photo->artist_id == $artist_id)) {
			$photo->caption = $caption;
			$photo->save();
			echo json_encode(array('artist_photo_id' => $artist_photo_id, 'caption' => $caption));
		} else {
			$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		}
	}

	/**
	 * cleanup temp files on sudden user "stop upload" event via JSON files array lists from the UI plugin
	 */
	public function cleanuptempfilesAction() {

		/** all filenames in the current users cancelled upload */
		$filenames = $this->_request->getParam("filenames", array());

		/** all filenames in the current users cancelled upload queue that have been successfully processed and uploaded already */
		$successfulfiles = $this->_request->getParam("successfulfiles", array());

		/** cleanup all the files in the user currently uploaded files list */
		foreach ($filenames as $filename) {
			/** if it's a file that hasn't sucessfully uploaded already, then just unlink the /tmp/ file for sanity */
			if (!in_array($filename, $successfulfiles)) {
				unlink("/tmp/".$filename);
				print ("/tmp/".$filename."\n");
			}
		}
	}

	function gallerymodalAction(){
		$this->checkArtistVendor();
		$this->view->artist = $this->getArtist();
		$this->_helper->layout()->setLayout("modalwindowgallery");
		$modal_window_id = $this->_request->getParam("modalWindowID", "");
		$this->view->modal_window_id = $modal_window_id;
		$artistPhotos = Model_ArtistPhotos::getArtistPhotosByArtistID($this->getArtist()->artist_id);
		$this->view->artistPhotos = $artistPhotos;
		$this->view->image_domain = Zend_Registry::get("configuration")->images->url;
		$this->view->images_per_row = 5;
		$this->view->selected_photo = $this->_request->getParam("selected_photo",0);
	}

	public function downloadimageAction(){
		$allParams = $this->_getAllParams();
		$imageObj =  new Service_ManageImageAssets();
		$image_path = $imageObj->getImage($allParams['image_id']);
		$image_asset = Model_ImageAssets::getImageAssetsByID($allParams['image_id']);
		header('Content-Description: File Transfer');
		header('Content-Type: image/jpeg');
		header('Content-Disposition: attachment; filename='.basename($image_path));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $image_asset->file_size);
		ob_clean();
		flush();
		readfile($image_path);
		exit;
	}
}