<?php
/**
 * GUI for Acl
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI for Acl
 */

/**
 * Presenter for unauthorized access
 * 
 */
class Acl_DeniedPresenter extends Acl_BasePresenter
{
	function  startup() {
		parent::startup();
		$this->redirect(':Admin:Login:');
	}

}
