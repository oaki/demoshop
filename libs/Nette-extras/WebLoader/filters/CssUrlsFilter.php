<?php
/**
 * Absolutize urls in CSS
 *
 * @author Jan Marek
 * @license MIT
 */
class CssUrlsFilter extends NObject {
	
	/**
	 * Make relative url absolute
	 * @param string image url
	 * @param string single or double quote
	 * @param string absolute css file path
	 * @param string source path
	 * @return string
	 */
	public static function absolutizeUrl($url, $quote, $cssFile, $sourcePath) {
		// is already absolute
		if (preg_match("/^([a-z]+:\/)?\//", $url)) return $url;

		$docroot = realpath(WWW_DIR);
		$basePath = rtrim(NEnvironment::getVariable("baseUri"), '/');

		// inside document root
		if (NStrings::startsWith($cssFile, $docroot)) {
			$path = $basePath . substr(dirname($cssFile), strlen($docroot)) . DIRECTORY_SEPARATOR . $url;

		// outside document root
		} else {
			$path = $basePath . substr($sourcePath, strlen($docroot)) . DIRECTORY_SEPARATOR . $url;
		}

		$path = self::cannonicalizePath($path);

		return $quote === '"' ? addslashes($path) : $path;
	}


	/**
	 * Cannonicalize path
	 * @param string path
	 * @return path
	 */
	private static function cannonicalizePath($path) {
		foreach (explode(DIRECTORY_SEPARATOR, $path) as $i => $name) {
			if ($name === "." || ($name === "" && $i > 0)) continue;

			if ($name === "..") {
				array_pop($pathArr);
				continue;
			}

			$pathArr[] = $name;
		}

		return implode("/", $pathArr);
	}


	/**
	 * Invoke filter
	 * @param string code
	 * @param WebLoader loader
	 * @param string file
	 * @return string
	 */
	public function __invoke($code, WebLoader $loader, $file = null)
	{
		// thanks to kravco
//		$regexp = '~
//			(?<![a-z])
//			url\(                                     ## url(
//				\s*                                   ##   optional whitespace
//				([\'"])?                              ##   optional single/double quote
//				(   (?: (?:\\\\.)+                    ##     escape sequences
//					|   [^\'"\\\\,()\s]+              ##     safe characters
//					|   (?(1)   (?!\1)[\'"\\\\,() \t] ##       allowed special characters
//						|       ^                     ##       (none, if not quoted)
//						)
//					)*                                ##     (greedy match)
//				)
//				(?(1)\1)                              ##   optional single/double quote
//				\s*                                   ##   optional whitespace
//			\)                                        ## )
//		~xs';
//		
////		$matches = NULL;
//		$ret = preg_replace_callback(
//			$regexp,
//			
//			$this->m(),
//			
//			$code
//		);
		return  preg_replace_callback(
			 "(url\(['\"]?([^\)'\"]*)['\"]?\))",
			function ($matches) use ($loader, $file) {
				
				return "url('" . CssUrlsFilter::absolutizeUrl($matches[1], NULL, $file, $loader->sourcePath) . "')";
			},
			$code
		);
//		return $ret;
		
	}
	
	function m($matches, $file, $sourcePath){
//		NDebug::dump($file);
		NDebug::dump($matches);
//		NDebug::dump($sourcePath);
			return "url('" . CssUrlsFilter::absolutizeUrl($matches[2], $matches[1], $file, $sourcePath) . "')";
	}
	
}