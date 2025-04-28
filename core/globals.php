<?php

	const APP_NAME = 'APP-ETUDIANTS';
	const APP_VERSION = '0.0.1';
	const URL_BASE = 'http://studentapp.local.net/';
	define("SHOW_OEUVRES", getenv('SHOW_OEUVRES') == '1');
	define("EXTERNAL_URL", getenv('EXTERNAL_URL'));
	define("HTML_DIR_INDEX", rtrim(dirname($_SERVER['SCRIPT_NAME']), "/") . "/");
	const HTML_PUBLIC_DIR = HTML_DIR_INDEX; //.'public/';
	const HTML_PUBLIC_ASSETS_DIR = HTML_PUBLIC_DIR . 'assets/';
	const HTML_PUBLIC_STYLES_DIR = HTML_PUBLIC_DIR . 'styles/';
	const HTML_PUBLIC_IMAGES_DIR = HTML_PUBLIC_DIR . 'images/';
	const HTML_PUBLIC_LIBS_DIR = HTML_PUBLIC_DIR . 'libs/';
	const HTML_PUBLIC_SCRIPTS_DIR = HTML_PUBLIC_DIR . 'scripts/';
	const HTML_PUBLIC_UPLOADS_DIR = HTML_PUBLIC_DIR . 'uploads/';
	const HTML_PUBLIC_OUTIL_DIR = HTML_PUBLIC_DIR . 'outils/';
	const PHP_PUBLIC_DIR = PHP_DIR . 'public/';
	const PHP_PUBLIC_ASSETS_DIR = PHP_PUBLIC_DIR . 'assets/';
	const PHP_PUBLIC_SCRIPTS_DIR = PHP_PUBLIC_DIR . 'scripts/';
	const PHP_PUBLIC_IMAGES_DIR = PHP_PUBLIC_DIR . 'images/';
	const PHP_UPLOAD_DIR = PHP_PUBLIC_DIR . 'uploads/';
	const PHP_UPLOAD_DIR_ARTISTES = PHP_UPLOAD_DIR . 'artistes/';
	const PHP_UPLOAD_DIR_USERS = PHP_UPLOAD_DIR . 'utilisateurs/';
	const PHP_EMAIL_TPL = PHP_DIR . 'core/views/template/mails/';
	const PHP_EMAIL_HTML_TPL = PHP_DIR . 'core/views/template/mails/html_templates/';

	define("DEV_MODE", getenv('DEV_MODE') == 'true');

	//Vérification de l'existence des répertoires et création si nécessaire
	if (!(file_exists(PHP_UPLOAD_DIR))){
		mkdir(PHP_UPLOAD_DIR, recursive: true);
		@chmod(PHP_UPLOAD_DIR, 0777);
	}elseif (!is_writable(PHP_UPLOAD_DIR)){
		@chmod(PHP_UPLOAD_DIR, 0777);
	}