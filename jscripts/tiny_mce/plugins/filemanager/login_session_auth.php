<?php

session_start();


//require APP_NETTE_DIR.'/bootstrap_admin.php';
//
//$auth = new Authentication ();

if(isset($_GET['return_url'])){
	$_SESSION['isLoggedIn'] = true;
	$_SESSION['user'] = array('id'=>'1');
	header("location: " . $_GET['return_url']);
}


//ini_set('display_errors',1 );
//ini_set('memory_limit','100M');
//
//// absolute filesystem path to the web root
//define('WWW_DIR', $_SERVER['DOCUMENT_ROOT']);
//
//// absolute filesystem path to the application root
//define('APP_DIR', WWW_DIR . '');
//
//// absolute filesystem path to the application root
//define('APP_NETTE_DIR', WWW_DIR . '/app');
//
//// absolute filesystem path to the libraries
//define('LIBS_DIR', WWW_DIR . '/vendor');
//
//
//
//define ('BASEDIR', '/');
//
//
//require APP_NETTE_DIR.'/bootstrap_admin.php';
//
//$auth = new Authentication ();
//
//if(isset($_GET['return_url'])){
//	$_SESSION['isLoggedIn'] = true;
//	$_SESSION['user'] = NEnvironment::getSession('Authentication')->login_form;
//	header("location: " . $_GET['return_url']);
//		exit;
//}
