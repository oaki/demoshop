<?php
class LoginControl_old extends BaseControl{
	function render($options = array()){
		trigger_error(__METHOD__ . '() is deprecated; use UserProfilControl', E_USER_WARNING); 
		$params = $this->getPresenter()->getParam();
		if(isset($params['do']) AND $params['do'] == 'LoginForm-classicLoginForm-submit'){
			$this->renderClassic();
		}else{
			$template = $this->template;
			$template->setFile( dirname(__FILE__).'/default.phtml');

			$template->options = $options;

			$template->user = @NEnvironment::getUser()->getIdentity()->data;

			$this['loginForm']->setDefaults(array('backlink'=>$_SERVER['REQUEST_URI']));

			$template->backlink = $_SERVER['REQUEST_URI'];

	//		$template->backlink = $this->getPresenter()->name.':'.$this->getPresenter()->action;
			$template->render();
		}
	}

	function renderClassic(){
		$template = $this->template;
		$template->setFile( dirname(__FILE__).'/classic.phtml');

		$template->user = @NEnvironment::getUser()->getIdentity()->data;

		$this['classicLoginForm']->setDefaults(array('backlink'=>$_SERVER['REQUEST_URI']));
		$template->backlink = $_SERVER['REQUEST_URI'];
		$template->render();
	}
	
	function createComponent($name) {
		switch ($name) {
			case 'baseForm':
				$form = new NAppForm();
				$form->getElementPrototype()->addClass('ajax');
				$form->addText('login', _('Prihlasovacie meno'))
					->addRule(NFORM::FILLED, _('Prihlasovacie meno musí byť vyplnené'));
				/*
				 * todo nejaky problem pri odhlaseny
				 */
//				$form->addProtection(_('Bohužial Váš formulár expiroval. Prosím odošlite formulár znovu.') );
				$renderer = $form->getRenderer();

				$renderer->wrappers['controls']['container'] = NULL;

				$renderer->wrappers['pair']['container'] = 'div';
				$renderer->wrappers['label']['container'] = NULL;
				$renderer->wrappers['control']['container'] = NULL;

				$form->addPassword('password',  'Heslo')
					->addRule(NFORM::FILLED, _('Heslo musí byť vyplnené.') );

				$form->addSubmit('btn_submit', _('Prihlásiť'));
				$form->addHidden('backlink');


				return $form;
				break;

			case 'loginForm' :
				$form = $this->createComponent('baseForm');
				$form->onSuccess[] = array( $this, 'onSubmitAuthenticate');
				$this->addComponent($form, $name);
				break;

			case 'classicLoginForm':
				$form = $this->createComponent('baseForm');
				$form->onSuccess[] = array( $this, 'onSubmitAuthenticate');
				$this->addComponent($form, $name);
				break;

			default :
				return parent::createComponent ( $name );
				break;
		}
	}

	
	function onSubmitAuthenticate(NFORM $form) {
		$values = $form->getValues();

		$this->invalidateControl('login');
		$this->invalidateControl('minilogin');
//		$this->invalidateControl('classs');
		
		$user = NEnvironment::getUser();
		try {
			
			$user->setExpiration ( '+ 2 days', FALSE );
//			$user->setAuthenticationHandler ( new UserModel() );
			$user->login ( $values ['login'], $values ['password'] );
			$form->setValues ( array (), TRUE );
			
			if($values['backlink']!=''){
				$this->getPresenter()->redirectUrl($values['backlink']);
			}else{
				//$this->getPresenter()->redirect('this');
			}
			
		} catch ( NAuthenticationException $e ) {
			$form->addError ( $e->getMessage () );			
		}
	
	}
}