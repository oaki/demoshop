<?php

class ProductWeightModel extends NObject{

	static function getFluent(){
		return dibi::select('*')->from('product_weight');
	}

	static function add($values){
		dibi::query("INSERT INTO [product_weight]",$values);
	}

	static function edit($values, $id_product_weight){
		dibi::query("UPDATE [product_weight] SET",$values,"WHERE id_product_weight = %i",$id_product_weight);
	}

	static function delete($id_product_weight){
		dibi::query("DELETE FROM [product_weight] WHERE id_product_weight = %i",$id_product_weight);
	}

	static function getPrice($weight){
		return dibi::fetchSingle("SELECT weight_price FROM [product_weight] WHERE weight_to <= %s",$weight,"ORDER BY weight_to DESC LIMIT 1");
	}


	
}