<?php

/**
 * Description of KeywordControl
 *
 * @author oaki
 */
class KeywordControl extends BaseControl {

	private $result;

	private $keywords;
	
	private $google_num_result;
	
	
	function setKeywords($keywords){
		$this->keywords = $keywords;
	}
	
	function setGoogleNumResult($google_num_result){
		$this->google_num_result = $google_num_result;
	}
	
	public function render() {
		
		if(!$this->presenter->user->isAllowed('cms', 'edit'))
			throw new NAuthenticationException;
		
		$this['requestBtn']->setDefaults( array('keywords'=>$this->keywords) );
		
		$this->template->setFile( dirname(__FILE__).'/default.latte');
		
		$this->template->result = $this->result;
		
		$this->template->render();
	}
	
	function handleShowKeywords(NForm $form){
		$values = $form->getValues();
		
		/* @var $seo Seo\GoogleSeoModel */
		
		$seo = Seo\GoogleSeoModel::init();
		$seo->setKeyword( $values['keywords'] );
		$seo->setGoogleNumResults($values['google_num_result']);
		
			
		$this->result = $seo->run();
		
		$this->flashMessage('Vyhladane');
		
		$this->invalidateControl();
		 
		
//		$this->invalidateControl('keywords');
//		$google_keywords = $stats->getGoogleKeywords( );
		
//		print_r($google_keywords);exit; 
//		$stats = Stats\StatsModel::init();
//		
//		$keywords = $stats->parseKeywords( $values['url'] );
//		
//		$google_keywords = $stats->getGoogleKeywords( );
//		
//		print_r($google_keywords);exit; 
	}
	
	function createComponentRequestBtn($name){
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