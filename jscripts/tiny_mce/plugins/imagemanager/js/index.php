<?php
	// Use installer
	if (file_exists("../install"))
		die("alert('You need to run the installer or rename/remove the \"install\" directory.');");

	error_reporting(E_ALL ^ E_NOTICE);

	require_once("../includes/general.php");
	require_once('../classes/Utils/JSCompressor.php');

	$compress = true;

	// Some PHP installations seems to
	// output the dir rather than the current file here
	// it gets to /js/ instead of /js/index.php
	$baseURL = $_SERVER["PHP_SELF"];

	// Hmm, stange?
	if (strpos($baseURL, 'default.php/') > 0 || strpos($baseURL, 'index.php/') > 0)
		$baseURL = $_SERVER["SCRIPT_NAME"];

	// Is file, get dir
	if (getFileExt($baseURL) == "php")
		$baseURL = dirname($baseURL);

	// Remove trailing slash if it has any
	if ($baseURL && $baseURL[strlen($baseURL) - 1] == '/')
		$baseURL = substr($baseURL, 0, strlen($baseURL)-1);

	// Remove any weird // or /// items
	$baseURL = preg_replace('/\\/+/', '/', $baseURL);

	if ($compress) {
		$compressor = new Moxiecode_JSCompressor(array(
			'expires_offset' => 3600 * 24 * 10,
			'disk_cache' => true,
			'cache_dir' => '_cache',
			'gzip_compress' => true,
			'remove_whitespace' => true,
			'charset' => 'UTF-8'
		));

		// Compress these
		$compressor->addFile('mox.js');
		$compressor->addFile('gz_loader.js');
		$compressor->addContent("mox.defaultDoc = 'index.php';");
		$compressor->addContent('mox.findBaseURL(/(\/js\/|\/js\/index\.php)$/);');
		$compressor->compress();

		die;
	} else {
		header("Content-type: text/javascript");
		require_once('mox.js');
		echo "\nmox.defaultDoc = 'index.php';\n";
		echo "\nmox.findBaseURL(/(\/js\/|\/js\/index\.php)$/);\n";
	}
?>