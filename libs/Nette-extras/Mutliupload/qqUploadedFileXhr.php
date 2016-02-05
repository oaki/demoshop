<?php

/**
 * @access public
 */
class qqUploadedFileXhr {
	
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
		$input = fopen("php://input", "r");
        $temp = tmpfile();

        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()  ){
            return false;
        }

        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
	}

	/**
	 * @access public
	 */
	public function getName() {
		return $_GET['qqfile'];
	}

	/**
	 * @access public
	 */
	public function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
	}
}
