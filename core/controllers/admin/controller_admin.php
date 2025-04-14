<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données
	 */

	header("Cache-Control: no-cache, must-revalidate");
	$includedCssScripts = [['href' => HTML_PUBLIC_STYLES_DIR . 'final_views_gestion.css']];
	if ($page == 'compte-connect'){
		$includedCssScripts[] = ['href' => 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . INTL_TEL_INPUT_VERSION . '/build/css/intlTelInput.css'];
	}
	require_once 'core/views/template/header.phtml';
	/**
	 * liens spécifiques :
	 * ?section=admin&page=maintenance&action=regenerer-miniatures => régénération de toutes les miniatures
	 * ?section=admin&page=maintenance&action=regenerer-miniature&id=xxx => régénération des miniatures pour 1 oeuvre
	 * ?section=admin&page=maintenance&action=resample-image => Test resample image (cas Réjane)
	 * ?section=admin&page=maintenance&action=infos-gd => informations concernant les librairies GD et images
	 * ?section=admin&page=maintenance&action=get-php-info => informations concernant les librairies GD et images
	 * ?section=admin&page=maintenance&action=test-email-contact => test de l'envoi d'un mail de contact
	 * ?section=admin&page=comptes-stripe-connect&action={view|create|update|delete|transfer} => tests pour Stripe
	 * ?section=admin&page=maintenance&action=rencrypt-ibans => réencryptage des IBAN
	 * ?section=admin&page=maintenance&action=test-emails => tester les mails
	 */
	switch ($page){
		case 'traductions':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-traductions.js"];
				$lstTraductions = DAOTexte::getAll();
				require_once 'core/views/admin/view_gestion_traductions.phtml';
				break;
			}
		case 'textes':
			{
				if (!isset($userLogged) or !$userLogged->isAdmin()){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-textes.js"];
					$lstTextes = DAOTexte::getAll();
					require_once 'core/views/admin/view_gestion_textes.phtml';
					break;
				}
			}
		case 'parametres':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-parametres.js"];
				$paramShowInfos = DAOParametres::getByLibelle('SHOW_DEBUG');
				if (!is_null($paramShowInfos) and intval($paramShowInfos->getValeur()) == 1){
					debug('DEV_MODE : ' . DEV_MODE);
					debug(DHLToolkit::getActiveUrl());
					debug(StripeToolkit::getActivePublicKey());
					debug(StripeToolkit::getActiveSecretKey());
				}
				$lstParametres = DAOParametres::getAll();
				require_once 'core/views/admin/view_gestion_parametres.phtml';
				break;
			}
		case 'maintenance':
			{
				switch ($action){
					case 'rencrypt-ibans':
						{
							/**
							 * @var InfosBanque $infosBanque
							 */
							BDD::openTransaction();
							$infosBanques = DAOInfosBanque::getAll();
							foreach ($infosBanques as $infosBanque){
								if ($infosBanque->getIban() != ''){
									DAOInfosBanque::update($infosBanque);
								}
							}
							debug('Traitement terminé');
							BDD::commitTransaction();
							break;
						}
					case 'regenerer-miniature':
						{
							/**
							 * @var Oeuvre       $oeuvre
							 * @var VisuelOeuvre $visuel
							 */
							$nbOeuvres = 0;
							$nbVisuels = 0;
							$idOeuvre = intval($_GET['id'] ?? 0); //2031
							if ($idOeuvre>0){
								$uneOeuvre = DAOOeuvreAdmin::getById($idOeuvre);
								debug($uneOeuvre->getChemin());
								if (file_exists($uneOeuvre->getChemin()) and is_readable($uneOeuvre->getChemin())){
									$newMiniature = @AIImage::staticCreateMiniature($uneOeuvre->getChemin(), $uneOeuvre->getMiniature());
									$uneOeuvre->setMiniature($newMiniature);
									debug($uneOeuvre->getMiniature());
									DAOOeuvreAdmin::updateMiniature($uneOeuvre);
									foreach ($uneOeuvre->getVisuels() as $unVisuel){
										if (file_exists($unVisuel->getChemin()) and is_readable($unVisuel->getChemin())){
											$newMiniature = @AIImage::staticCreateMiniature($unVisuel->getChemin(), $unVisuel->getMiniature());
											$unVisuel->setMiniature($newMiniature);
											DAOVisuelOeuvreAdmin::updateMiniature($unVisuel);
											debug($unVisuel->getMiniature());
											$nbVisuels++;
										}
									}
									$nbOeuvres++;
								}
							}
							debug("$nbOeuvres oeuvres traitées, $nbVisuels visuels traités");
							break;
						}
					case 'regenerer-miniatures':
						{
							/**
							 * @var Oeuvre       $oeuvre
							 * @var VisuelOeuvre $unVisuel
							 */
							$lesOeuvres = DAOOeuvreAdmin::getAllForMaintenance();
							$nbOeuvres = count($lesOeuvres);
							$numOeuvre = 0;
							$nbVisuelsTotal = 0;
							foreach ($lesOeuvres as $oeuvre){
								$nbVisuels = 0;
								if (file_exists($oeuvre->getChemin()) and is_readable($oeuvre->getChemin())){
									$numOeuvre++;
									debug($numOeuvre . ' / ' . $nbOeuvres . ' : ');
									$pathOeuvre = @AIImage::staticCreateMiniature($oeuvre->getChemin(), $oeuvre->getMiniature());
									debug('Miniature : ' . $pathOeuvre);
									$oeuvre->setMiniature($pathOeuvre);
									DAOOeuvreAdmin::updateMiniature($oeuvre);
									foreach ($oeuvre->getVisuels() as $unVisuel){
										if (file_exists($unVisuel->getChemin()) and is_readable($unVisuel->getChemin())){
											$newMiniature = @AIImage::staticCreateMiniature($unVisuel->getChemin(), $unVisuel->getMiniature());
											debug('Miniature Visuel ' . ($nbVisuels + 1) . ': ' . $newMiniature);
											$unVisuel->setMiniature($newMiniature);
											DAOVisuelOeuvreAdmin::updateMiniature($unVisuel);
											$nbVisuels++;
										}
									}
									@flush();
								}
								$nbVisuelsTotal += $nbVisuels;
								//						if ($numOeuvre > 3) die();
							}
							debug("$numOeuvre oeuvres traitées, $nbVisuelsTotal visuels traités");
							break;
						}
					case 'infos-gd':
						{
							debug(gd_info());
							debug('Format PNG : ' . (imagetypes() & IMG_PNG));
							debug('Format AVIF : ' . (imagetypes() & IMG_AVIF));
							debug('Format WebP : ' . (imagetypes() & IMG_WEBP));
							debug('Format JPEG : ' . (imagetypes() & IMG_JPEG));
							break;
						}
					case 'resample-image':
						{
							//					$oeuvre = DAOOeuvre::getById(1678);
							//					$oeuvre = DAOOeuvre::getById(1551);
							$oeuvre = DAOOeuvre::getById(1397);
							$imgSrc = $oeuvre->getChemin();
							$imgDest = $oeuvre->getMiniature();
							@AIImage::staticCreateMiniature($imgSrc, $imgDest);
							break;
						}
					case 'get-php-info':
						{
							phpinfo();
							break;
						}
					case 'test-email-contact':
						{
							$categorie = 'test serveur de messagerie';
							$objet = 'Test de messagerie';
							$message = trim(htmlentities('Ceci est un message de test '));
							$mailer = new Mailer();
							try{
								if ($mailer->isDefined()){
									$content = ['categorie' => $categorie, 'objet' => $objet, 'message' => $message];
									//Destinataires
									$mailer->setFrom(DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), $mailer->getSenderApp());
									$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur());
									$mailer->addCC(DAOParametres::getByLibelle('destemail-vente')->getValeur());
									$mailer->addBCC(DAOParametres::getByLibelle('destemail-support')->getValeur());
									$mailer->addReplyTo(DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), $mailer->getSenderApp());
									//Content
									$mailer->isHTML(); // Défini le mail au format HTML
									$mailer->Subject = "Vous avez reçu un message depuis " . $mailer->getSenderApp();
									$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'sender_name' => $mailer->getSenderApp(), 'sender_email' => DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), 'sender_object' => $content['objet'], 'email_categorie' => $categorie, 'sender_message' => nl2br($content['message']), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp()]);
									$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
									$mailer->setTemplateHtml('core/views/template/mails/mail_contact.phtml');
									$mailer->Body = $mailer->compileHTML();
									$mailer->setTemplateText('core/views/template/mails/mail_contact.txt');
									$mailer->AltBody = $mailer->compileText();
									if ($mailer->send()){
										$message = 'Votre message nous a bien été transmis, <br />';
										$message .= 'Bien cordialement, <br />';
										$message .= "L'équipe Art Interactivities";
									}else{
										$message = $mailer->ErrorInfo;
									}
								}
							}catch (Exception $e){
								$message = $mailer->ErrorInfo;
							}
							debug($message);
						}
					case 'test-emails':
						{
							$baseDir = PHP_EMAIL_HTML_TPL;
							$templates = array_diff(scandir($baseDir, SCANDIR_SORT_ASCENDING), array('..', '.'));
							$lstFiles = array();
							foreach ($templates as $template){
								$file = $baseDir . $template;
								$fileName = basename($file, '.html');
								$lstFiles[] = $fileName;
							}
							if (!empty($_GET) and isset($_GET['nommail'])){
								$nomMail = $_GET['nommail'];
								$doNotSend = false;
								$tplPhtml = '';
								$tplTxt = '';
								$tplPhtml = $nomMail . '.phtml';
								$tplTxt = $nomMail . '.txt';
								if (!file_exists(PHP_EMAIL_TPL . $tplPhtml)){
									$message = 'Le template ' . $tplPhtml . ' n\'existe pas';
									$doNotSend = true;
								}
								if (!$doNotSend){
									$objet = 'Test de mail : ' . $nomMail;
									$message = trim(htmlentities('Ceci est un message de test '));
									$mailer = new Mailer();
									try{
										if ($mailer->isDefined()){
											//Destinataires
											$mailer->setFrom(DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), $mailer->getSenderApp());
											$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur());
											//Content
											$mailer->isHTML(); // Défini le mail au format HTML
											$mailer->Subject = $mailer->getSenderApp() . ' : ' . $objet;
											$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'sender_name' => $mailer->getSenderApp(), 'sender_email' => DAOParametres::getByLibelle('senderemail-pagecontact')->getValeur(), 'sender_message' => nl2br($message), 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp()]);
											$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
											$mailer->setTemplateHtml('core/views/template/mails/' . $tplPhtml);
											$mailer->Body = $mailer->compileHTML();
											$mailer->setTemplateText('core/views/template/mails/' . $tplTxt);
											$mailer->AltBody = $mailer->compileText();
											if ($mailer->send()){
												$message = 'Mail envoyé à ' . DAOParametres::getByLibelle('destemail-contact')->getValeur() . '<br />';
											}else{
												$message = $mailer->ErrorInfo;
											}
										}
									}catch (Exception $e){
										$message = $mailer->ErrorInfo;
									}
								}
							}
							require_once 'core/views/admin/view_test_emails.phtml';
						}
				}
				break;
			}
		case 'les-ventes':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-ventes.js"];
				$paramShowInfos = DAOParametres::getByLibelle('SHOW_DEBUG');
				if (!is_null($paramShowInfos) and intval($paramShowInfos->getValeur()) == 1){
					debug(DHLToolkit::getActiveUrl());
					debug(StripeToolkit::getActivePublicKey());
					debug(StripeToolkit::getActiveSecretKey());
				}
				$lstVentes = DAOCommandeAdmin::getAll();

				require_once 'core/views/admin/view_gestion_ventes.phtml';
				break;
			}
		case 'les-commandes-sur-mesure':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-custom-commande.js"];
				$lstCommandes = DAOCommandeCustomAdmin::getAll();
				require_once 'core/views/admin/view_gestion_custom_commandes.phtml';
				break;
			}
		case 'les-negociations-oeuvres':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-negociations-oeuvres.js"];
				// Récupération des négociations
				$lstStatutsNegociationOeuvre = DAOStatutNegociation::getAll();
				$lstNegociationsOeuvres = DAONegociationOeuvreAdmin::getAll();
				require_once 'core/views/admin/view_gestion_negociations_oeuvres.phtml';
				break;
			}
		case 'facture-achat':
			{
				if (!isset($userLogged) or !$userLogged->isAdmin()){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$factureAchat = DAOFactureAchat::getById($_GET['id']);
					if (DEV_MODE or is_null($factureAchat->getCheminFacturePDFAdmin()) or !file_exists($factureAchat->getCheminFacturePDFAdmin())){
						FactureAchatPDFAdmin::generatePDF($factureAchat);
					}
					$fullExternalFilePath = $factureAchat->getPublicFacturePDFAdmin();
					$fileName = pathinfo($fullExternalFilePath, PATHINFO_FILENAME);
					require_once 'core/views/admin/view_admin_facture_acheteur.phtml';
				}
				break;
			}
		case 'facture-vente':
			{
				if (!isset($userLogged) or !$userLogged->isAdmin()){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$factureVente = DAOFactureVente::getById($_GET['id']);
					if (DEV_MODE or is_null($factureVente->getCheminFacturePDFAdmin()) or !file_exists($factureVente->getCheminFacturePDFAdmin())){
						FactureVentePDFAdmin::generatePDF($factureVente);
					}
					$fullExternalFilePath = $factureVente->getPublicFacturePDFAdmin();
					$fileName = pathinfo($fullExternalFilePath, PATHINFO_FILENAME);
					require_once 'core/views/admin/view_admin_facture_vendeur.phtml';
				}
				break;
			}
		case 'comptes-stripe-connect':
			{
				$paramShowInfos = DAOParametres::getByLibelle('SHOW_DEBUG');
				if (!is_null($paramShowInfos) and intval($paramShowInfos->getValeur()) == 1){
					debug(DHLToolkit::getActiveUrl());
					debug(StripeToolkit::getActivePublicKey());
					debug(StripeToolkit::getActiveSecretKey());
				}
				$userArtiste = DAOUser::getById(269);
				$idStripeAccount = $userArtiste->getStripeAccountId();
				debug($userArtiste->getStripeAccountId());
				//			die();
				// Récupération des comptes connectés sur le compte Stripe
				$stripeClient = StripeToolkit::getStripeClient();
				if ($action == 'view'){
					try{
						if (!is_null($idStripeAccount)){
							$account = $stripeClient->accounts->retrieve($idStripeAccount);
							debug($account->requirements);
						}
					}catch (ApiErrorException $e){
						debug($e);
					}
				}elseif ($action == 'create'){
					if (is_null($idStripeAccount)){
						try{
							$iban = DEV_MODE ? 'FR1420041010050500013M02606' : $userArtiste->getInfosBanque()->getIban();
							$url = (DEV_MODE ? 'https://www.artinteractivities.com' : EXTERNAL_URL) . getUrl('artistes', 'profil', 'view', ['id' => DAOArtiste::getByIdUser($userArtiste->getId())->getId()]);
							$account = $stripeClient->accounts->create(['type' => 'express', /*'controller' => [
								'requirement_collection' => 'application',
								'fees' => ['payer' => 'application'],
								'losses' => ['payments' => 'application'],
								'stripe_dashboard' => ['type' => 'none'],
							],*/ 'capabilities' => ['card_payments' => ['requested' => true], 'transfers' => ['requested' => true],], 'country' => 'FR', 'email' => $userArtiste->getEmail(), 'default_currency' => 'EUR', 'business_type' => 'individual', 'external_account' => ['object' => 'bank_account', 'country' => 'FR', // Pays du compte bancaire
								'currency' => 'EUR', // Devise du compte
								'account_holder_name' => $userArtiste->getInfosBanque()->getTitulaire(), // Titulaire du compte
								'account_holder_type' => 'individual', // Type du titulaire
								//'routing_number' => '110000000', // Numéro d'acheminement
								'account_number' => $iban, // FR1420041010050500013M02606
							], 'individual' => ['address' => ['city' => $userArtiste->getAdresseFacturation()->getCpVille()->getVille(), 'country' => $userArtiste->getAdresseFacturation()->getPays()->getCodeIso2(), 'line1' => $userArtiste->getAdresseFacturation()->getAdresse1(), 'line2' => $userArtiste->getAdresseFacturation()->getAdresse2(), 'postal_code' => $userArtiste->getAdresseFacturation()->getCpVille()->getCodepostal()], 'email' => $userArtiste->getEmail(), 'first_name' => $userArtiste->getPrenom(), 'last_name' => $userArtiste->getNom(), //								'phone' => str_replace(' ', '', trim($userArtiste->getTelPortable())),
								'dob' => [ // Date de naissance
									'day' => intval($userArtiste->getDateNaissance()->format('d')), 'month' => intval($userArtiste->getDateNaissance()->format('m')), 'year' => intval($userArtiste->getDateNaissance()->format('Y'))]], 'business_profile' => ['url' => $url, 'product_description' => 'Oeuvres d\'art', 'mcc' => '5399', 'support_phone' => str_replace(' ', '', trim($userArtiste->getTelPortable()))],/*'tos_acceptance' => [
								'date' => date_create('now')->getTimestamp(),
								'ip' => $_SERVER['REMOTE_ADDR'],
								'user_agent' => $_SERVER['HTTP_USER_AGENT']
							]*/]);
							$userArtiste->setStripeAccountId($account->id);
							DAOUser::updateStripeAccountId($userArtiste);
							debug($account);
						}catch (ApiErrorException $e){
							debug($e);
						}
					}
				}elseif ($action == 'update'){
					try{
						if (!is_null($idStripeAccount)){
							$accountLinks = $stripeClient->accountLinks->create(['account' => $idStripeAccount, 'refresh_url' => EXTERNAL_URL, 'return_url' => EXTERNAL_URL, 'type' => 'account_onboarding']);
							debug($accountLinks);
						}
					}catch (ApiErrorException $e){
						debug($e);
					}
				}elseif ($action == 'delete'){
					try{
						if (!is_null($idStripeAccount)){
							$account = $stripeClient->accounts->retrieve($idStripeAccount);
							$accountDeleted = $account->delete();
							if ($accountDeleted->deleted){
								$userArtiste->setStripeAccountId(null);
								DAOUser::updateStripeAccountId($userArtiste);
							}
							debug($accountDeleted);
						}
					}catch (ApiErrorException $e){
						debug($e);
					}
				}elseif ($action == 'transfer'){
					try{
						if (!is_null($idStripeAccount)){
							$transfer = $stripeClient->transfers->create(['amount' => 20000, 'currency' => 'eur', 'destination' => $idStripeAccount]);
							debug($transfer);
						}
					}catch (ApiErrorException $e){
						debug($e);
					}
				}
				require_once 'core/views/admin/view_gestion_stripe_connect.phtml';
				break;
			}
		case 'compte-connect':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-compte-connect.js", 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . INTL_TEL_INPUT_VERSION . '/build/js/intlTelInput.min.js', HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-champs-telephone.js"];
				$lstPaysStared = DAOPays::getAll(includeStared: true, staredOnly: true);
				$lstPaysNotStared = DAOPays::getAll(includeStared: false, staredOnly: false, staredFirst: false);
				require_once 'core/views/admin/view_gestion_compte_connect.phtml';
				break;
			}
		case 'other':
			{
				break;
			}
		default:
			{
				require_once 'core/controllers/controller_error.php';
			}
	}
	require_once 'core/views/template/footer.phtml';
