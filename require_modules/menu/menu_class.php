<?php

class MenuItem extends NObject {
	private $id_menu, $lang, $session, $var, $tree, $doTreeSelect;
	private static $tempSequence;
	public $childs, $parents = array();
	public static $url_identifier_number;
	public $bufferTree, $doTreeSelectMoveModulText;
	
	public $pageModel;
	
	/*new */
	public static $activedMenu;
	
	//---------Konstruktor Triedy

	function __construct($id_menu = NULL, $lang = NULL) {
		$this->session = NEnvironment::getSession ( 'Menu' );
		
		$this->pageModel = PageModel::init();
		
		if($id_menu == NULL){
			if (isset ( $_GET ["id_menu"] )) {
				$this->session ['id_menu'] = $_GET ["id_menu"];
			}
			
			if (! isset ( $this->session ['id_menu'] ))
				$this->session ['id_menu'] = NEnvironment::getVariable ( 'ADMIN_DEFAULT_IDMENU' );
			
			$this->id_menu = $this->session ['id_menu'];
		}else{
			$this->id_menu = $id_menu;
		}
		
		if(@$lang == NULL){
			if (! isset ( NEnvironment::getSession('Page')->lang ))
				$this->lang = NEnvironment::getVariable ( 'ADMIN_DEFAULT_LANG' );
			
			$this->lang = NEnvironment::getSession('Page')->lang;
		}else{
			$this->lang = $lang;
		}
		
		$this->var = NEnvironment::getConfig ( 'ADMINMENU' );
		
	
	}
	
	//--------Pridat polozku--------------------------- 

	//------

	function AddItemToSql($values) {
		
		if ($values ['name'] == "")
			throw new Exception ( "Musite vyplnit nazov" );
		
		$sequence = dibi::fetchSingle ( "SELECT MAX(sequence) as max FROM menu_item WHERE parent=%i", $values ['parent'], " ORDER BY sequence" ) + 1;
		
		
		
		if ($values ['slug'] == "") {
			$values ['slug'] = $values ['name'];
		}
		
		
		$values ['slug'] = $this->url_identifier ( $values ['slug'], '' );
				
		if($values['parent'] == NULL)
			$values['parent'] = 0;
		else{
//			$parent_url = self::getUrlIdentifier($values['parent']);
		}
		
		$collums = Tools::getCollum('menu_item');
		foreach( $values as $k=>$v){
			if(!in_array($k, $collums)){
				unset($values[$k]);
			}
		}
		
		$values['id_menu'] = $this->id_menu;
		
		dibi::query ( "INSERT INTO menu_item", $values );
		
		
		$id_menu_item = dibi::insertId ();
		
//		self::doSequence($values ['parent']);
	
		$this->repairUrls( );
		
		$this->pageModel->invalidateCache();
		
		return $id_menu_item;
	}
	
	private function changeMenuItem() {
	
		if ($_POST ['slug'] == "" OR @$_POST ['autoChangeUrl'] == 1) {
			$_POST ['slug'] = $this->url_identifier ( $_POST ['name'], $_GET ['id_menu_item'] );
		} else {
			$_POST ['slug'] = $this->url_identifier ( $_POST ['slug'], $_GET ['id_menu_item'] );
		}
		
		$values = $_POST;
		
		$collums = Tools::getCollum('menu_item');
		foreach( $values as $k=>$v){
			if(!in_array($k, $collums)){
				unset($values[$k]);
			}
		}
		
		dibi::query ( "UPDATE menu_item	SET ",$values," WHERE id_menu_item=%i",$_GET ['id_menu_item']);
		$this->repairUrls( );
		
		$this->pageModel->invalidateCache();
		
		Log::addLog ( $this, "Uprava polozky v menu", $_GET ['id_menu_item'] );
	}
	
	 function repairUrls( ){
		$list = dibi::fetchAll("SELECT id_menu_item FROM [menu_item]");
		foreach($list as $l){
			self::repairUrl($l['id_menu_item']);
		}
		
	}
	
	
	function repairUrl( $id_menu_item ){
		 
		$url = self::doUrl($id_menu_item);
		dibi::query ( "UPDATE menu_item	SET ",array('url_identifier'=>$url)," WHERE id_menu_item=%i", $id_menu_item);
	}
	
	 function doUrl( $id_menu_item ){
		$this->parents = array();
		$this->checkParent($id_menu_item);
		
		$tmp = array();
		
		foreach($this->parents as $p){
			$tmp[] = dibi::fetchSingle("SELECT slug FROM [menu_item] WHERE id_menu_item = %i",$p);
		}
		
		return rtrim(substr( implode("/", $tmp), 1).'/', '/');
	}

	
	
	
	function url_identifier($name, $id_current = 0) {
		$newname = "";
		$i = 0;
		while ( $i < 500 ) {
			if ($i == 0)
				$newname = NStrings::webalize ( $name );
			else
				$newname = NStrings::webalize ( $name . $i );
			
			$s = dibi::fetchSingle ( "SELECT COUNT(id_menu_item) FROM menu_item WHERE url_identifier=%s", $newname, " AND id_menu_item!=%i", $id_current );
			if ($s == 0) {
				break;
			} else {
				$i ++;
			}
			;
		}
		return $newname;
	}
	
	function menuAction() {
		
		if (isset ( $_POST ['changeMenuItem'] )) {
			$this->changeMenuItem ();
		}
		
		//posun hore

		if (isset ( $_GET ['menu_id_up'] )) {
			
			$id1 = $_GET ['menu_id_up'];
			$parent = dibi::fetchSingle ( "SELECT parent FROM menu_item WHERE id_menu_item=%i", $id1 );
			
			$list = dibi::fetchAll ( "SELECT id_menu_item FROM menu_item WHERE parent=%i", $parent, " AND id_menu=%i",$this->id_menu," ORDER BY sequence DESC " );
			
			$pom = 0;
			
			$id2 = "";
			
			foreach ( $list as $l ) {
				if ($pom == 1) {
					$id2 = $l ['id_menu_item'];
					$pom ++;
				}
				if ($l ['id_menu_item'] == $id1) {
					$pom ++;
				}
			}
			
			if ($id2 != "") {
				$p1 = dibi::fetchSingle ( "SELECT sequence FROM menu_item WHERE id_menu_item=%i", $id1 );
				$p2 = dibi::fetchSingle ( "SELECT sequence FROM menu_item WHERE id_menu_item=%i", $id2 );
				
				dibi::query ( "UPDATE menu_item SET sequence=%i", $p2, " WHERE id_menu_item=%i", $id1 );
				
				dibi::query ( "UPDATE menu_item SET sequence=%i", $p1, " WHERE id_menu_item=%i", $id2 );
			
			}

			self::doSequence($parent);
		
		}
		
		//posun dole

		if (isset ( $_GET ['menu_id_down'] )) {
			
			$id1 = $_GET ['menu_id_down'];
			$parent = dibi::fetchSingle ( "SELECT parent FROM menu_item WHERE id_menu_item=%i", $id1 );
			
			$list = dibi::fetchAll ( "SELECT * FROM menu_item WHERE parent=%i", $parent, " AND id_menu=%i",$this->id_menu," ORDER BY sequence " );
			
			$pom = 0;
			$id2 = "";
			foreach ( $list as $l ) {
				if ($pom == 1) {
					$id2 = $l ['id_menu_item'];
					$pom ++;
				}
				if ($l ['id_menu_item'] == $id1) {
					$pom ++;
				}
			}
			
			if ($id2 != "") {
				$p1 = dibi::fetchSingle ( "SELECT sequence FROM menu_item WHERE id_menu_item=%i", $id1 );
				$p2 = dibi::fetchSingle ( "SELECT sequence FROM menu_item WHERE id_menu_item=%i", $id2 );
				
				dibi::query ( "UPDATE menu_item SET sequence=%i", $p2, " WHERE id_menu_item=%i", $id1 );
				dibi::query ( "UPDATE menu_item SET sequence=%i", $p1, " WHERE id_menu_item=%i", $id2 );
			
			}
			self::doSequence($parent);
		
		}
		
		/*

	    * Zmazanie

	    */
		if (isset ( $_GET ['id_menu_item_del'] )) {
			$objNode = new node ();
			
			$this->deleteItemTree ( $_GET ['id_menu_item_del'], $objNode );
			
			$objNode->deleteNode ( $_GET ['id_menu_item_del'] );
			
			dibi::query ( "DELETE FROM menu_item WHERE id_menu_item=%i", $_GET ['id_menu_item_del'] );
			Log::addLog ( $this, "Vymazanie polozky z menu", $_GET ['id_menu_item_del'] );
		}
		
		/*

	    * pridanie menu_item

	    */
		if (isset ( $_POST ['addMenuItem'] ) and $_POST ['name'] != "") {
			$id_menu_item = $this->addItemToSql ($_POST);
			
			if (@$_POST ['id_type_modul'] == "") {
				header ( "Location: admin.php?id_menu_item=" . $id_menu_item );
			} else {
				header ( "Location: admin.php?id_menu_item=" . $id_menu_item . "&addnode=1&id_type_modul=" . $_POST ['id_type_modul'] );
			}
			
			Log::addLog ( $this, "Pridanie polozky do menu", $_POST ['name'] );
			exit ();
		}
		
		if (isset ( $_GET ['id_menu_item'] )) {
			$s = dibi::fetch ( "SELECT * FROM menu_item WHERE id_menu_item=%i", $_GET ['id_menu_item'] );
			
			if (!$s) {
				header ( "Location: admin.php" );
				exit ();
			}
		}
	}
	
	

	private function getTree(){
		$this->bufferTree = '';
		$s = dibi::query ( "
    	SELECT 
    		*
    	FROM 
    		menu_item 
    	WHERE 
    		lang=%s", $this->lang, " AND 
    		id_menu=%i", $this->id_menu, " 
    	ORDER BY sequence" )->fetchAssoc('parent,#');
		
		return $s;
	}
	
	//--------Zobrazi strom menu-----------------

	function doTree($parent = 0, $level = 0, $resetTree = false) {
		if($resetTree){
			$this->tree = $this->getTree();
		}
		
		
		if(isset($this->tree[$parent])){
			$last = count($this->tree[$parent]);		
			foreach ( $this->tree[$parent] as $k=>$l ) {
				if($k == 0){
					
					$level ++;
				
					$prew = "";
				
					$pom = 0;
				}
					$pom ++;
					
						
						$this->bufferTree .= '<li style="padding-left:' . ($level * 10) . 'px;width:' . (220 - $level * 10) . '"';
						if($l ['id_menu_item'] == @$_GET['id_menu_item']){
							$this->bufferTree .=' class="active"';	
						}
						
						$this->bufferTree .='>';
						
							$this->bufferTree .= '<a class="deleteIcon" href="javascript:confl(\'Naozaj chcete odstrániť položku ' . htmlentities (  str_replace("'", '-', $l ['name']) ) . '\',\'?id_menu_item_del=' . $l ['id_menu_item'] . '\');" style="float:right;margin-top:2px;" title="Zmazať položku"></a>';
							if ($prew != "") {
								$this->bufferTree .= '<a class="menuUpIcon" href="?menu_id_up=' . $l ['id_menu_item'] . '" title="Posunúť hore"><img style="float:right;" src="/require_modules/menu/images/up.gif" alt="Hore"/></a>';
							} else {
								$this->bufferTree .= '<img style="float:right;" src="/require_modules/menu/images/up_disabled.gif" alt=""/>';
							}
							
							
							if ($last != $pom) {
								$this->bufferTree .= '<a class="menuDownIcon" href="?menu_id_down=' . $l ['id_menu_item'] . '" title="Posunúť dole"><img style="float:right;" src="/require_modules/menu/images/down.gif" alt="Dole"/></a>';
							} else {
								$this->bufferTree .= '<img style="float:right;" src="/require_modules/menu/images/down_disabled.gif" alt=""/>';
							}
						$this->bufferTree .= '<a href="?id_menu_item=' . $l ['id_menu_item'] . '">' . $l ['name'] . '</a>

	            </li>';
					
					
					$prew = $l ['id_menu_item'];
					$this->doTree ( $l ['id_menu_item'], $level );
				
			}
		}
	}
	
	//--------Zobrazi strom menu-----------------
	public function doTreeToAccess($parent = 0, $level = 0, $resetTree = false) {
		if($resetTree){
			$this->tree = $this->getTree();
		}
		
		
		if(isset($this->tree[$parent])){
			$last = count($this->tree[$parent]);		
			foreach ( $this->tree[$parent] as $k=>$l ) {
					
						
						$this->bufferTree .= '<li style="padding-left:' . ($level * 10) . 'px;width:' . (220 - $level * 10) . '">
							<a href="?id_menu_item=' . $l ['id_menu_item'] . '">' . $l ['name'] . '</a>
	           			 </li>';
					
					$this->doTreeToAccess ( $l ['id_menu_item'], $level + 1 );
				
			}
		}
	}

	
	//--------Zobrazi strom menu-----------------

	public function doTreeSelect($parent = 0, $level = 0, $search_id = "", $toSql = "") {
		
		$list = dibi::fetchAll ( "SELECT * FROM menu_item WHERE parent=%i", $parent, " AND lang=%s", $this->lang, " AND id_menu=%i", $this->id_menu, " $toSql ORDER BY sequence" );
		
		if (count ( $list ) > 0) {
			++ $level;
			foreach ( $list as $l ) {
				$this->doTreeSelect.='<option value="'.$l ['id_menu_item'].'"';
	
				if ($search_id != "") {
					if ($l ['id_menu_item'] == $search_id)
						$this->doTreeSelect.='selected="selected"';
				}
				$this->doTreeSelect.='>';
				for($i = 0; $i < $level; ++ $i)
					$this->doTreeSelect.='&nbsp;&nbsp;';
			
				$this->doTreeSelect.=$l ['name'].'</option>';
				MenuItem::doTreeSelect ( $l ['id_menu_item'], $level, $search_id, $toSql );
			}
		}
	}
	
	//--------Zobrazi strom menu pre premiestnenie modulu-----------------//

	

	public function doTreeSelectMoveModul( $parent = 0, $level = 0, $id_menu_item = "", $id_type_modul = "") {
		 
		$list = dibi::fetchAll ( "SELECT * FROM menu_item WHERE parent=%i",$parent," AND lang=%i",$this->lang," AND id_menu=%i",$this->id_menu," ORDER BY sequence" );
		
		if (count ( $list ) > 0) {
			++ $level;
			foreach ( $list as $l ) {

					$this->doTreeSelectMoveModulText.='<option value="'.$l ['id_menu_item'].'"';
					if ($id_menu_item != "") {
						if ($l ['id_menu_item'] == $id_menu_item)
							$this->doTreeSelectMoveModulText.='selected="selected"';
					}
					
					$this->doTreeSelectMoveModulText.='>';
					
					for($i = 0; $i < $level; ++ $i)
						$this->doTreeSelectMoveModulText.='&nbsp;&nbsp;';
					

					$this->doTreeSelectMoveModulText.=$l ['name'];              

             		$this->doTreeSelectMoveModulText.='</option>';

				$this->doTreeSelectMoveModul ($l ['id_menu_item'], $level, $id_menu_item, $id_type_modul );
			}
		}
	}
	
	//--zobrazi strom v adminovy

	function render() {
		
		$t = new NFileTemplate ();
		
		$t->setFile ( APP_DIR . '/templates/admin/menu/menuLeftHolder.phtml' );
		$t->registerFilter ( new NLatteFilter() );
		$t->var = $this->var;
		
		$t->menu = $this->getTree();
		
		return ( string ) $t;

	}
	
	function showAddMenuItem() {
			
			$this->doTreeSelect = '';
			$this->doTreeSelect ( );
			
//			echo $this->doTreeSelect;exit;
			MT::addTemplate(APP_DIR.'/templates/admin/menu/menuAddNewItem.phtml', 'menuAddNewItem');
			MT::addVar('menuAddNewItem', 'treeSelect', $this->doTreeSelect);
			MT::addVar('menuAddNewItem', 'type_modul', dibi::fetchAll("SELECT * FROM type_modul WHERE visible_for_user = '1'"));
			
	}
	
	
	
	//---uprava polozky menu

	function showChangeMenuItem($id_menu_item) {
		
		$l = dibi::fetch( "SELECT * FROM menu_item WHERE id_menu_item=%i",$id_menu_item );
		if (!$l)
			throw new Exception ( "Polozka v menu neexistuje" );
		
		MT::addTemplate(APP_DIR.'/templates/admin/menu/menuChangeItem.phtml', 'changeMenu');
		
	//----zistenie deti + priradenie do pomocnej pre sql dotaz
		$this->checkChild ( $id_menu_item );
		
		$id_menu_item_to_sql = "";
		$count_child = count ( $this->childs ) - 1;
		foreach ( $this->childs as $i => $child ) {
			$id_menu_item_to_sql .= " AND ";
			$id_menu_item_to_sql .= " id_menu_item!='" . $child . "'";
		}
		
		$this->doTreeSelect = '';
		$this->doTreeSelect ( 0, 0, $l ['parent'], $id_menu_item_to_sql );
		
		
		$f = new FilesNode('menu', $l ['id_menu_item']);
		
		
		MT::addVar('changeMenu', 'showMultiupload', $f->render());
		  
		
		
		MT::addVar('changeMenu', 'l', $l);
		
		MT::addVar('changeMenu', 'doTreeSelect', $this->doTreeSelect);
		
	}
	
	
	
	protected function checkChild($id_parent) {
		$list = dibi::fetchAll( "SELECT id_menu_item FROM menu_item WHERE parent=%i",$id_parent );
		foreach($list as $l){
			$this->checkChild ( $l ['id_menu_item']);
		}
		$this->childs[] = $id_parent;
	}
	
	protected function checkParent($id_menu_item) {
		$list = dibi::fetchAll( "SELECT parent FROM menu_item WHERE id_menu_item=%i",$id_menu_item);
		foreach($list as $l){
			$this->checkParent ( $l ['parent']);
		}
		$this->parents [] = $id_menu_item;
	}
	//-------------vymazanie polozky z menu--------------

	function deleteItemTree($parent, $objNode) {
		
		$list = dibi::fetchAll ( "SELECT id_menu_item FROM menu_item WHERE parent=%i", $parent );
		foreach($list as $l){
				$this->deleteItemTree ( $l ['id_menu_item'], $objNode );
				$objNode->deleteNode ( $l ['id_menu_item'] );
				dibi::query( "DELETE FROM menu_item WHERE id_menu_item=%i",$l ['id_menu_item'] );
				
				//vymazanie z access

				
				
				Log::addLog ( $this, "Vymazanie polozky z menu", $l ['id_menu_item'] );
		}		
	}
	
	static public function doSequence($parent = 0){
		$menu_item = dibi::fetchAll("SELECT id_menu_item FROM [menu_item] WHERE parent = %s", $parent,"ORDER BY sequence");
		$counter = 0;
		foreach($menu_item as $m){
			dibi::query("UPDATE [menu_item] SET sequence = %i", ++$counter,"WHERE id_menu_item = %i", $m['id_menu_item']);
		}	
		
	}

}

