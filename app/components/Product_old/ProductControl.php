<?php
class ProductException extends Exception{}

class ProductControl_old extends NControl {
	const MODULE_NAME = 'product';
	
	public function render($id_node, $full = false)
	{
	
		$template = $this->template;
		$template->product = self::getProduct($id_node);
		
		if($full){
			$template->setFile(dirname(__FILE__) . '/Product.phtml');
			$template->images = self::getImages($id_node);
			if(isset($template->images[0])){
				$template->firstBigImage = $template->images[0];
				$template->firstBigImage['big'] = Files::gURL($template->firstBigImage['src'], $template->firstBigImage['ext'], 700, 250, 5);
				$template->firstBigImage['medium'] = Files::gURL($template->firstBigImage['src'], $template->firstBigImage['ext'], 290, 290, 5);
				unset($template->images[0]);				
			}
			 
			 
			
		}else{				
			$template->setFile(dirname(__FILE__) . '/ProductAnnotation.phtml');
			//$template->image = self::getImage($id_node);
			$template->product['url'] = $this->getPresenter()->link('List:current', array('categories'=> $this->getPresenter()->getParam('categories') , 'url_identifier'=>$template->product['url_identifier']));			
		}
//		var_dump();
//			print_r($template->product);
		$template->render();
	}
	
	public static function getProduct($id_node){
		return dibi::fetch("SELECT * FROM [module_product] WHERE id_node=%i",$id_node);
	}	
	
	public static function getImage($id_node){		
		
		$image = FilesNode::getOneFirstFile(self::MODULE_NAME, $id_node);
		
		$image['thumbs'] = Files::gURL($image['src'], $image['ext'], 110, 110, 5);
		$image['big'] = Files::gURL($image['src'], $image['ext'], 800, 600);
		return $image;
	}
	
	
	public static function getImages($id_node){		
		$images = FilesNode::getAllFiles(self::MODULE_NAME, $id_node);
		
		foreach($images as $k=>$image){
			$images[$k]['thumbs'] = Files::gURL($image['src'], $image['ext'], 210, 160, 5);
			$images[$k]['big'] = Files::gURL($image['src'], $image['ext'], 800, 600); 
		}
		return $images;
	}
	
	static function getIdProductByUrl($url_identifier){
		return dibi::fetchSingle("SELECT id_node FROM [module_product] WHERE url_identifier = %s", $url_identifier);
	}
	
	
	static function getDatasource($lang){
		return dibi::datasource("
			SELECT module_product.*, menu_item.id_menu_item FROM [module_product] JOIN node USING(id_node) JOIN [menu_item] USING(id_menu_item)
			WHERE 
				menu_item.lang = %s",$lang,"
		");
	}
	
	
	
}