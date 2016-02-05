<?

class Admin_UserForm implements ITemplateModul{
  function __construct(){
  	
  }
  
  function add($id_node){
    $arr = array(
    	'id_node' =>$id_node,
    );
    dibi::query("INSERT INTO user_form ",$arr);
  }
  
  function delete($id_node){
  	dibi::query("DELETE FROM user_form WHERE id_node=%i",$id_node);
   	Log::addLog($this,"Vymazanie user_form",$id_node);
  }
  
  function showForm(){
    $l = dibi::fetch("SELECT * FROM user_form WHERE id_node=%i",$_GET['id_modul']);
    
    MT::addTemplate(dirname(__FILE__).'/Admin_UserForm.phtml','user_form');
   
    MT::addVar('user_form', 'l', $l);
  }
  
  function showTitle($id_node,$id_type_modul){
    $l = dibi::fetch("SELECT * FROM user_form WHERE id_node=%i",$id_node);
       	 
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
   
  }
  
  function saveUserForm($values, $id_node){
  	dibi::query("UPDATE [user_form] SET", array('title'=>$values['title']),'WHERE id_node = %id',$id_node);
  }
  
  
  
  /*
   * DUPLICATE
   */
  static public function duplicate($id_node, $new_id_node){
  	// nacitanie, co ma skopirovat 
  	$values = dibi::fetch("SELECT * FROM [user_form] WHERE id_node = %i", $id_node);
  	
  	//vytvorenie
  	self::add($new_id_node);
  	self::saveUserForm($values, $new_id_node);
  }
  
	
	
	function createSQL(){
		dibi::query('
			CREATE TABLE `user_form` (
			  `id_node` int(11) NOT NULL,
			  `title` varchar(255) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}
  
}