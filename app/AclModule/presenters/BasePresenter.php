<?php

/**
 * Description of BasePresenter
 *
 * @author oaki
 */
abstract class Acl_BasePresenter extends BasePresenter {

	public $cache;
	/**
	 * (non-phpDoc)
	 *
	 * @see Nette\Application\Presenter#startup()
	 */
	public function startup() {
		parent::startup();

		$this['header']['css']->addFile('acl/acl.css');
		$this['header']['js']->addFile('acl/create-key.js');

		
		$this->cache = NEnvironment::getCache();
		
	}
	function  beforeRender() {
		parent::beforeRender();
		$this->template->current = $this->getPresenter()->getName();
	}
        
}