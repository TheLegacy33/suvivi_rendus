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
	case 'updateUserCoordonnees': {
			$idUser = $_POST['idUser'];
			$user = DAOUser::getById($idUser);
			$civilite = $_POST['civilite'];
			$jourNaissance = $_POST['jourNaissance'];
			$moisNaissance = $_POST['moisNaissance'];
			$anneeNaissance = $_POST['anneeNaissance'];
			$pseudoUser = htmlentities(trim($_POST['pseudoUser']));
			$loginIdentifiant = htmlentities(trim($_POST['loginIdentifiant']));
			$nomUser = htmlentities(trim($_POST['nomUser']));
			$prenomUser = htmlentities(trim($_POST['prenomUser']));
			$emailUser = htmlentities(trim($_POST['emailUser']));
			$telFixeUser = $_POST['telFixeUser'];
			$telPortableUser = $_POST['telPortableUser'];
			$typeAcheteur = $_POST['typeAcheteur'];
			$user->setPseudo($pseudoUser);
			$user->setNom($nomUser);
			$user->setPrenom($prenomUser);
			$user->setCivilite(DAOCivilite::getById($civilite));
			$user->setDateNaissance(date_create($anneeNaissance . '-' . $moisNaissance . '-' . $jourNaissance));
			$user->setEmail($emailUser);
			$user->setTelPortable($telPortableUser);
			$user->setTelFixe($telFixeUser);
			$user->setTypesAcheteur(DAOTypeAcheteur::getByLibelle($typeAcheteur));
			$user->setLogin($loginIdentifiant);
			if (BDD::openTransaction()) {
				/**
				 * Concernant l'entreprise
				 */
				if (DAOUser::update($user)) {
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
				print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteCoordonnees': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			$user = $artiste->getUser();
			$civilite = $_POST['civilite'];
			$jourNaissance = $_POST['jourNaissance'];
			$moisNaissance = $_POST['moisNaissance'];
			$anneeNaissance = $_POST['anneeNaissance'];
			$pseudoArtiste = htmlentities(trim($_POST['pseudoArtiste']));
			$loginIdentifiant = htmlentities(trim($_POST['loginIdentifiant']));
			$nomArtiste = htmlentities(trim($_POST['nomArtiste']));
			$prenomArtiste = htmlentities(trim($_POST['prenomArtiste']));
			$afficherNaissance = $_POST['affichernaissance'];
			$afficherPseudo = $_POST['afficherpseudo'];
			$emailUser = htmlentities(trim($_POST['emailUser']));
			$telFixeUser = $_POST['telFixeUser'];
			$telPortableUser = $_POST['telPortableUser'];
			$assujettiTVA = $_POST['assujettiTVAChecked'] ?? null;
			$attestationTVA = $_FILES['attestationTVA'] ?? null;

			$artiste->setPseudo($pseudoArtiste);
			$artiste->setNom($nomArtiste);
			$artiste->setPrenom($prenomArtiste);
			$artiste->setAfficherPseudo($afficherPseudo == 'true');
			$artiste->setAfficherNaissance($afficherNaissance == 'true');
			$user->setCivilite(DAOCivilite::getById($civilite));
			$user->setDateNaissance(date_create($anneeNaissance . '-' . $moisNaissance . '-' . $jourNaissance));
			$user->setEmail($emailUser);
			$user->setTelPortable($telPortableUser);
			$user->setTelFixe($telFixeUser);
			$user->setLogin($loginIdentifiant);

			$artiste->setAssujettiTVA($assujettiTVA == 'oui' ? true : ($assujettiTVA == 'non' ? false : null));

			$artiste->setCategorieArtiste(DAOCategorieArtiste::getById(intval($_POST['categorieArtiste'])));
			if (BDD::openTransaction()) {
				/**
				 * Concernant l'entreprise
				 */
				$error = false;
				if ($artiste->getCategorieArtiste()->isCategoriePro()) {
					// L'artiste est pro je dois modifier ou ajouter l'entreprise
					$raisonSociale = htmlentities($_POST['raisonSociale']);
					$siret = $_POST['siret'];
					$nomRepresentant = htmlentities($_POST['nomRepresentant']);
					$prenomRepresentant = htmlentities($_POST['prenomRepresentant']);
					if (is_null($artiste->getEntreprise())) {
						$entreprise = new Entreprise($raisonSociale, $siret, $nomRepresentant, $prenomRepresentant, $emailUser, telFixe: $telFixeUser, telPortable: $telPortableUser);
						$entreprise->setAdresse(null);
						if (!DAOEntreprise::insert($entreprise)) {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
							$error = true;
						} else {
							$artiste->setEntreprise($entreprise);
						}
					} else {
						$artiste->getEntreprise()->setRaisonSociale($raisonSociale);
						$artiste->getEntreprise()->setSiret($siret);
						$artiste->getEntreprise()->setNomRepresentant($nomRepresentant);
						$artiste->getEntreprise()->setPrenomRepresentant($prenomRepresentant);
						if (!DAOEntreprise::update($artiste->getEntreprise())) {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
							$error = true;
						}
					}
				} else {
					// L'artiste n'est pas pro je dois retirer son entreprise si il en avait une
					if (!is_null($artiste->getEntreprise())) {
						if (!DAOEntreprise::delete($artiste->getEntreprise())) {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
							$error = true;
						} else {
							$artiste->setEntreprise(null);
						}
					}
				}

				// Traitement de l'attestation de TVA
				$attestationTVA = $_FILES['attestationTVA'] ?? null;
				if (!is_null($attestationTVA)) {
					// Je viens ajouter le document si il est différente du dernier transmis
					$sendDoc = true;
					$docSent = new AIFile($attestationTVA['name'], $attestationTVA['full_path'], $attestationTVA['tmp_name'], $attestationTVA['error'], $attestationTVA['size'], $attestationTVA['type']);
					if (!is_null($artiste->getAttestationTVA())) {
						$lastAttestation = $artiste->getAttestationTVA();
						if (str_starts_with($docSent->getName(), $lastAttestation->getNom())) {
							$sendDoc = false;
						}
					}

					if ($sendDoc) {
						$docSent->setLocalFilePath($artiste->getPersonalFolder() . 'documents/');
						if ($docSent->moveFile(false)) {
							$newAttestation = new Document(
								pathinfo($docSent->getName(), PATHINFO_FILENAME),
								$docSent->getFullPath(),
								DAOStatutDocument::getById(1),
								DAOTypeDocument::getById(12) //Document bancaire
							);
							$artiste->setAttestationTVA($newAttestation);

							if (!DAOArtiste::addAttestationTVA($artiste, $newAttestation)){
								$error = true;
							}
						}
					}
				}

				if (!$error) {
					if (DAOArtiste::update($artiste)) {
						if (DAOUser::update($user)) {
							http_response_code(200);
							print(json_encode(['userUpdated' => $user->getId(), 'artisteUpdate' => $artiste->getId(), 'result' => 'success']));
							BDD::commitTransaction();
						} else {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'artisteUpdate' => $artiste->getId(), 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'artisteUpdate' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtistePays': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			$paysNationalite = $_POST['paysnationalite']; // htmlentities()
			$paysResidence = $_POST['paysresidence']; //htmlentities()
			$artiste->setNationalite(DAOPays::getByNom($paysNationalite));
			$artiste->setResidence(DAOPays::getByNom($paysResidence));
			if (BDD::openTransaction()) {
				if (DAOArtiste::update($artiste)) {
					http_response_code(200);
					print(json_encode(['artisteUpdate' => $artiste->getId(), 'result' => 'success']));
					BDD::commitTransaction();
				} else {
					http_response_code(500);
					print(json_encode(['artisteUpdate' => $artiste->getId(), 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdate' => $artiste->getId(), 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'updateUserAdresseFacturation': {
			$idUser = $_POST['idUser'];
			$user = DAOUser::getById($idUser);
			// Adresse de facturation
			$nomContactFacturation = htmlentities($_POST['nomContactFacturation']);
			$prenomContactFacturation = htmlentities($_POST['prenomContactFacturation']);
			$adresse1Facturation = htmlentities($_POST['adresse1Facturation']);
			$adresse2Facturation = $_POST['adresse2Facturation'] == '' ? null : htmlentities($_POST['adresse2Facturation']);
			$complementAdresseFacturation = $_POST['complementAdresseFacturation'] == '' ? null : htmlentities($_POST['complementAdresseFacturation']);
			$regionFacturation = $_POST['regionFacturation'];
			$departementFacturation = $_POST['departementFacturation'];
			$codepostalFacturation = $_POST['codepostalFacturation'];
			$villeFacturation = htmlentities($_POST['villeFacturation']);
			$paysFacturation = $_POST['paysFacturation'];
			$adresseFacturation = $user->getAdresseFacturation();
			if (BDD::openTransaction()) {
				$error = false;
				if (is_null($adresseFacturation)) {
					$adresseFacturation = new Adresse(DAOTypeAdresse::getByLibelle('Facturation'), 'Principale', $adresse1Facturation, $adresse2Facturation, $complementAdresseFacturation, $codepostalFacturation, $villeFacturation, $regionFacturation, $departementFacturation, $paysFacturation);
					$user->addAdresse($adresseFacturation);
				} else {
					$adresseFacturation->setNomContact($nomContactFacturation);
					$adresseFacturation->setPrenomContact($prenomContactFacturation);
					$adresseFacturation->setAdresse1($adresse1Facturation);
					$adresseFacturation->setAdresse2($adresse2Facturation);
					$adresseFacturation->setComplementAdresse($complementAdresseFacturation);
					if ($adresseFacturation->getCpVille()->getVille() != $villeFacturation or $adresseFacturation->getCpVille()->getCodepostal() != $codepostalFacturation) {
						if (DAOCPVille::exists($codepostalFacturation, $villeFacturation)) {
							$adresseFacturation->setCpVille(DAOCPVille::getByCPVille($codepostalFacturation, $villeFacturation));
						} else {
							$unCpVille = new CPVille($codepostalFacturation, $villeFacturation);
							if (DAOCPVille::insert($unCpVille)) {
								$adresseFacturation->setCpVille($unCpVille);
							} else {
								$error = true;
							}
						}
					}
					$oldRegion = is_null($adresseFacturation->getRegion()) ? '' : $adresseFacturation->getRegion()->getNom();
					if ($oldRegion != $regionFacturation) {
						if (trim($regionFacturation) == '') {
							$adresseFacturation->setRegion(null);
						} else {
							if (DAORegion::exists($regionFacturation)) {
								$adresseFacturation->setRegion(DAORegion::getByNom($regionFacturation));
							} else {
								$uneRegion = new Region($regionFacturation);
								if (DAORegion::insert($uneRegion)) {
									$adresseFacturation->setRegion($uneRegion);
								} else {
									$error = true;
								}
							}
						}
					}
					$oldDepartement = is_null($adresseFacturation->getDepartement()) ? '' : $adresseFacturation->getDepartement()->getNom();
					if ($oldDepartement != $departementFacturation) {
						if (trim($departementFacturation) == '') {
							$adresseFacturation->setDepartement(null);
						} else {
							if (DAODepartement::exists($departementFacturation)) {
								$adresseFacturation->setDepartement(DAODepartement::getByNom($departementFacturation));
							} else {
								$unDepartement = new Departement($departementFacturation);
								if (DAODepartement::insert($unDepartement)) {
									$adresseFacturation->setDepartement($unDepartement);
								} else {
									$error = true;
								}
							}
						}
					}
					if ($adresseFacturation->getPays()->getNomFr() != $paysFacturation) {
						if (DAOPays::exists($paysFacturation)) {
							$adresseFacturation->setPays(DAOPays::getByNom($paysFacturation));
						} else {
							$unPays = new Pays($paysFacturation);
							if (DAOPays::insert($unPays)) {
								$adresseFacturation->setPays($unPays);
							} else {
								$error = true;
							}
						}
					}
				}
				if (!$error) {
					if ($adresseFacturation->getId() == 0) {
						if (DAOAdresse::insert($adresseFacturation)) {
							if (DAOUser::insertAdresse($user, $adresseFacturation)) {
								http_response_code(200);
								print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => true, 'result' => 'success']));
							} else {
								http_response_code(500);
								print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => false, 'result' => 'error']));
								$error = true;
							}
						}
					} else {
						if (DAOAdresse::update($adresseFacturation)) {
							http_response_code(200);
							print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => true, 'result' => 'success']));
						} else {
							http_response_code(500);
							print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => false, 'result' => 'error']));
							$error = true;
						}
					}
				}
				if ($error) {
					BDD::rollbackTransaction();
				} else {
					BDD::commitTransaction();
				}
			}
			break;
		}
	case 'updateUserAdresseLivraison': {
			$idUser = $_POST['idUser'];
			$user = DAOUser::getById($idUser);
			$adresseLivraison = null;
			//Adresse de livraison
			$chkAdresseLivraisonDifferente = $_POST['chkAdresseLivraisonDifferente'];
			if ($chkAdresseLivraisonDifferente) {
				$statutContactLivraison = $_POST['statutContactLivraison'];
				$raisonSocialeLivraison = htmlentities($_POST['raisonSocialeLivraison']);
				$nomContactLivraison = htmlentities($_POST['nomContactLivraison']);
				$prenomContactLivraison = htmlentities($_POST['prenomContactLivraison']);
				$adresse1Livraison = htmlentities($_POST['adresse1Livraison']);
				$adresse2Livraison = $_POST['adresse2Livraison'] == '' ? null : htmlentities($_POST['adresse2Livraison']);
				$complementAdresseLivraison = $_POST['complementAdresseLivraison'] == '' ? null : htmlentities($_POST['complementAdresseLivraison']);
				$infosComplementairesLivraison = $_POST['infosComplementairesLivraison'] == '' ? null : htmlentities($_POST['infosComplementairesLivraison']);
				$regionLivraison = $_POST['regionLivraison'];
				$departementLivraison = $_POST['departementLivraison'];
				$codepostalLivraison = $_POST['codepostalLivraison'];
				$villeLivraison = htmlentities($_POST['villeLivraison']);
				$paysLivraison = $_POST['paysLivraison'];
				$telFixeLivraison = $_POST['telFixeLivraison'] == '' ? null : $_POST['telFixeLivraison'];
				$telPortableLivraison = $_POST['telPortableLivraison'] == '' ? null : $_POST['telPortableLivraison'];
				$adresseLivraison = $user->getAdresseLivraison();
				if (BDD::openTransaction()) {
					$error = false;
					if (is_null($adresseLivraison)) {
						$adresseLivraison = new Adresse(DAOTypeAdresse::getByLibelle('Livraison'), 'Principale', $adresse1Livraison, $adresse2Livraison, $complementAdresseLivraison, $codepostalLivraison, $villeLivraison, $regionLivraison, $departementLivraison, $paysLivraison, $infosComplementairesLivraison, $raisonSocialeLivraison, $nomContactLivraison, $prenomContactLivraison, $telFixeLivraison, $telPortableLivraison, DAOStatutAdresse::getById($statutContactLivraison));
						$user->addAdresse($adresseLivraison);
					} else {
						$adresseLivraison->setRaisonSociale($raisonSocialeLivraison);
						$adresseLivraison->setNomContact($nomContactLivraison);
						$adresseLivraison->setPrenomContact($prenomContactLivraison);
						$adresseLivraison->setTelFixe($telFixeLivraison);
						$adresseLivraison->setTelPortable($telPortableLivraison);
						$adresseLivraison->setAdresse1($adresse1Livraison);
						$adresseLivraison->setAdresse2($adresse2Livraison);
						$adresseLivraison->setComplementAdresse($complementAdresseLivraison);
						$adresseLivraison->setInfosComplementaires($infosComplementairesLivraison);
						$adresseLivraison->setStatutAdresse(DAOStatutAdresse::getById($statutContactLivraison));
						if ($adresseLivraison->getCpVille()->getVille() != $villeLivraison or $adresseLivraison->getCpVille()->getCodepostal() != $codepostalLivraison) {
							if (DAOCPVille::exists($codepostalLivraison, $villeLivraison)) {
								$adresseLivraison->setCpVille(DAOCPVille::getByCPVille($codepostalLivraison, $villeLivraison));
							} else {
								$unCpVille = new CPVille($codepostalLivraison, $villeLivraison);
								if (DAOCPVille::insert($unCpVille)) {
									$adresseLivraison->setCpVille($unCpVille);
								} else {
									$error = true;
								}
							}
						}
						$oldRegion = is_null($adresseLivraison->getRegion()) ? '' : $adresseLivraison->getRegion()->getNom();
						if ($oldRegion != $regionLivraison) {
							if (trim($regionLivraison) == '') {
								$adresseLivraison->setRegion(null);
							} else {
								if (DAORegion::exists($regionLivraison)) {
									$adresseLivraison->setRegion(DAORegion::getByNom($regionLivraison));
								} else {
									$uneRegion = new Region($regionLivraison);
									if (DAORegion::insert($uneRegion)) {
										$adresseLivraison->setRegion($uneRegion);
									} else {
										$error = true;
									}
								}
							}
						}
						$oldDepartement = is_null($adresseLivraison->getDepartement()) ? '' : $adresseLivraison->getDepartement()->getNom();
						if ($oldDepartement != $departementLivraison) {
							if (trim($departementLivraison) == '') {
								$adresseLivraison->setDepartement(null);
							} else {
								if (DAODepartement::exists($departementLivraison)) {
									$adresseLivraison->setDepartement(DAODepartement::getByNom($departementLivraison));
								} else {
									$unDepartement = new Departement($departementLivraison);
									if (DAODepartement::insert($unDepartement)) {
										$adresseLivraison->setDepartement($unDepartement);
									} else {
										$error = true;
									}
								}
							}
						}
						if ($adresseLivraison->getPays()->getNomFr() != $paysLivraison) {
							if (DAOPays::exists($paysLivraison)) {
								$adresseLivraison->setPays(DAOPays::getByNom($paysLivraison));
							} else {
								$unPays = new Pays($paysLivraison);
								if (DAOPays::insert($unPays)) {
									$adresseLivraison->setPays($unPays);
								} else {
									$error = true;
								}
							}
						}
					}
					if (!$error) {
						if ($adresseLivraison->getId() == 0) {
							if (DAOAdresse::insert($adresseLivraison)) {
								if (DAOUser::insertAdresse($user, $adresseLivraison)) {
									http_response_code(200);
									print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => true, 'result' => 'success']));
								} else {
									http_response_code(500);
									print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => false, 'result' => 'error']));
									$error = true;
								}
							}
						} else {
							if (DAOAdresse::update($adresseLivraison)) {
								http_response_code(200);
								print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => true, 'result' => 'success']));
							} else {
								http_response_code(500);
								print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => false, 'result' => 'error']));
								$error = true;
							}
						}
					}
					if ($error) {
						BDD::rollbackTransaction();
					} else {
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(200);
				print(json_encode(['userUpdated' => $user->getId(), 'adressesUpdated' => false, 'result' => 'success']));
			}
			break;
		}
	case 'updateInfosBanque': {
			$idUser = $_POST['idUser'];
			$user = DAOUser::getById($idUser);
			$methodePaiement = intval($_POST['methodePaiement']);
			$statutCompte = intval($_POST['statutCompte']);
			$typeCompte = intval($_POST['typeCompte']);
			$titulaireCompte = htmlentities($_POST['titulaireCompte']);
			$banqueCompte = htmlentities($_POST['banqueCompte']);
			$ibanCompte = htmlentities($_POST['ibanCompte']);
			$bicswiftCompte = htmlentities($_POST['bicswiftCompte']);
			$adresseBanqueCompte1 = htmlentities($_POST['adresseBanqueCompte1']);
			$adresseBanqueCompte2 = htmlentities($_POST['adresseBanqueCompte2']);
			$codePostalCompte = htmlentities($_POST['codePostalCompte']);
			$villeCompte = htmlentities($_POST['villeCompte']);
			$paysCompte = htmlentities($_POST['paysCompte']);
			$infoComplementaireCompte = htmlentities($_POST['infoComplementaireCompte']);
			$numeroCompte = htmlentities($_POST['numeroCompte']);
			$infoComplementaireCompteAutre = htmlentities($_POST['infoComplementaireCompteAutre']);
			if (BDD::openTransaction()) {
				$ajout = false;
				$update = false;
				$ajoutAutre = false;
				$updateAutre = false;
				if (is_null($user->getInfosBanque())) {
					$infosBanque = new InfosBanque($titulaireCompte, $banqueCompte, $ibanCompte, $infoComplementaireCompte, $bicswiftCompte, $numeroCompte, $adresseBanqueCompte1, $adresseBanqueCompte2, $codePostalCompte, $villeCompte);
					$infosBanque->setMethodeVirement(DAOMethodeVirement::getById($methodePaiement));
					$infosBanque->setTypeCompte(DAOTypeCompte::getById($typeCompte));
					$infosBanque->setStatutCompte(DAOStatutCompte::getById($statutCompte));
					$infosBanque->setPaysBanque(DAOPays::getByNom($paysCompte));
					$ajout = true;
				} else {
					$infosBanque = $user->getInfosBanque();
					$infosBanque->setTitulaire($titulaireCompte);
					$infosBanque->setNomBanque($banqueCompte);
					$infosBanque->setIban($ibanCompte);
					$infosBanque->setBicSwift($bicswiftCompte);
					$infosBanque->setMethodeVirement(DAOMethodeVirement::getById($methodePaiement));
					$infosBanque->setTypeCompte(DAOTypeCompte::getById($typeCompte));
					$infosBanque->setStatutCompte(DAOStatutCompte::getById($statutCompte));
					$infosBanque->setAdresseBanque1($adresseBanqueCompte1);
					$infosBanque->setAdresseBanque2($adresseBanqueCompte2);
					$infosBanque->setInfoComplementaire($infoComplementaireCompte);
					$infosBanque->setNumeroCompte($numeroCompte);
					if (is_null($infosBanque->getCpVilleBanque())) {
						if (DAOCPVille::exists($codePostalCompte, $villeCompte)) {
							$infosBanque->setCpVilleBanque(DAOCPVille::getByCPVille($codePostalCompte, $villeCompte));
						} else {
							$unCpVille = new CPVille($codePostalCompte, $villeCompte);
							if (DAOCPVille::insert($unCpVille)) {
								$infosBanque->setCpVilleBanque($unCpVille);
							} else {
								$error = true;
							}
						}
					} else {
						if ($infosBanque->getCpVilleBanque()->getVille() != $villeCompte or $infosBanque->getCpVilleBanque()->getCodepostal() != $codePostalCompte) {
							if (DAOCPVille::exists($codePostalCompte, $villeCompte)) {
								$infosBanque->setCpVilleBanque(DAOCPVille::getByCPVille($codePostalCompte, $villeCompte));
							} else {
								$unCpVille = new CPVille($codePostalCompte, $villeCompte);
								if (DAOCPVille::insert($unCpVille)) {
									$infosBanque->setCpVilleBanque($unCpVille);
								} else {
									$error = true;
								}
							}
						}
					}

					$update = true;
				}

				$fileDocBancaire = $_FILES['chDocBancaire'] ?? null;
				$fileIdRecto = $_FILES['chIdCardRecto'] ?? null;
				$fileIdVerso = $_FILES['chIdCardVerso'] ?? null;

				if (!is_null($fileDocBancaire)) {
					$sendDoc = true;
					$docSent = new AIFile($fileDocBancaire['name'], $fileDocBancaire['full_path'], $fileDocBancaire['tmp_name'], $fileDocBancaire['error'], $fileDocBancaire['size'], $fileDocBancaire['type']);
					if (!is_null($infosBanque->getDocumentBancaire())) {
						$document = $infosBanque->getDocumentBancaire();
						if (!str_starts_with($docSent->getName(), $document->getNom())) {
							@unlink($document->getChemin());
							if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
								DAODocument::delete($document);
							}
						} else {
							$sendDoc = false;
						}
					}

					if ($sendDoc) {
						$docSent->setLocalFilePath($user->getPersonalFolder() . 'documents/');
						if ($docSent->moveFile(false)) {
							$infosBanque->setDocumentBancaire(
								new Document(
									pathinfo($docSent->getName(), PATHINFO_FILENAME),
									$docSent->getFullPath(),
									DAOStatutDocument::getById(1),
									DAOTypeDocument::getById(9) //Document bancaire
								)
							);
						}
					}
				}

				if (!is_null($fileIdRecto)) {
					$sendDoc = true;
					$docSent = new AIFile($fileIdRecto['name'], $fileIdRecto['full_path'], $fileIdRecto['tmp_name'], $fileIdRecto['error'], $fileIdRecto['size'], $fileIdRecto['type']);
					if (!is_null($infosBanque->getIdCardRecto())) {
						$document = $infosBanque->getIdCardRecto();
						if (!str_starts_with($docSent->getName(), $document->getNom())) {
							@unlink($document->getChemin());
							if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
								DAODocument::delete($document);
							}
						} else {
							$sendDoc = false;
						}
					}

					if ($sendDoc) {
						$docSent->setLocalFilePath($user->getPersonalFolder() . 'documents/');
						if ($docSent->moveFile(false)) {
							$infosBanque->setIdCardRecto(
								new Document(
									pathinfo($docSent->getName(), PATHINFO_FILENAME),
									$docSent->getFullPath(),
									DAOStatutDocument::getById(1),
									DAOTypeDocument::getById(10) // identite recto
								)
							);
						}
					}
				}

				if (!is_null($fileIdVerso)) {
					$sendDoc = true;
					$docSent = new AIFile($fileIdVerso['name'], $fileIdVerso['full_path'], $fileIdVerso['tmp_name'], $fileIdVerso['error'], $fileIdVerso['size'], $fileIdVerso['type']);
					if (!is_null($infosBanque->getIdCardVerso())) {
						$document = $infosBanque->getIdCardVerso();
						if (!str_starts_with($docSent->getName(), $document->getNom())) {
							@unlink($document->getChemin());
							if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
								DAODocument::delete($document);
							}
						} else {
							$sendDoc = false;
						}
					}

					if ($sendDoc) {
						$docSent->setLocalFilePath($user->getPersonalFolder() . 'documents/');
						if ($docSent->moveFile(false)) {
							$infosBanque->setIdCardVerso(
								new Document(
									pathinfo($docSent->getName(), PATHINFO_FILENAME),
									$docSent->getFullPath(),
									DAOStatutDocument::getById(1),
									DAOTypeDocument::getById(11) // identite verso
								)
							);
						}
					}
				}

				if (is_null($user->getInfosBanqueAutre())) {
					$infosBanqueAutre = new InfosBanqueAutre($infoComplementaireCompteAutre);
					$user->setInfosBanqueAutre($infosBanqueAutre);
					$ajoutAutre = true;
				} else {
					$infosBanqueAutre = $user->getInfosBanqueAutre();
					$infosBanqueAutre->setInfoComplementaire($infoComplementaireCompteAutre);
					$user->setInfosBanqueAutre($infosBanqueAutre);
					$updateAutre = true;
				}

				$fileAutreDocBancaire = $_FILES['chAutresDocBancaire'] ?? null;
				if (!is_null($fileAutreDocBancaire)) {
					$docSent = new AIFile($fileAutreDocBancaire['name'], $fileAutreDocBancaire['full_path'], $fileAutreDocBancaire['tmp_name'], $fileAutreDocBancaire['error'], $fileAutreDocBancaire['size'], $fileAutreDocBancaire['type']);
					$docSent->setLocalFilePath($user->getPersonalFolder() . 'documents/');
					if ($docSent->moveFile(false)) {
						$infosBanqueAutre->addDocumentBancaireAutre(
							new Document(
								pathinfo($docSent->getName(), PATHINFO_FILENAME),
								$docSent->getFullPath(),
								DAOStatutDocument::getById(1),
								DAOTypeDocument::getById(9) //Document bancaire
							)
						);
					}
				}
				if ($ajout && !$update) {
					$error = !DAOInfosBanque::insert($user, $infosBanque);
				} else {
					$error = !DAOInfosBanque::update($infosBanque);
				}
				if ($ajoutAutre && !$updateAutre) {
					$error = !DAOInfosBanqueAutre::insert($user, $infosBanqueAutre);
				} else {
					$error = !DAOInfosBanqueAutre::update($infosBanqueAutre);
				}
				if (!$error) {
					http_response_code(200);
					print(json_encode(['userUpdated' => $user->getId(), 'infosbanqueUpdated' => true, 'result' => 'success']));
					BDD::commitTransaction();
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'infosbanqueUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			}
			break;
		}
	case 'deleteDocBancaire': {
			$idDoc = $_POST['iddoc'] ?? 0;
			$idInfosBancaires = $_POST['idinfosbanc'] ?? 0;
			$document = DAOInfosBanque::getDocumentBancaire($idInfosBancaires);
			$infosBanque = DAOInfosBanque::getById($idInfosBancaires);
			if (!is_null($document)) {
				if (file_exists($document->getChemin())) {
					if (@unlink($document->getChemin())) {
						BDD::openTransaction();
						if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
							DAODocument::delete($document);
						}
						http_response_code(200);
						print(json_encode(['userUpdated' => $infosBanque->getIdUtilisateur(), 'docDeleted' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'deleteDocBancaireAutre': {
			$idDocAutre = $_POST['iddocautre'] ?? 0;
			$idInfosBancairesAutres = $_POST['idinfosbancautre'] ?? 0;
			$document = DAOInfosBanqueAutre::getDocumentBancaireAutre($idInfosBancairesAutres, $idDocAutre);
			$infosBanqueAutre = DAOInfosBanqueAutre::getById($idInfosBancairesAutres);
			if (!is_null($document)) {
				if (file_exists($document->getChemin())) {
					if (@unlink($document->getChemin())) {
						BDD::openTransaction();
						if (DAOInfosBanqueAutre::deleteDocument($infosBanqueAutre, $document)) {
							DAODocument::delete($document);
						}
						http_response_code(200);
						print(json_encode(['userUpdated' => $infosBanqueAutre->getIdUtilisateur(), 'docDeleted' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
			}
			break;
		}
	case 'deleteRectoIdCard': {
			$idDoc = $_POST['iddoc'] ?? 0;
			$idInfosBancaires = $_POST['idinfosbanc'] ?? 0;
			$document = DAOInfosBanque::getIdCardRecto($idInfosBancaires);
			$infosBanque = DAOInfosBanque::getById($idInfosBancaires);
			if (!is_null($document)) {
				if (file_exists($document->getChemin())) {
					if (@unlink($document->getChemin())) {
						BDD::openTransaction();
						if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
							DAODocument::delete($document);
						}
						http_response_code(200);
						print(json_encode(['userUpdated' => $infosBanque->getIdUtilisateur(), 'docDeleted' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'deleteVersoIdCard': {
			$idDoc = $_POST['iddoc'] ?? 0;
			$idInfosBancaires = $_POST['idinfosbanc'] ?? 0;
			$document = DAODocument::getIdCardVerso($idInfosBancaires);
			$infosBanque = DAOInfosBanque::getById($idInfosBancaires);
			if (!is_null($document)) {
				if (file_exists($document->getChemin())) {
					if (@unlink($document->getChemin())) {
						BDD::openTransaction();
						if (DAOInfosBanque::deleteDocument($infosBanque, $document)) {
							DAODocument::delete($document);
						}
						http_response_code(200);
						print(json_encode(['userUpdated' => $infosBanque->getIdUtilisateur(), 'docDeleted' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'docDeleted' => false, 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'updateUserPassword': {
			$idUser = $_POST['idUser'] ?? 0;
			$oldPwd = $_POST['oldpassword'] ?? '';
			$newPwd = $_POST['newpassword'] ?? '';
			if (!DAOUser::checkUserPassword($idUser, $oldPwd) && $newPwd != '') {
				http_response_code(401);
				print(json_encode(['userUpdated' => $idUser, 'passChecked' => false]));
			} else {
				if (BDD::openTransaction()) {
					$user = DAOUser::getById($idUser);
					if (DAOUser::updatePassword($user, $newPwd)) {
						http_response_code(200);
						print(json_encode(['userUpdated' => $user->getId(), 'passChecked' => true, 'passwordUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'passChecked' => false, 'passwordUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updateUserOptions': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			$user = $artiste->getUser();
			$accepteDedicace = $_POST['acceptededicace'];
			$accepteNegociation = $_POST['acceptenegociation'];
			$tauxNegociation = intval($_POST['tauxnegociation']);
			$accepteCommande = $_POST['acceptecommande'];
			if (BDD::openTransaction()) {
				$artiste->setAccepteDedicace($accepteDedicace == 'true');
				$artiste->setAccepteNegociation($accepteNegociation == 'true');
				$artiste->setAccepteCommande($accepteCommande == 'true');
				if ($artiste->accepteNegociation()) {
					$artiste->setTauxNegociation(DAOTauxNegociation::getById($tauxNegociation));
				} else {
					$artiste->setTauxNegociation(null);
				}
				if (DAOArtiste::updateUserOptions($artiste)) {
					http_response_code(200);
					print(json_encode(['userUpdated' => $user->getId(), 'optionsUpdated' => true, 'result' => 'success']));
					BDD::commitTransaction();
				} else {
					http_response_code(500);
					print(json_encode(['userUpdated' => false, 'optionsUpdated' => false, 'result' => 'error']));
					BDD::rollbackTransaction();
				}
			}
			break;
		}
	case 'updatePortrait': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$user = DAOUser::getById($idUser);
				if (!empty($_FILES)) {
					$file = $_FILES['imgPortrait'];
					$fileSent = new AIImage($file['name'], $file['full_path'], $file['tmp_name'], $file['error'], $file['size'], $file['type']);
					if ($fileSent->getSize() > 0) {
						$fileSent->setLocalFilePath($user->getPersonalFolder() . 'profil/');
						if ($fileSent->moveFile()) {
							//$user->setPhoto($fileSent->getFullPath());
							$user->setPhoto($fileSent->getMiniatureFilePath());
						}
					}
					if (BDD::openTransaction()) {
						if (DAOUser::updatePortrait($user)) {
							http_response_code(200);
							print(json_encode(['userUpdated' => $user->getId(), 'photoUpdated' => true, 'result' => 'success']));
							BDD::commitTransaction();
						} else {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'photoUpdated' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'photoUpdated' => false, 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'deletePortrait': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$user = DAOUser::getById($idUser);
				if (BDD::openTransaction()) {
					if (DAOUser::deletePortrait($user)) {
						//Suppression du fichier physique
						if (@unlink($user->getPhoto())) {
							http_response_code(200);
							print(json_encode(['userUpdated' => $user->getId(), 'photoDeleted' => true, 'fileDeleted' => true, 'result' => 'success']));
							BDD::commitTransaction();
						} else {
							http_response_code(500);
							print(json_encode(['userUpdated' => $user->getId(), 'photoDeleted' => true, 'fileDeleted' => false, 'result' => 'success']));
							BDD::rollbackTransaction();
						}
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'photoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'photoUpdated' => false, 'result' => 'error']));
				BDD::rollbackTransaction();
			}
			break;
		}
	case 'updateUserInfos': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$typeInfo = $_POST['typeInformation'];
				$texteInfo = htmlentities($_POST['texteInformation']);
				if (BDD::openTransaction()) {
					if (DAOArtiste::updateInformation($artiste, DAOInformation::getByLibelle($typeInfo), $texteInfo)) {
						http_response_code(200);
						print(json_encode(['userUpdated' => $idUser, 'infoUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'getUserInfos': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$typeInfo = $_POST['typeInformation'];
				$retVal = html_entity_decode($artiste->getInformation(DAOInformation::getByLibelle($typeInfo))->getValeur());
				http_response_code(200);
				print(json_encode(['userFetched' => $idUser, 'infoFetched' => $retVal, 'result' => 'success']));
			} else {
				http_response_code(500);
				print(json_encode(['userFetched' => false, 'infoFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteFormations': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$formations = json_decode($_POST['formations']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les formations existantes
					if (DAOArtiste::deleteFormations($artiste)) {
						foreach ($formations as $index => $formation) {
							$formation->numOrdre = $index + 1;
							if (!DAOArtiste::addFormation($artiste, $formation)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'formationUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'formationUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteFormations': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$formations = $artiste->getFormations();
				$nbFormations = count($formations);
				$script = '';
				foreach ($formations as $id => $formation) {
					$script .= '<div class="button-tableau" data-fields="chFormations[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($formation->getPeriode()) . '" id="chFormationDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					// $script .= '<input class="organisme capitalize" type="text" value="' . ($formation->getOrganisme()) . '" id="chFormationOrganisme_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Organisme de formation">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($formation->getLibelle()) . '" id="chFormationLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Libellé de la formation">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($formation->getLieu()) . '" id="chFormationLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbFormations; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chFormations[]">';
					$script .= '<input class="periode" type="text" value="" id="chFormationDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					// $script .= '<input class="organisme capitalize" type="text" value="" id="chFormationOrganisme_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Organisme de formation">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chFormationLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Libellé de la formation">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chFormationLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteRecompenses': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$recompenses = json_decode($_POST['recompenses']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les récompenses existantes
					if (DAOArtiste::deleteRecompenses($artiste)) {
						foreach ($recompenses as $index => $recompense) {
							$recompense->numOrdre = $index + 1;
							if (!DAOArtiste::addRecompense($artiste, $recompense)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'recompenseUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'recompenseUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteRecompenses': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$recompenses = $artiste->getRecompenses();
				$nbRecompenses = count($recompenses);
				$script = '';
				foreach ($artiste->getRecompenses() as $id => $recompense) {
					$script .= '<div class="button-tableau" data-fields="chRecompenses[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($recompense->getPeriode()) . '" id="chRecompenseDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					// $script .= '<input class="organisme capitalize" type="text" value="'.($recompense->getOrganisme()).'" id="chRecompenseOrganisme_'.($id + 1).'" class="alignement-button-deux" placeholder="Organisme">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($recompense->getLibelle()) . '" id="chRecompenseLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Libellé de la récompense">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($recompense->getLieu()) . '" id="chRecompenseLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbRecompenses; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chRecompenses[]">';
					$script .= '<input class="periode" type="text" value="" id="chRecompenseDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					// $script .= '<input class="organisme capitalize" type="text" value="" id="chRecompenseOrganisme_'.($index + 1).'" class="alignement-button-deux" placeholder="Organisme">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chRecompenseLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Libellé de la récompense">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chRecompenseLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteExposIndividuelles': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$expositions = json_decode($_POST['expositionsIndividuelles']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les expositions existantes
					if (DAOArtiste::deleteExposIndividuelles($artiste)) {
						foreach ($expositions as $index => $exposition) {
							$exposition->numOrdre = $index + 1;
							if (!DAOArtiste::addExpoIndividuelle($artiste, $exposition)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'expositionUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'expositionUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteExpositions': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$expositionsIndividuelles = DAOExposition::getByTypeAndArtisteId(DAOTypeExposition::getByLibelle('Individuelles'), $artiste->getId());
				$nbExpositions = count($expositionsIndividuelles);
				$script = '';
				foreach ($expositionsIndividuelles as $id => $expositionIndividuelle) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsIndividuelles[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($expositionIndividuelle->getPeriode()) . '" id="chExpositionIndividuelleDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($expositionIndividuelle->getLibelle()) . '" id="chExpositionIndividuelleLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($expositionIndividuelle->getLieu()) . '" id="chExpositionIndividuelleLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbExpositions; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsIndividuelles[]">';
					$script .= '<input class="periode" type="text" value="" id="chExpositionIndividuelleDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chExpositionIndividuelleLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chExpositionIndividuelleLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteExposCollectives': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$expositions = json_decode($_POST['expositionsCollectives']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les expositions existantes
					if (DAOArtiste::deleteExposCollectives($artiste)) {
						foreach ($expositions as $index => $exposition) {
							$exposition->numOrdre = $index + 1;
							if (!DAOArtiste::addExpoCollective($artiste, $exposition)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'expositionUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'expositionUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteCollectives': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$expositionsCollectives = DAOExposition::getByTypeAndArtisteId(DAOTypeExposition::getByLibelle('Collectives'), $artiste->getId());
				$nbExpositions = count($expositionsCollectives);
				$script = '';
				foreach ($expositionsCollectives as $id => $expositionCollectives) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsCollectives[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($expositionCollectives->getPeriode()) . '" id="chExpositionCollectivesDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($expositionCollectives->getLibelle()) . '" id="chExpositionCollectivesLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($expositionCollectives->getLieu()) . '" id="chExpositionCollectivesLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbExpositions; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsCollectives[]">';
					$script .= '<input class="periode" type="text" value="" id="chExpositionCollectivesDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chExpositionCollectivesLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chExpositionCollectivesLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'getArtistePermanentes': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$expositionsPermanentes = DAOExposition::getByTypeAndArtisteId(DAOTypeExposition::getByLibelle('Permanentes'), $artiste->getId());
				$nbExpositions = count($expositionsPermanentes);
				$script = '';
				foreach ($expositionsPermanentes as $id => $expositionPermanentes) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsPermanentes[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($expositionPermanentes->getPeriode()) . '" id="chExpositionPermanentesDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($expositionPermanentes->getLibelle()) . '" id="chExpositionPermanentesLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($expositionPermanentes->getLieu()) . '" id="chExpositionPermanentesLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbExpositions; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chExpositionsPermanentes[]">';
					$script .= '<input class="periode" type="text" value="" id="chExpositionPermanentesDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chExpositionPermanentesLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Nom de l\'exposition">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chExpositionPermanentesLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteExposPermanentes': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$expositions = json_decode($_POST['expositionsPermanentes']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les expositions existantes
					if (DAOArtiste::deleteExposPermanentes($artiste)) {
						foreach ($expositions as $index => $exposition) {
							$exposition->numOrdre = $index + 1;
							if (!DAOArtiste::addExpoPermanentes($artiste, $exposition)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'expositionUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'expositionUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'updateArtisteCollections': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$collections = json_decode($_POST['collections']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les expositions existantes
					if (DAOArtiste::deleteCollections($artiste)) {
						foreach ($collections as $index => $collection) {
							$collection->numOrdre = $index + 1;
							if (!DAOArtiste::addCollections($artiste, $collection)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'collectionUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'collectionUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteCollections': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$collections = DAOCollection::getByArtisteId($artiste->getId());
				$nbCollections = count($collections);
				$script = '';
				foreach ($collections as $id => $uneCollection) {
					$script .= '<div class="button-tableau" data-fields="chCollections[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($uneCollection->getPeriode()) . '" id="chCollectionDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($uneCollection->getNbOeuvre()) . '" id="chCollectionNombres_' . ($id + 1) . '" placeholder="3 oeuvres">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($uneCollection->getOrganisme()) . '" id="chCollectionOrganisme_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Musée du Louvre">';
					$script .= '<input class=lieu" type="text" value="' . htmlspecialchars($uneCollection->getVille()) . '" id="chCollectionVille_' . ($id + 1) . '" placeholder="Paris">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($uneCollection->getPays()) . '" id="chCollectionPays_' . ($id + 1) . '" placeholder="France">';
					$script .= '</div>';
				}
				for ($index = $nbCollections; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chCollections[]">';
					$script .= '<input class="periode" type="text" value="" id="chCollectionDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="periode" type="text" value="" id="chCollectionNombres_' . ($index + 1) . '" placeholder="3 oeuvres">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chCollectionOrganisme_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Musée du Louvre">';
					$script .= '<input class="lieu" type="text" value="" id="chCollectionVille_' . ($index + 1) . '" placeholder="Paris">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chCollectionPays_' . ($index + 1) . '" placeholder="France">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteFoires': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$foires = json_decode($_POST['foires']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les foires existantes
					if (DAOArtiste::deleteFoires($artiste)) {
						foreach ($foires as $index => $foire) {
							$foire->numOrdre = $index + 1;
							if (!DAOArtiste::addFoire($artiste, $foire)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'foireUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'foireUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteFoires': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$evenements = DAOEvenement::getByTypeAndArtisteId(DAOTypeEvenement::getByLibelle('Foires'), $artiste->getId());
				$nbEvenements = count($evenements);
				$script = '';
				foreach ($evenements as $id => $evenementFoire) {
					$script .= '<div class="button-tableau" data-fields="chFoires[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($evenementFoire->getPeriode()) . '" id="chFoiresDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($evenementFoire->getLibelle()) . '" id="chFoiresLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Nom de la foire">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($evenementFoire->getLieu()) . '" id="chFoiresLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbEvenements; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chFoires[]">';
					$script .= '<input class="periode" type="text" value="" id="chFoiresDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chFoiresLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Nom de la foire">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chFoiresLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteSalons': {
			$idUser = $_POST['idUser'];
			$artiste = DAOArtiste::getByIdUser($idUser);
			if (!empty($_POST)) {
				$salons = json_decode($_POST['salons']);
				if (BDD::openTransaction()) {
					$updated = true;
					// Je commence par supprimer toute les formations existantes
					if (DAOArtiste::deleteSalons($artiste)) {
						foreach ($salons as $index => $salon) {
							$salon->numOrdre = $index + 1;
							if (!DAOArtiste::addSalon($artiste, $salon)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'salonUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
						//						BDD::rollbackTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'salonUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtisteSalons': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$evenements = DAOEvenement::getByTypeAndArtisteId(DAOTypeEvenement::getByLibelle('Salons'), $artiste->getId());
				$nbEvenements = count($evenements);
				$script = '';
				foreach ($evenements as $id => $evenementSalon) {
					$script .= '<div class="button-tableau" data-fields="chSalons[]">';
					$script .= '<input class="periode" type="text" value="' . htmlspecialchars($evenementSalon->getPeriode()) . '" id="chSalonsDates_' . ($id + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="' . htmlspecialchars($evenementSalon->getLibelle()) . '" id="chSalonsLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Nom du salon">';
					$script .= '<input class="lieu capitalize" type="text" value="' . htmlspecialchars($evenementSalon->getLieu()) . '" id="chSalonsLieu_' . ($id + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				for ($index = $nbEvenements; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chSalons[]">';
					$script .= '<input class="periode" type="text" value="" id="chSalonsDates_' . ($index + 1) . '" placeholder="01|2023 - 02|2024">';
					$script .= '<input class="libelle capitalize" type="text" value="" id="chSalonsLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Nom du salon">';
					$script .= '<input class="lieu capitalize" type="text" value="" id="chSalonsLieu_' . ($index + 1) . '" placeholder="Pays">';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtistePresses': {
			if (!empty($_POST)) {
				// debug($_FILES);
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$presses = json_decode($_POST['presses']);
				// debug($presses);
				if (BDD::openTransaction()) {
					$updated = true;
					if (DAOArtiste::deletePresses($artiste)) {
						foreach ($presses as $index => $presse) {
							$presse->numOrdre = $index + 1;
							if (isset($presse->idimg) and $presse->idimg != '') {
								$imgpresse = $_FILES['fichierImage_' . $presse->idimg];
								$imgpresseEnvoye = new AIImage($imgpresse['name'], $imgpresse['full_path'], $imgpresse['tmp_name'], $imgpresse['error'], $imgpresse['size'], $imgpresse['type']);
								if ($imgpresseEnvoye->getSize() > 0) {
									$imgpresseEnvoye->setLocalFilePath($artiste->getPersonalFolder() . 'presse/');
									if ($imgpresseEnvoye->moveFile()) {
										$presse->cheminphoto = $imgpresseEnvoye->getFullPath();
										$presse->cheminminiature = $imgpresseEnvoye->getMiniatureFilePath();
									}
								}
							}
							if (!DAOArtiste::addPresse($artiste, $presse)) {
								$updated = false;
							}
						}
					}
					if ($updated) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $idUser, 'presseUpdated' => true, 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'presseUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			}
			break;
		}
	case 'getArtistePresses': {
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$artiste = DAOArtiste::getByIdUser($idUser);
				$presses = $artiste->getPresses();
				$nbPresses = count($presses);
				$script = '';
				foreach ($presses as $id => $presse) {
					$script .= '<div class="button-tableau" data-fields="chPresses[]">';
					$script .= '<input class="date-magazine" type="text" value="' . htmlspecialchars($presse->getPeriode()) . '" id="chPresseDates_' . ($id + 1) . '" placeholder="2024">';
					$script .= '<input class="nom-magazine " type="text" value="' . htmlspecialchars($presse->getMagazine()) . '" id="chPresseOrganisme_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Le Monde">';
					$script .= '<input class="titre-article " type="text" value="' . htmlspecialchars($presse->getTitre()) . '" id="chPresseLibelle_' . ($id + 1) . '" class="alignement-button-deux" placeholder="Titre de l\'article">';
					//					$script .= '<button type=button data-action="modal" data-modal="chPresseModal_' . ($id + 1) . '" id="chPresseImg_' . ($id + 1) . '"><p>Ajouter une image</p></button>';
					$script .= '<input class="lien" type="text" value="' . htmlspecialchars($presse->getLien()) . '" id="chPresseLieu_' . ($id + 1) . '" placeholder="Lien de l\'article">';
					//					$script .= '<dialog id="chPresseModal_' . ($id + 1) . '" class="modal-depot-image-presse">';
					//					$script .= '<header >';
					//					$script .= '<h3 id="titrePresse_' . ($id + 1) . '">Ajouter une image ' . ($id + 1) . '</h3>';
					//					$script .= '<span id="modalClosePresse_' . ($id + 1) . '"class="modal-close">';
					//					$script .= '<svg id="svgClosePresse_' . ($id + 1) . '" enable-background="new 0 0 413.348 413.348" viewBox="0 0 413.348 413.348" xmlns="http://www.w3.org/2000/svg">';
					//					$script .= '<path id="pathPresse_' . ($id + 1) . '"d="m413.348 24.354-24.354-24.354-182.32 182.32-182.32-182.32-24.354 24.354 182.32 182.32-182.32 182.32 24.354 24.354 182.32-182.32 182.32 182.32 24.354-24.354-182.32-182.32z" />';
					//					$script .= '</svg>';
					//					$script .= '</span>';
					//					$script .= '</header>';
					//					$script .= '<section id="sectionPresse_' . ($id + 1) . '"class="modal-body">';
					//					$script .= '<div id="zoneImage_' . ($id + 1) . '"class="zone-image">';
					//					$script .= '<div class="input-div-depot" id="imgPresseContainer_' . ($id + 1) . '">';
					//					$script .= ' <img id="imgViewImagePresse_' . ($id + 1) . '" src="' . HTML_PUBLIC_IMAGES_DIR . 'miniatures/theme/oeuvre-voyage.jpg" alt="Image miniature" > ';
					//					$script .= '</div>';
					//					$script .= '</div>';
					//					$script .= '<div id="buttonImagePresse_' . ($id + 1) . '"class="button-zone-image">';
					//					$script .= '<input type="file" data-imgview="imgViewImagePresse_' . ($id + 1) . '" id="chInputImagePresse_' . ($id + 1) . '" class="file-oeuvre" name="chPresse" accept="image/tiff, image/jpeg, image/png, image/jpg" data-id="' . ($id + 1) . '">';
					//					$script .= '<input type="file" data-imgview="imgViewImagePresse_' . ($id + 1) . '" id="chInputChangeImagePresse_' . ($id + 1) . '" class="file-oeuvre" name="chChangePresse" accept="image/tiff, image/jpeg, image/png, image/jpg" data-id="' . ($id + 1) . '">';
					//					$script .= '<button id="btnAjoutPresse_' . ($id + 1) . '" onclick="appelInput(\'chInputImagePresse_' . ($id + 1) . '\')">Ajouter une image</button>';
					//					$script .= '<button id="btnReplacePresse_' . ($id + 1) . '">Remplacer l’image</button>';
					//					$script .= '<button id="btnSupprimerPresse_' . ($id + 1) . '">Supprimer l\'image</button>';
					//					$script .= '</div>';
					//					$script .= '</section>';
					//					$script .= '</dialog>';
					$script .= '</div>';
				}
				for ($index = $nbPresses; $index < 3; $index++) {
					$script .= '<div class="button-tableau" data-fields="chPresses[]">';
					$script .= '<input class="date-magazine" type="text" value="" id="chPresseDates_' . ($index + 1) . '" placeholder="2024">';
					$script .= '<input class="nom-magazine " type="text" value="" id="chPresseOrganisme_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Le Monde">';
					$script .= '<input class="titre-article " type="text" value="" id="chPresseLibelle_' . ($index + 1) . '" class="alignement-button-deux" placeholder="Titre de l\'article">';
					//					$script .= '<button type=button data-action="modal" data-modal="chPresseModal_' . ($index + 1) . '" id="chPresseImg_' . ($index + 1) . '"><p>Ajouter une image</p></button>';
					$script .= '<input class="lien" type="text" value="" id="chPresseLieu_' . ($index + 1) . '" placeholder="Lien de l\'article">';
					//					$script .= '<dialog id="chPresseModal_' . ($index + 1) . '" class="modal-depot-image-presse">';
					//					$script .= '<header >';
					//					$script .= '<h3 >Ajouter une image ' . ($index + 1) . '</h3>';
					//					$script .= '<span class="modal-close">';
					//					$script .= '<svg  enable-background="new 0 0 413.348 413.348" viewBox="0 0 413.348 413.348" xmlns="http://www.w3.org/2000/svg">';
					//					$script .= '<path d="m413.348 24.354-24.354-24.354-182.32 182.32-182.32-182.32-24.354 24.354 182.32 182.32-182.32 182.32 24.354 24.354 182.32-182.32 182.32 182.32 24.354-24.354-182.32-182.32z" />';
					//					$script .= '</svg>';
					//					$script .= '</span>';
					//					$script .= '</header>';
					//					$script .= '<section class="modal-body">';
					//					$script .= '<div id="zoneImage_' . ($index + 1) . '"class="zone-image">';
					//					$script .= '<div class="input-div-depot" id="imgPresseContainer_' . ($index + 1) . '">';
					//					$script .= ' <img id="imgViewImagePresse_' . ($index + 1) . '" src="' . HTML_PUBLIC_IMAGES_DIR . 'miniatures/theme/oeuvre-voyage.jpg" alt="Image Miniature" > ';
					//					$script .= '</div>';
					//					$script .= '</div>';
					//					$script .= '<div class="button-zone-image">';
					//					$script .= '<input type="file" data-imgview="imgViewImagePresse_' . ($index + 1) . '" id="chInputImagePresse_' . ($index + 1) . '" class="file-oeuvre" name="chPresse" accept="image/tiff, image/jpeg, image/png, image/jpg" data-id="' . ($index + 1) . '">';
					//					$script .= '<input type="file" data-imgview="imgViewImagePresse_' . ($index + 1) . '" id="chInputChangeImagePresse_' . ($index + 1) . '" class="file-oeuvre" name="chChangePresse" accept="image/tiff, image/jpeg, image/png, image/jpg" data-id="' . ($index + 1) . '">';
					//					$script .= '<button id="btnAjoutPresse_' . ($index + 1) . '" onclick="appelInput(\'chInputImagePresse_' . ($index + 1) . '\')">Ajouter une image</button>';
					//					$script .= '<button id="btnReplacePresse_' . ($index + 1) . '">Remplacer l’image</button>';
					//					$script .= '<button id="btnSupprimerPresse_' . ($index + 1) . '">Supprimer l\'image</button>';
					//					$script .= '</div>';
					//					$script .= '</section>';
					//					$script .= '</dialog>';
					$script .= '</div>';
				}
				http_response_code(200);
				print($script);
			} else {
				http_response_code(500);
				print(json_encode(['artisteFetched' => false, 'formationsFetched' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateImageAtelier':
	case 'updateImagePrincipale': {
			$image = ($action == 'updateImageAtelier' ? 'Atelier' : 'Principale');
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$user = DAOUser::getById($idUser);
				$artiste = DAOArtiste::getByIdUser($idUser);
				if (!empty($_FILES)) {
					$file = $_FILES['img' . $image];
					$fileSent = new AIImage($file['name'], $file['full_path'], $file['tmp_name'], $file['error'], $file['size'], $file['type']);
					if ($fileSent->getSize() > 0) {
						$fileSent->setLocalFilePath($artiste->getPersonalFolder() . 'profil/');
						if ($fileSent->moveFile(withMiniature: false)) {
							$artiste->setPhoto($image, $fileSent->getFullPath());
						}
					}
					if (BDD::openTransaction()) {
						if (DAOArtiste::updateImage($image, $artiste)) {
							http_response_code(200);
							print(json_encode(['artisteUpdated' => $artiste->getId(), 'imageUpdated' => true, 'result' => 'success']));
							BDD::commitTransaction();
						} else {
							http_response_code(500);
							print(json_encode(['artisteUpdated' => false, 'imageUpdated' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'imageUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'deleteImagePrincipale':
	case 'deleteImageAtelier': {
			$image = ($action == 'deleteImageAtelier' ? 'Atelier' : 'Principale');
			if (!empty($_POST)) {
				$idUser = $_POST['idUser'];
				$user = DAOUser::getById($idUser);
				$artiste = DAOArtiste::getByIdUser($idUser);
				if ($image == 'Principale') {
					@unlink($artiste->getImagePrincipale());
					$artiste->setImagePrincipale(null);
					$artiste->setTitreImagePrincipale(null);
				} else {
					@unlink($artiste->getImageAtelier());
					$artiste->setImageAtelier(null);
					$artiste->setTitreImageAtelier(null);
				}
				if (BDD::openTransaction()) {
					if (DAOArtiste::updateImage($image, $artiste)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => $artiste->getId(), 'imageUpdated' => true, 'imageReplacement' => HTML_PUBLIC_IMAGES_DIR . 'new_img/placeholder.png', 'result' => 'success']));
						BDD::commitTransaction();
					} else {
						http_response_code(500);
						print(json_encode(['artisteUpdated' => false, 'imageUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['artisteUpdated' => false, 'imageUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'deleteImagePreuve': {
			if (!empty($_POST)) {
				$idOeuvre = intval($_POST['idOeuvre'] ?? 0);
				if ($idOeuvre > 0) {
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					if (!is_null($oeuvre->getCheminPreuve())) {
						@unlink($oeuvre->getCheminPreuve());
						$oeuvre->setCheminPreuve(null);
						if (BDD::openTransaction()) {
							if (DAOOeuvre::updateImagePreuve($oeuvre)) {
								http_response_code(200);
								print(json_encode(['oeuvreUpdated' => $oeuvre->getId(), 'preuveUpdated' => true, 'result' => 'success']));
								BDD::commitTransaction();
							} else {
								http_response_code(500);
								print(json_encode(['oeuvreUpdated' => false, 'preuveUpdated' => false, 'result' => 'error']));
								BDD::rollbackTransaction();
							}
						}
					} else {
						http_response_code(200);
						print(json_encode(['oeuvreUpdated' => false, 'preuveUpdated' => false, 'result' => 'success']));
					}
				} else {
					http_response_code(200);
					print(json_encode(['oeuvreUpdated' => false, 'preuveUpdated' => false, 'result' => 'success']));
				}
			} else {
				http_response_code(500);
				print(json_encode(['oeuvreUpdated' => false, 'preuveUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'dropArtistKeyword':
	case 'addArtistKeyword': {
			if (!empty($_POST)) {
				$idUser = intval($_POST['idArtiste']);
				$artiste = DAOArtiste::getByIdUser($idUser);
				$keywordId = intval(htmlentities($_POST['idKeyWord']));
				if (BDD::openTransaction()) {
					$retAction = null;
					try {
						if ($action == 'addArtistKeyword') {
							$retAction = DAOArtiste::addArtistKeyword($artiste, $keywordId);
						} else {
							$retAction = DAOArtiste::delArtistKeyword($artiste, $keywordId);
						}
						if ($retAction) {
							http_response_code(200);
							print(json_encode(['userUpdated' => $idUser, 'infoUpdated' => true, 'result' => 'success']));
							BDD::commitTransaction();
						} else {
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					} catch (Exception $ex) {
						http_response_code(500);
						print(json_encode(['userUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}
			} else {
				http_response_code(500);
				print(json_encode(['userUpdated' => false, 'infoUpdated' => false, 'result' => 'error']));
			}
			break;
		}
	case 'updateArtisteAbsence': {
			$iduser = intval($_POST['idUser'] ?? 0);
			$artiste = DAOArtiste::getByIdUser($iduser);
			$chkAbsence = intval($_POST['absence']) ?? 0;
			$dateDebut = $_POST['datedebutabsence'] ?? '';
			$dateFin = $_POST['datefinabsence'] ?? '';
			if ($iduser > 0 and $artiste) {
				$user = DAOUser::getById($iduser);
				if (BDD::openTransaction()) {
					$periodeAbsence = new PeriodeAbsence($chkAbsence > 0, $dateDebut == '' ? null : date_create($dateDebut), $dateFin == '' ? null : date_create($dateFin));
					if (DAOArtiste::updateAbsence($artiste, $periodeAbsence)) {
						http_response_code(200);
						print(json_encode(['artisteUpdated' => true, 'result' => 'success']));
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
				print(json_encode(['userUpdated' => false, 'result' => 'error']));
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
