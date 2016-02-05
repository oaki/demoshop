<?php
class DuplicateException extends Exception{}

class Setting extends NObject{

	function __construct(){
		$this->action();
	}
	
	function action(){
		
	}
	
	function show(){
		MT::addTemplate( dirname(__FILE__).'/menu.phtml', 'leftHolder' );
		MT::addVar('leftHolder', 'dir', '/require_modules/Setting');
		MT::addCss('/require_modules/Setting/css/setting.css', 'settingCss');
		
		MT::addContent( MT::renderCurrentTemplate('leftHolder'), 'leftHolder');
		
		MT::addTemplate( dirname(__FILE__).'/setting.phtml', 'setting');
		NDebug::fireLog('start Duplicate Tree');
		switch(@$_GET['setting_action']){
			case 'lang':
					if(isset($_GET['ajax_action'])){
						switch($_GET['ajax_action']){
							case "active_lang":
								$active = dibi::fetchSingle("SELECT active FROM [lang] WHERE id_lang=%i",$_GET['id_lang']);
								
								dibi::query("UPDATE [lang] SET active=%i", (bool)!$active,"WHERE id_lang=%i",$_GET['id_lang']);
								break;
								
							case 'edit_name':
								dibi::query("UPDATE [lang] SET name=%s",$_GET['name'],"WHERE id_lang=%i",$_GET['id_lang']);
								break;

							case 'edit_rate':
								dibi::query("UPDATE [lang] SET rate=%s",$_GET['rate'],"WHERE id_lang=%i",$_GET['id_lang']);
								break;

							case 'edit_currency':
								dibi::query("UPDATE [lang] SET currency=%s",$_GET['currency'],"WHERE id_lang=%i",$_GET['id_lang']);
								break;
						}
						exit;
					}
					if(isset($_GET['id_lang_delete'])){
						dibi::query("DELETE FROM lang WHERE id_lang=%i",$_GET['id_lang_delete']);
						header('Location: admin.php?setting_action=lang');
						exit;
					}
					MT::addTemplate( dirname(__FILE__).'/lang.phtml', 'lang' );
					MT::addVar('lang', 'langs', dibi::fetchAll("SELECT * FROM lang") );	

					$form = new NForm();
					$renderer = $form->getRenderer();
//
					$renderer->wrappers['controls']['container'] = 'div';
					$renderer->wrappers['pair']['container'] = 'div';
					$renderer->wrappers['label']['container'] = 'span';
					$renderer->wrappers['control']['container'] = '';
					

					$form->addText('lang', 'Pridanie nového jazyka')
						->addRule(NForm::FILLED, 'Názov jazyka musí byť vyplnený.');
						
					$form->addText('iso', 'ISO')
						->addRule(NForm::FILLED, 'ISO jazyka musí byť vyplnený.');
					
					$form->addSubmit('add_lang','Pridať');
					
					$form->onSubmit[] = array($this,'addLang');
					 	
					
					$form->fireEvents();
					MT::addVar('lang','lang_form',$form);
				break;
		
			/*
			 * Duplicate
			 */
			case 'duplicate':
					MT::addTemplate( dirname(__FILE__).'/duplicate/duplicate.phtml', 'duplicate' );
					$form = new NForm();
					
					$form->addSelect('sourceLang', 'Zdrojový jazyk', dibi::query("SELECT iso,name FROM [lang]")->fetchPairs('iso', 'name'));
					$form->addSelect('destLang', 'Jazyk, do ktorého prekopírujeme všetky položky.', dibi::query("SELECT iso,name FROM [lang]")->fetchPairs('iso', 'name'));
					$form->addSubmit('duplicate_submit', 'Kopírovať');
					
					$form->onSubmit[] = array($this, 'duplicate');
					$form->fireEvents();
					MT::addVar('duplicate','duplicate_form', $form);
				break;
			
		}
	}
	
	function addLang(NForm $form){
		try{
			if($form->isValid()){
				$v = $form->getValues();
				dibi::query("INSERT INTO [lang]",array('name'=>$v['lang'], 'iso'=>$v['iso'], 'active'=>1));
			}
			header('Location: admin.php?setting_action=lang');
		}catch(Exception $e){
			$form->addError($e->getMessage());
		}
	}
	
	
	public static function getLangs(){
		return dibi::query('SELECT * FROM [lang] ORDER BY sequence')->fetchAssoc('iso');
	}
	
	
	/*
	 * Duplicate
	 */
	function duplicate($form){
		$values = $form->getValues();
		
		try{
			dibi::begin();
			if($values['sourceLang'] == $values['destLang'])
				throw new DuplicateException('Nemôžete kopírovať do toho istého jazyku.');
			

			if(dibi::fetchSingle("SELECT 1 FROM [menu_item] WHERE lang = %s", $values['destLang']) == 1)
				throw new DuplicateException('V jazyku "'.$values['destLang'].'" sa už nachádzajú položky.');
				
			self::recursionAddMenuItem(0, $values, 0);
			
			
			
			dibi::commit();
		}catch(DuplicateException $e){
			$form->addError($e->getMessage());
		}
		
		
	}
	
	
	function recursionAddMenuItem($parent = 0, $values, $new_parent = 0){
		//skopirovanie menu
		$sourceMenu = dibi::fetchAll("SELECT * FROM [menu_item] WHERE lang = %s", $values['sourceLang'],"AND parent = %i",$parent,"ORDER BY sequence");

		foreach($sourceMenu as $sm){
			$tmp_id_menu_item = $sm['id_menu_item'];
			unset($sm['id_menu_item']);
			
			$sm['parent'] = $new_parent;
			$menuInstance = new MenuItem($sm['id_menu'], $values['destLang']);
			$dest_new_parent = $menuInstance->AddItemToSql($sm);
			
			$id_file_node = FilesNode::getFileNode('menu', $tmp_id_menu_item);
		  	
		  	if($id_file_node){  		
		  		FilesNode::copyTo($id_file_node, 'menu', $dest_new_parent);
		  	}
		  	
			self::duplicateNode($tmp_id_menu_item, $dest_new_parent);
			
			self::recursionAddMenuItem($tmp_id_menu_item, $values, $dest_new_parent);
			unset($menuInstance);
		}
	}
	
	function duplicateNode($id_menu_item, $new_id_menu_item){
		
		$sourceNode = dibi::fetchAll("SELECT * FROM [node] WHERE id_menu_item = %i", $id_menu_item);
		$node = new Node();
		foreach($sourceNode as $sn){
				
//       		$arr = array(
//       			'id_user' => $session['id_user'],
//       			'id_menu_item' => $_GET['id_menu_item'],
//       			'sequence' => $sequence,
//       			'id_type_modul' => $id_type_modul,
//       		);
			$tmp_id_node = $sn['id_node'];
			unset($sn['id_node']);
			$sn['id_menu_item'] = $new_id_menu_item;
			
          	dibi::query("INSERT INTO node ",$sn);
          	
          	$last_id = dibi::insertId();
          	
          	$node->nodeFactory($sn['id_type_modul'])->duplicate($tmp_id_node, $last_id);          	
		}
	}
}
