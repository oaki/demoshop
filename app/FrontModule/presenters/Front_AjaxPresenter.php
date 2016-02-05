<?php

/**
 * Vracia ajaxom cast stranky, napr. hlasku pri pridani do kosika a podobne 
 *
 * @author oaki
 */
class Front_AjaxPresenter extends BasePresenter {


	public function actionAddToCartMsg() {
		$this->template->msg = $this->getService('translator')->translate('Produkt bol vložený do kosíka');
	}

	public function renderDefault() {
		
	}

}