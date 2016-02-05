<?php

class LangTranslateModel extends BaseModel implements ITranslator{
	
	private $translate;
	
	function __construct(DibiConnection $connection, ICacheStorage $cache) {
		parent::__construct($connection, $cache);
		$this->table = 'lang_translate';
		$this->translate = $this->fetchPairs($iso);
	}
	
	public function setLang( $lang = NULL)
	{
		
		$this->lang = $lang;

		$this->id_lang = Lang::convertIsoToId($lang);

		$this->translate = Lang::getTranslate($lang);


	}
	
	function fetchPairs( $iso ){
		$key = 'fetchPairs('.$iso.')';
		if($this->loadCache($key)){
			return $this->cache[$key];
		}
		
		return $this->saveCache($key, $this->getFluent()
				->removeClause('select')
				->select('key,translate')
				->where("iso = %s", $iso)->fetchPairs("key","translate") 
				);
		
		
	}
	
	
	/**
	 * Translates the given string.
	 * @param  string	translation string
	 * @param  int		count (positive number)
	 * @return string
	 */
	public function translate($message, $count = 1)
	{
		$message = (string) $message;
		
//		if (!empty($message) && isset($this->dictionary[$message])) {
//			$word = $this->dictionary[$message];
//			if ($count === NULL) $count = 1;
//			
//			$s = preg_replace('/([a-z]+)/', '$$1', "n=$count;" . $this->meta['Plural-Forms']);
//			eval($s);
//			$message = $word->translate($plural);
//		}
		
		if(!isset($this->translate[$message]) OR $this->translate[$message] == ''){
			$message = $message;
//			echo $message;exit;
			if( !dibi::fetchSingle("SELECT 1 FROM [lang_translate] WHERE [key] LIKE %s", $message,"AND [id_lang] = %i",$this->id_lang)){
//				echo dibi::$sql;
				Lang::insertTranslateKey($message, $this->id_lang);


			}

			$this->translate[$message] = $message;
			Lang::invalidateCache();
		}else
			$message = $this->translate[$message];

//		
//		$args = func_get_args();
//		if (count($args) > 1) {
//			array_shift($args);
//			$message = vsprintf($message, $args);
//		}
//		
//		
		return $message;
	}
}