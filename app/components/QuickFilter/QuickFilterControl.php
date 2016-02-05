<?php

class QuickFilterControl extends BaseControl{
	
	function render( $params = array() ){
			
		$this->template->params = $params;
		$this->template->setFile( dirname(__FILE__).'/default.latte');
		$this->template->render();
	}
	
	
	function getSession(){
		return $this->getPresenter()->getSession('quick_filter');
	}
	
	/*
	 * Zoradenie
	 */
	
	
	function handleChangeOrder(NForm $form){
		$v = $form->getValues();
		$orderSession = $this->getSession();
		$orderSession['order'] = $v['order_select'];
		
		if( $this->getPresenter()->isAjax()){
			$this->getPresenter()->invalidateControl('productList');
		}else{
			$this->redirect('this');
		}
	}
	
	function getOrderFilterArray(){
		return array(
			'adddate desc'=>_('Od najnovších'),
			'price desc'=>_('Od najdrahších'),
			'price asc'=>_('Od najlacnejších'),
		);		
	}
	
	
	/*
	 * Pocet na stranu
	 *
	 */
	
	
	function handleChangeNumOnPage( NForm $form ){
		$v = $form->getValues();
		
		$numOnPageSession = $this->getSession();
		$numOnPageSession['num'] = $v['num_on_page'];
		
		if( $this->getPresenter()->isAjax()){
			$this->getPresenter()->invalidateControl('productList');
		}else{
			$this->redirect('this');
		}
	}
	
	function getNumOnPageFilterArray(){
		return array(
			'9'=>_('menej produktov'),
			'32'=>_('viac produktov')			
		);		
	}
	
	protected function createComponentNumOnPage($name) {
		$numOnPageSession = $this->getSession();
				
		$f = new MyForm();
		$f->getElementPrototype()->class='ajax';
		$f->addSelect('num_on_page','Počet', $this->getNumOnPageFilterArray() )
				->getControlPrototype()->class = 'orderBy';
		$f->onSuccess[] = array($this,'handleChangeNumOnPage');
//		dump($this->getNumOnPageSession());exit;
		$f->setDefaults(array('num_on_page'=>$numOnPageSession['num']));

		return $f;
		
	}
	
	
	function  createComponent($name) {
		switch ($name) {		
			case 'order':
				$orderSession = $this->getSession();
				
				$f = new MyForm();
				$f->getElementPrototype()->class='ajax';
				$f->addSelect('order_select','Zoradenie podľa: ', $this->getOrderFilterArray() )
						->getControlPrototype()->class = 'orderBy';
				$f->onSuccess[] = array($this,'handleChangeOrder');
				$f->setDefaults(array('order_select'=>$orderSession['order']));
				
				return $f;
				break;
			default:
				return parent::createComponent($name);
			break;
		}
	}
}