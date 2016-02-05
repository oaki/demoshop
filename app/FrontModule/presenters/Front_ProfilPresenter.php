<?php
class Front_ProfilPresenter extends Front_BasePresenter {

	/** @persistent */
	public $backlink;


	function  startup() {
		parent::startup();
		if($this->getAction() != 'registration' AND $this->getAction() != 'lostPassword'){
		    if( !NEnvironment::getUser()->isLoggedIn() ){
			    $this->flashMessage(_('Nie ste prihlásený.'));
			    $this->redirect('Eshop:homepage');
		    }
		}
	}

	function beforeRender(){
		parent::beforeRender();
		$this['header']['css']->addFile('profil.css');
	}

	function renderDefault(){
//		$this['userBaseForm']
		
	}

	
	function actionRegistration() {
		//ak je prihlaseny presmeruje ho na svoj profil
		if( $this->user->isLoggedIn()){ $this->redirect('Profil:default'); }
		
		
	}

	function actionOrder($id){
		$this->template->o = OrderModel::get($id, $this->user->getIdentity()->data['user_id']);
		if(!$this->template->o){ throw new NBadRequestException('Objednávka č. '.(int)$id.' neexistuje');}
		
		
	}

	function actionOrderList(){
		$datasource = dibi::query("
		    SELECT
			*			
		    FROM
			[order]			
		    WHERE
				id_user = %i",@$this->user->getIdentity()->data['user_id'],"
				AND deleted = 0
		    ORDER BY add_date DESC
		");
	    $this->template->list = $datasource->fetchAll();
	}
	
	function actionLogOut($backlink='Front_Homepage'){
		NEnvironment::getUser()->logout();
		
		$this->redirectUrl( $backlink );		
	}
	
	

	function sendLostPassword(NFORM $form){
		$values = $form->getValues();
		
		$user = UserModel::getFluent()->where("login = %s",$values['login'])->fetch(); 
		if(!$user){
			$form->addError(_('Prepáčte, zodpovedajúci používateľ nebol nájdený'));	
		}else{
			$this->flashMessage('Vaše nové heslo vám bude v krátkej dobe zaslané na "'.$values['login'].'".');
			
	        $template = $this->template;
	        $template->setFile( APP_DIR.'/FrontModule/templates/Profil/lostPasswordEmail.phtml' );
	        
	        
	        $new_password = Tools::random(8);
			
	        UserModel::update($user['user_id'], array('new_password'=>UserModel::getHash($new_password) ) );
	        
	        $template->new_password = $new_password;
	        
	        $mail = new MyMail();
	        $mail->addTo( $values['login'] );
	        
	        $mail->addBcc( NEnvironment::getVariable('error_email') );
	         
	        $mail->setSubject( _('Stratene heslo') );
	        $mail->setTemplate($template);
	        
	        $mail->send();
			
			$this->redirect('this');
			$this->terminate();		
		}		
	}


	protected function createComponent($name) {
		
		switch ($name) {
			case 'lostPasswordForm' :
		 
				$form = new NAppForm;
				$form->addText('login', 'Prihlasovacie meno/Email')
					->addRule(NFORM::FILLED,'Prihlasovacie meno musí byť vyplnené.');
					
//				$form->addText('email', 'Emailová adresa')
//					->addRule(NFORM::EMAIL,'Emailová adresa nie je v správnom tvare.');
//				
				$form->addSubmit('btn_submit_lost_password', 'Odoslať')
					->getControlPrototype()->class='classic-btn border-radius-2';
				
				$form->onSuccess[] = array($this, 'sendLostPassword');
				
				return $form;
				break;
				
			
				
			case 'registrationForm':
				$form = $this->createComponent('userBaseForm');
				$form['password']->addRule(NForm::FILLED, _('Heslo musí byť vyplnené'))
								 ->addRule(NForm::MIN_LENGTH, _('Minimálny počet znakov pre heslo je %s'), 5);

				$form->addGroup('');
				$form->addSubmit('btn_user_form', _('Registrovať') )
						->getControlPrototype()->class='classic-btn border-radius-2';

				$form->onSuccess[] = array($this, 'saveRegistration');
				return $form;
				break;

			case 'profilForm':
				
				$form = $this->createComponent('userBaseForm');

//				$form['btn_user_form']->setValues('Uložiť');
				$form->setDefaults($this->user->getIdentity()->data);
//				print_r($this->user->getIdentity()->data);
				$form->addGroup();
				$form->addSubmit('btn_user_form', _('Uložiť') )
						->getControlPrototype()->class='classic-btn border-radius-2';

				$form->onSuccess[] = array($this, 'saveProfil');
				return $form;
				break;
					
			default :				
				return parent::createComponent ( $name );
				break;
		}
	}


	function saveProfil(NFORM $form){
		$values = $form->getValues();

		$data = $this->user->getIdentity()->data;
//		print_r($data);exit;
		
		$user = UserModel::getFluent( false )->where(' login = %s', $values['login'],"AND login != %s",$data['login'])->fetch();

		try {
			if($user AND $user['login'] == $values['login'])
				throw new InvalidStateException( _('Uživateľ pod týmto prihlasovacím meno už existuje.') );

//			if($user AND $user['email'] == $values['email'])
//				throw new InvalidStateException( _('Emailová adresa sa už nachádza v databáze. ') );
//
			unset($values['passwordCheck']);
			unset($values['terms_and_conditions']);

			//ak nevyplni heslo, zostava stare
			if($values['password'] == '')
				unset($values['password']);
			

			UserModel::update($data['id'], $values);

			$values = UserModel::get($data['id']);
//			$this->user->getIdentity()->updating();
			foreach($values as $key => $value) // zmenime to i v aktualni identite, aby se nemusel prihlasovat znovu
			  $this->user->getIdentity()->$key = $value;

			$this->flashMessage("Váš profil bol aktualizovaný.");

			$this->redirect('this');
			
		} catch (InvalidStateException $e) {
			$form->addError($e->getMessage());
//			throw $e;
		}



	}

	function saveRegistration(NFORM $form){
		$values = (array)$form->getValues();
		
		$user = UserModel::getFluent( false )->where(' login = %s', $values['login'])->fetch();
		
		try {
			if($user AND $user['login'] == $values['login'])
				throw new InvalidStateException( _('Uživateľ pod týmto prihlasovacím meno už existuje.') );
				
//			if($user AND $user['email'] == $values['email'])
//				throw new InvalidStateException( _('Emailová adresa sa už nachádza v databáze. ') );
//		
			
			unset($values['passwordCheck']);
			unset($values['terms_and_conditions']);
			
			$values['activate'] = 1;
			
			//registrovany dostane automaticky 2% zlavu
			$values['discount'] = 2;
			UserModel::insert($values);
			
			$this->flashMessage("Registrácia je dokončená, účet aktivovaný a ste prihlásený na stránke.");
			
			$template = clone $this->template;
			$template->setFile( APP_DIR.'/FrontModule/templates/Profil/registrationConfirmEmail.phtml' );
			$template->values = $values;

			$mail = new MyMail();
			$mail->addTo( $values['login'] );
 
			$mail->addBcc( NEnvironment::getVariable('error_email') );

			$mail->setSubject( _('Informácie o účte') );
			$mail->setTemplate($template);

			$mail->send();
			
			$this->user->login ( $values ['login'], $values ['password'] );

			if($this->backlink !=''){
				
				$this->restoreRequest($this->backlink);
			}else{
				$this->redirect('Homepage:default');
			}
			
		} catch (InvalidStateException $e) {
			$form->addError($e->getMessage());
//			throw $e;
		}
				
		
		
	}
}