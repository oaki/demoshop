<?php
  class crypt_old {
      private $key;
      function __construct($key){
      	$this->key = $key;
      }
      
      public function encrypt($value){
         if(!$value){return false;}
         $text = $value;
         $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
         $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
         $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_ECB, $iv);
         //return urlencode(trim(base64_encode($crypttext))); //encode for cookie
         $str = str_replace("+","image_plus",trim(base64_encode($crypttext))); //encode for cookie
         $str=str_replace("/","image_slash",$str);
         $str=str_replace("=","image_eq",$str);
         return urlencode($str);
      }
      
      public function decrypt($value){
         if(!$value){return false;}        
		 $str= str_replace("image_slash","/",$value);
		 $str = str_replace("image_plus","+",$str);
		 $str=str_replace("image_eq","=",$str);
         $crypttext = base64_decode($str); //decode cookie
         $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
         $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
         $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $crypttext, MCRYPT_MODE_ECB, $iv);
//         return urldecode(trim($decrypttext));
		return urldecode(trim($decrypttext));
      }

  }
?>