<?

class article implements ITemplateModul{
  private $id_type_modul;
  const  RELATIVE_PATH = '/require_modules/cms/modules/article';
  const MODULE_NAME = 'article';
  
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
    	'template' => 1
    );
    
    for($i=1; $i<50; ++$i){
    	if(dibi::fetchSingle("SELECT 1 FROM [article] WHERE url_identifier=%s", $arr['url_identifier'])){
    		$arr['url_identifier'] = Tools::random(8);
    	}else{
    		break;
    	}
    }
    dibi::query("INSERT INTO article ",$arr);
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM article WHERE id_node=%i",$id_node);
  }
  
  function showForm(){
	$l = dibi::fetch("SELECT * FROM article WHERE id_node=%i",$_GET['id_modul']);
    
	MT::addTemplate(dirname(__FILE__).'/adminArticle.phtml','article');
   
    MT::addVar('article', 'l', $l);
    
    $f = new FilesNode(self::MODULE_NAME, $_GET['id_modul']);
	$f->type = 'all';
    
    MT::addVar('article', 'showMultiupload', $f->render());
    
//    Admin_Comment::action(false);
//    MT::addVar('article', 'comments', Admin_Comment::getCommentListForArticle($_GET['id_modul']));
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM article WHERE id_node=%i",$id_node);
       	 
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
	  
    if(isset($_POST['saveArticle'])){
      $this->saveArticle($_POST, $_POST['id_node']);
    }
  }
  
  function saveArticle($values, $id_node){
  	$tmp = dibi::fetch("SELECT * FROM article WHERE id_node=%i", $id_node);
  	
  	unset($values['id_node']);
  	
  	//odstranenie premennych ktore niesu v databaze
  	
	$values = Tools::getValuesForTable('article', $values);
  	
  	$name = NStrings::webalize($values['url_identifier']);
  	$newname = "";
	$i = 0;
	while ( $i < 50 ) {
		if ($i == 0)
			$newname =  $name;
		else
			$newname = NStrings::webalize ( $name . $i );
		
		$s = dibi::fetchSingle ( "SELECT COUNT(id_node) FROM article WHERE url_identifier=%s", $newname, " AND id_node!=%i", $id_node );
		if ($s == 0) {
			break;
		} else {
			$i ++;
		}
		;
	}
  	
    $values['url_identifier'] = $newname;
  	
    
  	dibi::query("UPDATE article SET ",$values," WHERE id_node=%i",$id_node);
 
	  NEnvironment::getService('Article')->invalidateCache();
  }
  
  
  
  /*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [article] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::saveArticle($values, $new_id_node);
  }
  
}