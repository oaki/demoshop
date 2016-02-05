<?php
/**
 * reCAPTCHA for Nette
 * ===================
 * 
 * Author:    Jake Cooney
 *            jake[at]jakecooney.com
 *            jakecooney.com
 * License:   CreativeCommons Attribution-Share Alike 3.0 Unported
 *            http://creativecommons.org/licenses/by-sa/3.0/ 
 * 
 * Revision #1 (2009-09-07)
 * 
**/  

class reCAPTCHA extends NTextBase
{
	public static $libPath;
	public static $publicKey = '6LdH8cYSAAAAAEbRxqSz-_b38NlPGRvBFN7pCJYK';
	public static $privateKey = '6LdH8cYSAAAAACmYhrIk7PAgTIanQMXHwzFlDjkW';
	
	protected $status;
	
	public function __construct($label = NULL)
	{
		if(!@require_once(self::$libPath))
			throw new Exception('The reCAPTCHA library is missing.');		
	
		$this->monitor('Form');
		parent::__construct($label);
		
		$this->control->type = 'text';
		$this->value = '';
		$this->control = NHtml::el();
		$this->status = NHtml::el('strong');
	}
	
	public static function addRecaptcha(NForm $form,$name,$label)
	{
		return($form[$name] = new self($label));
	}
	
	protected function attached($form)
	{
	}
	
	public function getControl()
	{
		$control = clone $this->control;
		$control->add($this->getRecaptchaHTML($error));
		$control->add($this->getError($error));
		return($control);
	}
	protected function getRecaptchaHTML(&$error)
	{
		return(recaptcha_get_html(self::$publicKey,$error));
	}
	protected function getError(&$error)
	{
		$status = clone $this->status;
		$status->class('error');
		$status->setText($error);
		return($status);
	}
	
	public function getInput()
	{
		$control = parent::getControl();
		$control->value = ($this->value === '') ? $this->emptyValue : $this->tmpValue;
		return($control);
	}
	
	
	public static function validate(IFormControl $control)
	{
		$response = recaptcha_check_answer(
			self::$privateKey,
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);
		return($response->is_valid);
	}
}

reCAPTCHA::$libPath = dirname(__FILE__).'/reCaptchaLib.php';

/** add Nette\FormContainer method */
NFormContainer::extensionMethod('NFormContainer::addRecaptcha', array('reCAPTCHA', 'addRecaptcha'));
?>