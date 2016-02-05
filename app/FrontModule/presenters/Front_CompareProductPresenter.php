<?php

/**
 * Description of Front_ComparePresenter
 *
 * @author oaki
 */
class Front_CompareProductPresenter extends Front_BasePresenter {



	public function actionDefault() {
		$t = $this->getService('CompareProduct')->fetchAll();		
	}

	public function renderDefault() {
		
	}

	function handleAddProduct($id_product_param){
		
		$this->getService('CompareProduct')->insert($id_product_param);
		
		if ($this->isAjax()) {
			$this->terminate();			
		}else{
			$this->getPresenter()->redirect('this');
		}
	}
	
	function handleRemoveProduct($id_product_param){
		
		$this->getService('CompareProduct')->delete($id_product_param);
		
		if ($this->isAjax()) {
			$this->terminate();			
		}else{
			$this->getPresenter()->redirect('this');
		}
	}
}