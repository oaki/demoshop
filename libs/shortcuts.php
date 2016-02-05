<?php


/**
 * NDebugger::dump shortcut.
 */
function dd($var)
{
	foreach (func_get_args() as $arg) {
		NDebugger::dump($arg);
	}
	return $var;
}

function dde()
{
	foreach (func_get_args() as $arg) {
		NDebugger::dump($arg);
	}
	exit;
}

