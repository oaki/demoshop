<?php

class BreadcrumbControl extends BaseControl{
	
	function renderPage($id_menu_item){
		$page = $this->getService('Page');
		
		$template = $this->template;
		
		$template->parents = $page->getParent( $id_menu_item );

		$template->setFile(dirname(__FILE__) . '/page.latte');	
			
		$template->render();		
	}
	
	function renderCategory($id_category, $id_lang, $color = 'black'){
		
		$template = $this->template;
		$template->color_home = $color;
		$tmp = CategoryModel::getParents($id_category, $id_lang);
		$tmp = array_reverse($tmp);
		$tree = CategoryModel::getTree($id_lang);
		
		$template->parents = array();
			
		foreach($tmp as $t){
			$template->parents[] = $tree[$t];
		}		
		
		$template->setFile(dirname(__FILE__) . '/eshop_category.latte');	
			
		$template->render();		
	}
	
	function renderCustom( array $parents, $id_lang, $color = 'black' ){
		/*
		 * parents
		 * array( 0=>array('link'=>'www', 'name'=>'test'))
		 */
		$template = $this->template;
		$template->color_home = $color;
		$template->parents = $parents;
			
		$template->setFile(dirname(__FILE__) . '/custom.latte');	
			
		$template->render();		
	}
	
}