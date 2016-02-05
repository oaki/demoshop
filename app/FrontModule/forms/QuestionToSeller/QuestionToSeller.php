<?php

class QuestionToSeller extends MyForm{
	
	function __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		
		$this->builtForm();
	}
	
	
	function builtForm(){
		$this->addText('name', 'Vaše meno:')
			->addRule( self::FILLED,'Vaše meno musí byť vyplnené');
		
		$this->addText('email', 'Váš e-mail:')
				->addRule( self::EMAIL,'Email nie je v správnom tvare');
		
		$this->addText('phone', 'Váš telefón:');
		
		$this->addText('number', 'Napíšte hocijaké číslo:')
				->addRule(self::NUMERIC,'Napíšte hocijaké číslo');
		
		$this->addTextarea('text', 'Správa:')
			->addRule( self::FILLED,'Správa musí byť vyplnená');
		
		$this->addHidden('link');
			
		
		$this->addSubmit('btn', 'Odoslať');
		
		$this->addProtection();
		
		$this->onSuccess[] = array($this,'handleSubmit');
		
		
		
	}
	
	function handleSubmit( NForm $form){
		$values = $form->getValues();
		
		$template = $this->getPresenter()->createTemplate();
		
		$template->setFile(dirname(__FILE__).'/email.phtml');
		$template->values = $values;
		$template->form = $form;
		
		$conf = $this->getPresenter()->context->parameters;
		
		$mail = new MyMail();

		$mail->addTo( $conf['client_email'] );
		$mail->addBcc( 'form@vizion.sk' );

		$mail->setTemplate($template);
		
		$mail->setSubject( 'Formulár - Otázka na predajcu' );
		
		$mail->send();

		$this->getPresenter()->flashMessage('Formulár bol úspešne odoslaný. Čoskoro Vás budeme kontaktovať.');
		$this->getPresenter()->redirect('this');
	}
}