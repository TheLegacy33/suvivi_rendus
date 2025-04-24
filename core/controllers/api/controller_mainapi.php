<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */

	use Random\RandomException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'checkUserExists':
			{
				$loginToTest = $_POST['identifiant'] ?? '';
				http_response_code(200);
				print(json_encode(['testedLogin' => $loginToTest, 'userExists' => DAOUser::userExists($loginToTest)]));
				break;
			}
		case 'checkActive':
			{
				$loginToTest = $_POST['identifiant'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedLogin' => $loginToTest, 'userActive' => DAOUser::checkActive($loginToTest)]));
				break;
			}
		case 'checkAuth':
			{
				$loginToTest = $_POST['identifiant'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedLogin' => $loginToTest, 'userAuthentified' => DAOUser::checkAuth($loginToTest, $passwordToCheck)]));
				break;
			}
		case 'checkUserPassword':
			{
				$idUser = $_POST['userId'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedUser' => $idUser, 'passChecked' => DAOUser::checkUserPassword($idUser, $passwordToCheck)]));
				break;
			}
		case 'askForPasswordRenew':
			{
				$mailToProcess = htmlentities($_POST['email'] ?? '');
				try{
					$token = DAOUser::getTokenForNewPassword($mailToProcess);
				}catch (RandomException|Exception $e){
					$token = null;
				}
				if (($token ?? '') != ''){
					$mailer = new Mailer();
					try{
						if ($mailer->isDefined()){
							$content = ['lost_password_link' => EXTERNAL_URL . getUrl('utilisateur', 'lost-password', 'define-new-password', ['token' => $token]), 'contact_link' => EXTERNAL_URL . getUrl('messagerie', 'contact-us'), 'connexion_link' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')];
							$dest = ['email' => $mailToProcess];
							//Destinataires
							$mailer->setFrom(DAOParametres::getByLibelle('senderemail-noreply')->getValeur(), $mailer->getSenderApp());
							$mailer->addAddress($dest['email']);
							//Content
							$mailer->isHTML(); // Défini le mail au format HTML
							$mailer->Subject = "Demande de reinitialisation du mot de passe (" . $mailer->getSenderApp() . ")";
							$mailer->setVariables(['url_image_logo' => 'cid:art_interactivities_logo', 'senderapp' => $mailer->getSenderApp(), 'lost_password_link' => $content['lost_password_link'], 'contact_link' => $content['contact_link'], 'connexion_link' => $content['connexion_link'], 'annee' => date('Y'), 'nom_entreprise' => $mailer->getSenderApp()]);
							$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
							$mailer->setTemplateHtml('core/views/template/mails/mail_lost_password_fr.phtml');
							$mailer->Body = $mailer->compileHTML();
							$mailer->setTemplateText('core/views/template/mails/mail_lost_password_fr.txt');
							$mailer->AltBody = $mailer->compileText();
							if ($mailer->send()){
								http_response_code(200);
								print(json_encode(['emailProcessed' => $mailToProcess, 'processedResult' => 'success', 'returnUrl' => getUrl('utilisateur', 'connexion-inscription')]));
							}else{
								http_response_code(500);
								$message = $mailer->ErrorInfo;
								print(json_encode(['emailProcessed' => $mailToProcess, 'processedResult' => 'error', 'erreur' => $message]));
							}
						}
					}catch (Exception $e){
						http_response_code(500);
						$message = $mailer->ErrorInfo;
						print(json_encode(['emailProcessed' => $mailToProcess, 'processedResult' => 'error', 'erreur' => $message]));
					}
				}
				break;
			}
		case 'renewUserPassword':
			{
				$token = htmlentities($_POST['token'] ?? '');
				$newPwd = $_POST['password'] ?? '';
				if ($token != '' && $newPwd != ''){
					if (BDD::openTransaction()){
						if (DAOUser::renewPassword($token, $newPwd)){
							http_response_code(200);
							print(json_encode(['userUpdated' => true, 'passwordUpdated' => true, 'processedResult' => 'success', 'returnUrl' => getUrl('utilisateur', 'connexion-inscription')]));
							BDD::commitTransaction();
						}else{
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'passwordUpdated' => false, 'processedResult' => 'error', 'erreur' => 'Erreur update']));
							BDD::rollbackTransaction();
						}
					}
				}
				break;
			}
		case 'createUserPassword':
			{
				$token = htmlentities($_POST['token'] ?? '');
				$newPwd = $_POST['password'] ?? '';
				if ($token != '' && $newPwd != ''){
					if (BDD::openTransaction()){
						if (DAOUser::createPassword($token, $newPwd)){
							http_response_code(200);
							print(json_encode(['userUpdated' => true, 'passwordUpdated' => true, 'processedResult' => 'success', 'returnUrl' => getUrl('utilisateur', 'connexion-inscription')]));
							BDD::commitTransaction();
						}else{
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'passwordUpdated' => false, 'processedResult' => 'error', 'erreur' => 'Erreur update']));
							BDD::rollbackTransaction();
						}
					}
				}
				break;
			}
		case 'isUserLogged':
			{
				http_response_code(200);
				print(json_encode(['userLogged' => Session::getActiveSession()->isUserLogged(), 'userId' => Session::getActiveSession()->getUserId()]));
				break;
			}
		case 'getOeuvresSerie':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['oeuvresSerieFetched' => false, 'result' => 'error']));
				}else{
					$idSerie = htmlentities($_GET['idserie']);
					$serie = DAOSerie::getById($idSerie);
					$serie->setOeuvres(DAOOeuvre::getByIdSeriePrivate($idSerie));
					http_response_code(200);
					print(json_encode(['oeuvresSerieFetched' => true, 'serieFetched' => $serie, 'result' => 'success']));
				}
				break;
			}
		case 'getVisuelsOeuvre':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['visuelOeuvreFetched' => false, 'result' => 'error']));
				}else{
					$idOeuvre = htmlentities($_GET['idoeuvre']);
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					//				debug($oeuvre);
					http_response_code(200);
					print(json_encode(['visuelOeuvreFetched' => true, 'oeuvre' => $oeuvre, 'result' => 'success']));
				}
				break;
			}
		case 'deleteoeuvre':
			{
				$idoeuvre = intval($_POST['idoeuvre'] ?? 0);
				if ($idoeuvre>0){
					$oeuvre = DAOOeuvre::getById($idoeuvre);
					// Je commence par supprimer les fichiers de l'oeuvre
					@unlink($oeuvre->getMiniature());
					@unlink($oeuvre->getChemin());
					foreach ($oeuvre->getVisuels() as $visuel){
						@unlink($visuel->getMiniature());
						@unlink($visuel->getChemin());
					}
					if (BDD::openTransaction()){
						if (DAOOeuvre::delete($oeuvre)){
							BDD::commitTransaction();
							http_response_code(200);
							print(json_encode(['oeuvreDeleted' => $oeuvre->getId(), 'result' => 'success']));
							//						BDD::rollbackTransaction();
						}else{
							BDD::rollbackTransaction();
							http_response_code(500);
							print(json_encode(['oeuvreDeleted' => false, 'result' => 'error']));
						}
					}else{
						BDD::rollbackTransaction();
						http_response_code(500);
						print(json_encode(['oeuvreDeleted' => false, 'result' => 'error']));
					}
				}else{
					http_response_code(500);
					print(json_encode(['oeuvreDeleted' => false, 'result' => 'error']));
				}
				break;
			}
		case 'addSerieArtiste':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['chActionForm'] ?? '');
					$idSerie = intval($_POST['chIdSerie'] ?? 0);
					$nomSerie = htmlentities($_POST['chNomSerie'] ?? '');
					$prixSerie = floatVal($_POST['chPrixSerie'] ?? 0);
					$description = htmlentities($_POST['chDescriptionSerie'] ?? '');
					$idArtiste = intval($_POST['chIdArtiste'] ?? 0);
					$idUserLogged = Session::getActiveSession()->getUserId();
					if (DAOArtiste::getById($idArtiste)->getUser()->getId() != $idUserLogged){
						http_response_code(403);
						print(json_encode(['serieCreated' => false, 'result' => 'error']));
					}else{
						if ($actionForm == 'add' && intval($idSerie) == 0){
							$newSerie = new Serie($nomSerie, $description, $idArtiste);
							$newSerie->setPrixVente($prixSerie);
							if (DAOSerie::insert($newSerie)){
								http_response_code(200);
								print(json_encode(['serieCreated' => true, 'result' => 'success']));
							}else{
								http_response_code(500);
								print(json_encode(['serieCreated' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['collectionCreated' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'editSerieArtiste':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['chActionForm'] ?? '');
					$idSerie = intval($_POST['chIdSerie'] ?? 0);
					$nomSerie = htmlentities($_POST['chNomSerie'] ?? '');
					$prixSerie = floatVal($_POST['chPrixSerie'] ?? 0);
					$description = htmlentities($_POST['chDescriptionSerie'] ?? '');
					$idArtiste = intval($_POST['chIdArtiste'] ?? 0);
					$idUserLogged = Session::getActiveSession()->getUserId();
					if (DAOArtiste::getById($idArtiste)->getUser()->getId() != $idUserLogged){
						http_response_code(403);
						print(json_encode(['serieUpdated' => false, 'result' => 'error']));
					}else{
						if ($actionForm == 'edit' && intval($idSerie) != 0){
							$serieToUpdate = DAOSerie::getById($idSerie);
							$updateToDo = false;
							if ($nomSerie != $serieToUpdate->getNom()){
								$serieToUpdate->setNom($nomSerie);
								$updateToDo = true;
							}
							if ($prixSerie != $serieToUpdate->getNom()){
								$serieToUpdate->setPrixVente($prixSerie);
								$updateToDo = true;
							}
							if ($description != $serieToUpdate->getDescription()){
								$serieToUpdate->setDescription($description);
								$updateToDo = true;
							}
							if ($updateToDo){
								if (DAOSerie::update($serieToUpdate)){
									http_response_code(200);
									print(json_encode(['serieUpdated' => true, 'result' => 'success']));
								}else{
									http_response_code(500);
									print(json_encode(['serieUpdated' => false, 'result' => 'error']));
								}
							}else{
								http_response_code(200);
								print(json_encode(['serieUpdated' => false, 'result' => 'success']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['serieUpdated' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'delSerieArtiste':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['action'] ?? '');
					$idSerie = intval($_POST['idserie'] ?? 0);
					$idArtiste = intval($_POST['idartiste'] ?? 0);
					$idUserLogged = Session::getActiveSession()->getUserId();
					if (DAOArtiste::getById($idArtiste)->getUser()->getId() != $idUserLogged){
						http_response_code(403);
						print(json_encode(['serieUpdated' => false, 'result' => 'error']));
					}else{
						if ($actionForm == 'del' && intval($idSerie) != 0){
							$serieASupprimer = DAOSerie::getById($idSerie);
							if ($serieASupprimer->getId() != 0){
								if (DAOSerie::delete($serieASupprimer)){
									http_response_code(200);
									print(json_encode(['serieDeleted' => true, 'result' => 'success']));
								}else{
									http_response_code(500);
									print(json_encode(['serieDeleted' => false, 'result' => 'error']));
								}
							}else{
								http_response_code(500);
								print(json_encode(['serieDeleted' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['serieDeleted' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'delOeuvreFromSerieArtiste':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['action'] ?? '');
					$idOeuvre = intval($_POST['idoeuvre'] ?? 0);
					$idSerie = intval($_POST['idserie'] ?? 0);
					$idArtiste = intval($_POST['idartiste'] ?? 0);
					$idUserLogged = Session::getActiveSession()->getUserId();
					if (DAOArtiste::getById($idArtiste)->getUser()->getId() != $idUserLogged){
						http_response_code(403);
						print(json_encode(['oeuvreRemoved' => false, 'result' => 'error']));
					}else{
						if ($actionForm == 'remove' && intval($idOeuvre) != 0 && intval($idSerie) != 0){
							$serieAModifier = DAOSerie::getById($idSerie);
							if ($serieAModifier->getId() != 0 && $serieAModifier->getIdArtiste() == $idArtiste){
								if (DAOSerie::removeOeuvre($serieAModifier, $idOeuvre)){
									http_response_code(200);
									print(json_encode(['oeuvreRemoved' => true, 'result' => 'success']));
								}else{
									http_response_code(500);
									print(json_encode(['oeuvreRemoved' => false, 'result' => 'error']));
								}
							}else{
								http_response_code(500);
								print(json_encode(['oeuvreRemoved' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['oeuvreRemoved' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'addOeuvreToSerieArtiste':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['action'] ?? '');
					$idOeuvre = intval($_POST['idoeuvre'] ?? 0);
					$idSerie = intval($_POST['idserie'] ?? 0);
					$idArtiste = intval($_POST['idartiste'] ?? 0);
					$idUserLogged = Session::getActiveSession()->getUserId();
					if (DAOArtiste::getById($idArtiste)->getUser()->getId() != $idUserLogged){
						http_response_code(403);
						print(json_encode(['oeuvreAdded' => false, 'result' => 'error']));
					}else{
						if ($actionForm == 'add' && $idOeuvre != 0 && $idSerie != 0 && $idArtiste != 0){
							$serieAModifier = DAOSerie::getById($idSerie);
							if ($serieAModifier->getId() != 0 && $serieAModifier->getIdArtiste() == $idArtiste){
								if (DAOSerie::addOeuvre($serieAModifier, $idOeuvre)){
									http_response_code(200);
									print(json_encode(['oeuvreAdded' => true, 'result' => 'success']));
								}else{
									http_response_code(500);
									print(json_encode(['oeuvreAdded' => false, 'result' => 'error']));
								}
							}else{
								http_response_code(500);
								print(json_encode(['oeuvreAdded' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['oeuvreAdded' => false, 'result' => 'error']));
						}
					}
				}
				break;
			}
		case 'getOeuvresPourSerie':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['oeuvresFetched' => false, 'result' => 'error']));
				}else{
					$idArtiste = intval($_GET['idartiste'] ?? 0);
					$oeuvres = DAOOeuvre::getByArtisteIdPrivate($idArtiste);
					$statutOeuvreShop = DAOStatutOeuvreShop::getByLibelle('A vendre');
					$lstOeuvres = array_filter($oeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvreShop){
						try{
							return is_null($oeuvreFiltered->getSerie()) && $oeuvreFiltered->getStatutOeuvreShop()->getId() == $statutOeuvreShop->getId();
						}catch (Exception $ex){
							return false;
						}
					}, ARRAY_FILTER_USE_BOTH);
					http_response_code(200);
					print(json_encode(['oeuvresFetched' => true, 'nboeuvres' => count($lstOeuvres), 'oeuvres' => $lstOeuvres, 'result' => 'success', 'userCurrency' => json_decode($_COOKIE['userCurrency']), 'userLocale' => json_decode($_COOKIE['userLocale'])]));
				}
				break;
			}
		case 'getArtisteTags':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['artisteFetched' => false, 'result' => 'error']));
				}else{
					$idArtiste = intval($_GET['idartiste'] ?? 0);
					$artiste = DAOArtiste::getById($idArtiste);
					$tags = $artiste->getMotsClefs();
					http_response_code(200);
					print(json_encode(['artisteFetched' => true, 'nbtags' => count($tags), 'tags' => $tags, 'result' => 'success']));
				}
				break;
			}
		case 'getArtisteLocalisation':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['artisteFetched' => false, 'result' => 'error']));
				}else{
					$idArtiste = intval($_GET['idartiste'] ?? 0);
					$artiste = DAOArtiste::getById($idArtiste);
					$regionArtiste = '';
					if (count($artiste->getUser()->getAdresses())>0 and !is_null($artiste->getUser()->getAdresses()[0]->getDepartement())){
						$regionArtiste = $artiste->getUser()->getAdresses()[0]->getDepartement()->getNom();
					}
					$paysArtiste = $artiste->getNationalite()->getNomFr();
					http_response_code(200);
					print(json_encode(['artisteFetched' => true, 'region' => $regionArtiste, 'pays' => $paysArtiste, 'result' => 'success']));
				}
				break;
			}
		case 'getArtisteInfosMiniature':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['artisteFetched' => false, 'result' => 'error']));
				}else{
					$idArtiste = intval($_GET['idartiste'] ?? 0);
					$artiste = DAOArtiste::getById($idArtiste);
					$tags = $artiste->getMotsClefs();
					$regionArtiste = '';
					if (count($artiste->getUser()->getAdresses())>0 and !is_null($artiste->getUser()->getAdresses()[0]->getDepartement())){
						$regionArtiste = $artiste->getUser()->getAdresses()[0]->getDepartement()->getNom();
					}
					$paysArtiste = $artiste->getNationalite()->getNomFr();
					http_response_code(200);
					print(json_encode(['artisteFetched' => true, 'nbtags' => count($tags), 'tags' => $tags, 'region' => $regionArtiste, 'pays' => $paysArtiste, 'result' => 'success']));
				}
				break;
			}
		case 'getNewOeuvreTitre':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['artisteFetched' => false, 'result' => 'error']));
				}else{
					$idArtiste = intval($_GET['idartiste'] ?? 0);
					$newTitreOeuvre = DAOOeuvre::geneNewTitreOeuvre($idArtiste);
					http_response_code(200);
					print(json_encode(['artisteFetched' => true, 'newtitre' => $newTitreOeuvre, 'result' => 'success']));
				}
				break;
			}
		case 'getoeuvresbyartist':
			{
				$idartiste = intval($_GET['idartiste'] ?? 0);
				$lesOeuvres = DAOOeuvre::getByArtisteIdPrivate($idartiste);
				$statutOeuvre = DAOStatutOeuvre::getByLibelle('Validée');
				$statutOeuvreShop = DAOStatutOeuvreShop::getByLibelle('A vendre');
				$lstOeuvresEnVente = array_filter($lesOeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvre, $statutOeuvreShop){
					try{
						return $oeuvreFiltered->getStatutOeuvre()->getId() == $statutOeuvre->getId() && $oeuvreFiltered->getStatutOeuvreShop()->getId() == $statutOeuvreShop->getId();
					}catch (Exception $ex){
						return false;
					}
				}, ARRAY_FILTER_USE_BOTH);
				$statutOeuvre = DAOStatutOeuvre::getByLibelle('Validée');
				$statutOeuvreShop = DAOStatutOeuvreShop::getByLibelle('Pas à vendre');
				$lstOeuvresPasEnVente = array_filter($lesOeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvre, $statutOeuvreShop){
					try{
						return $oeuvreFiltered->getStatutOeuvre()->getId() == $statutOeuvre->getId() && $oeuvreFiltered->getStatutOeuvreShop()->getId() == $statutOeuvreShop->getId();
					}catch (Exception $ex){
						return false;
					}
				}, ARRAY_FILTER_USE_BOTH);
				$statutOeuvre = DAOStatutOeuvre::getByLibelle('Validée');
				$statutOeuvreShop = DAOStatutOeuvreShop::getByLibelle('Vendue');
				$lstOeuvresVendues = array_filter($lesOeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvre, $statutOeuvreShop){
					try{
						return $oeuvreFiltered->getStatutOeuvre()->getId() == $statutOeuvre->getId() && $oeuvreFiltered->getStatutOeuvreShop()->getId() == $statutOeuvreShop->getId();
					}catch (Exception $ex){
						return false;
					}
				}, ARRAY_FILTER_USE_BOTH);
				$statutOeuvre = DAOStatutOeuvre::getByLibelle('En attente');
				$lstOeuvresEnAttente = array_filter($lesOeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvre){
					return $oeuvreFiltered->getStatutOeuvre()->getId() == $statutOeuvre->getId();
				}, ARRAY_FILTER_USE_BOTH);
				$statutOeuvre = DAOStatutOeuvre::getByLibelle('Refusée');
				$lstOeuvresRefusees = array_filter($lesOeuvres, function (Oeuvre $oeuvreFiltered) use ($statutOeuvre){
					return $oeuvreFiltered->getStatutOeuvre()->getId() == $statutOeuvre->getId();
				}, ARRAY_FILTER_USE_BOTH);
				http_response_code(200);
				print(json_encode(['nboeuvresenvente' => count($lstOeuvresEnVente), 'oeuvresenvente' => $lstOeuvresEnVente, 'nboeuvrespasenvente' => count($lstOeuvresPasEnVente), 'oeuvrespasenvente' => $lstOeuvresPasEnVente, 'nboeuvresvendues' => count($lstOeuvresVendues), 'oeuvresvendues' => $lstOeuvresVendues, 'nboeuvresenattente' => count($lstOeuvresEnAttente), 'oeuvresenattente' => $lstOeuvresEnAttente, 'nboeuvresrefusees' => count($lstOeuvresRefusees), 'oeuvresrefusees' => $lstOeuvresRefusees, 'baseurl_edit' => getUrl('oeuvres', 'form', 'edit'), 'userCurrency' => json_decode($_COOKIE['userCurrency']), 'userLocale' => json_decode($_COOKIE['userLocale'])]));
				break;
			}
		case 'getInfosEpreuve':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['epreuveFetched' => false, 'result' => 'error']));
				}else{
					$idEpreuve = intval($_GET['id'] ?? 0);
					if ($idEpreuve === 0){
						http_response_code(500);
						print(json_encode(['epreuveFetched' => false, 'result' => 'error']));
					}else{
						$epreuve = DAOEpreuveSculpture::getById($idEpreuve);
						http_response_code(200);
						print(json_encode(['epreuveFetched' => true, 'edition' => $epreuve, 'result' => 'success']));
					}
				}
				break;
			}
		case 'getInfosOeuvre':
			{
				if (empty($_GET)){
					http_response_code(500);
					print(json_encode(['oeuvreFetched' => false, 'result' => 'error']));
				}else{
					$idOeuvre = intval($_GET['id'] ?? 0);
					if ($idOeuvre === 0){
						http_response_code(500);
						print(json_encode(['oeuvreFetched' => false, 'result' => 'error']));
					}else{
						$oeuvre = DAOOeuvre::getById($idOeuvre);
						http_response_code(200);
						print(json_encode(['oeuvreFetched' => true, 'edition' => $oeuvre, 'result' => 'success']));
					}
				}
				break;
			}
		case 'getUserLocalCookie':
			{
				$cookie = $_COOKIE['userLocale'];
				http_response_code(200);
				print(json_encode(['cookieFetched' => true, 'userLocaleCookie' => $cookie, 'result' => 'success']));
				break;
			}
		case 'getUserCurrencyCookie':
			{
				$cookie = $_COOKIE['userCurrency'];
				http_response_code(200);
				print(json_encode(['cookieFetched' => true, 'userCurrencyCookie' => $cookie, 'result' => 'success']));
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
