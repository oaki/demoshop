<?php
class CaptchaSecurityImages {
 
   var $font = 'classes/monofont.ttf';
 
   function generateCode($characters) {
      /* list all possible characters, similar looking characters and vowels have been removed */
      $possible = '23456789bcdfghjkmnpqrstvwxyz';
      $code = '';
      $i = 0;
      while ($i < $characters) { 
         $code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
         $i++;
      }
      return $code;
   }
 
   function CaptchaSecurityImages($width='55',$height='40',$characters='4') {
      $code = $this->generateCode($characters);
      /* font size will be 75% of the image height */
      $font_size = $height * 0.80;
      $image = imagecreate($width, $height) or die('Cannot initialize new GD image stream');
      /* set the colours */
      $background_color = imagecolorallocate($image,  152, 171, 0);
      $text_color = imagecolorallocate($image, 20, 40, 100);
      $noise_color = imagecolorallocate($image, 232, 236, 198);
      /* generate random dots in background */
      for( $i=0; $i<150; $i++ ) {
         imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
      }
      /* generate random lines in background */
      for( $i=0; $i<10; $i++ ) {
         imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
      }
      /* create textbox and add text */
      $textbox = imagettfbbox($font_size, 0, $this->font, $code) or die('Error in imagettfbbox function');
      $x = ($width - $textbox[4])/2;
      $y = ($height - $textbox[5])/2;
      imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font , $code) or die('Error in imagettftext function');
      /* output captcha image to browser */
      header('Content-Type: image/jpeg');
      imagejpeg($image);
      imagedestroy($image);
      $_SESSION['security_code'] = $code;
   }
 
}
 
$width = isset($_GET['width']) && $_GET['height'] < 600 ? $_GET['width'] : '55';
$height = isset($_GET['height']) && $_GET['height'] < 200 ? $_GET['height'] : '20';
$characters = isset($_GET['characters']) && $_GET['characters'] > 2 ? $_GET['characters'] : '4';
if(isset($_GET['captcha'])){ 
  $captcha = new CaptchaSecurityImages($width,$height,$characters);
}?>
