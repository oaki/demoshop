<?php
class CMS extends NObject {
	function __construct() {
		
		//-----vytvorenie objektu menu
		
		$menu = new MenuItem ();
		
		$menu->menuAction ();
		
		//-----vytvorenie objektu node
		$node = new node ();
		$node->nodeAction ();
		
		MT::addContent( $menu->render (), 'leftHolder');
		
		
		
		try {
			
			if (isset ( $_GET ['id_menu_item'] )  OR isset($_GET ['addMenuItem'] ) OR isset($_GET['changeMenuItem'])){
				 MT::addTemplate(APP_DIR.'/templates/admin/modulHolder.phtml', 'modulHolder');
	    		 MT::addVar('modulHolder','type_modul', dibi::fetchAll("SELECT * FROM [type_modul] WHERE visible_for_user='1'") );
			}
	
    		 
			//zobrazenie zmeny polozky pre menu
			if(isset($_GET['changeMenuItem'])){
				$menu->showChangeMenuItem($_GET ['id_menu_item']);
			}	
			
				
			if (isset ( $_GET ['id_menu_item'] )  AND !isset($_GET['changeMenuItem'])){
				
				$node->showModul ();
			}
				
			//pridanie polozky do menu
			if (isset($_GET ['addMenuItem'] ))
				$menu->showAddMenuItem();
			
			
		
			//zachytenie vynimie
		} catch ( NodeException $e ) {
			echo '<div style="border: 2px solid red; padding: 5px;">' . $e->getMessage () . '</div>';
			exit ();
		}
		
		
	}
}