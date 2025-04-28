<?php
	/**
	 * @var string $section
	 * @var string $page
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'view-evaluations':
			{
				$idEtudiant = intval($_GET['idetudiant'] ?? 0);
				$idEvaluation = intval($_GET['idevaluation'] ?? 0);
				$codeSaisi = htmlentities(trim($_GET['code'] ?? ''));
				if ($idEtudiant > 0 and $codeSaisi != ''){
					$etudiant = DAOEtudiants::getById($idEtudiant);
					if ($codeSaisi === $etudiant->getCodeConnexion()){
						$classe = DAOClasses::getById($etudiant->getIdClasse());
						$lstEvaluations = DAOEvaluations::getAllByClasse($classe);

						$retVal = array();
						foreach ($lstEvaluations as $evaluation){
							if ($idEvaluation === 0 OR $evaluation->getId() === $idEvaluation){
								$fichiers = DAOFichiers::getByEvaletEtudiant($evaluation, $etudiant);
								if (count($fichiers) <= 0){
									$fichiers = null;
								}
								$retVal[$evaluation->getId()] = [
									'nomevaluation' => $evaluation->getNom(),
									'fichiers' => $fichiers
								];
							}
						}
						http_response_code(200);
						print(json_encode(['evaluationsFetched' => true, 'content' => $retVal, 'status' => 200, 'result' => 'success']));
					}else{
						http_response_code(304);
						print(json_encode(['evaluationsFetched' => false, 'content' => [], 'status' => 304, 'result' => 'error']));
					}
				}else{
					http_response_code(404);
					print(json_encode(['evaluationsFetched' => false, 'content' => [], 'status' => 404, 'result' => 'error']));
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
