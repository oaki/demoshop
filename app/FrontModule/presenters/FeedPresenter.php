<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 */



/**
 * Feed channel presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class Front_FeedPresenter_old extends BasePresenter
{

	
	public function renderSitemap()
	{
		
		$this->template->categories = CategoryModel::getTree(1);
		
	}
	
	/**
	 * @return void
	 */
	protected function beforeRender()
	{
		// disables layout
		$this->setLayout(FALSE);
	}



	public function renderXml()
	{
		$this->template->items = 
		dibi::query('
SELECT 
product.id_product AS id, 
product_lang.name, 
product_lang.description, 
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
		
		
		$this->template->baseUri = 'http://www.matrace-rosty.sk';
		
		$this->id_lang = 1;
		
		for($i=0;$i<count($this->template->items);$i++) {
			$productWithLowestPrice = dibi::query('SELECT * FROM product_param WHERE id_product = %i',$this->template->items[$i]['id'],' ORDER BY price LIMIT 0,1')->fetch();
			$this->template->items[$i]['url'] = $this->getPresenter()->link('Eshop:current', array('categories'=> $this->template->items[$i]['category_link'], 'url_identifier'=>NStrings::webalize($this->template->items[$i]['product_link']) ));
			$this->template->items[$i]['image'] = ProductModel::getImage($this->template->items[$i]['id']);
			$this->template->items[$i]['price_vat'] = round($productWithLowestPrice['price'],2);
			$this->template->items[$i]['price'] = round($this->template->items[$i]['price_vat']/1.2,2);
			$this->template->items[$i]['vat'] = '0.20';
			$this->template->items[$i]['size'] = $productWithLowestPrice['size'];
			$this->template->items[$i]['material'] = $productWithLowestPrice['material'];
		}
	}

}
