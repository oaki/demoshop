<?php

class CartLoginControl extends UserProfilControl{
	
		
	
	function render(){
		$this->template->setFile( dirname(__FILE__).'/cart-login.latte');
		$this->template->render();
	}
	
}
