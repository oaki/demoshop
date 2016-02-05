<?php

/**
 * Description of Admin_VatPresenter
 *
 * @author oaki
 */
/**
* @property-read helpsModel $helpsModel
*/


class Admin_Setting_HelpsPresenter extends Admin_Setting_BasePresenter {

	private $helpsModel;
	
	function startup() {
		parent::startup();
		$this->helpsModel = HelpModel::init();
	}
	
	public function actionDefault() {
		
	}
	
	public function actionEdit( $id ) {
		$this['form']->setDefaults( $this->helpsModel->fetch($id) );
	}

	public function renderDefault() {
		$this['header']['css']->addFile('../jscripts/tabella_v2/maite.tabella.css');
	    $this['header']['js']->addFile('/tabella_v2/maite.tabella.js');		
	}
	
	
	function handleSave(NForm $form){
		$values = $form->getValues();
		$this->helpsModel->update($values, $values['id_helps']);
		
		$this->flashMessage('Uložené.');
		$this->redirect('this');
	}
	
	function handleDelete($id){
		
		$this->helpsModel->delete($id);
		
		$this['tabella']->invalidateControl();
	}
	
	function  createComponentTabella($name) {
		$model = $this->helpsModel;
		$grid = new Maite\Tabella(array(
			'context' => $this->context,
			'source' => $this->helpsModel->getFluent(),
			'id_table'=>'id_helps',
			'order'=>'id_helps',
			'onSubmit' => function($post) use ($model) {
				
			}
		));

		$this->addComponent($grid, $name);
		
		$grid->addColumn('Názov', 'key', array('width' => 100,'editable' => true));

		$grid->addColumn('Text', 'text', array('width' => 200));
		
		
		$presenter = $this->getPresenter();
		
		$grid->addColumn("", "active",
			array(
				"width" => 20,
				'type'=>  Maite\Tabella::TEXT,				
				"filter" => false,
				'order'=>false,				
				"renderer" => function( $row ) use ($presenter) {
					
					$el = NHtml::el( "td" );
					
			
					/*
					 * link na editaciu produktu
					 */

					$el->add(
						NHtml::el( 'a' )->href(	$presenter->link( 'edit' , array('id'=>$row->id_helps))	)
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
		$f->addText('key', 'Názov')
				->addRule(NForm::FILLED,'Názov musí byť vyplnený');
		
		$f->addTextArea('text', 'Popis')
				->getControlPrototype()->class = 'long';
		
		$f->addHidden('id_helps');
		
		$f->addSubmit('btn','Uložiť')->getControlPrototype()->class = 'submit';
		
		$f->onSuccess[] = array($this,'handleSave');
		
		return $f;
	}
	
	

}