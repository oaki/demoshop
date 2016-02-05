<?php

/**
 * Description of Front_SearchingPresenter
 *
 * @author oaki
 */
class Front_SearchPresenter extends Front_BasePresenter {

	/** @persistent */
	public $q;

	
	public $sale, $news, $top;
	
	static function getQuery(){		
		
		return dibi::select('id_product')
				->from('product')					
					->join('product_lang')					
						->using('(id_product)')
					->join('product_param')					
						->using('(id_product)')
					->join('category_product')					
						->using('(id_product)')
					->join('category')
						->using('(id_category)')
				->where("product.active = 1 AND category.active = 1" );
	}
	
	function actionDefault( $q = NULL, $sale = 0, $news = 0, $top = 0 ){
		
		$this->q = str_replace('%', '', $q);
		$this->sale = $sale;
		$this->news = $news;
		$this->top = $top;
		
		$this->template->showForm = true;
		
		$list = self::getQuery();		
		
		if( $this->sale == 1){
			$list->where('product.sale = 1');
			$this->template->name = 'Akciový tovar';
			$this->template->showForm = false;
		}
		

		if( $this->news == 1){
			$list->where('product.news = 1');
			$this->template->name = 'Novinky';
			$this->template->showForm = false;
		}
		
		if( $this->top == 1){
			$list->where('product.our_tip = 1');
			$this->template->name = 'TOP Produkty';
			$this->template->showForm = false;
		}
		
		
		if( $this->q != ''){
			$list->where('
					(
					product_lang.name LIKE %s', '%'.$this->q.'%','
					OR product_lang.description LIKE %s', '%'.$this->q.'%','
					OR product_param.code LIKE %s',$this->q,'
					)
					');
			
			$this->template->name = 'Vyhľadávanie slova: '.$this->q;
		}
		
		$list->groupBy('id_product');
		/*
		 * breadcrumb
		 */
		if(!isset($this->template->name))
				$this->template->name = 'Vyhľadávanie';
		$this->template->breadcrumb = array();
		$this->template->breadcrumb[] = array('link'=>$this->link('this'),'name'=>$this->template->name);
		
//		$count_list = clone $list;
		$count = $this->template->count = count($list);

		$vp = new VisualPaginator($this, 'paginator');

	    $paginator = $vp->getPaginator();
	    $paginator->itemsPerPage = 70;
	    $paginator->itemCount = (int)$count;

	    $this->template->products = $list->limit($paginator->offset.','.$paginator->itemsPerPage )->fetchAll( );

	    $this->template->paginator = $paginator;

		foreach($this->template->products as $k=>$p){
			$this->template->products[$k] = ProductModel::getProductWithParams($p['id_product'], $this->id_lang, $this->user);			
		}
	}


	public function renderDefault($q) {
		
	}
	
	public function actionSuggestion($term) {
		$query = self::getQuery();
		$query->removeClause('select')->select('product_lang.name')
			->where('product_lang.name LIKE %s', '%'.$term.'%')
			->groupBy('name')
			->limit(10);
		$list = $query->fetchAll();
		$r = array();
		foreach($list as $l){
			$r[] = $l['name'];
		}
		$this->sendResponse(new NJsonResponse( $r ) );
		$this->terminate();
	}
	
	
	
	
	function  createComponent($name) {
		switch ($name) {		
			
			
			
			default:
				return parent::createComponent($name);
			break;
		}
	}
	

}