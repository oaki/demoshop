<?php

class Admin_HomepagePresenter extends Admin_BasePresenter
{
	function startup() {
		$this->redirect(':Admin:Eshop:default');
	}
}
