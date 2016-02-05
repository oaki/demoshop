<?php

/**
 * Description of CartSmallControl
 *
 * @author oaki
 */
class CartSmallControl extends CartControl {

	public function render($controls = true) {
//		$this->beforeRender();
//print_r($this->template);
		$this->template->setFile(dirname(__FILE__) . '/small.phtml');
//		$this->template->total_sum-=$this->template->weight_price;
		$this->template->cart_info = OrderModel::getCartInfo(
				$this->session->products,
				false, 
				false, 
				$this->getPresenter()->context, $isCache = true);
		$this->template->render();
	}

}
