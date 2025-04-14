<?php
	/**
	 * @var string $section
	 * @var string $action
	 * @var User $userLogged
	 *
	 * Controller pour les appels API
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'getpourcnegocartiste':
			{
				$idArtiste = $_GET['idartiste'] ?? 0;
				$idOeuvre = $_GET['idoeuvre'] ?? 0;
				if ($idArtiste>0 and $idOeuvre>0){
					$artiste = DAOArtiste::getById($idArtiste);
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					$tauxNegociation = 0;
					if ($oeuvre->isNegociable() and (!is_null($artiste->getTauxNegociation()) and $artiste->getTauxNegociation()->getTaux()>0) and $oeuvre->getIdArtiste() == $idArtiste){
						$tauxNegociation = $artiste->getTauxNegociation()->getTaux();
					}
					http_response_code(200);
					print(json_encode(['cartFetched' => true, 'tauxNegociation' => $tauxNegociation, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['cartFetched' => false, 'tauxNegociation' => 0, 'result' => 'error']));
				}
				break;
			}
		case 'proposerprix':
			{
				if (!empty($_POST)){
					$idArtiste = $_POST['idartiste'] ?? 0;
					$idOeuvre = $_POST['idoeuvre'] ?? 0;
					$montantPropose = $_POST['montantpropose'] ?? 0;
					$idAcheteur = $userLogged->getId();
					if (BDD::openTransaction()){
						$artiste = DAOArtiste::getById($idArtiste);
						$oeuvre = DAOOeuvre::getById($idOeuvre);

						if ($idAcheteur > 0 AND $oeuvre->isNegociable() and (!is_null($artiste->getTauxNegociation()) and $artiste->getTauxNegociation()->getTaux()>0) and $oeuvre->getIdArtiste() == $idArtiste){
							$tauxNegociation = $artiste->getTauxNegociation()->getTaux();
							$prixMini = ceil($oeuvre->getPrixUnitaire() * (1 - ($tauxNegociation / 100)));
							if ($montantPropose >= $prixMini){
								// Création de la négociation
								$negociation = new NegociationOeuvre($idOeuvre, $idAcheteur, $montantPropose);
								if (DAONegociationOeuvre::insert($negociation)){
									$mouvementNegociation = new MouvementNegociationOeuvre($negociation->getId(), $montantPropose);
									if (DAOMouvementNegociationOeuvre::insert($mouvementNegociation)){
										$negociation->addMouvement($mouvementNegociation);
										$oeuvre->setStatutOeuvre(DAOStatutOeuvre::getById(7)); // En cours de négociation
										if (DAOOeuvre::updateStatut($oeuvre)){
											BDD::commitTransaction();
//											BDD::rollbackTransaction();

											/**
											 * Envoi des mails :
											 * un pour AI
											 */
											$mailer = null;
											try{
												$mailer = new Mailer();
												if ($mailer->isDefined()){
													/**
													 * Envoi du mail a AI de la transaction
													 */
													//Adresses
													$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
													$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
													$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
													//Content
													$mailer->isHTML(); // Défini le mail au format HTML
													$mailer->Subject = $mailer->getSenderApp() . " : Nouvelle négociation sur l'oeuvre " . $oeuvre->getTitre() . " de " . $artiste->getUser()->getFullName();
													$mailer->setVariables([
														'url_image_logo' => 'cid:art_interactivities_logo',
														'senderapp' => $mailer->getSenderApp(),
														'acheteur_civilite' => $userLogged->getCivilite()->getLibelle(),
														'acheteur_nom' => $userLogged->getFullName(),
														'acheteur_email' => $userLogged->getEmail(),
														'acheteur_telephone' => $userLogged->getTelPortable(),
														'acheteur_date_naissance' => $userLogged->getDateNaissance()->format('d/m/Y'),
														'vendeur_civilite' => $artiste->getUser()->getCivilite()->getLibelle(),
														'vendeur_nom' => $artiste->getUser()->getFullName(),
														'vendeur_email' => $artiste->getUser()->getEmail(),
														'vendeur_telephone' => $artiste->getUser()->getTelPortable() . ' / ' . $artiste->getUser()->getTelFixe(),
														'titre_oeuvre' => $oeuvre->getTitre(),
														'lien_oeuvre' => EXTERNAL_URL . $oeuvre->getUrl(),
														'montant_propose' => formatMontant($montantPropose),
														'annee' => date('Y'),
														'nom_entreprise' => $mailer->getSenderApp(),
														'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]
													);
													$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
													$mailer->setTemplateHtml('core/views/template/mails/mail_demande_negociation_oeuvre_ai.phtml');
													$mailer->Body = $mailer->compileHTML();
													$mailer->setTemplateText('core/views/template/mails/mail_demande_negociation_oeuvre_ai.txt');
													$mailer->AltBody = $mailer->compileText();
													$mailer->send();
													//debug('Mail '.$mailer->Subject.' envoyé à '.DAOParametres::getByLibelle('destemail-vente')->getValeur());
													$mailer = null;
												}
												http_response_code(200);
												print(json_encode(['negociationCreated' => true, 'result' => 'success']));
											}catch (Exception $e){
												if (!is_null($mailer)){
													$message = $mailer->ErrorInfo;
												}else{
													$message = "Erreur mailer";
												}
												http_response_code(500);
												print(json_encode(['creationCustomCommande' => false, 'result' => 'error', 'error' => $message]));
											}
										}else{
											BDD::rollbackTransaction();
											http_response_code(500);
											print(json_encode(['negociationCreated' => false, 'result' => 'error', 'message' => 'Erreur update oeuvre']));
										}
									}else{
										BDD::rollbackTransaction();
										http_response_code(500);
										print(json_encode(['negociationCreated' => false, 'result' => 'error', 'message' => 'Erreur insert mvt']));
									}
								}else{
									BDD::rollbackTransaction();
									http_response_code(500);
									print(json_encode(['negociationCreated' => false, 'result' => 'error', 'message' => 'Erreur insert negoc']));
								}
							}else{
								BDD::rollbackTransaction();
								http_response_code(500);
								print(json_encode(['negociationCreated' => false, 'result' => 'error', 'message' => 'Mauvais prix']));
							}
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['negociationCreated' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'transmitNegociationVendeur':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['transmitNegociationVendeur' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idNegociation = intval($_POST['idnegociation'] ?? 0);
					if ($idNegociation > 0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la négociation actuelle
						$negociation = DAONegociationOeuvreAdmin::getById($idNegociation);
						$acheteur = DAOUser::getById($negociation->getIdAcheteur());
						$oeuvre = DAOOeuvre::getById($negociation->getIdOeuvre());
						$artiste = DAOArtiste::getById($oeuvre->getIdArtiste());
						$vendeur = $artiste->getUser();

						//Ensuite j'update le statut de la négociation
						$negociation->setStatutNegociation(DAOStatutNegociation::getById(4));
						if (!DAONegociationOeuvreAdmin::updateStatut($negociation)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification negociation';
							http_response_code(500);
							print(json_encode(['transmitNegociationVendeur' => false, 'result' => 'error', 'error' => $message]));
						}else{
							BDD::commitTransaction();
//							BDD::rollbackTransaction();
							/**
							 * Envoi du mail pour le vendeur
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail au vendeur
									 */
									$languageVendeur = is_null($artiste->getUser()->getLocaleTrad()) ? 'fr' : $artiste->getUser()->getLocaleTrad()->getLibelle();
									$lang = substr($languageVendeur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $artiste->getUser()->getFullName());
									}else{
										$mailer->addAddress($artiste->getUser()->getEmail(), $artiste->getUser()->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Une nouvelle proposition de négociation vient de vous être adressée";
									$contenuCommande = '';

									$variables = [
										'url_image_logo' => 'cid:art_interactivities_logo',
										'senderapp' => $mailer->getSenderApp(),
										'date_proposition' => $negociation->getDateCreation()->format('d/m/Y'),
										'titre_oeuvre' => $oeuvre->getTitre(),
										'lien_oeuvre' => EXTERNAL_URL . $oeuvre->getUrl(),
										'montant_propose' => formatMontant($negociation->getMontantNegociation()),
										'annee' => date('Y'),
										'nom_entreprise' => $mailer->getSenderApp(),
										'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')
									];
									$templateHTML = 'core/views/template/mails/mail_transmission_negociation_vendeur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_transmission_negociation_vendeur_' . $lang . '.txt';
									$mailer->setVariables($variables);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml($templateHTML);
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText($templateTXT);
									$mailer->AltBody = $mailer->compileText();
									$mailer->send();
									//debug('Mail '.$mailer->Subject.' envoyé à '.$acheteur->getEmail().'->'.$acheteur->getPseudo());
									$mailer = null;
								}
								http_response_code(200);
								print(json_encode(['transmitNegociationVendeur' => true, 'negociationId' => $negociation->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['transmitNegociationVendeur' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['transmitNegociationVendeur' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'annulernegociationoeuvre':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idNegociation = intval($_POST['idnegociation'] ?? 0);
					if ($idNegociation > 0){
						$message = 'OK';
						BDD::openTransaction();

						//Je commence par récupérer la négociation actuelle
						$negociation = DAONegociationOeuvreAdmin::getById($idNegociation);
						$acheteur = DAOUser::getById($negociation->getIdAcheteur());
						$oeuvre = DAOOeuvre::getById($negociation->getIdOeuvre());
						$artiste = DAOArtiste::getById($oeuvre->getIdArtiste());
						$vendeur = $artiste->getUser();

						//Ensuite j'update le statut de la négociation
						$negociation->setStatutNegociation(DAOStatutNegociation::getById(2));
						$negociation->setDateCloture(date_create('now'));
						if (!DAONegociationOeuvreAdmin::updateStatut($negociation)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification negociation';
							http_response_code(500);
							print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => $message]));
						}else{
							// Je remets l'oeuvre en vente
							$oeuvre->setStatutOeuvre(DAOStatutOeuvre::getById(2));
							$oeuvre->setStatutOeuvreShop(DAOStatutOeuvreShop::getById(2));
							if (!DAOOeuvreAdmin::updateStatut($oeuvre)){
								BDD::rollbackTransaction();
								$message = 'Erreur modification oeuvre';
								http_response_code(500);
								print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => $message]));
							}else{
								BDD::commitTransaction();
//								BDD::rollbackTransaction();
								/**
								 * Envoi du mail pour l'acheteur
								 */
								$mailer = null;
								try{
									$mailer = new Mailer();
									if ($mailer->isDefined()){
										/**
										 * Envoi du mail à l'acheteur
										 */
										$languageAcheteur = is_null($acheteur->getLocaleTrad()) ? 'fr' : $acheteur->getLocaleTrad()->getLibelle();
										$lang = substr($languageAcheteur, 0, 2);
										$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
										if (DEV_MODE){
											$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $acheteur->getFullName());
										}else{
											$mailer->addAddress($acheteur->getEmail(), $acheteur->getFullName());
										}
										//Content
										$mailer->isHTML(); // Défini le mail au format HTML
										$mailer->Subject = $mailer->getSenderApp() . " : Annulation de votre négociation par Art Interactivities";
										$contenuCommande = '';

										$variables = [
											'url_image_logo' => 'cid:art_interactivities_logo',
											'senderapp' => $mailer->getSenderApp(),
											'date_proposition' => $negociation->getDateCreation()->format('d/m/Y'),
											'titre_oeuvre' => $oeuvre->getTitre(),
											'montant_propose' => formatMontant($negociation->getMontantNegociation()),
											'annee' => date('Y'),
											'nom_entreprise' => $mailer->getSenderApp(),
											'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')
										];
										$templateHTML = 'core/views/template/mails/mail_refus_negociation_acheteur_' . $lang . '.phtml';
										$templateTXT = 'core/views/template/mails/mail_refus_negociation_acheteur_' . $lang . '.txt';
										$mailer->setVariables($variables);
										$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
										$mailer->setTemplateHtml($templateHTML);
										$mailer->Body = $mailer->compileHTML();
										$mailer->setTemplateText($templateTXT);
										$mailer->AltBody = $mailer->compileText();
										$mailer->send();
										//debug('Mail '.$mailer->Subject.' envoyé à '.$acheteur->getEmail().'->'.$acheteur->getPseudo());
										$mailer = null;
									}
									http_response_code(200);
									print(json_encode(['cancelNegociation' => true, 'negociationId' => $negociation->getId(), 'result' => 'success']));
								}catch (Exception $e){
									if (!is_null($mailer)){
										$message = $mailer->ErrorInfo;
									}else{
										$message = "Erreur mailer";
									}
									http_response_code(500);
									print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => $message]));
								}
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'validnegociationoeuvre':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['validNegociation' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idNegociation = intval($_POST['idnegociation'] ?? 0);
					if ($idNegociation > 0){
						$message = 'OK';
						BDD::openTransaction();

						//Je commence par récupérer la négociation actuelle
						$negociation = DAONegociationOeuvreAdmin::getById($idNegociation);
						$acheteur = DAOUser::getById($negociation->getIdAcheteur());
						$oeuvre = DAOOeuvre::getById($negociation->getIdOeuvre());
						$artiste = DAOArtiste::getById($oeuvre->getIdArtiste());
						$vendeur = $artiste->getUser();

						// J'ajoute le mouvement
						$mouvementsNegociations = $negociation->getMouvements();
						/**
						 * @var MouvementNegociationOeuvre $lastMouvement
						 */
						$lastMouvement = end($mouvementsNegociations);
						$montantValide = $lastMouvement->getMontantVendeur() ?? $lastMouvement->getMontantAcheteur();
						$mouvementNegociation = new MouvementNegociationOeuvre($negociation->getId(), $montantValide);
						$mouvementNegociation->setMontantAcheteur($montantValide);
						$mouvementNegociation->setMontantVendeur($montantValide);
						$mouvementNegociation->setMontantFinal($montantValide);
						$mouvementNegociation->setDateMouvement(date_create('now'));

						if (!DAOMouvementNegociationOeuvre::insert($mouvementNegociation)){
							BDD::rollbackTransaction();
							$message = 'Erreur mouvement negociation';
							http_response_code(500);
							print(json_encode(['validNegociation' => false, 'result' => 'error', 'error' => $message]));
						}else{
							$negociation->addMouvement($mouvementNegociation);

							//Ensuite j'update le statut de la négociation et le prix négocié
							$negociation->setStatutNegociation(DAOStatutNegociation::getById(6));
							$negociation->setDateCloture(date_create('now'));
							$negociation->setMontantNegociationFinal($montantValide);

							if (!DAONegociationOeuvreAdmin::update($negociation)){
								BDD::rollbackTransaction();
								$message = 'Erreur modification negociation';
								http_response_code(500);
								print(json_encode(['validNegociation' => false, 'result' => 'error', 'error' => $message]));
							}else{
								BDD::commitTransaction();
//								BDD::rollbackTransaction();
								/**
								 * Envoi des mails pour l'acheteur et le vendeur
								 */
								$mailer = null;
								try{
									$mailer = new Mailer();
									if ($mailer->isDefined()){
										/**
										 * Envoi du mail au vendeur
										 */
										$languageVendeur = is_null($artiste->getUser()->getLocaleTrad()) ? 'fr' : $artiste->getUser()->getLocaleTrad()->getLibelle();
										$lang = substr($languageVendeur, 0, 2);
										$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
										if (DEV_MODE){
											$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $artiste->getUser()->getFullName());
										}else{
											$mailer->addAddress($artiste->getUser()->getEmail(), $artiste->getUser()->getFullName());
										}
										//Content
										$mailer->isHTML(); // Défini le mail au format HTML
										$mailer->Subject = $mailer->getSenderApp() . " : Validation de la négociation par Art Interactivities";
										$contenuCommande = '';
										$variables = [
											'url_image_logo' => 'cid:art_interactivities_logo',
											'senderapp' => $mailer->getSenderApp(),
											'date_proposition' => $negociation->getDateCreation()->format('d/m/Y'),
											'titre_oeuvre' => $oeuvre->getTitre(),
											'montant_propose' => formatMontant($negociation->getMontantNegociationFinal()),
											'annee' => date('Y'),
											'nom_entreprise' => $mailer->getSenderApp(),
											'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')
										];
										$templateHTML = 'core/views/template/mails/mail_valide_negociation_vendeur_' . $lang . '.phtml';
										$templateTXT = 'core/views/template/mails/mail_valide_negociation_vendeur_' . $lang . '.txt';
										$mailer->setVariables($variables);
										$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
										$mailer->setTemplateHtml($templateHTML);
										$mailer->Body = $mailer->compileHTML();
										$mailer->setTemplateText($templateTXT);
										$mailer->AltBody = $mailer->compileText();
										$mailer->send();
										//debug('Mail '.$mailer->Subject.' envoyé à '.$acheteur->getEmail().'->'.$acheteur->getPseudo());
										$mailer = null;
									}

									$mailer = new Mailer();
									if ($mailer->isDefined()){
										/**
										 * Envoi du mail à l'acheteur
										 */
										$languageAcheteur = is_null($acheteur->getLocaleTrad()) ? 'fr' : $acheteur->getLocaleTrad()->getLibelle();
										$lang = substr($languageAcheteur, 0, 2);
										$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
										if (DEV_MODE){
											$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $acheteur->getFullName());
										}else{
											$mailer->addAddress($acheteur->getEmail(), $acheteur->getFullName());
										}
										//Content
										$mailer->isHTML(); // Défini le mail au format HTML
										$mailer->Subject = $mailer->getSenderApp() . " : Validation de la négociation par Art Interactivities";
										$contenuCommande = '';
										$variables = [
											'url_image_logo' => 'cid:art_interactivities_logo',
											'senderapp' => $mailer->getSenderApp(),
											'date_proposition' => $negociation->getDateCreation()->format('d/m/Y'),
											'titre_oeuvre' => $oeuvre->getTitre(),
											'montant_propose' => formatMontant($negociation->getMontantNegociationFinal()),
											'annee' => date('Y'),
											'nom_entreprise' => $mailer->getSenderApp(),
											'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')
										];
										$templateHTML = 'core/views/template/mails/mail_valide_negociation_acheteur_' . $lang . '.phtml';
										$templateTXT = 'core/views/template/mails/mail_valide_negociation_acheteur_' . $lang . '.txt';
										$mailer->setVariables($variables);
										$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
										$mailer->setTemplateHtml($templateHTML);
										$mailer->Body = $mailer->compileHTML();
										$mailer->setTemplateText($templateTXT);
										$mailer->AltBody = $mailer->compileText();
										$mailer->send();
										//debug('Mail '.$mailer->Subject.' envoyé à '.$acheteur->getEmail().'->'.$acheteur->getPseudo());
										$mailer = null;
									}
									http_response_code(200);
									print(json_encode(['validNegociation' => true, 'negociationId' => $negociation->getId(), 'result' => 'success']));
								}catch (Exception $e){
									if (!is_null($mailer)){
										$message = $mailer->ErrorInfo;
									}else{
										$message = "Erreur mailer";
									}
									http_response_code(500);
									print(json_encode(['validNegociation' => false, 'result' => 'error', 'error' => $message]));
								}
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['cancelNegociation' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
			case 'rm':
			{
				if (!empty($_POST)){
					$idPanier = $_POST['idpanier'] ?? 0;
					$idOeuvre = $_POST['idoeuvre'] ?? 0;
					if (BDD::openTransaction()){
						$panier = DAOPanier::getByUniqueId($idPanier);
						if (DAOPanier::deleteLigne($panier->getId(), $idOeuvre)){
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							http_response_code(200);
							$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
							$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
							print(json_encode(['cartUpdated' => true, 'cartContent' => $panier, 'result' => 'success']));
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['cartUpdated' => false, 'result' => 'error']));
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
