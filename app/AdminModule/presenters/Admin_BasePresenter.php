<?php

abstract class Admin_BasePresenter extends BasePresenter {

	function startup(){
		parent::startup();
		
		$cache = NEnvironment::getCache();
		if (!isset($cache['acl'])) $cache['acl'] = new Acl();
		
		NEnvironment::getUser()->setAuthorizator($cache['acl']);
		
		$user = NEnvironment::getUser();
		
		$aclModel = new AclModel();
		
		// user authentication


		if (!$this->user->isLoggedIn()) {
			$backlink = $this->application->storeRequest();
			$this->redirect(':Admin:Login:default', array('backlink' => $backlink, 'lang'=>$this->lang));
		}

		if( !$this->user->isAllowed('cms','edit') ){
			$this->flashMessage('Nemáte dostatočné prava.');
			$backlink = $this->application->storeRequest();
			$this->redirect(':Admin:Login:default', array('backlink' => $backlink, 'lang'=>$this->lang));
		}
		
//		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		

		$this['header']['css']->addFile('../templates/admin/css/index.css');
		$this['header']['css']->addFile('ajax.css');
		$this['header']['css']->addFile('../jscripts/jquery/flick/jquery-ui-1.8.6.custom.css');
		$this['header']['css']->addFile('../jscripts/jquery/tags/jquery.tagsinput.css');
		$this['header']['js']->addFile('jquery/tags/jquery.tagsinput.js');
		
//		$this['header']['js']->addFile('/jquery/jquery-1.4.2.js');
		$this['header']['js']->addFile('/jquery/jquery.nette.js');
		$this['header']['js']->addFile('/jquery/jquery.livequery.js');
		$this['header']['js']->addFile('jquery/jquery-ui-1.8.5.custom.min.js');
		$this['header']['js']->addFile('jquery/jquery.highlight.js');
		$this['header']['js']->addFile('jquery/jquery.easy-confirm-dialog.js');
		$this['header']['js']->addFile('confl.js');
		
//		$this['header']->setHtmlTag( NHtml::el('script type="text/javacript"')->add( 'alert(1)' ) );
		
	}


}


