<?php

/**
 * Description of Front_ProductPresenter
 *
 * @author oaki
 */
class Front_ProductPresenter extends Front_BasePresenter {


	public function actionDefault($id, $id_category) {
		
	}
	
	
	/*
	 * Pridanie do kosika
	 */
	
	function handleAddToCart(NForm $form){
		$values = $form->getValues();
		
		$cartControl = new CartControl($this,'cart');
		$cartControl->handleAdd($values['id'], $values['count']);
	}
	
	protected function createComponentAddToCartForm(){
			$presenter = $this;
			return new NMultiplier( function($id) use ($presenter) {
					/* @var $f NForm */
					$f = new MyForm();
					
					$f->getElementPrototype()->class = 'ajax';
					$f->addText('count','Počet')
							->addRule(NForm::FILLED, 'Počet musí byť vyplnený.')
							->addRule(NForm::INTEGER, 'Musíte zadať číslo.');
					$f->addSubmit('btn','DO KOŠÍKA');
					$f->addHidden('id',$id);
					$f->onSuccess[] = array($presenter,'handleAddToCart');
					$f->setDefaults(array('count'=>1));
					return $f;
			});
	}

	
	
	
	public function renderDefault($id, $id_category) {
		
		if(!$id)
			throw new NBadRequestException('Product neexistuje');
		
		$this->template->id_category = $id_category;
		
		$this->template->category_parents = CategoryModel::getParents($this->template->id_category, $this->id_lang);
		
		
		$this->template->product = ProductModel::getProductWithParams( $id, $this->id_lang, $this->user );
		
		
		
		$this['productNewsSaleAvaiableTable']->setIdProduct( $id );
		$this['productNewsSaleAvaiableTable']->setIdCategory( $id_category );
		$this['productNewsSaleAvaiableTable']->setIdLang( $this->id_lang );
		$this['productNewsSaleAvaiableTable']->setUser( $this->user );
		
		/*
		 * META INFO
		 */
		
		if($this->template->product){
			if($this->template->product['meta_title']==''){
				$this['header']->addTitle( $this->template->product['name'] );
			}else{
				$this['header']->addTitle( $this->template->product['meta_title'] );
			}
			
			if($this->template->product['meta_description']==''){
				$this['header']->setDescription( $this->template->product['name'] );
			}else{
				$this['header']->setDescription( $this->template->product['meta_description'] );
			}
			
			
			$this['header']->addKeywords( $this->template->product['meta_keywords'] );
		}
		
		$this['header']['js']->addFile('jquery/bubblepopup/jquery.bubblepopup.v2.3.1.min.js');
		$this['header']['css']->addFile('../jscripts/jquery/bubblepopup/jquery.bubblepopup.v2.3.1.css');

	}
	
	
	function createComponent($name) {
		
		switch($name){
			
			
			
			case 'attachment' :
			    return new AttachmentControl;
			    break;
			
			default:
				return parent::createComponent($name);
				break;
		}
		
	}

}