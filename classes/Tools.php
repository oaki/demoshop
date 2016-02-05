<?
class Tools extends NObject{
	// *****************************************************
	// -------------------- seo_string ---------------------
	// *****************************************************
	
	public static function seoString($url, $extension=false)
	{
	     $url = mb_strtolower($url,"utf-8");
	     
	     $a = array("á","ä","č","ď","é","ě","ë","í","ň","ó","ö","ô","ř","š","ť","ú","ů","ü","ý","ž","ľ","ĺ","§",
	     " ","`","´","=",",",".","°","~","!","@","#",
	     "$","%","^","*","(",")","{","}",":","|",
	     "<",">","?","'",";",",",".");
	     $b = array("a","a","c","d","e","e","e","i","n","o","o","o","r","s","t","u","u","u","y","z","l","l","",
	     "-","","","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-");
	     $url = str_replace($a, $b, $url);
	     $url = str_replace("---","-",$url);
	     $url = str_replace("--","-",$url);
	    
	     if($extension) // pripona k seostringu
	     {
	       $url = $url.".html";
	     }
	     
	     return $url;
	}
	
	  public static function random($length, $base = "abcdefghjkmnpqrstwxyz123456789")
        {
                $max = strlen($base)-1;
                $string = "";

                mt_srand((double)microtime()*1000000);
                while (strlen($string) < $length)
                        $string .= $base[mt_rand(0,$max)];

                return $string;
        }

	static function getValuesForTable($table, $input_values){
		$row = self::getCollum($table);
		$output = array();
		foreach($row as $r){
			if(isset($input_values[ $r  ]))
				$output[ $r ] = $input_values[ $r ];
		}
		
		return $output;
	}
		
	static function getCollum($table, $param = 'Field'){

	    $l = dibi::query("SHOW COLUMNS FROM ".$table)->fetchAll();
	   
	    if($param){
		$r = array();
		foreach($l as $p){
		    $r[$p[$param]] = $p[$param];
		}
	    }else{
		$r = $l;
	    }

	    return $r;
	}
	
}