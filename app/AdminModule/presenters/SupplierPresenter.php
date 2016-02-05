<?php

class Admin_SupplierPresenter extends Admin_BasePresenter{
    function renderDefault(){
	$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	$this['header']['js']->addFile('/tabella/nette.tabella.js');

    }

    /*
     * HANDLE
     */
    function handleDelete($id){
	SupplierModel::delete($id);

	$this->flashMessage( _('Dodávateľ bol zmazaný') );
	$this->redirect('this');
    }

    function handleAddSupplier(NForm $form){
	$values = $form->getValues();
	SupplierModel::add($values);
//	$this->invalidateControl('addSupplierForm');
	$this->flashMessage( _('Dodávateľ bol pridaný.') );
	$this->redirect('this');
    }


    protected function  createComponentAddSupplierForm($name){
	 $f = new NAppForm($this, $name);

	 $f->addText('name', _('Názov dodávateľa') )
		 ->addRule(NForm::FILLED, _('Názov dodávateľa musí byť vyplnený') );
	 $f->addSubmit('btn_add_supplier', _('Pridať') );
	
	 $f->onSuccess[] = array($this, 'handleAddSupplier');
    }

    protected function createComponentSupplierTabella( $name ) {


	$datasource = SupplierModel::getFluent()->toDatasource()->orderBy('name');

	$grid = new Tabella( $datasource,
		array(	'id_table' =>'id_product_supplier',
			'sorting'=>'id_product_supplier',
			'order'=>'id_product_supplier',
			'truncate'=>0,
			"onSubmit" => function( $post ) {
	    
			    SupplierModel::save( $post, $post['id_product_supplier'] );
			}
		)
	);

	$grid->addColumn( "Názov", "name", array( "width" => 300, "editable" => true ) );
	$grid->addColumn( "Počet produktov", "product_count", array( "width" => 80, "editable" => false ) );
	
	$grid->addColumn("", "action",
		array(
		    "width" => 100,
		    'type'=>  Tabella::SELECT,
		    "filter" => false,
		    "options" => false,

		    "editable" => true,
		    "renderer" => function( $row ){
			    $el = NHtml::el( "td" );

			    /*
			     * link na zmazanie produktu
			     */

			    $el->add(
				NHtml::el( 'a' )->href(
				    NEnvironment::getApplication()->getPresenter()->link( 'delete!' , array('id'=>$row->id_product_supplier))
				)->addClass( 'deleteIcon' )
			    );



			    /*
			     * link na editaciu produktu
			     */

//			    $el->add(
//				NHtml::el( 'a' )->href(
//				    NEnvironment::getApplication()->getPresenter()->link( 'Product:edit' , array('id'=>$row->id_product_supplier))
//				)->addClass( 'editIcon' )
//			    );

			    /*
			     * ikona aktivan polozka, neaktivan polozka
			     */
			   
			    return $el;
			}
		    )
		);

	$this->addComponent( $grid, $name );
    }
    
}