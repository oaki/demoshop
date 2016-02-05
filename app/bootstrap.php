<?php
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

function set_magic_quotes_runtime(){
    return false;
}
// iba kvolyufilenode, ktore sa pouziva aj pre nette aj pre stare CMS
define('_NETTE_MODE', true);

// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
//require LIBS_DIR . '/nette.min.php';
require LIBS_DIR . '/nette.min.php';
require LIBS_DIR . '/shortcuts.php';

// Configure application
$configurator = new NConfigurator;

// Enable Nette Debugger for error visualisation & logging
$_ips = array(
    '127.0.0.1',
    '82.119.100.182s',
    '99.38.224.183'

);
$ip = NULL;

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
    list($ip) = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
if (in_array($ip, $_ips)) {
    $configurator->setProductionMode(false);
};
unset($_ips);

$configurator->enableDebugger(dirname(__FILE__) . '/log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(dirname(__FILE__) . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->addDirectory(LIBS_DIR)
    ->addDirectory(WWW_DIR . '/require_modules')
    ->addDirectory(WWW_DIR . '/classes')
    ->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(dirname(__FILE__) . '/config/config.neon');
$configurator->addConfig(dirname(__FILE__) . '/config/config.db.neon');
$configurator->addParameters(array('logDir' => LOG_DIR));

$container = $configurator->createContainer();

//$container->session->setExpiration('+ 30 days');

//$container->addService('MyMemcache', function($container) {	
//	$journal = $container->getService('nette.cacheJournal');
//	$mem = new NMemcachedStorage('localhost', 11211, '', $journal);
//	
//	return new NCache($mem,'MyMemcache');
//}, NULL);

if (!$configurator->isProductionMode()) {

    CallbackPanel::register();
}


//print_r($container->parameters['database']);exit;
try {
    dibi::connect($container->parameters['database']);


} catch (Exception $e) {
    echo "Nepodarilo sa pripojit";
    exit ();
}

$session = NEnvironment::getSession('cart');

if (!isset($session->products)) {
    $session->products = array();
}


NRoute::addStyle('lang', NULL);
NRoute::setStyleProperty('lang', NRoute::PATTERN, '[a-z0-9]{1,2}'); // tohle je mozna i ted zbytecne,

NRoute::addStyle('paginator-page', NULL);
NRoute::setStyleProperty('paginator-page', NRoute::PATTERN, '[0-9]{1,5}'); // tohle je mozna i ted zbytecne,


//NRoute::$styles['id_category'] = array(
//	NRoute::PATTERN => '.*?',
//);


NRoute::addStyle('#cat');
NRoute::setStyleProperty('#cat', NRoute::PATTERN, '[\/a-z0-9^-]+');
NRoute::setStyleProperty('#cat', NRoute::FILTER_IN, callback('CategoryModel::slugToId'));
NRoute::setStyleProperty('#cat', NRoute::FILTER_OUT, callback('CategoryModel::idToSlug'));


$container->router[] = new NRoute('[<lang>/]<id #cat>', array(
    'module' => 'Front',
    'presenter' => 'Eshop',
    'action' => 'default',
    'lang' => 'sk'
));


/*
 * PRODUCT
 */

NRoute::addStyle('#product');
NRoute::setStyleProperty('#product', NRoute::PATTERN, '[a-z0-9^-]+');
NRoute::setStyleProperty('#product', NRoute::FILTER_IN, callback('ProductModel::slugToId'));
NRoute::setStyleProperty('#product', NRoute::FILTER_OUT, callback('ProductModel::idToSlug'));


$container->router[] = new NRoute('[<lang>/]<id_category #cat>/<id #product>.html', array(
    'module' => 'Front',
    'presenter' => 'Product',
    'action' => 'default',
    'lang' => 'sk'
));


//
//$page = NEnvironment::getService('page');
//   'id' => array(
//		NRoute::PATTERN=>'.*?',
//        NRoute::FILTER_IN => function($url) {
//            $p = DiscountModel::slugToId($url);
//			if(!$p)
//				return NULL;
//			
//			return $p;
//        },
//        NRoute::FILTER_OUT => function($id) {            
//            $p = DiscountModel::idToSlug($id);
//			if(!$p)
//				return NULL;
//			
//			return $p;
//        }
//    ),

/*
 * PAGE
 */
$page = NEnvironment::getService('Page');

NRoute::addStyle('#page');
NRoute::setStyleProperty('#page', NRoute::PATTERN, '[\/a-z0-9^-]+');
//NRoute::setStyleProperty('#page', NRoute::PATTERN, '.*?');
NRoute::setStyleProperty('#page', NRoute::FILTER_IN, function ($url) use ($page) {
        return $page->slugToId($url);
    });
NRoute::setStyleProperty('#page', NRoute::FILTER_OUT, function ($url) use ($page) {
        return $page->idToSlug($url);
    });


$container->router[] = new NRoute('[<lang>/]<id #page>', array(
    'module' => 'Front',
    'presenter' => 'Page',
    'action' => 'default',
    'lang' => 'sk'
));


/*
 * ARTICLE
 */
$article = NEnvironment::getService('Article');

NRoute::addStyle('#article');
NRoute::setStyleProperty('#article', NRoute::PATTERN, '[a-z0-9^-]+');
NRoute::setStyleProperty('#article', NRoute::FILTER_IN, function ($url) use ($article) {
        return $article->slugToId($url);
    });
NRoute::setStyleProperty('#article', NRoute::FILTER_OUT, function ($url) use ($article) {
        return $article->idToSlug($url);
    });

//tuto niekedy moze byt lomitko, asi ak upravim route pre page a nebude koncit lomitkom
$container->router[] = new NRoute('[<lang>/]<id_menu_item #page>/<id #article>.html', array(
    'module' => 'Front',
    'presenter' => 'Article',
    'action' => 'default',
    'lang' => 'sk'
));
//
//
//	


$container->router[] = new NRoute('[<lang>/]vyhladavanie/[<q>][/strana-<paginator-page>]', array(
    'module' => 'Front',
    'presenter' => 'Search',
    'action' => 'default',
    'id' => NULL,
    'lang' => 'sk',
    'paginator-page' => NULL
));

//POZOR pre vymenenie id_product_template_group je pouzita tato routa, lebo tabella neviem pracovat dobre s parametrami
//prepise http://demoeshop.vizion.sk/admin/product/edit/1?id_product_template_group=6 
// na http://demoeshop.vizion.sk/admin/product/edit/1_6

//NRoute::addStyle('id_product_template_group', NULL);
//NRoute::setStyleProperty('id_product_template_group', NRoute::PATTERN, '[0-9]{1,5}');
//
//
//$container->router[] = new NRoute('[<lang>/]<module>/<presenter>/<action>/group_<id_product_template_group>/<id>', array(
//	'module'=>'Front',
//	'presenter' => 'Homepage',
//	'action' => 'default',	
//	'lang' => 'sk',
//	'id_product_template_group'=>NULL,
//	'id' => NULL,
//));

$container->router[] = new NRoute('sitemap.xml', 'Front:Feed:sitemap');
$container->router[] = new NRoute('rss.xml', 'Front:Feed:rss');


$container->router[] = new NRoute('[<lang>/]<module>/<presenter>/<action>/<id>', array(
    'module' => 'Front',
    'presenter' => 'Homepage',
    'action' => 'default',
    'id' => NULL,
    'lang' => 'sk'
));


// Configure and run the application!
$container->application->run();