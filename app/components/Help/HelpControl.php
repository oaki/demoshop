<?php

class HelpControl extends BaseControl{
	
	private $helps;
	
	private $helpModel;
	
	function __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		$this->helpModel = HelpModel::init();
	}
	
	function render(){
		echo 1;
		
		exit;
	}
	
	function handleGetHelps($data){
		
		$data = $this->getPresenter()->getParam('data');
		
		$list = $this->helpModel->fetchPairs();
		
		$return = array();
		
		foreach($data as $d){
			
			if(isset($list[$d])){
				if($list[$d]!='')
					$return[$d] = $list[$d];
			}else{
				$this->helpModel->insert(array('key'=>$d));
			}
		}
		
		$this->getPresenter()->sendResponse(new NJsonResponse($return));exit;
		$this->template->setFile( dirname(__FILE__).'/default.latte');
		$this->template->helps = $return;
		$this->template->render();
		exit;
		
	}
	
	function renderJavascript(){
		$this->template->setFile( dirname(__FILE__).'/javascript.latte');
		$this->template->render();
	}
}