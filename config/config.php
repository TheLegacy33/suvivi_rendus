<?php
	$config = array(
		"DB_HOST" => getenv('MYSQL_HOST'),
		"DB_PORT" => getenv('MYSQL_PORT'),
		"DB_NAME" => getenv('MYSQL_BDD'),
		"DB_USER" => getenv('MYSQL_USER'),
		"DB_PASSWORD" => getenv('MYSQL_PASS'),
		"EXTERNAL_URL" => getenv('EXTERNAL_URL') ?? 'https://studentapp.local.net',
		"DEV_MODE" => getenv('DEV_MODE') == 'true'
	);

	date_default_timezone_set('Europe/Paris');

	//	ini_set('memory_limit', '4096M');
	$arr_cookie_options = array('lifetime' => 0, 'path' => '/', 'domain' => '', // leading dot for compatibility or use subdomain
		'secure' => true,     // or false
		'httponly' => true,    // or false
		'samesite' => 'None' // None || Lax  || Strict
	);

	session_set_cookie_params($arr_cookie_options);