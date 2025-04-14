<?php

	error_reporting(E_ALL & ~ E_NOTICE);
	ini_set('display_errors', 1);

	chdir('../');
	define("PHP_DIR", getcwd().'/');
	require_once 'core/router.php';