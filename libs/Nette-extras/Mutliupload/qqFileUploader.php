<?php


/**
 * @access public
 */
class qqFileUploader {
	private $allowedExtensions = array();
	private $sizeLimit = 10485760;
	protected $file;
	protected $uploadJustOneFile = false;

	/**
	 * @access public
	 * @param array $allowedExtensions
	 * @param $sizeLimit
	 * @ParamType $allowedExtensions array
	 * @ParamType $sizeLimit 
	 */
 	function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;   


        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }

	/**
	 * 
	 * Returns array('success'=>true) or array('error'=>'error message')
	 * @access public
	 * @param $uploadDirectory
	 * @param $replaceOldFile
	 * @ParamType $uploadDirectory 
	 * @ParamType $replaceOldFile 
	 */
	public function handleUpload($uploadDirectory, $replaceOldFile = FALSE) {
	 	if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = strtolower($pathinfo['extension']);

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            $filename = self::doNameFile($uploadDirectory, $filename, $ext);           
        }
        
        if ($this->file->save($uploadDirectory .'/'. $filename . '.' . $ext)){
            return array('success'=>true,'filename'=>$filename,'ext'=>$ext);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
	}

	/**
	 * @access public
	 * @param $dir
	 * @param $name
	 * @param $ext
	 * @ParamType $dir 
	 * @ParamType $name 
	 * @ParamType $ext 
	 */
	public function doNameFile($dir, $name, $ext) {
		$number="";
 
	    while(file_exists($dir.'/'.NStrings::webalize($name).$number.".".$ext)){
	     ++$number;
	    }
	//    echo NStrings::webalize($name).$number.".".$ext;exit;
	    return NStrings::webalize($name).$number; 
	}
}
