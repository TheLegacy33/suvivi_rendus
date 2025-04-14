<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API langues / pays / devises
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'changeUserPrefs':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'result' => 'error']));
				}else{
					$idUser = $_POST['userId'];
					$newLocaleId = intval($_POST['newLocale']);
					$newLocale = DAOLocale::getById($newLocaleId);
					$newCurrencyCode = $_POST['newCurrency'];
					$newCurrency = DAODevises::getByNomAbrege($newCurrencyCode);
					$newUnitId = intval($_POST['newUnit']);
					$newUnit = DAOUnite::getById($newUnitId);
					if ($idUser == 0 and !Session::getActiveSession()->isUserLogged()){
						setDefaultCookies($newLocale, $newCurrency, $newUnit);
						http_response_code(200);
						print(json_encode(['userUpdated' => true, 'result' => 'success']));
					}else{
						$user = DAOUser::getById($idUser);
						$user->setLocaleTrad($newLocale);
						$user->setDevisePreferee($newCurrency);
						$user->setPreferedUnit($newUnit);
						if (DAOUser::updateAllPrefs($user)){
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
