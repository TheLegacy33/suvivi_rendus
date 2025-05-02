<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données de la page des évaluations
	 */
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case 'files':
			{
				switch ($action){
					case 'send':
						{
							if (empty($_POST)){
								$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-envoi-fichier-evaluation.js"];
								$lstEcoles = DAOEcoles::getAll();
								require_once 'core/views/view_form_send_file.phtml';
							}else{
								$idEcole = intval($_POST['chEcole'] ?? 0);
								$idClasse = intval($_POST['chClasse'] ?? 0);
								$idEtudiant = intval($_POST['chEtudiant'] ?? 0);
								$idEvaluation = intval($_POST['chEvaluation'] ?? 0);
								$codeSaisi = htmlentities(trim($_POST['chCodeConnexion'] ?? ''));
								if ($idEcole>0 and $idClasse>0 and $idEtudiant>0 and $idEvaluation>0 and $codeSaisi != ''){
									$etudiant = DAOEtudiants::getById($idEtudiant);
									if ($codeSaisi === $etudiant->getCodeConnexion()){
										$ecole = DAOEcoles::getById($idEcole);
										$classe = DAOClasses::getById($idClasse);
										$evaluation = DAOEvaluations::getById($idEvaluation);

										$file = $_FILES['chFichier'];
										$fileSent = new AppFile(date_create('now')->format('Ymd-His') . '[' . $etudiant->getFullName(false) . ']' . $file['name'], $file['full_path'], $file['tmp_name'], $file['error'], $file['size'], $file['type']);
										if ($fileSent->getSize()>0){
											$fileSent->setLocalFilePath(PHP_UPLOAD_DIR . 'rendus/' . $ecole->getNom() . '/' . $evaluation->getNom() . '/' . $classe->getNom() . '/');
											if ($fileSent->moveFile(includeDateTime: false)){
												BDD::openTransaction();
												$fichier = new Fichier($fileSent->getName(), $fileSent->getFullPath(), $etudiant, $evaluation);
												if (DAOFichiers::insert($fichier)){
													BDD::commitTransaction();
													MailToolBox::sendEmailConfirmationFichier($etudiant, $fichier);
													if (!is_null($classe->getEmailRendu())){
														MailToolBox::sendFileToRendu($etudiant, $fichier, $classe);
													}
													require_once 'core/views/view_valid_send_file.phtml';
												}else{
													BDD::rollbackTransaction();
													$message = "Erreur d'enregistrement du fichier !";
													require_once 'core/views/view_invalid_send_file.phtml';
												}
											}else{
												$message = "Erreur d'enregistrement du fichier !";
												require_once 'core/views/view_invalid_send_file.phtml';
											}
										}else{
											$message = 'Taille de fichier non valide !';
											require_once 'core/views/view_invalid_send_file.phtml';
										}
									}else{
										$message = 'Code saisi non valide !';
										require_once 'core/views/view_invalid_send_file.phtml';
									}
								}else{
									$message = 'Les informations transmises sont incorrectes !';
									require_once 'core/views/view_invalid_send_file.phtml';
								}
							}
							break;
						}
					default:
						{
							debug($action);
							break;
						}
				}
				break;
			}
		case 'student':
			{
				switch ($action){
					case 'view':
						{
							$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-student-view-evaluations.js"];
							$lstEcoles = DAOEcoles::getAll();
							require_once 'core/views/view_form_student_evaluations.phtml';
							break;
						}
					default:
						{
							require_once('core/controllers/controller_error.php');
							break;
						}
				}
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';
