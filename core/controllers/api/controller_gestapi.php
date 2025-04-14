<?php

/**
 * @var string $section
 * @var string $action
 * @var Oeuvre $oeuvre
 *
 * Controller pour les appels API
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
switch ($action) {
	case 'updatestatutuser': {
			$iduser = intval($_POST['iduser'] ?? 0);
			$idstatut = intval($_POST['idstatut'] ?? 0);
			if ($iduser > 0 and $idstatut > 0) {
				$user = DAOUser::getById($iduser);
				$user->setStatut(DAOStatutUser::getById($idstatut));
				if (BDD::openTransaction()) {
					if (DAOUser::updateStatut($user)) {
						http_response_code(200);
						print(json_encode(['userUpdated' => $user->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatestatutoeuvre': {
			$idoeuvre = intval($_POST['idoeuvre'] ?? 0);
			$idstatut = intval($_POST['idstatut'] ?? 0);
			if ($idoeuvre > 0 and $idstatut > 0) {
				$oeuvre = DAOOeuvre::getById($idoeuvre);
				$oeuvre->setStatutOeuvre(DAOStatutOeuvre::getById($idstatut));
				if (BDD::openTransaction()) {
					if (DAOOeuvreAdmin::updateStatut($oeuvre)) {
						http_response_code(200);
						print(json_encode(['oeuvreUpdated' => $oeuvre->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatehighlightoeuvre': {
			$idoeuvre = intval($_POST['idoeuvre'] ?? 0);
			$highlight = $_POST['highlight'] ?? 'false';
			if ($idoeuvre > 0) {
				$oeuvre = DAOOeuvre::getById($idoeuvre);
				$oeuvre->setHighlight($highlight == 'true');
				if (BDD::openTransaction()) {
					if (DAOOeuvreAdmin::updateHighlight($oeuvre)) {
						http_response_code(200);
						print(json_encode(['oeuvreUpdated' => $oeuvre->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateverrouartscandale': {
			$idoeuvre = intval($_POST['idoeuvre'] ?? 0);
			$verrou = $_POST['verrouArtScandale'] ?? 'false';
			if ($idoeuvre > 0) {
				$oeuvre = DAOOeuvre::getById($idoeuvre);
				$oeuvre->setVerrouArtScandale($verrou == 'true');
				if (BDD::openTransaction()) {
					if (DAOOeuvreAdmin::updateVerrouArtScandale($oeuvre)) {
						http_response_code(200);
						print(json_encode(['oeuvreUpdated' => $oeuvre->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatestatutvisuel': {
			$idvisuel = intval($_POST['idvisuel'] ?? 0);
			$idstatut = intval($_POST['idstatut'] ?? 0);
			if ($idvisuel > 0 and $idstatut > 0) {
				$visuel = DAOVisuelOeuvre::getById($idvisuel);
				$visuel->setStatutVisuel(DAOStatutVisuel::getById($idstatut));
				if (BDD::openTransaction()) {
					if (DAOVisuelOeuvreAdmin::updateStatut($visuel)) {
						http_response_code(200);
						print(json_encode(['visuelUpdated' => $visuel->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatetypevisuel': {
			$idvisuel = intval($_POST['idvisuel'] ?? 0);
			$idtype = intval($_POST['idtype'] ?? 0);
			if ($idvisuel > 0 and $idtype > 0) {
				$visuel = DAOVisuelOeuvreAdmin::getById($idvisuel);
				$visuel->setTypeVisuel(DAOTypeVisuel::getById($idtype));
				if (BDD::openTransaction()) {
					if (DAOVisuelOeuvreAdmin::updateType($visuel)) {
						http_response_code(200);
						print(json_encode(['visuelUpdated' => $visuel->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['visuelUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateconfianceartiste': {
			$idartiste = intval($_POST['idartiste'] ?? 0);
			$indiceconfiance = intval($_POST['idconfiance'] ?? 0);
			if ($idartiste > 0) {
				$artiste = DAOArtisteAdmin::getById($idartiste);
				$artiste->setArtisteDeConfiance(intval($indiceconfiance) == 1);
				if (BDD::openTransaction()) {
					if (DAOArtisteAdmin::updateConfianceArtiste($artiste)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $artiste->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateassujettitvaartiste': {
			$idartiste = intval($_POST['idartiste'] ?? 0);
			$indiceassujettitva = intval($_POST['idassujettitva'] ?? 0);
			if ($idartiste > 0) {
				$artiste = DAOArtisteAdmin::getById($idartiste);
				$artiste->setAssujettiTVA(intval($indiceassujettitva) == 1);
				if (BDD::openTransaction()) {
					if (DAOArtisteAdmin::updateAssujettiTvaArtiste($artiste)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $artiste->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateproblemacheteur': {
			$idacheteur = intval($_POST['idacheteur'] ?? 0);
			$indiceprobleme = intval($_POST['idprobleme'] ?? 0);
			if ($idacheteur > 0) {
				$acheteur = DAOUser::getById($idacheteur);
				$acheteur->setHasProblem($indiceprobleme == 1);
				if (BDD::openTransaction()) {
					if (DAOUserAdmin::updateStatutProbleme($acheteur)) {
						http_response_code(200);
						print(json_encode(['acheteurUpdated' => $acheteur->getId(), 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['acheteurUpdated' => false, 'result' => 'Error Update']));
						BDD::rollbackTransaction();
					}
				} else {
					BDD::rollbackTransaction();
					http_response_code(500);
					print(json_encode(['acheteurUpdated' => false, 'result' => 'Error Transaction']));
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'Error Id Acheteur']));
			}
			break;
		}
	case 'updatepresentationartiste': {
			if (!empty($_POST)) {
				$idartiste = $_POST['idartiste'];
				$artiste = DAOArtiste::getById($idartiste);
				$texteInfo = htmlentities($_POST['presentation']);
				if (BDD::openTransaction()) {
					if (DAOArtiste::updateInformation($artiste, DAOInformation::getByLibelle('presentation'), $texteInfo)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idartiste, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatebiographieartiste': {
			if (!empty($_POST)) {
				$idartiste = $_POST['idartiste'];
				$artiste = DAOArtiste::getById($idartiste);
				$texteInfo = htmlentities($_POST['biographie']);
				if (BDD::openTransaction()) {
					if (DAOArtiste::updateInformation($artiste, DAOInformation::getByLibelle('biographie'), $texteInfo)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idartiste, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateformationartiste': {
			if (!empty($_POST)) {
				$idformation = $_POST['idvaleur'];
				$idartiste = $_POST['idartiste'];
				$periode = htmlentities($_POST['periode']);
				$organisme = htmlentities($_POST['organisme']);
				$lieu = htmlentities($_POST['lieu']);
				$libelle = htmlentities($_POST['libelle']);
				if (BDD::openTransaction()) {
					if (DAOFormation::update($idformation, $idartiste, $periode, $organisme, $lieu, $libelle)) {
						http_response_code(200);
						print(json_encode(['formationUpdated' => $idformation, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['formationUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updaterecompenseartiste': {
			if (!empty($_POST)) {
				$idrecompense = $_POST['idvaleur'];
				$idartiste = $_POST['idartiste'];
				$periode = htmlentities($_POST['periode']);
				$organisme = htmlentities($_POST['organisme']);
				$lieu = htmlentities($_POST['lieu']);
				$libelle = htmlentities($_POST['libelle']);
				if (BDD::openTransaction()) {
					if (DAORecompense::update($idrecompense, $idartiste, $periode, $organisme, $lieu, $libelle)) {
						http_response_code(200);
						print(json_encode(['recompenseUpdated' => $idrecompense, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['recompenseUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updateexpositionartiste': {
			if (!empty($_POST)) {
				$idexposition = $_POST['idvaleur'];
				$idartiste = $_POST['idartiste'];
				$periode = htmlentities($_POST['periode']);
				$lieu = htmlentities($_POST['lieu']);
				$libelle = htmlentities($_POST['libelle']);
				$idtype = $_POST['idtype'];
				if (BDD::openTransaction()) {
					if (DAOExposition::update($idexposition, $idartiste, $periode, $lieu, $libelle, $idtype)) {
						http_response_code(200);
						print(json_encode(['expositionUppdated' => $idexposition, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['expositionUppdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updateevenementartiste': {
			if (!empty($_POST)) {
				$idevenement = $_POST['idvaleur'];
				$idartiste = $_POST['idartiste'];
				$periode = htmlentities($_POST['periode']);
				$lieu = htmlentities($_POST['lieu']);
				$libelle = htmlentities($_POST['libelle']);
				$idtype = $_POST['idtype'];
				if (BDD::openTransaction()) {
					if (DAOEvenement::update($idevenement, $idartiste, $periode, $lieu, $libelle, $idtype)) {
						http_response_code(200);
						print(json_encode(['evenementUpdated' => $idevenement, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['evenementUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updatereperesartiste': {
			$idartiste = intval($_POST['idartiste'] ?? 0);
			$idrepere = intval($_POST['idrepere'] ?? 0);
			$actionrepere = $_POST['actionrepere'] ?? '';
			if ($actionrepere != '') {
				if (BDD::openTransaction()) {
					switch ($actionrepere) {
						case 'add': {
								if (DAOArtisteAdmin::addRepereToArtiste($idartiste, $idrepere)) {
									http_response_code(200);
									print(json_encode(['artisteUpdated' => $idartiste, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						case 'del': {
								if (DAOArtisteAdmin::delRepereFromArtiste($idartiste, $idrepere)) {
									http_response_code(200);
									print(json_encode(['artisteUpdated' => $idartiste, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						default: {
								http_response_code(500);
								print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
								BDD::rollbackTransaction();
							}
					}
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateoccasionoeuvre': {
			$idoeuvre = intval($_POST['idoeuvre'] ?? 0);
			$idoccasion = intval($_POST['idoccasion'] ?? 0);
			$valide = 1;
			$actionoccasion = $_POST['actionoccasion'] ?? '';
			if ($actionoccasion != '') {
				if (BDD::openTransaction()) {
					switch ($actionoccasion) {
						case 'add': {
								if (DAOOeuvreAdmin::addOccasionToOeuvre($idoccasion, $idoeuvre, $valide)) {
									http_response_code(200);
									print(json_encode(['oeuvreUpdated' => $idoeuvre, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						case 'del': {
								if (DAOOeuvreAdmin::delOccasionFromOeuvre($idoeuvre, $idoccasion)) {
									http_response_code(200);
									print(json_encode(['oeuvreUpdated' => $idoeuvre, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						default: {
								http_response_code(500);
								print(json_encode(['oeuvreUpdated' => false, 'result' => 'error']));
								BDD::rollbackTransaction();
							}
					}
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updatemotsclefsartiste': {
			$idartiste = intval($_POST['idartiste'] ?? 0);
			$idmotclef = intval($_POST['idmotclef'] ?? 0);
			$actionmotclef = $_POST['actionmotclef'] ?? '';
			if ($actionmotclef != '') {
				if (BDD::openTransaction()) {
					switch ($actionmotclef) {
						case 'add': {
								if (DAOArtisteAdmin::addMotClefToArtiste($idartiste, $idmotclef)) {
									http_response_code(200);
									print(json_encode(['artisteUpdated' => $idartiste, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						case 'del': {
								if (DAOArtisteAdmin::delMotClefFromArtiste($idartiste, $idmotclef)) {
									http_response_code(200);
									print(json_encode(['artisteUpdated' => $idartiste, 'result' => 'success']));
									BDD::commitTransaction();
								} else {
									http_response_code(500);
									print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
									BDD::rollbackTransaction();
								}
								break;
							}
						default: {
								http_response_code(500);
								print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
								BDD::rollbackTransaction();
							}
					}
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'getoeuvresbyartist': {
			$idartiste = intval($_GET['idartiste'] ?? 0);
			$statut = $_GET['statut'] ?? 'attente';
			$highlight = isset($_GET['highlight']) && $_GET['highlight'] == 'true';
			$scandale = isset($_GET['scandale']) && $_GET['scandale'] == 'true';
			if ($highlight) {
				$lesOeuvres = DAOOeuvreAdmin::getAllHighlightByArtisteId($idartiste, $scandale);
			} else {
				if ($statut == 'attente') {
					$lesOeuvres = DAOOeuvreAdmin::getAllAttenteByArtisteId($idartiste, $scandale);
				} else if ($statut == 'validee') {
					$lesOeuvres = DAOOeuvreAdmin::getAllValideeByArtisteId($idartiste, $scandale);
				} else if ($statut == 'autres') {
					$lesOeuvres = DAOOeuvreAdmin::getAllOthersByArtisteId($idartiste, $scandale);
				} else {
					$lesOeuvres = DAOOeuvreAdmin::getAllPresentationByArtisteId($idartiste, $scandale);
				}
			}
			$lesOeuvresHighLight = [];
			$lesOeuvresEnAttente = [];
			$lesOeuvresValides = [];
			$lesOeuvresAutres = [];
			$lesOeuvresPresentation = [];
			foreach ($lesOeuvres as $oeuvre) {
				//				if ($oeuvre->getStatutOeuvre()->getId() !== 6){
				if ($oeuvre->isHighlight()) {
					$lesOeuvresHighLight[] = $oeuvre;
				} else {
					if (!$oeuvre->isPresentation()) {
						if ($statut == 'attente') {
							$lesOeuvresEnAttente[] = $oeuvre;
						}
						if ($statut == 'validee') {
							$lesOeuvresValides[] = $oeuvre;
						}
						if ($statut == 'autres') {
							$lesOeuvresAutres[] = $oeuvre;
						}
					} else {
						$lesOeuvresPresentation[] = $oeuvre;
					}
				}
				//				}
			}
			http_response_code(200);
			print(json_encode(['nboeuvreshighlight' => count($lesOeuvresHighLight), 'oeuvreshighlight' => $lesOeuvresHighLight, 'nboeuvresenattente' => count($lesOeuvresEnAttente), 'oeuvresenattente' => $lesOeuvresEnAttente, 'nboeuvresvalidees' => count($lesOeuvresValides), 'oeuvresvalidees' => $lesOeuvresValides, 'nboeuvresautres' => count($lesOeuvresAutres), 'oeuvresautres' => $lesOeuvresAutres, 'nboeuvrespresentation' => count($lesOeuvresPresentation), 'oeuvrespresentation' => $lesOeuvresPresentation, 'baseurl_visuels' => getUrl('gestion', 'listevisuels', 'viewbyoeuvre'), 'baseurl_edit' => getUrl('gestion', 'form-oeuvre', 'edit'), 'baseurl_details' => getUrl('gestion', 'oeuvre', 'view'), 'baseurl_artistedetails' => getUrl('gestion', 'artiste', 'view'), 'lstStatutsOeuvre' => $lstStatutOeuvre = DAOStatutOeuvre::getAll()]));
			break;
		}
	case 'getoeuvres': {
			$statut = $_GET['statut'] ?? 'attente';
			$highlight = isset($_GET['highlight']) && $_GET['highlight'] == 'true';
			$scandale = isset($_GET['scandale']) && $_GET['scandale'] == 'true';
			if ($highlight) {
				$lesOeuvres = DAOOeuvreAdmin::getAllHighlight($scandale);
			} else {
				if ($statut == 'attente') {
					$lesOeuvres = DAOOeuvreAdmin::getAllAttente($scandale);
				} else if ($statut == 'validee') {
					$lesOeuvres = DAOOeuvreAdmin::getAllValidee($scandale);
				} else if ($statut == 'autres') {
					$lesOeuvres = DAOOeuvreAdmin::getAllOthers($scandale);
				} else {
					$lesOeuvres = DAOOeuvreAdmin::getAllPresentation($scandale);
				}
			}
			$lesOeuvresHighLight = [];
			$lesOeuvresEnAttente = [];
			$lesOeuvresValides = [];
			$lesOeuvresAutres = [];
			$lesOeuvresPresentation = [];
			foreach ($lesOeuvres as $oeuvre) {
				if ($oeuvre->getStatutOeuvre()->getId() !== 6) {
					if ($oeuvre->isHighlight()) {
						$lesOeuvresHighLight[] = $oeuvre;
					} else {
						if (!$oeuvre->isPresentation()) {
							if ($statut == 'attente') {
								$lesOeuvresEnAttente[] = $oeuvre;
							}
							if ($statut == 'validee') {
								$lesOeuvresValides[] = $oeuvre;
							}
							if ($statut == 'autres') {
								$lesOeuvresAutres[] = $oeuvre;
							}
						} else {
							$lesOeuvresPresentation[] = $oeuvre;
						}
					}
				}
			}
			http_response_code(200);
			print(json_encode(['nboeuvreshighlight' => count($lesOeuvresHighLight), 'oeuvreshighlight' => $lesOeuvresHighLight, 'nboeuvresenattente' => count($lesOeuvresEnAttente), 'oeuvresenattente' => $lesOeuvresEnAttente, 'nboeuvresvalidees' => count($lesOeuvresValides), 'oeuvresvalidees' => $lesOeuvresValides, 'nboeuvresautres' => count($lesOeuvresAutres), 'oeuvresautres' => $lesOeuvresAutres, 'nboeuvrespresentation' => count($lesOeuvresPresentation), 'oeuvrespresentation' => $lesOeuvresPresentation, 'baseurl_visuels' => getUrl('gestion', 'listevisuels', 'viewbyoeuvre'), 'baseurl_edit' => getUrl('gestion', 'form-oeuvre', 'edit'), 'baseurl_details' => getUrl('gestion', 'oeuvre', 'view'), 'baseurl_artistedetails' => getUrl('gestion', 'artiste', 'view'), 'lstStatutsOeuvre' => $lstStatutOeuvre = DAOStatutOeuvre::getAll()]));
			break;
		}
	case 'getOeuvreBySearch': {
			$search = $_GET['namesearch'] ?? '';
			$scandale = isset($_GET['scandale']) && $_GET['scandale'] == 'true';
			$lstArtiste = DAOArtiste::searchArtistByName($search);
			$lstOeuvres = [];
			foreach ($lstArtiste as $unArtiste) {
				$lstOeuvres = array_merge($lstOeuvres, DAOOeuvreAdmin::getByArtisteIdPrivate($unArtiste->getId(), $scandale));
			}
			http_response_code(200);
			print(json_encode([
				'oeuvressearch' => $lstOeuvres,
				'baseurl_visuels' => getUrl('gestion', 'listevisuels', 'viewbyoeuvre'),
				'baseurl_edit' => getUrl('gestion', 'form-oeuvre', 'edit'),
				'baseurl_details' => getUrl('gestion', 'oeuvre', 'view'),
				'baseurl_artistedetails' => getUrl('gestion', 'artiste', 'view'),
				'lstStatutsOeuvre' => $lstStatutOeuvre = DAOStatutOeuvre::getAll() // <= obligatoire pour le js
			]));
			break;
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
