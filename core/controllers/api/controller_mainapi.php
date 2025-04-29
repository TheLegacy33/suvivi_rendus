<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */

	use Random\RandomException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){

		case 'other':
			{
				break;
			}
		default:
			{
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}
