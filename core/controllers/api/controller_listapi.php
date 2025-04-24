<?php
	/**
	 * @var string $section
	 * @var string $page
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */

	use Random\RandomException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'getclasses':
			{
				$idEcole = intval($_GET['idecole'] ?? 0);
				if ($idEcole > 0){
					$ecole = DAOEcoles::getById($idEcole);
					$lstClasses = DAOClasses::getAllByEcole($ecole);
					http_response_code(200);
					print(json_encode(['classesFetched' => true, 'content' => $lstClasses, 'status' => 200, 'result' => 'success']));

				}else{
					http_response_code(404);
					print(json_encode(['classesFetched' => false, 'content' => [], 'status' => 404, 'result' => 'error']));
				}
				break;
			}
		case 'getetudiants':
			{
				$idClasse = intval($_GET['idclasse'] ?? 0);
				if ($idClasse > 0){
					$classe = DAOClasses::getById($idClasse);
					$lstEtudiants = DAOEtudiants::getAllByClasse($classe);
					http_response_code(200);
					print(json_encode(['etudiantsFetched' => true, 'content' => $lstEtudiants, 'status' => 200, 'result' => 'success']));

				}else{
					http_response_code(404);
					print(json_encode(['etudiantsFetched' => false, 'content' => [], 'status' => 404, 'result' => 'error']));
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
