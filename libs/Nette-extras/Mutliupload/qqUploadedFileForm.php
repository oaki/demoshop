<?php


/**
 * @access public
 */
class qqUploadedFileForm {
	
	/**
	 * 
	 * Save the file to the specified path
	 * 
	 * @access public
	 * @param $path
	 * @return boolean
	 * @ParamType $path 
	 * @ReturnType boolean
	 */
	public function save($path) {
		if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
	}

	/**
	 * @access public
	 */
	public function getName() {
		 return $_FILES['qqfile']['name'];
	}

	/**
	 * @access public
	 */
	public function getSize() {
		return $_FILES['qqfile']['size'];
	}
}
