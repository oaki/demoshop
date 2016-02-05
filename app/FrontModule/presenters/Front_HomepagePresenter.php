<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 Pavol Bincik
 * @package    MyApplication
 */



/**
 * Homepage presenter.
 *
 * @author     Pavol Bincik
 * @package    Quis
 */
final class Front_HomepagePresenter extends Front_BasePresenter
{

	
	public function renderDefault()
	{
		//uvod
		$home = dibi::fetch("SELECT * FROM [menu_item] WHERE home = 1 AND lang = %s", $this->lang);
		if(!$home)
			$home = dibi::fetch("SELECT * FROM [menu_item] WHERE lang = %s", $this->lang,"ORDER BY sequence LIMIT 1");
		
		$this->template->id_menu_item = $home['id_menu_item'];
		
		/*
		 * META INFO
		 */		
		$this['header']->addTitle( $home['meta_title'] );
		$this['header']->setDescription( $home['meta_description'] );
		
		$node = $this->getService('Node');
		
		$query = $node->getAll( $this->template->id_menu_item );
		
		$this->template->node_list = $query->fetchAll();
	
		
		//produkty na uvode
		$list = dibi::select('id_product')
				->from('product')					
				->where('home = 1');
		
		$count_list = clone $list;

		$count = $count_list->removeClause('select')->select('COUNT(id_product)')->fetchSingle();

		$vp = new VisualPaginator($this, 'paginator');

	    $paginator = $vp->getPaginator();
	    $paginator->itemsPerPage = 12;
	    $paginator->itemCount = (int)$count;

	    $this->template->products = $list->limit($paginator->offset.','.$paginator->itemsPerPage )->fetchAll( );

	    $this->template->paginator = $paginator;

		foreach($this->template->products as $k=>$p){
			$this->template->products[$k] = ProductModel::getProductWithParams($p['id_product'], $this->id_lang, $this->user);			
		}	
		
		
		/* widget */
		
		
		

		$this['productNewsSaleAvaiableTable']->setIdLang( $this->id_lang );
		$this['productNewsSaleAvaiableTable']->setUser( $this->user );
		
		
	}

	
	
}
