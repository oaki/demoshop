<?

ini_set('display_errors',1 );
ini_set('memory_limit','100M');

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__));

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '');

// absolute filesystem path to the application root
define('APP_NETTE_DIR', WWW_DIR . '/app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

//relative path to user template
define('TEMPLATE_DIR', '/templates/pitrade');

//absolute path to the template
define('ABS_TEMPLATE_DIR', WWW_DIR.TEMPLATE_DIR);

// absolute filesystem path to the temporary files
define('TEMP_DIR', APP_NETTE_DIR . '/temp');

define ('BASEDIR', '/');

define('LOG_DIR', WWW_DIR . '/app/log');

require LIBS_DIR . '/fixSessionPhp7.php';
require APP_NETTE_DIR.'/bootstrap_admin.php';


//-----------jazyk---------------------
if(isset($_GET['lang'])){
	NEnvironment::getSession('Page')->lang = $_GET['lang'];
}

if(NEnvironment::getSession('Page')->lang == ''){
	NEnvironment::getSession('Page')->lang = NEnvironment::getVariable ( 'ADMIN_DEFAULT_LANG' );
}


//if(!isset($_SESSION['lang'])){$_SESSION['lang']=ADMIN_DEFAULT_LANG;}


//-----------jazyk---------------------
//if(isset($_GET['id_menu'])){$_SESSION['id_menu']=$_GET['id_menu'];}
//if(!isset($_SESSION['id_menu'])){$_SESSION['id_menu']=0;}



MT::addCss('/templates/admin/css/index.css');
MT::addScripts('/jscripts/mootools/mootools-1.2.4-core-nc.js','mootools');
MT::addScripts('/jscripts/mootools/mootools-1.2.4.4-more.js','mootools_adds');
MT::addScripts('/jscripts/mootools/mootools_listener.js','mootools_listener');
MT::addScripts('/jscripts/tableHighlighter.js','tableHighlighter');

MT::addTextScripts('
     function confl(message, url) {
     	if(confirm(message)) location.href = url;
     }
','conf');


$auth = new Authentication ();
$session = NEnvironment::getSession("Authentication");

//NDebug::dump(NEnvironment::getUser()->getIdentity());
/*
 * Skontrolovanie prav na adresare a subory
 * v pripade nespravnych - opravenie
 */
if(isset($_GET['checkAndRepairPermission'])){
	echo 'Run checkAndRepairPermission';
	$error = array();
	if(!chmod("./app/temp/", 0777))
		$error[]= 'temp is not writeable '."/app/temp/";
		
	if(!chmod("./app/log/", 0777))
		$error[]= 'log is not writeable';
		
	if(!chmod("./app/sessions/", 0777))
		$error[]= 'session is not writeable';
		
	if(empty($error)){
		echo '<br>Succes';
	}else{
		foreach($error as $e){
			echo '<li>'.$e.'</li>';
		}
	}
}
//print_r($session);

$session = Page::getSession();

if(isset($_GET['section']))
	$session['section'] = $_GET['section'];


try{
	switch ($session ['section']) {
		default:
			$session['section'] = 'cms';
			
			$cms = new CMS();
			
			break;
		 
		case "eshop":
			$eshop = new EShop();
	//		Product::showAddProduct();
	//		MT::addContent('eshop');
			break;
		
		case "users" :
			$auth->showUsers ();
			break;
		
		case "visitors" :
			$v = new visitor_admin ();
			$v->showVisitor ();
			break;
		
		case "admin_modules" :
			$t = new type_modul ();
			$t->showForm ();
			break;
		
		case "log" :
			$l = new log ();
			$l->showLog ();
			break;
		
		case "file_manager" :
			MT::addTemplate(APP_DIR.'/templates/admin/fileManagerIFrame.phtml','fileManager');
			break;
	
		
		case "groups" :
			$a->showGroup ();
			break;
		
		case "newsletter" :
			Admin_Newsletter::show();
			break;
			
		case 'setting':
			$s = new Setting();
			
			$s->show();
			break;
			
			
		case 'comment':
			Admin_Comment::action();
			Admin_Comment::commentList();
		break;
	
	}
}catch(LogicException $e){
	MT::addTemplate(APP_DIR.'/templates/admin/error_message.phtml','error_message');
	MT::addVar('error_message','error', $e->getMessage());
}
MT::render();
