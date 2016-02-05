<?php
class  Admin_LoginPresenter extends BasePresenter{
	/** @persistent */
	public $backlink = '';
	
	public function startup()
	{
		parent::startup();
		$this['header']['css']->addFile('authentication/auth.css');
		$this['header']['js']->addFile('jquery/In-Field-Labels/src/jquery.infieldlabel.min.js');
		
		$this->session->start(); // required by $form->addProtection()
	}
	

	/********************* component factories *********************/

	/**
	 * Sign in form component factory.
	 * @return NAppForm
	 */
	protected function createComponent($name)
	{
		switch($name){
			case 'loginForm':
				
				$form = new NAppForm;
				$form->addText('username', _('Prihlasovacie meno').':')
					->addRule(NForm::FILLED, _('Prihlasovacie meno musí byť vyplnené.') );
		
				$form->addPassword('password', _('Heslo').':')
					->addRule(NForm::FILLED, _('Prihlasovacie heslo musí byť vyplnené.'));
		
				$form->addSubmit('submit_login', 'Log In');
				
				$renderer = $form->getRenderer();
	
				$renderer->wrappers['controls']['container'] = NULL;
				
				$renderer->wrappers['pair']['container'] = 'div';
				$renderer->wrappers['label']['container'] = NULL;
				$renderer->wrappers['control']['container'] = NULL;
		
				$form->addProtection( _('Sedenie vypršalo. Proším obnovte prihlasovací formulár a zadajte údaje znovu.'), 1800);
	
				$form['submit_login']->getControlPrototype()->class = 'btnLogin';
				
				$form->onSuccess[] = callback($this, 'loginFormSubmitted');
				return $form;
						
				break;
				
			default:
				return parent::createComponent($name);
				break;
		}
	}



	public function loginFormSubmitted($form)
	{
		try {
//			$this->user->setExpiration('+ 2 days', FALSE);
			
			$this->user->login($form['username']->value, $form['password']->value);

			
			$this->application->restoreRequest($this->backlink);

			$this->redirect('Eshop:');

		} catch (NAuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}



	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('default');
	}
}

