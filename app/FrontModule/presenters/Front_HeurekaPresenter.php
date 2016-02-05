<?php
class Front_HeurekaPresenter extends Front_BasePresenter{

	function renderXml() {
		
		//$this->id_lang
		$list =	dibi::fetchAll('
				SELECT 
				product.id_product
				FROM 
					`product`					
					JOIN category_product USING (id_product)					
					JOIN category USING (id_category)
				WHERE 
					product.active = 1 AND					
					product.added = 1 AND
					category.active = 1				
				
				GROUP BY (id_product)');
		
		
		$this->template->baseUri = 'http://'.$_SERVER['HTTP_HOST'];
		
		$this->id_lang = 1;
		
		$this->template->items = array();
		
		foreach($list as $k=>$l){
			$this->template->items[$k] = ProductModel::getProductWithParams($l['id_product'], $this->id_lang, NULL);
			$this->template->items[$k]['url']  = $this->link(':Front:Product:default', array('id'=>$l['id_product'], 'id_category' => $this->template->items[$k]['main_category']));
			
			//zisti nazvy kategorii
			$category = array();
			foreach($this->template->items[$k]['categories'] as $cat){
				$tmp = CategoryModel::get($cat['id_category'], $this->id_lang);
				
				$category[] = $tmp['name'];				
			}
			
			$this->template->items[$k]['categories_name'] = implode(" | ",$category);		
		}
		
//		dde($this->template->items);
		
	}

	function renderXml_old() {
		
		//$this->id_lang
		$this->template->items = 
		dibi::query('
SELECT 
product.id_product AS id, 
product_lang.name, 
product_lang.meta_description, 
product_lang.link_rewrite AS product_link,
category_product.id_category AS id_category,
category_lang.name AS category_name,
category_lang.link_rewrite AS category_link

FROM `product`
LEFT JOIN product_lang USING (id_product)
LEFT JOIN category_product USING (id_product)
LEFT JOIN category_lang USING (id_category)
WHERE product_lang.id_lang = 1 AND
 category_lang.id_lang = 1
GROUP BY (id_product)')->fetchAll();
		
		
		$this->template->baseUri = 'http://'.$_SERVER['HTTP_HOST'];
		
		$this->id_lang = 1;
		
		for($i=0;$i<count($this->template->items);$i++) {
			
			$this->template->items[$i]['url']  = $this->link(':Front:Product:default', array('id'=>$this->template->items[$i]['id'], 'id_category' => $this->template->items[$i]['id_category']));

			$this->template->items[$i]['image'] = ProductModel::getImage($this->template->items[$i]['id']);
			$this->template->items[$i]['price_vat'] = round($productWithLowestPrice['price'],2);
			$this->template->items[$i]['price'] = round($this->template->items[$i]['price_vat']/1.2,2);
			$this->template->items[$i]['vat'] = '0.20';
			$this->template->items[$i]['size'] = $productWithLowestPrice['size'];
			$this->template->items[$i]['material'] = $productWithLowestPrice['material'];
		}
		
		dde($this->template->items);
	}

}
