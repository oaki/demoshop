<?php

/**
 * Description of Front_ArticlePresenter
 *
 * @author oaki
 */
class Front_ArticlePresenter extends Front_BasePresenter {

	/** @persistent */
	public $id;

	/** @persistent */
	public $id_menu_item;

	public function actionDefault($id, $id_menu_item) {
		
		
		
		$this->template->article = $this->getService('Article')->get($id);
		
		/*
		 * META INFO
		 */
		$this['header']->addTitle( $this->template->article['meta_title'] );
		$this['header']->setDescription( $this->template->article['meta_description'] );
		$this['header']->addKeywords( $this->template->article['meta_keywords'] );
		
	}

	public function renderDefault($id, $id_menu_item) {
		$this->template->id_menu_item = dibi::fetchSingle("SELECT id_menu_item FROM [node] WHERE id_node = %i",$id);
	}
	
	
	protected function createComponent($name){

		switch($name){			
			case 'attachment':
				return new AttachmentControl;
				break;
			
			default:
				return parent::createComponent($name);
				break;
		}
	}

}