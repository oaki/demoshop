<?php

namespace Seo;
use DOMDocument;
use DOMXPath;
use dibi,DibiConnection,NCache,NEnvironment;



class GoogleSeoModel extends \CacheModel
{
	private $connection;
	
	private $context;

	private $google_num_results = 5;
	
	private $keywords = array();
	
	private $forbidden_words = array(
		'clickeshop',
		'404',
		'moved permanently',
		'not found',
		'/',
		'untitled document',
		'heureka.sk',
		'najnakup.sk','error','wiki'
	);

	public static function init(){
		return new self( dibi::getConnection(), \NEnvironment::getCache( get_class() ), \NEnvironment::getContext() );
	}

	public function __construct(\DibiConnection $connection, NCache $cache, $context ){

		$this->connection = $connection;
		$this->cache = $cache;
		$this->context = $context;
		
	}

	public function getConnection(){
			return $this->connection;
	}

	
	function setKeyword( $word ){
		$words = $this->separateWord($word);
		
		foreach($words as $w)
			$this->addKeyword( $w );
		
	}
	
	function setGoogleNumResults($num){
		$this->google_num_results = $num;
	}
	
	
	function run(){
		$return = array();
		
		$key = serialize($this->keywords).serialize($this->google_num_results);
		
		$return = $this->loadCache($key);
		if($return)
			return $return;
		
		foreach($this->keywords as $word){
			$return[$word] = array();
			$links = $this->getGoogleLinksForKeyword($word);
//			dump($return);
			foreach($links as $k=>$link){
				if(!isset($return[$word]['links']))
					$return[$word]['links'] = array();

				$return[$word]['links'][$link] = $this->parseKeywords($link);
			}
		}
		
		
		return $this->saveCache($key,$return);
	}
	
	/*==================================
	Get url content and response headers (given a url, follows all redirections on it and returned content and response headers of final url)

	@return    array[0]    content
			array[1]    array of response headers
	==================================*/
	static function get_url_content( $url,  $loop = 0, $timeout = 5 )
	{
		$url = str_replace( "&amp;", "&", $url );

		try{
			$cookie = tempnam (TEMP_DIR, "CURLCOOKIE");
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_HTTPHEADER,array (
				"Content-Type: text/xml; charset=utf-8",
				"Expect: 100-continue"
			));
			curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
			@curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
			@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_ENCODING, "" );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
			
			$err     = curl_errno( $ch );
			$errmsg  = curl_error( $ch );
			$response = curl_getinfo( $ch );
			$content = curl_exec( $ch );			
			
			curl_close ( $ch );
	
		}catch(Exception $e){
			
		}

		
		return array( 
			'content'=>$content, 
			'response'=>$response,
			'errno' =>$err,
			'errmsg' => $errmsg,
		);
		
	}

	//vrati rozdeleny retazec na zaklade separatorov
	private function separateWord( $str ){
		
		//odstran html tagy
		$str = strip_tags($str);
		
		$my_separator = '|:::|';

		$separators = array('|', ',');
		$tmp = str_replace($separators, $my_separator, $str);
		
		return explode($my_separator, $tmp);
	}
	
	
	//rozparsuje klucive slova a ulozi ich do premennej
	function parseKeywords($url){
		$info = $this->parse($url);
		
		$return = array();
		// title
		$return['title'] = $this->validateArray($this->separateWord( $info['title'] ));		
		
		// description
		$return['description'] = $this->validateArray($this->separateWord( $info['description'] ));		
		
		// h1
		$h1_max_count = 1;
		$tmp_count = 0;
		$return['h1'] = array();
		foreach($info['h1'] as $h1){
			if($h1_max_count > $tmp_count ){
				$words_h1 = $this->validateArray($this->separateWord( $h1 ));		
				$return['h1'][] = $words_h1;
				$tmp_count++;
			}
		}
			
		
		return $return;
	}
	
	
	function validateArray( array $array_word ){
//		echo 'Validate array:';dump($array_word);
		foreach($array_word as $k=>$w){
			$tmp = $this->validateKeyword($w);
			if($tmp){
				$array_word[$k] = $tmp;
			}else{
				unset($array_word[$k]);
			}
		}
//		echo 'Validate array vystup';dump($array_word);
		
		return $array_word;
	}
	
	function validateKeyword( $word ){
		$word = \NStrings::lower( trim($word) );
		
		foreach($this->forbidden_words as $w){
			if(strpos($word, $w) !== false){		
				return false;
			}
		}	
				
		//ak je slovo kratsie ako 4 znaky neprida ho
		if(!empty($word) AND strlen($word)>3){
			
			return $word;
		}
		
		return false;
	}
	
	
	//prida klucove slova s tym ze ich osetri na zakazane slova atd
	function addKeyword($word){
		if($tmp = $this->validateKeyword($word))
			$this->keywords[$tmp] = $tmp;
	}
	
	//rozparsuje stranku
	function parse( $url ){
		
		$page = self::get_url_content($url);
		
		require_once LIBS_DIR.'/simplehtmldom/simple_html_dom.php';
		$html = str_get_html(  $page['content'] );			
		$text = NULL;	
		$title = NULL;
		$keywords = NULL;
		$description = NULL;
		
		$h1 = array();
		$h2 = array();
		
			
		foreach($html->find('title') as $element)
			  $title = $element->innertext; 
		
		foreach($html->find('meta[name=description]') as $element)
			  $description = $element->content;
		
		foreach($html->find('meta[name=keywords]') as $element)
			   $keywords = $element->innertext; 

		foreach($html->find('h1') as $element)
			   $h1[] = $element->innertext; 
		
		foreach($html->find('h2') as $element)
			   $h2[] = $element->innertext; 
		
		$arr = array(				
				'url'=>$url,
				'title'=>$title,
				'keywords'=>$keywords,
				'description'=>$description,
				'h1'=>$h1,
				'h2'=>$h2
			);
		
		return $arr;
	}
	
	
	
	
	
	//vrati linky pre klucove slovo na googli
	function getGoogleLinksForKeyword( $word ){
		
		$keyword_google_additional_params = array(
			'ie'=>'utf-8',
			'oe'=>'utf-8',
			'aq'=>'t',
			'rls'=>'org.mozilla:en-US:official',
			'client'=>'firefox-a'
		);

		$url = 'http://www.google.sk/search?q='.rawurlencode($word).'&num='.$this->google_num_results.'&'.http_build_query($keyword_google_additional_params);
		
		$info = $this->get_url_content($url);
//		dde($info);
		preg_match_all('|<h3 class=(["\'])?r\\1?><a.+href="(.+)".+</h3>|Umis', $info['content'], $matches);
		
		foreach($matches[2] as $k=>$m){
			$tmp = explode('http://', $m);
			$tmp2= @explode('&amp;sa=U',$tmp[1]);
			$matches[2][$k] = 'http://'.$tmp2[0];
		}
			
		return $matches[2];
	}
	
	
	
}