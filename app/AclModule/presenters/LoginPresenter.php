<?php
/**
 * GUI for Acl
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI for Acl
 */


/**
 * Login
 *
 */
class Acl_LoginPresenter extends Acl_BasePresenter
{
    /******************
     * Default
     ******************/
    public function renderDefault() {
    }

    protected function createComponentLogin($name) {
        $form = new NAppForm($this, $name);
        $renderer = $form->getRenderer();
        $renderer->wrappers['label']['suffix'] = ':';
        //$form->addGroup('Login');
        $form->addText('name', 'Name', 30)
            ->addRule(NForm::FILLED, 'You have to fill name.');
		
        $form->addPassword('password', 'Password', 30)
            ->addRule(NForm::FILLED, 'You have to fill password.');
        //$form->addProtection('Security token did not match. Possible CSRF attack.');
        $form->addSubmit('signon', 'Sign on');
        $form->onSuccess[] = array($this, 'SignOnFormSubmitted');
    }
	
    public function SignOnFormSubmitted(NAppForm $form) { // Login form submitted
        try {
            $this->user->login($form['name']->getValue(), $form['password']->getValue());
            $this->user->setExpiration(30*60, TRUE, TRUE); // set expiration 30 minuts
            if (ACL_CACHING) {
               unset($this->cache['gui_acl']); // invalidate cache
            }
            $this->redirect('Default:');
        } catch (NAuthenticationException $e) {
            $form->addError($e->getMessage());
            $form->setValues(array('name' => ''));
        }
    }

    /******************
     * Logout
     ******************/
    public function actionLogout() {
        $this->flashMessage('Sing off');
        $this->user->signOut(TRUE); // TRUE - delete identity
        $this->redirect('Default:');
    }
}
