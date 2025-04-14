<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API convertisseur
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'changeUserCurrency':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'result' => 'error']));
				}else{
					$idUser = $_POST['userId'];
					$newCurrencyCode = $_POST['newCurrency'];
					$newCurrency = DAODevises::getByNomAbrege($newCurrencyCode);
					if ($idUser == 0 and !Session::getActiveSession()->isUserLogged()){
						$arr_cookie_options = array('expires' => time() + (86400 * 3650), 'path' => '/', 'domain' => '', // leading dot for compatibility or use subdomain
							'secure' => true,     // or false
							'httponly' => true,    // or false
							'samesite' => 'None' // None || Lax  || Strict
						);
						setcookie('userCurrency', json_encode($newCurrency), $arr_cookie_options);
						http_response_code(200);
						print(json_encode(['userUpdated' => true, 'result' => 'success']));
					}else{
						$user = DAOUser::getById($idUser);
						$user->setDevisePreferee($newCurrency);
						if (DAOUser::updateDevisePreferee($user)){
							http_response_code(200);
							print(json_encode(['userUpdated' => true, 'result' => 'success']));
						}else{
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
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