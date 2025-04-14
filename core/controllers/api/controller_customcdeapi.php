<?php
	/**
	 * @var string              $section
	 * @var string              $action
	 * @var User                $userLogged
	 * @var Artiste             $artisteLogged
	 * @var CommandeCustom      $commande
	 * @var LigneCommandeCustom $ligneCommande
	 * @var Devises             $defaultCurrency
	 *
	 * Controller pour les appels API
	 */

	use Random\RandomException;
	use Stripe\Exception\ApiErrorException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'creerCommandeCustom':
			{
				/**
				 * Action permettant de créer une nouvelle commande sur mesure
				 * Cela crée également un compte pour l'utilisateur qui devra renseigner son mot de passe pour l'activer
				 */
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['creationCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idArtiste = intval($_POST['idArtiste'] ?? 0);
					$civilite = intval($_POST['civilite'] ?? 0);
					$emailUser = htmlentities(strtolower($_POST['email'] ?? ''));
					$nomUser = htmlentities(strtoupper($_POST['nom'] ?? ''));
					$prenomUser = htmlentities(ucfirst($_POST['prenom'] ?? ''));
					$telUser = htmlentities($_POST['tel'] ?? '');
					$dateNaissance = strtolower($_POST['datenaissance'] ?? '');
					$budget = floatval($_POST['budget'] ?? 0);
					$details = htmlentities($_POST['details'] ?? '');
					$newUser = true;
					if ($idArtiste>0 and $civilite>0 and $budget>0 and $emailUser != '' and $nomUser != '' and $prenomUser != '' and $telUser != '' and $details != ''){
						$transactionOk = true;
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par créer le compte utilisateur
						$userCommande = new User('', $emailUser, '', $nomUser, $prenomUser, null, $telUser, null);
						$userCommande->setCivilite(DAOCivilite::getById($civilite));
						$userCommande->setStatut(DAOStatutUser::getById(1)); // 1 : en attente
						$userCommande->addRole(DAORoleUser::getByLibelle('Acheteur'));
						$userCommande->setDateNaissance(date_create($dateNaissance));
						$userCommande->setTypesAcheteur(DAOTypeAcheteur::getById(25));
						$userCommande->createPersonalFolder();
						if (DAOUser::userExists($emailUser)){
							$newUser = false;
							$userCommande = DAOUser::getById(DAOUser::getIdByLogin($emailUser));
						}else{
							if (!DAOUser::insert($userCommande)){
								$message = "Erreur d'insertion de l'utilisateur !";
								BDD::rollbackTransaction();
								$transactionOk = false;
							}
						}
						if ($transactionOk){
							//Ensuite je crée la commande
							$commande = new CommandeCustom();
							$commande->setNumero(null);
							$commande->setAdresseFacturation(null);
							$commande->setAdresseLivraison(null);
							$commande->setArtiste(DAOArtiste::getById($idArtiste));
							$commande->setDateCommande(date_create('now'));
							$commande->setEmailUser($emailUser);
							$commande->setNomUser($nomUser);
							$commande->setPrenomUser($prenomUser);
							$commande->setStatutCommande(DAOStatutCommandeCustom::getById(1)); // En cours
							$commande->setTelUser($telUser);
							$commande->setUser($userCommande);
							$commande->setTypeExpedition(DAOTypeExpedition::getById(2)); // Main propre
							if (!DAOCommandeCustom::insert($commande)){
								$message = 'Erreur insertion commande';
								$transactionOk = false;
							}else{
								$ligneCommande = new LigneCommandeCustom();
								$ligneCommande->setDescription($details);
								$ligneCommande->setDeviseAchat($defaultCurrency);
								$ligneCommande->setPrixDefinitif($budget);
								$ligneCommande->setQuantite(1);
								$ligneCommande->setIdCommandeCustom($commande->getId());
								if (!DAOCommandeCustom::insertLigne($ligneCommande)){
									$message = 'Erreur création ligne commande';
									$transactionOk = false;
								}else{
									$commande->addLigne($ligneCommande);
									$mvtNegociation = new MouvementNegociationCommande($budget, $details, DAOTypeActeurNegociation::getById(1), date_create('now'));
									$mvtNegociation->setIdCommandeCustom($commande->getId());
									if (!DAOMouvementNegociationCommande::insert($mvtNegociation)){
										$message = 'Erreur création mouvement commande';
										$transactionOk = false;
									}else{
										$commande->addMouvement($mvtNegociation);
									}
								}
							}
						}
						if ($transactionOk){
							//						$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//						debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation de sa commande et de la création de son compte
									 */
									$languageAcheteur = is_null($userCommande->getLocaleTrad()) ? 'fr' : $userCommande->getLocaleTrad()->getLibelle();
									$lang = substr($languageAcheteur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $userCommande->getFullName());
									}else{
										$mailer->addAddress($userCommande->getEmail(), $userCommande->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Confirmation de votre commande " . $commande->getNumero();
									if ($newUser){
										try{
											$token = DAOUser::getTokenForNewPassword($userCommande->getEmail(), true);
										}catch (RandomException|Exception $e){
											$token = null;
										}
										$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $userCommande->getFullName(), 'acheteur_email' => $userCommande->getEmail(), 'detail_commande' => formatMontant($commande->getMouvements()[0]->getMontant()), 'montant_commande' => formatMontant($commande->getMouvements()[0]->getMontant()), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'url_create_password' => EXTERNAL_URL . getUrl('utilisateur', 'new-password', 'define-new-password', ['token' => $token]), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
										$templateHTML = 'core/views/template/mails/mail_validation_commande_custom_acheteur_newcompte_' . $lang . '.phtml';
										$templateTXT = 'core/views/template/mails/mail_validation_commande_custom_acheteur_newcompte_' . $lang . '.txt';
									}else{
										$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $userCommande->getFullName(), 'detail_commande' => formatMontant($commande->getMouvements()[0]->getMontant()), 'montant_commande' => formatMontant($commande->getMouvements()[0]->getMontant()), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
										$templateHTML = 'core/views/template/mails/mail_validation_commande_custom_acheteur_' . $lang . '.phtml';
										$templateTXT = 'core/views/template/mails/mail_validation_commande_custom_acheteur_' . $lang . '.txt';
									}
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
									 * Envoi du mail a AI de la transaction
									 */
									//Adresses
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Nouvelle commande sur mesure pour " . $commande->getArtiste()->getUser()->getFullName();
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $userCommande->getCivilite()->getLibelle(), 'acheteur_nom' => $userCommande->getFullName(), 'acheteur_email' => $userCommande->getEmail(), 'acheteur_telephone' => $userCommande->getTelPortable(), 'acheteur_date_naissance' => $userCommande->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $commande->getArtiste()->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $commande->getArtiste()->getUser()->getFullName(), 'vendeur_email' => $commande->getArtiste()->getUser()->getEmail(), 'vendeur_telephone' => $commande->getArtiste()->getUser()->getTelPortable() . ' / ' . $commande->getArtiste()->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'details_commande' => nl2br($commande->getMouvements()[0]->getDetails()), 'montant_commande' => formatMontant($commande->getLignes()[0]->getPrixDefinitif()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_validation_commande_custom_ai.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_validation_commande_custom_ai.txt');
									$mailer->AltBody = $mailer->compileText();
									$mailer->send();
									//debug('Mail '.$mailer->Subject.' envoyé à '.DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer = null;
								}
								http_response_code(200);
								print(json_encode(['creationCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
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
							print(json_encode(['creationCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}
					}
				}
				break;
			}
		case 'completerCommandeCustom':
			{
				/**
				 * Action permettant de compléter commande sur mesure
				 */
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['complementCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$budget = floatval($_POST['budget'] ?? 0);
					$details = htmlentities($_POST['details'] ?? '');
					$newUser = true;
					if ($idCommande>0 and $budget>0 and $details != ''){
						$transactionOk = true;
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$userCommande = $commande->getUser();
						//Ensuite je rajoute le mouvement
						$mvtNegociation = new MouvementNegociationCommande($budget, $details, DAOTypeActeurNegociation::getById(1), date_create('now'));
						$mvtNegociation->setIdCommandeCustom($commande->getId());
						if (!DAOMouvementNegociationCommande::insert($mvtNegociation)){
							$message = 'Erreur création mouvement commande';
							$transactionOk = false;
						}else{
							$commande->addMouvement($mvtNegociation);
							//Je mets à jour le prix de la ligne commande
							$ligneCommande = $commande->getLignes()[0];
							$ligneCommande->setPrixDefinitif($mvtNegociation->getMontant());
							if (!DAOCommandeCustom::updateLigne($ligneCommande)){
								$message = 'Erreur modification ligne commande';
								$transactionOk = false;
							}
						}
						if ($transactionOk){
							//						$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//						debug($commandeCustom);
							//
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation de sa commande et de la création de son compte
									 */
									$languageAcheteur = is_null($userCommande->getLocaleTrad()) ? 'fr' : $userCommande->getLocaleTrad()->getLibelle();
									$lang = substr($languageAcheteur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $userCommande->getFullName());
									}else{
										$mailer->addAddress($userCommande->getEmail(), $userCommande->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Complément de votre commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $userCommande->getFullName(), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_complement_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_complement_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail a AI de la transaction
									 */
									//Adresses
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Définit le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Ajout de complément pour la commande " . $commande->getId();
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $userCommande->getCivilite()->getLibelle(), 'acheteur_nom' => $userCommande->getFullName(), 'acheteur_email' => $userCommande->getEmail(), 'acheteur_telephone' => $userCommande->getTelPortable(), 'acheteur_date_naissance' => $userCommande->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $commande->getArtiste()->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $commande->getArtiste()->getUser()->getFullName(), 'vendeur_email' => $commande->getArtiste()->getUser()->getEmail(), 'vendeur_telephone' => $commande->getArtiste()->getUser()->getTelPortable() . ' / ' . $commande->getArtiste()->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'acteur' => ($mvtNegociation->getActeurNegociation()->getId() == 1 ? "l'acheteur" : ($mvtNegociation->getActeurNegociation()->getId() == 2 ? "le vendeur" : "vous")), 'details_complement' => nl2br($mvtNegociation->getDetails()), 'montant_complement' => formatMontant($mvtNegociation->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_complement_commande_custom_ai.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_validation_commande_custom_ai.txt');
									$mailer->AltBody = $mailer->compileText();
									$mailer->send();
									//debug('Mail '.$mailer->Subject.' envoyé à '.DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer = null;
								}
								http_response_code(200);
								print(json_encode(['complementCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['complementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['complementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}
					}
				}
				break;
			}
		case 'transmitCommandeCustom':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$newUser = true;
					if ($idCommande>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						//Ensuite j'update le statut de la commande
						$commande->setTransmiseVendeur(true);
						if (!DAOCommandeCustomAdmin::updateStatut($commande)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification commande';
							http_response_code(500);
							print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour le vendeur
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation de tranmission de sa commande
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
									$mailer->Subject = $mailer->getSenderApp() . " : Transmission de votre commande " . $commande->getNumero() . " à l'artiste";
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_transmission_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_transmission_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail au vendeur
									 */
									$languageVendeur = is_null($vendeur->getUser()->getLocaleTrad()) ? 'fr' : $vendeur->getUser()->getLocaleTrad()->getLibelle();
									$lang = substr($languageVendeur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $vendeur->getFullName());
									}else{
										$mailer->addAddress($vendeur->getUser()->getEmail(), $vendeur->getUser()->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Une nouvelle commande sur mesure vient de vous être adressée";
									$contenuCommande = '';
									$mouvements = array_reverse($commande->getMouvements());
									/**
									 * @var MouvementNegociationCommande $mouvement
									 */
									foreach ($mouvements as $idx => $mouvement){
										$contenuCommande .= '<p>';
										$contenuCommande .= '<span>Date : </span><span>' . $mouvement->getDateMouvement()->format('d/m/Y H:i:s') . '</span><br />';
										$contenuCommande .= '<span>Détails : </span><span>' . $mouvement->getDetails() . '</span><br />';
										$contenuCommande .= '<span>Montant proposé : </span><span>' . formatMontant($mouvement->getMontant()) . '</span><br />';
										if ($idx<count($mouvements)){
											$contenuCommande .= '<hr>';
										}
										$contenuCommande .= '</p>';
									}
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $acheteur->getCivilite()->getLibelle(), 'acheteur_nom' => $acheteur->getFullName(), 'acheteur_date_naissance' => $acheteur->getDateNaissance()->format('d/m/Y'), 'numero_commande' => $commande->getNumero(), 'date_commande' => $commande->getDateCommande()->format('d/m/Y'), 'contenu_commande' => nl2br($contenuCommande), 'total_commande' => formatMontant($commande->getLignes()[0]->getPrixDefinitif()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_transmission_commande_custom_vendeur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_transmission_commande_custom_vendeur_' . $lang . '.txt';
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
								print(json_encode(['transmitedCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'accepteCommandeCustomVendeur':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					if ($idCommande>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						//Ensuite j'update le statut de la commande
						$commande->setAccepteVendeur(true);
						if ($commande->isAccepteVendeur() and $commande->isAccepteAcheteur()){
							$commande->setStatutCommande(DAOStatutCommandeCustom::getById(2));
						}
						if (!DAOCommandeCustom::updateStatut($commande)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification commande';
							http_response_code(500);
							print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							$mvtNegociation = new MouvementNegociationCommande($commande->getLignes()[0]->getPrixDefinitif(), 'Commande acceptée par l\'artiste', DAOTypeActeurNegociation::getById(2), date_create('now'));
							$mvtNegociation->setIdCommandeCustom($commande->getId());
							if (!DAOMouvementNegociationCommande::insert($mvtNegociation)){
								$message = 'Erreur création mouvement commande';
								$transactionOk = false;
							}else{
								$commande->addMouvement($mvtNegociation);
							}
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation d'acceptation de sa commande
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
									$mailer->Subject = $mailer->getSenderApp() . " : Acceptation de votre commande " . $commande->getNumero() . " par l'artiste";
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_accepte_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_accepte_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail à AI de confirmation de l'acceptation par le vendeur
									 */
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Acceptation de la commande " . $commande->getNumero() . " par l'artiste";
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $acheteur->getCivilite()->getLibelle(), 'acheteur_nom' => $acheteur->getFullName(), 'acheteur_email' => $acheteur->getEmail(), 'acheteur_telephone' => $acheteur->getTelPortable(), 'acheteur_date_naissance' => $acheteur->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $vendeur->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $vendeur->getUser()->getFullName(), 'vendeur_email' => $vendeur->getUser()->getEmail(), 'vendeur_telephone' => $vendeur->getUser()->getTelPortable() . ' / ' . $vendeur->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'montant_commande' => formatMontant($commande->getLignes()[0]->getPrixDefinitif()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_accepte_vendeur_commande_custom_ai.phtml';
									$templateTXT = 'core/views/template/mails/mail_accepte_vendeur_commande_custom_ai.txt';
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
								print(json_encode(['acceptedCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'accepteCommandeCustomAcheteur':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$newUser = true;
					if ($idCommande>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						//Ensuite j'update le statut de la commande
						$commande->setAccepteAcheteur(true);
						if ($commande->isAccepteVendeur() and $commande->isAccepteAcheteur()){
							$commande->setStatutCommande(DAOStatutCommandeCustom::getById(2));
						}
						if (!DAOCommandeCustom::updateStatut($commande)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification commande';
							http_response_code(500);
							print(json_encode(['transmitedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							$mvtNegociation = new MouvementNegociationCommande($commande->getLignes()[0]->getPrixDefinitif(), 'Commande acceptée par l\'acheteur', DAOTypeActeurNegociation::getById(1), date_create('now'));
							$mvtNegociation->setIdCommandeCustom($commande->getId());
							if (!DAOMouvementNegociationCommande::insert($mvtNegociation)){
								$message = 'Erreur création mouvement commande';
								$transactionOk = false;
							}else{
								$commande->addMouvement($mvtNegociation);
							}
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour le vendeur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail au vendeur
									 */
									$languageVendeur = is_null($vendeur->getUser()->getLocaleTrad()) ? 'fr' : $vendeur->getUser()->getLocaleTrad()->getLibelle();
									$lang = substr($languageVendeur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $vendeur->getUser()->getFullName());
									}else{
										$mailer->addAddress($vendeur->getUser()->getEmail(), $vendeur->getUser()->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Acceptation de votre proposition pour la commande " . $commande->getNumero() . " par l'acheteur";
									$contenuCommande = '';
									$mouvements = array_reverse($commande->getMouvements());
									/**
									 * @var MouvementNegociationCommande $mouvement
									 */
									foreach ($mouvements as $idx => $mouvement){
										$contenuCommande .= '<p>';
										$contenuCommande .= '<span>Date : </span><span>' . $mouvement->getDateMouvement()->format('d/m/Y H:i:s') . '</span><br />';
										$contenuCommande .= '<span>Détails : </span><span>' . $mouvement->getDetails() . '</span><br />';
										$contenuCommande .= '<span>Montant proposé : </span><span>' . formatMontant($mouvement->getMontant()) . '</span><br />';
										if ($idx<count($mouvements)){
											$contenuCommande .= '<hr>';
										}
										$contenuCommande .= '</p>';
									}
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $acheteur->getCivilite()->getLibelle(), 'acheteur_nom' => $acheteur->getFullName(), 'acheteur_date_naissance' => $acheteur->getDateNaissance()->format('d/m/Y'), 'numero_commande' => $commande->getNumero(), 'date_commande' => $commande->getDateCommande()->format('d/m/Y'), 'contenu_commande' => nl2br($contenuCommande), 'total_commande' => formatMontant($commande->getLignes()[0]->getPrixDefinitif()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_accepte_commande_custom_vendeur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_accepte_commande_custom_vendeur_' . $lang . '.txt';
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
									 * Envoi du mail à AI de confirmation de l'acceptation par l'acheteur
									 */
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Acceptation de la commande " . $commande->getNumero() . " par l'acheteur";
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $acheteur->getCivilite()->getLibelle(), 'acheteur_nom' => $acheteur->getFullName(), 'acheteur_email' => $acheteur->getEmail(), 'acheteur_telephone' => $acheteur->getTelPortable(), 'acheteur_date_naissance' => $acheteur->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $vendeur->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $vendeur->getUser()->getFullName(), 'vendeur_email' => $vendeur->getUser()->getEmail(), 'vendeur_telephone' => $vendeur->getUser()->getTelPortable() . ' / ' . $vendeur->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'montant_commande' => formatMontant($commande->getLignes()[0]->getPrixDefinitif()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_accepte_acheteur_commande_custom_ai.phtml';
									$templateTXT = 'core/views/template/mails/mail_accepte_acheteur_commande_custom_ai.txt';
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
								print(json_encode(['acceptedCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['acceptedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'repondreCommandeCustom':
			{
				/**
				 * Action permettant au vendeur de répondre à un commande sur mesure
				 */
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['reponseCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$budget = floatval($_POST['budget'] ?? 0);
					$details = htmlentities($_POST['details'] ?? '');
					$newUser = true;
					if ($idCommande>0 and $budget>0 and $details != ''){
						$transactionOk = true;
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$userCommande = $commande->getUser();
						//Ensuite je rajoute le mouvement
						$mvtNegociation = new MouvementNegociationCommande($budget, $details, DAOTypeActeurNegociation::getById(2), date_create('now'));
						$mvtNegociation->setIdCommandeCustom($commande->getId());
						if (!DAOMouvementNegociationCommande::insert($mvtNegociation)){
							$message = 'Erreur création mouvement commande';
							$transactionOk = false;
						}else{
							$commande->addMouvement($mvtNegociation);
							//Je mets à jour le prix de la ligne commande
							$ligneCommande = $commande->getLignes()[0];
							$ligneCommande->setPrixDefinitif($mvtNegociation->getMontant());
							if (!DAOCommandeCustom::updateLigne($ligneCommande)){
								$message = 'Erreur modification ligne commande';
								$transactionOk = false;
							}
						}
						if ($transactionOk){
							//						$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//						debug($commandeCustom);
							//
							BDD::commitTransaction();
							//												BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation de la réponse et de la création de son compte
									 */
									$languageAcheteur = is_null($userCommande->getLocaleTrad()) ? 'fr' : $userCommande->getLocaleTrad()->getLibelle();
									$lang = substr($languageAcheteur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $userCommande->getFullName());
									}else{
										$mailer->addAddress($userCommande->getEmail(), $userCommande->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Réponse à votre commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $userCommande->getFullName(), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_reponse_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_reponse_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail a AI de la transaction
									 */
									//Adresses
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Définit le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Ajout d'une réponse pour la commande " . $commande->getId();
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $userCommande->getCivilite()->getLibelle(), 'acheteur_nom' => $userCommande->getFullName(), 'acheteur_email' => $userCommande->getEmail(), 'acheteur_telephone' => $userCommande->getTelPortable(), 'acheteur_date_naissance' => $userCommande->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $commande->getArtiste()->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $commande->getArtiste()->getUser()->getFullName(), 'vendeur_email' => $commande->getArtiste()->getUser()->getEmail(), 'vendeur_telephone' => $commande->getArtiste()->getUser()->getTelPortable() . ' / ' . $commande->getArtiste()->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'acteur' => ($mvtNegociation->getActeurNegociation()->getId() == 1 ? "l'acheteur" : ($mvtNegociation->getActeurNegociation()->getId() == 2 ? "le vendeur" : "vous")), 'details_complement' => nl2br($mvtNegociation->getDetails()), 'montant_complement' => formatMontant($mvtNegociation->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_complement_commande_custom_ai.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_validation_commande_custom_ai.txt');
									$mailer->AltBody = $mailer->compileText();
									$mailer->send();
									//debug('Mail '.$mailer->Subject.' envoyé à '.DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer = null;
								}
								http_response_code(200);
								print(json_encode(['reponseCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['reponseCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['reponseCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}
					}
				}
				break;
			}
		case 'confirmExpeditionCommandeCustom':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['expeditionConfirmedCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					if ($idCommande>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						//Ensuite j'update le statut de la commande
						$commande->setStatutCommande(DAOStatutCommandeCustom::getById(6)); // Expédiée
						if (!DAOCommandeCustom::updateStatut($commande)){
							BDD::rollbackTransaction();
							$message = 'Erreur modification commande';
							http_response_code(500);
							print(json_encode(['expeditionConfirmedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour AI ?
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation d'acceptation de sa commande
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
									$mailer->Subject = $mailer->getSenderApp() . " : Expédition de votre commande " . $commande->getNumero() . " par l'artiste";
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_expedition_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_expedition_commande_custom_acheteur_' . $lang . '.txt';
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
								print(json_encode(['expeditionConfirmedCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['expeditionConfirmedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['expeditionConfirmedCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'effectuerPaiementCommandeCustom':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idCommande = intval($_POST['idCommande'] ?? 0);
					$montant = floatval($_POST['montant'] ?? 0);
					if ($idCommande>0 && $montant>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer la commande actuelle
						$commande = DAOCommandeCustom::getById($idCommande);
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						//J'ajoute le paiement correspondant
						$montantRegle = 0;
						foreach ($commande->getPaiements() as $paiement){
							$montantRegle += $paiement->getMontant();
						}
						$montantRestant = $commande->getLignes()[0]->getPrixDefinitif() - $montantRegle;
						$paiement = new PaiementCommandeCustom($montant, 'Paiement', DAOMoyenPaiement::getById(3), DAOTypePaiement::getById(($montant == $montantRestant) ? 2 : 1), DAOStatutPaiement::getById(1));
						$paiement->setDatePaiement(date_create('now'));
						$paiement->setIdCommandeCustom($idCommande);
						if (!DAOPaiementCommandeCustom::insert($paiement)){
							BDD::rollbackTransaction();
							$message = 'Erreur enregistrement paiement';
							http_response_code(500);
							print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							//Ensuite j'update le statut de la commande
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour le vendeur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation du paiement
									 */
									$languageAcheteur = is_null($acheteur->getLocaleTrad()) ? 'fr' : $acheteur->getLocaleTrad()->getLibelle();
									$lang = substr($languageAcheteur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress($acheteur->getEmail(), $acheteur->getFullName());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Paiement enregistré pour votre commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'date_paiement' => $paiement->getDatePaiement()->format('d/m/Y H:i:s'), 'montant_paiement' => formatMontant($paiement->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_paiement_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_paiement_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail au vendeur de confirmation du paiement
									 */
									$languageVendeur = is_null($vendeur->getUser()->getLocaleTrad()) ? 'fr' : $vendeur->getUser()->getLocaleTrad()->getLibelle();
									$lang = substr($languageVendeur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $vendeur->getUser()->getFullName());
									}else{
										$mailer->addAddress($vendeur->getUser()->getEmail(), $vendeur->getUser()->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Paiement enregistré pour la commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'date_paiement' => $paiement->getDatePaiement()->format('d/m/Y H:i:s'), 'montant_paiement' => formatMontant($paiement->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_paiement_commande_custom_vendeur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_paiement_commande_custom_vendeur_' . $lang . '.txt';
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
									 * Envoi du mail à AI
									 */
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Paiement enregistré pour la commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'acheteur_civilite' => $acheteur->getCivilite()->getLibelle(), 'acheteur_nom' => $acheteur->getFullName(), 'acheteur_email' => $acheteur->getEmail(), 'acheteur_telephone' => $acheteur->getTelPortable(), 'acheteur_date_naissance' => $acheteur->getDateNaissance()->format('d/m/Y'), 'vendeur_civilite' => $vendeur->getUser()->getCivilite()->getLibelle(), 'vendeur_nom' => $vendeur->getUser()->getFullName(), 'vendeur_email' => $vendeur->getUser()->getEmail(), 'vendeur_telephone' => $vendeur->getUser()->getTelPortable() . ' / ' . $vendeur->getUser()->getTelFixe(), 'numero_commande' => $commande->getNumero(), 'date_paiement' => $paiement->getDatePaiement()->format('d/m/Y H:i:s'), 'montant_paiement' => formatMontant($paiement->getMontant()), 'moyen_paiement' => $paiement->getMoyenPaiement()->getLibelle(), 'type_paiement' => $paiement->getTypePaiement()->getLibelle(), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_gestionnaire' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_paiement_commande_custom_ai.phtml';
									$templateTXT = 'core/views/template/mails/mail_paiement_commande_custom_ai.txt';
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
								print(json_encode(['paiementCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
				}
				break;
			}
		case 'confirmPaymentCommandeCustom':
			{
				if (empty($_POST)){
					http_response_code(500);
					print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => 'POST Vide ou artiste non loggé']));
				}else{
					$idPaiement = intval($_POST['idPaiement'] ?? 0);
					if ($idPaiement>0){
						$message = 'OK';
						BDD::openTransaction();
						//Je commence par récupérer le paiement
						$paiement = DAOPaiementCommandeCustom::getById($idPaiement);
						$commande = DAOCommandeCustom::getById($paiement->getIdCommandeCustom());
						$acheteur = $commande->getUser();
						$vendeur = $commande->getArtiste();
						$paiement->setStatutPaiement(DAOStatutPaiement::getById(2));
						if (!DAOPaiementCommandeCustom::update($paiement)){
							BDD::rollbackTransaction();
							$message = 'Erreur enregistrement paiement';
							http_response_code(500);
							print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
						}else{
							//$commandeCustom = DAOCommandeCustom::getById($commande->getId());
							//debug($commandeCustom);
							BDD::commitTransaction();
							//						BDD::rollbackTransaction();
							/**
							 * Envoi des mails :
							 * un pour l'acheteur
							 * un pour le vendeur
							 * un pour AI
							 */
							$mailer = null;
							try{
								$mailer = new Mailer();
								if ($mailer->isDefined()){
									/**
									 * Envoi du mail à l'acheteur de confirmation du paiement
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
									$mailer->Subject = $mailer->getSenderApp() . " : Paiement confirmé pour votre commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'date_paiement' => $paiement->getDatePaiement()->format('d/m/Y H:i:s'), 'montant_paiement' => formatMontant($paiement->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_confirm_paiement_commande_custom_acheteur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_confirm_paiement_commande_custom_acheteur_' . $lang . '.txt';
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
									 * Envoi du mail au vendeur de confirmation du paiement
									 */
									$languageVendeur = is_null($vendeur->getUser()->getLocaleTrad()) ? 'fr' : $vendeur->getUser()->getLocaleTrad()->getLibelle();
									$lang = substr($languageVendeur, 0, 2);
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
									if (DEV_MODE){
										$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $vendeur->getUser()->getFullName());
									}else{
										$mailer->addAddress($vendeur->getUser()->getEmail(), $vendeur->getUser()->getFullName());
									}
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = $mailer->getSenderApp() . " : Paiement confirmé pour la commande " . $commande->getNumero();
									$variables = ['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'nom_client' => $acheteur->getFullName(), 'numero_commande' => $commande->getNumero(), 'date_paiement' => $paiement->getDatePaiement()->format('d/m/Y H:i:s'), 'montant_paiement' => formatMontant($paiement->getMontant()), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp(), 'lien_espace_vendeur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
									$templateHTML = 'core/views/template/mails/mail_confirm_paiement_commande_custom_vendeur_' . $lang . '.phtml';
									$templateTXT = 'core/views/template/mails/mail_confirm_paiement_commande_custom_vendeur_' . $lang . '.txt';
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
								print(json_encode(['paiementCustomCommande' => true, 'commandeCustomId' => $commande->getId(), 'result' => 'success']));
							}catch (Exception $e){
								if (!is_null($mailer)){
									$message = $mailer->ErrorInfo;
								}else{
									$message = "Erreur mailer";
								}
								http_response_code(500);
								print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
							}
						}
					}else{
						$message = 'Erreur de paramètre';
						http_response_code(500);
						print(json_encode(['paiementCustomCommande' => false, 'result' => 'error', 'error' => $message]));
					}
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
		default:
			{
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}
