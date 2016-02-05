<?php

namespace Stats;
use DOMDocument;
use DOMXPath;
use dibi,DibiConnection,NCache,NEnvironment;

class StatsException extends \Exception{}

class StatsModel extends \CacheModel
{
	private $connection;
	
	private $context;

	private $site_url;
	
	private $google_num_results = 5;
	
	private $keyword_limit = 4;
	
	private $keywords = array();
	
	private $forbidden_words = array(
		'clickeshop',
		'404',
		'moved permanently',
		'not found',
		'/',
		
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

		$separators = array('|', ',', '-');
		$tmp = str_replace($separators, $my_separator, $str);
		
		return explode($my_separator, $tmp);
	}
	
	
	//rozparsuje klucive slova a ulozi ich do premennej
	function parseKeywords($url){
		$info = $this->parse($url);
		
		// title
		$words_title = $this->separateWord( $info['title'] );		
		foreach($words_title as $w){ $this->addKeyword($w);	}
		
		// description
		$words_description = $this->separateWord( $info['description'] );		
		foreach($words_description as $w){ $this->addKeyword($w);}
		
		// keywords
		
		$words_keywords = $this->separateWord( $info['keywords'] );		
		foreach($words_keywords as $w){ $this->addKeyword($w);}	
		
		// h1
		$h1_max_count = 1;
		$tmp_count = 0;
		foreach($info['h1'] as $h1){
			if($h1_max_count > $tmp_count ){
				$words_h1 = $this->separateWord( $h1 );		
				foreach($words_h1 as $w){ $this->addKeyword($w);}	
				$tmp_count++;
			}
		}
		
		// h2
//		foreach($info['h2'] as $h2){
//			$words_h2 = $this->separateWord( $h2 );		
//			foreach($words_h2 as $w){ $this->addKeyword($w);}		
//		}
		
		dump($this->keywords);
		return $this->getKeywords();
	}
	
	//vrati klucove slova
	function getKeywords(){
		return $this->keywords;
	}
	
	
	//prida klucove slova s tym ze ich osetri na zakazane slova atd
	function addKeyword($word){
		$word = \NStrings::lower( trim($word) );
		
		foreach($this->forbidden_words as $w){
			if(strpos($word, $w) !== false){		
				return false;
			}
		}	
		
		
		//ak obsahuje nepovolene znaky neprida 
//		echo '<br>
//			'.$word.'|'.(preg_match('/^([a-zA-Z0-9_ ]+)$/', $word));
//		if(preg_match('/^([a-zA-Z0-9_ ]+)$/', $word) === 0){
//			return false;
//		};
		
		
		
		//ak je slovo kratsie ako 4 znaky neprida ho
		if(!empty($word) AND strlen($word)>3)
			$this->keywords[$word] = $word;
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
	
	
	/*
	 * zoberie z modelu klucove slova a zisti na googli a rozparsuje linky a vyberie klucove slova
	 */
	function getGoogleKeywords(){
		$check_links = array();
		foreach( $this->keywords as $word){
			$links = $this->getGoogleLinksForKeyword($word);
			foreach($links as $l){
				$check_links[] = $l;
			}			
		}
		
		$google_model = self::init();
		
		foreach( $check_links as $link){
			$google_model->parseKeywords( $link );
		}
		
		return $google_model->getKeywords();
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
		
		preg_match_all('|<h3 class=(["\'])?r\\1?><a.+href="(.+)".+</h3>|Umis', $info['content'], $matches);
		
		foreach($matches[2] as $k=>$m){
			$tmp = explode('http://', $m);
			$tmp2= @explode('&amp;sa=U',$tmp[1]);
			$matches[2][$k] = 'http://'.$tmp2[0];
		}
			
		return $matches[2];
	}
	
	
	
}