<?php



class MyForm extends NAppForm{

	function  __construct(IComponentContainer $parent = NULL, $name = NULL) {
		NForm::extensionMethod('addDateTimePicker', 'MyForm::Form_addDateTimePicker');

		parent::__construct($parent, $name);
		
		$t = NEnvironment::getApplication()->getPresenter()->getService('translator');
		$t->setLang(NEnvironment::getApplication()->getPresenter()->lang);
		
		$this->setTranslator( $t );	
		
		NFormContainer::extensionMethod('NFormContainer::addRecaptcha', array('reCAPTCHA', 'addRecaptcha'));
	}

	static function Form_addDateTimePicker(NForm $_this, $name, $label, $cols = NULL, $maxLength = NULL)
	{
	   return $_this[$name] = new DateTimePicker($label, $cols, $maxLength);
	}
}