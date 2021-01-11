<?php
/**
 * Model_ArtistPhotos
 */
class Model_ArtistPhotos extends Model_Base_ArtistPhotos implements 
	Web_Imageassets_Interface, 
		Web_Model_Artist_Id_Interface, 
		Web_Model_Artist_Completion_Interface, 
		Web_Socialprofile_Resource_Interface, 
		Web_Socialprofile_Identifier_Interface{

	/**
	 * Retrieve collection of Model_ArtistPhotos objects for a given an artist_id
	 *
	 * @param int $artist_id
	 * @return Doctrine_Collection Model_ArtistPhotos
	 */
	public static function getArtistPhotosByArtistID($artist_id){
		return Doctrine_Query::create()->from('Model_ArtistPhotos')->where('artist_id = ?', $artist_id)->orderby("artist_photo_id desc")->execute();
	}

	/**
	 * getTotalCompleted
	 *
	 * Required to implement Web_Model_Artist_Completion_Interface
	 *
	 * @param int $artist_id
	 * @return int|boolean
	 */
	public static function getTotalCompleted($artist_id){
		
		if(empty($artist_id)){
			return false;
		}
		
		if(!($result = Doctrine_Query::create()->select("COUNT(*) AS total")->from('Model_ArtistPhotos ap')->where('ap.artist_id = ?', $artist_id)->fetchOne())){
			return 0;
		}
		
		return intval($result->total);
	}

	/**
	 * get artist photos by artist photo id
	 *
	 * @param int $artist_photo_id
	 */
	public static function getArtistPhotosByArtistPhotoId($artist_photo_id){
		return Doctrine_Query::create()->from('Model_ArtistPhotos')->where('artist_photo_id = ?', $artist_photo_id)->fetchOne();
	}

	/**
	 * Get artist id as iamge category id
	 * @return int
	 */
	public function getCategoryIdFromAsset(Model_ImageAssets $imageAssets){
		return $imageAssets->ArtistPhotos->artist_id;
	}

	/**
	 * generate path image category wise
	 *
	 * @param string $artistid
	 * @return string
	 */
	public function generateImageAssetsPath($artist_id){
		
		if(isset($artist_id)){
			$initial_path = substr($artist_id, 0, 5) . "/";
			$sub_destination_path = $initial_path;
			$sub_initial_path = $artist_id . "/";
			$sub_destination_path .= $sub_initial_path;
			return $sub_destination_path;
		}
	}

	/**
	 * getArtistId
	 *
	 * Required to implement Web_Model_Artist_Completion_Interface
	 * @return int
	 */
	public function getArtistId(){
		return $this->artist_id;
	}

	/**
	 * get artist info by artist_photo_id
	 *
	 * @param int $artist_photo_id
	 * @return Model_ArtistPhotos|false
	 */
	public static function getArtistPhotoById($artist_photo_id){
		return Doctrine_Query::create()->from('Model_ArtistPhotos')->where('artist_photo_id = ?', $artist_photo_id)->fetchOne();
	}

	/**
	 * remove artist image asset
	 *
	 * @param Model_ImageAssets $imageAssets
	 */
	public function deleteCategoryAsset(Model_ImageAssets $imageAssets){
		$artistPhotos = $imageAssets->ArtistPhotos;
		$artistPhotos->delete();
	}

	/**
	 * getArtistCompletion
	 *
	 * Required to implement Web_Model_Artist_Completion_Interface
	 *
	 * @param int $total_completed
	 * @return int
	 */
	public static function getArtistCompletion($total_completed){
		
		if($total_completed >= 2){
			return 100;
		}
		
		if($total_completed == 1){
			return 67;
		}
		
		return 0;
	}

	/**
	 * getResource
	 *
	 * Required to implement Web_Socialprofile_Resource_Interface
	 *
	 * @param int $id
	 * @return Model_ArtistPhotos|null
	 */
	public static function getResource($id){
		return Doctrine_Query::create()->from('Model_ArtistPhotos ap')->where('ap.artist_photo_id = ?', $id)->fetchOne();
	}

	/**
	 * getIdentifier
	 *
	 * required by Web_Socialprofile_Identifier_Interface
	 *
	 * @return int
	 */
	public function getIdentifier(){
		return $this->artist_photo_id;
	}

	public function setIdentifier($id){
		$this->artist_photo_id = $id;
	}

	public static function getArtistPhotosByArtistIDByAsc($artist_id){
		return Doctrine_Query::create()->from('Model_ArtistPhotos')->where('artist_id = ?', $artist_id)->orderby("artist_photo_id asc")->execute();
	}
	
}
