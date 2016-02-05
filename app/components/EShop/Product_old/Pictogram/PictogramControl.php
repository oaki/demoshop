<?php

/**
 * Description of PictogramControl
 *
 * @author oaki
 */
class PictogramControl extends BaseControl {

	public $id_product;
	
	function  __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
	}

	/*
	 * handles
	 */
	function handleAddPictogram(){
		$items = $this->getPresenter()->getParam('items');
		$id_product = $this->getPresenter()->getParam('id');
		
		$items = explode(',',$items);
		PictogramModel::deleteAll($id_product);
		PictogramModel::addMore($id_product, $items);

		
		$this->invalidateControl();
	}

	public function render() {
		$id_product = $this->getPresenter()->getParam('id');

		$this->template->setFile( dirname(__FILE__).'/pictogram.phtml' );
		$this->template->all_files = PictogramModel::getAllFiles();

		$this->template->files = PictogramModel::get( $id_product );
		
		$this->template->id_product = $id_product;

		$this->template->render();
	}

}