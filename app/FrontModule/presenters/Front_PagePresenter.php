<?php
class Front_PagePresenter extends Front_BasePresenter{
	
	/** @persistent */
	public $id;
	
	public $back_url;
	
	
	function renderDefault( $id ){
		
		if(!$id)
			throw new NBadRequestException( _('Stranka neexistuje') );
		
		$this->template->id_menu_item = $id;
		
		$this->template->page = $this->getService('Page')->findOne( array('id_menu_item'=>$id) );

		/* ak je to homepage */		
		if($this->template->page['home'] == 1)
			$this->redirect (':Front:Homepage:default');
		/*
		 * META INFO
		 */
		$this['header']->addTitle( $this->template->page['meta_title'] );
		$this['header']->setDescription( $this->template->page['meta_description'] );
//		$this['header']->addKeywords( $this->template->page['meta_keywords'] );
		
		$node = $this->getService('Node');
		
		$query = $node->getAll( $this->template->page['id_menu_item'] );
		$query_count = clone $query;
		
		$vp = new VisualPaginator($this, 'paginator');
		$paginator = $vp->getPaginator();
		$paginator->itemsPerPage = 4;
		$paginator->itemCount = $query_count->select(false)->select('COUNT(id_node)')->fetchSingle();
		
		$this->template->node_list = $query->limit($paginator->itemsPerPage )->offset( $paginator->offset )->fetchAll();
	}
}