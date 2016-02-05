<?php

/**
 * Description of Admin_User
 *
 * @author oaki
 */
class Admin_UserPresenter extends Admin_BasePresenter {

    public function renderDefault() {

	}

	public function renderEdit($id) {
	    
		
	}

	function  beforeRender() {
	    parent::beforeRender();

	    $this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');
	    
	    
	}
	

	function  createComponent($name) {
		 switch ($name){

		 case 'userTabella':

			
			 $model = new UserModel();



			$grid = new Tabella( $model->getFluent(false)->toDatasource() ,
				array(
					'sorting'=>'desc',
					'order'=>'id_gui_user',

				)
			);


			$grid->addColumn( "Login/Email", "login", array( "width" => 100 ) );
			$grid->addColumn( "Meno", "name", array( "width" => 100 ) );
			$grid->addColumn( "Priezvisko", "surname", array( "width" => 100 ) );
			$grid->addColumn( "Naposledy prihlásený", "lastvisit", array( "width" => 100 ) );


		$grid->addColumn("", "",
			array(
				"width" => 30,
				'filter'=>NULL,
				"options" => '',

				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );

					/*
					 * link na zmazanie produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'deleteUser!' , array('id'=>$row->id_gui_user))
					)->addClass( 'deleteIcon ajax' )
					);



					/*
					 * link na editaciu produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'edit' , array('id'=>$row->id_gui_user))
					)->addClass( 'editIcon' )
					);

	 /*
						 * ikona aktivan polozka, neaktivan polozka
						 */
						$span = NHtml::el('span');

						if($row->activate){
						$span->addClass('activeIcon active');
						}else{
						$span->addClass('unactiveIcon active ');
						}
						$el->add($span);

					return $el;
				}
				)
			);



			$this->addComponent( $grid, $name );
			 break;

		case 'userForm':
			$form = UserModel::baseForm( false );
			
			$form->addGroup('');
			$form->addText('discount', 'Zľava použivateľa');
			$form->addHidden('id');
			$form->addSubmit('btn_submit', 'Uložiť');
			$form->onSuccess[] = array($this, 'saveUser');

			$form->setDefaults( UserModel::get($this->getParam('id')));
			return $form;
			break;

		 default:
			return parent::createComponent($name);
			 break;
		 }
	}//end createComponent


	function handleDeleteUser($id){
		UserModel::delete($id);
		$this['userTabella']->invalidateControl();
	}

	function saveUser(NForm $form){
		$values = $form->getValues();
//		print_r($values);
//		exit;

		unset($values['passwordCheck']);

		$id_user = $values['id'];
		unset($values['id']);

		//ak nevyplni heslo, zostava stare
		if($values['password'] == '')
			unset($values['password']);

		UserModel::update($id_user, $values);
		$this->redirect('this');
	}

        
}