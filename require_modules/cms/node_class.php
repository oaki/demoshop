<?
class NodeException extends Exception {
}



//---------------------------//

//-----------NODE------------//

//---------------------------//

class node{

	public function nodeFactory($id_type_modul, $dir = NULL){

   if(!$dir)
  	$dir = dibi::fetchSingle("SELECT dir FROM type_modul WHERE id_type_modul=%i",$id_type_modul);


   if($dir == ''){
		throw new NodeException("Modul neexistuje.");
   }

    switch($dir){

        case "article":

          

          return new article();

        break;

        

        case "poll":

         

          return new poll();

        break;

        

        case "gallery":          

          return new gallery();

        break;
        
         case "gmap":          
          return new Admin_GMAP();
        break;

        

        case "blog":

         

          return new Admin_Blog();

        break;
        
        case "UserForm":
         
          return new Admin_UserForm();
        break;

        case "GalleryMultiupload":         
          return new Admin_GalleryMultiuploadControl();
        break;
        
        case "product":
          require_once "moduls/product/product.php";
          return new Product();
        break;
        
        default:
        	//musi byt v tvare Admin_/dir/.php
        	$class_name = 'Admin_'.$dir;
        	return new $class_name;        	
        	
        	break;

    }

  }

  

  //pridanie modulov///

  function addModul($id_node,$id_type_modul){ 

   

      $this->nodeFactory($id_type_modul)->add($id_node);
   
      header("Location: admin.php?id_menu_item=".$_GET['id_menu_item']."&id_type_modul=".$id_type_modul."&id_modul=".$id_node);  

      exit; 

  }

  

  

  

  function nodeAction(){
	  $node = NodeModel::init();

   //-------------------------------//
    //--------AJAX REQUEST-----------//
    //------------------------------//
    if(@$_GET['ajax_change_modul_position']){
      if( is_numeric(@$_GET['id_node_ajax']) AND is_numeric($_GET['position']) ){
		  
		  dibi::query("UPDATE node SET position=%i",$_GET['position']," WHERE id_node=%i",$_GET['id_node_ajax']);
		  $node->invalidateCache();
		}
      exit;
    }
    
    if(@$_GET['ajax_change_modul_visible']){
      if( is_numeric(@$_GET['id_node_ajax']) AND is_numeric($_GET['modul_visible']) )
      	dibi::query("UPDATE node SET visible=%s",$_GET['modul_visible']," WHERE id_node=%i",$_GET['id_node_ajax']);
		$node->invalidateCache();
//      	echo dibi::$sql;
      exit;
    }

    
    //------SITE MAP pre premiestnenie modulu
    if(isset($_GET['ajax_show_site_map_for_modul'])){
    	
    	$m = new MenuItem();
    	$m->doTreeSelectMoveModul ( 0, 0, $_GET ['id_menu_item'], $_GET ['id_type_modul'] );
    	echo '<form action="" method="post">
        		<select name="id_menu_item_for_change">';
    	
    	echo $m->doTreeSelectMoveModulText;

    	echo '<input type="hidden" name="id_type_modul" value="'.htmlspecialchars($_GET['id_type_modul']).'" />
		       <input type="hidden" name="id_node" value="'.htmlspecialchars($_GET['id_node']).'" />
		       <input type="submit" name="changeModulParent" value="Ulož" /> 
      		</form>';
    	 
     exit;
    }
    
    if(isset($_POST['changeModulParent'])){
      $this->changeModulParent();
	  $node->invalidateCache();
      header("Location: ?id_menu_item=".$_POST['id_menu_item_for_change']."&showModulsOnPage=1");
      exit;
    }
    

    if(isset($_GET['id_menu_item']) AND isset($_GET['id_type_modul']) AND isset($_GET['id_modul'])){

      

      if(!dibi::fetchSingle("SELECT COUNT(*) FROM node WHERE id_menu_item=%i",$_GET['id_menu_item']," AND id_type_modul=%i",$_GET['id_type_modul']," AND id_node=%i",$_GET['id_modul'])){

      	header("Location:admin.php");

      	exit;

     	}

    }



    if( ( isset($_GET['addnode']) AND isset($_GET['id_type_modul']) ) OR isset($_POST['addModulToMenu']) ){

       if(isset($_POST['addModulToMenu']))
       	$id_type_modul = $_POST['id_type_modul']; 
       else 
       	$id_type_modul = $_GET['id_type_modul'];

        
       	$sequence = dibi::fetchSingle("SELECT MAX(sequence) FROM node WHERE id_menu_item=%i", $_GET['id_menu_item']) + 1;

       		$session = NEnvironment::getSession('Authentication');
			
			$config = NEnvironment::getConfig();
			
       		$arr = array(
       			'id_user' => NEnvironment::getUser()->getIdentity()->id,
       			'id_menu_item' => $_GET['id_menu_item'],
       			'sequence' => $sequence,
       			'id_type_modul' => $id_type_modul,
       			'visible' => $config['NODE_VISIBLE']
       		);

          	dibi::query("INSERT INTO node ",$arr);
          	

          	$this->addModul(dibi::insertId(), $id_type_modul);
			$node->invalidateCache();

    }

    

    //zmenenie poradia

    if(isset($_GET['modul_id_up']) OR isset($_GET['modul_id_down'])){

      $this->changeOrderNode();
		$node->invalidateCache();
      header("Location: admin.php?id_menu_item=".$_GET['id_menu_item']."&showModulsOnPage=1");
      exit;

    }

    

    //ak je iba jeden modul, hned sa zobrazi ale iba ak nieje setnuta section a showModulsOnPage

    if(isset($_GET['id_menu_item']) AND !isset($_GET['id_type_modul']) AND !isset($_GET['section']) AND !isset($_GET['showModulsOnPage']) AND !isset($_GET['changeMenuItem'])){

     $l = dibi::fetch("SELECT *, COUNT(*) as node_count FROM node WHERE id_menu_item=%i",$_GET['id_menu_item']," ORDER BY sequence");

        if($l['node_count'] == 1){    

	          header("Location: admin.php?id_menu_item=".$_GET['id_menu_item']."&id_type_modul=".$l['id_type_modul']."&id_modul=".$l['id_node']);  

	          exit;

        }     

    }

    

    //pridanie do premenej activeModul instanciu objektu

    if(isset($_GET['id_type_modul']) AND isset($_GET['id_modul'])){

        $this->activeModul = $this->nodeFactory($_GET['id_type_modul']);
        

        $this->activeModul->action();

    }

    

    if(isset($_GET['id_modul_del']) AND is_numeric($_GET['id_modul_del'])){

      $this->deleteNode(NULL,$_GET['id_modul_del']);
		$node->invalidateCache();
      header("Location: admin.php?id_menu_item=".$_GET['id_menu_item']);exit;

    }

     

  }

  

  

  function showModul(){              

    $list = dibi::fetchAll("SELECT * FROM node LEFT JOIN type_modul USING(id_type_modul) WHERE id_menu_item=%i",$_GET['id_menu_item']," ORDER BY sequence DESC");

	
    try{

      if(!$list)return false;

    }catch(NodeException $e){
		echo '<div style="border: 2px solid red; padding: 5px;">

          	'.$e->getMessage().'

        </div>';        

    }
    
    
  	if(isset($_GET['id_type_modul']) AND isset($_GET['id_modul'])){
  		$this->activeModul->showForm();
  	}else{

  		
		$array_list = array();
		foreach($list as $l){			
			$l['title'] = $this->nodeFactory($l['id_type_modul'], $l['dir'])->showTitle($l['id_node'],$l['id_type_modul']);
			$array_list[] = $l;			
		}
			
		MT::addTemplate(APP_DIR.'/templates/admin/showNode.phtml', 'showNode');
//			print_r($array_list);
		MT::addVar('showNode', 'array_list', $array_list);
//		print_r(NEnvironment::getConfig('variable'));
		MT::addVar('showNode', 'var', NEnvironment::getContext()->parameters);
  	}
	
  	         

  }

  

  

function deleteNode($id_menu_item, $id_node = false) {
	//ak sa maze z menu , mazu sa vsetky polozky z node, kde sa nachadzali moduli

	//ak nie id_node je idecko danoho uzla a zmaze sa len ten

	if ($id_node == false) {
		$list = dibi::fetchAll ( "SELECT id_node,id_type_modul FROM node WHERE id_menu_item=%i", $id_menu_item );
		foreach($list as $l){			
			$this->nodeFactory ( $l ['id_type_modul'] )->delete ( $l ['id_node'] );
		}
		dibi::query( "DELETE FROM node WHERE id_menu_item=%i", $id_menu_item );
	} else {
		$list = dibi::fetchAll ( "SELECT id_node,id_type_modul FROM node WHERE id_node=%i", $id_node );
		foreach($list as $l){
			$this->nodeFactory ( $l ['id_type_modul'] )->delete ( $id_node );
			dibi::query( "DELETE FROM node WHERE id_node=%i",$id_node );
		}
	}
}

function changeOrderNode() {

	$order = 'DESC';
	
	
	if (isset ( $_GET ['modul_id_up'] )) {
		$id_node = $_GET ['modul_id_up'];	
	}
	if (isset ( $_GET ['modul_id_down'] )) {
		$id_node = $_GET ['modul_id_down'];
	}
	
	
	$pom = dibi::fetch("SELECT sequence,id_node,id_menu_item FROM node WHERE id_node=%i",$id_node," LIMIT 1 ");
	
	if (isset ( $_GET ['modul_id_up'] )) {
		$sequence = $pom['sequence'] + 1.5;
	}
	if (isset ( $_GET ['modul_id_down'] )) {
		$sequence = $pom['sequence'] - 1.5;
	}
//	print_r($pom);
	dibi::query("UPDATE node SET sequence=%s",$sequence,"WHERE id_node=%i",$id_node);
//	echo dibi::$sql;
	
	//oprava poradia

	
	$list = dibi::fetchAll( "SELECT * FROM node WHERE id_menu_item='" . $pom['id_menu_item'] . "' ORDER BY sequence" );
	$sequence = 0;
//	echo dibi::$sql;
//	print_r($list);
//	exit;
//	exit;
	foreach($list as $l){
		++ $sequence;
		dibi::query ( "UPDATE node SET sequence=%i",$sequence," WHERE id_node=%i",$l ['id_node']);
	}
//	exit;
}

  

  private function changeModulParent(){


      dibi::query("

      	UPDATE node SET id_menu_item=%i",$_POST['id_menu_item_for_change']," WHERE id_node=%i",$_POST['id_node']
      );

      

  }

}