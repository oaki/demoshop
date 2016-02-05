<?php

class ProductNewsSaleAvaiableTableControl extends BaseControl{
	private $id_product = NULL;
	private $id_category = NULL;
	private $id_lang = NULL;
	private $user = NULL;
	
	function setIdProduct($id_product){
		$this->id_product = $id_product;
	}
	function setIdCategory($id_category){
		$this->id_category = $id_category;
	}
	function setIdLang($id_lang){
		$this->id_lang = $id_lang;
	}
	function setUser($user){
		$this->user = $user;
	}
	
	function render(){
		$this->template->setFile( dirname(__FILE__).'/default.latte');
		
		if($this->id_product)
			$this->template->product_alternative = ProductModel::getProductAlternativeValues($this->id_product, $this->id_lang, $this->user, 'RAND()', 4 );
		
		$this->template->product_news = ProductModel::getFluent( $this->id_lang )
				->removeClause('select')
				->select('id_product')
				->where('%if',$this->id_category,'id_category = %i',$this->id_category,'AND %end news = 1')
				->orderBy('RAND()')
				->limit('0,6')
				->fetchAll();
		foreach($this->template->product_news as $k=>$product){
			$this->template->product_news[$k] = ProductModel::getProductWithParams($product->id_product, $this->id_lang, $this->user);
		};
		
		
		$this->template->product_sale = ProductModel::getFluent( $this->id_lang )
				->removeClause('select')
				->select('id_product')
				->where('%if',$this->id_category,'id_category = %i',$this->id_category,'AND %end sale = 1')
				->orderBy('RAND()')
				->limit('0,6')
				->fetchAll();
		foreach($this->template->product_sale as $k=>$product){
			$this->template->product_sale[$k] = ProductModel::getProductWithParams($product->id_product, $this->id_lang, $this->user);
		};
		
		
		$this->template->product_our_tip = ProductModel::getFluent( $this->id_lang )
				->removeClause('select')
				->select('id_product')
				->where('%if',$this->id_category,'id_category = %i',$this->id_category,'AND %end our_tip = 1')
				->orderBy('RAND()')
				->limit('0,6')
				->fetchAll();
		
		foreach($this->template->product_our_tip as $k=>$product){
			$this->template->product_our_tip[$k] = ProductModel::getProductWithParams($product->id_product, $this->id_lang, $this->user);
		};
		
		$this->template->render();
	}
}