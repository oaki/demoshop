<?php

class AjaxTextControl extends BaseControl{
	private $result;
	private $time = 1;
	
	function render(){
		$this->template->setFile(dirname(__FILE__).'/default.latte');
		$this->template->time = $this->time;
		$this->template->result = $this->result;
		$this->template->render();
	}
	
	function handleRefresh(){
		$this->time = time();
		$this->flashMessage('Cas bol zmeneny');
        $this->invalidateControl();
	}
	
	function handleShowKeywords(NForm $form){
		$values = $form->getValues();
		
		/* @var $seo Seo\GoogleSeoModel */
		
		$seo = Seo\GoogleSeoModel::init();
		$seo->setKeyword( $values['keywords'] );
		$seo->setGoogleNumResults( $values['google_num_result'] );
		
			
		$this->result = $seo->run();
		
		$this->flashMessage('Vyhladane');
		
		$this->invalidateControl();
		
	}
	
	protected function createComponentForm($name) {
		$f = new MyForm;
		$f->getElementPrototype()->class = 'ajax';
		
		$f->addText('keywords', 'Klúčové slovo')
				->addRule(NForm::FILLED,'Klúčové slovo musí byť vyplnené')
				->addRule(NForm::MIN_LENGTH,"Klúčové slovo musí byť dlhšie", 3);
		$count = array();for($i=1;$i<30;$i++){ if(!($i%5))$count[$i]=$i;}
		$f->addSelect('google_num_result', 'Počet stránok', $count);
		$f->addSubmit('btn','Zobraziť príbuzné klúčové slová');
		$f->onSuccess[] = array($this, 'handleShowKeywords');
		
		return $f;
	}
}