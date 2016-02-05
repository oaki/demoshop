<?php

/**
 * Description of Admin_VatPresenter
 *
 * @author oaki
 */
/**
* @property-read DeliveryModel $deliveryModel
*/


class Admin_Setting_DeliveryPresenter extends Admin_Setting_BasePresenter {

	private $deliveryModel;
	
	function startup() {
		parent::startup();
		$this->deliveryModel = $this->getService('Delivery');
	}
	
	public function actionDefault() {
		$this->template->list = $this->deliveryModel->fetchAll();
	}
	
	public function actionEdit( $id ) {
		$this['form']->setDefaults( $this->deliveryModel->fetch($id) );
	}

	public function renderDefault() {
		$this['header']['css']->addFile('../jscripts/tabella_v2/maite.tabella.css');
	    $this['header']['js']->addFile('/tabella_v2/maite.tabella.js');
		
	}
	
	
	function handleSave(NForm $form){
		$values = $form->getValues();
		
		if( $this->deliveryModel->isIdExist($values['id_delivery']) ){
			$this->deliveryModel->update($values, $values['id_delivery']);
		}else{
			$this->deliveryModel->insert($values);
		}
		
		$this->flashMessage('Uložené.');
		$this->redirect('this');
	}
	
	function handleDeleteDelivery($id){
		
		$this->deliveryModel->delete($id);
		
		$this['deliveryTabella']->invalidateControl();
	}
	
	function  createComponentDeliveryTabella($name) {
		$model = $this->deliveryModel;
		$grid = new Maite\Tabella(array(
			'context' => $this->context,
			'source' => $this->deliveryModel->getFluent(),
			'id_table'=>'id_delivery',
			'order'=>'sequence',
			'onSubmit' => function($post) use ($model) {
				
			},
			'onDelete' => function($id) use ($model) {
				
				$model->delete($id);
			}
		));

		$this->addComponent($grid, $name);
		
		$grid->addColumn('Názov', 'name', array('width' => 100,'editable' => true));

		$grid->addColumn('Popis', 'description', array('width' => 200));
		$grid->addColumn('Cena', 'price', array('width' => 100));

		
		$presenter = $this->getPresenter();
		
		$grid->addColumn("", "active",
			array(
				"width" => 20,
				'type'=>  Maite\Tabella::TEXT,				
				"filter" => false,
				'order'=>false,				
				"renderer" => function( $row ) use ($presenter) {
					
					$el = NHtml::el( "td" );
					
					$el->add(
						NHtml::el( 'a' )->href(	$presenter->link( 'deleteDelivery!' , array('id'=>$row->id_delivery)))
							->addClass( 'deleteIcon ajax' )
							->title('Naozaj chcete zmazať položku?')
					);



					/*
					 * link na editaciu produktu
					 */

					$el->add(
						NHtml::el( 'a' )->href(	$presenter->link( 'Delivery:edit' , array('id'=>$row->id_delivery))	)
							->addClass( 'editIcon' )
					);

					
					/*
					 * posuvanie - ak sa spusti posubanie, treba vypnut zoradovanie !!! order=>false
					 */

//					$el->add(
//					NHtml::el( 'a' )->href('#')->addClass( 'moveIcon' )
//							->addId( 'index_'.$row['id_product'] )
//					);
					
					return $el;
				}
			)
		);

			

		
		
	}
	
	function createComponentForm($name) {
		$f = new MyForm;
		$f->addText('name', 'Názov')
				->addRule(NForm::FILLED,'Názov musí byť vyplnený');
		
		
		$f->addText('price','Cena')
			->addRule(NForm::FILLED,'Cena musí byť vyplená')
			->addRule(NForm::FLOAT,'Cena musí byť číslo');
		
		$f->addTextArea('description', 'Popis')
				->getControlPrototype()->class = 'long';
		
		$f->addHidden('id_delivery');
		
		$f->addSubmit('btn','Uložiť')->getControlPrototype()->class = 'submit';
		
		$f->onSuccess[] = array($this,'handleSave');
		
		return $f;
	}
	
	

}