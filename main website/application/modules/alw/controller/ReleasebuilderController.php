<?php
class Alw_ReleasebuilderController extends Web_Controller_Alw_Release {

	public function init() {
		parent::init ();

		$contextSwitch = $this->_helper->getHelper('ContextSwitch');
		$contextSwitch->addActionContext('copytrackmetadata', 'json')->addActionContext('copyreleasemetadata', 'json')->addActionContext(
			'checkimagefile', 'json')->addActionContext('addaudioimportassetbatch', 'json')->addActionContext('processpayment', 'json')->addActionContext(
			'updatecreditcard', 'json')->addActionContext('sac', 'json')->addActionContext('checkisrc','json')->addActionContext('savetrackcredit','json')->addActionContext('removetrackcredit','json')->addActionContext('creditremoveconfirm','json')->initContext('json');

	}

	public function indexAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		$this->getResponse ()->setRedirect ( "/alw/index/viewexareleases/" )->sendResponse ();
	}

	/**
	 * Return JSON encoded data pertaining to tracks for view
	 */
	public function gettracksAction() {
		if ($getData = $this->_request->isPost ()) {
			$release_id = $this->_request->getPost ( 'release_id' );
			$release_type = $this->_request->getPost ( 'release_type' );
			$isInContent = ($this->_request->getPost ( 'isInContent' ) == "true" ? true : false);
			$isEditable = ($this->_request->getPost ( 'isEditable' ) == "true" ? true : false);
			if (isset ( $release )) {
				$release->free ();
			}
			$release_data = $this->getRelease ();
			$releaseCorrection = new Service_ReleaseCorrection ($release_data->release_id);
			if (isset ( $tracks )) {
				$tracks->free ();
			}

			$tracks =  $releaseCorrection->getTrackCorrections($release_data, ($isInContent && $isEditable));
			$release = $releaseCorrection->getReleaseCorrection($release_data, ($isInContent && $isEditable));
			$album_artist_data = array();
			foreach($release->primary as $eachArtist){
				$album_artist_data[] = $eachArtist->artist_name;
			}

			$album_artist = (count($album_artist_data)>0 ? implode('|' ,$album_artist_data) : "");
			$new_tracks = array ();
			$changedField = array ();
			$rejectedNotesObj = array ();
			foreach ( $tracks as $v ) {
					$changedField = array ();
					$rejectedFieldsArray = array();

					$key = "db" . $v->id;
					$upc = isset ( $release_data ['upc'] ) ? $release_data ['upc'] : "";
					$release_id = isset ( $release_data->release_id ) ? $release_data->release_id : "";
					$cd = isset ( $v->cd ) ? $v->cd : "";
					$track_id = isset ( $v->track_id ) ? $v->track_id : "";
					$modeltrack = Model_Track::getTrackByID ( $v->id );

					// for changed fields.
					$convertToCollection = new Oa_View_Helper_Releasetrack ();
					$correctionDetails = $convertToCollection->convertToDoctrineCollection ( $v );
					$correctedTracks = new Web_Correction_Track ( $modeltrack, $correctionDetails );
					$changedField = $correctedTracks->getCorrectedFieldNames ();
					if (! empty ( $changedField )) {
						$new_tracks [$key] ['changedField'] = $changedField;
					}

					//for displying rejected comments
					$releaseApprovalQueueData = Model_ReleaseApprovalQueue::getReleasesApprovalQueueByParams ( array ('release_id' => $release_id ) );
					if (! empty ( $releaseApprovalQueueData )) {
						$rejectedNotesObj = Model_RejectionNotes::getRejectionNotesByApprovalID ( $releaseApprovalQueueData->release_approval_id, $v->id );
					}

					$new_tracks [$key] ['commentTrackName'] = '';
					$new_tracks [$key] ['commentTrackLevelArtists'] = '';
					$new_tracks [$key] ['commentTrackLevelFeaturingArtists'] = '';
     				$new_tracks [$key] ['commentTrackLevelRemixerArtists'] = '';
					$new_tracks [$key] ['comment_pLine'] = '';
					$new_tracks [$key] ['comment_track_writer'] = '';
					$new_tracks [$key] ['comment_track_publisher'] = '';
					$new_tracks [$key] ['commentPerformer'] = '';
					$new_tracks [$key] ['comment_explicit'] = '';
					$new_tracks [$key] ['comment_composer'] = '';
					$new_tracks [$key] ['comment_orchestra'] = '';
					$new_tracks [$key] ['comment_ensemble'] = '';
					$new_tracks [$key] ['comment_conductor'] = '';
					$new_tracks [$key] ['commentOriginalFileName'] = '';
					$new_tracks [$key] ['commentTrackDeductPublishing'] = '';
					$new_tracks [$key] ['rejectionCorrectedFields'] = '';
					$new_tracks [$key] ['comment_meta_language'] = '';

					if (count ( $rejectedNotesObj ) > 0) {
						$totalCount = count ( $rejectedNotesObj );
						$rejectionCorrectedFields = array();
						foreach ( $rejectedNotesObj as $notes ) {
							if ($notes ['table_name'] == 'track') {
								$rejectedFieldsArray[] = $notes['field_name'];
								switch ($notes ['field_name']) {
									case 'track_name' :
										$new_tracks [$key] ['commentTrackName'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_name'	:	''	;
										break;
									case 'track_artist' :
										$new_tracks [$key] ['commentTrackLevelArtists'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_artist'	:	''	;
										if($release->genre_id == '12'){
											$totalCount ++;
											$new_tracks [$key] ['commentPerformer'] = $notes ['comments'];
											$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'performer'	:	''	;
										}
										break;
									case 'track_featuring' :
          								$new_tracks [$key] ['commentTrackLevelFeaturingArtists'] = $notes ['comments'];
          								$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_featuring'	:	''	;
								        break;
							        case 'track_remixer' :
							            $new_tracks [$key] ['commentTrackLevelRemixerArtists'] = $notes ['comments'];
							            $notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_remixer'	:	''	;
								        break;
									case 'p_line' :
										$new_tracks [$key] ['comment_pLine'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'p_line'	:	''	;
										break;
									case 'explicit_lyrics' :
										$new_tracks [$key] ['comment_explicit'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'explicit_lyrics'	:	''	;
										break;
									case 'track_writer' :
										$new_tracks [$key] ['comment_track_writer'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_writer'	:	''	;
										break;
									case 'track_publisher' :
										$new_tracks [$key] ['comment_track_publisher'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'track_publisher'	:	''	;
										break;
									case 'performer' :
										$new_tracks [$key] ['commentPerformer'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'performer'	:	''	;
										break;
									case 'composer' :
										$new_tracks [$key] ['comment_composer'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'composer'	:	''	;
										break;
									case 'orchestra' :
										$new_tracks [$key] ['comment_orchestra'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'orchestra'	:	''	;
										break;
									case 'ensemble' :
										$new_tracks [$key] ['comment_ensemble'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'ensemble'	:	''	;
										break;
									case 'conductor' :
										$new_tracks [$key] ['comment_conductor'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'conductor'	:	''	;
										break;
									case 'original_file_name' :
										$new_tracks [$key] ['commentOriginalFileName'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'original_file_name'	:	''	;
										break;
									case 'third_party_publisher' :
										$new_tracks [$key] ['commentTrackDeductPublishing'] = $notes ['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'third_party_publisher'	:	''	;
										break;
									case 'meta_language' :
										$new_tracks [$key] ['comment_meta_language'] = $notes['comments'];
										$notes ['corrected'] == 'Y' ?	$rejectionCorrectedFields[] = 'meta_language'	:	''	;
								}

							}
						}// end for each field
						$new_tracks[$key]['rejectedFields'] = implode("|",$rejectedFieldsArray);
						$new_tracks [$key] ['rejectedNotesObj'] = $totalCount;
						$new_tracks [$key] ['rejectionCorrectedCount'] = count($rejectionCorrectedFields);
						$new_tracks [$key] ['rejectionCorrectedFields'] = implode('|' ,$rejectionCorrectedFields );
					}

					//$audio_file_number = isset($v['audio_file_number']) ? $v['audio_file_number'] : "";
					$original_file_name = isset ( $v->original_file_name ) ? $v->original_file_name : "";
					if ($release_id) {
						$new_tracks [$key] ['release_id'] = $release_id;
					}
					if ($upc) {
						$new_tracks [$key] ['upc'] = $upc;
					}

					$new_tracks [$key] ['id'] = $v->id;
					$new_tracks [$key] ['original_file_name'] = $v->original_file_name;
					$new_tracks [$key] ['cd'] = $cd;
					$new_tracks [$key] ['track_id'] = $v->track_id;
					$new_tracks [$key] ['track_name'] = $v->track_name;
					$new_tracks [$key] ['isrc'] = $v->isrc;
					$new_tracks [$key] ['length_minute'] = $v->length_minute;
					$new_tracks [$key] ['length_seconds'] = $v->length_seconds;
					$new_tracks [$key] ['explicit_lyrics'] = $v->explicit_lyrics;
					$new_tracks [$key] ['version'] = $v->version;
					$new_tracks [$key] ['p_line'] = $v->p_line;
					$new_tracks [$key] ['offer_type'] = $v->offer_type;
					$new_tracks [$key] ['additional_id'] = $v->additional_id;
					$new_tracks [$key] ['track_type'] = $v->track_type;
					$new_tracks [$key] ['mp3_url'] = $v->mp3_url;
					$new_tracks [$key] ['stereo_or_mono'] = $v->stereo_or_mono;
					$new_tracks [$key] ['bonus'] = $v->bonus;
					$new_tracks [$key] ['youtube_matches'] = $v->youtube_matches;
					$new_tracks [$key] ['youtube_uploads'] = $v->youtube_uploads;
					$new_tracks [$key] ['preorder_only'] = $v->preorder_only;
					$new_tracks [$key] ['last_updated'] = $v->last_updated;
					$latest_asset = Model_ImportAsset::getLatestAssetByUniqueTackId ( $v->id );
					$check_error_result = $latest_asset ? $latest_asset->result : "";
					if ($check_error_result) {
						$audio_upload_error = $this->mapBadFileError ( $check_error_result );
					}
					$new_tracks [$key] ['audio_upload_error'] = isset ( $audio_upload_error ) ? $audio_upload_error : "";
					$new_tracks [$key] ['third_party_publisher'] = $v->third_party_publisher;
					$new_tracks [$key] ['original_cd'] = $cd;
					$new_tracks [$key] ['original_track_id'] = $track_id;
					$new_tracks [$key] ['album_artist'] = $album_artist;
					$new_tracks [$key] ['release_status'] = $v->Releases->release_status;
					$new_tracks [$key] ['look_for_mp3'] = "N";
					$new_tracks [$key] ['mp3_check_count'] = 0;
					$new_tracks [$key] ['meta_language'] = $v->meta_language;

					/** getting track featuring artist **/
					$tmp_featuring_artist = array();
					foreach ($v->featuring as $artist) {
						$tmp_featuring_artist[] = $artist->name;
					}
					$new_tracks [$key] ['featuring'] = implode("|",$tmp_featuring_artist);

					/** getting track remixer artist **/
					$tmp_remixer_artist = array();
					foreach ($v->remixer as $remixer){
						$tmp_remixer_artist[] = $remixer->name;
					}
					$new_tracks [$key] ['remixer'] = implode("|",$tmp_remixer_artist);

					/** process track information based on weather your in a classical release or not */
					if ($release->genre_id == 12) {
						/** classical field processing  - explicitly added array key for sort as fetch is random and not as per track_artist table id */
						$track_roles = array ('performer', 'composer', 'orchestra', 'ensemble', 'conductor' );
						foreach ( $track_roles as $role ) {
							$tmp_track_entry_string = array ();
							foreach ( $v->track_artist as $vv ) {
								if ($vv->type == $role) {
									$tmp_track_entry_string [] = $vv->name;

								}
							}
							$new_tracks [$key] [$role] = implode ( '|', $tmp_track_entry_string );
						}
						/** @todo, get this working with the classical roles for tool tip processing */
						$new_tracks [$key] ['track_artists'] = "";
					} else {
						$tmp_track_artist_string = array ();

						foreach ( $v->track_artist as $vv ) { // NOT CLASSICAL SO JUST FETCH PERFORMER AS ARTIST
							if ($vv->type == 'performer')
								$tmp_track_artist_string [] = $vv->name;

						}
						$new_tracks [$key] ['track_artists'] = implode ( '|', $tmp_track_artist_string );
					}
					/** Getting Track Publishers - explicitly added array key for sort as fetch is random and not as per publisher table id*/
					$tmp_track_publishers_string = array ();
					foreach ( $v->track_publisher as $vv ) {
						$tmp_track_publishers_string [] = $vv->publisher_name;
					}
					$new_tracks [$key] ['publishers'] = implode ( '|', $tmp_track_publishers_string );

					/** Getting Track Writers - explicitly added array key for sort as fetch is random and not as per writer table id*/
					$tmp_track_writers_string = array ();
					foreach ( $v->track_writer as $vv ) {
						$tmp_track_writers_string [] = $vv->writer_name;
					}
					$new_tracks [$key] ['writers'] = implode ( '|', $tmp_track_writers_string );
					if (! $upc || ! $release_id || ! $cd || ! $track_id) {
						$new_tracks [$key] ['path'] = "";
					}
					if ($release_type == "label_processing") {
						$new_tracks [$key] ['track_code'] = $upc . "_" . $v->id;
					} else {
						$new_tracks [$key] ['track_code'] = $upc . "_" . $cd . "_" . $track_id;
					}
			}
			$correction_flag = ($isEditable && $isInContent) ? true : false;
			/** Getting Audio upload info */
			if (count ( $new_tracks ) > 0) {
				$new_tracks = $this->_helper->Gettrackpath->gettrackpathandduration ( $new_tracks, $correction_flag );
				foreach ( $new_tracks as $key => $value ) {
					if (isset ( $value ['audio_upload_error'] )) {
						$err_msg = $this->mapBadFileError ( $value ['audio_upload_error'] );
						$value ['audio_upload_error'] = $err_msg;
					}
				}
			}
		}
		echo json_encode ( $new_tracks );
		exit ();
	}

	/**
	 * function updatetracksAction
	 * update/remove track and cd info from the database and the files on the media server
	 * to match current update coming from user's request
	 */
	public function updatetracksAction() {

		$release_id = $this->_request->getParam ( 'release_id' );
		$release_obj = Model_Releases::getReleaseByReleaseID ( $release_id );
		/** get post data */
		if ($getData = $this->_request->isPost ()) {
			$track_data = $this->_request->getPost ();
		}

		/** Remove Deleted Tracks */
		$old_tracks = array ();
		$new_tracks = array ();

		$previous_tracks = Model_Track::getTracksByReleaseid ( $release_id );
		$previous_tracks_hash = array ();
		foreach ( $previous_tracks as $previous_track ) {
			$old_tracks [] = $previous_track->id;
			$previous_tracks_hash [$previous_track->id] = $previous_track;
		}

		foreach ( $track_data as $track_info ) {
			$new_tracks [] = $track_info ['unique_track_id'];
		}

		/** Remove Tracks from DB and the Media Server */
		$track_remove_names = array ();
		$delete_tracks = array_diff ( $old_tracks, $new_tracks );

		foreach ( $delete_tracks as $track_to_delete ) {
			$track_details = $previous_tracks_hash [$track_to_delete];
			$cd = $track_details->cd;
			$track_id = $track_details->track_id;
			$track_remove_names [] = $release_obj->upc . "_" . $track_details->id;
			/*delete licensing review before deleting track
			 *
			 */
			$lr_obj = Model_LicensingReviewStatus::getLicensingReviewIdsByTrackId ( $track_details->id );
			foreach ( $lr_obj as $v ) {
				Model_LicensingReviewStatusChangeHistory::deleteLicensingReviewStatusChangeHistory ( $v->id );
			}
			Model_LicensingReviewStatus::deleteLicensingReviewStatus ( $track_details->id );
			/** delete track from DB */
			$track_details->TrackArtist->delete ();
			$track_details->TrackWriter->delete ();
			$track_details->TrackPublisher->delete ();
			$track_details->delete ();
		}

		if (count ( $track_remove_names ) > 0) {
			/**  remove file from media server */
			$this->deleteTracksAction ( $track_remove_names );
		}
		/** For each track, Update track if any changes from UI */
		foreach ( $track_data as $track_info ) {
			$id = $track_info ['unique_track_id'];
			$cd = $track_info ['cd'];
			$track_number = $track_info ['trackNumber'];
			//$track_obj->changeTrack($id);
			$track_details = Model_Track::getTrackByID ( $id );
			if ($cd != $track_details->cd || $track_number != $track_details->track_id) {
				$track_update = Model_track::getTrackByID ( $id );
				$track_update->last_updated = new Doctrine_Expression ( 'NOW()' );
				$track_update->cd = $cd;
				$track_update->track_id = $track_number;
				$track_update->save ();
			}
		}
		echo 1;
		exit ();
	}

	public function getreleasestatusAction() {
		try {
			$isCorrectionFlag = true;
			$sessionData = new Zend_Session_Namespace ( 'Alw' );
			$vendor_id = $sessionData->S_VENDOR_ID;
			$release = $this->getRelease ();
			if($release->release_status != 'in_content'){
				$isCorrectionFlag = false;
			}
			$release_correction = new Service_ReleaseCorrection ($release->release_id);
			$release_info = $release_correction->getReleaseCorrection ( $release, true );
			$product_type = Model_Releases::getReleaseType($release_info->upc);

			$release_subgenre = $release_info->release_subgenre;
			$cd_receive_info = Model_CdReceive::getCdReceiveByParams ( array ("vendor_id" => $vendor_id, "upc" => $release_info->upc ) );
			$num_of_existing_tracks = count ( Model_Releases::getMusicTrackObjects ( $release->release_id ) ) + count ( Model_Releases::getVideoTrackObjects ( $release->release_id ) );

			$volumes = array (); //This is used for checking whether there is gap between volumes, eg. volume 1, 3, 4 should raise an error
			$track_last_updated = "";
			$track_codes = array ();
			$actual_track_codes = array ();
			$num_of_release_subgenre = count ( $release_subgenre );
			$num_of_completed_music_tracks = 0;
			$num_of_completed_video_tracks = 0;
			$image_scan = "N";
			$image_scan_date = "";
			$empty_volume = false;
			$rb = 0;
			$art = 0;
			$at = 0;
			$vthumb = 0;
			$imprint = "";
			$pip=0;

			if($cd_receive_info){
				$image_scan = $cd_receive_info->image_scan;
				$image_scan_date = $cd_receive_info->image_scan_date;
			}

			$release_last_updated = isset ( $release_info->last_updated ) ? $release_info->last_updated : "";
			$upc = $release_info->upc;
			$release_id = $release_info->release_id;
			$release_name = $release_info->release_name;
			$release_date = $release_info->release_date;
			$artist_name = $release_info->ArtistInfo->name;
			$genre_id = $release_info->genre_id;
			$release_status = $release_info->release_status;
			$c_line = $release_info->c_line;
			$imprint = $release_info->label;

			//In case of no tracks, the release level query executes and does not return any value for CD
			if ($num_of_existing_tracks) {
				$tracks = $release_correction->getTrackCorrections ( $release, true );
				foreach ( $tracks as $track ) {
					if (isset ( $track->cd )) {
						$volumes [] = $track->cd;
					}

					$track_codes [$track->id] = $upc . "_" . $track->cd . "_" . $track->track_id;
					$audio_track_code [$track->id] = $upc . "_" . $track->id;
					if (is_null ( $release_last_updated ) || trim ( $release_last_updated ) == "" || strtotime ( $track_last_updated ) < strtotime ( $track->last_updated )) {
						$track_last_updated = $track->last_updated;
					}
					if ($track->track_type == "music" && $track->explicit_lyrics && $track->track_name && $track->p_line) {
						$num_of_completed_music_tracks ++;
					} elseif ($track->track_type == "video" && $track->explicit_lyrics && $track->track_name && $track->p_line) {
						$num_of_completed_video_tracks ++;
					}
				}
			}
			//}


			//this is for checking whether there is any empty volume
			$volumes = array_unique ( $volumes );
			sort ( $volumes );
			$i = 1;
			foreach ( $volumes as $v ) {
				if ($i != $v) {
					$empty_volume = true;
				}
				$i ++;
			}

			$validation = $this->validaterelease ( $release_id, $vendor_id );

			if ($imprint && $release_id && $release_date && $release_date != "0000-00-00" && $release_name && $artist_name && $genre_id && $c_line && ($num_of_release_subgenre > 0)) {
				$rb = 1;
			}

			$imageValidator = new Web_Validate_Checkimage ();
			if ($imageValidator->isValid ( $release_info, $isCorrectionFlag, $product_type)){
				$art = 1;
				$vthumb = 1;
			}

			if($product_type == 'music_video'){
				$video_asset = new Web_Controller_Action_Helper_VideoAsset();
				$previewVideo = Model_TrackVideoMarker::getPreviewByTrackId($track->id);
				if($previewVideo){
					$pip=1;
				}

				if(($release->getVideoTrackObjects($release_id)->count() == 1) && ($video_asset->verifyMusicVideoAsset($upc))&& ($tracks[0]->original_file_name != '') && (!empty($tracks[0]->original_file_name))){
					$at = 1;
				}
			}else{
				if($num_of_existing_tracks && ($num_of_completed_music_tracks > 0 && !isset($validation['track_missing_errors']))){
					$at = 1;
				}
			}
			//TODO check video thumnail and set this flag to 1 if exists
			//$vthumb = 0;

			echo json_encode(
			array("num_of_music_tracks"=>$num_of_completed_music_tracks, "num_of_video_tracks"=>$num_of_completed_video_tracks,
								"rb"=>$rb, "art"=>$art, "at"=>$at, "vthumb"=>$vthumb,"pip"=>$pip, "release_basics_last_updated"=>substr($release_last_updated, 0, 10),
								"last_track_update"=>substr($track_last_updated, 0, 10), "image_scan_date"=>substr($image_scan_date, 0, 10),
								"image_error"=>$imageValidator->getErrors()));

		}catch(Exception $e){
			echo json_encode(array("err"=>$e->getMessage()));
		}
		exit ();
	}

	public function viewAction() {
		$vendor_id = $this->getVendorId ();
		$this->checkReleaseVendor ();
		$showEdit = $this->_request->getParam ( "correction", false );
		$correctionService = new Service_ReleaseCorrection ($this->getRelease()->release_id);
		$releaseCorrection = $correctionService->getReleaseCorrection ( $this->getRelease(), $showEdit );
		$isEditable = false;
		$hasActiveCorrection = $releaseCorrection->hasActiveCorrection ();
		$isInContent = $releaseCorrection->isInContent ();
		if ($isInContent) {
			if ($hasActiveCorrection && $showEdit) {
				//$correctionDetails = Model_ReleaseCorrection::getActiveCorrection($releaseCorrection->release_id);
				$isEditable = true;
			} else if (! $hasActiveCorrection && $showEdit) { //-- WHEN EDITING FOR THE FIRST TIME, CREATE AN CORRECTION RECORD
				/** get session orchard admin user or vendor id */
				$sessionData = new Zend_Session_Namespace ( 'Alw' );
				$updated_by = $sessionData->S_VEND_CONTACT_ID;
				$updated_type = "vendor";
				if (isset ( $sessionData->S_USERID )) {
					$updated_by = $sessionData->S_USERID;
					$updated_type = "oa";
				}
				$release_correction_id = Model_ReleaseCorrection::updateReleaseCorrection ( $releaseCorrection->release_id, 'active', $updated_by, $updated_type );
				$isEditable = true;
			}
		} else {
			if ($this->getRelease ()->release_status == "label_processing") {
				$isEditable = true;
			}
		}

		//Check ACL
		$isUserAllowedEdit = false;
		$roleresourceprivilegeValidator = new Web_Validate_Checkroleresourceprivilege();
		if($roleresourceprivilegeValidator->isValid(array("resource"=>"releasebuilder", "privilege"=>"createrelease"))){
			$isUserAllowedEdit = true;
		} else {
			$isEditable = false;
		}
		//If Release is deleted its not Editable
		if($this->getRelease ()->deletions == 'Y' ){
			$isEditable = false;
		}

		/** Check Coverart correction exist in release correction details */
		$isChangeCoverart = $releaseCorrection->isFieldChanged ( 'coverart' );
		$mediaAuthenticator = new Web_Controller_Plugin_MediaAuthenticator ();
		$media_token = $mediaAuthenticator->getToken ( $vendor_id, 'stream', 'alw' );
		$connectionArgs = array ();
		$connectionArgs ['user_id'] = $vendor_id;
		$connectionArgs ['token'] = $media_token;
		$connectionArgs ['user_type'] = 'alw';

		/** To get edited fields from 'correction_details' */
		$editedFields = $releaseCorrection->getCorrectedFieldNames ();

		/* To check the status of release_id is 'rejected' or not */
		$releaseApprovalQueueData = Model_ReleaseApprovalQueue::getReleasesApprovalQueueByParams ( array ('release_id' => $releaseCorrection->release_id ) );
		if (isset ( $releaseApprovalQueueData )) {
			$this->view->releaseApprovalQueueStatus = $releaseApprovalQueueData ['status'];
			if ($releaseApprovalQueueData ['status'] == "rejected") {
				$rejectionNotes = Model_RejectionNotes::getRejectionNotesByApprovalID ( $releaseApprovalQueueData ['release_approval_id'] );
				$trackVolumeAndId = array();
				if ($rejectionNotes) {
					/* This code is to get other rejection comments from oa for $release_id supplied */
					foreach ( $rejectionNotes as $rejection ) {
					   if($rejection["table_name"] == "track" && !in_array($rejection["key_id"],$trackVolumeAndId) ){
					   		$getTrack = Model_Track::getTrackByID($rejection["key_id"]);
					   		if($getTrack){
					   			$trackVolumeAndId[$rejection["key_id"]]["cd"] = $getTrack->cd;
					   			$trackVolumeAndId[$rejection["key_id"]]["trackid"] = $getTrack->track_id;
					   		}
					   }
					}
				    $this->view->trackVolumes = $trackVolumeAndId;
					$this->view->rejectionNotes = $rejectionNotes;
				}
			}
		}

		/* To check whether release is submitted or not for approval to 'oa_admin' */
		$submitErrorCorrection = Model_ReleaseCorrection::getReleaseCorrectionByReleaseId ( $releaseCorrection->release_id );
		if (isset ( $submitErrorCorrection )) {
			$this->view->submitErrorCorrection = $submitErrorCorrection ['status'];
		}

		/* To retrieve last submitted date of release when release_status = 'transfer_to_content' */
		$this->view->submittedDateForRelease = "";
		if (strlen ( $this->getRelease ()->ingestion_completed ) > 0) {
			$this->view->submittedDateForRelease = $this->getRelease ()->ingestion_completed;
		} else {
			$releaseStatuses = $this->getRelease ()->ReleaseStatus;
			for($i = count ( $releaseStatuses ) - 1; $i >= 0; $i --) {
				$releaseStatus = $releaseStatuses [$i];
				if ($releaseStatus->status == 'transfer_to_content') {
					$this->view->submittedDateForRelease = $releaseStatus->date;
					break;
				}
			}
		}

		/** get session orchard admin user or vendor id for release correction details */
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$login_info = array ();
		$login_info ['user_id'] = $sessionData->S_VEND_CONTACT_ID;
		$login_info ['user_type'] = "vendor";
		if (isset ( $sessionData->S_USERID )) {
			$login_info ['user_id'] = $sessionData->S_USERID;
			$login_info ['user_type'] = "oa";
		}

		$this->view->validation = $this->validaterelease ( $releaseCorrection->release_id, $vendor_id );
		$this->view->classicalFields = $releaseCorrection->getRoleWiseReleaseArtists ( $releaseCorrection->release_artist ); //$this->getClassicalReleaseFields($releaseCorrection);
		$this->view->connectionArgs = $connectionArgs;
		$this->view->release = $releaseCorrection;
		//$artist_id = $releaseCorrection->artist_id;
		//$this->view->artist =  $releaseCorrection->artist; //Model_ArtistInfo::getArtistById($artist_id);
		//$this->view->genre = $releaseCorrection->genre; //Model_Genre::getGenreByParams(array("genre_id"=>$releaseCorrection->genre_id));
		$this->view->isInContent = $isInContent;
		$this->view->isEditable = $isEditable;
		$this->view->hasActiveCorrection = $hasActiveCorrection;
		$this->view->isUserAllowedEdit = $isUserAllowedEdit;
		$this->view->checkaudio = $this->_request->getParam ( "checkaudio", 0 );
		$this->view->streaming_url = Zend_Registry::get ( "configuration" )->streaming->full_length;
		$this->view->showEdit = $showEdit;
		$this->view->editedFields = $editedFields;
		$this->view->isChangeCoverart = $isChangeCoverart;
		$this->view->loginInfo = $login_info;
		$this->view->isMixedRelease = $correctionService->getMixedRelease ( $this->getRelease () );
		$this->view->product_type = Model_Releases::getReleaseType($this->getRelease()->upc);
		if($this->view->product_type == 'music_video'){
			$trackinfo = Model_Releases::getVideoTrackObjects($this->getRelease()->release_id);
			$this->view->trackinfo = $trackinfo;
			$this->view->trackcredit = Model_TrackCredit::getTrackCreditDetailsByTrackId($trackinfo[0]->id);
			$video_asset = new Web_Controller_Action_Helper_VideoAsset();
			$linkedAssetDetails = $video_asset->getMusicVideoAssetByUpc($this->getRelease()->upc);
			$unlinkedAssets = array();
			if (!is_array($linkedAssetDetails)){
				$unlinkedAssets = $video_asset->getUnlinkedAssets();
			}
			$this->view->linkedAssetDetails = $linkedAssetDetails;
			$this->view->unlinkedAssets = $unlinkedAssets;
		}
	}

	public function cancelupdatedreleaseAction() {
		$release_id = $this->_getParam ( 'release_id' );
		// Delete Release Correction data
		$objReleaseCorrection = new Service_ReleaseCorrection();
		$deleteCorrectionData = $objReleaseCorrection->deleteReleaseCorrectionData ( $release_id );
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		$redirect_url = "/alw/releasebuilder/view?release_id=" . $release_id;
		$this->getResponse ()->setRedirect ( $redirect_url )->sendResponse ();
		exit ();
	}

	public function getclassicalreleasefieldsAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->checkReleaseVendor ();
		$correction = $this->_request->getParam ( "correction", false );
		$correctionService = new Service_ReleaseCorrection ( $this->getRelease ()->release_id);
		$releaseCorrection = $correctionService->getReleaseCorrection ( $this->getRelease (), $correction );
		$results = $this->getClassicalReleaseFields ( $releaseCorrection );
		echo json_encode ( $results );
		exit ();
	}

	/**
	 * function getClassicalReleaseFields
	 * gather information about extra classical fields for a release id
	 *
	 * @param int $release_id, release id to gather information for
	 * @return array, array of classical field type information in array
	 */
	protected function getClassicalReleaseFields(Web_Correction_Release $release) {
		$composers = array ();
		$orchestras = array ();
		$ensembles = array ();
		$conductors = array ();
		$classical_entries = $release->release_artist->toArray();
		sort($classical_entries);
		foreach ( $classical_entries as $classical_entry_value ) {

			switch ($classical_entry_value['role']) {
				case 'composer' :
					$composers [] = $classical_entry_value['artist_name'];
					break;
				case 'orchestra' :
					$orchestras [] = $classical_entry_value['artist_name'];
					break;
				case 'ensemble' :
					$ensembles [] = $classical_entry_value['artist_name'];
					break;
				case 'conductor' :
					$conductors [] = $classical_entry_value['artist_name'];
					break;
			}
		}

		return array ('composers' => $composers, 'orchestras' => $orchestras, 'ensembles' => $ensembles, 'conductors' => $conductors );
	}

	function edittrackmodalAction() {
		$this->_helper->layout ()->disableLayout ();
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			//$show_deduct_publishing = false;
			$sessionData = new Zend_Session_Namespace ( 'Alw' );
			$vendor_id = $sessionData->S_VENDOR_ID;
			$validator = new Web_Validate_Checkomstype ();
			/*if($validator->isValid($vendor_id)){
				$show_deduct_publishing = true;
			}
			$this->view->show_deduct_publishing = $show_deduct_publishing;*/
			$this->view->post_data = $postData;
		}
		$this->view->languages = Model_Language::getLanguageByParams('', true, true);
		$correction = $this->_request->getParam ( "correction", false );
		/** get genre info for processing classical field options */
		$releaseObj = Model_Releases::getReleaseByReleaseID ( $this->_request->release_id );
		$releaseCorrection = new Service_ReleaseCorrection ($releaseObj->release_id);
		$release_info = $releaseCorrection->getReleaseCorrection ( $releaseObj, $correction );

		$this->view->genre_id = $release_info->genre_id;
		$this->view->release_id = $this->_request->release_id;
		$this->view->release_status = $release_info->release_status;
		//Following code is for generating input file element for each track. This is needed because if user re-upload multiple tracks
		//at the same time, the uploadprogress_get_info function which get the upload progress info need to reference to the file input.
		//Each of the tracks that the user is reuploading need a thread of getting upload progress.
		$this->view->all_tracks = Model_Track::getTracksByReleaseid ( $this->_request->release_id );
	}

	function addtrackmodalAction() {
		$this->_helper->layout ()->disableLayout ();
	}

	function displayloadingimageAction() {
		$this->_helper->layout ()->disableLayout ();
	}

	public function thumbnailmodalAction(){
		$this->_helper->layout()->setLayout("modalwindowwrapper");
	}

	function createreleasemodalAction(){
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$this->view->placeholder("subTitle")->set($this->view->translate->_("Release Basics"));
		$this->view->productType=$this->_request->getParam('product_type', 'music');
		$objrelease_data = $this->getRelease();
		$editedFields = array ();
		if($objrelease_data != null){
			$this->view->placeholder("title")->set($this->view->translate->_("Edit Release Basics"));
		}else{
			$this->view->placeholder("title")->set($this->view->translate->_("Create Release"));
		}
		$vendor_id = $this->getVendorId ();
		$vendor_data = Model_Vendor::getVendorByVendorID ( $vendor_id );
		$this->view->imprints = Model_Releases::getImprintsByVendorID ( $vendor_id );

		$correction = $this->_request->getParam ( "correction", false );
		$releaseDirector = "";
		$releaseEditor = "";
		$releaseProducer = "";
		$releaseOther = "";
		$vendor_id = $this->getVendorId();
		$vendor_data = Model_Vendor::getVendorByVendorID($vendor_id);
		$this->view->imprints = Model_Releases::getImprintsByVendorID($vendor_id);

		$rejectionNotes = array();
		$artists = Model_ArtistInfo::getArtistNamesByVendorId ( $vendor_id );
		$this->view->artistData = array ();
		$various_artist_exist = 0;
		foreach ( $artists as $key => $values ) {
			$this->view->artistData [$values ['artist_id']] = $values ['name'];
			if ($values ['name'] == "Various Artists") {
				$various_artist_exist = 1;
			}
		}

		if (! $various_artist_exist) {
			$this->view->artistData ['-1'] = "Various Artists";
		}
		natcasesort ( $this->view->artistData );

		$this->view->language = Model_Language::getAllLanguages();
		$genreObj = new Model_Genre ();
		$this->view->genres = Model_Genre::getAllGenres ();
		$this->view->rejectionNotes = $this->_request->getParam ( "rejectionNotes", array () );
		$this->view->subgenres = array ();
		$this->view->release_subgenres = array ();
		$this->view->formats = array ('CD (Full Length)' => 'Full Length', 'EP' => 'EP', 'Single' => 'Single' );
		$releaseData = new Web_Correction_Release ( new Model_Releases () );
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			if (isset ( $postData ['release_id'] )) {
				$flg_alr = false;
				if (! is_object ( $vendor_data ) || empty ( $vendor_data )) {
					$this->view->fatal_error = "<b>No such vendor.</b>";
				} else {
					if (($vendor_data->show_release_builder == 'Y') || ($vendor_data->assigned_to == 187)) {
						$flg_alr = true;
					}
				}

				if ($postData ['release_id']) {
					$release_correction = new Service_ReleaseCorrection ($objrelease_data->release_id);
					$releaseData = $release_correction->getReleaseCorrection ( $objrelease_data, $correction );
					$this->view->tracks = Model_Track::getTrackByParams(array("release_id"=>$postData['release_id']));

					if (! $releaseData) {
						$this->view->fatal_error = '<b>Release Was Not Created.</b>';
					} else {
					$releaseApprovalQueueData = Model_ReleaseApprovalQueue::getReleasesApprovalQueueByParams ( array ('release_id' => $postData ['release_id'] ) );
				if (isset ( $releaseApprovalQueueData )) {
			$this->view->releaseApprovalQueueStatus = $releaseApprovalQueueData ['status'];
			if ($releaseApprovalQueueData ['status'] == "rejected") {
				$rejectionNotes = Model_RejectionNotes::getRejectionNotesByApprovalID ( $releaseApprovalQueueData ['release_approval_id'] );
			}
		}
						$subgenre_arr = array ();
						$releaseData->release_id = $postData ['release_id'];
						$release_subgenre_obj = $releaseData->release_subgenre;
						$subgenre_arr = array ();
						foreach ( $release_subgenre_obj as $value ) {
							$subgenre_arr [] = $value->subgenre_id;
						}

						if (strlen ( $releaseData->genre_id ) > 0) {
							$this->view->subgenres = Model_SubGenre::getSubGenres ( $releaseData->genre_id );
						}
						if ($subgenre_arr) {
							$this->view->release_subgenres = Model_SubGenre::getSubgenresByOrchardIDs ( $subgenre_arr );
						}
					}
					$editedFields = $releaseData->getCorrectedFieldNames ();
				}

				if ($this->view->fatal_error) {
					echo "<center>" . $this->view->fatal_error . "</center>";
					exit ();
				}
			}
		}
		if ($releaseData->release_id == 0) {
			$releaseData->c_line = ($vendor_data->company) ? date ( 'Y' ) . ' ' . $vendor_data->company : '';
		}
		if(isset($postData['product_type']) && $postData['product_type'] == 'music_video' && !empty($postData['release_id']) && $postData['release_id'] > 0){
				$creditsObj = new Web_Controller_Action_Helper_Credits($this->view->tracks->id);
				$this->view->releaseDirector = $creditsObj->buildCreditsNames('director');
				$this->view->releaseEditor = $creditsObj->buildCreditsNames('editor');
				$this->view->releaseProducer = $creditsObj->buildCreditsNames('producer');
				$this->view->releaseOther = $creditsObj->buildCreditsNames('other');
		}

		/** primary artist **/
		$primaryArtist = $releaseData->primary;
		$primary_artist = array();
		if ($primaryArtist) {
			foreach ( $primaryArtist as $artist ) {
				$primary_artist [] = $artist->artist_name;
			}
			$this->view->primaryArtist = implode('|',$primary_artist);
		}

		/** featuring artist **/
		$featuringArtist = $releaseData->featuring;
		$featuring_artist= array();
		if ($featuringArtist) {
			foreach ( $featuringArtist as $artist ) {
				$featuring_artist [] = $artist->artist_name;
			}
			$this->view->featuringArtist = implode('|',$featuring_artist);
		}

		/** remixer artist **/
		$remixerArtist = $releaseData->remixer;
		$remixer_artist = array();
		if ($remixerArtist) {
			foreach ( $remixerArtist as $artist ) {
				$remixer_artist [] = $artist->artist_name;
			}
			$this->view->remixerArtist = implode('|',$remixer_artist);
		}

		/** get classical fields if applicable */
		if ($releaseData->genre_id == 12) {

			/** get possible classical information for release level display */
			$composers_list = "";
			$orchestras_list = "";
			$ensembles_list = "";
			$conductors_list = "";

			$fields =  $releaseData->getRoleWiseReleaseArtists ( $releaseData->release_artist ); //$this->getClassicalReleaseFields ( $releaseData );

			foreach ( $fields ['composers'] as $composer_entry ) {
				$composers_list .= $composer_entry . "|";
			}
			foreach ( $fields ['orchestras'] as $orchestra_entry ) {
				$orchestras_list .= $orchestra_entry . "|";
			}
			foreach ( $fields ['ensembles'] as $ensemble_entry ) {
				$ensembles_list .= $ensemble_entry . "|";
			}
			foreach ( $fields ['conductors'] as $conductor_entry ) {
				$conductors_list .= $conductor_entry . "|";
			}

			/** release artist model */
			$release_artist = new Model_ReleaseArtist ();

			// classical values
			$this->view->releaseComposer = trim ( $composers_list, "|" );
			$this->view->releaseOrchestra = trim ( $orchestras_list, "|" );
			$this->view->releaseEnsemble = trim ( $ensembles_list, "|" );
			$this->view->releaseConductor = trim ( $conductors_list, "|" );
		}

		if($releaseData->isInContent()){
			$this->view->placeholder ( "errorCorrectionInfo" )->set ( "release" );
		}

		$release_status = $this->_request->getParam ( 'release_status' );
		$this->view->release_status = $release_status;
		$form = new Alw_Form_CreateReleaseCorrectionForm ( $releaseData );
		$this->view->formHidden = $form;

		$form = new Alw_Form_CreateReleaseForm ( $releaseData );
		$this->view->form = $form;
		$this->view->editedFields = $editedFields;
		$this->view->release_id = $releaseData->release_id;
		$this->view->rejectionNotes = $rejectionNotes;
		$this->view->releaseData = $releaseData;
		$this->view->isInContent = $releaseData->isInContent ();
		$this->view->modal_window_id = $this->_request->getParam ( "modalWindowID", "" );
	}

	public function editrejectionnotes($changedArray) {
		foreach($changedArray as $value){
			$objRejectionNotes = Model_RejectionNotes::getRejectionNotesByID($value);
			if(count($objRejectionNotes)){
				$objRejectionNotes->corrected = "Y";
				$objRejectionNotes->save();
			}
		}
	}

	function editreleasemodalAction() {
		$this->_helper->layout ()->disableLayout ();
	}

	function validatereleaseAction($formData) {
		$errors = '';

		if (! $formData ['release_name']) {
			$errors .= 'Missing release name';
		}

		if (! $formData ['artist_id']) {
			$errors .= ($errors ? '; ' : '') . 'Missing artist';
		}

		if (! $formData ['release_date']) {
			$errors .= ($errors ? '; ' : '') . 'Missing release date';
		} else {
			$dateArray = explode ( '-', $formData ['release_date'] );
			list ( $Year, $Month, $Day ) = $dateArray;
			if ($Month < 10 && strlen ( $Month ) == 1) {
				$Month = '0' . $Month;
			}
			if ($Day < 10 && strlen ( $Day ) == 1) {
				$Day = '0' . $Day;
			}
			$formData ['release_date'] = $Year . '-' . $Month . '-' . $Day;
			if (! Zend_Validate::is ( $formData ['release_date'], 'Date' )) {
				$errors .= ($errors ? '; ' : '') . 'Invalid release date: ' . $formData ['release_date'];
			}
		}
		if($formData['product_type'] != 'music_video' && empty($formData['format'])){
			$errors .= ($errors ? '; ' : '') . 'Missing format';
		}

		if (! $formData ['genre']) {
			$errors .= ($errors ? '; ' : '') . 'Missing genre';
		}

		if($formData['c_line'] && (!Zend_Validate::is(substr($formData['c_line'], 0, 4), 'Digits') ||
		substr($formData['c_line'], 4, 1) != ' ' || !substr($formData['c_line'], 5))){
			$errors .= ($errors ? '; ' : '') . 'Invalid c-line: ' . $formData['c_line'];
		}

		if($formData['product_type'] == 'music_video'){
			if(!$formData['language']){
				$errors .= 'Missing language';
			}
			if(!$formData['isrc_type']=='OWN' && !empty($formData['isrc']) ){
				$errors .= 'Missing ISRC';
			}
			if(!$formData['explicit']){
				$errors .= 'Missing explicit';
			}
			if(!$formData['p_line']){
				$errors .= 'Missing P-Line';
			}
		}
		return $errors;
	}

	/**
	 * Save New/Existing Release
	 * This function will persist the release level information to the Database
	 * as well as return valid JSON formatted information to the view for correctly presenting release level data
	 */
	function savereleaseAction() {
		try {
			if ($this->_request->isPost ()) {
				$postData = $this->_request->getPost ();
				$this_genre_id = null;
				if (isset ( $postData ['release_id'] )) {
					if(isset($postData['arrayOfChangedFields'])){
						$this->editrejectionnotes($postData['arrayOfChangedFields']);
					}
					$sessionData = new Zend_Session_Namespace ( 'Alw' );
					$vendor_id = $this->getVendorId ();
					$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
					$vendor_data = array ();
					$errors = '';
					if ($vendor_id) {
						$vendor_data = Model_Vendor::getVendorByVendorID ( $vendor_id );
					}
					/** Make sure the release is from the vendor */
					if ($postData ['release_id'] > 0) {
						$isValidRelease = Model_Releases::isValidReleaseId ( $postData ['release_id'], $vendor_id );
						/** if invalid release for current vendor then exit, don't continue*/
						if (! $isValidRelease) {
							echo "Invalid Release ID for this Vendor.";
							exit ();
						}
					}
					$this->view->err_msg = '';
					$formData = $this->_request->getPost ();
					$subgenre_data = explode ( ',', $formData ['subgenres'] );
					$errors = $this->validatereleaseAction ( $formData );
					$new_artist_id = 0;
					if (! empty ( $errors )) {
						echo $errors;
					} else {
						/** add brand new artist? comments needed here */
						if ($formData ['artist_id'] == "-1") {
							$label = Model_Vendor::getPrimaryContactDetail ( $vendor_id );
							$artist_add = new Model_ArtistInfo ();
							$artist_add->vendor_id = $vendor_id;
							$artist_add->name = "Various Artists";
							$artist_add->orchard_country = $label->orchard_country;
							$artist_add->when_entered = new Doctrine_Expression ( "NOW()" );
							$artist_add->entered_by = $vendor_id;
							$artist_add->save ();
							$new_artist_id = $artist_add->artist_id;

						}

						/** get session orchard admin user or vendor id */
						$updated_by = $sessionData->S_VEND_CONTACT_ID;
						$updated_type = "vendor";
						if (isset ( $sessionData->S_USERID )) {
							$updated_by = $sessionData->S_USERID;
							$updated_type = "oa";
						}
						/** if existing release, update to correction table, else create new */
						if ($postData ['release_id'] > 0) {
							$release_original = Model_Releases::getReleaseByReleaseID ( $postData ['release_id'] );
							$current_release_status = $release_original->release_status;
							$correction = $this->_request->getParam ( "correction", false );
							$release_correction = new Service_ReleaseCorrection ($release_original->release_id);
							$release_update = $release_correction->getReleaseCorrection ( $release_original, $correction );
							// save data in temperory table.
							if ($release_update->release_status == 'in_content') {
								$this_genre_id = $release_update->genre_id;
								$release_id = $postData ['release_id'];
								if ($release_update->release_name != $formData ['release_name'])
									$release_data_arr ['release_name'] = $formData ['release_name'];
								if ($release_update->label != $formData ['imprint_name'])
									$release_data_arr ['label'] = $formData ['imprint_name'];

								if ($release_update->genre_id != $formData ['genre']) {
									$release_data_arr ['genre_id'] = $formData ['genre'];
									if ($release_update->genre_id == 12) {
										$releaseCorrectionObj = $release_update->ReleaseCorrection [count ( $release_update->ReleaseCorrection ) - 1];
										Model_ReleaseCorrectionDetail::deleteCorrectionDataByParams ( array ('release_correction_id' => $releaseCorrectionObj->release_correction_id, 'field_name' => 'release_artist' ) );
									}
								}
								if ($release_update->c_line != $formData ['c_line'])
									$release_data_arr ['c_line'] = $formData ['c_line'];
								if (! empty ( $release_data_arr )) {
									Model_ReleaseCorrectionDetail::saveCorrectionData ( $release_id, 'releases', $release_id, $release_data_arr, $updated_by, $updated_type );
								}
								// release subgenre
								$old_subgenres = $release_update->release_subgenre;
								$old_subgenres_arr = array ();
								foreach ( $old_subgenres as $old_subgenre ) {
									$old_subgenres_arr [] = $old_subgenre->subgenre_id;
								}
								$array_diff = array_diff ( array_merge ( $subgenre_data, $old_subgenres_arr ), array_intersect ( $subgenre_data, $old_subgenres_arr ) );
								if (count ( $array_diff ) > 0) {
									foreach ( $subgenre_data as $subgenre ) {
										$subgenre_data_arr [$release_id] [] = $subgenre;
									}

									Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'releases', 'release_subgenre', $release_id, json_encode ( $subgenre_data_arr ), $updated_by, $updated_type );
								}

								//primary artist
								$primaryArtist = '';
								$featuringArtist = '';
								$remixerArtist = '';
								if ($release_update->primary){
									foreach ($release_update->primary as $primaryArtistObj){
										$primary_artist_arr[] = $primaryArtistObj->artist_name;
									}
									if (!empty($primary_artist_arr))
										$primaryArtist = implode("|", $primary_artist_arr);
								}
								if ($formData ['primary_artist'] != $primaryArtist) {
									$this->processReleaseArtist ( $formData ['primary_artist'], 'performer', $release_update, $updated_by, $updated_type );
								}

								// featuring artist
								if ($release_update->featuring){
									foreach ($release_update->featuring as $featuringArtistObj){
										$featuring_artist_arr[] = $featuringArtistObj->artist_name;
									}
									if (!empty($featuring_artist_arr))
										$featuringArtist = implode("|", $featuring_artist_arr);
								}
								if ($formData ['featuring'] != $featuringArtist) {
									$this->processReleaseArtist ( $formData ['featuring'], 'featuring', $release_update, $updated_by, $updated_type );
								}

								// Remixer artist
								if ($release_update->remixer){
									foreach ($release_update->remixer as $remixerArtistObj){
										$remixer_artist_arr[] = $remixerArtistObj->artist_name;
									}
									if (!empty($remixer_artist_arr))
										$remixerArtist = implode("|", $remixer_artist_arr);
								}
								if ($formData ['remixer'] != $remixerArtist) {
									$this->processReleaseArtist ( $formData ['remixer'], 'remixer', $release_update, $updated_by, $updated_type );
								}

								$release = $release_update;
							} else {
								$release_original = $this->setReleaseData ( $release_original, $formData, $new_artist_id );
								$release_original->save ();
								Model_ReleaseSubgenre::updateReleaseSubgenres ( $postData ['release_id'], $subgenre_data, $release_original->upc );
								$release = $release_original;
							}

						} else if ($postData ['release_id'] == 0) {
							$upc = Service_Upc::getNewUPC();
							$release_add = new Model_Releases ();
							$release_add->upc = $upc;
							$release_add->release_status = 'label_processing';
							//$release_add->ingestion_completed = new Doctrine_Expression("NOW()");
							$release_add = $this->setReleaseData ( $release_add, $formData, $new_artist_id );
							$release_add->save ();
							/** setup the release status information**/
							$release_status = new Model_ReleaseStatus ();
							$release_status->release_id = $release_add->release_id;
							$release_status->status = 'label_processing';
							$release_status->date = date ( 'Y-m-d' );
							$release_status->changed_by = $vend_contact_id;
							$release_status->changed_by_type = 'vendor';
							$release_status->upc = $upc;
							$release_status->save ();
							$cd_receive_info = Model_CdReceive::initiateCdReceive ( $upc, $vendor_id, 'release_builder', $formData ['artist_name'], $formData ['release_name'] );
							// release subgenre
							Model_ReleaseSubgenre::addReleaseSubgenres ( $release_add->release_id, $subgenre_data, $upc );
							$release = Model_Releases::getReleaseByReleaseID ( $release_add->release_id );

						}
						$release_id = ($postData ['release_id'] > 0) ? $postData ['release_id'] : $release_add->release_id;

						if ($release->release_status != 'in_content') {
							//primary artist
							$this->processReleaseArtist ( $formData ['primary_artist'], 'performer', $release, $updated_by, $updated_type );

							// featuring artist
							$this->processReleaseArtist ( $formData ['featuring'], 'featuring', $release, $updated_by, $updated_type );

							// Remixer artist
							$this->processReleaseArtist ( $formData ['remixer'], 'remixer', $release, $updated_by, $updated_type );
						}
						/** classical field processing  */
						if ($formData ['genre'] == 12) {
							if ($postData ['release_id'] > 0 && $current_release_status == 'in_content') {
								$i = 0;
								$release_artist_arr = array();
								foreach($release_update->release_artist as $releaseArtistObj){
									if(strlen($releaseArtistObj->release_artist_id) > 0){
										$i = $releaseArtistObj->release_artist_id;
									}else{
										$i++;
									}
									$release_artist_arr[$releaseArtistObj->role][$i] = $releaseArtistObj->artist_name;
								}
								isset($release_artist_arr['composer']) ? ksort($release_artist_arr['composer']) : $release_artist_arr['composer'] = array();
								isset($release_artist_arr['orchestra']) ? ksort($release_artist_arr['orchestra']) : $release_artist_arr['orchestra'] = array();
								isset($release_artist_arr['ensemble']) ? ksort($release_artist_arr['ensemble']) : 	$release_artist_arr['ensemble'] = array();
								isset($release_artist_arr['conductor']) ? ksort($release_artist_arr['conductor']) : $release_artist_arr['conductor'] = array();

								$classical_info = $formData ['classical_info'];
								$classical_artists = array ();
								$classical_roles = explode ( '^', $classical_info );
								$isChanged = false;
								foreach ( $classical_roles as $classical_role_entry ) {
									$classical_data = explode ( '=', $classical_role_entry );
									$original_list = implode('|' , $release_artist_arr[$classical_data [0]]);

									if($classical_data [1] != $original_list){ // compare with original values if changed
										$isChanged = true;
									}

									$classical_artist_names = explode ( '|', $classical_data [1] );
									foreach ( $classical_artist_names as $classical_artist_names_entry ) {
										$classical_artists [$release_id] [] = array ('artist_name' => $classical_artist_names_entry, 'role' => $classical_data [0] );
									}
								}
								if($isChanged){
									Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'releases', 'release_artist', $release_id, json_encode ( $classical_artists ), $updated_by, $updated_type );
								}
							} else {
								$this->processClassicalReleaseArtists ( $release, $formData ['classical_info'] );
							}
						}
						else if ($release->release_status != 'in_content') {
							$this->clearClassicalReleaseArtists ( $release_id );
						}

						if (! isset ( $upc ))
							$upc = $release->upc;

						if($formData['product_type'] == 'music_video'){
							$releaseTracks = Model_Track::getTracksByUpc($upc);
							if($releaseTracks->count()==1 && $releaseTracks[0]->track_type == 'video'){
								$Model_track = $releaseTracks[0];
							}else{
								$Model_track = new Model_Track();
								$Model_track->upc = $upc;
								$Model_track->release_id = $release_id;
								$Model_track->track_type = 'video';
							}
							$isrcInput = ($formData['isrc_type']=='OWN') ? $formData['isrc'] : NULL;
							$Model_track->isrc = $isrcInput;
							$Model_track->explicit_lyrics = $formData['explicit'];
							$Model_track->p_line = $formData['p_line'];
							$Model_track->track_name = $formData['release_name'];
							$Model_track->save();
							$track_id = $Model_track->identifier();
							if(isset($formData['credits_info']) ){
								$creditsObj = new Web_Controller_Action_Helper_Credits($track_id['id']);
								$creditsObj->processCredits($formData['credits_info']);
							}
						}
					}
				}

				/** set up array to return to view for release level processing */
				$new_release_info = array ();
				/** if leaving classical genre, apply all classical data to the regular 'performer' level to each track */
				if (($this_genre_id == 12) && ($postData ['genre'] != 12)) {
					/** get tracks for release */
					$release_correction = new Service_ReleaseCorrection ($this->getRelease()->release_id);
					$tracks = $release_correction->getTrackCorrections ( $this->getRelease (), true );
					/** foreach track level artist in the tracks, apply to 'performer' */
					foreach ( $tracks as $track_value ) {
						$track_artist_correction_arr = array ();
						$track_id = $track_value->id;
						$track_artists = $track_value->track_artist;
						foreach ( $track_artists as $track_artist ) {
							if (isset ( $current_release_status ) && $current_release_status == 'in_content') {
								$track_artist_correction_arr [$track_id] [] = array ('name' => $track_artist->name, 'type' => 'performer' );
							} else {
								$trackartist_update = Model_TrackArtist::getTrackArtistByParams ( array ("id" => $track_artist->id ) );
								$trackartist_update->track_id = $track_id;
								$trackartist_update->type = 'performer';
								$trackartist_update->name = trim ( $track_artist->name );
								$trackartist_update->save ();
							}
							$new_release_info [$track_id] [] = trim ( $track_artist->name );
						}
						if (! empty ( $track_artist_correction_arr )) {
							Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $this->getRelease ()->release_id, 'track', 'track_artist', $track_id, json_encode ( $track_artist_correction_arr ), $updated_by, $updated_type );
						}
					}
				}
				/** if "classical" genre, gather classical release level fields */
				if ($postData ['genre'] == 12) {
					$classical_release_fields = Array ();
					$classical_roles = explode ( '^', $formData ['classical_info'] );
					foreach ( $classical_roles as $classical_role_entry ) {
						$classical_data = explode ( '=', $classical_role_entry );
						$role = $classical_data [0];
						$classical_artist_names = explode ( '|', $classical_data [1] );
						$classical_release_fields [$role] = $classical_artist_names;
					}
					$new_release_info ['classical_release_fields'] = $classical_release_fields;
				}
				$new_release_info ['current_release_id'] = $release_id;
				echo json_encode ( $new_release_info );
			}
		} catch ( Exception $e ) {
			echo json_encode ( $e->getMessage () );
		}
		exit ();
	}

	/**
	 * function processClassicalReleaseArtists
	 * submit a ^ separated list of name value pair 'role'='name'|'another name' ( | separated list of names)
	 *
	 * @param $release_id, id of current release to process classical artists for
	 * @param string $classical_info
	 *
	 * @example $classical_info string contents:
	 * classical_info	"performer=first last|first last^composer=first last|first last^orchestra=first last|first last^ensemble=first last|first last^conductor=first last|first last"
	 */
	protected function processClassicalReleaseArtists($release, $classical_info) {
		/** remove previous entries */
		$this->clearClassicalReleaseArtists ( $release->release_id );
		$classical_artists = array ();
		$classical_roles = explode ( '^', $classical_info );
		foreach ( $classical_roles as $classical_role_entry ) {
			$classical_data = explode ( '=', $classical_role_entry );
			$role = $classical_data [0];
			$classical_artist_names = explode ( '|', $classical_data [1] );
			foreach ( $classical_artist_names as $classical_artist_names_entry ) {
				if (! empty ( $classical_artist_names_entry )&&(strlen(trim($classical_artist_names_entry)) > 0)) {
					$artist_name = $classical_artist_names_entry;
					/** release model */
					$release_artist = new Model_ReleaseArtist ();
					$release_artist->release_id = $release->release_id;
					$release_artist->upc = $release->upc;
			        $release_artist->artist_name = trim ( $artist_name );
					$release_artist->role = trim ( $role );
					$release_artist->save ();
				}
			}
		}
	}

	/**
	 * function clearClassicalReleaseArtists
	 * clear out release level artists in the classical roles
	 *
	 * @param int $release_id, current release id to work with
	 */
	protected function clearClassicalReleaseArtists($release_id) {
		Model_ReleaseArtist::deleteReleaseArtists ( $release_id );
	}

	function reloadsubgenresAction() {
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			if ($postData ['genre_id']) {
				$subgenres = Model_SubGenre::getSubGenres ( $postData ['genre_id'] );
				if (! empty ( $subgenres )) {
					echo '<option value="">' . $this->view->translate->_ ( "Select subgenre" ) . '</option>';
					foreach ( $subgenres as $key => $values ) {
						echo '<option value="' . $values->orchard_id . '">' . $values->name . '</option>';
					}
				}
			}
		}
		echo '';
		exit ();
	}

	function reloadimprintsAction() {
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			if ($postData ['vendor_id']) {
				$release_ids = Model_Vendor::getReleases ( $postData ['vendor_id'] );
				$imprints = array ();
				echo '<option value="">Select imprint</option>';
				if (! empty ( $release_ids )) {
					foreach ( $release_ids as $key => $value ) {
						$release_data = Model_Releases::getReleaseByReleaseID ( $value->release_id );
						if ($release_data && ! in_array ( $release_data->label, $imprints )) {
							echo '<option value="' . $release_data->label . '">' . $release_data->label . '</option>';
							$imprints [] = $release_data->label;
						}
					}
				}
			}
		}
		echo '';
		exit ();
	}

	function createartistmodalAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->view->countries = Model_Country::getAllCountries ();
		$this->view->release_id = 0;
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			$this->view->release_id = $postData ['release_id'];
			$form = new Alw_Form_CreateArtistForm ( $postData );
			$this->view->form = $form;
		}
	}

	function appendHttpAction($url) {
		if (trim ( $url ) && substr ( trim ( $url ), 0, 7 ) != 'http://' && substr ( trim ( $url ), 0, 8 ) != 'https://' && substr ( trim ( $url ), 0, 6 ) != 'ftp://') {
			$url = 'http://' . trim ( $url );
		}
		return $url;
	}

	function removeMyspaceAction($url) {
		if (substr ( trim ( $url ), 0, 7 ) == 'http://') {
			$url = substr ( trim ( $url ), 7 );
		}
		if (substr ( trim ( $url ), 0, 11 ) == 'myspace.com') {
			$url = substr ( trim ( $url ), 11 );
		} else if (substr ( trim ( $url ), 0, 15 ) == 'www.myspace.com') {
			$url = substr ( trim ( $url ), 15 );
		}
		return $url;
	}

	function validateartistAction($formData) {
		$errors = '';

		if (! $formData ['artist_name']) {
			$errors .= 'Missing artist name';
		}

		if (! $formData ['artist_country']) {
			$errors .= ($errors ? '; ' : '') . 'Missing artist country';
		}

		if (trim ( $formData ['artist_website'] ) && ! Zend_Uri::check ( $formData ['artist_website'] )) {
			$errors .= ($errors ? '; ' : '') . 'Invalid URL: ' . $formData ['artist_website'];
		}

		if ($formData ['myspace_url'] && ! Zend_Uri::check ( $formData ['myspace_url'] )) {
			$errors .= ($errors ? '; ' : '') . 'Invalid Myspace URL: ' . $formData ['myspace_url'];
		}

		if ($formData ['additional_websites']) {
			$url_array = explode ( ';', $formData ['additional_websites'] );
			foreach ( $url_array as $key => $url ) {
				$url = $this->appendHttpAction ( $url );
				if (! Zend_Uri::check ( $url )) {
					$errors .= ($errors ? '; ' : '') . 'Invalid additional website URL: ' . $url;
				}
			}
		}

		return $errors;
	}

	function validatetracksAction() {
		$errors = '';
		if ($this->_request->isPost ()) {
			$track_data = $this->_request->getPost ();
			if (! $track_data ['track_name']) {
				$errors .= 'Missing track name';
			}
			if (! $track_data ['p_line']) {
				$errors .= ($errors ? '; ' : '') . 'Missing P-line';
			}
			if (!$track_data ['third_party_publisher'] || $track_data ['third_party_publisher'] == NULL || $track_data ['third_party_publisher'] == "") {
				$errors .= ($errors ? '; ' : '') . 'Missing Deduct Publishing';
			}
		}
		echo $errors;
		exit ();
	}

	/**
	 * function savetracksAction
	 * upon edit track modal update for track, complete this AJAX which persists info to DB
	 */
	function savetracksAction() {
		try {
			$response = array ();

			if ($this->_request->isPost ()) {

				$track_arr = $this->_request->getPost ();
				$added_track_count = 0;

				/** get session orchard admin user or vendor id */
				$sessionData = new Zend_Session_Namespace ( 'Alw' );
				$updated_by = $sessionData->S_VEND_CONTACT_ID;
				$updated_type = "vendor";
				if (isset ( $sessionData->S_USERID )) {
					$updated_by = $sessionData->S_USERID;
					$updated_type = "oa";
				}

				$releaseObj = '';
				$track_artist = new Model_TrackArtist ();
				$track_writer = new Model_TrackWriter ();
				$track_publisher = new Model_TrackPublisher ();
				foreach ( $track_arr as $key => $track_values ) {
					$track_correction_arr = array ();
					$release_id = $track_values ['release_id'];
					if (empty($releaseObj) && !$releaseObj instanceof Model_Releases ){
						$releaseObj = Model_Releases::getReleaseByReleaseID ( $release_id );
					}
					$track_id = $track_values ['unique_track_id'];
					if (! empty ( $track_id ) && is_numeric ( $track_id )) {
						$track_update = Model_Track::getTrackByID ( $track_id );
						if ($releaseObj->release_status == 'in_content') {
							if ($track_values ['old_trackName'] != $track_values ['trackName'])
								$track_correction_arr ['track_name'] = $track_values ['trackName'];
							if ($track_values ['old_explicit'] != $track_values ['explicit'])
								$track_correction_arr ['explicit_lyrics'] = $track_values ['explicit'];
							if ($track_values ['old_pline'] != $track_values ['pline'])
								$track_correction_arr ['p_line'] = $track_values ['pline'];
							if ($track_values ['old_trackLanguage'] != $track_values ['trackLanguage'])
								$track_correction_arr ['meta_language'] = $track_values ['trackLanguage'];

							Model_ReleaseCorrectionDetail::saveCorrectionData ( $release_id, 'track', $track_id, $track_correction_arr, $updated_by, $updated_type );
							$releaseCorrectionObj = $releaseObj->ReleaseCorrection[count($releaseObj->ReleaseCorrection)-1];
							if ($releaseObj->genre_id != 12){
								Model_ReleaseCorrectionDetail::deleteCorrectionDataByParams(array('release_correction_id'=>$releaseCorrectionObj->release_correction_id ,'field_name'=>'track_artist','table_name'=>'track','key_id'=>$track_id));
							}
						} else {
							$track_update->bonus = 'N';
							$track_update->track_name = $track_values ['trackName'];
							if (isset ( $track_values ['isrc'] )) {
								if (! Web_Controller_Plugin_ReleaseUtility::checkISRC ( $track_values ['isrc'] ) && $track_values ['isrc']) {
									throw new Exception ( "Invalid ISRC: " . $track_values ['isrc'] );
								}
							}
							$track_update->isrc = $track_values ['ISRC'];
							$track_update->explicit_lyrics = $track_values ['explicit'];
							if(!empty($track_values['third_party_publisher']) || $track_values['third_party_publisher'] != NULL || $track_values['third_party_publisher'] != ""){
								$track_update->third_party_publisher =  $track_values['third_party_publisher'];
							}

							if ($track_values ['pline']) {
								$thePattern = '/[1-9][0-9]{3}/';
								if (! preg_match ( $thePattern, $track_values ['pline'] )) {
									throw new Exception ( "Invalid pline." );
								}
							}
							$track_update->p_line = $track_values ['pline'];
							$track_update->last_updated = new Doctrine_Expression ( 'NOW()' );
							$track_update->meta_language = $track_values['trackLanguage'];
							$track_update->save ();
						}
						$track_type = $track_update->track_type;

					/** UPDATE REJECTION TABLE IF IT IS REJECTED RELEASE*/
						if (count($releaseObj->ReleaseApprovalQueue) > 0) {
								$approvalQueue = $releaseObj->ReleaseApprovalQueue[count($releaseObj->ReleaseApprovalQueue)- 1];
								if($approvalQueue->status == 'rejected'){ // LATEST STATUS IS REJECTED SO UPDATED THE CORRECTION
									$changedFieldNames = (isset($track_values ['rejectionCorrectedFields'])  ? $track_values ['rejectionCorrectedFields'] : array()) ;
									$countChanged = count($changedFieldNames);
									$queryFields = array();
									$approval_id = $approvalQueue->release_approval_id;
									if($countChanged == 0 && $changedFieldNames != array()){ // SINGLE VALUE
										$changedFieldNames = array($changedFieldNames);
									}

									if($countChanged > 0){
										in_array('explicit', $changedFieldNames) ? $changedFieldNames[] = 'explicit_lyrics'  : '';
										if($releaseObj->genre_id == '12'){
											in_array('performer', $changedFieldNames) ? $changedFieldNames[] = 'track_artist'  : '';
										}
										$rejectionObjs = Model_RejectionNotes::getRejectionId($changedFieldNames , $track_id, $approval_id);
										$rejectionIds = array();
										foreach($rejectionObjs as $key => $value){
											if($value){
												foreach($value as $ids){
													$rejectionIds[] = $ids;
												}
											}
										}
										if(count($rejectionIds) > 0){
											$countChanged = Model_RejectionNotes::updateRejectionComments( $rejectionIds ); //array("release_approval_id"=>$approvalQueue->release_approval_id , "table_name"=>"'track'" ) ,
										}
									}
								}

						} // REJECTION QUEUE UPDATES - ENDS

					} else {
						$track_add = new Model_Track ();
						$track_add->upc = $releaseObj->upc;
						$track_add->release_id = $track_values ['release_id'];
						$track_add->bonus = 'N';
						$track_add->track_name = $track_values ['trackName'];
						if (isset ( $track_values ['isrc'] )) {
							if (! Web_Controller_Plugin_ReleaseUtility::checkISRC ( $track_values ['isrc'] ) && $track_values ['isrc']) {
								throw new Exception ( "Invalid ISRC: " . $track_values ['isrc'] );
							}
						}
						$track_add->isrc = $track_values ['ISRC'];
						$track_add->explicit_lyrics = $track_values ['explicit'];

						if ($track_values ['pline']) {
							$thePattern = '/[1-9][0-9]{3}/';
							if (! preg_match ( $thePattern, $track_values ['pline'] )) {
								throw new Exception ( "Invalid pline." );
							}
						}
						$track_add->p_line = $track_values ['pline'];

						$track_add->cd = $track_values ['cd'];
						$track_add->track_id = $track_values ['trackNumber'];
						$track_add->original_file_name = html_entity_decode ( $track_values ['original_file_name'] );
						$track_add->last_updated = new Doctrine_Expression ( 'NOW()' );
						$track_add->meta_language = $track_values['trackLanguage'];
						$track_add->save ();
						$track_id = $track_add->id;
						$track_type = $track_add->track_type;
					}

					$releaseCorrectionObj = new Service_ReleaseCorrection($releaseObj->release_id);
					$releaseCorrectionInfo = $releaseCorrectionObj->getReleaseCorrection($releaseObj,true);

					if($releaseCorrectionInfo->Genre->genre_id != 12 ){
						$track_roles = array('performer' => $track_values ['trackLevelArtists'], 'featuring' => $track_values['trackLevelFeaturingArtists'], 'remixer' => $track_values['trackLevelRemixer']);
						foreach($track_roles as $role => $track_role){
							$artist_array = $this->createTrackArtistAndReleaseArray($role, $track_role, $release_id);
							/** if track artist is same as release artist, delete track artist using role */
							if($artist_array['track_artist'] == $artist_array['release_artist']){
								$track_artist->deleteTrackArtistsByRole($track_id, $role);
							}
						}
					}
					if (isset ( $track_values ['trackLevelArtists'] ) && ! empty ( $track_values ['trackLevelArtists'] )) {
						$releaseArtistInfo = $releaseCorrectionInfo->performer;
						$existing_roles_release_names = array();
						if ($releaseArtistInfo){
							foreach ($releaseArtistInfo as $artist_data){
								$existing_roles_release_names[] = $artist_data->artist_name;
							}
						}

						/** gather form submitted entries for trackLevelArtists from token input ('|' divided) */
						$tmp_track_artists = explode ( "|", $track_values ['trackLevelArtists'] );

						/** delete empty nodes */
						$tmp_track_artists = array_diff ( $tmp_track_artists, array ('' ) );
						$existing_roles_release_names = array_diff ( $existing_roles_release_names, array ('' ) );

						$tmp_track_artists_list = implode ( "|", $tmp_track_artists );
						$existing_roles_release_names_list = implode ( "|", $existing_roles_release_names );

						/** If different in some way, we've left the default state:
						 */
						if($tmp_track_artists_list != $existing_roles_release_names_list){
							if ($releaseObj->release_status == 'in_content') {
								if ($track_values ['old_trackLevelArtists'] != $track_values ['trackLevelArtists']) {
									$track_artist_correction_arr = array ();
									foreach ( $tmp_track_artists as $each_track_artist ) {
										$track_artist_correction_arr [$track_id] [] = array ('name' => $each_track_artist, 'type' => 'performer' );
									}
									Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'track', 'track_artist', $track_id, json_encode ( $track_artist_correction_arr ), $updated_by, $updated_type );
								}
							} else {
								$track_artist->deleteTrackArtistsByRole ($track_id, 'performer' );
								foreach ( $tmp_track_artists as $tmp_track_artists_entry ) {
									$track_artist_add = new Model_TrackArtist ();
									$track_artist_add->track_id = $track_id;
									$track_artist_add->name = trim ( $tmp_track_artists_entry );
									$track_artist_add->type = 'performer';
									$track_artist_add->save ();
								}
							}
						}
					}
					// featuring artist
					$old_featuring = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['old_trackLevelFeaturingArtists']);
					$new_featuring = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['trackLevelFeaturingArtists']);

					if (strcmp($old_featuring, $new_featuring) != 0)
					{
						$this->processTrackArtist($track_values['trackLevelFeaturingArtists'], 'featuring', $releaseObj, $track_id, $updated_by, $updated_type);
					}

					//  remixer artist
					$old_remixer = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['old_trackLevelRemixer']);
					$new_remixer = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['trackLevelRemixer']);
					if (strcmp($old_remixer, $new_remixer) != 0)
					{
						$this->processTrackArtist($track_values['trackLevelRemixer'], 'remixer', $releaseObj, $track_id, $updated_by, $updated_type);
					}

					// track writer
					if (isset ( $track_values ['writers'] ) && ! empty ( $track_values ['writers'] )) {
						$tmp_track_writers = explode ( "|", $track_values ['writers'] );
						$tmp_track_writers = array_diff ( $tmp_track_writers, array ('' ) );
						if ($releaseObj->release_status == 'in_content') {
							$old_writer = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['old_writers']);
							$new_writer = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['writers']);
							if (strcmp($old_writer, $new_writer) != 0) {
								$track_writer_correction_arr = array ();
								foreach ( $tmp_track_writers as $each_track_writer ) {
									$track_writer_correction_arr [$track_id] [] = $each_track_writer;
								}
								Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'track', 'track_writer', $track_id, json_encode ( $track_writer_correction_arr ), $updated_by, $updated_type );
							}
						}else{
							$track_writer->deleteTrackWriterByTrackId ( $track_id );
							foreach ( $tmp_track_writers as $each_track_writer ) {
								if (! empty ( $each_track_writer ) && (strlen(trim($each_track_writer)) > 0)) {
									$track_writer_add = new Model_TrackWriter ();
									$track_writer_add->unique_track_id = $track_id;
									$track_writer_add->track_id = $track_values ['trackNumber'];
									$track_writer_add->cd = $track_values ['cd'];
									$track_writer_add->writer_name = trim ( $each_track_writer );
									$track_writer_add->upc = $track_values ['upc'];
									$track_writer_add->save ();
								}
							}
						}
					}

					// track publisher
					if (isset ( $track_values ['publishers'] ) && ! empty ( $track_values ['publishers'] )) {
						$tmp_track_publishers = explode ( "|", $track_values ['publishers'] );
						$tmp_track_publishers = array_diff ( $tmp_track_publishers, array ('' ) );
						if ($releaseObj->release_status == 'in_content') {
							$old_publisher = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['old_publishers']);
							$new_publisher = preg_replace('/'. preg_quote('|', '/') . '$/', '', $track_values['publishers']);

							if (strcmp($old_publisher, $new_publisher) != 0) {
								$track_publisher_correction_arr = array ();
								foreach ( $tmp_track_publishers as $each_track_publisher ) {
									$track_publisher_correction_arr [$track_id] [] = $each_track_publisher;
								}
								Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'track', 'track_publisher', $track_id, json_encode ( $track_publisher_correction_arr ), $updated_by, $updated_type );
							}
						}else{
							$track_publisher->deleteTrackPublisherByTrackId ( $track_id );
							foreach ( $tmp_track_publishers as $each_track_publisher ) {
								if (!empty( $each_track_publisher ) &&(strlen(trim($each_track_publisher)) > 0)){
									$track_publisher_add = new Model_TrackPublisher ();
									$track_publisher_add->unique_track_id = $track_id;
									$track_publisher_add->track_id = $track_values ['trackNumber'];
									$track_publisher_add->cd = $track_values ['cd'];
									$track_publisher_add->publisher_name = trim ( $each_track_publisher );
									$track_publisher_add->upc = $track_values ['upc'];
									$track_publisher_add->save ();
								}
							}
						}
					}
					/** classical field processing roles list */
					if($releaseObj && $releaseCorrectionInfo->Genre->genre_id == 12 ){
						$track_roles = array ('performer', 'composer', 'orchestra', 'ensemble', 'conductor' );
						$changedFlag = 0;

						/** iterate through each role for classical entries */
						$track_artist_correction_arr = array ();
						foreach ( $track_roles as $role ) {
							if (isset ( $track_values [$role] )) {
								/** get role entries for release level */
								$existing_roles_release = $releaseCorrectionInfo->release_artist;
								if($role == 'performer'){
									$existing_roles_release = $releaseCorrectionInfo->performer;
								}
								/** gather form submitted entries for that role from token input ('|' divided) */
								if (strcmp(trim($track_values['old_'.$role]), trim($track_values [$role])) != 0)
								{
									$changedFlag = 1;
								}
								$tmp_track_role = explode ( "|", $track_values [$role] );
								/** gather an artist_names list only of the existing release level artists */
								$existing_roles_release_names = array ();
								foreach ( $existing_roles_release as $existing_role_entry_id => $existing_role_entry_name ) {
									if ($existing_role_entry_name->role == $role)
										$existing_roles_release_names [] = $existing_role_entry_name->artist_name;
								}

								$tmp_track_role_list = implode ( "|", $tmp_track_role );
								$existing_roles_release_names_list = implode ( "|", $existing_roles_release_names );
								/** If different in some way, we've left the default state:
								 */
							   	if ($releaseObj->release_status != 'in_content') {
					         		$track_artist->deleteTrackArtistsByRole ( $track_id, $role );
						        }
								if ($role == "composer") {
									if ($releaseObj->release_status == 'in_content') {
										foreach ( $tmp_track_role as $tmp_track_role_entry ) {
											$track_artist_correction_arr [$track_id] [] = array ('name' => $tmp_track_role_entry, 'type' => $role );
										}
									} else {
										foreach ( $tmp_track_role as $tmp_track_role_entry ) {
											if (! empty ( $tmp_track_role_entry ) && (strlen(trim($tmp_track_role_entry)) > 0))
											{
												$track_artis_add = new Model_TrackArtist ();
												$track_artis_add->track_id = $track_id;
												$track_artis_add->name = trim ( $tmp_track_role_entry );
												$track_artis_add->type = $role;
												$track_artis_add->save ();
											}
										}
									}
								}
								else if ($tmp_track_role_list != $existing_roles_release_names_list  && $role !="composer") {
									if ($releaseObj->release_status == 'in_content') {
										foreach ( $tmp_track_role as $tmp_track_role_entry ) {
											$track_artist_correction_arr [$track_id] [] = array ('name' => $tmp_track_role_entry, 'type' => $role );
										}
									} else {
										foreach ( $tmp_track_role as $tmp_track_role_entry ) {
											if (! empty ( $tmp_track_role_entry ) && (strlen(trim($tmp_track_role_entry)) > 0))
											{
												$track_artis_add = new Model_TrackArtist ();
												$track_artis_add->track_id = $track_id;
												$track_artis_add->name = trim ( $tmp_track_role_entry );
												$track_artis_add->type = $role;
												$track_artis_add->save ();
											}
										}
									}
								}
							}
						}

						if(!empty($track_artist_correction_arr) && $changedFlag == 1)
						{
							Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release_id, 'track', 'track_artist', $track_id, json_encode ( $track_artist_correction_arr ), $updated_by, $updated_type );
						}
					}
					$response [$track_values ['unique_id']] ['track_id'] = intval ( $track_id );
					$response [$track_values ['unique_id']] ['cd'] = intval ( $track_values ['cd'] );
					$response [$track_values ['unique_id']] ['trackNumber'] = intval ( $track_values ['trackNumber'] );
					$response [$track_values ['unique_id']] ['original_file_name'] = $track_values ['original_file_name'];
					$response [$track_values ['unique_id']] ['inprogress'] = intval ( $track_values ['inprogress'] );
					$response [$track_values ['unique_id']] ['import_asset_id'] = (! empty ( $track_values ['import_asset_id'] ) ? intval ( $track_values ['import_asset_id'] ) : 0);
					$response [$track_values ['unique_id']] ['trackType'] = $track_type;
					$added_track_count ++;
				}
				if ($added_track_count) {
					echo json_encode ( $response );
				}
			}
		} catch ( Exception $e ) {
			echo json_encode ( array ("err" => $e->getMessage () ) );
		}
		exit ();
	}

	function verifythumbnailmodalAction(){
		$this->_helper->layout()->disableLayout();
		$this->view->asset_id = $this->_request->getParam('asset_id');
		$this->view->upc = $this->_request->getParam('upc');
	}

	function uploadartworkmodalAction(){
		$this->_helper->layout()->disableLayout();
	}


	/**
	 * This Action is triggered by the Uploadify Flash object which uploads audio files onto the apache webserver tmp location.
	 * This function validates the POST and moves it to a temporary set location on the webserver.
	 * @return string Temporary file name of the uploaded file.
	 */
	public function uploadifyAction() {
		$response = array ();
		$form = new Alw_Form_UploadForm ();
		$this->view->form = $form;
		if ($this->_request->isPost ()) {
			$formData = $this->_request->getPost ();
			if ($form->isValidPartial ( $formData )) {
				$response ['original_filename'] = "";
				$upload = new Zend_File_Transfer_Adapter_Http ();
				$file_info = $upload->getFileInfo ();
				if ($file_info) {
					$response ['code'] = 1;
					$identifier = "Filedata";
					if (array_key_exists ( "UPLOAD_IDENTIFIER", $formData )) {
						$identifier = $formData ["UPLOAD_IDENTIFIER"];
					}
					$response ['original_filename'] = $file_info [$identifier] ['name'];
				}
				$id = Zend_Session::getId();
				$fname = $response['original_filename'];
				$timestamp = time();
				$path_parts = pathinfo($response['original_filename']);
				$extension = $path_parts['extension'];
				$new_name = md5($id . $fname) . $timestamp . "." . $extension;
				$upload->setDestination(PUBLIC_PATH . '/tmp/')->addFilter('Rename', $new_name)->addValidator('Size', false,
				array('min'=>'10kB', 'max'=>'900MB'));
				if(!$upload->isValid()){
					//Zend_Debug::dump($upload->getMessages(), 'Invalid Upload File:');
					$response ['code'] = 0;
					$response ['msg'] = "<br>File Upload Error<br>";
					foreach ( $upload->getErrors () as $error_code ) {
						switch ($error_code) {
							case 'fileSizeTooSmall' :
							case 'fileSizeNotFound' :
								$response ['msg'] .= $this->view->translate->_ ( "fileSizeError" );
								break;
							case 'fileSizeTooBig' :
								$response ['msg'] .= "The audio file you uploaded is <b>very large (over 900 mb)</b>. Please upload only Raw, uncompressed WAV audio files, 44.1khz, 16-bit, stereo quality.<br>";
								break;
							case 'fileMimeTypeFalse' :
							case 'fileMimeTypeNotDetected' :
							case 'fileMimeTypeNotReadable' :
								$response ['msg'] .= "Your audio file is not a .WAV file. Please upload only Raw, uncompressed WAV audio files, 44.1khz, 16-bit, stereo quality.<br>";
								break;
						}
					}
					$this->logger ( $this->_request->getPost ( 'upc' ) . "\t" . $response ['msg'], 2 );
					$this->logger ( $this->_request->getPost ( 'release_id' ) . "\t" . $response ['msg'], 2 );
					echo json_encode ( $response );
					exit ();
				}
				try {
					$upload->receive ();
				} catch ( Zend_File_Transfer_Exception $e ) {
					//TODO: Add error handling if file transfer fails (Send Email to IT)
					//Zend_Debug::dump($e->getMessage(),'Server Upload Error');
					$response ['code'] = 0;
					$response ['msg'] = 'Server upload error.';
					$this->logger ( $this->_request->getPost ( 'release_id' ) . "\tFile transfer to temp folder failed." . $e->getMessage (), 0 );
					echo json_encode ( $response );
					exit ();
				}
				$file_info = $upload->getFileInfo ();
				if ($file_info) {
					$response ['code'] = 1;
					$identifier = "Filedata";
					if (array_key_exists ( "UPLOAD_IDENTIFIER", $formData )) {
						$identifier = $formData ["UPLOAD_IDENTIFIER"];
					}
					$response ['destination'] = $file_info [$identifier] ['tmp_name'];
					echo json_encode ( $response );
				}
			} else {
				$response ['code'] = 0;
				$response ['msg'] = $this->view->translate->_ ( "invalidUploadPost" );
				echo json_encode ( $response );
				exit ();
			}
		}
		exit ();
	}

	public function deletetracksAction($track_remove_names = array()) {
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
		}
		if (array_key_exists ( "track_remove_names", $postData )) {
			$track_remove_names = $postData ['track_remove_names'];
			$track_remove_names = array ($track_remove_names );
		}

		$this->_helper->Assetingestion->deleteAudioAssets ( $track_remove_names );

		if (strtolower ( $this->_request->getActionName () ) != "updatetracks") {
			exit ();
		}
	}

	/**
	 * This Action is triggered by the Uploadify Flash object which uploads image files onto the apache webserver tmp location.
	 * This function validates the POST and moves it to a temporary set location on the webserver.
	 * @return string Temporary file name of the uploaded file.
	 */

	public function imageuploadifyAction() {
		$response = array ();
		$form = new Alw_Form_UploadForm ();
		$this->view->form = $form;
		if ($this->_request->isPost ()) {
			$formData = $this->_request->getPost ();
			if ($form->isValidPartial ( $formData )) {
				$upload = new Zend_File_Transfer_Adapter_Http ();
				$validator = new Web_Validate_Chain_Coverart ();

				//TODO Change the dest path on launch
				$upload->setDestination(PUBLIC_PATH . '/tmp/')->addValidator($validator);

				if (! $upload->isValid ()) {
					//TODO: Add error handling if file validation fails
					//Zend_Debug::dump($upload->getMessages(), 'Invalid Upload File:');
					$response ['code'] = 0;
					$response ['msg'] = "";
					$dimension_error_displayed = false;
					foreach ( $upload->getErrors () as $error_code ) {
						switch ($error_code) {
							case Zend_Validate_File_MimeType::FALSE_TYPE :
							case Zend_Validate_File_MimeType::NOT_DETECTED :
							case Zend_Validate_File_MimeType::NOT_READABLE :
							case Web_Validate_File_Imagemagick_Resolution::NOT_READABLE :
							case Zend_Validate_File_Size::NOT_FOUND :
							case Zend_Validate_File_Size::TOO_SMALL :
							case Zend_Validate_File_Size::TOO_BIG :
							case Web_Validate_File_Imagemagick_Colorspace::NOT_VALID :
							case Web_Validate_File_Imagemagick_Colorspace::NOT_INVALID :
							case Web_Validate_File_Imagemagick_Colorspace::NOT_READABLE :
							case Web_Validate_File_Imagemagick_Imagetypecompressed::NOT_VALID :
							case Web_Validate_File_Imagemagick_Squareimage::NOT_SQUARE :
								$response ['msg'] .= $this->view->translate->_ ("The artwork file you supplied falls outside of the image requirements. Please upload a JPG or TIFF file sized at 1500 x 1500 pixels or greater, 72 DPI or greater, in RGB. No layers, color masks, filters or crop/fold marks are permitted.");
								break;
							case Zend_Validate_File_ImageSize::WIDTH_TOO_BIG :
							case Zend_Validate_File_ImageSize::WIDTH_TOO_SMALL :
							case Zend_Validate_File_ImageSize::HEIGHT_TOO_BIG :
							case Zend_Validate_File_ImageSize::HEIGHT_TOO_SMALL :
							case Zend_Validate_File_ImageSize::NOT_DETECTED :
							case Zend_Validate_File_ImageSize::NOT_READABLE :
							case Web_Validate_File_Imagemagick_Resolution::RESOLUTION_SMALL :
								if (! $dimension_error_displayed) {
									$response ['msg'] .= $this->view->translate->_ ("The artwork file you supplied falls outside of the image requirements. Please upload a JPG or TIFF file sized at 1500 x 1500 pixels or greater, 72 DPI or greater, in RGB. No layers, color masks, filters or crop/fold marks are permitted.");
									$dimension_error_displayed = true;
								}
								break;

						}
					}
					$this->logger ( $this->_request->getPost ( 'asset_code' ) . "\t" . $response ['msg'], 3 );
					ob_clean ();
					echo json_encode ( $response );
					exit ();
				}

				try {
					$this->logger ( "uploading...", 2 );
					$upload->receive ();
				} catch ( Zend_File_Transfer_Exception $e ) {
					//TODO: Add error handling if file transfer fails (Send Email to IT)
					//Zend_Debug::dump($e->getMessage(),'Server Upload Error');
					$response ['code'] = 0;
					$response ['msg'] = $e->getMessage ();
					$this->logger ( $this->_request->getPost ( 'asset_code' ) . "\tFile transfer to temp folder failed." . $e->getMessage (), 1 );
				}

				try {
					if ($this->_request->getPost ( 'correction_flag' )) {
						$job_type = "replace";
					} else {
						$job_type = "upload";
					}
					$asset_code = pathinfo ( $formData ['asset_code'] );
					$upc = $asset_code ["filename"];
					$importAssetBatch = new Model_ImportAssetBatch ();
					$sessionData = new Zend_Session_Namespace ( 'Alw' );
					$importAssetBatch->vendor_contact_id = $sessionData->S_VEND_CONTACT_ID;
					$importAssetBatch->impersonated = $sessionData->S_USERID;
					$importAssetBatch->ip_address = ( int ) (sprintf ( "%u\n", ip2long ( $_SERVER ['REMOTE_ADDR'] ) ));
					$importAssetBatch->last_updated = new Doctrine_Expression ( 'NOW()' );
					$importAssetBatch->import_type = $job_type;
					$release = Model_Releases::getReleaseByParams ( array ('upc' => $upc ) );
					$importAsset = new Model_ImportAsset ();
					$importAsset->import_asset_batch_id = 0;
					$importAsset->foldername = $release->vendor_catalog_number;
					$importAsset->filename = $this->_request->getPost ( 'asset_code' );
					$importAsset->asset_type = 'image';
					$detail = new Model_ImportAssetDetail ();
					$detail->upc = $release->upc;
					$detail->track_id = 0;
					$importAsset->ImportAssetDetail [] = $detail;
					$importAssetBatch->ImportAsset [] = $importAsset;
					$importAssetBatch->save ();
				} catch ( Exception $e ) {
					$this->logger ( $e->getMessage (), 2 );
				}

				$response ['code'] = 1;
				if(! $this->convertImageTo300dpi($upload->getFileName())){
					$response ['code'] = 0;
					$response ['msg'] = "Error on converting resolutions. Please check TIF upload specifications.";
				} else {
					if (! $response ['msg'] = $this->convertImage ( $upload->getFileName (), $this->_request->getPost ( 'asset_code' ), $upload->getMimeType (), $job_type )) {
						$response ['code'] = 0;
						$response ['msg'] = "Error converting TIF to JPG. Please check TIF upload specifications.";
					} else {
						/** get session orchard admin user or vendor id */
						$user_id = $this->_request->getPost ( 'user_id' );
						$user_type = $this->_request->getPost ( 'user_type' );

						//Irrespective of the type of file uploaded, always upload only the TIF file.
						$srcfilename = substr ( $upload->getFileName (), 0, strrpos ( $upload->getFileName (), "." ) ) . ".tif";
						if (! $this->uploadAction ( $srcfilename, $this->_request->getPost ( 'asset_code' ), $importAssetBatch->ImportAsset [0]->id, $this->_request->getPost ( 'correction_flag' ), $user_id, $user_type )) {
							$response ['code'] = 0;
							$response ['msg'] = "Server error while copying uploaded file";
						} else {
							$upc = substr ( $this->_request->getPost ( 'asset_code' ), 0, - 4 );

							$cd_receive_info = Model_CdReceive::getCdReceiveByUpc ( $upc );
							if (! $cd_receive_info) {
								$cd_receive_info = new Model_CdReceive ();
								$cd_receive_info->upc = $upc;
							}
							$cd_receive_info->vendor_id = $this->_request->getPost ( 'vendor_id' );
							$cd_receive_info->image_scan = 'Y';
							$cd_receive_info->image_scan_date = date ( 'Y-m-d' );
							$cd_receive_info->asset_type = 'release_builder';
							$cd_receive_info->date_received = new Doctrine_Expression ( "NOW()" );
							$cd_receive_info->save ();

							//If uploaded file is JPG, it needs to be specifically deleted after successful upload of the TIF file.
							//Upload of TIF file will move the TIF but not the JPG that was uploaded.
							if ($this->check_extension ( $upload->getFileName (), "jpg" )) {
								unlink ( $upload->getFileName () );
							}
						}
					}
				}
				ob_clean ();
				echo json_encode ( $response );
			}
		}
		exit ();
	}

	public function check_extension($filename, $ext) {
		$ext = ltrim ( $ext, "." );
		$extension = trim ( strtolower ( substr ( strrchr ( $filename, '.' ), 1 ) ) );
		if ($extension == $ext) {
			return true;
		}
		return false;
	}

	public function convertImageTo300dpi($src_file) {
		$sizeOriginal = getimagesize($src_file);
        if (empty($sizeOriginal) || ($sizeOriginal[0] === 0) || ($sizeOriginal[1] === 0)) {
            return false;
        }
        if ($sizeOriginal[0] > 1500) {
        	$cmdSize = 'convert -resize 1500x1500 "' . $src_file . '" "' . $src_file . '"';
			$webShellExecSize = new Web_Shellexec();
			$webShellExecSize->ExecCmd($cmdSize);

			$sizeNew = getimagesize($src_file);
			if(empty($sizeOriginal) || ($sizeOriginal[0] === 0) || ($sizeOriginal[1] === 0) || $sizeNew[0] > 1500){
				return false;
			}
        }
		$imageOriginal 	= new Imagick($src_file);
		$resolution 	= $imageOriginal->getImageResolution();
		if($resolution['x'] < 300 || $resolution['y'] < 300){
			$cmd = 'convert -strip -density 300x300 "' . $src_file . '" "' . $src_file . '"';
			$webShellExec = new Web_Shellexec();
			$webShellExec->ExecCmd($cmd);

			$imageUpdated	= new Imagick($src_file);
			$resolutionNew	= $imageUpdated->getImageResolution();
			if($resolutionNew['x'] < 300 || $resolutionNew['y'] < 300){
				return false;
			}
		}
		return true;
	}

	public function convertImage($src_file, $img_file, $mime_type = null, $job_type = 'upload') {
		$track_code = preg_replace ( '/\.[^.]*$/', '', basename ( $img_file ) );

		if ($mime_type == 'image/jpeg' || $mime_type == 'image/jpg') {
			$dest_file = substr ( $src_file, 0, strrpos ( $src_file, "." ) ) . ".tif";
			$conversion_cmd = 'convert "' . $src_file . '" -compress None "' . $dest_file . '"';
			shell_exec ( $conversion_cmd );
			if (! file_exists ( $dest_file ) || ! filesize ( $dest_file )) {
				return false;
			}
			chmod ( $dest_file, 0644 );
		} else {
			//In case the tif file extension is all caps "TIF" or any variation such as "tiff", etc.
			$dest_file = substr ( $src_file, 0, strrpos ( $src_file, "." ) ) . ".tif";
			$filterFileRename = new Zend_Filter_File_Rename ( array ('target' => $dest_file, 'overwrite' => true, 'source' => $src_file ) );
			$filterFileRename->filter ( $src_file );
		}
		$src_file = $dest_file;
		$correctionFolder = '';
		if ($job_type == "replace") {
			$correctionFolder .= "_correction";
		}
		$dest_file = PUBLIC_PATH . "/images/lg_coverart" . $correctionFolder . "/$track_code" . ".jpg";
		$dest_thumb = PUBLIC_PATH . "/images/coverart" . $correctionFolder . "/c_$track_code" . ".jpg";

		$conversion_cmd = 'convert -colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 360x360 "' .
		$src_file . '" "' . $dest_file . '"';
		shell_exec ( $conversion_cmd );
		if (! file_exists ( $dest_file ) || ! filesize ( $dest_file )) {
			return false;
		}
		chmod ( $dest_file, 0644 );

		$conversion_cmd = 'convert -colorspace RGB -compress JPEG -density 72x72 -antialias -quality 100 -resize 110x110 "' .
		$src_file . '" "' . $dest_thumb . '"';
		shell_exec ( $conversion_cmd );
		if (! file_exists ( $dest_thumb ) || ! filesize ( $dest_thumb )) {
			return false;
		}
		chmod ( $dest_thumb, 0644 );

		return basename ( $dest_file );
	}

	/**
	 * This Action is triggered upon completion of the uploadifyAction by the Uploadify flash object.
	 * It moves the uploaded file from the temporary location on the webserver onto the temporary location on the main storage array.
	 * It then issues a webservice call to one of the encoders to convert the uploaded audio to MP3, move the WAV and MP3 to main storage array and file the locations in the DB.
	 * It retrieves MP3 information using the check_audio function and returns it.
	 * @return string JSON encoded information about the encoded version of the uploaded file
	 */
	public function uploadAction($sourceFile = null, $destFilename = null, $import_asset_id = null, $correction_flag1 = false, $user_id = null, $user_type = 'vendor', $reupload_filename = null) {
		try {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNeverRender ( true );
			$correction_flag = false;
			$is_image_file = false;
			$filename = "";
			if (! $sourceFile || ! $destFilename) {
				if ($this->_request->isPost ()) {
					$sourceFile = $this->_request->getPost ( 'filename' );
					$correction_flag1 = $this->_request->getPost ( 'correction_flag' );

					if ($correction_flag1 === true || $correction_flag1 == "true") {
						$correction_flag = true;
					}
					if ($correction_flag && $this->_request->getPost ( 'import_asset_id' )) {
						$getImportAsset = Model_ImportAsset::getImportAssetByID ( $this->_request->getPost ( 'import_asset_id' ) );
						$filename = $getImportAsset->filename;
					}
					if ($this->check_extension ( $sourceFile, "wav" )) {
						$destFilename = $this->_request->getPost ( 'asset_code' ) . ".wav";

						if ($correction_flag === "true") {
							$asset_type_id = 353;
						} else {
							$asset_type_id = 1;
						}
					} elseif ($this->check_extension ( $sourceFile, "m4a" )) {
						$destFilename = $this->_request->getPost ( 'asset_code' ) . ".m4a";
						$asset_type_id = 310;
					}
					$total_tracks = $this->_request->getPost ( 'total_tracks' );
					$import_asset_id = $this->_request->getPost ( 'import_asset_id' );
					$this->_helper->Assetingestion->deleteAudioAssets ( array ($this->_request->getPost ( 'asset_code' ) ), $correction_flag );

					/** get session orchard admin user or vendor id */
				    $sessionData = $this->getSession();
				    $updated_by = $sessionData->S_VEND_CONTACT_ID;
				    $updated_type = "vendor";
				    if (isset ( $sessionData->S_USERID )) {
				      $updated_by = $sessionData->S_USERID;
				      $updated_type = "oa";
				    }
				}
			} else {
				$is_image_file = true;
				if ($correction_flag1 === "true" || $correction_flag1 === true || $correction_flag1 === '1' || $correction_flag1 === 1) {
					$correction_flag = true;
					$asset_type_id = 355;
					$filename = $destFilename;
				} else {
					$asset_type_id = 2;
				}
				/** get session orchard admin user or vendor id */
				$updated_by = $user_id;
				$updated_type = $user_type;
			}
			$track_code = preg_replace ( '/\.[^.]*$/', '', $destFilename );
			if ($correction_flag === "true" || $correction_flag === true || $correction_flag === '1' || $correction_flag === 1) {
				$job_type = "replace";
				if ($is_image_file === false) {
					$destFilename = $filename . "." . pathinfo ( $sourceFile, PATHINFO_EXTENSION );
				}
			} else {
				$job_type = "upload";
			}
			$destFile = Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $destFilename;

			// Rename uploaded file using Zend Framework
			$filterFileRename = new Zend_Filter_File_Rename ( array ('target' => $destFile, 'overwrite' => true, 'source' => $sourceFile ) );
			if (! $filterFileRename->filter ( $sourceFile )) {
				echo "Error Copying file to temp storage";
				exit ();
			}

			if ($import_asset_id) {
				$importAsset = Model_ImportAsset::getImportAssetByID ( $import_asset_id );
				$importAsset->status = "upload_complete";
				$importAsset->upload_completed = new Doctrine_Expression ( 'NOW()' );
				$importAsset->save ();
			}
			if (strpos ( $track_code, "_" ) !== false) {
				list ( $upc, $track_id ) = explode ( "_", $track_code );
			} else {
				$upc = $track_code;
			}

			$track_id = isset ( $track_id ) ? $track_id : 0;
			$assetReceivedQueue = new Model_AssetReceivedQueue ();
			$assetReceivedQueue->asset_type_id = $asset_type_id;
			$assetReceivedQueue->priority = 1;
			$assetReceivedQueue->picked_up = "N";
			$assetReceivedQueue->import_asset_id = intval ( $import_asset_id );
			$assetReceivedQueue->uploaded_asset_filename = Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_unc_path . $destFilename;
			$assetReceivedQueue->job_type = $job_type;
			$assetReceivedDestinationDetails = new Model_AssetReceivedDestinationDetails ();
			$assetReceivedDestinationDetails->destination_upc = $upc;
			$assetReceivedDestinationDetails->destination_track_id = $track_id;
			$assetReceivedQueue->AssetReceivedDestinationDetails [] = $assetReceivedDestinationDetails;
			$this->logger ( print_r ( $assetReceivedQueue->toArray (), true ), 2 );
			$assetReceivedQueue->save ();
			if(!($correction_flag))
			{
				if (! empty ( $track_id ) && is_numeric ( $track_id )) {
						$track_update = Model_Track::getTrackByID ( $track_id );
						$track_update->original_file_name = $this->_request->getPost ( 'reupload_filename' );
						$track->last_updated = new Doctrine_Expression ( 'NOW()' );
						$track_update->save();
				}
			}
			if ($correction_flag && ($this->check_extension ( $sourceFile, "wav" ) || $this->check_extension ( $sourceFile, "m4a" ))) {
				$track_correction_arr ['track'] = "true";
				$track_correction_arr ['original_file_name'] = $this->_request->getPost ( 'reupload_filename' );
				$release = Model_Releases::getReleaseByUPC ( $upc );
				$releaseCorrectionObj = new Model_ReleaseCorrectionDetail ();
				Model_ReleaseCorrectionDetail::saveCorrectionData ( $release->release_id, "track", $track_id, $track_correction_arr, $updated_by, $updated_type );
			} else if ($correction_flag && $is_image_file) {
				$coverart_correction_arr ['coverart'] = "true";
				$release = Model_Releases::getReleaseByUPC ( $upc );
				$releaseCorrectionObj = new Model_ReleaseCorrectionDetail ();
				Model_ReleaseCorrectionDetail::saveCorrectionData ( $release->release_id, "releases", $release->release_id, $coverart_correction_arr, $updated_by, $updated_type );
			}
			return true;
		} catch ( Exception $e ) {
			$this->logger ( $e->getMessage (), 2 );
		}
	}

	//Async HTTP call
	private function backgroundPost($url) {
		$parts = parse_url ( $url );
		$fp = fsockopen ( $parts ['host'], isset ( $parts ['port'] ) ? $parts ['port'] : 80, $errno, $errstr, 30 );

		if (! $fp) {
			return false;
		} else {
			$out = "POST " . $parts ['path'] . " HTTP/1.1\r\n";
			$out .= "Host: " . $parts ['host'] . "\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-Length: " . strlen ( $parts ['query'] ) . "\r\n";
			$out .= "Connection: Close\r\n\r\n";
			if (isset ( $parts ['query'] ))
				$out .= $parts ['query'];
			fwrite ( $fp, $out );
			fclose ( $fp );
			return true;
		}
	}

	/**
	 * Returns important information such as path and duration of the MP3 for the specified track code.
	 * @param $track_code UPC_CD_TRACK
	 * @return JSON string Path and duration of the MP3 for the specified track code
	 */
	public function check_audio($track_code) {
		$response_arr = array ();
		$assetDetailsService = new Service_AssetDetails ();
		$response_arr = $assetDetailsService->getAssetDetails ( Service_AssetDetails::MP3_192, Service_AssetDetails::AVL, $track_code );
		$response_enc = json_encode ( $response_arr );
		return $response_enc;
	}

	public function checkimagefileAction() {
		$filePath = 0;
		$isCorrectionFlag = true;
		$vendor_id = $this->getVendorId ();
		$this->checkReleaseVendor ();
		$release = $this->getRelease ();
		if($release->release_status != 'in_content'){
			$isCorrectionFlag = false;
		}
		$release_correction = new Service_ReleaseCorrection ($release->release_id);
		$release_data = $release_correction->getReleaseCorrection ( $release, true );
		$isChangeCoverart = $release_data->isFieldChanged('coverart');
		$product_type = Model_Releases::getReleaseType($release->upc);
		$imageValidator = new Web_Validate_Checkimage ();
		if ($imageValidator->isValid ( $release_data, $isCorrectionFlag, $product_type)) {
			$image_info = Web_Controller_Plugin_ImagePathLookup::getImage($release->upc, false, null, null, $release_data->release_id, $isChangeCoverart);

			$filePath = $image_info['path'];
		}
		$this->view->filename = $filePath;
	}

	/**
	 * Returns important information such as path and duration of the MP3 for the specified track code.
	 * @param $track_code UPC_CD_TRACK
	 * @return JSON string Path and duration of the MP3 for the specified track code
	 */
	public function checkaudiofilesAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		$track_codes = $this->_request->getParam ( "track_codes", array () );
		/*if($this->_request->isPost()){
			$track_codes = $this->_request->getPost('track_codes');
			}*/
		$response_arr = $this->checkAudioFiles ( $track_codes );
		$response_enc = json_encode ( $response_arr );
		echo $response_enc;
		exit ();
	}

	/**
	 * @todo updating length_seconds should be removed - we do it updateuploadrequestAction;  new Service_AssetDetails should be taken out of the loop; also combine this function with checkUploadedTracks
	 */
	public function checkAudioFiles($asset_codes = array()) {
		$response_arr = array ();
		$check_assets_arr = array ();
		$importAsset_status = "";
		$importAsset_result = "";
		$cache = Zend_Registry::get ( 'Cache' );
		foreach ( $asset_codes as $asset_code ) {
			$track_base_name = array ();
			$import_asset_id = 0;
			$response_arr [$asset_code] = array ();
			list ( $upc, $track_unique_id, $import_asset_id ) = explode ( "_", $asset_code );
			if ($import_asset_id) {
				$importAsset = Model_ImportAsset::getImportAssetByID ( $import_asset_id );
				$importAsset_status = ($importAsset !== false) ? $importAsset->status : "";
				$importAsset_result = ($importAsset !== false) ? $importAsset->result : "";
			}
			$import_asset_id = ( int ) $import_asset_id;
			$asset_type = Service_AssetDetails::MP3_192;
			if ($import_asset_id === 0 || $importAsset_status == "finished") {
				$release = Model_Releases::getReleaseByUPC ( $upc );
				$track = Model_Track::getTrackByID ( $track_unique_id );
				if ($release->release_status != "in_content") {
					$track_base_name [] = $upc . "_" . $track_unique_id;
					$assetDetailsServiceObj1 = new Service_AssetDetails ();
					$actual_tracks_obj = $assetDetailsServiceObj1->getAssetDetails ( $asset_type, Service_AssetDetails::AVL, $track_base_name );
				} else {
					$releaseCorrectionDetails = Model_ReleaseCorrection::getActiveCorrection ( $release->release_id );
					if ($releaseCorrectionDetails [0]->ReleaseCorrectionDetails) {
						foreach ( $releaseCorrectionDetails [0]->ReleaseCorrectionDetails as $correctionDetail ) {
							if ($correctionDetail->table_name == "track" && $correctionDetail->field_name == "track" && $correctionDetail->key_value == "true") {
								if ($track_unique_id == $track->id) {
									$track_base_name [] = $track->upc . "_" . $track->cd . "_" . $track->track_id;
									break;
								}
							}
						}
						$asset_type = Service_AssetDetails::MP3_192_CORRECTION;
					}
					$assetDetailsServiceObj2 = new Service_AssetDetails ();
					$actual_tracks_obj = $assetDetailsServiceObj2->getAssetDetails ( $asset_type, Service_AssetDetails::AVL, $track_base_name );
				}
				if (isset ( $actual_tracks_obj [$track_base_name [0]] ) && false !== $actual_tracks_obj [$track_base_name [0]]) {
					$duration = $actual_tracks_obj [$track_base_name [0]] ["duration"];
					//$track = Model_Track::getTrackByID($track_unique_id);
					$track->last_updated = new Doctrine_Expression ( 'NOW()' );
					if (! ($track->length_minute) && ! ($track->length_seconds)) {
						$track->length_minute = ($duration > 0) ? intval(floor($duration/60)) : 0;
						$track->length_seconds = ($duration > 0) ? intval($duration % 60) : 0;
						$track->save ();
					}
					$response_arr [$asset_code] ['duration'] = $actual_tracks_obj [$track_base_name [0]] ["duration"];
					$response_arr [$asset_code] ['path'] = $actual_tracks_obj [$track_base_name [0]] ["streaming_path"];
					$response_arr [$asset_code] ['msg'] = "";
				}
			} else {
				$response_arr [$asset_code] ['msg'] = $this->mapBadFileError ( $importAsset_result );
			}

		}
		return $response_arr;
	}

	public function submitfinalreleaseAction() {
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$user_id = $sessionData->S_USERID;
		$vendor_id = $sessionData->S_VENDOR_ID;
		$err_msg = "";
		if ($this->_request->isPost ()) {
			$release_id = $this->_request->getPost ( 'release_id' );
			$label = Model_Vendor::getVendorByVendorID($vendor_id);
			$release = Model_Releases::getReleaseByParams(array("release_id" => $release_id));
			$active_correction = Model_ReleaseCorrection::getActiveCorrection($release_id);
			if ($label->requiresOneTimeFee() && !Model_ReleasePaymentLog::isUPCPaidFor($release->upc) && !$active_correction->count()) {
				$err_msg .= "Payment is required. Owner of this label is Orchard.";
			} else {
				$validation = $this->validaterelease ( $release_id, $vendor_id );
				if (count ( $validation ) <= 0) {
					$submit = $this->_helper->Assetingestion->submitrelease ( $release );
					if ($submit == true ) {
						exit ();
					} else {
						$err_msg = $submit;
					}
				} else {
					$err_msg = "Validation failed. Please click on Validate Your Release link to make sure all required fields have been entered.";
				}
			}
		}
		echo $err_msg;
		exit ();
	}

	public function validatereleasemodalAction() {
		if ($release_id = $this->_request->getParam ( 'release_id' )) {
			$sessionData = new Zend_Session_Namespace ( 'Alw' );
			$vendor_id = $sessionData->S_VENDOR_ID;
			$release = Model_Releases::getReleaseByReleaseID ( $release_id );
			$response = $this->validaterelease ( $release_id, $vendor_id );
			$isChangeCoverart = false;
			$release_correction = new Service_ReleaseCorrection ($release_id);
			$release_data = $release_correction->getReleaseCorrection ( $release, true );
			$isChangeCoverart = $release_data->isFieldChanged ( 'coverart' );
			//Zend_Debug::dump($response, "Release response:");
		//Zend_Debug::dump(json_decode($this->checkaudiofilesAction($track_codes)), "Release data:");
		} else {
			$response = "Unexpected error occured while trying to validate release. Please try again";
		}
		$this->view->release = $release;
		$this->view->response = $response;
		$this->view->isChangeCoverart = $isChangeCoverart;
		$this->_helper->layout ()->disableLayout ();
	}

	public function validaterelease($release_id, $vendor_id) {
		$response = array ();
		$iscorrection = true;
		if ($release = Model_Releases::getReleaseByReleaseID ( $release_id )) {
			if ($release->release_status != "in_content") {
				$iscorrection = false;
			}
			$correction_obj = Model_ReleaseCorrection::getReleaseCorrectionByReleaseId ( $release_id );
			$release_correction = new Service_ReleaseCorrection ($release->release_id);
			$release_data = $release_correction->getReleaseCorrection ( $release, $iscorrection );
			$db_tracks = $release_correction->getTrackCorrections ( $release, $iscorrection );
			$productType = Model_Releases::getReleaseType($this->getRelease()->upc);
			$this->view->productType = $productType;
			if (($release_data->release_status != 'in_content' || ($release_data->release_status == 'in_content' && isset ( $correction_obj->status ) && $correction_obj->status == "active"))) {
				$upc = $release_data->upc;
				$artist_id = $release_data->artist_id;
				$artist_data = Model_ArtistInfo::getArtistById ( $artist_id );
				if ($release_id == $release_data->release_id) {
					$this->view->upc = $upc;
					$this->view->release_id = $release_data->release_id;
					if (! Web_Controller_Plugin_ReleaseUtility::checkUPC ( $upc )) {
						$response ['release_errors'] ['required'] [] = "Invalid UPC";
					}
				} else {
					$response ['release_missing_errors'] ['required'] [] = "UPC";
				}

				if (! $release_data->release_name) {
					$response ['release_missing_errors'] ['required'] [] = "Release Name";
				}
				if (count($release_data->primary)==0) {
					$response ['release_missing_errors'] ['required'] [] = "Primary Artist";
				}
				if (! $release_data->label) {
					$response ['release_missing_errors'] ['required'] [] = "Imprint";
				}
				if (! $release_data->genre_id) {
					$response ['release_missing_errors'] ['required'] [] = "Genre";
				}
				if($productType == 'music'){
					if (! $release_data->c_line) {
						$response ['release_missing_errors'] ['required'] [] = "C-Line";
					} else {
						$thePattern = '/[1-9][0-9]{3}/';
						if (preg_match ( $thePattern, $release_data->c_line, $matches )) {
							$c_line = $matches [0];
							if ($c_line == $release_data->c_line or $c_line . ' ' == $release_data->c_line) {
								$response ['release_errors'] ['required'] [] = "Invalid C-Line";
							}
						}
					}
				}

				if ($productType == 'music_video' && !$release_data->language_id){
					$response['release_missing_errors']['required'][] = "Language";
				}


				$isChangeCoverart = false;
				$isChangeCoverart = $release_data->isFieldChanged ( 'coverart' );
				if($release_data->not_for_distribution != "Y"){
					$imageValidator = new Web_Validate_Checkimage ();
					if (! $imageValidator->isValid ( $release_data, $iscorrection, $productType)) {
						if ($productType == 'music_video'){
							$response['release_errors']['required'][] = "Video Thumbnail";
						}else{
							$response['release_errors']['required'][] = "Album Art";
						}
					}
				}
				$subgenre = $release_data->release_subgenre;
				if (count ( $subgenre ) <= 0) {
					$response ['release_missing_errors'] ['required'] [] = "Subgenre";
				}
				$db_track_count = isset ( $db_tracks ) ? count ( $db_tracks ) : 0;
				$volumes_tracks_arr = array ();
				if ($db_track_count <= 0) {
					$response ['track_missing_errors'] ['required'] [0] [0] [] = "Tracks Information";
				} else {

					$valid_isrc_arr = array ();
					foreach ( $db_tracks as $db_track ) {
						$cd_arr [] = $db_track->cd;

						$track_arr [] = $db_track->track_id;

						$volumes_tracks_arr [$db_track->cd] [$db_track->id] = $db_track->track_id;
						$track_codes [] = $upc . "_" . $db_track->cd . "_" . $db_track->track_id;
						if (! $db_track->track_name || ($db_track->track_name == "")) {
							$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Track Name";
						}
						if($release_data->genre_id== 12){
							$composerArr = array();
							foreach ($db_track->track_artist as $artist){
								if($artist->type == 'composer')
								$composerArr[]= $artist->name;
							}
							if(count($composerArr) <= 0){
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Composer";
							}
						}
						if (! $db_track->p_line)
							$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "P-Line";
						else {
							$thePattern = '/[1-9][0-9]{3}/';
							if (preg_match ( $thePattern, $db_track->p_line, $matches )) {
								$p_line = $matches [0];
								if ($p_line == $db_track->p_line or $p_line . ' ' == $db_track->p_line) {
									if($productType == 'music'){
										echo $db_track->p_line. " :: ".$db_track->cd. " :: ".$db_track->track_id;

										$response['track_missing_errors']['required'][$db_track->cd][$db_track->track_id][] = "P-Line";
									}else{
										$response['release_missing_errors']['required'][] = "P-Line";
									}
								}
							}
						}
						if($productType != 'music_video'){
							if (0 && ! $db_track->isrc) {
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "ISRC";
							} else {
								if (0 && ! Web_Controller_Plugin_ReleaseUtility::checkISRC ( $db_track->isrc )) {
									$response ['track_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Invalid ISRC";
								} else if (!empty($db_track->isrc)){
									$valid_isrc_arr [] = $db_track->isrc;
								}
							}
						}
						if ($release_data->release_status != 'in_content' && (empty ( $db_track->third_party_publisher ) || $db_track->third_party_publisher == NULL || $db_track->third_party_publisher == "")) {
							$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Deduct Publishing";
						}
						$track_artists = $db_track->track_artist;
						if (isset ( $artist_data->name ) && $artist_data->name == "Various Artists") {
							if (! count ( $track_artists )) {
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Track Artist";
							}
						}
						if ($productType == 'music_video'){
							if(!empty($db_track->isrc)){
								if(!Web_Controller_Plugin_ReleaseUtility::checkISRC($db_track->isrc)){
									$response['release_errors']['required'][] = "Invalid ISRC";
								}else{
									$valid_isrc_arr[] = $db_track->isrc;
								}
							}

							if(!$db_track->explicit_lyrics){
								$response['release_missing_errors']['required'][] = "Explicit";
							}

							if(!$db_track->track_type){
								$response['release_missing_errors']['required'][] = "Track Type";
							}

							$dashboardItem = Model_VideoDashboardItem::getDashboardItemByVendorIdUpcAndFilename($vendor_id, $upc, $db_track->original_file_name);
							if (!$dashboardItem){
								$response['video_missing_errors']['required'][] = "No Video";
							}else{
								$status = Model_VideoDashboardStatus::getDashboardStatusByStatusTypeId($dashboardItem->status_type_id);
								switch($status->status_type){
									case 'Processing (Mastering)':
										$response['video_missing_errors']['required'][] = "In Processing";
										break;
									case 'Processing (Error)':
										$response['video_missing_errors']['required'][] = "Processing Error";
										break;
									case 'Fail':
										$response['video_missing_errors']['required'][] = "Processing Fail";
										break;
									case 'Complete':
										$previewVideo = Model_TrackVideoMarker::getPreviewByTrackId($db_track->id);
										if(!$previewVideo){
											$response['video_missing_errors']['required'][] = "No Preview Video";
										}
										break;
								}
							}
						}
						/*$track_artist = $db_track->getRoleWiseTrackArtists($track_artists);
						$track_composer_count = isset($track_artist['composers']) ? count($track_artist['composers']) : 0;

						if ($track_composer_count < 1 && $release_data->genre_id == 12) {
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Track Composer";
						}*/

						if ($db_track->track_type != "video") {
							if (! $db_track->meta_language || ($db_track->meta_language == "")) {
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Meta Language";
							}
							if($release_data->not_for_distribution != "Y"){
								//Check whether actual asset exist
								if ($release_data->release_status == "label_processing") {
									$actual_track_codes = $upc . "_" . $db_track->id;
								} else {
									$actual_track_codes = $upc . "_" . $db_track->cd . "_" . $db_track->track_id;
								}
								$actual_tracks_obj = $db_track->track;

								if (! isset ( $actual_tracks_obj [$actual_track_codes] ["file_size"] )) {
									$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Audio";
								}
							}
						} else {
							$trackVideo = Doctrine_Query::create()->from('Model_TrackVideo t')->where( 't.id=?', $db_track->id)->fetchone();
							if($trackVideo){
								$resolution = $trackVideo->resolution;
								$fps = $trackVideo->fps;
								$aspect_ratio = $trackVideo->aspect_ratio;
							}
							if (!isset($resolution) || !$resolution)
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Video is missing Resolution.";
							if (!isset($fps) || ! $fps)
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Video is missing FPS.";
							if (!isset($aspect_ratio) || ! $aspect_ratio)
								$response ['track_missing_errors'] ['required'] [$db_track->cd] [$db_track->track_id] [] = "Video is missing Aspect Ratio.";

						}
					}

					$validation_arr = $this->validateCDAndTracks ( $cd_arr, $track_arr );

					if (isset ( $validation_arr ['Track'] ) || isset ( $validation_arr ['Cd'] )) {
						$this->reorderTracks ( $db_tracks, $volumes_tracks_arr, (isset ( $validation_arr ['Cd'] ) ? $validation_arr ['Cd'] : 0), (isset ( $validation_arr ['Track'] ) ? $validation_arr ['Track'] : 0) );
					}

					//Check duplicated ISRC
					if (count ( $valid_isrc_arr ) != count ( array_unique ( $valid_isrc_arr ) )) {
						$response ['release_errors'] ['required'] [] = "Duplicate ISRCs";
					}
				}
			} else {
				$response ['release_errors'] ['required'] [] = "Transfered to content";
			}
		} else {
			$response ['release_errors'] ['required'] [] = "No release exists";
		}
		return $response;
	}

	public function viewreleaseAction() {
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$this->view->streaming_url = Zend_Registry::get ( "configuration" )->streaming->full_length;
		if ($this->_request->isGet ()) {
			//Set objects that are independent of release type
			$genre_obj = new Model_Interim_Genre ();
			$this->view->genres = $genre_obj->getGenres ();
			$subgenre_obj = new Model_Interim_Subgenre ();
			$subgenres = array ();
			$subgenre_names = array ();
			$release = new Model_Interim_Releases ();

			if ($this->_request->getQuery ( 'release_id' )) {
				$release_id = $this->_request->getQuery ( 'release_id' );
				$release = new Model_Interim_Releases ( $release_id );
				$release_data = $release->getReleaseData ();
				$upc = $release_data ["upc"];
				$this->view->is_label_copy = true;
			} elseif ($this->_request->getQuery ( 'upc' )) {
				$this->view->is_label_copy = false;
				$upc = $this->_request->getQuery ( 'upc' );
				$release_data = $release->getReleaseDataByUpc ( $upc );
				$release_id = $release_data ["release_id"];
			} else {
				$this->_helper->redirector ( "index", "index", "alw" );
			}

			if (! Web_Controller_Plugin_ReleaseUtility::confirmReleaseOwnership ( $vendor_id, $upc )) {
				$this->_helper->layout ()->disableLayout ();
				$this->_helper->viewRenderer->setNeverRender ( true );
				$this->getResponse ()->setRedirect ( "/alw/" )->sendResponse ();
				return;
			}

			//Set required objects
			if (count ( $release_data )) {
				$cd_receive_info = $release->getCdReceiveInfo ( $vendor_id );
				$this->view->release_status = $release_data ['release_status'];
				$release_subgenre_obj = new Model_Interim_ReleaseSubgenre ();
				$subgenre_str = implode ( ',', $release_subgenre_obj->getReleaseSubgenres ( $release_id ) );
				$track = new Model_Interim_Track ();
				$track_data = $track->getTracks ( $release_id );
			}

			$this->view->release = $release_data;
			$artist = new Model_Interim_Artist ( $release_data ["artist_id"] );
			$artist_data = $artist->getArtistData ();
			$this->view->artist = $artist_data;

			if ($artist_data ['vendor_id'] != $vendor_id) {
				$this->_helper->redirector ( "index", "index", "alw" );
			}

			//Set subgenre
			if ($subgenre_str) {
				$subgenres = $subgenre_obj->getSubgenresByOrchardIDs ( $subgenre_str );
			}
			foreach ( $subgenres as $key => $value ) {
				$subgenre_names [] = $value ['name'];
			}
			$this->view->subgenres = implode ( ', ', $subgenre_names );
			//Set total track count
			$this->view->total_num_of_track = count($track_data);

			/** get possible classical information for release level display */
			if ($release_data ['genre_id'] == '12') {

				$performers_list = "";
				$composers_list = "";
				$orchestras_list = "";
				$ensembles_list = "";
				$conductors_list = "";

				$fields = $this->getClassicalReleaseFields ( $this->_request->getQuery ( 'release_id' ), $this->_request->getQuery ( 'upc' ) );

				foreach ( $fields ['performers'] as $performer_entry ) {
					$performers_list .= $performer_entry . ", ";
				}
				foreach ( $fields ['composers'] as $composer_entry ) {
					$composers_list .= $composer_entry . ", ";
				}
				foreach ( $fields ['orchestras'] as $orchestra_entry ) {
					$orchestras_list .= $orchestra_entry . ", ";
				}
				foreach ( $fields ['ensembles'] as $ensemble_entry ) {
					$ensembles_list .= $ensemble_entry . ", ";
				}
				foreach ( $fields ['conductors'] as $conductor_entry ) {
					$conductors_list .= $conductor_entry . ", ";
				}

				$this->view->performers_list = trim ( $performers_list, ", " );
				$this->view->composers_list = trim ( $composers_list, ", " );
				$this->view->orchestras_list = trim ( $orchestras_list, ", " );
				$this->view->ensembles_list = trim ( $ensembles_list, ", " );
				$this->view->conductors_list = trim ( $conductors_list, ", " );
			}

			$this->view->genre_id = $release_data ['genre_id'];

			/** Get Token for Flash Player Authentication */
			$MediaAuthenticator = new Web_Controller_Plugin_MediaAuthenticator ();
			$media_token = $MediaAuthenticator->getToken ( $vendor_id, 'stream', 'alw' );

			$connectionArgs = array ();
			$connectionArgs ['user_id'] = $vendor_id;
			$connectionArgs ['token'] = $media_token;
			$connectionArgs ['user_type'] = 'alw';

			$this->view->connectionArgs = $connectionArgs;
			$this->view->product_type = Model_Releases::getReleaseType($this->getRelease()->upc);
		}
	}

	/**
	 * function logger
	 * log alw.log entries to disk on the server
	 *
	 * Error Types:
	 * 0	= Audio Upload Error
	 * 1	= Image Upload Error
	 * 2	= Invalid Audio Format
	 * 3	= Invalid Image Format
	 *
	 * @param string $message
	 * @param int $code
	 */
	public function logger($message, $code = 0) {
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$line = date ( "Y-m-d H:i:s" ) . "\t" . $_SERVER ['REMOTE_ADDR'] . "\t" . $vendor_id . "\t" . $code . "\t" . $message . "\n";
		file_put_contents ( MODULE_PATH . "/alw/data/logs/alw.log", $line, FILE_APPEND );
	}

	public function getWAVError($info) {
		$err_msg = "";
		switch ($info ['fileformat']) {
			case 'riff' :
				if ($info ['riff'] ['audio'] [0] ['sample_rate'] != 44100) {
					$err_msg .= ' -> Invalid frequency: ' . $info ['riff'] ['audio'] [0] ['sample_rate'] . "Hz\n. ";
				}
				if ($info ['riff'] ['audio'] [0] ['bitrate'] != 1411200) {
					$err_msg .= ' -> Invalid bitrate: ' . $info ['riff'] ['audio'] [0] ['bitrate'] . "bps\n";
				}
				if ($info ['riff'] ['audio'] [0] ['channels'] != 2) {
					$err_msg .= ' -> Invalid channels: ' . $info ['riff'] ['audio'] [0] ['channels'] . "\n";
				}
				if ($info ['filesize'] <= 0) {
					$err_msg .= ' -> Invalid filesize: ' . $info ['filesize'] . "bytes\n";
				}
				break;
			default :
				if ($info ['audio'] ['sample_rate'] != 44100) {
					$err_msg .= ' -> Invalid frequency: ' . $info ['audio'] ['sample_rate'] . "Hz\n";
				}
				if ($info ['audio'] ['bitrate'] != 1411200) {
					$err_msg .= ' -> Invalid bitrate: ' . $info ['audio'] ['bitrate'] . "bps\n";
				}
				if ($info ['audio'] ['channels'] != 2) {
					$err_msg .= ' -> Invalid channels: ' . $info ['audio'] ['channels'] . "\n";
				}
				if ($info ['filesize'] <= 0) {
					$err_msg .= ' -> Invalid filesize: ' . $info ['filesize'] . "bytes\n";
				}
				break;
		}
		return $err_msg;
	}

	public function cacheassetinfoAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		if ($this->_request->isPost ()) {
			$audioData = $this->_request->getPost ();
			if (isset ( $audioData ['status_code'] ) && $audioData ['status_code'] == 'success') {
				list ( $release_id, $track_unique_id ) = explode ( "_", $audioData ['asset_code'] );
				$track = Model_Track::getTrackByID ( $track_unique_id );
				$track->last_updated = new Doctrine_Expression ( 'NOW()' );
				$asset_info = unserialize ( $audioData ['msg'] );
				$track->length_minute = $asset_info ['length_minute'] ? $asset_info ['length_minute'] : 0;
				$track->length_seconds = $asset_info ['length_seconds'] ? $asset_info ['length_seconds'] : 0;
				$track->save ();
			}
			$cache = Zend_Registry::get ( 'Cache' );
			$cache->save ( $audioData, 'audio_status_' . $audioData ['asset_code'] );
		}
	}

	public function confirmreleasemodalAction() {
		$this->_helper->layout ()->disableLayout ();
		// get session data.
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			$release_id = $postData ['release_id'];
			$releaseData = Model_Releases::getReleaseByReleaseID($release_id);
			$this->view->release_id = $release_id;
			$release_validation = $this->validaterelease ( $this->view->release_id, $vendor_id );
			if (count ( $release_validation ) > 0) {
				$this->_helper->redirector ( 'validatereleasemodal', "releasebuilder", "alw", array ('release_id' => $this->view->release_id ), true );
			}
			$vendor = Model_Vendor::getVendorByVendorID ( $vendor_id );
			$this->view->payment_required = false;
			$active_correction = Model_ReleaseCorrection::getActiveCorrection($release_id);
			if ($vendor->requiresOneTimeFee () && !Model_ReleasePaymentLog::isUPCPaidFor($releaseData->upc) && !$active_correction->count()){
				$this->view->payment_required = true;
			}
		}
	}

	function checkreuploadprogressAction() {
		$getData = $this->_request->getPost ();
		//id is used to located the identifer that is posted together with the file.
		if ($getData ["id"]) {
			$result = uploadprogress_get_info ( $getData ["id"] );
			$result ["track_id"] = $getData ['track_id'];
			echo json_encode ( $result );
		}
		exit ();
	}

	/**
	 * display dialog for processing a payment for a vendor via a modal window dialog in the view
	 */
	function enterpaymentmodalAction(){

		$this->_helper->layout()->disableLayout();
		$this->view->currency = new Zend_Currency('en_US');
		$productType = Model_Releases::getReleaseType($this->getRelease()->upc);
		$this->view->productType = $productType;

		/** get session data */
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$vendor_contact_id = $sessionData->S_VEND_CONTACT_ID;

		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			$this->view->release_id = $postData ['release_id'];
			if (! is_numeric ( $postData ['release_id'] )) {
				exit ();
			}
			$release = new Model_Releases ();
			$release_id = $postData ['release_id'];
			$this->view->release_data = $release->getReleaseByReleaseID ( $release_id );
			$release_validation = $this->validaterelease ( $this->view->release_id, $vendor_id );
			if (count ( $release_validation ) > 0) {
				$this->_helper->redirector ( 'validatereleasemodal', "releasebuilder", "alw", array ('release_id' => $release_id ), true );
			}

			$this->view->application_fees = $this->getApplicationFees ( $release ['distribution'] );

			$vendorData = Model_Vendor::getVendorByVendorID ( $vendor_id );

			$model_artist = Model_ArtistInfo::getArtistById ( $this->view->release_data ['artist_id'] );
			$this->view->artist = $model_artist;
			/** check if vendor has a credit card on file */
			$this->view->hasCreditCard = Model_CreditCardInfo::doesCreditCardExist ( $vendor_id );

			/** check if logged in user has permission to add a new credit card */
			$roleresourceprivilege_validator = new Web_Validate_Checkroleresourceprivilege ();
			$this->view->canSaveCreditCardInfo = $roleresourceprivilege_validator->isValid ( array ("resource" => "account", "privilege" => "savecreditcardinfo" ) );

			/** get credit card info */
			$this->view->creditCardInfo = Model_CreditCardInfo::getCreditCardInfo ( $vendor_id );
			$this->view->release_id = $postData ['release_id'];

			/** check if credit card has expired and whether it is valid */
			$this->view->flg_isValidCreditCard = Model_CreditCardInfo::isCreditCardValid ( $vendor_id );

			/**
			 * initializes all variables for form
			 */
			$this->view->image_info = Web_Controller_Plugin_ImagePathLookup::getImage ( $this->view->release_data ['upc'], false, 100, 100, $this->view->release_id );
			$this->view->cardholder_name = $this->view->creditCardInfo ? $this->view->creditCardInfo->cardholder_name : "";
			$this->view->cc_type = $this->view->creditCardInfo ? $this->view->creditCardInfo->cc_type : "";
			$this->view->last_4_digits = $this->view->creditCardInfo ? $this->view->creditCardInfo->last_4_digits : "";
			$this->view->exp_date = $this->view->creditCardInfo ? $this->view->creditCardInfo->exp_date : "";
			$this->view->cvv2 = $this->view->creditCardInfo ? $this->view->creditCardInfo->cvv2 : "";
		}
	}

	function getApplicationFees($distribution) {
		if ($distribution == "digital") {
			$applfees = 35.00;
		} elseif ($distribution == "phys/digital") {
			$applfees = 69.00;
		} else {
			$applfees = 35.00;
		}
		return $applfees;
	}

	/**
	 * process payment for a releasebuilder activity
	 */
	function processpaymentAction() {
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
		$this->view->currency = new Zend_Currency ( 'en_US' );

		if ($this->_request->isPost ()) {
			$postData = $this->_request->getPost ();
			$release_id = $postData ['release_id'];
			$release = Model_Releases::getReleaseByReleaseID ( $release_id );
			if (! $release) {
				$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
				return;
			}

			$release_id = $release->release_id;
			$upc = $release->upc;
			if (Model_ReleasePaymentLog::isUPCPaidFor ( $upc )) {
				$this->view->response = "UPC Has Been Paid For";
				$submitRelease = $this->_helper->Assetingestion->submitrelease ( $release );
				if (! ($submitRelease === true)) {
					$this->view->errors = $submitRelease;
					$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
				}
				return;
			}

			$release_validation = $this->validaterelease ( $release_id, $vendor_id );
			if (count ( $release_validation ) > 0) {
				$this->view->errors = $this->view->translate->_ ( "Release could not be validated." );
				$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
				return;
			}
			$applicationFees = $this->getApplicationFees ( $release->distribution );
			$creditCardHelper = new Web_Controller_Action_Helper_Creditcard ();
			$creditCardInfo = Model_CreditCardInfo::getCreditCardInfo ( $vendor_id );
			$comment = 'Release Fee for release_id: ' . $release_id . " and upc: " . $upc;
			$currency = "USD";
			$creditCardResult = $creditCardHelper->processPayment ( $creditCardInfo, $applicationFees, $comment, $currency );
			$creditCardLog = $creditCardResult->getCreditCardLog ();

			/** handle special payflow network congestion exception */
			if ($creditCardResult->getErrorCode () == 10609) {
				$this->view->errors = $this->view->translate->_ ( "Error Processing Transaction." ) . "<br/>" . $this->view->translate->_ ( "Please try again later or update your credit card info." );
				$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
			} else {
				// save one time payment and subscription LOGS
				$releasePaymentLogOneTime = new Model_ReleasePaymentLog ();
				$releasePaymentLogOneTime->amount = $applicationFees;
				$releasePaymentLogOneTime->credit_card_log_id = $creditCardLog->id;
				$releasePaymentLogOneTime->upc = $upc;
				$releasePaymentLogOneTime->currency = $currency;
				$releasePaymentLogOneTime->feetype = 'onetime';
				$releasePaymentLogOneTime->payment_date = date ( 'Y-m-d' );
				if ($creditCardResult->wasSuccessful ()) {
					$releasePaymentLogOneTime->result = 'success';
				} else {
					$releasePaymentLogOneTime->result = 'fail';
				}
				$releasePaymentLogOneTime->save ();

				/** result 0 designates success */
				if ($creditCardResult->getErrorCode () == 0 && $vendor_id) {
					$this->view->response = $this->view->translate->_ ( "Credit Card payment has been approved." );
					/** submit final release */
					$submitRelease = $this->_helper->Assetingestion->submitrelease ( $release );
					if (! ($submitRelease === true)) {
						$this->view->errors = $submitRelease;
						$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
					}
				} else {
					$this->view->errors = $creditCardResult->getErrorMessage ();
					$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
				}
			}
		}
	}

	/**
	 * update vendor's credit card information during a releasebuilder activity that may be in process
	 */
	function updatecreditcardAction() {

		/** get session data */
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;

		if ($this->_request->isPost ()) {

			$submittedCreditCard = $this->_request->getPost ();
			$submittedCreditCard ['auth_date'] = $submittedCreditCard ['month'] . substr ( $submittedCreditCard ['year'], 2, 2 );
			$submittedCreditCard ['vendor_id'] = $vendor_id;

			/** gather any errors from the Zend Validate Credit Card Utility and generate an array of which span error "ids" should show up on UI after this AJAX call */
			$errors = array ();
			$credit_card_validator = new Zend_Validate_CreditCard ();
			$credit_card_validator->setType ( array (Zend_Validate_CreditCard::MASTERCARD, Zend_Validate_CreditCard::VISA, Zend_Validate_CreditCard::AMERICAN_EXPRESS, Zend_Validate_CreditCard::DISCOVER ) );
			if (! $credit_card_validator->isValid ( $submittedCreditCard ['credit_card_number'] )) {
				$errors [] = "invalid_credit_card_number";
			}

			/** 4 numeric digits for American Express, else 3 for CVV2 */
			($submittedCreditCard ['cc_type'] == "American Express") ? $cvv2Check = "/^[0-9]{4}$/" : $cvv2Check = "/^[0-9]{3}$/";
			if (! ($submittedCreditCard ['cvv2']) || ! preg_match ( $cvv2Check, $submittedCreditCard ['cvv2'] )) {
				$errors [] = "invalid_security_code";
			}
			if (! ($submittedCreditCard ['cardholder_name'])) {
				$errors [] = "invalid_cardholder_name";
			}
			if (! ($submittedCreditCard ['cc_type'])) {
				$errors [] = "invalid_credit_card_selection";
			}
			if (count ( $errors )) {
				$errors = array_unique ( $errors );
				$this->view->errors = $errors;
				$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
			}

			/** run through payflow if no validation errors */
			if (count ( $errors ) == 0) {
				/** authorize card is valid */
				$creditCardHelper = new Web_Controller_Action_Helper_Creditcard ();
				$authorizedCreditCard = $creditCardHelper->authorizeCreditCard ( $submittedCreditCard );

				/** handle special payflow network congestion exception */
				if ($authorizedCreditCard ['RESULT'] == 10609) {
					$this->view->payflow_error = $this->view->translate->_ ( "Error Processing Transaction." ) . "<br/>" . $this->view->translate->_ ( "Please try again later or update your credit card info." );
					$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
				} else {
					/** result 0 designates sucess */
					if ($authorizedCreditCard ['RESULT'] == 0 && $vendor_id) {

						$this->view->response = $this->view->translate->_ ( "Credit Card payment has been approved." );

						$credit_card_info = Model_CreditCardInfo::getCreditCardInfo ( $vendor_id );
						if (! $credit_card_info) {
							$credit_card_info = new Model_CreditCardInfo ();
							$credit_card_info->date_created = date ( "Y-m-d H:i:s" );
						}

						$credit_card_info->vendor_id = $vendor_id;
						$credit_card_info->cardholder_name = $submittedCreditCard ['cardholder_name'];
						$credit_card_info->cc_type = $submittedCreditCard ['cc_type'];
						$credit_card_info->cvv2 = $submittedCreditCard ['cvv2'];
						$credit_card_info->exp_date = $submittedCreditCard ['year'] . "-" . $submittedCreditCard ['month'] . "-01";
						$credit_card_info->last_4_digits = substr ( $submittedCreditCard ['credit_card_number'], - 4 );
						$credit_card_info->date_changed = date ( "Y-m-d H:i:s" );
						$credit_card_info->pnref = $authorizedCreditCard ['PNREF'];
						$credit_card_info->is_valid = "Y";
						$credit_card_info->save ();

						$authorizedCreditCard ['credit_card_info_id'] = $credit_card_info->id;
						$creditCardHelper->logCreditCardHistory ( $authorizedCreditCard );

					} else {
						$this->view->payflow_error = $authorizedCreditCard ['RESPMSG'];
						$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
					}
				}
			}
		}
	}

	/**
	 *
	 * @param $status
	 * @param $source_id - release_id, upc or unique_track id
	 * @param $target_release_id - release_id.
	 * $param $source_type [optional] - track or release, by default it is track
	 */
	public function copymetadata($status, $source_id, $target_release_id, $source_type = "track") {
		try {
			$sessionData = new Zend_Session_Namespace ( 'Alw' );
			$vendor_id = $sessionData->S_VENDOR_ID;
			$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
			$orchadmin_user_id = $sessionData->S_USERID;
			$label_copy_tracks = array ();
			$audio_error_tracks = array ();
			$release = Model_Releases::getReleaseByReleaseID ( $target_release_id );

			if ($status && $source_id && $target_release_id) {
				$sourceTrack = new Model_Track ();
				if ($source_type == "track") {
					//Case of copying only one track
					$source_tracks = $sourceTrack->getTrackByParams ( array ("id" => $source_id ), true );
				} else {
					//Case of copying the whole release
					$source_tracks = $sourceTrack->getTrackByParams ( array ("release_id" => $source_id ), true );
				}
				$track_table_columns = Doctrine_Core::getTable ( 'Model_Track' )->getColumnNames ();
				$common_columns = $track_table_columns;

				$source_destination_track_map = array ();
				foreach ( $source_tracks as $index => $source_track ) {
					if($source_track->track_type == 'music'){
					$source_track_code = "";
					$destination_track_code = "";
					$source_release_id = "";
					$source_upc = "";
					$source_track_code = $source_track->Releases->upc . "_" . $source_track->cd . "_" .
					$source_track->track_id;
					$source_upc = $source_track->Releases->upc;

					$label_copy_track = new Model_Track ();
					$label_copy_track->last_updated = new Doctrine_Expression ( 'NOW()' );
					if ($source_type == "track") {
						$last_volume_number = Doctrine_Query::create ()->select ( 'cd as last_volume_number' )->from ( 'Model_Track' )->where ( 'release_id = ?', $target_release_id )->orderby ( 'cd desc' )->limit ( 1 )->fetchone ();
						$last_track_number = Doctrine_Query::create ()->select ( 'track_id as last_track_number' )->from ( 'Model_Track' )->where ( 'release_id = ?', $target_release_id )->orderby ( 'track_id desc' )->limit ( 1 )->fetchone ();
						$last_volume_number = ($last_volume_number !== false) ? $last_volume_number->last_volume_number : 1;
						$last_track_number = ($last_track_number !== false) ? $last_track_number->last_track_number : 0;
					}

					foreach ( $common_columns as $index => $column_name ) {
						//exception fields
						if ($source_type == "track") {
							$exception_fields = array ('id', 'upc', 'release_id', 'cd', 'track_id', 'last_updated' );
						} else {
							$exception_fields = array ('id', 'upc', 'release_id', 'last_updated' );
						}
						if (! in_array ( $column_name, $exception_fields )) {
							$new_value = $source_track->$column_name;
							if ($new_value) {
								$label_copy_track->$column_name = $new_value;
							}
						}
					}

					$label_copy_track->release_id = $target_release_id;

					if ($source_type == "track") {
						$label_copy_track->cd = $last_volume_number;
						$label_copy_track->track_id = $last_track_number + 1;
					}

					$label_copy_track->upc = $release->upc;
					$label_copy_track->last_updated = new Doctrine_Expression ( 'NOW()' );
					$label_copy_track->save ();
					$label_copy_tracks [] = $label_copy_track;
					$destination_track_code = $release->upc . "_" . $label_copy_track->id;

					$track_artists = $source_track->TrackArtist;
					if (count ( $track_artists->toArray () )) {
						foreach ( $track_artists as $k => $track_artist ) {
							if (! empty( $track_artist->name ) && (strlen(trim($track_artist->name)) > 0))
							{
								$new_track_artist = new Model_TrackArtist ();
								$new_track_artist->track_id = $label_copy_track->id;
								$new_track_artist->type = $track_artist->type;
								$new_track_artist->name = trim($track_artist->name);
								$new_track_artist->save ();
							}
						}
					} else {
						$artist_id = $source_track->Releases->artist_id;
						$artist = new Model_ArtistInfo ();
						$main_artist = $artist->getArtistByParams ( array ("artist_id" => $artist_id ) );
						if ($main_artist !== false) {
							$main_artist_name = $main_artist->name ? $main_artist->name : "";
							$new_track_artist = new Model_TrackArtist ();
							$new_track_artist->track_id = $label_copy_track->id;
							$new_track_artist->type = "performer";
							if (strpos ( $main_artist_name, "various artist" ) === false) {
								$new_track_artist->name = $main_artist_name;
							}
							$new_track_artist->save ();
						}
					}
					$track_writers = $source_track->TrackWriter;
					if (count ( $track_writers->toArray () )) {
						foreach ( $track_writers as $k => $track_writer ) {
							if(!empty($track_writer->writer_name) && (strlen(trim($track_writer->writer_name)) > 0)){
								$new_track_writer = new Model_TrackWriter ();
								$new_track_writer->writer_name = $track_writer->writer_name;
								$new_track_writer->unique_track_id = $label_copy_track->id;
								$new_track_writer->save ();
							}
						}
					}
					$track_publishers = $source_track->TrackPublisher;
					if (count ( $track_publishers->toArray () )) {
						foreach ( $track_publishers as $k => $track_publisher ) {
							if(!empty($track_publisher->publisher_name) && (strlen(trim($track_publisher->publisher_name)) > 0)){
								$new_track_publisher = new Model_TrackPublisher ();
								$new_track_publisher->publisher_name = $track_publisher->publisher_name;
								$new_track_publisher->ownership = $track_publisher->ownership;
								$new_track_publisher->unique_track_id = $label_copy_track->id;
								$new_track_publisher->save ();
							}
						}
					}

					$source_destination_track_map [$source_upc . "_" . $source_track->id] = $destination_track_code;
					/*if(!$path){
						$audio_error_tracks[] = $label_copy_track->id;
						}*/
				}}
				$this->logger ( json_encode ( $source_destination_track_map ), 2 );
				$this->_helper->Assetingestion->addAudioImportAssetBatch ( $source_destination_track_map, $status, "copy", $vend_contact_id, $orchadmin_user_id );
				return array ("new_tracks" => $label_copy_tracks, "audio_error_tracks" => $audio_error_tracks );
			}
		} catch ( Exception $e ) {
			$this->logger ( $e->getMessage (), 1 );
		}
		return 0;
	}

	public function copytrackmetadataAction() {
		$unique_track_id = $this->_request->getParam ( "unique_track_id", "" );
		$status = $this->_request->getParam ( "status", "" );
		$release_id = $this->_request->getParam ( "release_id", "" );
		//check whether the target release has a track with the same isrc
		$tracks = Model_Track::getTrackByParams ( array ("release_id" => $release_id ), true );
		//$source_track = ($status == "in_content" ? ($trackObj->getTrackByParams(array("id"=>$unique_track_id))) : ($trackObj->getTrackByParams(array("id"=>$unique_track_id))));
		$source_track = Model_Track::getTrackByParams ( array ("id" => $unique_track_id ) );
		$isrcs = array ();
		foreach ( $tracks as $k => $each_track ) {
			if ($source_track->isrc && $each_track->isrc) {
				if ($source_track->isrc == $each_track->isrc) {
					$this->view->error = "ISRC: " . $source_track->isrc .
																			 " already exists in this release. ";
					$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
					return;
				}
			}
		}
		$results = $this->copymetadata ( $status, $unique_track_id, $release_id );
		if ($results) {
			$this->view->unique_track_id = $results ["new_tracks"] [0]->id;
			$this->view->audio_error_tracks = $results ["audio_error_tracks"];
		} else {
			$this->view->error = "Track cannot be copied at the moment, please try later. ";
			$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
			return;
		}
	}

	public function addaudioimportassetbatchAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
		$orchadmin_user_id = $sessionData->S_USERID;
		$track_codes = $this->_request->getParam ( "track_codes", array () );
		if ($this->_request->getParam ( "correction_flag" ) && $this->_request->getParam ( "release_status", "" ) == "in_content") {
			$job_type = "replace";
		} else {
			$job_type = "upload";
		}
		$this->view->track_import_asset_id_map = $this->_helper->Assetingestion->addAudioImportAssetBatch ( $track_codes, $this->_request->getParam ( "release_status", "" ), $job_type, $vend_contact_id, $orchadmin_user_id );
	}

	public function copyreleasemetadataAction() {
		$version = $this->_request->getParam ( "version", "" );
		$status = $this->_request->getParam ( "status", "" );
		$release_unique_id = $this->_request->getParam ( "release_unique_id", 0 );
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
		$orchadmin_user_id = $sessionData->S_USERID;
		try {
			if ($status && $release_unique_id) {
				$release = Model_Releases::getReleaseByParams ( array ("release_id" => $release_unique_id ) );
				$release_table_columns = Doctrine_Core::getTable ( 'Model_Releases' )->getColumnNames ();
				$new_release = new Model_Releases ();
				$common_columns = $release_table_columns;
				foreach ( $common_columns as $index => $column_name ) {
					if (! in_array ( $column_name, array ('release_id', 'version', 'upc', 'language_code', 'language_id', 'ingestion_completed', 'last_updated' ) )) {
						if ($release->$column_name) {
							if ($column_name == "physical_release_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "theatrical_release_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "original_digital_release_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "release_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "sale_start_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "original_release_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} elseif ($column_name == "preorder_date" && $release->$column_name == "0000-00-00") {
								$new_release->$column_name = NULL;
							} else {
								$new_release->$column_name = $release->$column_name;
							}
						}
					}
				}
				$new_release->last_updated = new Doctrine_Expression ( 'NOW()' );
				$new_release->version = $version;
				$new_release->deletions = 'N';
				$new_language_code = $release->language_id;
				if ($new_language_code)
					$new_release->ingestion_completed = new Doctrine_Expression ( 'NOW()' );
				$new_release->upc = Service_Upc::getNewUPC();
				$new_release->release_status = "label_processing";
				$new_release->save ();

				//set releases status to label processing
				$new_release_status = new Model_ReleaseStatus ();
				$new_release_status->release_id = $new_release->release_id;
				$new_release_status->upc = $new_release->upc;
				$new_release_status->status = "label_processing";
				$new_release_status->date = date ( "Y-m-d" );
				$new_release_status->changed_by = $vendor_id;
				$new_release_status->changed_by_type = "vendor";
				$new_release_status->save ();

				//copy release artists
				$release_artists = $release->ReleaseArtist;
				$roleArr = array();
				foreach ( $release_artists as $index => $release_artist ) {
					if(! empty ( $release_artist->artist_name )&&(strlen(trim($release_artist->artist_name)) > 0)){
						$new_release_artist = new Model_ReleaseArtist ();
						$new_release_artist->release_id = $new_release->release_id;
						$new_release_artist->upc = $new_release->upc;
						$new_release_artist->artist_name = $release_artist->artist_name;
						if ($release_artist->url)
							$new_release_artist->url = $release_artist->url;
						if ($release_artist->role)
							$new_release_artist->role = $release_artist->role;
						$new_release_artist->save ();
					}
					if($release_artist->role == 'performer' && (!empty( $release_artist->artist_name )&&(strlen(trim($release_artist->artist_name)) > 0)))
							$roleArr[] = $release_artist->role;
				}

				//If 'performer' is not available in release from which we have copied then it will take splited 'orchard_artist'.
				$primary_artist = array();
				if (count($roleArr) == 0){
					$artistObj = Model_ArtistInfo::getArtistByParams(array("artist_id" => $release->artist_id));
					$artistName = $artistObj->name;
					$primary_artist = $this->_helper->Performer->splitArtist($artistName);
					foreach($primary_artist as $index => $performer){
						$performer_release_artist = new Model_ReleaseArtist ();
						$performer_release_artist->release_id = $new_release->release_id;
						$performer_release_artist->upc = $new_release->upc;
						$performer_release_artist->artist_name = $performer;
						$performer_release_artist->role = 'performer';
						$performer_release_artist->save ();
					}
				}

				//copy release subgenre
				$release_subgenres = $release->ReleaseSubgenre;
				foreach ( $release_subgenres as $index => $release_subgenre ) {
					$new_release_subgenre = new Model_ReleaseSubgenre ();
					$new_release_subgenre->release_id = $new_release->release_id;
					if ($release_subgenre->subgenre_id)
						$new_release_subgenre->subgenre_id = $release_subgenre->subgenre_id;
					$new_release_subgenre->upc = $new_release->upc;
					$new_release_subgenre->save ();
				}

				//copy track
				$this->copymetadata ( $status, $release_unique_id, $new_release->release_id, "release" );

				//copy image
				$source_upc_for_copy_image = $release->upc;
				$foldername_for_copy_image = $release->vendor_catalog_number;
				$this->_helper->Assetingestion->addImageImportAssetBatchForCopyImage ( $source_upc_for_copy_image, $new_release->upc, $foldername_for_copy_image, $vend_contact_id, $orchadmin_user_id );
				//copy cd_received
				$cd_receive = $release->CdReceive;
				$new_cd_receive = new Model_CdReceive ();
				$new_cd_receive->vendor_id = $vendor_id;
				$new_cd_receive->orchadmin_user_id = $orchadmin_user_id;
				$new_cd_receive->upc = $new_release->upc;
				$new_cd_receive->date_received = new Doctrine_Expression ( "NOW()" );

				if ($status == "in_content") {
					$new_cd_receive->image_scan = "Y";
					$new_cd_receive->encoding = "Y";
				} else {
					$new_cd_receive->image_scan = $cd_receive->image_scan;
					$new_cd_receive->encoding = $cd_receive->encoding;

				}
				$new_cd_receive->total_tracks_encoded = count ( $release->Track );
				$new_cd_receive->total_tracks = count ( $release->Track );
				$new_cd_receive->encoding_date = new Doctrine_Expression ( "NOW()" );
				$new_cd_receive->image_scan_date = new Doctrine_Expression ( "NOW()" );
				$new_cd_receive->save ();
				$returnArr ['release_id'] = $new_release->release_id;
				echo json_encode ( $returnArr );
				exit ();
			}
		} catch ( Exception $e ) {
			$this->view->error = $e->getMessage ();
			$this->getResponse ()->setHttpResponseCode ( 500 )->sendResponse ();
		}
	}

	public function enterversionmodalAction() {
		$this->_helper->layout ()->setLayout ( "modalwindowwrapper" );
		$this->view->placeholder ( "title" )->set ( $this->view->translate->_ ( "Copy Release" ) );
		$status = $this->_request->getParam ( "status", "" );
		$release_unique_id = $this->_request->getParam ( "release_unique_id", 0 );
		$version = $this->_request->getParam ( "version", "" );
		$this->view->placeholder ( "buttons" )->set ( '<a id="copyrelease_button" type="button" class="rounded modalSubmit" onclick=\'copyrelease({status:"' . $status . '",release_unique_id:"' . $release_unique_id . '"})\'><span>Copy Release</span></a>' );
		$this->view->album_artist = $this->_request->getParam ( "album_artist", "" );
		$this->view->release_unique_id = $this->_request->getParam ( "release_unique_id", "" );
		$this->view->release_name = $this->_request->getParam ( "release_name", "" );
		$this->view->vendor_catalog_number = $this->_request->getParam ( "vendor_catalog_number", "" );
	}

	public function copyimageAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );
		$source_file = $this->_request->getParam ( 'jpg_file' );
		$is_thumbnail = $this->_request->getParam ( 'thumbnail' );
		$job_type = $this->_request->getParam ( 'job_type' );
		switch ($job_type) {
			case "replace" :
				$thumbnail_path = PUBLIC_PATH . "/images/coverart_correction/";
				$image_path = PUBLIC_PATH . "/images/lg_coverart_correction/";
				break;
			default :
				$thumbnail_path = PUBLIC_PATH . "/images/coverart/";
				$image_path = PUBLIC_PATH . "/images/lg_coverart/";
				break;
		}
		if ($is_thumbnail) {
			if (file_exists ( $thumbnail_path . $source_file )) {
				unlink ( $thumbnail_path . $source_file );
			}
			if (copy ( Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file, $thumbnail_path . $source_file )) {
				unlink ( Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file );
			}else{
				$this->logger ( "Copy of source coverart from ". Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file ." to destination ". $thumbnail_path . $source_file ." fails" , 1 );
			}
		} else {
			if (file_exists ( $image_path . $source_file )) {
				unlink ( $image_path . $source_file );
			}
			if (copy ( Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file, $image_path . $source_file )) {
				unlink ( Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file );
			}else{
				$this->logger ( "Copy of source lg_coverart from ". Zend_Registry::get ( 'configuration' )->releaseBuilder->upload_mount_path . $source_file ." to destination ". $image_path . $source_file ." fails" , 1 );
			}
		}
	}

	public function updateuploadrequestAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNeverRender ( true );

		$import_asset_id = $this->_request->getParam('import_asset_id', 0);

		$status = $this->_request->getParam('status');
		$job_type = $this->_request->getParam('job_type');
		$result = $this->_request->getParam('result');
		$encoding_completed = $this->_request->getParam('encoding_completed');
		try{
			$update_upload_request = new Service_UpdateUploadRequest ($import_asset_id, $status, $job_type, $result, $encoding_completed);
			$update_upload_request->updateOnUpload ();
		} catch(Exception $e) {
			$this->view->file_error = $e->getMessage();
			$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		}
	}

	public function mapBadFileError($audio_upload_error) {
		$err_msg = '';
		if ($result_arr = @unserialize ( $audio_upload_error )) {
			foreach ( $result_arr as $type => $msg ) {
				switch ($type) {
					case "badSampleRate" :
						$err_msg .= $this->view->translate->_ ( "audioFrequencyError" );
						$err_msg .= " $msg Hz.<br/>";
						break;
					case "badBitrate" :
						$err_msg .= $this->view->translate->_ ( "audioBitrateError" );
						$err_msg .= " $msg bps.<br/>";
						break;
					case "badChannels" :
						$err_msg .= $this->view->translate->_ ( "audioChannelsError" );
						$err_msg .= " $msg .<br/>";
						break;
					default :
						$err_msg .= $this->view->translate->_ ( "audioEncodingError" );
						break;
				}
			}
		} elseif (! empty ( $audio_upload_error )) {
			if (strpos ( $audio_upload_error, 'Error converting' ) !== false) {
				$err_msg = $this->view->translate->_ ( "Invalid M4A or WAV file" );
			} else
				$err_msg = $audio_upload_error;
		}
		return $err_msg;
	}

	/**
	 * display dialog for thanking the user
	 */
	function thankyouAction() {
		$this->view->release_id = $this->_request->getParam ( 'release_id' );
	}

	function getvideostatushistoryAction(){
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$dashbordItemId= $this->_request->getParam('dashboard_item_id');
		$this->view->videoDashboardItem = Model_VideoDashboardItem::getDisplayItem($dashbordItemId);
		$this->view->videoDashboardStatus = Model_VideoDashboardItemStatus::getDashboardItemStatusDetailsById($dashbordItemId);

	}
	private function validateCDAndTracks($cd_arr, $track_arr){
		$response = array();
		$track_count = count($track_arr);
		if(($track_count != max($track_arr)) || (count(array_unique($track_arr) != max($track_arr)))){
			$response['Track'] = 1;
		}

		$cd_arr = array_unique ( $cd_arr );
		$volumes_count = count ( $cd_arr );
		$this->view->volumes_count = $volumes_count;
		if ($volumes_count != max ( $cd_arr )) {
			$response ['Cd'] = 1;
		} else {
			$response ['Cd'] = 0;
		}
		return $response;
	}

	private function reorderTracks($tracks, $volumes_tracks_arr, $cd_validation = 0, $track_validation = 0) {

		$volume_arr = array ();
		$tracks_arr = array ();

		foreach ( $volumes_tracks_arr as $key => $volume ) {
			$volume_arr [] = $key;
			$n = 1;
			foreach ( $volume as $track_key => $track ) {
				$tracks_arr [$key] [$n ++] = $track_key;
			}
		}

		$volume_arr = array_unique ( $volume_arr );
		for($i = 0; $i < count ( $volume_arr ); $i ++) {
			$volume_new_arr [$volume_arr [$i]] = $i + 1;
			for($j = 1; $j <= count ( $tracks_arr [$volume_arr [$i]] ); $j ++) {
				$track_new_arr [$volume_new_arr [$volume_arr [$i]]] [$tracks_arr [$volume_arr [$i]] [$j]] = $j;
			}
		}

		foreach ( $tracks as $track ) {
			$track_update = Model_track::getTrackByID ( $track->id );
			$track_update->last_updated = new Doctrine_Expression ( 'NOW()' );
			$track_update->cd = $volume_new_arr [$track->cd];
			if ($track_validation) {
				$track_update->track_id = $track_new_arr [$track_update->cd] [$track->id];
			}
			$track_update->save ();
		}

	}

	/**
	 * Show modal window to get the confirmation (Yes / No) before "cancel submittion" action
	 * @param release_id and release_name for current release that the user is canceling
	 */
	public function cancelconfirmationmodalAction() {
		$this->_helper->layout ()->setLayout ( "modalwindowwrapper" );
		$this->view->placeholder ( "title" )->set ( $this->view->translate->_ ( "Cancel Submission" ) );
		$release_id = $this->_request->getParam ( "release_id", "" );
		$release_name = $this->_request->getParam ( "release_name", "" );

		$this->view->placeholder ( "buttons" )->set ( '<a id="cancel_button" type="button" class="rounded modalSubmit"
				onclick="modal_overlaycancelSubmittedReleaseModal.close()"><span>No</span></a>
				<a id="submit_button" type="button" class="rounded modalSubmit" href="#"
			onclick="cancelSubmission()"><span>Yes</span></a>' );

		$this->view->release_id = $release_id;
		$this->view->release_name = $release_name;

	}

	/**
	 * Cancel submittion so user can do more edits - by changing status to lable_processing / (in_content)active
	 * Delete from release_approval_queue as it is no longer in submitted state.
	 * @param release_id for current release that the user is canceling
	 */
	public function cancelsubmittedAction() {
		$release_id = $this->_request->getParam ( 'release_id' );
		$sessionData = new Zend_Session_Namespace ( 'Alw' );
		$vendor_id = $sessionData->S_VENDOR_ID;
		$vend_contact_id = $sessionData->S_VEND_CONTACT_ID;
		$this->_helper->layout ()->disableLayout ();
		if (! isset ( $release_id ) || $release_id <= 0) {
			echo json_encode ( array ("errorMessage" => "Invalid Release ID." ) );
			exit ();
		}
		try {
			$releaseObj = Model_Releases::getReleaseByReleaseID ( $release_id );
			/** if invalid release for current vendor then exit, don't continue*/
			if (! $releaseObj->isValidReleaseId ( $release_id, $vendor_id )) {
				echo json_encode ( array ("errorMessage" => "Invalid Release ID for this Vendor." ) );
				exit ();
			}
			$returnArray = array ("success" => 1 );
			// REMOVE FROM APPROVAL QUEUE FOR OA APPROVAL, AS IT NO LONGER IS SUBMITTED. ALSO CHECK IF IT IS NOT CHECKED OUT BY OA
			$approvalQueueObj = Model_ReleaseApprovalQueue::getReleasesApprovalQueueByParams ( array ('release_id' => $release_id ) );
			if ($approvalQueueObj && $approvalQueueObj->status == "checked_in") {
				$approvalQueueObj->delete ();
			} else {
				echo json_encode ( array ("errorMessage" => "In Progress with OA Approval. Cannot cancel now." ) );
				exit ();
			}

			if ($releaseObj->release_status == "in_content") {
				$releaseCorrectionObj = Model_ReleaseCorrection::getReleaseCorrectionByReleaseId ( $release_id );
				if (empty ( $releaseCorrectionObj ) || (isset ( $releaseCorrectionObj->status ) && $releaseCorrectionObj->status != 'submitted')) {
					echo json_encode ( array ("errorMessage" => "Invalid Release ID. This is not yet submitted." ) );
					exit ();
				} else {
					$releaseCorrectionObj->status = "active";
					$releaseCorrectionObj->last_updated = date ( 'Y-m-d H:i:s' );
					$releaseCorrectionObj->last_updated_by = $vendor_id;
					$releaseCorrectionObj->save ();
					$returnArray = array_merge ( $returnArray, array ("correction" => 1 ) );
				}
			} else {
				echo json_encode ( array ("errorMessage" => "Invalid Release status. This is not yet submitted." ) );
				exit ();
			}
			echo json_encode ( $returnArray );
			exit ();

		} catch ( Exception $e ) {
			echo json_encode ( array ("errorMessage" => $e->getMessage () ) );
			exit ();
		}
	}

	protected function setReleaseData($releaseObj, $formData, $new_artist_id) {
		$releaseObj->release_name = isset ( $formData ['release_name'] ) ? $formData ['release_name'] : 'NULL';
		$releaseObj->vendor_catalog_number = isset ( $formData ['label_catalog'] ) ? $formData ['label_catalog'] : 'NULL';
		$releaseObj->label = (isset ( $formData ['imprint_name'] ) && ! empty ( $formData ['imprint_name'] )) ? $formData ['imprint_name'] : ((isset ( $vendor_data->company ) && ! empty ( $vendor_data->company )) ? $vendor_data->company : '');
		$releaseObj->release_date = ($formData ['release_date'] && $formData ['release_date'] != 'NULL') ? $formData ['release_date'] : 'NULL';
		$releaseObj->sale_start_date = ($formData ['sales_date'] && $formData ['sales_date'] != 'NULL') ? $formData ['sales_date'] : 'NULL';
		$releaseObj->new_release = 'New';
		$releaseObj->manufacturer_upc = isset ( $formData ['manufacturer_upc'] ) ? $formData ['manufacturer_upc'] : 'NULL';
		$releaseObj->c_line = isset ( $formData ['c_line'] ) ? $formData ['c_line'] : 'NULL';
		$releaseObj->genre_id = isset ( $formData ['genre'] ) ? $formData ['genre'] : 'NULL';
		$releaseObj->distribution = 'digital';

		$releaseObj->artist_id = ($new_artist_id ? $new_artist_id : $formData ['artist_id']);
		$releaseObj->version = isset ( $formData ['version'] ) ? $formData ['version'] : 'NULL';
		if($formData['product_type'] == 'music_video'){
			$releaseObj->language_id = $formData['language'];
			$releaseObj->keywords = $formData['keywords'];
			if($releaseObj->format == '' || $releaseObj->format == null){
				$releaseObj->format = "CD (Full Length)";
			}
		}else{
			$releaseObj->format = isset ( $formData ['format'] ) ? $formData ['format'] : 'NULL';
		}
		$releaseObj->last_updated = new Doctrine_Expression ( "NOW()" );
		return $releaseObj;
	}

	/*
	 * save release artist whose role is primary, featuring and remixer.
	 */
	public function processReleaseArtist($releaseArtists, $role, $release, $updated_by, $updated_type) {
		if ($releaseArtists){
			$tmp_release_artist = explode ( "|", $releaseArtists );
		}
		if ($release->release_status == 'in_content') {
			$artist_data_arr = array();
			if (! empty ( $tmp_release_artist )) {
				foreach ( $tmp_release_artist as $artist ) {
						$artist_data_arr [$release->release_id] [] = array ('artist_name' => $artist, 'role' => $role );
				}
			}
			$releaseCorrectionObj = $release->ReleaseCorrection [count ( $release->ReleaseCorrection ) - 1];
			Model_ReleaseCorrectionDetail::deleteCorrectionDataByParams ( array ('release_correction_id' => $releaseCorrectionObj->release_correction_id, 'field_name' => $role ) );
			Model_ReleaseCorrectionDetail::saveJsonCorrectionData ( $release->release_id, 'releases', $role, $release->release_id, json_encode ( $artist_data_arr ), $updated_by, $updated_type );
		} else {
			Model_ReleaseArtist::deleteReleaseArtistsByRole ( $release->release_id, $role );
			if (! empty ( $tmp_release_artist )) {
				foreach ( $tmp_release_artist as $artist ) {
					if (! empty( $artist )&&( strlen(trim($artist)) > 0 )) {
						$releaseArtistObj = new Model_ReleaseArtist ();
						$releaseArtistObj->artist_name = $artist;
						$releaseArtistObj->role = $role;
						$releaseArtistObj->upc = $release->upc;
						$releaseArtistObj->release_id = $release->release_id;
						$releaseArtistObj->save ();
					}
				}
			}
		}
	}

	public function processTrackArtist($track_artist_values, $type, $releaseObj, $track_id, $updated_by, $updated_type)
	{
		$artist_array = $this->createTrackArtistAndReleaseArray($type,$track_artist_values,$releaseObj->release_id);
		$tmp_track_artist = explode("|", $track_artist_values);
		$tmp_track_artist = array_diff ($tmp_track_artist, array ('' ));
		if($artist_array['track_artist'] != $artist_array['release_artist']){
			if($releaseObj->release_status == 'in_content'){
				$track_artist_correction_arr = array();
				$releaseCorrectionObj = $releaseObj->ReleaseCorrection[count($releaseObj->ReleaseCorrection)-1];
				Model_ReleaseCorrectionDetail::deleteCorrectionDataByParams(array('release_correction_id'=>$releaseCorrectionObj->release_correction_id ,'field_name'=>$type,'table_name'=>'track','key_id'=>$track_id));
				foreach($tmp_track_artist as $each_track_artist){
					$track_artist_correction_arr[$track_id][] = array ('name' => $each_track_artist, 'type' => $type );
				}
				Model_ReleaseCorrectionDetail::saveJsonCorrectionData($releaseObj->release_id, 'track', $type, $track_id, json_encode($track_artist_correction_arr), $updated_by, $updated_type);
			}else{
				Model_TrackArtist::deleteTrackArtistsByRole($track_id, $type);
				foreach($tmp_track_artist as $each_track_artist){
					if (! empty( $each_track_artist ) && (strlen(trim ($each_track_artist)) > 0)){
						$track_artist = new Model_TrackArtist();
						$track_artist->name = $each_track_artist;
						$track_artist->type = $type;
						$track_artist->track_id = $track_id;
						$track_artist->save();
					}
				}
			}
		}
	}

	public function changeartworkrejectionAction() {
		$release_id = $this->_request->getPost ( 'rejection_id' ) ? $this->_request->getPost ( 'rejection_id' ) : 0;
		if($release_id > 0){
			$objRejectionNotes = Model_RejectionNotes::getRejectionNotesByID($release_id);
			if($objRejectionNotes){
				$objRejectionNotes->corrected = "Y";
				$objRejectionNotes->save();
			}
		}
		exit;
	}

	protected function createTrackArtistAndReleaseArray($track_artist_roles, $track_artist, $release_id){
		/** get release artist */
		$releaseArtistInfo = Model_ReleaseArtist::getReleaseArtistByParams(array('release_id'=>$release_id,'role'=>$track_artist_roles), true);
		$existing_roles_release_names = array();
		if ($releaseArtistInfo){
			foreach ($releaseArtistInfo as $artist_data){
				$existing_roles_release_names[] = trim($artist_data->artist_name);
			}
		}
		$tmp_track_artists_by_role = explode ("|", trim($track_artist));

		/** delete empty nodes */
		$tmp_track_artists_by_role = array_diff ($tmp_track_artists_by_role, array (''));
		$existing_roles_release_names = array_diff ($existing_roles_release_names, array (''));

		$tmp_track_artists_list = implode ("|", $tmp_track_artists_by_role);
		$existing_roles_release_names_list = implode ("|", $existing_roles_release_names);
		return array("track_artist" => $tmp_track_artists_list, "release_artist" => $existing_roles_release_names_list);
	}
	public function addunlinkassetAction(){
		$dashboard_item_id = $this->_request->getParam('videoList');
		$release_id = $this->_request->getParam("release_id",0);
		$videoAsset = new Web_Controller_Action_Helper_VideoAsset();
		$result = $videoAsset->addUnlinkedAsset($dashboard_item_id, $this->getRelease());

		$this->_redirect("/alw/releasebuilder/view?release_id=".$release_id);
		exit();
	}
	public function alertlinkedassetAction(){
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$allParams = $this->_getAllParams();
		unset($allParams['module']);
		unset($allParams['controller']);
		unset($allParams['action']);
		$this->view->modal_window_id = $allParams['modalWindowID'];
		$this->view->querystring = str_replace(array("=","?","&"),"/",http_build_query($allParams));

	}
	public function changelinkedassetAction(){
		$dashboard_item_id = $this->_request->getParam("dashboard_item_id",0);
		$release_id = $this->_request->getParam("release_id",0);
		$videoAsset = new Web_Controller_Action_Helper_VideoAsset();
		$result = $videoAsset->removeLinkedAsset($dashboard_item_id, $this->getRelease());

		$this->_redirect("/alw/releasebuilder/view?release_id=".$release_id);
		exit();
	}

	public function checkisrcAction(){
		$isrc = $this->_request->getParam("isrc");
		if(Web_Controller_Plugin_ReleaseUtility::checkISRC($isrc)){
			$checkisrcexistence = new Web_Validate_Checkisrcexist();

			if(!$checkisrcexistence->isValid(array('type'=>'music','isrc'=>$isrc))){
				return true;
			}
		}
		$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		return ;
	}

	public function removetrackcreditAction(){
		$id = $this->_request->getParam('trackCreditId');
		if(!empty($id)){
			$delete = Model_TrackCredit::deleteTrackCreditDetailsById($id);
		} else {
			$this->view->errors = "Credit Id Not found.";
			$this->getResponse()->setHttpResponseCode(500)->sendResponse();
		}
		return;
	}

	public function creditremoveconfirmAction() {
		$this->_helper->layout()->setLayout("modalwindowwrapper");
		$allParams = $this->_getAllParams();
		unset($allParams['module']);
		unset($allParams['controller']);
		unset($allParams['action']);
		$this->view->modal_window_id = $allParams['modalWindowID'];
		$querystring = str_replace(array("=","?","&"),"/",http_build_query($allParams));
		$this->view->trackCreditId = $this->_request->getParam('trackCreditId');
	}

	/**
	 * Assign the in-point for a video release
	 */
	public function assigninpointAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$videoTrackId = $this->_request->getParam('video_track_id');
		$inPoint = $this->_request->getParam('in_point');
		$videoDuration = $this->_request->getParam('video_duration');
		$asset_id = $this->_request->getParam('asset_id');
		$upc = $this->_request->getParam('upc');

		/** gather `track_video_marker` information */
		$trackVideoMarker = Model_TrackVideoMarker::getPreviewByTrackId($videoTrackId);

		/** create if it doesn't exist */
		if (empty($trackVideoMarker)) {
			$trackVideoMarker = new Model_TrackVideoMarker();
			$trackVideoMarker->track_id = $videoTrackId;
			$trackVideoMarker->type = 'preview';
		}

		/** parse and assign start_timecode */
		$timeInfo = Alw_View_Helper_Releasebuilder_Videothumbnail::secondsToTime($inPoint);
		$hours = sprintf("%02d", $timeInfo['h']);
		$min = sprintf("%02d", $timeInfo['m']);
		$sec = sprintf("%02d", $timeInfo['s']);
		$trackVideoMarker->time = "{$hours}:{$min}:{$sec}:00";
		$trackVideoMarker->save();
	}

	/**
	 * generate an example thumbnail for the user
	 */
	public function generateexamplethumbAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$asset_id = $this->_request->getParam('asset_id');
		$timestamp = $this->_request->getParam('timestamp');
		$videoPreview = new Alw_Service_Videopreview($asset_id);
		try {
			$videoPreview->getQAExampleThumbnail($asset_id, $timestamp);
		} catch (Exception $ex){}

		/** I/O wait on the tmp image produced in the tmp folder of the ALW site */
		$wait = 1;
		if (file_exists(PUBLIC_PATH."/tmp/HQThumb_{$asset_id}.jpg")) {
			unlink(PUBLIC_PATH."/tmp/HQThumb_{$asset_id}.jpg");
		}
		while ($wait < 10) {
			if (file_exists(PUBLIC_PATH."/tmp/HQThumb_{$asset_id}.jpg")) {
				return true;
			}
			$wait++;
			sleep(2);
		}
	}

	/**
	 * persist to storage the selected video thumbnail image for coverart
	 */
	public function persistvideocoverartAction() {

		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNeverRender(true);
		$asset_id = $this->_request->getParam('asset_id');
		$currentSecond = $this->_request->getParam('currentSecond');
		$upc = $this->_request->getParam('upc');
		$imageInfo = $this->_request->getParam('imageInfo');

		/** submit chosen thumbnail to TIF storage */
		$videoPreview = new Alw_Service_Videopreview($asset_id);
		try {
			$videoPreview->submitFinalDeliveryThumbnails($asset_id, $upc);
		} catch (Exception $ex){}

		/** derive the video master HQ thumbnail source */
		$sourceImage = PUBLIC_PATH."/tmp/HQThumb_".$asset_id.".jpg";
		 list($width, $height) = getimagesize($sourceImage);
		 $ratio = round($height/$width,2);

		/** coverart = 110x110 create */
		$destinationFile = "c_".$upc.".jpg";
		$destinationFolder = PUBLIC_PATH."/images/coverart/";
		$this->cropvideothumbnails(110, 110, $sourceImage, $destinationFile, $destinationFolder);

		/** coverart_12 = 110x110 create */
		$destinationFile = "c_".$upc.".jpg";
		$destinationFolder = PUBLIC_PATH."/images/coverart_12/";
		$this->cropvideothumbnails(110, (110*$ratio), $sourceImage, $destinationFile, $destinationFolder);

		/** lg_coverart = 360x360*/
		$destinationFile = $upc.".jpg";
		$destinationFolder = PUBLIC_PATH."/images/lg_coverart/";
		$this->cropvideothumbnails(360, 360, $sourceImage, $destinationFile, $destinationFolder);

		/** lg_coverart_12 = 360x360*/
		$destinationFile = $upc.".jpg";
		$destinationFolder = PUBLIC_PATH."/images/lg_coverart_12/";
		$this->cropvideothumbnails(360, (360*$ratio), $sourceImage, $destinationFile, $destinationFolder);

		/** update cd_receive table with the image scan date & image scan flag */
		$cdReceiveEntry = Model_CdReceive::getCdReceiveByUpc($upc);
		$cdReceiveEntry->image_scan = "Y";
		$cdReceiveEntry->image_scan_date = date("Y-m-d");
		$cdReceiveEntry->total_tracks = "1";
		$cdReceiveEntry->total_tracks_encoded = "1";
		$cdReceiveEntry->video_tracks_mastered = "1";
		$cdReceiveEntry->video_mastered = "Y";
		$cdReceiveEntry->save();

		unlink($sourceImage);
	}

	/**
	 * crop video thumbnail to specified width, height and destination
	 *
	 * @param integer $crop_width, width to crop to
	 * @param integer $crop_height, height to crop to
	 * @param integer $sourceImage, source of image file to crop from
	 * @param integer $destinationFile, filename of cropped file
	 * @param integer $destinationFolder, foldername of cropped file
	 */
	private function cropvideothumbnails($crop_width, $crop_height, $sourceImage, $destinationFile, $destinationFolder) {
		$canvas = imagecreatetruecolor($crop_width, $crop_height);
		$current_image = imagecreatefromjpeg($sourceImage);
		list($width, $height) = getimagesize($sourceImage);
		imagecopyresampled($canvas, $current_image , 0, 0, 0, 0, $crop_width, $crop_height, $width, $height);
		imagejpeg($canvas, $destinationFolder.$destinationFile, 100);
	}

	public function videouploadAction(){
		$vendor_id = $this->getVendorId();
		$this->checkReleaseVendor();
		// call to the service that retrives the token.
		$release = $this->getRelease();
		$release_id = $release->release_id;
		$connect_server = Zend_Registry::get ( "configuration" )->aspera->tokengenURL;
		$this->view->host = Zend_Registry::get ( "configuration" )->aspera->host;
		$this->view->release_name = $release->release_name;
		$this->view->release_id = $release_id;
		$token_url = sprintf($connect_server."?vendorId=%s&releaseId=%s", urlencode($vendor_id), urlencode($release_id));

        $cp = curl_init($token_url);
		$cs = fopen('php://memory', 'rw');
		curl_setopt($cp, CURLOPT_FILE, $cs);
		curl_exec($cp);

		$this->view->error = true;

		if (curl_getinfo( $cp, CURLINFO_HTTP_CODE ) == 200) {
	        rewind ($cs);
			$jsonVals = json_decode ( fread( $cs, 8192 ) );
			$this->view->token = $jsonVals->{"token"};
			$this->view->dir = $jsonVals->{"dir"};
			$this->view->user = $jsonVals->{"user"};
			$this->view->error = false;
        }

		fclose( $cs );
		curl_close( $cp );
	}
}