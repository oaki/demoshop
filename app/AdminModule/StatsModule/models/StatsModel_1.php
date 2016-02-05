<?php

namespace Stats;
use DOMDocument;
use DOMXPath;
use dibi,DibiConnection,NCache,NEnvironment;



class StatsModel_OLD extends \CacheModel
{
	private $connection;
	
	private $context;

	private $site_url;
	
	private $google_num_results = 5;
	private $keyword_limit = 4;

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

	private function toArray( &$main_array, $value){
		$value = \NStrings::lower(trim($value));
		
		$disabled_words = array(
			'moved permanently',
			'404',			
		);
		
		$is_allow = true;
		
		foreach($disabled_words as $w){
			if(strpos($value, $w)){		
				$is_allow = false;
			}
		}	
		
		if($is_allow)
			$main_array[$value] = $value;
	}
	
	function getKeyword( array $info ){
		$array = array();
		
		$words_title = array();
		$tmp_words_title = array();
		
		$words_keyword = array();
		$words_from_content = array();
		
		if(isset($info['heading_title'][0])){
			$words_title = explode(",", $info['heading_title'][0] );			

			foreach($words_title as $k=>$t){
				$tmp = explode('|', $t);

				if(count($tmp)>1){				
					unset($words_title[$k]);
					$tmp_words_title+=$tmp;
				}
			}
		}
		
		
		if(isset($info['heading_keywords'][0]))
			$words_keyword = explode(",", $info['heading_keywords'][0] );
		
		if(isset($info['heading_top_keywords']))
			$words_from_content = $info['heading_top_keywords'];
		
		
		
		foreach($words_title as $l){ $this->toArray($array, $l); }
		foreach($tmp_words_title as $l){ $this->toArray($array, $l);}
		foreach($words_keyword as $l){ $this->toArray($array, $l);}
		
		if(isset($info['h1'])){			
			foreach($info['h1'] as $l){ $this->toArray($array, $l);}			
		}
			
		
		if(isset($info['h2']))
			foreach($info['h2'] as $l){ $this->toArray($array, $l);}
		
//		foreach($words_from_content as $k=>$v){
//			$array[] = $k;
//		}
		
		foreach($array as $k=>$a){
			if(strlen($a)<3)
				unset($array[$k]);
		}
		
		return $array;
	}
	
	
	function getKeywordsFromGoogle($url){
		
		$info = $this->getInfoFromMyPage($url);
				
		$my_keywords = $info['keywords'];

		
		
		$ch = curl_init();

		$found = array();
		$uri = new \NUrlScript($url);
		$domain = $uri->host;
		
		$keyword_google_num_results = $this->google_num_results;
		$keyword_google_additional_params = array(
			'ie'=>'utf-8',
			'oe'=>'utf-8',
			'aq'=>'t',
			'rls'=>'org.mozilla:en-US:official',
			'client'=>'firefox-a'
		);
//		$keyword_google_additional_params = array();
		$return = array();
//		print_r($my_keywords);exit;
		foreach($my_keywords as $keyword){
			$keyword = trim($keyword);

			$uri = 'http://www.google.sk/search?q='.rawurlencode($keyword).'&num='.$keyword_google_num_results.'&'.http_build_query($keyword_google_additional_params);
			
			$content_array = self::get_url_content($uri);
			
			preg_match_all('|<h3 class=(["\'])?r\\1?><a.+href="(.+)".+</h3>|Umis', $content_array['content'], $matches);

			
			foreach($matches[2] as $k=>$m){

				$tmp = explode('http://', $m);
//				print_r($tmp);
				$tmp2= @explode('&amp;sa=U',$tmp[1]);
//				print_r($tmp2);
				$matches[2][$k] = $tmp2[0];
//				print_r($tmp);exit;
			}
//			 echo "<pre style=\"text-align: left;\">";
//			 print_r($matches);
//			 echo "</pre>";
//			 exit();
			$results = array();


			if (!empty($matches[2]))
			{
				$results = $matches[2];
			}

			$num = 1;
			
			foreach($results as $uri)
			{
				
				if (strpos($uri, $domain) !== FALSE)
				{
					if (!isset($found[$keyword]))
					{
						$found[$keyword] = array();
					}
					$found[$keyword][] = array('keyword'=>$keyword,'num'=>$num);
				}
				$num++;
			}
			
			$return[$keyword] = array('google_first_urls'=>$results,'found'=>$found);
		}

		
//		dump($return);
//		dump($found);
//		exit;
		return array('info'=>$info,'keywords'=>$return);
	}
	

	
//	
//	function strip_word_html($text, $allowed_tags = '<b><i><sup><sub><em><strong><u><br>')
//    {
//        mb_regex_encoding('UTF-8');
//        //replace MS special characters first
//        $search = array('/&lsquo;/u', '/&rsquo;/u', '/&ldquo;/u', '/&rdquo;/u', '/&mdash;/u');
//        $replace = array('\'', '\'', '"', '"', '-');
//        $text = preg_replace($search, $replace, $text);
//        //make sure _all_ html entities are converted to the plain ascii equivalents - it appears
//        //in some MS headers, some html entities are encoded and some aren't
//        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
//        //try to strip out any C style comments first, since these, embedded in html comments, seem to
//        //prevent strip_tags from removing html comments (MS Word introduced combination)
//        if(mb_stripos($text, '/*') !== FALSE){
//            $text = mb_eregi_replace('#/\*.*?\*/#s', '', $text, 'm');
//        }
//        //introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be
//        //'<1' becomes '< 1'(note: somewhat application specific)
//        $text = preg_replace(array('/<([0-9]+)/'), array('< $1'), $text);
//        $text = strip_tags($text, $allowed_tags);
//        //eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one
//        $text = preg_replace(array('/^\s\s+/', '/\s\s+$/', '/\s\s+/u'), array('', '', ' '), $text);
//        //strip out inline css and simplify style tags
//        $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
//        $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
//        $text = preg_replace($search, $replace, $text);
//        //on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
//        //that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
//        //some MS Style Definitions - this last bit gets rid of any leftover comments */
//        $num_matches = preg_match_all("/\<!--/u", $text, $matches);
//        if($num_matches){
//              $text = preg_replace('/\<!--(.)*--\>/isu', '', $text);
//        }
//        return $text;
//    } 
//	
	
	function strip_script($string) {
    // Prevent inline scripting
    //$string = preg_replace("/<script[^>]*>.*<*script[^>]*>/i", "", $string);

	

//$string = preg_replace("/<script[^>]*>.*?< *script[^>]*>/i", "", $string);
    // Prevent linking to source files
//    $string = preg_replace("/<script[^>]*>(.*)<\/script>/i", "", $string);
		
    //$string = preg_replace("/<script type=\"text\/javascript\">(.*?)<\/script>/", "", $string);
    $string = preg_replace("@<script[^>]*?.*?</script>@siu", "", $string);


    //styles
    $string = preg_replace("/<style[^>]*>.*<*style[^>]*>/i", "", $string);
    // Prevent linking to source files
    $string = preg_replace("/<style[^>]*>/i", "", $string);
    return $string;
}



	/*==================================
	Get url content and response headers (given a url, follows all redirections on it and returned content and response headers of final url)

	@return    array[0]    content
			array[1]    array of response headers
	==================================*/
	static function get_url_content( $url,  $loop = 0, $timeout = 5 )
	{
		$url = str_replace( "&amp;", "&", $url );
//echo $url;
		try{
			$cookie = tempnam (TEMP_DIR, "CURLCOOKIE");
			$ch = curl_init();
//			curl_setopt($ch,CURLOPT_HTTPHEADER,array (
//				"Content-Type: text/xml; charset=utf-8",
//				"Expect: 100-continue"
//			));
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
			
			
			$response = curl_getinfo( $ch );
			$content = curl_exec( $ch );			
			
//			print_r($content);
			curl_close ( $ch );
					
//			
//			if($response['redirect_url'] !='' AND $loop < 5){
//				echo 1;exit;
//				$loop = $loop+1;
//				$return = $this->get_url_content($url, $loop);
//				$response = $return['response'];
//				$content = $return['content'];
//			}
		}catch(Exception $e){
			
		}

		
		return array( 'content'=>$content, 'response'=>$response );
		
	}


	function getInfoFromMyPage_v2( $link ){
		$text = self::get_url_content($link);
		
		require_once LIBS_DIR.'/simplehtmldom/simple_html_dom.php';
		$html = str_get_html(  $text );			
		$text = NULL;	
		$title = NULL;
		$keywords = NULL;
		$description = NULL;
		
			
		foreach($html->find('title') as $element)
			  $title = $element->innertext; 
		
		foreach($html->find('meta[name=description]') as $element)
			  $description = $element->content;
		
		foreach($html->find('div[class=resultMainInfo]') as $element)
			   $text = $element->innertext; 
		
		$arr = array(				
				'link'=>$link,
				'title'=>$title,
				'keywords'=>$keywords,
				'description'=>$description,				
			);
		print_r($arr);
	}
	
	function getInfoFromMyPage( $url ){
			
			//odtrasni posledne lomitko
			$url = trim($url,'/');
			
			$html = new DOMDocument(); 
			
			// scrape html from page running on localhost
			$content_array = self::get_url_content($url);
			
			$page_html = $this->strip_script($content_array['content']);
			
			//if (!@$html->loadHTMLFile($url))
			if (!@$html->loadHTML($page_html)){
				throw new StatsException('stranka neexistuje');
			} 

			$xpath = new DOMXPath( $html );


			$results = array('url'=>$url);
			$keyword_limit = $this->keyword_limit;
			
			
			
			// search meta elements elements with attributes
			$search_tags = array(
				'heading_title' => 'title',
				'heading_description' => 'meta[@name="description"];content', 
				'heading_keywords' => 'meta[@name="keywords"];content',
				'h1' => 'h1', 
				'h2' => 'h2', 
				'h3' => 'h3', 
				'h4' => 'h4', 
			//	$top_keywords => 'h1|//h2|//h3|//h4|//h5|//h6|//p|//li|//blockquote|//a|//div|//address|//cite',
//				'heading_top_keywords' => 'body',
//				'heading_first_100_words' => 'p',
//				'Links' => 'a[@href];href', 
//				'heading_image_alt' => 'img;alt;src'
			);
				
			foreach($search_tags as $key => $tag)
			{
				//if (empty($results[$key])) $results[$key] = array();
				$attrs = explode(';', $tag);
				
				$xpath_results = $xpath->query("//".$attrs[0]);
				if (empty($xpath_results)) continue;
				foreach($xpath_results as $r)
				{
					if (is_int($key)) $key = $tag;
					$content = NULL;
					if (count($attrs) > 2)
					{
						$new_attrs = $attrs;
						array_shift($new_attrs);
						foreach($new_attrs as $attr)
						{
							$content[$attr] = $r->getAttribute($attr);
						}
					}
					else if (isset($attrs[1]))
					{
						$content = $r->getAttribute($attrs[1]);
					}
					else
					{
						$content = $r->nodeValue;
					}
					if (!empty($content))
					{
						$results[$key][] = $content;
					}
				}
			}
			
			print_r($results);
			
			if (!empty($results['heading_top_keywords'][0]))
			{
				
				// clean up script tags and style tags
				$page_content = $results['heading_top_keywords'][0];
//				echo "<pre style=\"text-align: left;\">";
//				print_r($page_content);
//				echo "</pre>";

//				$page_content = $this->strip_word_html($page_content);


//				echo 1;print_r($page_content);exit;
				$page_content = str_replace("\n", " ", $page_content);
				
				$page_words = preg_split("/[\s,\.]+/", $page_content);
//				print_r($page_words);exit;
				$page_words_count = array();
				
				foreach($page_words as $word)
				{
					if (ctype_alnum($word))
					{
						if (empty($page_words_count[$word]) && strlen($word) > 3)
						{
							$page_words_count[$word] = 0;
						}
						if (strlen($word) > 3)
						{
							$page_words_count[$word] += 1;
						}
					}
				}
				

				uasort($page_words_count, array(&$this, '_sort_word_density'));
				$page_words_count_limited = array_slice($page_words_count, 0, $keyword_limit);
				$results['heading_top_keywords'] = $page_words_count_limited;
			}
			
			// format First 100 words
			if (!empty($results['heading_first_100_words']))
			{
				$results['heading_first_100_words'] = \NStrings::truncate(implode(' ', $results['heading_first_100_words']), 100);
			}
			
			
			// format image alt tags
			if (!empty($results['heading_image_alt']))
			{
				foreach($results['heading_image_alt'] as $key => $img)
				{
					
					if (substr($img['src'], 0, 4) != 'http')
					{
						//ak nie je prvy lomitko prida ho
						
						if(strpos($img['src'], '/') == 0){
							$img['src'] = $url.$img['src'];
						}else{
							$img['src'] = $url.'/'.$img['src'];
						}
						
					}
					
					if (empty($img['alt']))
					{
						$img['alt'] = 'seo_image_alt_empty';
					}
					$results['heading_image_alt'][$key] = $this->anchor($img['src'], $img['alt']);
				}
			}
			
			// format links
			$formatted_links = array();
			
			$local_hostname = parse_url( 'http://'.$this->context->httpRequest->url->host );
			
			if (!empty($results['Links']))
			{
				foreach($results['Links'] as $key => $link)
				{
					$link = str_replace(array('#', 'javascript:;', 'javascript:void(0);'), '', $link);

					if (!empty($link) && substr($link, 0, 7) != 'mailto:')
					{
						$href = $url.$link;
						if (substr($href, 0, 4) != 'http')
						{
							$href = $url.$link;
						}
						$formatted_links[] = $link;

						$link_parsed = parse_url($link);
						
						if ($link_parsed AND isset($link_parsed['host']) AND !empty($link_parsed['host']) && $link_parsed['host'] != $local_hostname['host'])
						{
							$results['heading_outbound_links'][] = $this->anchor($link);
						}

					}
				}
				$results['Links'] = $formatted_links;
			}
			
			
			unset($results['Links']);
			
			// change errors back to original settings
			libxml_clear_errors(); 
			
			$results['keywords'] = $this->getKeyword($results);
			
			return $results;
	}
	
	
	
	
	function site_url($uri = ''){
		if($uri=='')
			return $this->context->httpRequest->uri->host;
		else{
			return parse_url($uri);
		}
	}
	
	
	
	function anchor($uri = '', $title = '', $attributes = ''){
		
		$title = (string) $title;

		if ( ! is_array($uri))
		{
			$this->site_url = ( ! preg_match('!^\w+://! i', $uri)) ? $this->site_url($uri) : $uri;
		}
		else
		{
			$this->site_url = $this->site_url($uri);
		}

		if ($title == '')
		{
			$title = $this->site_url;
		}

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		return '<a href="'.$this->site_url.'"'.$attributes.'>'.$title.'</a>';
	}
	
	function _sort_word_density($a, $b)
	{
		if ($a == $b) {
		    return 0;
		}
    	return ($a < $b) ? 1 : -1;
	}
}