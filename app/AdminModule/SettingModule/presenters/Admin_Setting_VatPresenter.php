<?php

/**
 * Description of Admin_VatPresenter
 *
 * @author oaki
 */
class Admin_Setting_VatPresenter extends Admin_Setting_BasePresenter {

	public function actionDefault() {
		
	}

	public function renderDefault() {
		$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');
		
	}
	
	function handleSave(NForm $form){
		$values = $form->getValues();
		VatModel::init()->insert($values);
		$this->flashMessage('Daň bola pridaná');
		$this->redirect('this');
	}
	
	function createComponentForm($name) {
		$f = new MyForm;
		
		$f->addText('name', 'Názov')
				->addRule(NForm::FILLED,'Názov musí byť vyplnená');
		
		$f->addText('value', 'Hodnota')
				->addRule(NForm::FILLED,'Hodnota musí byť vyplnená');
		
		$f->addSelect('is_default', 'Prednastavená?', array(0=>'nie', 1=>'áno'));
		
		$f->addSubmit('btn', 'Pridať');
		
		$f->onSuccess[] = array($this,'handleSave');
		
		return $f;
	}
	
	function  createComponentVatTabella($name) {
		$vat = VatModel::init();
		
		$grid = new Tabella( $vat->getFluent()->toDataSource(),
			array(
				'sorting'=>'asc',
				'order'=>'id_vat',
				'id_table'=>'id_vat',
				'limit'=>50,
				"onSubmit" => function( $post ) use ($vat){
					if($post['id_vat'] == 0){
						$vat->insert($post);
					}else{
						$vat->update($post['id_vat'], $post);
					}
				},
				"onDelete" => function( $id ) use ($vat){					
					$vat->delete($id);
				}
			)

		);

		$el = NHtml::el( "div" );
		$el->add(
				NHtml::el( 'a' )->href(
					NEnvironment::getApplication()->getPresenter()->link( 'addEmptyWeight!')
				)->addClass( 'addIcon ajax' )
		);


		//$grid->addColumn($el, '', array('width'=>20,  'filter'=>NULL, "editable" => false ) );

		$grid->addColumn( "Názov", "name", array( "width" => 50, "editable" => true ) );
		$grid->addColumn( "Hodnota", "value", array( "editable" => true ) );
		$grid->addColumn( "Prednadstavená?", "is_default", 
				array(
						"width" => 50,
						'type'=>  Tabella::SELECT,
						"editable" => true,
						"filter" => NULL,
						'renderer' => function($row){
							$el = NHtml::el( "td" )->setHtml( ($row['is_default']==1)?'áno':'nie' );
							return $el;
						}
					)
		);


		$grid->addColumn( "+", Tabella::ADD, array( 
			"type" => Tabella::DELETE 
        ));
		
		$this->addComponent( $grid, $name );


	}
     

}