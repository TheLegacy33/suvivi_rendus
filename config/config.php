<?php
	$config = array(
		"DB_HOST" => getenv('MYSQL_HOST'),
		"DB_PORT" => getenv('MYSQL_PORT'),
		"DB_NAME" => getenv('MYSQL_BDD'),
		"DB_USER" => getenv('MYSQL_USER'),
		"DB_PASSWORD" => getenv('MYSQL_PASS'),
		"EXTERNAL_URL" => 'http://studentapp.local.net',
		"DEV" => isset($_ENV['DEV']) AND getenv('DEV') == 'true'
	);
