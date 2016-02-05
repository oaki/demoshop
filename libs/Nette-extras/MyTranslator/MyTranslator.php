<?php
class MyTranslator extends NObject implements ITranslator
{
	/** @var string */
	public $lang;
	public $id_lang;
	protected $translate;
	private $cache;
	
	public function setLang( $lang = NULL)
	{
		
		$this->lang = $lang;

		$this->id_lang = Lang::convertIsoToId($lang);

		$this->translate = Lang::getTranslate($lang);


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

	
 	/**
     * Saves extracted data into gettext file
     * @param string $outputFile
     * @param array $data
     * @return GettextExtractor
     */
    public function save( )
    {
    	$e = new MyExtractor();
    	
        $data = $e->getData();
      
      	$id_lang = dibi::fetchSingle("SELECT id_lang FROM [lang] WHERE iso = %s", $this->lang);
      	
      	if(!$id_lang)
      		throw new Exception('ISO lang neexistuje');

      	foreach($data as $k=>$d){
	      	$arr = array(
	        	'id_lang'=>$id_lang,
	        	'key'=>$k,	        	
	        	'files'=>implode(", ", $d)
	        );
	        
	        try{
        		dibi::query("INSERT INTO lang_translate", $arr);
	        }catch(Exception $e){
	        	echo $e->getMessage();
	        }        
      	}
      	
		self::invalidateCache();

      	return $this;
        
    }
}	