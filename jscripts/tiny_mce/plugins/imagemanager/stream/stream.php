<?php
/**
 * stream.php
 *
 * @package MCManager.stream
 * @author Moxiecode
 * @copyright Copyright © 2007, Moxiecode Systems AB, All rights reserved.
 */

// Use install
if (file_exists("../install"))
	die('{"result":null,"id":null,"error":{"errstr":"You need to run the installer or rename/remove the \\"install\\" directory.","errfile":"","errline":null,"errcontext":"","level":"FATAL"}}');

error_reporting(E_ALL ^ E_NOTICE);

require_once("../classes/Utils/JSON.php");
require_once("../classes/Utils/Error.php");

@set_time_limit(5*60); // 5 minutes execution time

$MCErrorHandler = new Moxiecode_Error(false);
set_error_handler("StreamErrorHandler");

require_once("../includes/general.php");
require_once("../classes/ManagerEngine.php");

$cmd = getRequestParam("cmd", "");

if ($cmd == "")
	die("No command.");

$chunks = explode('.', $cmd);

$type = $chunks[0];
$method = $cmd = $chunks[1];

// Clean up type, only a-z stuff.
$type = preg_replace("/[^a-z]/i", "", $type);

if ($type == "")
	die("No type set.");

// Include Base and Core and Config.
$man = new Moxiecode_ManagerEngine($type);

require_once($basepath ."CorePlugin.php");
require_once("../config.php");

$man->dispatchEvent("onPreInit", array($type));
$config = $man->getConfig();

// Include all plugins
$pluginPaths = $man->getPluginPaths();

foreach ($pluginPaths as $path)
	require_once("../". $path);

// Dispatch onAuthenticate event
if ($man->isAuthenticated()) {
	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		$args = $_GET;

		// Dispatch event before starting to stream
		$man->dispatchEvent("onBeforeStream", array($cmd, &$args));

		// Check command, do command, stream file.
		$man->dispatchEvent("onStream", array($cmd, &$args));

		// Dispatch event after stream
		$man->dispatchEvent("onAfterStream", array($cmd, &$args));
	} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$args = array_merge($_POST, $_GET);
		$json = new Moxiecode_JSON();

		// Dispatch event before starting to stream
		$man->dispatchEvent("onBeforeUpload", array($cmd, &$args));

		// Check command, do command, stream file.
		$result = $man->executeEvent("onUpload", array($cmd, &$args));

		// Flash can't get our nice JSON output, so we need to return something... weird.
		if (isset($_GET["format"]) && ($_GET["format"] == "flash")) {
			if ($result->getRowCount() > 0) {
				$row = $result->getRow(0);

				switch ($row["status"]) {
					case "OK":
						// No header needed, 200 OK!
					break;

					case "DEMO_ERROR":
					case "ACCESS_ERROR":
					case "MCACCESS_ERROR":
					case "FILTER_ERROR":
						header("HTTP/1.1 405 Method Not Allowed");
						die('status=' . $row["status"]);
					break;

					case "OVERWRITE_ERROR":
						header("HTTP/1.1 409 Conflict");
						die('status=' . $row["status"]);
					break;

					case "RW_ERROR":
						header("HTTP/1.1 412 Precondition Failed");
						die('status=' . $row["status"]);
					break;

					case "SIZE_ERROR":
						header("HTTP/1.1 414 Request-URI Too Long");
						die('status=' . $row["status"]);
					break;

					default:
						header("HTTP/1.1 501 Not Implemented");
						die('status=' . $row["status"]);
				}
			}

		} else {
			// Output JSON function
			$data = $result->toArray();
			echo '<html><body><script type="text/javascript">parent.handleJSON({method:\'' . $method . '\',result:' . $json->encode($data) . ',error:null,id:\'m0\'});</script></body></html>';
		}

		// Dispatch event after stream
		$man->dispatchEvent("onAfterUpload", array($cmd, &$args));
	}
} else {
	if (isset($_GET["format"]) && ($_GET["format"] == "flash"))
		header("HTTP/1.1 405 Method Not Allowed");

	die('{"result":{login_url:"' . addslashes($config["authenticator.login_page"]) . '"},"id":null,"error":{"errstr":"Access denied by authenicator.","errfile":"","errline":null,"errcontext":"","level":"AUTH"}}');
}
?>