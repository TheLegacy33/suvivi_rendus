<?php

/**
 * @var string $section
 * @var string $action
 *
 * Controller pour les appels API
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

switch ($action) {
	case 'getUserCart': {
			$idUser = Session::getActiveSession()->getUserId();
			if ($idUser > 0) {
				$user = DAOUser::getById($idUser);
				$panier = DAOUser::getPanier($user);
				http_response_code(200);
				print(json_encode(['cartFetched' => true, 'cartContent' => $panier, 'result' => 'success']));
			} else {
				http_response_code(500);
				print(json_encode(['cartFetched' => false, 'cartContent' => null, 'result' => 'error']));
			}
			break;
		}

	case 'getActiveUserCart': {
			$idUser = Session::getActiveSession()->getUserId();
			if (DEV_MODE AND $idUser == 0 AND isset($_GET['idUserPostman'])){
				$idUser = intVal($_GET['idUserPostman']);
			}
			if ($idUser > 0) {
				$user = DAOUser::getById($idUser);
				$panier = DAOUser::getPanierActif($user);

				http_response_code(200);
				print(json_encode(['cartFetched' => true, 'cartContent' => $panier, 'result' => 'success']));
			} else {
				http_response_code(500);
				print(json_encode(['cartFetched' => false, 'cartContent' => null, 'result' => 'error']));
			}
			break;
		}

	case 'addToCart': {
			if (!empty($_POST)) {
				$idUser = intval($_POST['iduser'] ?? 0);
				$idPanier = $_POST['idpanier'] ?? 0;
				$lignePanierSent = json_decode($_POST['lignepanier']);

				$createPanier = false;
				$panier = new Panier();
				if ($idPanier == 0) {
					$panier->setDateCreation(date('Y-m-d'));
					if ($idUser == 0) {
						$panier->setUser(null);
						$panier->setStatutPanier(DAOStatutPanier::getByLibelle('Temporaire'));
					} else {
						$panier->setUser(DAOUser::getById($idUser));
						$panier->setStatutPanier(DAOStatutPanier::getByLibelle('Actif'));
					}
					$panier->setIdUnique(uniqid(md5('tmpcart'), more_entropy: false));
					$panier->setRemise(null);
					$createPanier = true;
				} else {
					$panier = DAOPanier::getByUniqueId($idPanier);
					$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
					$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
					$createPanier = false;
				}

				// Ensuite on traite la ligne
				// Si ce n'est pas une création de panier, je récupère les lignes du panier déjà enregistré
				$createLigne = true;
				$indexLigneAModifer = -1;
				if (!$createPanier) {
					if ($panier->getNbItems() > 0) {
						foreach ($panier->getLignes() as $index => $lignePanier) {
							if ($lignePanier->getOeuvre()->getId() === $lignePanierSent->oeuvreId) {
								$indexLigneAModifer = $index;
								$createLigne = false;
							}
						}
					}
				}

				if ($createLigne) {
					$lignePanier = new LignePanier($lignePanierSent->quantite ?? 0, $lignePanierSent->dedicace ?? false, $lignePanierSent->cadeau ?? false, date_create($lignePanierSent->date_creation ?? 'now'));

					$oeuvre = DAOOeuvre::getById($lignePanierSent->oeuvreId);
					$lignePanier->setOeuvre($oeuvre);
					$lignePanier->setPrixUnitaire($oeuvre->getPrixUnitaire());
					$lignePanier->setTauxPromotion($oeuvre->getTauxPromotion());
					$lignePanier->setPrixPromotion($oeuvre->getPrixPromotion());
					$lignePanier->setPrixNegocie($lignePanierSent->prixnegocie ?? 0);
					if (!$oeuvre->isEnPromotion() and intval($lignePanierSent->prixnegocie ?? 0) > 0) {
						$prixDefinitif = $lignePanierSent->prixnegocie;
					} elseif ($oeuvre->isEnPromotion()) {
						$prixDefinitif = $oeuvre->getPrixPromotion();
					} else {
						$prixDefinitif = $oeuvre->getPrixUnitaire();
					}
					$lignePanier->setPrixDefinitif($prixDefinitif);
//					$lignePanier->setMontantFdp($lignePanierSent->fdp);
					$panier->addLigne($lignePanier);
				} else {
					$lignePanier = $panier->getLignes()[$indexLigneAModifer];
					$lignePanier->setPrixUnitaire($lignePanier->getOeuvre()->getPrixUnitaire());
					$lignePanier->setTauxPromotion($lignePanier->getOeuvre()->getTauxPromotion());
					$lignePanier->setPrixPromotion($lignePanier->getOeuvre()->getPrixPromotion());
					$lignePanier->setPrixNegocie($lignePanierSent->prixnegocie ?? 0);
					if (!$lignePanier->getOeuvre()->isEnPromotion() and intval($lignePanierSent->prixnegocie ?? 0) > 0) {
						$prixDefinitif = $lignePanierSent->prixnegocie;
					} elseif ($lignePanier->getOeuvre()->isEnPromotion()) {
						$prixDefinitif = $lignePanier->getOeuvre()->getPrixPromotion();
					} else {
						$prixDefinitif = $lignePanier->getOeuvre()->getPrixUnitaire();
					}
					$lignePanier->setPrixDefinitif($prixDefinitif);
					$lignePanier->setMontantFdp($lignePanierSent->fdp);
				}
				if ($lignePanier->isCadeau()) {
					$infosCadeau = new InfosCadeau();
					DAOPanier::addInfosCadeau($infosCadeau);
					$lignePanier->setInfosCadeau($infosCadeau);
				}
				if (BDD::openTransaction()) {
					$transactionOk = true;
					if ($createPanier) {
						if (DAOPanier::insert($panier)) {
							$lignePanier->setIdPanier($panier->getId());

							if (!DAOPanier::insertLigne($lignePanier)) {
								$transactionOk = false;
							}
						} else {
							$transactionOk = false;
						}
					} else {
						if ($createLigne) {
							$transactionOk = DAOPanier::insertLigne($lignePanier);
						} else {
							$transactionOk = DAOPanier::updateLigne($lignePanier);
						}
					}
					if ($transactionOk) {
						BDD::commitTransaction();
						http_response_code(200);
						print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'cartContent' => null, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'removeFromCart': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$idOeuvre = $_POST['idoeuvre'] ?? 0;

				if (BDD::openTransaction()) {
					$panier = DAOPanier::getByUniqueId($idPanier);
					if (DAOPanier::deleteLigne($panier->getId(), $idOeuvre)) {
						BDD::commitTransaction();
//						BDD::rollbackTransaction();
						http_response_code(200);

						$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
						$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
						print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}
	case 'removeCardFromCart': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$idCarte = $_POST['idcarte'] ?? 0;
				$codeCarte = $_POST['codecarte'] ?? 0;

				if (BDD::openTransaction()) {
					$carteCadeau = DAOCarteCadeau::getById($idCarte);
					if ($carteCadeau->getCodeCarte() == $codeCarte){
						if (DAOCarteCadeau::delete($carteCadeau)){
							BDD::commitTransaction();
//							BDD::rollbackTransaction();
							http_response_code(200);
							$panier = DAOPanier::getByUniqueId($idPanier);
							$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
							$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
							print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['cartUpdated' => false, 'result' => 'error']));
						}
					}else{
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'addInfosCadeaux': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$idOeuvre = $_POST['idoeuvre'] ?? 0;
				$lignePanierSent = json_decode($_POST['lignepanier']);

				$panier = DAOPanier::getByUniqueId($idPanier);
				if (BDD::openTransaction()) {
					// j'ajoute les infos cadeau pour cette ligne dans ce panier
					$infosCadeau = new InfosCadeau($lignePanierSent->infoscadeau->origine, $lignePanierSent->infoscadeau->destinataire, $lignePanierSent->infoscadeau->message);
					if (DAOPanier::addInfosCadeau($infosCadeau)) {
						if (DAOPanier::updateCadeauLigne($panier->getId(), $idOeuvre, $infosCadeau)) {
							//								BDD::rollbackTransaction();
							BDD::commitTransaction();
							http_response_code(200);
							$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
							$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
							print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
						} else {
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['cartUpdated' => false, 'result' => 'error']));
						}
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'updateInfosCadeaux': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$idOeuvre = $_POST['idoeuvre'] ?? 0;
				$lignePanierSent = json_decode($_POST['lignepanier']);

				$panier = DAOPanier::getByUniqueId($idPanier);
				if (BDD::openTransaction()) {
					$infosCadeau = DAOPanier::getInfosCadeauLigne($panier->getId(), intval($idOeuvre));

					$infosCadeau->setDestinataire($lignePanierSent->infoscadeau->destinataire);
					$infosCadeau->setOrigine($lignePanierSent->infoscadeau->origine);
					$infosCadeau->setMessage($lignePanierSent->infoscadeau->message);

					if (DAOPanier::updateInfosCadeau($infosCadeau)) {
						//							BDD::rollbackTransaction();
						BDD::commitTransaction();
						http_response_code(200);
						$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
						$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
						print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'deleteInfosCadeaux': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$idOeuvre = $_POST['idoeuvre'] ?? 0;

				$panier = DAOPanier::getByUniqueId($idPanier);
				if (BDD::openTransaction()) {
					$infosCadeau = DAOPanier::getInfosCadeauLigne($panier->getId(), intval($idOeuvre));
					if (DAOPanier::updateCadeauLigne($panier->getId(), $idOeuvre, null)) {
						if (DAOPanier::deleteInfosCadeau($infosCadeau)) {
							//							BDD::rollbackTransaction();
							BDD::commitTransaction();
							http_response_code(200);
							$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
							$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
							print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
						} else {
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['cartUpdated' => false, 'result' => 'error']));
						}
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartUpdated' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'removeUserCart': {
			if (!empty($_POST)) {
				$idPanier = $_POST['idpanier'] ?? 0;
				$panier = DAOPanier::getByUniqueId($idPanier);
				if (BDD::openTransaction()) {
					if (DAOPanier::delete($panier->getId())) {
						BDD::commitTransaction();
						http_response_code(200);
						print(json_encode(['cartDeleted' => true, 'result' => 'success']));
					} else {
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['cartDeleted' => false, 'result' => 'error']));
					}
				}
			}
			break;
		}

	case 'getMontantsOeuvre': {
			if (!empty($_POST)) {
				$idOeuvre = $_POST['idoeuvre'] ?? 0;
				if ($idOeuvre > 0) {
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					$infosOeuvre = [
						'prixUnitaire' => $oeuvre->getPrixUnitaire(),
						'enPromo' => $oeuvre->isEnPromotion(),
						'tauxPromotion' => $oeuvre->getTauxPromotion(),
						'prixPromotion' => $oeuvre->getPrixPromotion()
					];

					http_response_code(200);
					print(json_encode(['oeuvreFetched' => true, 'infosOeuvre' => json_encode($infosOeuvre), 'result' => 'success']));
				} else {
					http_response_code(500);
					print(json_encode(['oeuvreFetched' => false, 'result' => 'error']));
				}
			} else {
				http_response_code(500);
				print(json_encode(['oeuvreFetched' => false, 'result' => 'error']));
			}
			break;
		}

	case 'checkCodeRemiseExiste': {
			$codeRemiseToCheck = htmlentities($_POST['coderemise']);
			$remiseToUse = DAORemise::getByCode($codeRemiseToCheck);
			http_response_code(200);
			if (is_null($remiseToUse)) {
				print(json_encode(['codeRemiseExiste' => false, 'remiseChecked' => $remiseToUse, 'result' => 'success']));
			} else {
				print(json_encode(['codeRemiseExiste' => true, 'remiseChecked' => $remiseToUse, 'result' => 'success']));
			}
			break;
		}

	case 'checkCodeRemiseValide': {
			$codeRemiseToCheck = htmlentities($_POST['coderemise']);
			$remiseToUse = DAORemise::getByCode($codeRemiseToCheck);

			// Est-ce que ce code remise est toujours d'actualité
			//  - Test de la date de début et de la date de fin

			$remiseValide = true;
			if (date_create(date('Y-m-d')) < date_create($remiseToUse->getDateDebut())) {
				$remiseValide = false;
			} else {
				if (!is_null($remiseToUse->getDateFin()) and (date_create(date('Y-m-d')) > date_create($remiseToUse->getDateFin()))) {
					$remiseValide = false;
				}
			}
			http_response_code(200);
			print(json_encode(['codeRemiseValide' => $remiseValide, 'remiseChecked' => $remiseToUse, 'result' => 'success']));
			break;
		}

	case 'checkCodeRemiseUtilisable': {
			$idPanier = $_POST['idpanier'];
			$panier = DAOPanier::getByUniqueId($idPanier);
			$codeRemiseToCheck = htmlentities($_POST['coderemise']);
			$remiseToCheck = DAORemise::getByCode($codeRemiseToCheck);
			// Est-ce que ce code remise est toujours d'actualité
			//  - Test de son utilisation unique par le user
			$remiseValide = DAOPanier::isCodeRemiseUtilisable($panier, $remiseToCheck);
			http_response_code(200);
			print(json_encode(['codeRemiseUtilisable' => $remiseValide, 'remiseChecked' => $remiseToCheck, 'result' => 'success']));
			break;
		}

	case 'applyRemiseToUserCart': {
			$idPanier = $_POST['idpanier'];
			$codeRemiseToCheck = htmlentities($_POST['coderemise']);
			$remiseToUse = DAORemise::getByCode($codeRemiseToCheck);

			if (!is_null($remiseToUse)) {
				$panier = DAOPanier::getByUniqueId($idPanier);

				if (DAOPanier::updateRemisePanier($panier, $remiseToUse)) {
					http_response_code(200);
					$panier->setRemise($remiseToUse);
					$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
					$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
					print(json_encode(['remiseApplied' => true, 'cartContent' => $panier, 'result' => 'success']));
				} else {
					http_response_code(500);
					print(json_encode(['remiseApplied' => false, 'result' => 'error']));
				}
			} else {
				http_response_code(500);
				print(json_encode(['remiseApplied' => false, 'result' => 'error']));
			}
			break;
		}

	case 'addGiftCardToCart': {
		if (!empty($_POST)){
			$idUser = intval($_POST['iduser'] ?? 0);
			$idPanier = $_POST['idpanier'] ?? 0;
			$infosGiftCard = json_decode($_POST['carteCadeau']);

			$createPanier = false;
			$panier = new Panier();
			if ($idPanier == 0){
				$panier->setDateCreation(date('Y-m-d'));
				if ($idUser == 0){
					$panier->setUser(null);
					$panier->setStatutPanier(DAOStatutPanier::getByLibelle('Temporaire'));
				}else{
					$panier->setUser(DAOUser::getById($idUser));
					$panier->setStatutPanier(DAOStatutPanier::getByLibelle('Actif'));
				}
				$panier->setIdUnique(uniqid(md5('tmpcart'), more_entropy: false));
				$createPanier = true;
			}else{
				$panier = DAOPanier::getByUniqueId($idPanier);
				$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
				$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
				$createPanier = false;
			}


			// Je commence par créer la carte
			$newCodeCarte = DAOCarteCadeau::geneCodeCarte();
			$giftCard = new CarteCadeau($newCodeCarte, date_create($infosGiftCard->chDateSouhaitee));
			$giftCard->setNomBeneficiaire(htmlentities($infosGiftCard->beneficiaire->chNomBeneficiaire));
			$giftCard->setPrenomBeneficiaire(htmlentities($infosGiftCard->beneficiaire->chPrenomBeneficiaire));
			$giftCard->setEmailBeneficiaire(htmlentities($infosGiftCard->beneficiaire->chEmailBeneficiaire));

			$adresseBeneficiaire = new Adresse(
				DAOTypeAdresse::getById(1),
				'Adresse personnelle',
				$infosGiftCard->beneficiaire->adresse->chAdresse1beneficiaire,
				$infosGiftCard->beneficiaire->adresse->chAdresse2beneficiaire,
				$infosGiftCard->beneficiaire->adresse->chAppartementBeneficiaire,
				$infosGiftCard->beneficiaire->adresse->chCodepostalBenficiaire,
				$infosGiftCard->beneficiaire->adresse->chVilleBeneficiaire,
				$infosGiftCard->beneficiaire->adresse->chRegionBenficiaire,
				$infosGiftCard->beneficiaire->adresse->chRegionBenficiaire,
				$infosGiftCard->beneficiaire->adresse->chPaysBeneficiaire,
				nomContact: $giftCard->getNomBeneficiaire(),
				prenomContact: $giftCard->getPrenomBeneficiaire()
			);
			$giftCard->setAdresseBeneficiaire($adresseBeneficiaire);

			$giftCard->setNomAcheteur(htmlentities($infosGiftCard->acheteur->chNomAcheteur));
			$giftCard->setPrenomAcheteur(htmlentities($infosGiftCard->acheteur->chPrenomAcheteur));
			$giftCard->setEmailAcheteur($infosGiftCard->acheteur->chEmailAcheteur);

			$giftCard->setTitreMessage(htmlentities($infosGiftCard->chTitreMessage));
			$giftCard->setContenuMessage(htmlentities($infosGiftCard->chContenuMessage));
			$giftCard->setFinMessage(htmlentities($infosGiftCard->chFinMessage));

			$giftCard->setMontant($infosGiftCard->chMontantCarte);
			$giftCard->setQuantite($infosGiftCard->chQuantite);
			$giftCard->setOccasion(DAOOccasions::getByLibelle($infosGiftCard->acheteur->chOccasionAcheteur));

			$giftCard->setDateAchat(null);
			$giftCard->setDateAffectationBeneficiaire(null);
			$giftCard->setDateAffectationPaiement(null);
			$giftCard->setDateCreation(date_create());
			$giftCard->setDateExpiration(date_create($infosGiftCard->chDateSouhaitee.' + 12 MONTH'));
			$giftCard->setStatutCarteCadeau(DAOStatutCarteCadeau::getById(1)); // En attente

			if ($idUser != 0){
				$giftCard->setIdAcheteur($idUser);
			}

			$panier->addCarteCadeau($giftCard);

			if (BDD::openTransaction()){
				$transactionOk = true;

				if (!DAOAdresse::insert($adresseBeneficiaire)){
					$transactionOk = false;
				}else{
					if ($createPanier){
						if (DAOPanier::insert($panier)){
							$giftCard->setIdPanier($panier->getId());
							if (!DAOCarteCadeau::insert($giftCard)){
								$transactionOk = false;
							}
						}else{
							$transactionOk = false;
						}
					}else{
						$transactionOk = DAOCarteCadeau::insert($giftCard);
					}
				}
				if ($transactionOk){
					BDD::commitTransaction();
//					BDD::rollbackTransaction();

					http_response_code(200);
					print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
				}else{
					BDD::rollbackTransaction();
					http_response_code(500);
					print(json_encode(['cartUpdated' => false, 'cartContent' => null, 'result' => 'error']));
				}
			}
		}else{
			BDD::rollbackTransaction();
			http_response_code(500);
			print(json_encode(['cartUpdated' => false, 'cartContent' => null, 'result' => 'error']));
		}
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
