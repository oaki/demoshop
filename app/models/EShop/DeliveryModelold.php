<?php


class DeliveryModel_old extends CacheModel{
	
	private $connection;
	
	private $context;
	
	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache(), NEnvironment::getContext() );
	}

	public function __construct(DibiConnection $connection, NCache $cache, $context ){
		$this->connection = $connection;
		$this->cache = $cache;
		$this->context = $context;
	}
	
	function getFluent(){
		return $this->connection->select("*")->from('delivery');
	}
	
	function fetchAll(){
		return $this->getFluent()->orderBy('sequence')->fetchAll();
	}
	
	function fetchPairs( $collum ){		
		return $this->getFluent()->orderBy('sequence')->fetchPairs('id_delivery', $collum);
	}
	
	function fetch($id){
		return $this->getFluent()->where('id_delivery = %i',$id)->fetch();
	}
	
		
	//cena aj s dph aj bez dph
	function getDeliveryWithPrice($id){
		
		$list = $this->fetch($id);
		
		
		$delivery_vat = $this->context->parameters['DELIVERY_TAX'];
		
		$list['price_array'] = array(
				'price'=>$list['price'],
				'tax_price'=>$list['price']/100*$delivery_vat,
				'price_with_tax'=>$list['price'] + ($list['price']/100*$delivery_vat),
				'tax'=>$delivery_vat
			);
		
		return $list;
	}
	
	
	function isIdExist($id){
		return $this->getFluent()->removeClause('select')->select('1')->where('id_delivery = %i',$id)->fetchSingle();
	}
	
	function insert($values){
		if(!isset($values['sequence'])){
			$values['sequence'] = $this->getFluent()->removeClause('select')->select('MAX(sequence)+1')->fetchSingle();
		}		
		$this->connection->insert('delivery', $values)->execute();
	}
	
	function update($values, $id){
		$this->connection->update('delivery', $values)->where('id_delivery = %i',$id)->execute();		
	}
	
	function delete($id){
		$this->connection->delete('delivery')->where('id_delivery = %i',$id)->execute();
		
	}
	
	
	function repairSequence(){
		$list = $this->fetchAll();
		$counter = 1;
		foreach($list as $l){
			$this->update( array('sequence'=>++$counter), $l['id_delivery']);
		}
	}
}