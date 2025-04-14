<?php

/**
 * @var string $section
 * @var string $action
 *
 * Controller pour les appels API langues
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
switch ($action) {
	case 'changeUserLocale': {
			if (empty($_POST)) {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'result' => 'error']));
			} else {
				$idUser = $_POST['userId'];
				$newLocaleId = intval($_POST['newLocale']);
				$newLocale = DAOLocale::getById($newLocaleId);
				if ($idUser == 0 and !Session::getActiveSession()->isUserLogged()) {
					$arr_cookie_options = array (
						'expires' => time() + (86400 * 3650),
						'path' => '/',
						'domain' => '', // leading dot for compatibility or use subdomain
						'secure' => true,     // or false
						'httponly' => true,    // or false
						'samesite' => 'None' // None || Lax  || Strict
					);
					setcookie('userLocale', json_encode($newLocale), $arr_cookie_options);
					setcookie('userLanguage', $newLocale->getLibelle(), $arr_cookie_options);
					http_response_code(200);
					print(json_encode(['userUpdated' => true, 'result' => 'success']));
				} else {
					$user = DAOUser::getById($idUser);
					$user->setLocaleTrad($newLocale);
					if (DAOUser::updateLocaleTrad($user)) {
						http_response_code(200);
						print(json_encode(['userUpdated' => true, 'result' => 'success']));
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}
	case 'traductionSearch': {
			if (empty($_GET)) {
				http_response_code(500);
				print(json_encode(['traductionUpdated' => false, 'result' => 'error']));
			} else {

				$code = $_GET['code'] ?? '00000';
				$libelle = Traducteur::getTraduction($code, $defaultLanguage);
				http_response_code(200);
				print(json_encode(['traductionFetched' => true, 'traductionLibelle' => $libelle, 'result' => 'success']));
			}
		}
	case 'other': {
			break;
		}
	default: {
			http_response_code(404);
			header('Content-Type: application/json; charset=utf-8');
			die(json_encode(['erreur' => 'Action inconnue']));
		}
}
