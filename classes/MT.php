<?
class MTException extends Exception{}

class MT extends NObject {
	private $css = array ();
	
	private $scripts = array ();
	
	private $textScripts = array ();
	
	private $content = '';
	
	private $var = array ();
	
	private $template;
	
	private $header_template;
	
	private $topmenu_template;
	
	private $templateFile = array();
	
	private $panes = array();
	
	private static $instance;
	
	private function __construct() {
		$this->template = new NFileTemplate ();
		$this->template->warnOnUndefined = false;
		$this->template->registerFilter ( new NLatteFilter() );
		
		
		// template headeru		 
		$this->header_template = clone $this->template;
		
		// template topmenu aj so submenu
		$this->topmenu_template = clone $this->template;
		$this->topmenu_template->submenu = array();
	}
	
	public static function getInstance() {
		if (self::$instance === NULL) {
			return self::$instance = new MT ();
		} else {
			return self::$instance;
		}
	}
	
	
	
	
	public static function addCss($css, $key = NULL) {
		if(!isset(self::getInstance ()->header_template->css))
			self::getInstance ()->header_template->css = array();
		self::getInstance ()->header_template->css [$key] = $css;
	}
	
	public static function addScripts($script, $key = NULL) {
		if(!isset(self::getInstance ()->header_template->scripts))
			self::getInstance ()->header_template->scripts = array();
		self::getInstance ()->header_template->scripts [$key] = $script;
	}
	
	public static function addTextScripts($script, $key = NULL) {
		if(!isset(self::getInstance ()->header_template->textScripts))
			self::getInstance ()->header_template->textScripts = array();
		
		self::getInstance ()->header_template->textScripts [$key] = $script;
	}
	
	
	
	
	public static function addVar($template_name, $key = NULL, $value) {
		$template = self::getInstance()->templateFile;
		if(isset($template[$template_name])){			
			$template[$template_name]->add($key,$value);
		}
	}
	
	
	public static function addTemplate($file, $key) {
		self::getInstance()->templateFile[$key] = clone self::getInstance ()->template;
		self::getInstance()->templateFile[$key]->setFile ( $file );		
	}
	
	public static function addForm($key,  $form) {
		self::getInstance()->templateFile[$key] = $form;
	}
	
	public static function renderCurrentTemplate($key, $clear = true){
		$t = self::getInstance()->templateFile[$key];
		
//		print_r($t);
		
//		if( (int)(!$t) ){
//			throw new MTException('Template doesn\'t exist');
//		}
			
		$return = (string)$t;
		if($clear)
			unset(self::getInstance()->templateFile[$key]);
		return $return;
	}
	
	public static function addContent($content, $key) {
		$t = self::getInstance ()->template;
		$t->setParameters(array($key=>$content));
	}
	
	public static function renderHeader() {
		$t = self::getInstance()->header_template;
		$t->setFile ( WWW_DIR . '/templates/header.phtml' );
		
		return  (string)$t;
	}
	
	public static function renderBottom() {
		$t = clone self::getInstance ()->template;
		$t->setFile ( APP_DIR . '/templates/admin/bottom.phtml' );
		$t->version = NEnvironment::getVariable('QUIS_VERSION');
		$t->project_name = NEnvironment::getVariable('project_name');
		
		return (string)$t;
	}
	
	public static function renderTopMenu() {
		
		self::getInstance()->topmenu_template->setFile ( APP_DIR . '/templates/admin/menu/menuHolder.phtml' );
		
		$session = NEnvironment::getSession ( 'page' );
		
		self::getInstance()->topmenu_template->langs = Setting::getLangs();
		
		self::getInstance()->topmenu_template->section = $session['section'];
//		$t->setFile ( APP_DIR . '/templates/admin/menu/menuHolder.phtml' );

		return (string)self::getInstance()->topmenu_template;
		
	}
	
	public static function addToSubMenu($href, $value) {
		self::getInstance()->topmenu_template->submenu[] = array('href'=>$href, 'name'=>$value);
	}

	
	public static function renderContentHolder() {
		
		
		if(isset(self::getInstance ()->templateFile) AND count(self::getInstance ()->templateFile)>0){
			foreach(self::getInstance ()->templateFile as $key=>$t){

	//			NDebug::dump($t);
				if(!isset(self::getInstance()->content))
					self::getInstance()->content = '';
				
//				dump((string)$t);
				self::getInstance()->content .= (string)$t;					
			}	
		}
		return self::getInstance ()->content;
		
	}
	
	public static function addToPanes($id, $content) {
		self::getInstance ()->panes[] = array('id'=>$id, 'content'=>$content);
	}
	
	public static function render() {
		
		$t = clone self::getInstance ()->template;
		
		$t->setFile ( APP_DIR . '/templates/admin/layout.phtml' );
		
		
		$t->header = self::renderHeader ();
		
		$t->panes = self::getInstance ()->panes;
		
		$t->footer = self::renderBottom ();
		$t->topMenu = self::renderTopMenu ();
		
		$t->flashes = Page::getFlashes();
		
//		$t->leftHolder = self::renderLeftHolder ();
		$t->contentHolder = self::renderContentHolder ();
//		print_r($t);

		echo $t->render ();
	}
}