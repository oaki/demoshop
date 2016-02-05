<?php

class CategoryFormControl extends BaseControl{
    public $mode = 'add';
    public static $_separator = '_lang_';

    /** @persistent */
    public $id_category;

    function render(){

	$template = $this->template;
	$template->setFile(dirname(__FILE__).'/default.phtml');

	$template->langs = Setting::getLangs();
	
	switch($this->mode){
	    case 'edit':
		$id_category = $this->getPresenter()->getParam('id_category');
		$this['baseForm']->addSubmit('btn','Upraviť');
		
		$this['baseForm']['id_category']->setValue($id_category);
		$values = array();
		foreach ($template->langs as $l){
		    
		    $val = CategoryModel::getFluent()->where('id_lang = %i',$l['id_lang'],'AND id_category=%i', $id_category)->fetch();
		 
		    $values += array(
			'name'.self::$_separator.$l['iso']=>$val['name'],
			'description'.self::$_separator.$l['iso']=>$val['description'],
			'link_rewrite'.self::$_separator.$l['iso']=>$val['link_rewrite'],
			'meta_title'.self::$_separator.$l['iso']=>$val['meta_title'],
//			'meta_keywords'.self::$_separator.$l['iso']=>$val['meta_keywords'],
			'meta_description'.self::$_separator.$l['iso']=>$val['meta_description'],
			'id_parent'=>$val['id_parent'],
			'show_on_bottom'=>$val['show_on_bottom'],
			'active'=>$val['active'],
		    );
		}
//print_r($values);
		$this['baseForm']->setDefaults($values);

		break;

	    default:
		$this['baseForm']->addSubmit('btn','Pridat');
		
		break;
	}
	
	$template->render();

    }

    function  createComponentBaseForm(){

//	NForm::extensionMethod('NForm::addCBTree', array('CBTree', 'addCBTree'));
//
//	$tree = new TreeView;
//
//	$tree->primaryKey = 'id_category';
//	$tree->parentColumn = 'id_parent';
//	$tree->useAjax = false;
//	$tree->addLink(null, 'name', 'id', true, $this->presenter);
//	$tree->dataSource = CategoryModel::getDatasource()->where("id_lang = %i",$this->getPresenter()->id_lang);
//$form->addCBTree('ctree', _('Kategórie'), $tree);
	$form = new NAppForm;

	$renderer = $form->getRenderer();
	// budeme generovat formulář jako definiční seznam
	$renderer->wrappers['controls']['container'] = NHtml::el('table')->addClass('standard');


	$langs = Setting::getLangs();
	foreach($langs as $k=>$lang){

	    $form->addGroup($lang['name'])->setOption('container', 'fieldset class=lang_fieldset id=lang_'.$lang['iso']);
	    $form->addText('name'.self::$_separator.$lang['iso'], _('Názov'))
		->addRule(NFORM::FILLED, 'Názov '.$lang['iso'].' musí byť vyplnený.' ) ;
	    $form->addTextArea('description'.self::$_separator.$lang['iso'],'Popis');
	    $form->addText('link_rewrite'.self::$_separator.$lang['iso'], 'URL link')->setDisabled();
	    $form->addText('meta_title'.self::$_separator.$lang['iso'], 'Titulok');
//	    $form->addText('meta_keywords'.self::$_separator.$lang['iso'], 'Kľúčové slová');
	    $form->addText('meta_description'.self::$_separator.$lang['iso'], 'Meta popis');
	    
	    
	}

	//$form->addText('icon','Názov ikony');
	$form->addCheckbox('show_on_bottom', 'Zobraziť v spodnej časti');
	$form->addGroup('');
	$form->addHidden('id_category');

	$c = new CategoryModel($this->getPresenter()->id_lang);
	$select = array(''=>'root');

	/*
	 * todo dorobit, aby sa nedala vybrat samu seba categoria
	 */
	$c->generateTreeToSelect($select, NULL, NULL);
	
	

	$form->addSelect('id_parent','Rodič', $select);
//		->addRule(NForm::FILLED, _('Rodič musí byť vyplnený.') );

	$form->addSelect('active', 'Aktívny', array( 1 => 'Povolený', 0 => 'Zakázaný'));
	
	$form->onSuccess[] = array($this, 'categoryAction');

	return $form;

    }


    function categoryAction(NForm $form){
	$values = $form->getValues();
	
	$langs = Setting::getLangs();
	
	switch($this->mode){

	    case 'edit':
		$id_category = $values['id_category'];

		foreach($langs as $l){
			
		    $lang_val = array();
			
		    foreach($values as $k=>$v){

				if(strpos($k, self::$_separator.$l['iso'])){
					list($name) = explode(self::$_separator.$l['iso'], $k);
					$lang_val[$name] = $v;

					//pridanie linky rewrite
					if($name == 'link_rewrite' AND $v == ''){
						$lang_val[$name] = NStrings::webalize( $values[ 'name'.self::$_separator.$l['iso']] );
					}elseif($name == 'link_rewrite'){
						$lang_val[$name] = NStrings::webalize( $lang_val[$name] );
					}
	//			    unset($values[$k]);
				}
		    }
		    
		    $lang_val+=array('id_parent'=>$values['id_parent'], 'active'=>$values['active'], 'show_on_bottom'=>$values['show_on_bottom']);

		    $c = new CategoryModel($l['id_lang']);
		    $c->save($lang_val , $id_category);
		}
		
		$c = new CategoryModel( 1 );
		$c->repairSequence();

	//	print_r($values);
		CategoryModel::repairCategoryRewriteLink();
		CategoryModel::invalidateCache();
		
		$this->getPresenter()->flashMessage( _('Kategória bola upravená.') );
		$this->getPresenter()->redirect("this");
		break;

	    case 'add':
		$id_category = CategoryModel::add(array('id_parent'=>$values['id_parent'], 'active'=>$values['active']));
		unset($values['id_parent'], $values['active']);

		foreach($langs as $l){
		    $lang_val = array();
		    foreach($values as $k=>$v){
			if(strpos($k, self::$_separator.$l['iso'])){
			    list($name) = explode(self::$_separator.$l['iso'], $k);
			    $lang_val[$name] = $v;

			     //pridanie linky rewrite
			    if($name == 'link_rewrite' AND $v == ''){
				$lang_val[$name] = NStrings::webalize( $values[ 'name'.self::$_separator.$l['iso']] );
			    }
			}
		    }

		    $lang_val+=array('id_category'=>$id_category, 'id_lang'=>$l['id_lang']);

		    CategoryModel::addCategoryLang($lang_val);

		}

		$c = new CategoryModel( 1 );
		$c->repairSequence();

		// prepisanie file_node na novy id_category
		dibi::query("UPDATE [file_node] SET id_module = %i",$id_category,"WHERE id_module = 999999 AND type_module = 'category'");

		CategoryModel::repairCategoryRewriteLink();
		CategoryModel::invalidateCache();
	//	print_r($values);
		$this->getPresenter()->flashMessage( _('Kategória bola pridaná.') );
		$this->getPresenter()->redirect("Eshop:default");
		break;
	}
    }


}