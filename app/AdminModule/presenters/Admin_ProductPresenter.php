<?php

class Admin_ProductPresenter extends Admin_BasePresenter {

    function beforeRender() {
	//	$this['header']['js']->addFile('jquery/jsTree/jquery.jstree.js');
		$this['header']['js']->addFile('jquery/jquery.collapsibleCheckboxTree.js');
		$this['header']['js']->addFile('jquery/Multiselect/jquery.multiselect.js');
		$this['header']['css']->addFile('../jscripts/jquery/Multiselect/multiselect-dual-list.css');

	
		$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');
		
		$this['header']['css']->addFile('components/keyword/keyword.css');

    }

    function renderEdit($id) {
		$files = new FilesNode( 'product', $id);
		$files->type = 'all';

		$this->template->fileNode = $files;

		$s = NEnvironment::getSession("Admin_Eshop");
		$this->template->back_url = $s['back_url'];
		
		$product = ProductModel::get($id, $this->id_lang);
		$keywords = $product['meta_title'];
		if($keywords == ''){
			$keywords = $product['name'];
		}
		
		$this['keyword']->setKeywords($keywords);
		
		
		
    }


    function renderAdd() {
		$id = ProductModel::insertNew();
		$this->redirect('edit', array('id'=>$id));	
    }

//    function actionDelete($id){
//	ProductModel::delete($id);
//	$this->flashMessage( _('Product zmazaný') );
//	$this->redirect($code)
//    }


	

    private function baseForm() {
		$f = new MyForm;

		$renderer = $f->getRenderer();
		$renderer->wrappers['controls']['container'] = NHtml::el('table')->addClass('standard');


		$f->addGroup(_('Pridanie produktu'))
			->setOption('container',
				NHtml::el('div')
				->class('groupHolder')
				->id('groupHolderMain')
		);

		$f->getElementPrototype()
			->class('long_input');


		$langs = Setting::getLangs();
		
		foreach ($langs as $l) {
			
			$f->addGroup((count($langs)==1)?'':$l['name'])->setOption('container', 'fieldset class=lang_fieldset id=lang_' . $l['iso']);
			$f->addText('name' . CategoryFormControl::$_separator . $l['iso'], 'Názov')
				->addRule(NForm::FILLED, _('Nazov musí byť vyplnený.') );
			$f->addTextArea('description' . CategoryFormControl::$_separator . $l['iso'], _('Popis') );
			$f->addTextArea('long_description' . CategoryFormControl::$_separator . $l['iso'], _('Dlhý popis') );
//			$f->addTextArea('referencies' . CategoryFormControl::$_separator . $l['iso'], _('Referencie') );

			$f->addText('link_rewrite' . CategoryFormControl::$_separator . $l['iso'], _('URL link') );
			$f->addText('meta_title' . CategoryFormControl::$_separator . $l['iso'], _('Titulok') );
//			$f->addText('meta_keywords' . CategoryFormControl::$_separator . $l['iso'], _('Kľúčové slová') );
			$f->addText('meta_description' . CategoryFormControl::$_separator . $l['iso'], _('Meta popis') );
			
		}

		$f->addGroup();

//		$f->addSelect('id_product_supplier',
//			'Výrobca',
//			array(NULL => '-------') + SupplierModel::getFluent()->fetchPairs('id_product_supplier', 'name')
//		);


			//->add(NHtml::el('span')->setHtml('palo'));

//		$f->addText('price', _('Cena'))
//			->addRule(NForm::FLOAT, _('Cena musí byť číslo.'));
		
//		$f->addText('code', _('Kód tovaru'));
		
//		$f->addText('weight', _('Váha'));
		
//		$f->addText('packing', _('Balenie'));
		
//		$f->addText('unit_of_measure', _('Merná jednotka'));
			
//		$f['price']->getControlPrototype()->addClass('short_text');
//
//		$f->addText('pack_type', 'Druh balenia');
//		$f['pack_type']->getControlPrototype()->addClass('short_text');
//
//
//		$f->addText('color', 'Farba');
//		$f['color']->getControlPrototype()->addClass('short_text');
//
//		$f->addText('capacity', 'Objem');
//		$f['capacity']->getControlPrototype()->addClass('short_text');
//
//		$f->addText('product_number', 'Číslo produktu');
//		$f['product_number']->getControlPrototype()->addClass('short_text');
//
//
//		$f->addText('pack_count', 'Balenie');
//		$f['pack_count']->getControlPrototype()->addClass('short_text');

		//$f->addText('ean13', 'Kód')->getControlPrototype()->addClass('short_text');

		$vat = VatModel::init();
		
		
		if($this->context->parameters['SHOW_TAX_FOR_PRODUCT'] == 1){
			$f->addRadioList('id_vat', _('DPH %'), $vat->getFluent()->fetchPairs('id_vat','name'))->setDefaultValue( $vat->getDefault() );
		}else{
			$f->addHidden('id_vat')->setDefaultValue( $vat->getDefault() );
		}
//
//		$f->addText('delivery_date', _('Dodacia lehota (dni)'))
//			->getControlPrototype()->addClass('short_text');

		$f->addRadioList('active', 'Aktívny', array(1 => 'Povolený', 0 => 'Zakázaný'))->setDefaultValue(1);
//		$f->addRadioList('available', 'Dostupný do 48 hodín', array(1 => 'áno', 0 => 'nie'))->setDefaultValue(1);
		

//		$f->addText('weight', 'Hmotnosť (kg)')->getControlPrototype()->class = 'small';

//		$f->addCheckbox('our_tip', 'Najpredávanejší');
		//$f->addCheckbox('sale', 'Výpredaj');
		
		$f->addCheckbox('home', 'Zobraziť na úvode');
		$f->addCheckbox('our_tip', 'TOP');
		$f->addCheckbox('news', 'Novinka');
		$f->addCheckbox('sale', 'Akcia');
		$f->addText('sale_percent', 'Zľava %')->getControlPrototype()->class = 'small';


		$f->addGroup('Kategórie')
		; //->setOption('container', NHtml::el('div')->class('groupHolder')->id('categoryHolderOptions'));

		NForm::extensionMethod('NForm::addCBTree', array('CBTree', 'addCBTree'));
		$tree = new TreeView;

		$id_product = $this->getParam('id');

		$tree->primaryKey = 'id_category';
		$tree->parentColumn = 'id_parent';
		$tree->useAjax = true;
		$tree->mode = 1;
		
		$tree->addLink(null, 'name', 'id', true, $this->presenter);
		$tree->dataSource = CategoryModel::getTreeCheckProduct($id_product)->where("id_lang = %i", $this->getPresenter()->id_lang);

		$f->addCBTree('id_categories', _('Kategórie'), $tree)
			->initialState = 'expand';

		$f->addGroup('Príslušenstvo');
				
		$product_alternative = ProductModel::getFluent($this->id_lang)->where('1=1 %if',$id_product,'AND id_product != %i', $id_product,"%end")->fetchPairs("id_product",'name');

		$f['product_alternative'] = new MultiSelectDualList( 'Príslušenstvo', $product_alternative);

		$templateGroupParams = $this->getService('ProductTemplateGroupModel')->fetchPairs();
		
		//ak je iba jedna, nezobrazi sa vyber
		if(count($templateGroupParams)>1){
			$f->addSelect('id_product_template_group', 'Skupina parametrov', $templateGroupParams);
		}else{			
			$f->addHidden('id_product_template_group')->setDefaultValue( key($templateGroupParams) );
		}
		$f->addHidden('id_product');

		$f->addGroup('')
			->setOption('container', NHtml::el('div')->class('button'));


	return $f;
    }

	/*
	 * handles
	 */
	function handleAddGeneratedParam(NFORM $form){
		$values = $form->getValues();

		$id_product = $values['id_product'];
		unset($values['id_product']);
//print_r($values);exit;
		//preindexovanie,
		$input = array();
		$input_keys = array();
		foreach($values as $k=>$v){
			if($v == NULL){
				$input[] = array( 0=>NULL);
			}else{
				$input[] = $v;
			}
			
			$input_keys[] = $k;			
		}
//print_r($input);
		$all_combination = ProductModel::array_cartesian_product($input);

//		print_r($all_combination);exit;
		foreach($all_combination as $key=>$combination){
			$arr = array();
			foreach($combination as $k=>$c){
				$arr[ $input_keys[$k] ] = $c;				
			}

			if($id_product!=NULL)
				$arr['id_product'] = $id_product;

			if( !ProductModel::isParamValue($arr) )
				ProductModel::addProductParamValue($arr);
		}

		$this['productParams']->invalidateControl();

	}

	function handleAddEmptyParam($id_product){
		$values['id_product'] = $id_product;
		ProductModel::addProductParamValue($values);
		$this['productParams']->invalidateControl();
//		$this->redirect('this');
	}

	function handleDeleteProductParam($id_product_param){
		ProductModel::deleteProductParamValue($id_product_param);
		$this['productParams']->invalidateControl();
	}

	function handleDelete($id){
		ProductModel::delete($id);
    }


	function getId(){
		$id = $this->getParam('id');
		if($id == ''){
			//vygeneruj a zapis do session
			$session = NEnvironment::getSession('admin_product');
			if(!isset($session['temp_id'])){
				$session['temp_id'] = rand(10000,999999);
			}
			$id = $session['temp_id'];
		}
		return $id;
	}

    /*
     * Create Component
     */
    function createComponent($name) {
	switch ($name) {


//	    case 'addForm':
//			$form = $this->baseForm();
//			$form->addSubmit('btn', 'Pridať');
////			$values['product_alternative'] = ProductModel::getFluent($this->id_lang)->where('id_category = 5')->fetchPairs("id_product",'id_product');
//			//poziadavka klienta - zobrat vsetky produkty z doplnkov
//			
//			
//			$form->setDefaults( array('id_product_template_group'=>$this->getService('ProductTemplateGroupModel')->getIdDefaultTemplate()) );
//			
//
//			$form->onSuccess[] = callback($this, 'add');
//			return $form;
//		break;


		


	    case 'editForm':

			$id_product = $this->getPresenter()->getParam('id');

			$form = $this->baseForm();

			
			$form['id_product']->setValue($id_product);
			$values = array();

			

//Titulok 	- doplnit categoria+ nazov produktu
//Kľúčové slová -
//Meta popis - doplnit categoria+ nazov produktu  + Výkon + Dĺžka Horenia

			foreach ($this->template->langs as $l) {

				$val = ProductModel::get($id_product, $l['id_lang']);

				// ziste ktore komponenty maju jazykovu mutaciu
				$controls_for_lang = array();
				
				foreach ($form->getComponents() as $control) {
				
				
				if (strpos($control->getName(), CategoryFormControl::$_separator)) {
					if (strpos($control->getName(), CategoryFormControl::$_separator . $l['iso'])) {
					list($val_key) = explode(CategoryFormControl::$_separator . $l['iso'], $control->getName());
					$values += array($control->getName() => $val[$val_key]);
					}
				} else {
					if (isset($val[$control->getName()]))
					$values += array($control->getName() => $val[$control->getName()]);
				}


				}
			}
	//print_r(ProductModel::getProductAlternative($id_product));exit;
			$values['product_alternative'] = ProductModel::getProductAlternative($id_product);
			// vyriesenie categorii

			//nacitanie prveho parametru - iba pre jednoparametrove produkty
			$param = (array)dibi::fetch("SELECT * FROM [product_param] WHERE id_product = %i",$id_product);
			
			$form->setDefaults( array_merge( $param,$values ) );



			$form->addSubmit('btn_save_and_stay', 'Upraviť a zostať tu');
			$form->addSubmit('btn_save', 'Upraviť');
			$form->onSuccess[] = callback($this, 'save');
			return $form;
		break;


		/*
		 * generator
		 */
		case 'generatorForm':
			//ProductModel::repairMinPrice();
			
			NFormContainer::extensionMethod('NFormContainer::addCheckboxList', array('CheckboxList', 'addCheckboxList'));

			$sizes = dibi::query("SELECT size FROM [product_param] WHERE size !='' GROUP BY size")->fetchPairs('size','size');
			$colors = dibi::query("SELECT color FROM [product_param] WHERE color !='' GROUP BY color")->fetchPairs('color','color');
			$materials = dibi::query("SELECT material FROM [product_param] WHERE material !='' GROUP BY material")->fetchPairs('material','material');

			$f = new MyForm();
//			$f->getElementPrototype()->addClass('ajax');
			$renderer = $f->getRenderer();
			$renderer->wrappers['controls']['container'] = NHtml::el('div')->addClass('container');
			$renderer->wrappers['pair']['container'] = NHtml::el('div')->addClass('pair-container');
			$renderer->wrappers['label']['container'] = NHtml::el('h3');
			$renderer->wrappers['control']['container'] = NHtml::el('div')->addClass('input-container');

//			usort($sizes, 'EshopProductControl::cmp');

			$f->addCheckboxList('size', 'Veľkosť', $sizes);
			$f->addCheckboxList('color', 'Farba', $colors);
			$f->addCheckboxList('material', 'Material', $materials);

			//ak je pridavanie a neexistuje id
			
					
			$f->addHidden('id_product', $this->getParam('id'));
//
//			
//			$renderer = $f->getRenderer();
//			$renderer->wrappers['label']['container'] = NULL;
//
//			//ziskaj vsetky mozne velkosti
//			$sizes = dibi::fetchAll("SELECT size FROM [product_param] GROUP BY size");
//			$f->addGroup('Veľkosť');
//			foreach($sizes as $k=>$size){
//				$f->addCheckbox('size_'.$k, $size['size']);
//			}
//
//			//ziskaj vsetky mozne farby
//			$colors = dibi::fetchAll("SELECT color FROM [product_param] GROUP BY color");
//			$f->addGroup('Farba');
//			foreach($colors as $k=>$color){
//				$f->addCheckbox('color_'.$k, $color['color']);
//			}
//
//			//ziskaj vsetky mozne material
//			$materials = dibi::fetchAll("SELECT material FROM [product_param] GROUP BY material");
//			$f->addGroup('Material');
//			foreach($materials as $k=>$material){
//				$f->addCheckbox('material_'.$k, $material['material']);
//			}


			$f->addGroup()->setOption('container', 'fieldset class=clear');
			$f->addSubmit('btn_generate','Generovať');

			$f->onSuccess[] = array($this, 'handleAddGeneratedParam');
			return $f;
			break;

		case 'productParams':
			
			
//			$d = ProductModel::getProductParamDatasource( $this->getParam('id'))->fetchAll();
			$id_product = $this->getParam('id');
			
			
			$grid = new Tabella( ProductModel::getProductParamDatasource( $id_product ),
				array(
					'sorting'=>'asc',
					'order'=>'sequence',
					'id_table'=>'id_product_param',
					'limit'=>50,
					"onSubmit" => function( $post ) {
//						print_r($post);exit;
						ProductModel::setProductParamValue($post, $post['id_product_param']);
					},
					"onDelete" => function( $id ) {
					    ProductModel::deleteProductParamValue($id);
					}
				)
				
			);

			$el = NHtml::el( "div" );
			$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'addEmptyParam!' , array('id_product'=>$id_product))
					)->addClass( 'addIcon ajax' )
			);

			$grid->addColumn($el, 'sequence', array('width'=>20,  'filter'=>NULL, "editable" => true ) );
			
			//vytiahnutie template_group pre produkt - ake bude mat parametre
			if($id_product){
				$id_product_template_group = ProductModel::getFluent(1, false,false)->removeClause('select')->select('id_product_template_group')->where('id_product = %i',$id_product)->fetchSingle();
			}else{
				//ak je id_product NULL jedna sa pridavanie produktu
				if( $id_product == NULL AND $this->getParam('id_product_template_group')!=NULL){
					$id_product_template_group = $this->getParam('id_product_template_group');

				}else{
					$id_product_template_group = $this->getService('ProductTemplateGroupModel')->getIdDefaultTemplate();
				}
			}
			
			
			
			$params = $this->getService('ProductTemplateGroupModel')->fetchAssocAllParam( $id_product_template_group);
			
			
			foreach($params as $p){
				if($p['checked']){
					$grid->addColumn( $this->getService('translator')->translate($p['row_name']), $p['row_name'], array( "width" => 50, "editable" => true ) );
				}
			}
			
			
			
			$grid->addColumn( "Cena", "price", array( "width" => 100, "editable" => true
//				,
//				"renderer" => function( $row ) {
//					$el = NHtml::el( "td" );
//					$el->add(
//					NHtml::el( 'a' )->href(
//						NEnvironment::getApplication()->getPresenter()->link( 'deleteProductParam!' , array('id_product_param'=>$row->id_product_param))
//						)->addClass( 'deleteIcon ajax' )
//					);
//
//					return $el;
//				})
			));
			
//			_repairPriceForView
			
//
//			
//			$grid->addColumn( "Na sklade", "stock", array( "width" => 50, "editable" => true ) );
////			$grid->addColumn( "Farba", "color", array( "width" => 100, "editable" => true ) );
////			$grid->addColumn( "Veľkosť", "size", array( "width" => 100, "editable" => true ) );
////			$grid->addColumn( "Material", "material", array( "width" => 100, "editable" => true ) );
//			$grid->addColumn( "Napojenie", "connection", array( "width" => 100, "editable" => true ) );
//			$grid->addColumn( "Cena", "price", array( "width" => 100, "editable" => true ) );

			
			$grid->addColumn("", "",
				array(
				"width" => 30,
				'filter'=>NULL,
				"options" => '',

				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );
					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'deleteProductParam!' , array('id_product_param'=>$row->id_product_param))
						)->addClass( 'deleteIcon ajax' )
					);

					return $el;
				})
			);
			$this->addComponent( $grid, $name );
			break;
		


		case 'pictogram':
			$p = new PictogramControl( $this, $name);
			return $p;
			break;
			
		
		case 'keyword':
			return new KeywordControl();
			break;
		
		case 'ajaxtest':
			return new AjaxTextControl();
			break;
		
	    default:
			return parent::createComponent($name);
		break;
	}
    }




    /*
     * Pridat produkt - depricated
     */

    function add_old(NFORM $form) {
		$values = $form->getValues();
		
		$arr = Tools::getValuesForTable('product', $values);

		if(isset($values['id_product_supplier'])){
			$arr['id_product_supplier'] = $values['id_product_supplier'];
		}

		
		$id_product = ProductModel::add( $arr );

		$product_lang_row = Tools::getCollum('product_lang');



		$langs = Setting::getLangs();

		foreach ($langs as $l) {
			$lang_val = array();
			foreach ($values as $k => $v) {
			if (strpos($k, CategoryFormControl::$_separator . $l['iso'])) {
				list($name) = explode(CategoryFormControl::$_separator . $l['iso'], $k);
				
				//overi ci je z danej tabulky
				if(in_array($name, $product_lang_row)){
					$lang_val[$name] = $v;

					 //pridanie linky rewrite
					if($name == 'link_rewrite' AND $v == ''){
						$lang_val[$name] = NStrings::webalize( $values[ 'name'.CategoryFormControl::$_separator.$l['iso']] );
					}
				}
			}
			}

			$lang_val+=array('id_product' => $id_product, 'id_lang' => $l['id_lang']);
			
			ProductModel::addProductLang($lang_val);
		}


		//pridaj param
		/*
		 * Iba ak je product bez parametrov
		 */
		$values['id_product'] = $id_product;
//		$val_product_param = Tools::getValuesForTable('product_param', $values);		
//		ProductModel::addProductParamValue($val_product_param);

		
		ProductModel::addProductToCategory($values['id_categories'], $id_product);

		// prepisanie file_node na novy id_category
		dibi::query("UPDATE [file_node] SET id_module = %i",$id_product,"WHERE id_module = 999998 AND type_module = 'product'");

		//prepisanie pictogram
		PictogramModel::addNullToProduct($id_product);

		
		ProductModel::saveProductAlternative($id_product, $values['product_alternative']);

		
		//prepisanie product param
		if( $id_product!=$this->getId())
			dibi::query("UPDATE [product_param] SET id_product = %i",$id_product,"WHERE id_product IS NULL");

		$this->flashMessage( _('Produkt bol prodaný') );

		$s = NEnvironment::getSession("Admin_Eshop");
		$back_url = $s['back_url'];
		if( $back_url !='' ){
			$this->redirectUrl($back_url);
		}else {
			$this->redirect('Eshop:default');
		}

    }


	
	function handleSaveTemplateGroup( $id, $id_product_template_group){
		
		ProductModel::save( array('id_product_template_group'=>$id_product_template_group) , $id, $this->id_lang);
		
		if($this->isAjax()){			
			$this->invalidateControl('productparams');
		}else{
			$this->redirect('this');
		}
	}

    /*
     * UPRAVIT PRODUCT
     */

    function save(NFORM $form) {
		$values = $form->getValues();
		
		$langs = Setting::getLangs();
		$id_product = $values['id_product'];

		$table_product_cols = Tools::getCollum('product');
		$table_product_lang_cols = Tools::getCollum('product_lang');

		//ulozenie jazuka
		foreach ($langs as $l) {
			$save_val = array();
			foreach ($table_product_lang_cols as $k => $c) {
			if (isset($values[$c . CategoryFormControl::$_separator . $l['iso']]))
				$save_val[$c] = $values[$c . CategoryFormControl::$_separator . $l['iso']];
			}

			//pridanie linky rewrite
			if($save_val['link_rewrite'] == ''){
				$save_val['link_rewrite'] = NStrings::webalize( $values[ 'name'.CategoryFormControl::$_separator.$l['iso']] );
			}else{
				$save_val['link_rewrite'] = NStrings::webalize($save_val['link_rewrite']);
			}

			ProductModel::save($save_val, $id_product, $l['id_lang']);
		}


		//ulozenie zakladnych hodnot
		$save_val = array();
		foreach ($table_product_cols as $p) {

			if (isset($values[$p])){
			$save_val[$p] = $values[$p];
			}
		}

		ProductModel::save($save_val, $id_product, $l['id_lang']);
		
		
		
		//ulozit param
		/*
		 * Iba ak je product bez parametrov
		 */
		$val_product_param = Tools::getValuesForTable('product_param', $values);
		//prvy parameter 
		$id_product_param = dibi::fetchSingle("SELECT id_product_param FROM [product_param] WHERE id_product = %i",$id_product,"ORDER BY sequence");
		ProductModel::setProductParamValue($val_product_param, $id_product_param);

		ProductModel::saveProductAlternative($id_product, $values['product_alternative']);

		dibi::begin();
		ProductModel::deleteProductFromCategories($id_product);
		ProductModel::addProductToCategory($values['id_categories'], $id_product);
		dibi::commit();


		ProductModel::invalidateCache();
		
		$this->flashMessage(_('Produkt bol uložený'));

		
		if($form['btn_save']->isSubmittedBy()){
			$s = NEnvironment::getSession("Admin_Eshop");
			$back_url = $s['back_url'];
			if( $back_url !='' ){
				$this->redirectUrl($back_url);
			}else {
				$this->redirect('Eshop:default');
			}
		}else{
			$this->redirect('this');
		}

    }


   
}