<?php
class Page extends NObject{
	private static $instance;
	private $session;
	private $flashes;
	
	private function __construct(){
		$this->session = NEnvironment::getSession ( 'Page' );
		$this->flashes = NEnvironment::getSession ( 'flash' );
	}
	
	public static function getSession() {
		return self::getInstance()->session;
	}
	
	public static function addFlash($message, $type = NULL) {
		self::getInstance()->flashes[] =  (object) array(
			'message' => $message,
			'type' => $type,
		);
		
		self::getInstance()->flashes->setExpiration(2);
	}
	
	public static function getFlashes() {
		return self::getInstance()->flashes;
	}
	
	public static function getInstance() {
		if (self::$instance === NULL) {
			return self::$instance = new self ();
		} else {
			return self::$instance;
		}
	}
	
	 
}