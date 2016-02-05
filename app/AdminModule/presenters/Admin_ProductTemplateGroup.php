<?php

/**
 * Description of Admin_ProductTemplateGroup
 *
 * @author oaki
 */


	
class Admin_ProductTemplateGroupPresenter extends Admin_BasePresenter {

	
	/**
	* @property-read ProductTemplateGroupModel $model
	*/
	private $model;
	
	function startup() {
		parent::startup();
		$this->model = $this->getService('ProductTemplateGroupModel');
	}
	
	public function actionDefault() {
		
	}


	public function renderDefault($id=NULL) {
		
		// ak je idcko prazdne zober prve a presmeruj to tam
		if($id==NULL){
			$list = $this->model->fetchAll();
			if($list AND isset($list[0])){
				$this->redirect('this',array('id'=>$list[0]['id_product_template_group']));
			}
		}
		
		//ziska vsetky skupiny
		$this->template->groups = $this->model->fetchAll();
		
		if($id){
			//aktualna skupina
			$this->template->group = $this->model->fetch($id);
			
			$this->template->group_params = $this->model->fetchAssocAllParam($id);
			
			$values = array(
				'id_product_template_group'=>$id,
				'group_name'=>$this->template->group['name'],
				'allow_change_price'=>$this->template->group['allow_change_price'],
				'default'=>$this->template->group['default'],
			);
			
			foreach($this->template->group_params as $l){
				$values[$l['row_name']] = ($l['checked']==1)?1:0;
			}
			
			$this['form']->setDefaults($values);
		}
	}

	function actionDelete($id){
		
		
		$this->model->delete($id);
		$this->flashMessage('Položka bola vymazaná.');
		$this->redirect('default', array('id'=>NULL));
	}
		
	function handleSave(NForm $form){
		$values = $form->getValues();
		
		//ak nie je skupina vytvorena, resp. neexistuje id_product_template_group, vytvori
		if( !$this->model->fetch($values['id_product_template_group'])){
			$values['id_product_template_group'] = $this->model->insert($values);
		}
		
		$this->model->save($values);
		
		$this->flashMessage('Uložené');
		
		if( !$this->isAjax()){
			$this->redirect('this', array('id'=>$values['id_product_template_group']));
		}
		
	}
	
	function createComponentForm($name) {
		$f = new MyForm;
		$f->addHidden('id_product_template_group');
		$f->addGroup('Nastavenie skupiny');
		$f->addText('group_name', 'Názov skupiny')
				->addRule(NForm::FILLED,'Názov skupiny musí byť vyplnený');
		
		$f->addCheckbox('default', 'Predvolená skupina?');
		
		$f->addCheckbox('allow_change_price', 'Možnosť meniť cenu pre jednotlivé parametre?');
		
		$f->addGroup('Parametre');		
		
		$rows = $this->model->getProductParamRows();
		
		foreach($rows as $k=>$r){
			$f->addCheckbox($r,$r);
		}
		
		$f->addGroup('');
		$f->addSubmit('btn','Uložiť');
		
		$f->onSuccess[] = array( $this, 'handleSave');
		return $f;
	}
}