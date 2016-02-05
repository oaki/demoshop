<?php

class UserProfilControl extends BaseControl{
	
	function renderTopLink(){
		$this->template->setFile( dirname(__FILE__).'/topLink.latte');
		$this->template->render();
	}
	
	
	function render(){
		$this->template->setFile( dirname(__FILE__).'/loginDialog.latte');
		$this->template->render();
	}
	
	function renderCartLogin(){
		$this->template->setFile( dirname(__FILE__).'/cart-login.latte');
		$this->template->render();
	}
	
	
	public function onSubmitAuthenticate(NFORM $form) {
		$values = $form->getValues();
		
		$this->invalidateControl();
		$user = $this->getPresenter()->user;
		try {			
//			$user->setExpiration ( '+ 30 days', FALSE );
			$user->login ( $values ['login'], $values ['password'] );
			$form->setValues ( array (), TRUE );
			
			if($values['backlink']!=''){
				$this->getPresenter()->redirectUrl($values['backlink']);
			}else{
				$this->getPresenter()->redirect('this');
			}
			
		} catch ( NAuthenticationException $e ) {
			$form->addError ( $e->getMessage () );			
		}
		
		
	}
	
	function createComponent($name) {
		switch ($name) {
			case 'loginForm':
				$form = new NAppForm();
				$form->getElementPrototype()->addClass('ajax');
				$form->addText('login', _('Prihlasovacie meno'))
					->addRule(NFORM::FILLED, _('Prihlasovacie meno musí byť vyplnené'));
				
				$form->addPassword('password',  'Heslo')
					->addRule(NFORM::FILLED, _('Heslo musí byť vyplnené.') );

				$form->addSubmit('btn_submit', _('Prihlásiť sa'));
				$form->addHidden('backlink');

				$form->onSuccess[] = array( 
					//new Front_AuthentificationPresenter( $this->getPresenter()->getContext() )
					$this, 'onSubmitAuthenticate');
				$this->addComponent($form, $name);
				break;
			
			case 'cartLoginForm':
				$form = new NAppForm();
				$form->getElementPrototype()->addClass('ajax');
				$form->addText('login', _('Prihlasovacie meno'))
					->addRule(NFORM::FILLED, _('Prihlasovacie meno musí byť vyplnené'));
				
				$form->addPassword('password',  'Heslo')
					->addRule(NFORM::FILLED, _('Heslo musí byť vyplnené.') );

				$form->addSubmit('btn_submit', _('Prihlásiť sa'));
				$form->addHidden('backlink');

				$form->onSuccess[] = array( 
					//new Front_AuthentificationPresenter( $this->getPresenter()->getContext() )
					$this, 'onSubmitAuthenticate');
//				dump($form);exit;
				$this->addComponent($form, $name);
				break;
			
			default:
				return parent::__construct();
			break;
		};
	}
}
