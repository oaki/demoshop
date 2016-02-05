<?php
class ContactFormControl extends NControl{

	function render($id_node){

		$template = $this->template;
		$template->setFile(dirname(__FILE__).'/default.phtml');
		$template->c = $this->get($id_node);
		
//		$param = array('id_node'=>$id_node);
		$template->id_node = $id_node;
		$this['form']['id_node']->setValue($id_node);
		echo $template;
	}

	function get($id_node){
		return dibi::fetch("SELECT * FROM [contact_form] WHERE id_node = %i", $id_node);
	}




	function createComponentForm($name){
		$form = new MyForm($this, $name);
		$form->addText('name', 'Meno:')->addRule(NForm::FILLED,'Meno a priezvisko musia byť vyplnené.');
		$form->addText('surname', 'Priezvisko:')->addRule(NForm::FILLED,'Meno a priezvisko musia byť vyplnené.');
		$form->addText('company', 'Firma:');
		$form->addText('email', 'Email:');
		$form->addText('tel', 'Telefón:');
		$form->addTextarea('text', 'Textová správa:')->addRule(NForm::FILLED,'Správa musí byť vyplnená.');
		$form->addSubmit('btn_form','Odoslať správu');
		$form->addHidden('id_node');
		$form->onSuccess[] = callback($this, 'handleSend');

		return $form;
	}

	function handleSend(NFORM $form){

		$values = $form->getValues();

		$contact_val = $this->get($values['id_node']);
		if( !$contact_val ){
			$this->getPresenter()->flashMessage('Formulár nepodarilo odoslať. Skúste prosím neskôr.');
		}

		$template = $this->template;
		$template->setFile(dirname(__FILE__).'/email.phtml');
		$template->values = $values;
		$mail = new MyMail();

		if($contact_val['email']!=''){
			$mail->addTo( $contact_val['email'] );
		}

		$mail->addBcc( 'form@vizion.sk' );

		$mail->setTemplate($template);
		
		$mail->setSubject( $contact_val['email_subject']);
		
		$mail->send();

		$this->getPresenter()->flashMessage('Formulár bol úspešne odoslaný. Čoskoro Vás budeme kontaktovať.');
		$this->getPresenter()->redirect('this');

	}
}