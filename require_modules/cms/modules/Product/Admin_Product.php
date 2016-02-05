<?

class Admin_Product implements ITemplateModul{
  private $id_type_modul;
  
  const MODULE_NAME = 'product';
  
  const  RELATIVE_PATH = '/require_modules/cms/modules/Product';
  function __construct(){
  	
//    $this->id_type_modul = dibi::fetchSingle("SELECT id_type_modul FROM type_modul WHERE dir='article'");
  	MT::addScripts('/jscripts/tiny_mce/tiny_mce.js','tinymce');
  	
  	MT::addScripts(self::RELATIVE_PATH.'/setting_tiny_mce.js','tinymce_article');
  	
  	
  }
  
  function add($id_node){
    $arr = array(
    	'id_node' =>$id_node,
    	'title' => '',
    	'text' => '',
    	'add_date' => new DateTime,
    	'change_date' => new DateTime,
    	'url_identifier'=>Tools::random(8),
    );
    
    for($i=1; $i<50; ++$i){
    	if(dibi::fetchSingle("SELECT 1 FROM [module_product] WHERE url_identifier=%s", $arr['url_identifier'])){
    		$arr['url_identifier'] = Tools::random(8);
    	}else{
    		break;
    	}
    }
    dibi::query("INSERT INTO [module_product] ",$arr);
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM [module_product] WHERE id_node=%i",$id_node);
   	Log::addLog($this,"Vymazanie produktu",$id_node);
  }
  
  function showForm(){
    $l = dibi::fetch("SELECT * FROM [module_product] WHERE id_node=%i",$_GET['id_modul']);
    
    MT::addTemplate(dirname(__FILE__).'/adminProduct.phtml','product');
   
    MT::addVar('product', 'l', $l);

    $f = new FilesNode(self::MODULE_NAME, $_GET['id_modul']);
    $f->type = 'all';
    $f->allowedExtensions = array('jpg','pdf','png');

    MT::addVar('product', 'showMultiupload', $f->render());

    
//    $g = new Admin_GalleryMultiuploadControl();
//    $g->action();
//    $g->showForm($withOptions = false);
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM [module_product] WHERE id_node=%i",$id_node);
       	 
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
  	
    if(isset($_POST['saveProduct'])){
      $this->saveProduct($_POST, $_POST['id_node']);
    }
  }
  
  function saveProduct($values, $id_node){
	  
  	$collums = Tools::getCollum('module_product');
	
	foreach( $values as $k=>$v){
		if(!in_array($k, $collums)){
			unset($values[$k]);
		}
	}
	
	
  	$newname = "";
	$name = $values['title'];
	
	$i = 0;
	while ( $i < 50 ) {
		if ($i == 0)
			$newname =  $name;
		else
			$newname = NStrings::webalize ( $name . $i );
		
		$s = dibi::fetchSingle ( "SELECT COUNT(id_node) FROM [module_product] WHERE url_identifier=%s", $newname, " AND id_node!=%i", $id_node );
		if ($s == 0) {
			break;
		} else {
			$i ++;
		}
		;
	}
  	
    $values['url_identifier'] = $newname;
  	
    
  	dibi::query("UPDATE [module_product] SET ",$values," WHERE id_node=%i",$id_node);
 
    Log::addLog($this,"Uprava produktu","Menil:".NEnvironment::getSession('Authentication')->login_form, $values['title'], $id_node);
  }
  
  
  
  /*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [module_product] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::saveProduct($values, $new_id_node);
  	
  	$id_file_node = FilesNode::getFileNode(self::MODULE_NAME, $id_node);
  	
  	if($id_file_node){  		
  		FilesNode::copyTo($id_file_node, self::MODULE_NAME, $new_id_node);
  	}
  	
  }
  
}