<?php
class Front_EshopPresenter extends Front_BasePresenter{

	/** @persistent */
	public $id;
	
	/** spiatocna linka */
	public $back_url;

	/** @persistent */
	public $order_by;


	function beforeRender(){
		
		parent::beforeRender();
		
		$this->template->id_category = $this->id;
		
		$this->template->category_parents = CategoryModel::getParents($this->template->id_category, $this->id_lang);
		
		
		//ak je iba jeden parent zobraz kategorie, inak zobraz produkty
		if(count($this->template->category_parents) == 1){
			$id_parent = current($this->template->category_parents);
			
			$category_list = CategoryModel::getFluent('id_category')->where('id_parent = %i',$id_parent)->fetchAll();
			
			$this->template->categories = array();
			
			foreach($category_list as $l){
				$_tmp = CategoryModel::get($l->id_category, $this->id_lang);
				
				$_tmp['product_count'] = dibi::fetchSingle("SELECT COUNT(id_product) FROM [product] JOIN [category_product] USING(id_product) WHERE id_category = %i",$l->id_category);
				$this->template->categories[] = $_tmp;
			}
			
			$this->template->product_count = dibi::fetchSingle("SELECT COUNT(id_product) FROM [product] JOIN [category_product] USING(id_product) WHERE id_category = %i",$this->id);
		}else{
			
	
		
	
			$list = dibi::select('id_product')
				->from('product')
					->join('category_product')
					->using('(id_product)')
					->join('product_param')
					->using('(id_product)')
				->where('id_category = ', $this->id,'AND product.active = 1');
				
			
			/*
			 * Filter
			 */
			$orderSession = $this['quickFilter']->getSession();
//			dde($orderSession['order']);
//			$orderSession['order'] = 'price';
			if($orderSession['order']){
				$list->orderBy($orderSession['order']);
			}else{
				$order_array = $this['quickFilter']->getOrderFilterArray();
				$list->orderBy(key($order_array));
			}
			
			$list->groupBy('id_product');
//			dump($order);
//		print_r($list);
			$count_list = clone $list;
//			$count = $count_list->removeClause('select')->select('COUNT(id_product)')->fetchSingle();
			$count = count($count_list);

			$vp = new VisualPaginator($this, 'paginator');

			$paginator = $vp->getPaginator();
			
			$numOnPageSession = $this['quickFilter']->getSession();
			if($numOnPageSession['num']){
				$paginator->itemsPerPage = $numOnPageSession['num'];
			}else{
				$num_on_page_array = $this['quickFilter']->getNumOnPageFilterArray();
				$paginator->itemsPerPage = key($num_on_page_array);
			}
			
			
			$paginator->itemCount = (int)$count;

			$this->template->product_count = $count;
			
			$this->template->products = $list->limit($paginator->offset.','.$paginator->itemsPerPage )->fetchAll( );
//dump($this->template->products);
			$this->template->paginator = $paginator;

			foreach($this->template->products as $k=>$p){
				$this->template->products[$k] = ProductModel::getProductWithParams($p['id_product'], $this->id_lang, $this->user);			
			}
//		};
		
		}
		
		
//		print_r($this->template->products);exit;
		
	}


	function renderDefault(){
		$key = 'category_info_'.$this->template->id_category;
		
		$this->template->category = CategoryModel::getCache($key );
		if(!$this->template->category){
			$this->template->category = CategoryModel::getFluent()->where('id_category = %i', $this->template->id_category)->fetch();
			CategoryModel::setCache($key, $this->template->category);
		}
		
		/*
		 * META INFO
		 */
		$this['header']->addTitle( $this->template->category['meta_title'] );
		$this['header']->setDescription( $this->template->category['meta_description'] );
		$this['header']->addKeywords( $this->template->category['meta_keywords'] );

	}




	function  createComponent($name) {
	 switch ($name) {
	  
		 
	  default:
		  return parent::createComponent($name);
	   break;
	 }
	}
}
