<?php

class PaymentModel extends BaseModel{
	
	private $context;
	
	
	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache(), NEnvironment::getContext() );
	}

	public function __construct(DibiConnection $connection, NCache $cache, $context ){
		$this->connection = $connection;
		$this->cache = $cache;
		$this->context = $context;
		$this->table = 'payment';
		
	}
	
	
	function fetchAll(){
		return $this->getFluent()->orderBy('sequence')->fetchAll();
	}
	
	function fetchPairs($collum){
		return $this->getFluent()->orderBy('sequence')->fetchPairs($this->getTableIdName(), $collum);
	}
	
	function fetch($id){
		return $this->getFluent()->where(array($this->getTableIdName()=>$id))->fetch();
	}
	
	function isIdExist($id){
		return $this->getFluent()->removeClause('select')->select('1')->where($this->getTableIdName().' = %i',$id)->fetchSingle();
	}
	
	function getDefault(){
		return $this->getFluent()->removeClause('select')->select( $this->getTableIdName() )->where('[default]= 1')->fetchSingle();
	}
	
	//cena aj s dph aj bez dph
	function getDeliveryWithPrice($id){
		
		$list = $this->fetch($id);
		
		
		$payment_vat = $this->context->parameters['PAYMENT_TAX'];
		
		$list['price_array'] = array(
				'price'=>$list['price'],
				'tax_price'=>$list['price']/100*$payment_vat,
				'price_with_tax'=>$list['price'] + ($list['price']/100*$payment_vat),
				'tax'=>$payment_vat
			);
		
		return $list;
	}
	
}