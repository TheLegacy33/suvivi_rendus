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
		case 'getconnexioncode':
			{
				$idEcole = intval($_POST['idecole'] ?? 0);
				$idClasse = intval($_POST['idclasse'] ?? 0);
				$idEtudiant = intval($_POST['idetudiant'] ?? 0);
				$idEvaluation = !isset($_POST['idevaluation']) ? 0 : (is_numeric($_POST['idevaluation']) ? intval($_POST['idevaluation']) : '*');
				if ($idEcole > 0 AND $idClasse > 0 AND $idEtudiant > 0 AND ($idEvaluation > 0 OR $idEvaluation == '*')){
					$etudiant = DAOEtudiants::getById($idEtudiant);

					if (is_null($etudiant->getDateExpirationCodeConnexion()) OR $etudiant->getDateExpirationCodeConnexion() < date_create('now + 1 hour')){
						$status = 201;
						$codeGenerated = DAOUser::generateCode();
						$etudiant->setCodeConnexion($codeGenerated);
						$etudiant->setDateExpirationCodeConnexion(date_create('now + 1 day'));
					}else{
						$status = 208;
					}

					MailToolBox::sendEmailCodeConnexion($etudiant);

					if (DAOEtudiants::update($etudiant)){
						http_response_code(200);
						print(json_encode(['codeGenerated' => true, 'content' => $etudiant->getDateExpirationCodeConnexion()->format('d/m/Y H:i:s'), 'status' => $status, 'result' => 'success']));
					}else{
						http_response_code(500);
						print(json_encode(['codeGenerated' => false, 'content' => [], 'status' => 500, 'result' => 'error']));
					}
				}else{
					http_response_code(404);
					print(json_encode(['codeGenerated' => false, 'content' => [], 'status' => 404, 'result' => 'error']));
				}
				break;
			}
		case 'verif-code':
			{
				$idEtudiant = intval($_GET['idetudiant'] ?? 0);
				$codeSaisi = htmlentities(trim($_GET['code'] ?? ''));

				if ($idEtudiant > 0 AND $codeSaisi !== ''){
					$etudiant = DAOEtudiants::getById($idEtudiant);
					if (is_null($etudiant->getDateExpirationCodeConnexion()) OR $etudiant->getDateExpirationCodeConnexion() < date_create('now') OR $etudiant->getCodeConnexion() !== $codeSaisi){
						$valid = false;
					}else{
						$valid = true;
					}

					http_response_code(200);
					print(json_encode(['codeVerified' => true, 'valid' => $valid, 'status' => 200, 'result' => $valid ? 'success' : 'error']));
				}else{
					http_response_code(404);
					print(json_encode(['codeVerified' => false, 'valid' => false, 'status' => 404, 'result' => 'error']));
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
