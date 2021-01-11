<?php
/*
Class to handle image upload/delete on image server
*/
class cls_image_handler{
	 
        /* Function to upload image on image server */
        public function uploadImage($destination_path, $file_name , $create_path){
                $create_path = self::checkPath($create_path);
                /** Validate storage space on image web server */
                if(disk_free_space($create_path) < 104857600){
                    throw new Exception("Not enough space in " . $create_path);
				}

	                /* Create folder on image server if it */
	                if(!empty($destination_path)){
	                        if(!is_dir(str_replace("//", "/", $create_path . "/" . $destination_path))){
	                                if(!mkdir(str_replace("//", "/", $create_path . "/" . $destination_path), 0775, true)){
	                                        throw new Exception("Failed to create folder : " . $destination_path . " under " . $create_path);
	                                }
	                        }
	                        $create_path = $create_path . $destination_path;
	                } else {
	                        throw new Exception("File Upload failed, destination path not found");
	                }
	                if(!empty($file_name)){
	                        $construct_path = str_replace("//", "/", $create_path . "/" . $file_name);
	                        /* Check File is already exist with same name or not, if yes delete it to copy new image */
	                        if(file_exists($construct_path)){
	                                if(!unlink($construct_path)){
	                                        throw new Exception("Existing file delete failed");
	                                }
	                        }
	                }else{
	                        throw new Exception("File Upload failed. File name not found");
	                }
	                /* Upload image file */
	                if(!move_uploaded_file($_FILES['UploadedImage']['tmp_name'] , $construct_path)){
	                        throw new Exception("File Upload failed");
	                } else {
	                        ob_clean();
	                        $response['code'] = 1;
	                        $response['msg'] = "success";
	                        /* return json encoded response */
	                        return json_encode($response);
	                }
	        }
	 
	        /* Function to delete image from image server */
	        public function deleteImage($path, $initial_path){
	                /** Check for file exists or not */
	                        if(file_exists($initial_path . $path)){
	                                /** Delete the file */
	                                if(unlink(str_replace("//", "/", $initial_path . "/" . $path))){
	                                        ob_clean();
	                                        $response['code'] = 1;
	                                        $response['msg'] = "success";
	                                        return json_encode($response);
									}else{
                                        throw new Exception("Image deletion failed");
	                                }
	                        }else{
	                                throw new Exception("Image does not exist");
	                        }
	        }
	 
	        /*Check pre and post appended "\" in path */
	        protected function checkPath($path){
	                $path = trim($path);
	                $path = (substr($path, 0, 1) == "/") ? $path : "/" . $path;
	                $path = (substr($path, -1) == "/") ? $path : $path . "/";
	                return $path;
			}
	}
?>