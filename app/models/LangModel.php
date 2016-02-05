<?php

class Lang extends NObject{

	private static $instance;
	private $data;
	private $cache;
	private $langs;
	private $assoc;

	private function  __construct() {
		$this->cache = NEnvironment::getCache('lang');
		
		if($this->cache['data'])
			$this->data = $this->cache['data'];
		else
			$this->data = $this->cache['data'] = dibi::query("SELECT * FROM [lang]")->fetchAssoc('id_lang');

		
	}

	static function convertIsoToId($iso){
		$all = self::getInstance()->getAll();
		if (isset($all[$iso])){
			return $all[$iso]['id_lang'];
		}else{
			return dibi::fetchSingle("SELECT id_lang FROM [lang] WHERE iso = %s",$iso);
		}
	}

	//pre vlozenie klucu, ktory je dynamicky generovany
	static function insertTranslateKey($key, $id_lang){		
		dibi::query("INSERT INTO [lang_translate]", array('key'=>$key, 'id_lang'=>$id_lang, 'translate'=>$key) );
	}

	static function getTranslate($lang){
		if (isset(self::getInstance()->cache['translate_'.$lang])){
			return self::getInstance()->cache['translate_'.$lang];
		}else{
			$tmp = dibi::query("SELECT * FROM [lang_translate] JOIN [lang] USING(id_lang) WHERE iso = %s", $lang)->fetchPairs("key","translate");

			self::getInstance()->cache['translate_'.$lang] =  $tmp;

			return $tmp;
		}
	}

	public static function invalidateCache(){
		$langs = self::getAll();
		foreach($langs as $l)
			unset(self::getInstance()->cache['translate_'.$l['iso']]);
	}
	

	public static function getInstance() {
		if (self::$instance === NULL) {
			return self::$instance = new self();
		} else {
			return self::$instance;
		}
	}

	static function get($id_lang){
		return self::getInstance()->data[$id_lang];
	}

	static function getAll(){
		
		

		
		if(self::getInstance()->langs)
			return self::getInstance()->langs;
		else{
			if(self::getInstance()->cache['langs'])
				return self::getInstance()->langs = self::getInstance()->cache['langs'];
			else
				return self::getInstance()->langs = self::getInstance()->cache['langs'] = dibi::query("SELECT * FROM [lang] ORDER BY sequence")->fetchAssoc('iso');
		}
	}

	static function getDatasourceGroupByKey(){
		$sql = '';
		$langs = self::getAll();
		foreach($langs as $l){
			$sql.='(SELECT translate FROM [lang_translate] WHERE [main].[key] = [lang_translate].[key] AND id_lang = '.$l['id_lang'].') AS '.$l['iso'].',';
		}


		return dibi::dataSource("
			SELECT
				".$sql."
				[key]
			FROM
				[lang_translate] main
			GROUP BY [key]");
	}

	static function save($key, $values){
		$langs = self::getAll();
		
		foreach($langs as $l){
			if( isset( $values[ $l['iso'] ] ) AND $values[ $l['iso'] ] != '' ){

				//ak zaznam neexistuje vytvor ho
				if( !dibi::fetchSingle("SELECT 1 FROM [lang_translate] WHERE id_lang = %i",$l['id_lang'],"AND [key] = %s",$key)){
					self::insertTranslateKey($key, $l['id_lang']);
				}
				
				dibi::query("
					UPDATE
						[lang_translate]
					SET ", array('translate'=> $values[$l['iso']] ),"
						WHERE
						id_lang = %i",$l['id_lang'],"
						AND [key] = %s",$key);
			}
		}

		return true;
	}

}