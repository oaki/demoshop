<?php

class ProductTemplateGroupModel extends CacheModel{
	private $connection;



	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache( get_class() ) );
	}

	public function __construct(DibiConnection $connection, NCache $cache ){

		$this->connection = $connection;
		$this->cache = $cache;
		
	}

	public function getConnection(){
			return $this->connection;
	}
	
	
	public function fetchAll(){
		return $this->connection->fetchAll("SELECT * FROM [product_template_group] ORDER BY name");
	}
	
	public function fetchPairs(){
		return $this->connection->query("SELECT * FROM [product_template_group] ORDER BY name")->fetchPairs('id_product_template_group','name');
	}

	public function fetchAssocAllParam($id_product_template_group, $without_checked = false){
		return $this->connection->query("
			SELECT 
				* 
			FROM 
				[product_template_group] JOIN [product_template_group_param] USING(id_product_template_group)
			WHERE 
				id_product_template_group = %i",$id_product_template_group,"
				%if",$without_checked,"AND checked = 1")
			->fetchAssoc('row_name');
	}
	
	public function fetch($id){
		return $this->connection->fetch("SELECT * FROM [product_template_group] WHERE id_product_template_group = %i",$id);
	}
	
	public function getProductParamRows(){
		$rows = Tools::getCollum('product_param');
		
		//vymazat zakazane polia - aby tam neslo id_product_param a podobne
		$disable_rows = array(
			"id_product_param",
			"id_product",	
			'sequence',
			'price'
		);
		
		foreach($rows as $k=>$l){
			if(in_array($l, $disable_rows)){
				unset($rows[$k]);
			}
		}
		
		return $rows;
	}
	
	function save($values){
		
//		dump($values);exit;
		//ak je predvolena skupina TRUE, treba zrusit vsetky ostatne a nastavit tuto
		if($values['default'] === TRUE){
			$this->connection->query("UPDATE product_template_group SET [default] = 0");
		}
		
		
		//uloz info o groupe
		$product_template_group_values = array(
			'id_product_template_group'=>$values['id_product_template_group'],
			'name'=>$values['group_name'],
			'allow_change_price'=>$values['allow_change_price'],
			'default'=>(int)$values['default'],
		);
		
		$this->connection->query("UPDATE product_template_group SET",$product_template_group_values,"WHERE id_product_template_group = %i",$product_template_group_values['id_product_template_group']);
		
		$this->addMissingRowToGroupParam($values['id_product_template_group']);
		
		
		$product_template_group_param = $this->connection->fetchAll("SELECT * FROM [product_template_group_param] WHERE id_product_template_group = %i",$values['id_product_template_group']);
		
		
		foreach($product_template_group_param as $k=>$l){
			// ak by sa stalo, ze uz predtym tam boli nejake parametre vyplnene a teraz sa zmenili, 
			if(isset($values[$l['row_name']]))
				$this->connection->query("UPDATE product_template_group_param SET",array('checked'=>(int)$values[$l['row_name']]),"WHERE id_product_template_group_param = %i",$l['id_product_template_group_param']);
		}
		
		
	}
	
	function addMissingRowToGroupParam($id_product_template_group){
		$product_param_row = $this->getProductParamRows();
		
		
		//over ci netreba nejake parametre vymazat
		$actual = $this->fetchAssocAllParam($id_product_template_group);
		
		foreach($product_param_row as $k=>$l){
			if(isset($actual[$l])){
				unset($actual[$l]);
			}
		}
		//ak zostanu nejake, vymaze ich
		if(count($actual)>0){
			foreach($actual as $l){
				$this->connection->query("DELETE FROM product_template_group_param WHERE id_product_template_group_param = %i",$l['id_product_template_group_param']);
			}
		}
		
		//zisti ci existuje group
		if( $this->connection->fetchSingle("SELECT 1 FROM [product_template_group] WHERE id_product_template_group = %i",$id_product_template_group)){
			foreach($product_param_row as $row_name){

				$is = $this->connection->fetchSingle("SELECT 1 FROM product_template_group_param WHERE id_product_template_group = %i",$id_product_template_group,"AND row_name = %s", $row_name);
				if(!$is){
					$arr = array(
						'id_product_template_group'=>$id_product_template_group,
						'row_name'=>$row_name
					);
					$this->connection->query("INSERT INTO product_template_group_param ",$arr);
				}
			}
		};
	}
	
	function insert($values){
		
		$arr = array(
			'name'=>$values['group_name'],
			'allow_change_price'=>$values['allow_change_price'],
		);
		$this->connection->insert('product_template_group', $arr)->execute();
		
		return $this->connection->insertId();
	}
	
	function delete($id){
		$this->connection->query("DELETE FROM [product_template_group] WHERE id_product_template_group = %i",$id);
	}
	
	function getIdDefaultTemplate(){
		return $this->connection->fetchSingle("SELECT id_product_template_group FROM [product_template_group] WHERE [default] = 1");
	}

}