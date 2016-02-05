<?php

/**
 * Description of Admin_WeightPresenter
 *
 * @author oaki
 */
class Admin_WeightPresenter extends Admin_BasePresenter{

	public function renderDefault() {
		$this['header']['js']->addFile('jquery/jquery.collapsibleCheckboxTree.js');
		$this['header']['js']->addFile('jquery/Multiselect/jquery.multiselect.js');
		$this['header']['css']->addFile('../jscripts/jquery/Multiselect/multiselect-dual-list.css');

		$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');
	}

	/*
	 * handles
	 */
	function handleAddEmptyWeight(){
		ProductWeightModel::add( array('id_product_weight'=>'auto') );
		$this->getComponent('weightTabella')->invalidateControl();
	}


	function handleDeleteWeight($id_product_weight){
		ProductWeightModel::delete( $id_product_weight );
		$this->getComponent('weightTabella')->invalidateControl();
	}

	function  createComponentWeightTabella($name) {
			$grid = new Tabella( ProductWeightModel::getFluent()->toDataSource(),
				array(
					'sorting'=>'asc',
					'order'=>'weight_to',
					'id_table'=>'id_product_weight',
					'limit'=>50,
					"onSubmit" => function( $post ) {
//						print_r($post);exit;
						ProductWeightModel::edit($post, $post['id_product_weight']);
					},
					"onDelete" => function( $id ) {
					    ProductWeightModel::delete($id);
					}
				)

			);

			$el = NHtml::el( "div" );
			$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'addEmptyWeight!')
					)->addClass( 'addIcon ajax' )
			);


			//$grid->addColumn($el, '', array('width'=>20,  'filter'=>NULL, "editable" => false ) );

			$grid->addColumn( "Váha do", "weight_to", array( "width" => 50, "editable" => true ) );
			$grid->addColumn( "Cena poštovného", "weight_price", array( "editable" => true ) );
			

			$grid->addColumn($el, "",
				array(
				"width" => 30,
				'filter'=>NULL,
				"options" => '',

				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );
					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'deleteWeight!' , array('id_product_weight'=>$row->id_product_weight))
						)->addClass( 'deleteIcon ajax' )
					);

					return $el;
				})
			);
			$this->addComponent( $grid, $name );


	}
        
}