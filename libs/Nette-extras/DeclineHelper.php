<?php

/**
 * Skonovanie
 *
 * @author Pavol Bincik
 */
final class DeclineHelper
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}

	/**
	 * Loads static helper
	 * Register with Template in for example BasePresenter->createTemplate()
	 *
	 * @param string $helper
	 * @return NCallback|null
	 */
	public static function loadHelper($helper)
    {
        $callback = callback(__CLASS__, $helper);
        return ($callback->isCallable()) ? $callback : NULL;
    }

	/**
	 * Formats and splits given string according to $lengths and $functions
	 *
	 * @param string $subject
	 * @param array|int $lengths
	 * @param string $delimiter
	 * @param array $functions
	 * @return string
	 */
	static public function decline( $count, $words) {
		if(is_array($words)){
			if(isset($words[ $count ]))
				return $words[ $count ];
			else{
				return $words[ 0 ];
			}
		}else{
			//pozrieme do moznych preddefinovanych slov
			$defined_words = array(
				'produkt'=> array(
					0=>'produktov',
					1=>'produkt',
					2=>'produkty',
					3=>'produkty',
					4=>'produkty',
					5=>'produktov',
				)
			);
			
			if( isset($defined_words[ $words ])){
				$words = $defined_words[ $words ];
				if(isset($words[ $count ]))
					return $words[ $count ];
				else{
					return $words[ 0 ];
				}
			}else{
				throw new Exception('Slovo neviem sklonovat');
			}
			
		}
	}
}