<?php


/**
 * @access public
 */
class Admin_GalleryMultiuploadControl extends FilesNode {
	const TEMPLATE_DIR = 'require_modules/cms/modules/GalleryMultiupload';
	const MODULE_NAME = 'gallery';
	/**
	 * @access public
	 */
	public function __construct() {		
		
		MT::addCss(self::TEMPLATE_DIR.'/css/index.css','gallery');
		MT::addCss(self::TEMPLATE_DIR.'/css/fileuploader.css','gallery_multiupload');
		MT::addScripts(self::TEMPLATE_DIR.'/fileuploader.js', 'gallery');
		
	}
	
/**
	 * @access public
	 */
	public function action() {
		 
		
	}
	

	/**
	 * @access public
	 * @param $id_node
	 * @param $id_type_modul
	 * @ParamType $id_node 
	 * @ParamType $id_type_modul 
	 */
	public function showTitle($id_node, $id_type_modul) {
		$l = dibi::fetch("SELECT * FROM gallery WHERE id_node=%i",$id_node);       	 
	    $title =  '
	    <h1>
	    	<a href="?id_menu_item='.$_GET['id_menu_item'].'&amp;id_type_modul='.$id_type_modul.'&amp;id_modul='.$id_node.'">
	    		';
	    	(!isset($l['name']) OR $l['name']=="")?$title.="Nedefinovane":$title.=$l['name'];
	    $title.='</a>
	    </h1>';    
	    return $title;
	}

	/**
	 * @access public
	 * @param $id_node
	 * @ParamType $id_node 
	 */
	public function add($id_node) {		
		$vars = NEnvironment::getConfig()->gallery;
		
	  	$arr = array(
	    	'id_node' =>$id_node,
	    	'name' => @$_POST['name'],
	    	'th_width' => $vars['th_width'],	  	
		  	'th_height' => $vars['th_height'],
		  	'width' => $vars['width'],
		  	'height' => $vars['height'],
	    	'open_in_window' => $vars['open_in_window'],
	  		
	    );
	     
	    dibi::query("INSERT INTO gallery ",$arr);       
	   
	    Log::addLog($this,"Pridanie fotogalerie",@$_POST['name']);
	}

	/**
	 * @access public
	 * @param $id_node
	 * @ParamType $id_node 
	 */
	public function delete($id_node) {
		
		parent::deleleFiles($id_node);
	  	
	    dibi::query("DELETE FROM gallery WHERE id_node=%i",$id_node);
	    
	    Log::addLog($this,"Vymazanie galerie",$id_node);
	}

	

	/**
	 * @access public
	 */
	public function showForm( $withOptions = true) {		
	   	MT::addTemplate(dirname(__FILE__)."/show_form.phtml", 'gallery');
	   	
	   	$id_node = $_GET['id_modul'];
	   	
	   	$form = new NForm('saveGalleryForm');
	   	$form->getElementPrototype()->id = "formGallery";
	   	$form->addGroup('Nastavenie galérie');
	   	$form->addText('name','Názov');
	   	$form->addText('th_width','Šírka náhľadov');
	   	$form->addText('th_height','Výška náhľadov');
	   	$form->addText('width','Šírka obrázka');
	   	$form->addText('height','Výška obrázka');
	   	$form->addSelect('open_in_window', 'Otvárať do nového okna', array(1=>'áno',0=>'nie'));
	   	$form->addSelect('type', 'Typ zobrazenia galérie', (array)NEnvironment::getConfig('gallery')->type );
	   	
	   	$form->addSubmit('saveGallery', 'Ulož');
	   	
	   	$form->onSubmit[] = array($this, 'save');
	   	
	   	$form->fireEvents();
	   	
	   	$defaults = self::get($id_node);
	   	if($defaults!=NULL)
	   		$form->setDefaults($defaults);
	   	
	    $f = new FilesNode(self::MODULE_NAME, $_GET['id_modul']);
	   	
	   	MT::addVar('gallery', 'showMultiupload', $f->render());
	   	MT::addVar('gallery', 'galleryOptionsForm',$form);
	   	
	   	MT::addVar('gallery', 'withOptions',$withOptions);
	   	
	   	MT::addVar('gallery', 'gallery', self::get($_GET['id_modul']));
	   	
	   	MT::addVar('gallery', 'list',  self::getAllFiles(self::MODULE_NAME, $_GET['id_modul'] ));
	   	
	}

	/**
	 * @access public
	 * @param $id_node
	 * @param $new_id_node
	 * @static
	 * @ParamType $id_node 
	 * @ParamType $new_id_node 
	 */
	public static function duplicate($id_node, $new_id_node) {
		dibi::begin();
	  	$tmp = self::get($id_node);
	  	
	  	unset($tmp['id_node']);
	  	self::add($new_id_node);
	  	self::saveGallery($tmp, $new_id_node);
	  	$vars = NEnvironment::getConfig()->gallery;
	  	$tmp = parent::getAll($id_node);
	  	
	  	$dir = $vars['dir_abs'].'/original';
	  	foreach($tmp as $t){
	  		$t['id_node'] = $new_id_node;
	  		unset($t['id_gallery_image']);	
	  		$new_filename = self::doNameFile($vars['dir_abs'].'/original', $t['src'], $t['ext']);
	  		//duplikovanie suboru
	  		$source =  $t['src'].'.'.$t['ext'];
	  		if(!copy($dir.'/'.$source, $dir.'/'.$new_filename.'.'.$t['ext']))
	  			echo ('Nepodarilo sa subor skopirovat: '.$dir.'/'.$source.' do :'.$dir.'/'.$new_filename.'.'.$t['ext']);
	  		$t['src'] = $new_filename;
	  		Files::addFile($t);
	  	}
	  	dibi::commit();
	}

	/**
	 * @access public
	 * @param $values
	 * @param $id_node
	 * @ParamType $values 
	 * @ParamType $id_node 
	 */
	public function save($values, $id_node = NULL) {
		if(!$id_node){
	  		$id_node = $_GET['id_modul'];
	  	}
	  	if( $values instanceof NForm) 
	  		$values = $values->getValues();
	  		
	  	$arr = array(  		
	    	'name' =>  $values['name'],
	    	'th_width' => $values['th_width'],	  	
		  	'th_height' => $values['th_height'],
		  	'width' => $values['width'],
		  	'height' => $values['height'],
	    	'open_in_window' => $values['open_in_window'],
	  		'type' => $values['type']
	  	);
	  	
	   dibi::query("UPDATE gallery SET ",$arr,"WHERE id_node=%i", $id_node);
	}
	
	
	public function get($id_node){
		return dibi::fetch("SELECT * FROM [gallery] WHERE id_node=%i",$id_node);
	}
}
