<?php
	@error_reporting(E_ALL ^ E_NOTICE);

	require_once('../classes/Utils/JSCompressor.php');

	// Set the error reporting to minimal.
	@error_reporting(E_ERROR | E_WARNING | E_PARSE);

	$classes = getParam("classes");

	$compressor = new Moxiecode_JSCompressor(array(
		'expires_offset' => 3600 * 24 * 10,
		'disk_cache' => true,
		'cache_dir' => '_cache',
		'gzip_compress' => true,
		'remove_whitespace' => true,
		'charset' => 'UTF-8'
	));

	// Load classes
	$classes = explode(',', $classes);

	foreach ($classes as $class) {
		$file = strtolower(str_replace(".", "/", $class));
		$file = preg_replace('/\\/+/', '/', $file) . ".js";

		$compressor->addFile($file);
	}

	$compressor->compress();

	// * * Functions

	function getParam($name, $def = false) {
		if (!isset($_GET[$name]))
			return $def;

		return preg_replace("/[^0-9a-z\.,_]+/i", "", $_GET[$name]); // Remove anything but 0-9,a-z,-_
	}
?>