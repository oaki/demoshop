<?php


namespace ShoppingCart;

class ProductMapper extends \NObject{
	
	public function loadToItem(Item $item, $data ){
		$item->name = $data['name'];
		$item->price = $data['name'];
	}
}