<?php

/**
 * Description of CartControl
 *
 * @author oaki
 */

/**
* @property-read \ShoppingCart\Repository $repository
*/
class CartControl_new extends BaseControl {

	private $repository;
	
	function __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		$this->repository = NEnvironment::getApplication()->getPresenter()->getService('ShoppingCart');
	}
    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
		$product = ProductModel::getProductIdentifyByParam(2, $this->getPresenter()->id_lang, $this->getPresenter()->user);
		
		$item = new \ShoppingCart\Item();
		
		$item = $this->repository->loadProductToItem($product, $item);
		print_r($item);exit;
//$this->repository->deleteAll();
		$this->repository->add($item, 3);
		
		$l = $this->repository->fetchAll();
		print_r($l);
//		$repository = new ShoppingCart\Repository(new ShoppingCart\SessionMapper($this->getPresenter()->getSession('cart')));

		 //NDebug::bardump($order_info);
		
		// ak je heslo použiteľné
//		if (DiscountHashModel::usable($order_info['values']['discount_hash'])) {
//			// zľava v €
//			$this->template->discount = ($this->template->total_sum-$this->template->weight_price)*0.03;
//			$order_info->discount = $this->template->discount;
//			
//			// zníženie výslednej ceny o 3%
//			$this->template->total_sum = $this->template->total_sum - $this->template->discount;
//		} else $this->template->discount = false;
		
		
		$this->template->setFile(dirname(__FILE__) . '/default.phtml');
		$this->template->render();
	//	$session = NEnvironment::getSession('cart');

    }

    

    /*
     * handle
     */
    function handleAdd($id_product_param, $count){
		
		$product = ProductModel::getProductIdentifyByParam($id_product_param, $this->getPresenter()->id_lang, $this->getPresenter()->user);
		
		$item = new \ShoppingCart\Item();
		
		$item = $this->repository->mapper->load($product);
		
//$this->repository->deleteAll();
		$this->repository->add($item, $count);
		
		
		$this->invalidateControl('defaultcart');
    }

    function handleDelete($id_product_param){
		//ak existuje pripocitaj
		if(isset($this->session->products[$id_product_param])){
			$this->session->products[$id_product_param]--;
			if($this->session->products[$id_product_param]<1)
			unset($this->session->products[$id_product_param]);
		}

		$this->getPresenter()->getComponent('cartsmall')->invalidateControl();
		$this->invalidateControl('defaultcart');
    }

    function handleUnsetProduct($id_product_param){
		//ak existuje pripocitaj
		if(isset($this->session->products[$id_product_param])){
			unset($this->session->products[$id_product_param]);
		}
		$this->redirect('this');
    }


    function handleCalculate(){
		//ak existuje pripocitaj
		$params = $this->getPresenter()->getParam();
		foreach( $params as $k=>$p){
			if( strstr($k, 'product_count_') ){
				list($pom,$id_product_param) = explode("product_count_",$k);

				if(isset($this->session->products[$id_product_param])){
					$count = (int)$p;
					if($count == 0){
						unset($this->session->products[$id_product_param]);
					}else{
						$this->session->products[$id_product_param] = $count;						
						
					}
				}
			}
		}
		$this->getPresenter()->getComponent('cartsmall')->invalidateControl();
		$this->invalidateControl('defaultcart');
    }
}
