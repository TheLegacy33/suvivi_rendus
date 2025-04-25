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
