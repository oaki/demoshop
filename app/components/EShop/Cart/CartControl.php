<?php

/**
 * Description of CartControl
 *
 * @author oaki
 */


//class ShoppingCart extends Nette\Object
//{
//        private $cart;
//
//        public function __construct(Nette\Http\Session $session)
//        {
//                $this->cart = $session->getSection(__CLASS__);
//        }
//
//        public function add(Item $item, $count = 1)
//        {
//                if (isset($this->cart[$item->id])) {
//                        // kdybych napsal += tak to u tohohle objektu bude AFAIK hazet chybu
//                        $this->cart[$item->id] = $this->cart[$item->id] + $count;
//                        return;
//                }
//
//                $this->cart[$item->id] = $count;
//        }
//
//
//        public function remove(Item $item)
//        {
//                unset($this->cart[$item->id]);
//        }
//
//        public function getItems()
//        {
//                return $this->cart->getIterator()->getArrayCopy();
//        }
//}



class CartControl extends BaseControl {

	protected $session;

    function  __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		$this->session = NEnvironment::getSession('cart');

		if(!isset($this->session->products)){
			$this->session->products = array();
		}
    }

	protected function createComponent($name) {
		switch ($name) {
			default :
				return parent::createComponent ( $name );
				break;
		}
	}



    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render($params = array()) {
		
		$order_info = NEnvironment::getSession('user_order_info');
		
		$this->template->delivery = (isset($params['delivery']) AND $params['delivery'] == true)?$order_info['values']['id_delivery']:false;
		$this->template->payment = (isset($params['delivery']) AND $params['delivery'] == true)?$order_info['values']['id_payment']:false;
		
		$this->template->cart_info = OrderModel::getCartInfo(
				$this->session->products,
				$this->template->delivery, 
				$this->template->payment, 
				$this->getPresenter()->context, $isCache = true);
		
//		dump($this->template->cart_info);
		$this->template->controls = (isset($params['controls']) AND $params['controls'] == false)?false:true;

		$this->template->setFile(dirname(__FILE__) . '/default.phtml');
		$this->template->render();
	//	$session = NEnvironment::getSession('cart');

    }

    

    /*
     * handle
     */
    function handleAdd($id_product_param, $count){
//		$id_product_param = (int)$_POST['id_product_param'];
		//ak existuje pripocitaj
		
		$session = $this->session;

		if(!isset($session->products[$id_product_param]) ){
		   $session->products[$id_product_param] = $count;
		}else{
			$session->products[$id_product_param]+=$count;
		}
		
		if ($this->getPresenter()->isAjax()) {
			$this->getPresenter()->getComponent('cartsmall')->invalidateControl();
			
		}else{
			$this->getPresenter()->redirect('this');
		}
		
		
		//$this->invalidateControl('defaultcart');
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
