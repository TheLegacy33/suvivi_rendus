<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 *
	 * @var User   $userLogged
	 *
	 */
	/**
	 * Redirections des pages ne respectant pas le flux
	 */

	if ($page == 'lost-password'){
		$referer = $_SERVER['HTTP_REFERER'] ?? '';
		if (DEV_MODE){
			if ($action == 'ask-renew' && $referer != EXTERNAL_URL . getUrl('utilisateur', 'login', 'view')){
				header('Location: ' . EXTERNAL_URL . getUrl('utilisateur', 'login', 'view'));
			}
		}
	}
	// Inclusion de CSS additionnel
	$includedCssScripts = [];
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case 'login':
			{
				$includedJSScripts = [
					HTML_PUBLIC_SCRIPTS_DIR . 'connexion-inscription.js'
				];
				require_once 'core/views/view_utilisateur_login.phtml';
				break;
			}
		case 'lost-password':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-renew-password.js'];
				if ($action == 'define-new-password'){
					$tokenSent = $_GET['token'] ?? '';
					$tokenValid = DAOUser::isValidToken($tokenSent);
				}
				require_once 'core/views/view_utilisateur_renew_password.phtml';
				break;
			}
		case 'new-password':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-create-password.js'];
				if ($action == 'define-new-password'){
					$tokenSent = $_GET['token'] ?? '';
					$tokenValid = DAOUser::isValidToken($tokenSent);
				}
				require_once 'core/views/view_utilisateur_create_password.phtml';
				break;
			}
		case 'tableau-de-bord':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					if (DAOArtiste::checkUserArtist($userLogged->getId())){
						$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-profil-artiste-complements.js"];
						$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());
						/**
						 * Pour les mots cles dans la modale compléments artiste
						 */
						$lstMotsClefsArtistesPlastiques = DAOMotsClefs::getAllByCategorie(DAOCategorieMotsClefs::getByLibelle('Artiste Plastique'));
						if (AIApplication::isActive('artistevivant')){
							$lstMotsClefsArtistesVivants = DAOMotsClefs::getAllByCategorie(DAOCategorieMotsClefs::getByLibelle('Artiste Vivant'));
						}else{
							$lstMotsClefsArtistesVivants = array();
						}
						$lstMotsClefs = array_merge($lstMotsClefsArtistesPlastiques, $lstMotsClefsArtistesVivants);
					}
					require_once 'core/views/view_tableau_de_bord.phtml';
				}
				break;
			}
		case 'mon-compte':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$lstCivilites = DAOCivilite::getAll();
					$lstStatutsAdresse = DAOStatutAdresse::getAll();
					$lstCategoriesArtistes = DAOCategorieArtiste::getAll();
					$lstPays = DAOPays::getAll();
					$lstDepartements = DAODepartement::getAll();
					$lstRegions = DAORegion::getAll();
					$lstTauxNegociation = DAOTauxNegociation::getAllActive();
					$lstTypesAcheteur = DAOTypeAcheteur::getAll();
					$lstMethodesVirement = DAOMethodeVirement::getAllActive();
					$lstStatutCompte = DAOStatutCompte::getAll();
					$lstTypesCompte = DAOTypeCompte::getAllActive();
					$lstDevises = DAODevises::getAll();
					$lstPaysStared = DAOPays::getAll(includeStared: true, staredOnly: true);
					$lstPaysNotStared = DAOPays::getAll(includeStared: false, staredOnly: false, staredFirst: false);
					// Si utilisateur acheteur charger vue utilisateur sinon charger vue artiste
					$view = 'core/views/view_error.phtml';
					$includedJSScripts = [HTML_PUBLIC_LIBS_DIR . "cropper.min.js", 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . INTL_TEL_INPUT_VERSION . '/build/js/intlTelInput.min.js', HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-champs-telephone.js"];
					if (count($userLogged->getRoles()) == 1 and $userLogged->hasRole(DAORoleUser::getById(2))){ // acheteur uniquement
						$includedJSScripts[] = HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-profils.js";
						$profilLogged = DAOUser::getById($userLogged->getId());
						$view = 'core/views/view_utilisateur_mon_compte.phtml';
					}else if ($userLogged->hasRole(DAORoleUser::getById(3))){ // Role vendeur
						$includedJSScripts[] = HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-profils-artiste.js";
						$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());
						$view = 'core/views/view_utilisateur_mon_compte_artiste.phtml';
					}
					$adresseFacturation = $userLogged->getAdresseFacturation();
					$adresseLivraison = $userLogged->getAdresseLivraison();
					require_once $view;
				}
				break;
			}
		case 'mes-coups-de-coeur':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('utilisateur', 'connexion-inscription') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-coups-coeur.js"];
					// Je dois prendre en compte celle qui a pu être stockée en cookies
					$wishLists = [];
					if (isset($_COOKIE['wishes']) and $_COOKIE['wishes'] != '' and $_COOKIE['wishes'] != '[]'){
						$oeuvresEnListeLocale = json_decode($_COOKIE['wishes']);
						$wishLists[] = new ListeSouhait('Non Sauvegardée', null, false);
						foreach ($oeuvresEnListeLocale as $oeuvreEnListe){
							$wishLists[count($wishLists) - 1]->addOeuvre(DAOOeuvre::getById($oeuvreEnListe->oeuvreId));
						}
					}
					// Et je récupère celles de la bdd
					/**
					 * @var ListeSouhait $list
					 */
					foreach (DAOListeSouhait::getAllByUser($userLogged) as $list){
						$wishLists[] = $list;
					}
					$collectionMur = array_filter($wishLists, function (ListeSouhait $liste){
						return $liste->isDefault();
					}, ARRAY_FILTER_USE_BOTH);
					$collectionsAutres = array_filter($wishLists, function (ListeSouhait $liste){
						return !$liste->isDefault();
					}, ARRAY_FILTER_USE_BOTH);
					require_once 'core/views/view_utilisateur_coups_de_coeur.phtml';
				}
				break;
			}
		case 'mes-artistes':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
					//				header('Location: ' . getUrl('index'));
				}else{
					$lstFollow = DAOUser::getFollowList($userLogged);
					$lstArtiste = [];
					foreach ($lstFollow as $idArtiste){
						$lstArtiste[] = DAOArtiste::getById($idArtiste);
					}
					require_once 'core/views/view_utilisateur_artistes_favoris.phtml';
				}
				break;
			}
		case 'mes-commandes':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					//debug($userLogged);
					$lstCommandes = DAOCommande::getAllByUserId($userLogged->getId());
					require_once 'core/views/view_utilisateur_commandes.phtml';
				}
				break;
			}
		case 'acheteur-mes-commandes-sur-mesures':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = ['https://js.stripe.com/v3/', HTML_PUBLIC_SCRIPTS_DIR . "js-stripe-toolkit.js", HTML_PUBLIC_SCRIPTS_DIR . "js-paiement-commande-sur-mesure.js", HTML_PUBLIC_SCRIPTS_DIR . 'js-custom-commande.js'];
					//				debug($userLogged);
					$uniteMesure = DAOUnite::getByLibelle('Internationale');
					$deviseOeuvre = DAODevises::getById(2);
					$lstDevises = DAODevises::getAll();
					$emailAcheteur = DEV_MODE ? 'michel@avalone-fr.com' : $userLogged->getEmail();
					$lstCommandes = DAOCommandeCustom::getAllByUserId($userLogged->getId());
					//debug($lstCommandes);
					if (isset($_GET['filter'])){
					}
					require_once 'core/views/view_utilisateur_commandes_sur_mesure_acheteur.phtml';
				}
				break;
			}
		case 'detail-commande':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					require_once 'core/views/view_utilisateur_detail_commande.phtml';
				}
				break;
			}
		case 'facture-achat':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$factureAchat = DAOFactureAchat::getById($_GET['id']);
					if (is_null($factureAchat->getCheminFacturePDF()) or !file_exists($factureAchat->getCheminFacturePDF())){
						FactureAchatPDF::generatePDF($factureAchat);
					}
					$fullExternalFilePath = $factureAchat->getPublicFacturePDF();
					$fileName = pathinfo($fullExternalFilePath, PATHINFO_FILENAME);
					require_once 'core/views/view_utilisateur_facture_acheteur.phtml';
				}
				break;
			}
		case 'mes-ventes':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "attachment_colis.js", HTML_PUBLIC_SCRIPTS_DIR . "attachment_certificat.js", HTML_PUBLIC_SCRIPTS_DIR . "js-recap-vente.js"];
					// Récupération des ventes de l'artiste en cours
					$lstCommandes = DAOCommandeVendeur::getAllByIdArtiste($artisteLogged->getId());
					require_once 'core/views/view_utilisateur_mes_ventes.phtml';
				}
				break;
			}
		case 'mes-commandes-sur-mesures':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-custom-commande.js'];
					$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());
					$uniteMesure = DAOUnite::getByLibelle('Internationale');
					$deviseOeuvre = DAODevises::getById(2);
					$lstDevises = DAODevises::getAll();
					// Récupération des commandes sur mesure de l'artiste en cours
					$lstCommandes = DAOCommandeCustomVendeur::getAllByArtisteId($artisteLogged->getId());
					require_once 'core/views/view_utilisateur_mes_commandes_sur_mesures.phtml';
				}
				break;
			}
		case 'mes-negociations-oeuvres':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-negociation-oeuvre.js'];
					// Récupération des négociations de l'acheteur
					$lstStatutsNegociationOeuvre = DAOStatutNegociation::getAll();
					$lstNegociationsOeuvres = DAONegociationOeuvre::getAllByIdAcheteur($userLogged->getId());
					require_once 'core/views/view_utilisateur_mes_negociations_oeuvres.phtml';
				}
				break;
			}
		case 'facture-vendeur':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					require_once 'core/views/view_utilisateur_facture_vendeur.phtml';
				}
				break;
			}
		case 'facture-vente':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$factureVente = DAOFactureVente::getById($_GET['id']);
					if (is_null($factureVente->getCheminFacturePDF()) or !file_exists($factureVente->getCheminFacturePDF())){
						FactureVentePDF::generatePDF($factureVente);
					}
					$fullExternalFilePath = $factureVente->getPublicFacturePDF();
					$fileName = pathinfo($fullExternalFilePath, PATHINFO_FILENAME);
					require_once 'core/views/view_utilisateur_facture_vendeur.phtml';
				}
				break;
			}
		case 'detail-livraison-commande':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					require_once 'core/views/view_utilisateur_detail_livraison_commande.phtml';
				}
				break;
			}
		case 'probleme-commande':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					require_once 'core/views/view_utilisateur_probleme_commande.phtml';
				}
				break;
			}
		case 'evaluer-vendeur':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					require_once 'core/views/view_utilisateur_evaluer_vendeur.phtml';
				}
				break;
			}
		case 'commenter-oeuvre':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$tabOeuvresConsultees = [1, 2, 3, 4, 5];
					require_once 'core/views/view_utilisateur_commenter_oeuvre.phtml';
				}
				break;
			}
		case 'retour-oeuvre':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
					//				header('Location: ' . getUrl('index'));
				}else{
					require_once 'core/views/view_utilisateur_retour_oeuvre.phtml';
				}
				break;
			}
		case 'perso-newsletters':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
					//				header('Location: ' . getUrl('index'));
				}else{
					$lstTypesArtsScandale = DAOTypeArtScandale::getAll();
					$abonnementsNews = $userLogged->getAbonnements();
					$chNewsletter = $_POST['chNewsletter'] ?? 'off';
					$checked = '';
					foreach ($abonnementsNews as $news){
						if ($news->getId() === 1){
							if ($news->isActif()){
								$checked = 'checked';
							}else{
								$checked = '';
							}
						}
					}
					if (!empty($_POST) and isset($_POST['btnBackHome'])){
						print('<script>location.replace("' . getUrl('index') . '"); </script>');
					}
					if (!empty($_POST) and isset($_POST['btnSubmitChoixNews'])){
						if (BDD::openTransaction()){
							if (count($abonnementsNews) != 0){
								// cas ou l'utilisateur possède au moins 1 abonnement
								if (DAOUser::userHasNewsletter($userLogged, 1)){
									foreach ($abonnementsNews as $news){
										if ($news->getId() === 1){
											if ($chNewsletter === 'off'){
												$actif = false;
												$checked = '';
												if (!DAOUser::updateActifNewsletter($userLogged, $news, $actif)){
													BDD::rollbackTransaction();
												}else{
													BDD::commitTransaction();
												}
											}else{
												$actif = true;
												$checked = 'checked';
												if (!DAOUser::updateActifNewsletter($userLogged, $news, $actif)){
													BDD::rollbackTransaction();
												}else{
													BDD::commitTransaction();
												}
											}
										}
									}
								}else{
									if ($chNewsletter != 'off'){
										$checked = 'checked';
										$userLogged->addAbonnement(DAOAbonnement::getByLibelle('Newsletter générale'));
										if (!DAOUser::insertNewsletter($userLogged, DAOAbonnement::getByLibelle('Newsletter générale'))){
											BDD::rollbackTransaction();
										}else{
											BDD::commitTransaction();
										}
									}
								}
							}else{
								// cas ou l'utilisateur ne possède pas d'abonnement
								if ($chNewsletter != 'off'){
									$checked = 'checked';
									$userLogged->addAbonnement(DAOAbonnement::getByLibelle('Newsletter générale'));
									if (!DAOUser::insertNewsletter($userLogged, DAOAbonnement::getByLibelle('Newsletter générale'))){
										BDD::rollbackTransaction();
									}else{
										BDD::commitTransaction();
									}
								}
							}
						}
					}
					require_once 'core/views/view_utilisateur_perso_newsletter.phtml';
				}
				break;
			}
		case 'perso-notifications':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
					//				header('Location: ' . getUrl('index'));
				}else{
					require_once 'core/views/view_utilisateur_perso_notifications.phtml';
				}
				break;
			}
		case 'messagerie':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [ //HTML_PUBLIC_LIBS_DIR.'tmce/tinymce.min.js',
						"https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js", HTML_PUBLIC_SCRIPTS_DIR . "js-messagerie.js"];
					$lstArtistes = DAOMessagerie::getAllArtistes();
					$lstGestionnaires = DAOMessagerie::getAIUsers();
					$lstObjetsMessage = DAOObjetMessage::getAll();
					//				debug(PHP_PUBLIC_IMAGES_DIR.'logo.svg');
					//				debug(EXTERNAL_URL.HTML_PUBLIC_IMAGES_DIR.'logo.svg');
					require_once 'core/views/view_utilisateur_messagerie.phtml';
				}
				break;
			}
		case 'les-outils-ai':
			{
				if (!isset($userLogged) or ($userLogged->getId() == 0)){
					print('<script>location.replace("' . getUrl('index') . '"); </script>');
				}else{
					$includedJSScripts = [];
					require_once 'core/views/view_utilisateur_outils_ai.phtml';
				}
				break;
			}
		case 'club-noir':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('utilisateur', 'connexion-inscription') . '"); </script>');
					//				header('Location: ' . getUrl('index'));
				}else{
					$lstTypesArtsScandale = DAOTypeArtScandale::getAll();
					$abonnementsNews = $userLogged->getAbonnements();
					$chkNewsScandale = $_POST['chNewsScandale'] ?? 'off';
					$checkedGeneral = '';
					if (!empty($_POST) and isset($_POST['btnSubmitChoix'])){
						// Je dois supprimer les liens pour l'utilisateur avec les types d'art scandale
						if (BDD::openTransaction()){
							if (DAOTypeArtScandale::removeForUser($userLogged)){
								if (!empty($_POST['chNewsletterScandale'])){
									// Je dois les ajouter
									foreach ($_POST['chNewsletterScandale'] as $idType){
										$type = DAOTypeArtScandale::getById(intval($idType));
										DAOTypeArtScandale::addToUser($userLogged, $type);
									}
								}
								BDD::commitTransaction();
							}else{
								BDD::rollbackTransaction();
							}
						}
						$userLogged->setTypesArtsScandale(DAOTypeArtScandale::getAllByIdUser($userLogged->getId()));
						if (BDD::openTransaction()){
							if (count($abonnementsNews) != 0){
								// cas ou l'utilisateur possède au moins 1 abonnement
								if (DAOUser::userHasNewsletter($userLogged, 5)){
									foreach ($abonnementsNews as $news){
										if ($news->getId() === 5){
											if ($chkNewsScandale === 'off'){
												$actif = false;
												if (!DAOUser::updateActifNewsletter($userLogged, $news, $actif)){
													BDD::rollbackTransaction();
												}else{
													DAOTypeArtScandale::removeForUserNewsletter($userLogged);
													BDD::commitTransaction();
												}
											}else{
												$actif = true;
												if (!DAOUser::updateActifNewsletter($userLogged, $news, $actif)){
													BDD::rollbackTransaction();
												}else{
													if (DAOTypeArtScandale::removeForUserNewsletter($userLogged)){
														if (!empty($_POST['chNewsletterScandaleNewsletter'])){
															// Je dois les ajouter
															foreach ($_POST['chNewsletterScandaleNewsletter'] as $idType){
																$type = DAOTypeArtScandale::getById(intval($idType));
																DAOTypeArtScandale::addToUserNewsletter($userLogged, $type);
															}
															$checkedGeneral = 'checked';
															BDD::commitTransaction();
														}else{
															BDD::rollbackTransaction();
														}
													}else{
														BDD::rollbackTransaction();
													}
												}
											}
										}
									}
								}else{
									if ($chkNewsScandale != 'off'){
										$checkedGeneral = 'checked';
										$userLogged->addAbonnement(DAOAbonnement::getByLibelle('Newsletter scandale'));
										if (!DAOUser::insertNewsletter($userLogged, DAOAbonnement::getByLibelle('Newsletter scandale'))){
											BDD::rollbackTransaction();
										}else{
											if (DAOTypeArtScandale::removeForUserNewsletter($userLogged)){
												if (!empty($_POST['chNewsletterScandaleNewsletter'])){
													// Je dois les ajouter
													foreach ($_POST['chNewsletterScandaleNewsletter'] as $idType){
														$type = DAOTypeArtScandale::getById(intval($idType));
														DAOTypeArtScandale::addToUserNewsletter($userLogged, $type);
													}
												}
												BDD::commitTransaction();
											}else{
												BDD::rollbackTransaction();
											}
										}
									}else{
										DAOTypeArtScandale::removeForUserNewsletter($userLogged);
										BDD::commitTransaction();
									}
								}
							}else{
								// cas ou l'utilisateur ne possède pas d'abonnement
								if ($chkNewsScandale != 'off'){
									$checkedGeneral = 'checked';
									$userLogged->addAbonnement(DAOAbonnement::getByLibelle('Newsletter scandale'));
									if (!DAOUser::insertNewsletter($userLogged, DAOAbonnement::getByLibelle('Newsletter scandale'))){
										BDD::rollbackTransaction();
									}else{
										if (DAOTypeArtScandale::removeForUserNewsletter($userLogged)){
											if (!empty($_POST['chNewsletterScandaleNewsletter'])){
												// Je dois les ajouter
												foreach ($_POST['chNewsletterScandaleNewsletter'] as $idType){
													$type = DAOTypeArtScandale::getById(intval($idType));
													DAOTypeArtScandale::addToUserNewsletter($userLogged, $type);
												}
											}
											BDD::commitTransaction();
										}else{
											BDD::rollbackTransaction();
										}
									}
								}else{
									DAOTypeArtScandale::removeForUserNewsletter($userLogged);
									BDD::commitTransaction();
								}
							}
						}
						$userLogged->setTypesArtsScandaleNewsletter(DAOTypeArtScandale::getAllNewsletterByIdUser($userLogged->getId()));
					}
					require_once 'core/views/view_utilisateur_club_noir.phtml';
				}
				break;
			}
		case 'utilisateur-carte-cadeaux':
			{
				require_once 'core/views/view_utilisateur_carte_cadeaux.phtml';
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';