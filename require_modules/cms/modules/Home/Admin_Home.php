<?

class Admin_Home implements ITemplateModul{
  private $id_type_modul;
  const  RELATIVE_PATH = '/require_modules/cms/modules/Home';
  const MODULE_NAME = 'Home';
  const TABLE = 'home';
  function __construct(){
  	
//    $this->id_type_modul = dibi::fetchSingle("SELECT id_type_modul FROM type_modul WHERE dir='Home'");
  	MT::addScripts('/jscripts/tiny_mce/tiny_mce.js','tinymce');
  	
  	MT::addScripts(self::RELATIVE_PATH.'/../article/setting_tiny_mce.js','tinymce_Home');
  	
  	
  }
  
  function add($id_node){
    $arr = array(
    	'id_node' =>$id_node,    	
    	'text' => '',    	
    );
    
    dibi::query("INSERT INTO ".self::TABLE." ",$arr);
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM ".self::TABLE." WHERE id_node=%i",$id_node);
  }
  
  function showForm(){
    $l = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i",$_GET['id_modul']);
    
    MT::addTemplate(dirname(__FILE__).'/default.phtml','Home');
   
    MT::addVar('Home', 'l', $l);
    
    $f = new Promo(self::MODULE_NAME, $_GET['id_modul']);

    MT::addVar('Home', 'showPromo', $f->render());
    
   
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i",$id_node);
       	 
    $title =  '
    <h1>
    	<a href="?id_menu_item='.$_GET['id_menu_item'].'&amp;id_type_modul='.$id_type_modul.'&amp;id_modul='.$id_node.'">
    		';
    	($l['text']=="")?$title.="Nedefinovane":$title.=NStrings::truncate( strip_tags($l['text']), 100);
    $title.='</a>
    </h1>';    
    return $title;
  }
  
  function action(){
    if(isset($_POST['saveHome'])){
      $this->saveHome($_POST, $_POST['id_node']);
    }
  }
  
  function saveHome($values, $id_node){
  	$tmp = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i", $id_node);
  	
  	unset($values['id_node']);
  	
  	//odstranenie premennych ktore niesu v databaze
  	
  	foreach($values as $key=>$v){
  		if(!array_key_exists($key, $tmp))	
  			unset($values[$key]);
  	}
  	
    
  	dibi::query("UPDATE ".self::TABLE." SET ",$values," WHERE id_node=%i",$id_node);
 
 
  }
  
  
  
  /*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [".self::TABLE."] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::saveHome($values, $new_id_node);
  }
  
}
