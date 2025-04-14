<?php
	/**
	 * @var string        $section
	 * @var string        $action
	 * @var User          $userLogged
	 * @var Artiste       $artisteLogged
	 * @var Commande      $commande
	 * @var LigneCommande $ligneCommande
	 *
	 * Controller pour les appels API
	 */

	use Stripe\Exception\ApiErrorException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'annulerventeoeuvre':
			{
				/**
				 * Action permettant au vendeur d'annuler
				 * Resultats :
				 *  statut ligne commande = 5
				 *    oeuvre statut = 2 -> Oeuvre validée
				 *    oeuvre statut shop = 1 -> pas à vendre
				 *  si toutes lignes commande à 5 alors statut commande à 4
				 *    Rembourser le montant correspondant sur Stripe
				 */
				if (empty($_POST) or is_null($artisteLogged)){
					http_response_code(500);
					print(json_encode(['statusUpdated' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idcommande'] ?? 0);
					$idOeuvre = intval($_POST['idoeuvre'] ?? 0);
					$idartiste = $artisteLogged->getId();
					if ($idCommande>0 and $idOeuvre>0){
						$transactionOk = true;
						BDD::openTransaction();
						$commande = DAOCommandeVendeur::getByIdVendeur($idCommande, $idartiste);
						$ligneCommande = $commande->getLigne($idOeuvre);
						if ($ligneCommande->getOeuvre()->getId() == $idOeuvre){
							$statuts = $ligneCommande->getStatuts();
							//							if (count($statuts)>1){
							//								$newStatut[] = $statuts[0];
							//								$statuts = $newStatut;
							//							}
							array_unshift($statuts, ['date_mouvement' => date_create('now'), 'statut' => DAOStatutLigneCommande::getById(5)]); //Passage de la ligne en annulée
							$ligneCommande->setStatuts($statuts);
							if (!DAOCommandeVendeur::replaceStatutsLigne($ligneCommande)){
								$transactionOk = false;
							}else{
								$ligneCommande->getOeuvre()->setStatutOeuvreShop(DAOStatutOeuvreShop::getById(1));
								$ligneCommande->getOeuvre()->setStatutOeuvre(DAOStatutOeuvre::getById(2));
								if (!DAOOeuvre::updateStatut($ligneCommande->getOeuvre())){
									$transactionOk = false;
								}else{
									if (DAOCommandeVendeur::getNbLignesAnnulees($commande) == count($commande->getLignes())){
										$commande->setStatutCommande(DAOStatutCommande::getById(4)); //Commande complètement Annulée
										if (!DAOCommandeVendeur::updateStatut($commande)){
											$transactionOk = false;
										}
										try{
											$paymentIntent = StripeToolkit::getStripeClient()->paymentIntents->retrieve($commande->getStripeInfos()->getPaymentIntentId());
											if ($paymentIntent->status == 'requires_payment_method' or $paymentIntent->status == 'requires_capture' or $paymentIntent->status == 'requires_confirmation' or $paymentIntent->status == 'requires_action'){
												$paymentIntent->cancel(['cancellation_reason' => 'abandoned']);
												$infosPayment = $commande->getStripeInfos();
												$infosPayment->setDatePaymentCanceled(date_create('now'));
												$infosPayment->setReasonPaymentCanceled("Annulé par l'artiste");
												DAOCommande::updateStripeInfos($infosPayment);
											}
										}catch (ApiErrorException $e){
											//TODO Informer AI que l'annulation du paiement a échoué et qu'il faut le faire manuellement
											$transactionOk = false;
										}
									}
								}
							}
						}
						if ($transactionOk){
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							http_response_code(200);
							print(json_encode(['statusUpdated' => true, 'commandeUpdated' => $commande, 'result' => 'success']));
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['statusUpdated' => false, 'result' => 'error', 'error' => 'Transaction erreur']));
						}
					}
				}
				break;
			}
		case 'updateStatutOeuvreCommande':
			{
				if (!empty($_POST)){
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$idOeuvre = intval($_POST['idOeuvre'] ?? 0);
					$commande = DAOCommandeVendeur::getById($idCommande);
					//				foreach ($commande->getLignes() as $ligneCommande){
					$ligneCommande = $commande->getLigne($idOeuvre);
					if ($ligneCommande->getOeuvre()->getId() == $idOeuvre){
						$statuts = $ligneCommande->getStatuts();
						//						if (count($statuts)>1){
						//							$newStatut[] = $statuts[0];
						//							$statuts = $newStatut;
						//						}
						array_unshift($statuts, ['date_mouvement' => date_create('now'), 'statut' => DAOStatutLigneCommande::getById(8) //Passage de la ligne disponibilité confirmée
						]);
						$ligneCommande->setStatuts($statuts);
						if (BDD::openTransaction()){
							if (DAOCommandeVendeur::replaceStatutsLigne($ligneCommande)){
								BDD::commitTransaction();
								//TODO : si toutes les lignes de la commande sont confirmées alors je capture le paiement
								//								BDD::rollbackTransaction();
								http_response_code(200);
								print(json_encode(['commandeUpdated' => true, 'photosUploaded' => true, 'result' => 'success']));
							}else{
								BDD::rollbackTransaction();
								http_response_code(500);
								print(json_encode(['commandeUpdated' => false, 'photosUploaded' => false, 'result' => 'Erreur enregistrement']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['commandeUpdated' => false, 'photosUploaded' => false, 'result' => 'Erreur Transaction']));
						}
					}
					//				}
				}else{
					http_response_code(500);
					print(json_encode(['commandeUpdated' => false, 'result' => 'Erreur parametres']));
				}
				break;
			}
		case 'sendColisPhotos':
			{
				if (!empty($_POST) && !empty($_FILES)){
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$idOeuvre = intval($_POST['idOeuvre'] ?? 0);
					$mailSent = true;
					$message = '';
					if ($idCommande>0 and $idOeuvre>0){
						$commande = DAOCommandeVendeur::getById($idCommande);
						$filesSent = array();
						for ($i = 0; $i<count($_FILES['photos']['name']); $i++){
							$fileSent = new AIFile($_FILES['photos']['name'][$i], $_FILES['photos']['full_path'][$i], $_FILES['photos']['tmp_name'][$i], $_FILES['photos']['error'][$i], $_FILES['photos']['size'][$i], $_FILES['photos']['type'][$i]);
							if ($fileSent->getSize()>0){
								$fileSent->setLocalFilePath($userLogged->getPersonalFolder() . 'documents/' . $commande->getNumero() . '/photos/');
								if ($fileSent->moveFile(includeDateTime: false)){
									$filesSent[] = $fileSent;
								}
							}
						}
						// Update statut lignecommande
						$transactionOk = true;
						BDD::openTransaction();
						$ligneCommandeValidee = null;
						//					foreach ($commande->getLignes() as $ligneCommande){
						$ligneCommande = $commande->getLigne($idOeuvre);
						if ($ligneCommande->getOeuvre()->getId() == $idOeuvre){
							$statuts = $ligneCommande->getStatuts();
							//							if (count($statuts)>1){
							//								$newStatut[] = $statuts[0];
							//								$statuts = $newStatut;
							//							}
							if ($statuts[0]['statut']->getId() === 7){
								$statuts[0]['date_mouvement'] = date_create('now');
							}else{
								array_unshift($statuts, ['date_mouvement' => date_create('now'), 'statut' => DAOStatutLigneCommande::getById(7)]); //Passage de la ligne validation par le transporteur
							}
							$ligneCommande->setStatuts($statuts);
							$ligneCommandeValidee = $ligneCommande;
							if (!DAOCommandeVendeur::replaceStatutsLigne($ligneCommande)){
								$transactionOk = false;
							}
						}
						//					}
						//J'envoie les photos par mail avec les informations de l'oeuvre
						if (count($filesSent)>0 and !is_null($ligneCommandeValidee)){
							$mailer = new Mailer();
							try{
								if ($mailer->isDefined()){
									//Destinataires
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-transport')->getValeur());
									$mailer->addCC(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									$mailer->addReplyTo($userLogged->getEmail(), $userLogged->getFullName());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = "Préparation du colis par " . $userLogged->getFullName() . " pour la commande " . $commande->getNumero();
									$oeuvreExpediee = $ligneCommandeValidee->getOeuvre();
									$largeur = $oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(2))->getValeur();
									$hauteur = $oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(1))->getValeur();
									$epaisseur = $oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(3))->getValeur();
									$libunitemesure = strtolower($oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(1))->getUnite()->getAbregeMesure());
									$poids = $oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(4))->getValeur();
									$libunitepoids = strtolower($oeuvreExpediee->getCaracteristique(DAOCaracteristique::getById(4))->getUnite()->getAbregePoids());
									$caracteristiques = $largeur . ' x ' . $hauteur . ' x ' . $epaisseur . '&nbsp;' . $libunitemesure . '&nbsp;' . $poids . '&nbsp;' . $libunitepoids;
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'titre' => $oeuvreExpediee->getTitre(), 'artiste_name' => $userLogged->getFullName(), 'artiste_email' => $userLogged->getEmail(), 'titre_oeuvre' => $ligneCommandeValidee->getOeuvre()->getTitre(), 'oeuvre_caracteristiques' => $caracteristiques, 'origine' => $userLogged->getAdresseFacturation(), 'destination' => $commande->getAdresseLivraison(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_colis_photos.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_colis_photos.txt');
									$mailer->AltBody = $mailer->compileText();
									// Traitement des pièces jointes
									/**
									 * @var AIFile $photo
									 */
									if (!empty($filesSent)){
										foreach ($filesSent as $photo){
											$mailer->addAttachment(path: $photo->getFullPath(), name: $photo->getName(), type: $photo->getType(), disposition: 'attachment');
										}
									}
									if ($mailer->send()){
										$mailSent = true;
										$message = 'envoyé';
									}else{
										$mailSent = false;
										$message = $mailer->ErrorInfo;
									}
								}
							}catch (Exception $e){
								$mailSent = false;
								$message = $mailer->ErrorInfo;
							}
						}
						if ($transactionOk){
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							http_response_code(200);
							print(json_encode(['commandeUpdated' => true, 'photosUploaded' => true, 'result' => 'success', 'mailsent' => $mailSent, 'mail_msg' => $message]));
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['commandeUpdated' => false, 'photosUploaded' => false, 'result' => 'error', 'mailsent' => $mailSent, 'mail_msg' => $message]));
						}
					}else{
						http_response_code(500);
						print(json_encode(['commandeUpdated' => false, 'photosUploaded' => false, 'result' => 'error', 'mailsent' => $mailSent, 'mail_msg' => $message]));
					}
				}else{
					http_response_code(500);
					print(json_encode(['commandeUpdated' => false, 'photosUploaded' => false, 'result' => 'error']));
				}
				break;
			}
		case 'sendCertificat':
			{
				if (!empty($_POST) && !empty($_FILES)){
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$idOeuvre = intval($_POST['idOeuvre'] ?? 0);
					$mailSent = true;
					$message = '';
					if ($idCommande>0 and $idOeuvre>0 and count($_FILES['certificat']['name'])>0){
						$commande = DAOCommandeVendeur::getById($idCommande);
						$ligneCommande = $commande->getLigne($idOeuvre);
						$oeuvre = $ligneCommande->getOeuvre();
						if ($ligneCommande->getOeuvre()->getId() == $idOeuvre){
							$i = 0;
							$fileSent = new AIFile($_FILES['certificat']['name'][$i], $_FILES['certificat']['full_path'][$i], $_FILES['certificat']['tmp_name'][$i], $_FILES['certificat']['error'][$i], $_FILES['certificat']['size'][$i], $_FILES['certificat']['type'][$i]);
							if ($fileSent->getSize()>0){
								$fileSent->setLocalFilePath($userLogged->getPersonalFolder() . 'documents/' . $commande->getNumero() . '/certificats/');
								if ($fileSent->moveFile(includeDateTime: false)){
									$ligneCommande->setCheminCertificat($fileSent->getFullPath());
									DAOCommande::updateCertificat($ligneCommande);
									$oeuvre->setCheminCertificat($fileSent->getFullPath());
									DAOOeuvre::updateCertificat($oeuvre);
								}
							}
						}
						//J'envoie le photos par mail
						if (!is_null($ligneCommande->getCheminCertificat())){
							$mailer = new Mailer();
							try{
								if ($mailer->isDefined()){
									//Destinataires
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-transport')->getValeur());
									$mailer->addCC(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									$mailer->addReplyTo($userLogged->getEmail(), $userLogged->getFullName());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = "Certificat de l'oeuvre " . $oeuvre->getTitre() . " pour la commande " . $commande->getNumero();
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'titre_oeuvre' => $oeuvre->getTitre(), 'artiste_name' => $userLogged->getFullName(), 'artiste_email' => $userLogged->getEmail(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_certificat_photos.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_certificat_photos.txt');
									$mailer->AltBody = $mailer->compileText();
									// Traitement des pièces jointes
									if (isset($fileSent)){
										$mailer->addAttachment(path: $fileSent->getFullPath(), name: $fileSent->getName(), type: $fileSent->getType(), disposition: 'attachment');
									}
									if ($mailer->send()){
										$mailSent = true;
										$message = 'envoyé';
									}else{
										$mailSent = false;
										$message = $mailer->ErrorInfo;
									}
								}
							}catch (Exception $e){
								$mailSent = false;
								$message = $mailer->ErrorInfo;
							}
						}
						if ($mailSent){
							http_response_code(200);
							print(json_encode(['commandeUpdated' => true, 'certificatSent' => true, 'result' => 'success', 'mailsent' => $mailSent, 'mail_msg' => $message]));
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['commandeUpdated' => false, 'certificatSent' => false, 'result' => 'error', 'mailsent' => $mailSent, 'mail_msg' => $message]));
						}
					}else{
						http_response_code(500);
						print(json_encode(['commandeUpdated' => false, 'certificatSent' => false, 'result' => 'error', 'mailsent' => $mailSent, 'mail_msg' => $message]));
					}
				}else{
					http_response_code(500);
					print(json_encode(['commandeUpdated' => false, 'certificatSent' => false, 'result' => 'error']));
				}
				break;
			}
		default:
			{
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}
