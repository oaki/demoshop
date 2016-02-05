<?php

/**
 * Description of Admin_WidgetPresenter
 * Sprava wigetou
 * @author oaki
 */
class Admin_WidgetPresenter extends Admin_BasePresenter {


	
	public function actionAdd() {
		$id = $this->getService('Widget')->insertNew();
		$this->redirect('edit', array('id'=>$id));		
	}
	
	public function handleDelete($id){
		$this->getService('Widget')->delete($id);
		$this['widgetTabella']->invalidateControl();
	}


	public function renderEdit($id) {
		$values = (array)$this->getService('Widget')->get($id);
		
		$this['widgetForm']->setDefaults((array)$values);
		$this->template->values = $values;
		
		

		$this->template->fileNode = $this->getService('Widget')->getFileNode($id);
		
	}

	public function renderDefault() {
		$this['header']['css']->addFile('../jscripts/tabella_v2/maite.tabella.css');
	    $this['header']['js']->addFile('/tabella_v2/maite.tabella.js');
	}
	
	
	function handleSaveForm(NForm $form){
		$values = $form->getValues();
		$this->getService('Widget')->update($values,$values['id_widget']);
		
		$this->redirectAjax();
	}
	
	function handleRemoveParam( $id_param ){
		$this->getService('WidgetParam')->delete($id_param);
		
		$this->redirectAjax();
	}
	
	function handleAddNewParam(NSubmitButton $button){
		$values = $button->getForm()->getValues();
		
		$this->getService('Widget')->update($values,$values['id_widget']);
		
		$this->getService('WidgetParam')->insert( array('id_widget'=>$values['id_widget']));
//		exit;
		$this->redirectAjax();
//		dump($saved);exit;
	}
	
	function  createComponentWidgetForm($name) {
		$f = new MyForm();
		$f->addText('identifier', 'Identifikátor')
				->addRule(NForm::FILLED,'Identifikátor musí byť vyplnený');
		
		$f->addText('name', 'Názov');
		$f->addTextarea('template', 'Šablóna')
				->getControlPrototype()->class='long';
		
		$f->addHidden('id_widget');
		
		$f->addContainer('params');
		
		$f->addSubmit('save', 'Uložiť');
		$f->addSubmit('addNewParam', 'Pridať parameter')
			->onClick[] = callback($this, 'handleAddNewParam');
		
		$f->onSuccess[] = array($this, 'handleSaveForm');
		
		
		$values = (array)$this->getService('Widget')->get($this->getParam('id'));
		
		

		foreach($values['params'] as $k=>$p){
			$container = $f['params']->addContainer($k);
			$container->addText('name','Názov');
			$container->addText('value','Hodnota');//->setDefaultValue(array($p['value']));
			$container->setDefaults((array)$p);
		}		
//		unset($values['params']);
		
		
		return $f;
	}
	
	function  createComponentWidgetTabella($name) {
		$model = $this->getService('Widget');
		
		$grid = new Maite\Tabella(array(
			'context' => $this->context,
			'source' => $model->getFluent(),
			'id_table'=>'id_delivery',
			'order'=>'sequence',
			'onSubmit' => function($post) use ($model) {
				
			},
			'onDelete' => function($id) use ($model) {
				
				$model->delete($id);
			}
		));

		$this->addComponent($grid, $name);
		
		$grid->addColumn('Identifikátor', 'identifier', array('width' => 100,'editable' => true));

		$grid->addColumn('Názov', 'name', array('width' => 200));
		
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
						NHtml::el( 'a' )->href(	$presenter->link( 'delete!' , array('id'=>$row->id_widget)))
							->addClass( 'deleteIcon ajax' )
							->title('Naozaj chcete zmazať položku?')
					);



					/*
					 * link na editaciu produktu
					 */

					$el->add(
						NHtml::el( 'a' )->href(	$presenter->link( 'edit' , array('id'=>$row->id_widget))	)
							->addClass( 'editIcon' )
					);

					
					return $el;
				}
			)
		);
		
	}

	function redirectAjax() {
		
		if($this->isAjax()){
			$this->invalidateControl('form');
		}else{
			$this->redirect('this');
		}
	}
}