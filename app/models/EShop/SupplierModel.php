<?php
class SupplierModel extends NObject{

    static function getFluent(){
	return dibi::select('*, COUNT(id_product) as product_count')->from('product_supplier')
		->leftJoin('product')
		    ->using('(id_product_supplier)')
		->groupBy('id_product_supplier');
    }
    
    static function save($values, $id_product_supplier){
	dibi::query("UPDATE [product_supplier] SET ", $values,"WHERE id_product_supplier = %i",$id_product_supplier);
    }

    static function delete($id_product_supplier){
	dibi::query("DELETE FROM [product_supplier] WHERE id_product_supplier = %i",$id_product_supplier);
    }

    static function add($values){
	dibi::query("INSERT INTO [product_supplier]", $values);
    }
}