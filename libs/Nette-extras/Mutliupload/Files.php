<?php


/**
 * @access public
 * @package Mutliupload
 */
class Files
{

    const _CRYPT = '!a@';

    protected $max_count_file = 0;// 0 is unlimited

    public $allowedExtensions = array();

    public $type = 'image';

    /**
     * @access public
     */
    public function uploadFile($id_file_node)
    {
//		print_r($id_file_node);
        // list of valid extensions, ex. array("jpeg", "xml", "bmp")
        $allowedExtensions = $this->allowedExtensions;
        if (empty ($allowedExtensions))
            $allowedExtensions = (array)NEnvironment::getConfig('file')->allowed_extension;
        // max file size in bytes

        $sizeLimit = (int)ini_get('post_max_size') * 1024 * 1024;

        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
        $result = $uploader->handleUpload(NEnvironment::getConfig('file')->dir_abs . '/original');

        if (@$result['success'] == 1)
            self::insert(array('id_file_node' => $id_file_node, 'src' => $result['filename'], 'ext' => $result['ext']));

        // to pass data through iframe you will need to encode all html tags

        return $result;
    }


    public static function insert($values)
    {

        $arr = array(
            'id_file_node' => $values['id_file_node'],
            'src' => $values['src'],
            'ext' => $values['ext'],
            'alt' => @$values['alt'],
            'sequence%sql' => (int)dibi::fetchSingle('SELECT MAX( sequence) + 1 FROM [file] WHERE id_file_node=%i', $values['id_file_node'], ' LIMIT 1'),
        );

        dibi::query("INSERT INTO [file]", $arr);
    }


    /**
     * @access public
     */
    public function deleteFile($id_file)
    {

        $file = dibi::fetch("SELECT * FROM [file] WHERE id_file=%i", $id_file);

        //vymazanie z temp adresara
        $files = scandir(NEnvironment::getConfig('file')->dir_abs . '/temp/');

        unset($files[0]);
        unset($files[1]);

        foreach ($files as $l) {

            @list($filename, $hash) = @explode('|', $l, 2);

            if ($file['src'] == $filename) {

                unlink(NEnvironment::getConfig('file')->dir_abs . '/temp/' . $l);
            }
        }

        @unlink(NEnvironment::getConfig('file')->dir_abs . '/original/' . $file['src'] . '.' . $file['ext']);

        dibi::query("DELETE FROM [file] WHERE id_file=%i", $id_file);

    }


    /**
     * @access public
     * @param $src_hash
     * @static
     * @ParamType $src_hash
     */
    public static function showImage($src_hash)
    {
        $crypt = new Crypt ();
        $crypt->Mode = Crypt::MODE_HEX;
        $crypt->Key = self::_CRYPT;

        list ($src, $hash) = explode("|", $src_hash, 2);

        list ($hash, $ext) = explode(".", $hash, 2);

        list ($width, $height, $flags) = explode("|", $crypt->decrypt($hash));
        //preverenie


        if (strlen($ext) > 5 or strstr($src, '/') or !is_numeric($width) or !is_numeric($width) or !is_numeric($height) or !is_numeric($flags) or $flags > 10) {
            throw new Exception ('Nastala chyba - nespravny subor.');
        }

        $image = NImage::fromFile(NEnvironment::getConfig()->file ['dir_abs'] . '/original/' . $src . '.' . $ext);
        
        //progressive
        $image->interlace(true);

        if ($flags != 6) {
            $image->resize($width, $height, $flags);
        }

        if ($flags == 5) {
            $image->crop("50%", "50%", $width, $height);
        }

        if ($flags == 6) {

            $image->resize($width, $height, 1);
            $i_height = $image->getHeight();
            $i_width = $image->getWidth();
            $position_top = (int)(($height - $i_height) / 2);
            $position_left = (int)(($width - $i_width) / 2);
            $color = array('red' => 255, 'green' => 255, 'blue' => 255);
            $blank = NImage::fromBlank($width, $height, $color);
            $blank->place($image, $position_left, $position_top);
            $image = $blank;
//			$image->crop ( "50%", "50%", $width, $height );
//			echo 1;
            $image->save(self::gURL($src, $ext, $width, $height, $flags, 'dir_abs'));

            $image->send();
            exit;
        }
//        var_dump(self::gURL($src, $ext, $width, $height, $flags, 'dir_abs'));
//        exit;
        $dirUrl = self::gURL($src, $ext, $width, $height, $flags, 'dir_abs');

        $image->save($dirUrl, 80, NImage::JPEG);

        $image->send();
        exit;
    }

    /**
     * @access public
     * @param $src
     * @param $ext
     * @param $width
     * @param $height
     * @param $flags
     * @param $dir
     * @param $mode
     * @static
     * @ParamType $src
     * @ParamType $ext
     * @ParamType $width
     * @ParamType $height
     * @ParamType $flags
     * @ParamType $dir
     * @ParamType $mode
     */
    public static function gURL($src, $ext, $width, $height, $flags = 0, $dir = 'dir', $mode = 'full_path')
    {
        $crypt = new Crypt ();
        $crypt->Mode = Crypt::MODE_HEX;
        $crypt->Key = self::_CRYPT;

        switch ($mode) {
            default :
                return NEnvironment::getConfig()->file [$dir] . '/temp/' . $src . '|' . $crypt->encrypt($width . '|' . $height . '|' . $flags) . '.' . $ext;
                break;

            case 'image_name' :
                return $src . '|' . $crypt->encrypt($width . '|' . $height . '|' . $flags) . '.' . $ext;
                break;

            case 'dir' :
                return NEnvironment::getConfig()->file [$dir] . '/temp/';
                break;
        }
    }

    public static function getFileURL($src, $ext)
    {
        return NEnvironment::getConfig()->file ['dir'] . '/original/' . $src . '.' . $ext;
    }

    /**
     * @access public
     * @param $filename
     * @ParamType $filename
     */
    public function removeFromCache($filename)
    {
        $list = self::getFilesFromDir();
        foreach ($list as $l) {
            self::deleteFileFromDir($l);
        }
    }

    /**
     * @access private
     * @static
     */
    private static function getFilesFromDir()
    {
        return scandir(NEnvironment::getConfig()->upload_file ['dir_abs'] . '/temp/');
    }

    /**
     * @access private
     * @static
     */
    private static function deleteFileFromDir($file)
    {
        $pom = explode('|', $file);
        if ($filename == $pom[0]) {
            unlink(NEnvironment::getConfig()->upload_file['dir_abs'] . '/temp/' . $file);
        }
    }

    protected static function updateFile($id_file, $values)
    {
        dibi::query("UPDATE [file] SET ", $values, "WHERE id_file = %i", $id_file);
    }

    static function duplicateFile($id_file)
    {
        $file = self::getFile($id_file);
//		print_r($file);

        $path = NEnvironment::getConfig('file')->dir_abs . '/original';

        $new_filename = self::getFileNameIfExist($path, $file['src'], $file['ext']);

        $old = $path . '/' . $file['src'] . '.' . $file['ext'];
        $new = $new_filename['dir'] . '/' . $new_filename['filename'];

        if (copy($old, $new)) {
//			echo $new.'
//			<br >';
        };
        return $new_file;
    }

    static function getFile($id_file)
    {
        return dibi::fetch("SELECT * FROM [file] WHERE id_file = %i", $id_file);
    }

    static function getFileNameIfExist($dir, $prefix, $ext)
    {
        $i = 0;
        $filename = $prefix . '.' . $ext;

        while (file_exists($dir . '/' . $filename)) { // If file exists, add a number to it.
            $filename = $prefix . '-' . ++$i . '.' . $ext;
//			NDebug::fireLog($filename);
        }

        $arr = array(
            'filename' => $filename,
            'dir' => $dir,
            'ext' => $ext,
            'prefix' => $prefix
        );
//		NDebug::fireLog($arr);
        return $arr;
    }
}
	
	
