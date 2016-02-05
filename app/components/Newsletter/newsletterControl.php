<?php
class NewsletterException extends Exception{}

class NewsletterControl extends NControl {

	public $_table = 'newsletter_emails'; 
	public function render()
	{
		
		$template = $this->template;
		
		$template->setFile(dirname(__FILE__) . '/default.phtml');
			
		$template->newsletterform = $this['newsletterform'];
		$template->render();
	}
	
	protected function createComponent($name){
		
		switch($name){
			default:
				
				parent::createComponent($name);				
				break;
				
			case 'newsletterform':
				
				$form = new MyForm;
				$form->getElementPrototype()->class = 'ajax';
				$form->addText('email', 'Váš email')
					->addRule(NAPPFORM::FILLED, 'Email musí byť vyplnený.')
						->addRule(NAPPFORM::EMAIL, 'Zadaný email nieje v správnom tvare.');
						
					;
					
//				$form['email']->getLabelPrototype()->class = 'inlined';
//				$form['email']->getLabelPrototype()->id = 'emaillabel';
				$form->addSubmit('sendnewsletter', 'Ok');
				$form->onSuccess[] = array($this, 'sendToEmailRegisterToNewsletter');;
				
				
				return $form;
				break;
		}
	}
	
	public function sendToEmailRegisterToNewsletter(NForm $form){
		$values = $form->getValues();
		
		if(NewsletterModel::add($values)){
			$this->flashMessage('Váš Email bol úspešne pridaný.');
		}else{
			$form->addError('Váš email sa už v databáze nachádza.');			
		};		
		
		$this->invalidateControl('newsletter');
		
	}
}