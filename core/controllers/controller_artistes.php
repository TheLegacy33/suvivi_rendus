<?php

/**
 * @var string $page
 * @var string $section
 * @var string $action
 * @var User   $userLogged
 *
 * Gestion des données
 */
// Pour le cache control
header("Cache-Control: no-cache, must-revalidate");
// Inclusion de CSS additionnel
$includedCssScripts = [['href' => HTML_PUBLIC_STYLES_DIR . 'fontawesome/css/fontawesome.min.css'], ['href' => HTML_PUBLIC_STYLES_DIR . 'fontawesome/css/solid.min.css'], ['href' => HTML_PUBLIC_LIBS_DIR . 'ps/perfect-scrollbar.css'], ['href' => 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . INTL_TEL_INPUT_VERSION . '/build/css/intlTelInput.css']];
$idViewer = null;
if ($page == 'profil') {
	//Update compteur de vues
	if (isset($_GET['id']) and !$userLogged->isAdmin() and !$userLogged->hasRole(DAORoleUser::getByLibelle('Gestionnaire'))) {
		if (!isset($_COOKIE['idviewer'])) {
			$idViewer = uniqid(more_entropy: true);
			$arr_cookie_options = array(
				'expires' => time() + (86400 * 3650),
				'path' => '/',
				'domain' => '', // leading dot for compatibility or use subdomain
				'secure' => true,     // or false
				'httponly' => true,    // or false
				'samesite' => 'None' // None || Lax  || Strict
			);
			setcookie('idviewer', $idViewer, $arr_cookie_options);
		} else {
			$idViewer = $_COOKIE['idviewer'];
		}
	}
}
require_once 'core/views/template/header.phtml';
switch ($page) {
	case 'profil': {
			$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'text-expand.js', HTML_PUBLIC_SCRIPTS_DIR . 'tabs-navs.js', HTML_PUBLIC_SCRIPTS_DIR . 'js-page-artiste.js', HTML_PUBLIC_SCRIPTS_DIR . 'js-custom-commande.js', 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . INTL_TEL_INPUT_VERSION . '/build/js/intlTelInput.min.js'];
			$tabSeries = [];
			$tabOeuvresVendues = [];
			$oeuvreConsulte = [];
			$vivant = false;
			$allOeuvreInSerie = [];
			$artiste = DAOArtiste::getById($_GET['id'] ?? 0);
			if (!$userLogged->isAdmin() and $artiste->getUser()->getId() != $userLogged->getId()) {
				if (!is_null($idViewer)) {
					DAOArtiste::updateCompteurVues($idViewer, intval($_GET['id']));
					$artiste->setNbVues(DAOArtiste::getNbVues($artiste->getId()));
				}
			}
			$lstUser = DAOUser::getAll();
			$lstFollow = [];
			$lstArtisteFollow = [];
			//debug(DAOArtiste::getById($_GET['id'] ?? 0)->getId());
			//debug(DAOUser::getFollowList(DAOUser::getById(239)));
			foreach ($lstUser as $user) {
				if ($user->getId() != $userLogged->getId()) {
					if (!empty(DAOUser::getFollowList($user))) {
						foreach (DAOUser::getFollowList($user) as $iduser) {
							//debug($iduser);
							if (DAOArtiste::getById($iduser)->getId() == $artiste->getId()) //
							{
								$lstFollow[] = $user;
							}
						}
					}
				}
			}
			$lesOeuvres = DAOOeuvre::getByArtisteIdPublic($artiste->getId(), withScandale: (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin())));
			$removeVendue = [];
			foreach ($lesOeuvres as $uneOeuvre) {
				if ($uneOeuvre->getStatutOeuvre()->getId() != 4 or ($uneOeuvre->getStatutOeuvre()->getId() == 2 and $uneOeuvre->getStatutOeuvreShop()->getId() != 3)) {
					$removeVendue[] = $uneOeuvre;
				};
			}
			$artisteLoggedFilter = DAOArtiste::getByIdUser($userLogged->getId());
			$lesOeuvresFiltres = array_filter($removeVendue, function (Oeuvre $oeuvreFiltered) use ($userLogged, $artisteLoggedFilter) {
				if ($userLogged->isAdmin()) {
					if (!$oeuvreFiltered->hasArtScandale()) {
						return true;
					} else {
						if ($oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							return false;
						}
					}
				} else {
					if ($userLogged->isAuthentified()) {
						if (!$oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							if ($oeuvreFiltered->hasArtScandale() and ($userLogged->acceteArtScandale() or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste()))) {
								foreach ($oeuvreFiltered->getTypesArtScandale() as $typeArtScandale) {
									if (in_array($typeArtScandale, $userLogged->getTypesArtsScandale()) or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste())) {
										return true;
									} else {
										return false;
									}
								}
								return true;
							} else {
								return false;
							}
						}
					} else {
						if (!$oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							return false;
						}
					}
				}
			}, ARRAY_FILTER_USE_BOTH);
			$allOeuvre = DAOOeuvre::getByArtisteIdPublic($artiste->getId(), withScandale: true);
			$nbAllOeuvre = count($allOeuvre);
			foreach ($allOeuvre as $uneOeuvre) {
				if ($uneOeuvre->getStatutOeuvre()->getId() == 4 or ($uneOeuvre->getStatutOeuvre()->getId() == 2 and $uneOeuvre->getStatutOeuvreShop()->getId() == 3)) {
					$tabOeuvresVendues[] = $uneOeuvre;
				};
			}
			$allOeuvreVendueFiltre = array_filter($tabOeuvresVendues, function (Oeuvre $oeuvreFiltered) use ($userLogged, $artisteLoggedFilter) {
				if ($userLogged->isAdmin()) {
					if (!$oeuvreFiltered->hasArtScandale()) {
						return true;
					} else {
						if ($oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							return false;
						}
					}
				} else {
					if ($userLogged->isAuthentified()) {
						if (!$oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							if ($oeuvreFiltered->hasArtScandale() and ($userLogged->acceteArtScandale() or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste()))) {
								foreach ($oeuvreFiltered->getTypesArtScandale() as $typeArtScandale) {
									if (in_array($typeArtScandale, $userLogged->getTypesArtsScandale()) or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste())) {
										return true;
									} else {
										return false;
									}
								}
								return true;
							} else {
								return false;
							}
						}
					} else {
						if (!$oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							return false;
						}
					}
				}
			}, ARRAY_FILTER_USE_BOTH);
			$nbVendu = count($tabOeuvresVendues);
			$nbAllOeuvreEnVente = $nbAllOeuvre - $nbVendu;
			$nbVenduFiltre = count($allOeuvreVendueFiltre);
			$FiltreOeuvre = count($lesOeuvresFiltres);
			$lesOeuvresScandale = array_filter($allOeuvre, function (Oeuvre $oeuvreFiltered) use ($userLogged, $artisteLoggedFilter) {
				if ($oeuvreFiltered->hasArtScandale()) {
					return true;
				} else {
					return false;
				}
			}, ARRAY_FILTER_USE_BOTH);
			$nbOeuvreScandale = count($lesOeuvresScandale);
			$artiste->setOeuvres($lesOeuvresFiltres);
			$lstOeuvresNew = $artiste->getLastOeuvres();
			$lesSeries = DAOSerie::getByArtisteId($artiste->getId());
			/**
			 * @var Serie $serie
			 */
			foreach ($lesSeries as $uneSerie) {
				$uneSerie->setOeuvres(DAOOeuvre::getByIdSerie($uneSerie->getId(), withScandale: (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin()))));
				$lesOeuvresFiltres = array_filter($uneSerie->getOeuvres(), function (Oeuvre $oeuvreFiltered) use ($userLogged, $artisteLoggedFilter) {
					if ($userLogged->isAdmin()) {
						if (!$oeuvreFiltered->hasArtScandale()) {
							return true;
						} else {
							if ($oeuvreFiltered->hasArtScandale()) {
								return true;
							} else {
								return false;
							}
						}
					} else {
						if ($userLogged->isAuthentified()) {
							if (!$oeuvreFiltered->hasArtScandale()) {
								return true;
							} else {
								if ($oeuvreFiltered->hasArtScandale() and ($userLogged->acceteArtScandale() or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste()))) {
									foreach ($oeuvreFiltered->getTypesArtScandale() as $typeArtScandale) {

										if (in_array($typeArtScandale, $userLogged->getTypesArtsScandale()) or ($artisteLoggedFilter and $artisteLoggedFilter->getId() == $oeuvreFiltered->getIdArtiste())) {
											return true;
										} else {
											return false;
										}
									}
									return true;
								} else {
									return false;
								}
							}
						} else {
							if (!$oeuvreFiltered->hasArtScandale()) {
								return true;
							} else {
								return false;
							}
						}
					}
				}, ARRAY_FILTER_USE_BOTH);
				$uneSerie->setOeuvres($lesOeuvresFiltres);
			}

			$nbOeuvresDansSeries = 0;
			foreach ($lesSeries as $uneSerie) {
				$nbOeuvresDansSeries += count($uneSerie->getOeuvres());
			}
			if ($nbOeuvresDansSeries > 0) {
				$artiste->setSeries($lesSeries);
			}
			if (isset($_COOKIE['id_oeuvre'])) {
				$idOeuvreCookie = json_decode($_COOKIE['id_oeuvre'], true);
				foreach ($idOeuvreCookie as $id) {
					$oeuvreConsulte[] = DAOOeuvre::getById($id);
				}
			}
			$lstCivilites = DAOCivilite::getAll();
			require_once 'core/views/view_profil_artiste.phtml';
			break;
		}
	case 'galerie': {
			$includedJSScriptsFirst = [HTML_PUBLIC_LIBS_DIR . "ps/perfect-scrollbar.js"];
			$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "galerie_artiste.js",];
			$lstPays = DAOPays::getAll();
			$lstQualifications = DAOQualificationArtiste::getAll();
			// Pour les filtres
			// Je dois récupérer toutes les initiales du nom des artistes
			$tabToutesLettres = genereTabLettres();
			$tabInitiales = DAOArtiste::getCountByInitiale(); // 2 : Validé
			foreach ($tabInitiales as $uneLettre => $nb) {
				$tabToutesLettres[$uneLettre] = ($nb > 0);
				$tabToutesLettres[chr(35)] += $nb;
			}
			$lstArtistes = [];
			switch ($action) {
				case 'view': {
						//$lstArtistesByName = DAOArtiste::getAllByName(DAOStatutUser::getById(2)); // 2 : Validé
						//$lstArtistesByPseudo = DAOArtiste::getAllByPseudo(DAOStatutUser::getById(2)); // 2 : Validé
						$depart = microtime(true);
						$lstArtistes = DAOArtiste::getAll(DAOStatutUser::getById(2), light: true);
						$fin = microtime(true);
						$temps = $fin - $depart;
						//Afficher le temps d'exécution
						$temps_de_chargement = number_format($temps, 3);
						break;
					}
				case 'search': {
						$keyword = $_POST["search"];
						$lstArtistes = DAOArtiste::searchArtistByName($keyword);
						break;
					}
			}
			foreach ($lstArtistes as $unArtiste) {
				// oeuvres
				$unArtiste->setOeuvres(DAOOeuvre::getByArtisteIdPublic($unArtiste->getId(), withScandale: (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin())), light: true));
				// séries
				//				$unArtiste->setSeries(DAOSerie::getByArtisteId($unArtiste->getId()));
			}
			usort($lstArtistes, function ($a, $b) {
				/**
				 * @var Artiste $a
				 * @var Artiste $b
				 */
				if ($a->getNbOeuvres() == $b->getNbOeuvres()) {
					return 0;
				}
				return ($a->getNbOeuvres() > $b->getNbOeuvres()) ? -1 : 1;
			});
			$nbParPage = 15;
			require_once 'core/views/view_mur_artistes.phtml';
			break;
		}
	case 'oeuvres': {
			$includedJSScriptsFirst = [HTML_PUBLIC_LIBS_DIR . "imagesloaded.pkgd.min.js", HTML_PUBLIC_LIBS_DIR . "masonry.pkgd.min.js"];
			$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-masonry.js", HTML_PUBLIC_SCRIPTS_DIR . "presentation-galerie.js"];
			$artiste = DAOArtiste::getById($_GET['idartiste'] ?? 0);
			$lstCouleurs = DAOCouleur::getAll();
			// pour differencier atelier et toute les oeuvres
			$atelier = $_GET['action'] ?? 'view';
			require_once 'core/views/view_galerie_oeuvre_artiste.phtml';
			break;
		}
	case 'serie-galerie': {
			$artiste = DAOArtiste::getById($_GET['idartiste'] ?? 0);
			$serieid = $_GET['idserie'] ?? 0;
			$artiste->setOeuvres(DAOOeuvre::getByArtisteIdPublic($artiste->getId(), withScandale: (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin()))));
			$artiste->setSeries(DAOSerie::getByArtisteId($artiste->getId()));
			$titre = $_GET['action'];
			$scandale = (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin()));
			switch ($action) {
				case 'nbserie': {
						$idserie = DAOSerie::getById($serieid);
						$idserie->setOeuvres(DAOOeuvre::getByIdSerie($serieid, withScandale: $scandale));
						require_once 'core/views/view_galerie_serie_artiste.phtml';
						break;
					}
				case 'allserie': {
						foreach ($artiste->getSeries() as $idserie) {
							$idserie->setOeuvres(DAOOeuvre::getByIdSerie($idserie->getId(), withScandale: $scandale));
						}
						require_once 'core/views/view_galerie_serie_artiste.phtml';
						break;
					}
			}
			break;
		}
	case 'mes-oeuvres-a-vendre': {
			if (!isset($userLogged) or $userLogged->getId() == 0) {
				print('<script>location.replace("' . getUrl('index') . '"); </script>');
			} else {
				$includedJSScriptsFirst = [HTML_PUBLIC_LIBS_DIR . "imagesloaded.pkgd.min.js", HTML_PUBLIC_LIBS_DIR . "masonry.pkgd.min.js"];
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-masonry.js", HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-mes-oeuvres-a-vendre.js"];
				$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());
				require_once 'core/views/view_utilisateur_oeuvres_a_vendre.phtml';
			}
			break;
		}
	case 'mes-negociations-oeuvres': {
			if (!isset($userLogged) or $userLogged->getId() == 0) {
				print('<script>location.replace("' . getUrl('index') . '"); </script>');
			} else {
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-negociation-oeuvre.js'];
				$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());

				// Récupération des négociations de l'artiste

				require_once 'core/views/view_artiste_mes_negociations_oeuvres.phtml';
			}
			break;
		}
	case 'mes-series': {
			if (!isset($userLogged) or $userLogged->getId() == 0) {
				print('<script>location.replace("' . getUrl('index') . '"); </script>');
			} else {
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-mes-series.js"];
				/**
				 * @var Serie $serie
				 */
				$profilLogged = DAOArtiste::getByIdUser($userLogged->getId());
				$profilLogged->setSeries(DAOSerie::getByArtisteId($profilLogged->getId()));
				foreach ($profilLogged->getSeries() as $serie) {
					$serie->setOeuvres(DAOOeuvre::getByIdSeriePrivate($serie->getId()));
				}
				$mesSeries = $profilLogged->getSeries();
				require_once 'core/views/view_utilisateur_mes_series.phtml';
			}
			break;
		}
	case 'atelier': {
			require_once 'core/views/view_artiste_atelier.phtml';
			break;
		}
	case 'other': {
			break;
		}
	default: {
			require_once('core/controllers/controller_error.php');
		}
}
require_once 'core/views/template/footer.phtml';
