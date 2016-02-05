<?

class Admin_ContactForm implements ITemplateModul{
  private $id_type_modul;
  const  RELATIVE_PATH = '/require_modules/cms/modules/ContactForm';
  const TABLE = 'contact_form';
  function __construct(){
  	
//    $this->id_type_modul = dibi::fetchSingle("SELECT id_type_modul FROM type_modul WHERE dir='article'");
  	MT::addScripts('/jscripts/tiny_mce/tiny_mce.js','tinymce');
  	MT::addScripts('/jscripts/jquery/jquery-1.6.4.min.js','jquery');
  	MT::addTextScripts(' jQuery.noConflict();');
	MT::addScripts('/jscripts/jquery/jquery-ui-1.8.5.custom.min.js','jquery-ui');
	MT::addScripts('/jscripts/jquery/tags/jquery.tagsinput.min.js','tagsinput');
  	MT::addScripts(self::RELATIVE_PATH.'/../article/setting_tiny_mce.js','tinymce_article');
  	
	MT::addScripts('http://maps.google.com/maps/api/js?sensor=false','google_maps');
  	
  	MT::addCss('/jscripts/jquery/tags/jquery.tagsinput.css','tagsinput');
  	MT::addCss('/jscripts/jquery/custom-theme/jquery-ui-1.8.13.custom.css','jquery-ui');
  }
  
  function add($id_node){
    $arr = array(
    	'id_node' =>$id_node,
    	'text' => '',
    	'email' => '',
    );
   
    dibi::query("INSERT INTO ".self::TABLE,$arr);
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM ".self::TABLE." WHERE id_node=%i",$id_node);
   	Log::addLog($this,"Vymazanie clanku",$id_node);
  }
  
  function showForm(){
    $l = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i",$_GET['id_modul']);
    
    MT::addTemplate(dirname(__FILE__).'/default.phtml',self::TABLE);
   
    MT::addVar(self::TABLE, 'l', $l);
     MT::addVar(self::TABLE, 'table', self::TABLE);
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i",$id_node);
       	 
    $title =  '
    <h1>
    	<a href="?id_menu_item='.$_GET['id_menu_item'].'&amp;id_type_modul='.$id_type_modul.'&amp;id_modul='.$id_node.'">
    		';
    	$title.="Kontakt";
    $title.='</a>
    </h1>';    
    return $title;
  }
  
  function action(){
    if(isset($_POST['save'.self::TABLE])){
      $this->save($_POST, $_POST['id_node']);
    }
  }
  
  function save($values, $id_node){
  	$tmp = dibi::fetch("SELECT * FROM ".self::TABLE." WHERE id_node=%i", $id_node);
//  	print_r($tmp);
  	unset($values['id_node']);
  	
  	//odstranenie premennych ktore niesu v databaze
  	
  	foreach($values as $key=>$v){
  		if(!array_key_exists($key, $tmp)){	
  			unset($values[$key]);
  			
  		}
  	}
//  	print_r($values);
  	
  	dibi::query("UPDATE ".self::TABLE." SET ",$values," WHERE id_node=%i",$id_node);
 
    Log::addLog($this,"Uprava contact form","Menil:".NEnvironment::getSession('Authentication')->login_form, '', $id_node);
  }
  
  
  
  /*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [".self::TABLE."] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::save($values, $new_id_node);
  }
  
}