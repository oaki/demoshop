<?

class Admin_GMAP implements ITemplateModul{
  private $id_type_modul;
  const  RELATIVE_PATH = '/require_modules/cms/modules/gmap';
  function __construct(){
  	
    $this->id_type_modul = dibi::fetchSingle("SELECT id_type_modul FROM type_modul WHERE dir='gmap'");
  	
  }
  
  function add($id_node){
    
  	$arr = array(
    	'id_node' =>$id_node,  
    	'width'=>400,
	    'height'=>400,
	    'zoom'=>12,
	    'langLocation'=>'sk',
      	
    );
 
    try{
       dibi::query("INSERT INTO gmap ",$arr);
    }catch (DibiDriverException $e){
    	
    	self::createSQL();
    	 dibi::query("INSERT INTO gmap ",$arr);
    }
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM gmap WHERE id_node=%i",$id_node);
   	Log::addLog($this,"Vymazanie gmap",$id_node);
  }
  
  function showForm(){
    $l = dibi::fetch("SELECT * FROM gmap WHERE id_node=%i",$_GET['id_modul']);
    
    
    MT::addTemplate(dirname(__FILE__).'/GMapAdmin.phtml','gmap');
   
    MT::addVar('gmap', 'l', $l);
    
    MT::addVar('gmap', 'node_info', dibi::fetch("SELECT * FROM [node] WHERE id_node=%i", $_GET['id_modul']));
    
    $form = new NForm();
    $form->addText('key', 'Kľúč')->getControlPrototype()->style="width:400px;";
    $form->addText('width', 'Sirka')->getControlPrototype()->style="width:80px;";
    $form->addText('height', 'Vyska')->getControlPrototype()->style="width:80px;";
    $form->addText('lat', 'Latitude')->getControlPrototype()->style="width:120px;";
    $form->addText('lon', 'Longitude')->getControlPrototype()->style="width:120px;";
    $form->addTextarea('infoWindowText', 'Info Text');
    $form->addCheckbox('googleBar', 'Google bar');
    $form->addCheckbox('enableScrollWheelZoom', 'Skrolovanie koleckom na mysi');
    $form->addCheckbox('doubleClickZoom', 'Priblizenie dvojklikom');
    $form->addText('langLocation', 'Lokalizacia')->getControlPrototype()->style="width:50px;";
    $form->addText('zoom', 'Priblizenie')->getControlPrototype()->style="width:50px;";
    $form->addText('address', 'Adresa')->getControlPrototype()->style="width:400px;";
    
    $form->addHidden('id_node', $_GET['id_modul']);
    
    $form->addSubmit('saveGMap', 'Ulozit');
    
    $form->setDefaults($l);
    
    
    MT::addVar('gmap', 'form', $form);
    
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM gmap WHERE id_node=%i",$id_node);
       	 
    $title =  '
    <h1>
    	<a href="?id_menu_item='.$_GET['id_menu_item'].'&amp;id_type_modul='.$id_type_modul.'&amp;id_modul='.$id_node.'">
    		';
    	($l['title']=="")?$title.="Nedefinovane":$title.=$l['title'];
    $title.='</a>
    </h1>';    
    return $title;
  }
  
  function action(){
    if(isset($_POST['saveGMap'])){
      $this->saveGMap($_POST, $_POST['id_node']);
    }
  }
  
	function saveGMap($values, $id_node){
	
  	$tmp = dibi::fetch("SELECT * FROM [gmap] WHERE id_node=%i", $id_node);
  	
  	unset($values['id_node']);
  	unset($values['saveGMap']);
  	//odstranenie premennych ktore niesu v databaze
  	$tmp = (array)$tmp;
  	foreach($values as $key=>$v){
  		if(!array_key_exists($key, $tmp)){	
  			unset($values[$key]);
  		}
  	}
  	
  	$values['googleBar'] = (int)$values['googleBar'];
  	$values['enableScrollWheelZoom'] = (int)$values['enableScrollWheelZoom'];
  	$values['doubleClickZoom'] = (int)$values['doubleClickZoom'];
	
  	if(isset($values['modul_visible'])){
		node::changeVisibility($id_node, $values['modul_visible']);
	}

  	dibi::query("UPDATE gmap SET ",$values," WHERE id_node=%i",$id_node);
  
    Log::addLog($this,"Uprava gmap","Menil:".NEnvironment::getSession('Authentication')->login_form,'',$id_node);
  }
  
/*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [gmap] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::saveGMap($values, $new_id_node);
  }
  
  function createSQL(){
  	dibi::query("
CREATE TABLE `gmap` (
  `id_node` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `width` int(4) DEFAULT NULL,
  `height` int(4) DEFAULT NULL,
  `lat` decimal(13,10) DEFAULT NULL,
  `lon` decimal(13,10) DEFAULT NULL,
  `infoWindowText` text NOT NULL,
  `googleBar` int(1) NOT NULL,
  `enableScrollWheelZoom` int(1) NOT NULL,
  `doubleClickZoom` int(1) NOT NULL,
  `langLocation` char(2) NOT NULL,
  `zoom` int(2) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_node`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
  	");
  }
}