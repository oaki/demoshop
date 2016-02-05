<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Admin_JsTreePresenter
 *
 * @author oaki
 */
class Admin_EShopPresenter extends Admin_BasePresenter {

    /** @persistent */
    public $id_category;

  
    /*
     * HANDLE
     */
    function handleDeleteProduct($id){
		ProductModel::delete($id);

		$this->flashMessage( _('Produkt bol zmazaný') );
		if($this->isAjax()){
			$this->invalidateControl('productTabella');
		}else{
			$this->redirect('this');
		}
		
    }

	
	function handleSaveSequence($data){
		
		//zoberieme normalne poradie
		$before_list = ProductModel::getFluent()->orderBy('product_sequence');
		if($this->getParam('id_category')!=''){
			$before_list->where('id_category = %i',$this->getParam('id_category'));
		}
		$before_list = $before_list->fetchAll();
		
		foreach($before_list as $k=>$l){
			if(isset($data[$k])){
				
				list( $tmp,$id_product) = explode("index_",$data[$k]);
				ProductModel::save(array('product_sequence'=>$l['product_sequence']), $id_product, 1);
//				echo dibi::$sql;
			}
			
		}
//		print_r($data);exit;
//		foreach( $data as $k=>$s){
//			list( $tmp,$id_product) = explode("index_",$s);
//			ProductModel::save(array('product_sequence'=>$k), $id_product, 1);
//		}
		exit;
    }
	

	function handleChangePrice(NFORM $form){
		$values = $form->getValues();

		$id_category = $this->getParam('id_category');
		$coeficient = $values['coeficient'];

		if(!is_numeric($id_category)){
			$form->addError('Nie je vybraná kategória');
			return $form;
		}
		
		if($coeficient == 0){
			$form->addError('Koeficient nesmie byť nula.');
			return $form;
		}

		$list = dibi::fetchAll("
				SELECT
				product_param.id_product_param,
				product_param.price
				FROM
					[category_product]
					JOIN [product_param] USING(id_product)
				WHERE
					id_category = %i",$id_category
				);



		foreach($list as $l){
			$arr = array( 'price'=>round($l['price']*$coeficient) );
			dibi::query("UPDATE [product_param] SET ", $arr,"WHERE id_product_param = %i",$l['id_product_param']);
		}

	}





    function renderDefault(){
		$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
		$this['header']['js']->addFile('/tabella/nette.tabella.js');
//		$this['header']['css']->addFile('../jscripts/tabella_v2/maite.tabella.css');
//		$this['header']['js']->addFile('/tabella_v2/maite.tabella.js');
		$session = NEnvironment::getSession("Admin_Eshop");
		$session['back_url'] = $_SERVER['REQUEST_URI'];
//		ProductModel::repairAllProductSequence();
    }

    function renderAddCategory(){
		$files = new FilesNode('category', 999999);
		$this->template->filesModule = $files;

    }

    function renderEditCategory($id_category){
		$files = new FilesNode('category', $id_category);
		$this->template->filesModule = $files;

    }
	
	protected function createComponentTestProductTabella( $name ) {
		$grid = new Maite\Tabella( array(
				'context' => $this->context,
				'source'  => dibi::select('*')->from('lang_translate'),
				'id_table'=>'id_lang',
				'limit'=>10
			)
		);
		$grid->addColumn('Nazov', 'translate');
		
		$grid->addColumn('+', Tabella::ADD, array(
				'type' => Tabella::DELETE
			));
		
		
		return $grid;
	}

    protected function createComponentProductTabella( $name ) {
//		ProductModel::repairAllProductSequence();
		$id_category = $this->getParam('id_category');

		$fluent = ProductModel::getFluent()->orderBy('product_sequence');

		if($id_category)
			$fluent->where('id_category = %i',$id_category);
		else {
			$fluent->groupBy('id_product');
		}

		$datasource = $fluent->toDatasource();

		$grid = new Tabella( $datasource,
			array(
				'sorting'=>'product_sequence',
				'order'=>'product_sequence',
				'limit'=>400,
				"onSuccess" => function( $post ) {
					LogModel::save( $post, $post['id_log'] );
				}
			)
		);

		$grid->addColumn( "Názov", "name", array( "width" => 300, "editable" => false ) );
		$grid->addColumn( "Cena", "min_price", array( "width" => 100, "editable" => false) );
		
		$_active = array(''=>'', 0=>'Zakázaný', 1=>'Povolený');



		$grid->addColumn("Akcia", "sale",
			array(
				"width" => 100,
				'type'=>  Tabella::SELECT,
				"filter" => array(''=>'',0=>'nie', 1=>'áno'),
				"options" => NULL,
				
				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );
					$pom = array(''=>'',0=>'nie', 1=>'áno');
					return $el->add($pom[$row['sale']]);
				}
				)
			);

		$grid->addColumn("Najpredá.", "our_tip",
			array(
				"width" => 100,
				'type'=>  Tabella::SELECT,
				"filter" => array(''=>'',0=>'nie', 1=>'áno'),
				"options" => NULL,
				
				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );
					$pom = array(''=>'',0=>'nie', 1=>'áno');
					return $el->add($pom[$row['our_tip']]);
				}
				)
			);

		$grid->addColumn("_", "active",
			array(
				"width" => 100,
				'type'=>  Tabella::SELECT,
				"filter" => $_active,
				"options" => '',
				
				"editable" => false,
				"renderer" => function( $row ) use ($_active) {
					$el = NHtml::el( "td" );
//					print_r($row);exit;
					/*
					 * link na zmazanie produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'deleteProduct!' , array('id'=>$row->id_product))
					)->addClass( 'deleteIcon ajax' )
						->title('Naozaj chcete zmazať položku?')
					);



					/*
					 * link na editaciu produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'Product:edit' , array('id'=>$row->id_product))
					)->addClass( 'editIcon' )
					);

					
					/*
					 * posuvanie - ak sa spusti posubanie, treba vypnut zoradovanie !!! order=>false
					 */

//					$el->add(
//					NHtml::el( 'a' )->href('#')->addClass( 'moveIcon' )
//							->addId( 'index_'.$row['id_product'] )
//					);
					
					/*
					 * ikona aktivan polozka, neaktivan polozka
					 */
					$span = NHtml::el('span');

					if($row->active){
					$span->addClass('activeIcon active');
					}else{
					$span->addClass('unactiveIcon active ');
					}
					$el->add($span);

					return $el;
				}
				)
			);


	//
	//	$grid->addColumn( "", "id_product",
	//	    array( "width" => 55,
	//		'filter'=>null,
	//		'order'=>false,
	//		"renderer" => function( $row ) {
	//
	//		return NHtml::el( "td" )->add(
	//			NHtml::el( 'a' )->href(
	//				NEnvironment::getApplication()->getPresenter()->link( 'Product:edit' , array('id'=>$row->id_product))
	//			)
	//			->addClass( 'editIcon' )
	//		);
	//
	//	    })
	//	);

		$this->addComponent( $grid, $name );
    }


	
    protected function createComponent($name) {
	
		switch($name){

			case 'JsTree':

				$tree = new JsTree();
				$tree->keyColumn = 'id_category';
				$tree->parentColumn = 'id_parent';
				$tree->table = "category";  // jméno tabulky, s kterou bude pracovat
				$tree->titleColumn = "name"; // jméno sloupce pro titulek
				$tree->orderColumn = "sequence"; // jméno sloupce pro pořadí
				$tree->numberingFrom = 0;
		//		$tree->parentColumn = "parent";
				//$tree->where = array(array("`web`=%i", WEB)); // je možné navěsit podmínky
				//$tree->defaultValues = array("web"=>WEB);
				$tree->enableContextmenu = false;
				$tree->enableDragDrop = true;
				$tree->enableDragDropOrder = false;
				$tree->showRoot = false;
				$tree->enableCheckbox = false; // zobrazení checkboxů
				$tree->checkboxColumn = "checked"; // sloupec, který ovlivňuje stav checkboxu
				$tree->onAfterUpdate[] = callback($this, "updateUrl");
				$tree->onAfterCreate[] = callback($this, "updateUrl");
				$tree->onClick[] = callback($this, "handleClick");
				$tree->openAll = true;

				return $tree;

			break;


			case 'changePrice':
				$f = new MyForm;
				$f->addText('coeficient',
						NHtml::el('p')->add(
								'Upraviť ceny v danej kategórii podľa koeficientu.')
						->add(
							NHtml::el('div')->add('Napr. ak chcete zvýšiť cenu o 10 percent, zadáte koeficient 1.10, ak o 25 percent 1.25. Ak chcete ceny znížiť o 10 percent, zadáte 0.90, ak o 15 percent, zadáte 0.85. Želanú kategóriu si zvolíte v ľavom v menu. Cena bude zaokrúhlená!')->addClass('silver')
						)
						)
						->addRule(NForm::FILLED, 'Koeficient musí byť vyplnený');

				$f->addSubmit('btn_percent',"Prepočitať");
				$f->onSuccess[] = array($this, 'handleChangePrice');
				return $f;
			break;
		
		   case 'addCategoryForm':

			   $c = new CategoryFormControl();
			   $c->mode = 'add';


			   return $c;
			   break;

		   case 'editCategoryForm':

			   $c = new CategoryFormControl();
			   $c->mode = 'edit';
			   return $c;
			   break;

		  case 'MyTree':

			   $c = new MyTreeControl();
			   $c->invalidateControl();
			   return $c;
			   break;


			default :
			   return parent::createComponent($name);;
			   break;

		}

    }
    

}
