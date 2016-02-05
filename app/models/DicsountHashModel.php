<?php
/* 
 * Model operujúci nad tabuľkou discount_hash
 */

class DiscountHashModel extends NObject{
	
	/*
	 * Vloženie nového kódu pre zľavu
	 */
	static function insert ($values) {
		dibi::query("INSERT INTO [discount_hash]",$values);
	}
	
	/*
	 * Vloženie nového kódu pre zľavu
	 */
	static function update ($hash,$values) {
		dibi::query("UPDATE [discount_hash] SET",$values,"WHERE hash = %s",$hash);
	}
	
	/*
	 * Zistenie existencie hashu
	 */
	static function exist ($hash) {
		return dibi::query("SELECT * FROM [discount_hash] WHERE hash = %s",$hash)->fetch();
	}
	
	/*
	 * Zistenie či je kód použiteľný
	 * TRUE ak je možné kód použiť
	 * FALSE ak bol kód už použitý alebo kód neexistuje
	 */
	static function usable ($hash) {
		return dibi::query("SELECT * FROM [discount_hash] WHERE hash = %s",$hash," AND id_order = 0")->fetch();
	}
	
	/*
	 * Metóda pre vrátenie nového kódu pre zľavu
	 * Generuje kódy dovtedy, kým nie je vygenerovaný autentický kód
	 * Kód sa vloží do DB a vráti
	 * 
	 * hash 		- generovaný kód
	 * id_order 	- id objednávky ktorá využije kód
	 * parent_order - id objednávky pri ktorej sa kód generuje
	 */
	static function getNew ($parent_order) {
		do {
			$newHash = Tools::random(10);
		} while (self::exist($newHash));
		self::insert(array("hash"=>$newHash,"id_order"=>0,"parent_order"=>$parent_order));
		return $newHash;;
	}
	
	/*
	 * Metóda pre použitie kódu
	 */
	static function usehash ($id_order,$hash) {
		self::update($hash,array("id_order"=>$id_order));
	}
	
	/*
	 * Metóda vracajúca či bola k objednávke aktivovaná zľava
	 */
	static function hasDiscount($id_order) {
		return dibi::query("SELECT * FROM [discount_hash] WHERE id_order = %i",$id_order)->fetch();
	}
}
