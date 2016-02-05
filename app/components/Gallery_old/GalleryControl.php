<?php
class GalleryControl_old extends NControl {
	private $id_node;
	const _CRYPT = 'erSgWMCTKbEgBmF';

	const MODULE_NAME = 'article';

	public function render($id_node) {
		
		$template = $this->template;
		$template->images = self::getImages ( $id_node );
		$template->setFile ( dirname ( __FILE__ ) . '/GalleryControl.phtml' );
		$template->render ();
	}
	
	public static function getAll($id_node) {
		return dibi::fetchAll ( "
			SELECT * 
			FROM 
				gallery 
				LEFT JOIN gallery_image USING(id_node) 
			WHERE 
				gallery.id_node=%i", $id_node, "				
			ORDER BY sequence
		" );
	}

	public static function getImages($id_node){
		$images = FilesNode::getAllFiles(self::MODULE_NAME, $id_node);

		foreach($images as $k=>$image){
			$images[$k]['thumb'] = Files::gURL($image['src'], $image['ext'], 220, 165, 5);
			$images[$k]['big'] = Files::gURL($image['src'], $image['ext'], 800, 600);
		}
		return $images;
	}

	public static function showImage($src_hash) {
		$crypt = new Crypt ();
		$crypt->Mode = Crypt::MODE_HEX;
		$crypt->Key = self::_CRYPT;
		
		list ( $src, $hash ) = explode ( "|", $src_hash, 2 );
		
		list ( $hash, $ext ) = explode ( ".", $hash, 2 );
		
		list ( $width, $height, $flags ) = explode ( "|", $crypt->decrypt ( $hash ) );
		//preverenie
		

		if (strlen ( $ext ) > 5 or strstr ( $src, '/' ) or ! is_numeric ( $width ) or ! is_numeric ( $width ) or ! is_numeric ( $height ) or ! is_numeric ( $flags ) or $flags > 10) {
			throw new GalleryException ( 'Nastala chyba - nespravny subor.' );
		}
		
		$image = NImage::fromFile ( NEnvironment::getConfig ()->gallery ['dir_abs'] . '/original/' . $src . '.' . $ext );
		
		//progressive
		$image->interlace ( true );
		
		$image->resize ( $width, $height, $flags );
		
		if ($flags == 5) {
			$image->crop ( "50%", "50%", $width, $height );
		}
		
		$image->save ( self::gURL ( $src, $ext, $width, $height, $flags, 'dir_abs' ) );
		
		$image->send ();
	}
	
	public static function gURL($src, $ext, $width, $height, $flags = 0, $dir = 'dir', $mode = 'full_path') {
		$crypt = new Crypt ();
		$crypt->Mode = Crypt::MODE_HEX;
		$crypt->Key = self::_CRYPT;
		
		switch ($mode) {
			default :
				return NEnvironment::getConfig ()->gallery [$dir] . '/temp/' . $src . '|' . $crypt->encrypt ( $width . '|' . $height . '|' . $flags ) . '.' . $ext;
				break;
			
			case 'image_name' :
				return $src . '|' . $crypt->encrypt ( $width . '|' . $height . '|' . $flags ) . '.' . $ext;
				break;
			
			case 'dir' :
				return NEnvironment::getConfig ()->gallery [$dir] . '/temp/';
				break;
		}
	
	}
	
	public function removeFromCache($filename){
		$list = scandir(NEnvironment::getConfig ()->gallery ['dir_abs'].'/temp/');
		foreach($list as $l){
			$pom = explode('|', $l);
			if($filename == $pom[0]){
				unlink(NEnvironment::getConfig()->gallery['dir_abs'].'/temp/'.$l);
			}
		}
		
	}

}