<?php

/**
 * @access  public
 * @package Mutliupload
 */
class FilesNode extends Files
{

    /**
     * @access     public
     *
     * @param char type_module
     * @param int  id_module
     *
     * @return char
     * @ParamType  type_module char
     * @ParamType  id_module int
     * @ReturnType char
     */
    protected $type_module, $id_module;

    function __construct($type_module, $id_module)
    {

        $this->type_module = $type_module;
        $this->id_module   = $id_module;

        //all actions for file node
        $actions    = array(
            'id_file_delete',
            'qqfile',
            'delete_all_images',
            'getImageList'
        );
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);

        parse_str(@$parsed_url['query'], $params);

        foreach ($params as $k => $p) {
            if (in_array($k, $actions))
                unset($params[$k]);
        }
        $parsed_url['params'] = $params;
        $parsed_url['url']    = $parsed_url['path'] . '?' . http_build_query($params);

        $this->parsed_url = $parsed_url;

        $this->action();
    }

    public function action()
    {

        if (isset($_GET['id_file_delete'])) {
            $this->deleteFile($_GET['id_file_delete']);
            header("Location: " . $this->parsed_url['url']);
            exit;
        }

        //uprava popisu
        if (isset($_GET['ajax_save_description'])) {
            $this->updateFile($_GET['id_file'], array('alt' => $_GET['alt']));
            echo 'upravené';
            exit;
        }

        if (isset($_GET['qqfile']) OR isset($_FILES['qqfile'])) {

            echo htmlspecialchars(json_encode($this->addFile($this->type_module, $this->id_module)), ENT_NOQUOTES);
            exit;
        }

        if (isset($_GET['delete_all_images'])) {

            self::deleleFiles($this->type_module, $this->id_module);
            header("Location: " . $this->parsed_url['url']);
            exit;
        }

        //vrati zobrazenie obrazkov
        if (isset($_GET['getImageList'])) {
            echo $this->renderImageList();
            exit;
        }

        //----------PORADIE SUBOROV-------------//

        if (isset($_GET['ajax_galler_image_order'])) {
            $array = explode("item_id_image_", $_GET['ajax_galler_image_order']);
            for ($i = 0; $i < count($array); $i++) {
                $this->updateFile(str_replace(",", "", $array[$i]), array('sequence' => $i));
            }
            echo "Poradie bolo uložené.";
            exit;
        }
    }

    public function render()
    {

        $template = new NFileTemplate();
        $template->registerFilter(new NLatteFilter());
        $template->setFile(dirname(__FILE__) . '/default.phtml');
        $template->id = 'Multiupload_' . $this->type_module;

        if (_NETTE_MODE) {
            $template->action = NEnvironment::getApplication()->getPresenter()->link('Homepage:upload');
        } else {
            $template->action = '/admin.php';
        }

        $template->imageList = $this->renderImageList();

        $template->parsed_url = $this->parsed_url;
        $template->list       = self::getAllFiles($this->type_module, $this->id_module, $this->type);

        return $template;
    }

    function renderImageList()
    {
        $template = new NFileTemplate();
        $template->registerFilter(new NLatteFilter());
        $template->setFile(dirname(__FILE__) . '/imageList.phtml');
        $template->id         = 'Multiupload_' . $this->type_module;
        $template->parsed_url = $this->parsed_url;
        $template->list       = self::getAllFiles($this->type_module, $this->id_module, $this->type);

        return $template;
    }

    public function addFileNode($type_module, $id_module)
    {
        dibi::query("INSERT INTO [file_node]", array('type_module' => $type_module, 'id_module' => $id_module));

        return dibi::insertId();
    }

    public function addFile($type_module, $id_module)
    {

        $id_file_node = self::getFileNode($type_module, $id_module);

        if (!$id_file_node) {
            $id_file_node = self::addFileNode($type_module, $id_module);
        }

        return self::uploadFile($id_file_node);
    }

    /**
     * @access public
     * @static
     */
    public static function getDatasource($type_module = false, $id_module = false)
    {
        return dibi::datasource("
			SELECT *
			FROM
				[file_node]
				JOIN [file] USING(id_file_node)
			WHERE 1
			%if", $type_module, "AND type_module = %s", $type_module, "
			%if", $id_module, "AND id_module = %i", $id_module);
    }

    /**
     * @access public
     * @static
     */
    public static function getFluent($type_module = false, $id_module = false)
    {
        return dibi::select('*')
            ->from('file_node')
            ->join('file')->using('(id_file_node)')
            ->where('1
							%if', $type_module, 'AND type_module = %s', $type_module, '
							%if', $id_module, 'AND id_module = %i', $id_module);
    }

    /**
     * @access    public
     *
     * @param id_file_node
     *
     * @static
     * @ParamType id_file_node
     */
    public static function getAllFiles($type_module, $id_module, $type = 'image')
    {

        switch ($type) {
            case 'image':
                return self::getFluent($type_module, $id_module)->where(" (ext = 'jpg' OR ext = 'gif' OR ext = 'png')")->orderBy('sequence')->fetchAll();
                break;

            case 'file':
                return self::getFluent($type_module, $id_module)->where(" (ext != 'jpg' AND ext != 'gif' AND ext != 'png') ")->orderBy('sequence')->fetchAll();
                break;
            default:
                return self::getFluent($type_module, $id_module)->orderBy('sequence')->fetchAll();
                break;
        }
    }

    public static function getOneFirstFile($type_module, $id_module, $type = 'image')
    {
        if ($type == 'image')
            return self::getFluent($type_module, $id_module)->where("  (ext = 'jpg' OR ext = 'gif' OR ext = 'png') ")->orderBy('sequence')->fetch();
        else
            return self::getFluent($type_module, $id_module)->where(" (ext != 'jpg' AND ext != 'gif' AND ext != 'png') ")->orderBy('sequence')->fetch();
    }

    /**
     * @access     public
     *
     * @param id_file_node
     *
     * @return boolean
     * @ParamType  id_file_node
     * @ReturnType boolean
     */
    public function deleleFiles($type_module, $id_module)
    {
        $all_files = self::getAllFiles($type_module, $id_module);

        foreach ($all_files as $f) {
            self::deleteFile($f['id_file']);
        }
    }

    public static function getFileNode($type_module, $id_module)
    {
        return dibi::query("SELECT id_file_node FROM [file_node] WHERE type_module =%s", $type_module, "AND id_module = %i", $id_module)->fetchSingle();
    }

    public static function getTypeModuleIdModule($id_file_node)
    {
        return dibi::fetch("SELECT type_module, id_module FROM [file_node] WHERE id_file_node = %i", $id_file_node);
    }

    public static function copyTo($id_file_node, $type_module, $id_module)
    {
        $old_module = self::getTypeModuleIdModule($id_file_node);
        $old_files  = self::getAllFiles($old_module['type_module'], $old_module['id_module']);

        NDebug::fireLog('type_module:' . $type_module);
        NDebug::fireLog('id_module:' . $id_module);

        foreach ($old_files as $f) {
            $new_file = Files::duplicateFile($f['id_file']);

            NDebug::fireLog('Stary subor: ' . $f['id_file']);
            NDebug::fireLog('Meno noveho suboru: ');
            NDebug::fireLog($new_file);

            $id_file_node = self::getFileNode($type_module, $id_module);

            if (!$id_file_node)
                $id_file_node = self::addFileNode($type_module, $id_module);

            $new_file['id_file_node'] = $id_file_node;
            Files::insert($new_file);
        }
    }

}
