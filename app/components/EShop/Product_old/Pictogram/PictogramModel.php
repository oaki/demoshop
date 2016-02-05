<?php

class PictogramModel extends NObject{

	static function getAllFiles(){
		return FilesNode::getAllFiles('pictogram', 1);
	}

	static function get($id_product){
		return dibi::query("SELECT * FROM [product_pictogram] JOIN [file] USING(id_file) WHERE 
			%if",$id_product==NULL,"id_product IS NULL %else id_product = %i",$id_product
			)->fetchAssoc('id_file');
	}

	static function deleteAll($id_product){
		return dibi::query("DELETE FROM [product_pictogram] WHERE id_product = %i",$id_product);
	}

	static function addMore($id_product, $ids_file){
		foreach( $ids_file as $id_file){
			self::add($id_product, $id_file);
		}
	}

	static function add($id_product, $id_file){
		$arr = array('id_product'=>$id_product, 'id_file'=>$id_file);
		return dibi::query("INSERT INTO [product_pictogram]", $arr);
	}

	static function addNullToProduct($id_product){
		return dibi::query("UPDATE [product_pictogram] SET id_product = %i", $id_product,"WHERE id_product IS NULL");
	}
	
}