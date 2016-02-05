<?php
function set_magic_quotes_runtime(){
	return false;
}

// Step 1: Load Nette Framework
require LIBS_DIR . '/nette.min.php';

define('_NETTE_MODE', false);


NDebugger::$strictMode = TRUE;
NDebugger::enable(false, APP_NETTE_DIR.'/log');

//NEnvironment::setVariable ( "tempDir", "%appNetteDir%/temp" );



// 2c) enable RobotLoader - this allows load all classes automatically
$loader = new NRobotLoader ();
$loader->setCacheStorage(new NFileStorage(TEMP_DIR));
$loader->addDirectory ( APP_NETTE_DIR );
$loader->addDirectory ( LIBS_DIR );
$loader->addDirectory ( WWW_DIR.'/require_modules' );
$loader->addDirectory ( WWW_DIR.'/classes' );
//$loader->addDirectory ( WWW_DIR.'/app/models' );
//$loader->addDirectory ( WWW_DIR.'/app/components' );
$loader->register ();

// 2b) load configuration from config.ini file
$config = NEnvironment::loadConfig(APP_NETTE_DIR.'/config/config.neon');

$neon = new NConfigNeonAdapter();
$n = $neon->load(APP_NETTE_DIR.'/config/config.db.neon');

$database = $n['common']['parameters'];
foreach($database as $k=>$p){
    NEnvironment::setVariable($k,$p);
}


//var_dump($d);exit;

//$config = NEnvironment::loadConfig(APP_NETTE_DIR.'/config/config.db.neon');

$session = NEnvironment::getSession ();
//$session->setSavePath(APP_NETTE_DIR . '/sessions');
//$session->setExpiration("1 day");
$session->start();

try {
	dibi::connect ( NEnvironment::getConfig()->database );
} catch ( Exception $e ) {
	// echo $e->getMessage();
	echo "Nepodarilo sa pripojit";
	exit ();
}


$cache = NEnvironment::getCache();
if (!isset($cache['acl'])) $cache['acl'] = new Acl();
//print_r($cache['acl']);
NEnvironment::getUser()->setAuthorizator($cache['acl']);
$user = NEnvironment::getUser();
$aclModel = new AclModel();

//NDebug::dump($aclModel->getRoles());
//NDebug::dump($aclModel->getResources());
//NDebug::fireLog($aclModel->getRules());
//Log::addGlobalLog();



