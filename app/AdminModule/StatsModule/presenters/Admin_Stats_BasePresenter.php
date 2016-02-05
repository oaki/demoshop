<?php

/**
 * Description of Admin_Setting_BasePresenter
 *
 * @author oaki
 */

abstract class Admin_Stats_BasePresenter extends Admin_BasePresenter {

	/**
	 * Checks authorization.
	 * @return void
	 */
	function startup(){
		parent::startup();	
		
		if( !$this->user->isAllowed('cms', 'edit') ){
			throw new \Nette\Security\AuthenticationException("Statistic Forbidden");
		}
	}

}