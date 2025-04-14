<?php

/**
 * @var string  $page
 * @var string  $section
 * @var string  $action
 * @var User    $userLogged
 *
 * @var Oeuvre  $uneOeuvre
 * @var Oeuvre  $oeuvre
 *
 * @var Artiste $artiste
 * @var string $defaultLanguage
 *
 * Gestion des données
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
/**
 * Vérifie les critères pour une oeuvre
 * @param $elements : critères de l'oeuvre à vérifier
 * @param $idsCritere : id des critères postées dans la requête
 * @param $idOeuvresFiltered : id des oeuvres déjà filtrées en cours de traitement
 * @param $oeuvre : oeuvre en cours de traitement
 * @return bool
 */
function verifierCritere($elements, $idsCritere, $idOeuvresFiltered, $oeuvre): bool
{
	foreach ($elements as $element) {
		if (my_in_array($element->getId(), $idsCritere, true)) { // Si le critère fait parties des critères sélectionnés
			if (!my_in_array($oeuvre->getId(), $idOeuvresFiltered, true)) { // Si l'oeuvre n'a pas déjà été traitée et ajoutée
				return true;
			}
		}
	}
	return false;
}

switch ($action) {
	case 'filtre-oeuvres-json': {
			$postedCriteresStr = $_POST['criteres'] ?? '{}';
			$idArtiste = $_POST['idartiste'] ?? null;
			$idOeuvresFiltered = [];

			$categorieUrl = $_POST['categorie'] ?? 'all';
			$styleUrl = $_POST['style'] ?? 'all';
			$themeUrl = $_POST['theme'] ?? 'all';
			$scandale = $_POST['action'] ?? 'view';
			//Définition des seuils de compteurs de vues
			$seuilVuesArtistes = DAOArtiste::getSeuilVues(quartile: 2);
			$seuilVuesOeuvres = DAOOeuvre::getSeuilVues(quartile: 2);

			if (!is_null($idArtiste)) {
				$artisteLoggedFilter = DAOArtiste::getById($idArtiste);
				$oeuvreArtiste = DAOOeuvre::getByArtisteIdPublic($idArtiste, withScandale: (isset($userLogged) and ($userLogged->acceteArtScandale() or $userLogged->isAdmin())), light: true);

				foreach ($oeuvreArtiste as $uneOeuvre) {
					if ($uneOeuvre->getStatutOeuvre()->getId() != 4 and ($uneOeuvre->getStatutOeuvre()->getId() == 2  and $uneOeuvre->getStatutOeuvreShop()->getId() != 3)) {
						$toutesLesOeuvres[] = $uneOeuvre;
					};
				}
			} else {
				$artisteLoggedFilter = DAOArtiste::getByIdUser($userLogged->getId());
				$oeuvresScandales = ($scandale == 'scandale');
				$depart = microtime(true);
				$allOeuvre = DAOOeuvre::getAll(artScandale: $oeuvresScandales, light: (trim($postedCriteresStr) === '{}'));
				foreach ($allOeuvre as $uneOeuvre) {

					if ($uneOeuvre->getStatutOeuvre()->getId() != 4 and ($uneOeuvre->getStatutOeuvre()->getId() == 2  and $uneOeuvre->getStatutOeuvreShop()->getId() != 3)) {

						$toutesLesOeuvres[] = $uneOeuvre;
					}
				}
				$fin = microtime(true);
				$temps_de_chargement = number_format(($fin - $depart), 3);
			}
			$lesOeuvres = [];
			$idOeuvresFiltered = [];
			foreach ($toutesLesOeuvres as $uneOeuvre) {

				if (my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
					continue;
				} else {
					if ($categorieUrl != 'all') {
						$uneCategorie = DAOMedium::getByLibelle($categorieUrl);
						if (!in_array($uneCategorie, $uneOeuvre->getMediums())) {
							continue;
						}
					} elseif ($styleUrl != 'all') {
						$unStyle = DAOStyleOeuvre::getByLibelle($styleUrl);
						if (!in_array($unStyle, $uneOeuvre->getStyles())) {
							continue;
						}
					} elseif ($themeUrl != 'all') {
						$unTheme = DAOTheme::getByLibelle($themeUrl);
						if (!in_array($unTheme, $uneOeuvre->getThemes())) {
							continue;
						}
					} elseif ($scandale != 'view') {
						if ($userLogged->isAdmin()) {
							$oeuvreOk = true;
						} elseif (!$userLogged->acceteArtScandale()) {
							$oeuvreOk = false;
						} else {
							$oeuvreOk = true;
							$typesScandaleOeuvre = $uneOeuvre->getTypesArtScandale();
							$userTypesScandales = $userLogged->getTypesArtsScandale();
							$oeuvreOk = false;
							foreach ($userTypesScandales as $userTypeScandale) {
								if (in_array($userTypeScandale, $typesScandaleOeuvre)) {

									$oeuvreOk = true;
								}
							}

							//							foreach ($typesScandaleOeuvre as $typeScandaleOeuvre){
							//								if (!in_array($typeScandaleOeuvre, $userTypesScandales)){
							//									$oeuvreOk = false;
							//								}
							//							}
						}
						if (!$oeuvreOk) {
							continue;
						}
					} elseif (!is_null($idArtiste)) {

						$artisteLoggedFilter = DAOArtiste::getByIdUser($userLogged->getId());
						if (($artisteLoggedFilter and $artisteLoggedFilter->getId() == $uneOeuvre->getIdArtiste())) {
							$oeuvreOk = true;
						} else if ($userLogged->isAdmin()) {
							$oeuvreOk = true;
						} else {
							if (!$uneOeuvre->hasArtScandale()) {
								$oeuvreOk = true;
							} else {
								$oeuvreOk = true;
								$typesScandaleOeuvre = $uneOeuvre->getTypesArtScandale();
								$userTypesScandales = $userLogged->getTypesArtsScandale();
								$oeuvreOk = false;
								foreach ($userTypesScandales as $userTypeScandale) {
									if (in_array($userTypeScandale, $typesScandaleOeuvre)) {

										$oeuvreOk = true;
									}
								}
							}
							if (!$oeuvreOk) {
								continue;
							}
						}
					}
				}


				// Récupération des infos tags et localisation de l'artiste pour chaque oeuvre
				$tags = DAOMotsClefs::getByArtisteId($uneOeuvre->getIdArtiste());
				$artisteAdresses = DAOAdresse::getByArtisteId($uneOeuvre->getIdArtiste());
				$regionArtiste = '';
				if (count($artisteAdresses) > 0 and !is_null($artisteAdresses[0]->getDepartement())) {
					$regionArtiste = $artisteAdresses[0]->getDepartement()->getNom();
				}
				$paysArtiste = DAOPays::getPaysNationaliteByArtisteId($uneOeuvre->getIdArtiste());
				$paysExpedition = DAOPays::getPaysResidenceByArtisteId($uneOeuvre->getIdArtiste());

				$uneOeuvre->nbTagsArtiste = count($tags);
				$uneOeuvre->tagsArtiste = $tags;
				$uneOeuvre->regionArtiste = $regionArtiste;
				$uneOeuvre->paysArtiste = $paysArtiste->getNomFr();
				$uneOeuvre->paysExpedition = $paysExpedition->getNomFr();
				if (trim($postedCriteresStr) !== '{}') {
					$postedCriteres = json_decode($postedCriteresStr);
					foreach ($postedCriteres as $typeCritere => $idsCritere) {
						switch ($typeCritere) {
							case 'popularite': {
									//									if (!in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
									if (!my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										switch (intval($idsCritere[0])) {
											case 1: {
													//Confirmés
													$artisteCategorie = DAOArtiste::getById($uneOeuvre->getIdArtiste())->getCategorieArtiste()->getLibelle();
													if ($artisteCategorie == 'Artiste Professionnel') {
														$lesOeuvres[] = $uneOeuvre;
														$idOeuvresFiltered[] = $uneOeuvre->getId();
													}
													break;
												}
											case 2: {
													//Emergents
													$artisteCategorie = DAOArtiste::getById($uneOeuvre->getIdArtiste())->getCategorieArtiste()->getLibelle();

													if ($artisteCategorie == 'Artiste non professionnel') {
														$lesOeuvres[] = $uneOeuvre;
														$idOeuvresFiltered[] = $uneOeuvre->getId();
													}
													break;
												}
											case 3: {
													//Artiste les plus vus
													$nbVuesArtiste = DAOArtiste::getById($uneOeuvre->getIdArtiste())->getNbVues();
													if ($nbVuesArtiste >= $seuilVuesArtistes) {
														$lesOeuvres[] = $uneOeuvre;
														$idOeuvresFiltered[] = $uneOeuvre->getId();
													}
													break;
												}
											case 4: {
													//Bestseller
													$lesOeuvres[] = $uneOeuvre;
													break;
												}
											case 5: {
													//Oeuvre les plus vues
													$nbVuesOeuvre = $uneOeuvre->getNbVues();
													if ($nbVuesOeuvre >= $seuilVuesOeuvres) {
														$lesOeuvres[] = $uneOeuvre;
														$idOeuvresFiltered[] = $uneOeuvre->getId();
													}
													break;
												}
										}
									}
									break;
								}
							case 'medium': {
									$hasMedium = false;
									foreach ($uneOeuvre->getMediums() as $medium) {
										if (my_in_array($medium->getId(), $idsCritere, true)) {
											$hasMedium = true;
										}
									}

									if ($hasMedium && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}

									break;
								}
							case 'technique': {
									foreach ($uneOeuvre->getTechniques() as $uneTechnique) {
										if ($uneTechnique instanceof TechniqueMere) {
											if (verifierCritere($uneTechnique->getTechniquesFilles(), $idsCritere, $idOeuvresFiltered, $uneOeuvre)) {
												$lesOeuvres[] = $uneOeuvre;
												$idOeuvresFiltered[] = $uneOeuvre->getId();
											}
										}
									}

									break;
								}
							case 'style': {
									if (verifierCritere($uneOeuvre->getStyles(), $idsCritere, $idOeuvresFiltered, $uneOeuvre)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'theme': {
									if (verifierCritere($uneOeuvre->getThemes(), $idsCritere, $idOeuvresFiltered, $uneOeuvre)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'occasion': {
									if (verifierCritere($uneOeuvre->getOccasions(), $idsCritere, $idOeuvresFiltered, $uneOeuvre)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'couleur': {
									if (verifierCritere($uneOeuvre->getCouleurs(), $idsCritere, $idOeuvresFiltered, $uneOeuvre)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'orientation': {
									if (!is_null($uneOeuvre->getOrientation()) && my_in_array($uneOeuvre->getOrientation()->getId(), $idsCritere, true) && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'prix_min': {
									if ($uneOeuvre->getPrixUnitaire() >= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'prix_max': {
									if ($uneOeuvre->getPrixUnitaire() <= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'hauteur_min': {
									if ($uneOeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('hauteur'))->getValeur() >= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'hauter_max': {
									if ($uneOeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('hauteur'))->getValeur() <= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}
									break;
								}
							case 'largeur_min': {
									if ($uneOeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('largeur'))->getValeur() >= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}

									break;
								}
							case 'largeur_max': {
									if ($uneOeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('largeur'))->getValeur() <= $idsCritere[0] && !my_in_array($uneOeuvre->getId(), $idOeuvresFiltered, true)) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}

									break;
								}
							case 'pays_expedition': {
									//									if ($artiste->getResidence()->getId() == $idsCritere) {
									if ($paysExpedition->getId() == $idsCritere) {
										$lesOeuvres[] = $uneOeuvre;
										$idOeuvresFiltered[] = $uneOeuvre->getId();
									}

									break;
								}
						}
					}
				} else {
					$lesOeuvres[] = $uneOeuvre;
				}
			}

			$lesOeuvresFiltres = $lesOeuvres;
			http_response_code(200);
			$idArtiste = DAOArtiste::getByIdUser($userLogged->getId());

			$retVal = [
				'artisteIdLogged' => $idArtiste ? $idArtiste->getId() : 0,
				'pictocoeur' => file_get_contents(PHP_PUBLIC_IMAGES_DIR . "pictos/picto-coeur.svg"),
				//						'pictocoeur' => '<img src="'.HTML_PUBLIC_IMAGES_DIR.'pictos/picto-coeur.svg" alt="coeur">',
				'pictospanier' => HTML_PUBLIC_IMAGES_DIR . "new_img/vente-panier-2.png",
				'pictogalerie' => '',
				'pictoscandale' => HTML_PUBLIC_IMAGES_DIR . "pictos/1_modifie.png",
				'iconpromo' => HTML_PUBLIC_IMAGES_DIR . 'pictos/icon-promo.png',
				'oeuvres' => array_values($lesOeuvresFiltres),
				'userCurrency' => json_decode($_COOKIE['userCurrency']),
				'userLocale' => json_decode($_COOKIE['userLocale']),
				'trad' => Traducteur::getTraduction('00287', $defaultLanguage),
				'tradVues' => Traducteur::getTraduction('00251', $defaultLanguage)
			];
			if (isset($temps_de_chargement)) {
				$retVal = array_merge(['tps' => $temps_de_chargement], $retVal);
			}

			print(json_encode($retVal));
			break;
		}
	case 'filtre-oeuvres-json-test': {
			$postedCriteresStr = $_POST['criteres'] ?? '{}';
			$idArtiste = $_POST['idartiste'] ?? null;
			$categorieUrl = $_POST['categorie'] ?? 'all';
			$styleUrl = $_POST['style'] ?? 'all';
			$themeUrl = $_POST['theme'] ?? 'all';
			$scandale = $_POST['action'] ?? 'view';
			//Définition des seuils de compteurs de vues
			$seuilVuesArtistes = DAOArtiste::getSeuilVues(quartile: 2);
			$seuilVuesOeuvres = DAOOeuvre::getSeuilVues(quartile: 2);

			// Récupération des œuvres
			$depart = microtime(true);
			$toutesLesOeuvres = getOeuvres($idArtiste, $scandale, $postedCriteresStr);
			$fin = microtime(true);

			$lesOeuvres = filtrerOeuvres($toutesLesOeuvres, $postedCriteresStr, $categorieUrl, $styleUrl, $themeUrl, $seuilVuesArtistes, $seuilVuesOeuvres);


			$temps = $fin - $depart;
			//Afficher le temps d'exécution
			$temps_de_chargement = number_format($temps, 3);

			$idArtiste = DAOArtiste::getByIdUser($userLogged->getId());
			$retVal = [
				'artisteIdLogged' => $idArtiste ? $idArtiste->getId() : 0,
				'pictocoeur' => file_get_contents(PHP_PUBLIC_IMAGES_DIR . "pictos/picto-coeur.svg"),
				'pictoscandale' => HTML_PUBLIC_IMAGES_DIR . "pictos/1_modifie.png",
				'iconpromo' => HTML_PUBLIC_IMAGES_DIR . 'pictos/icon-promo.png',
				'oeuvres' => array_values($lesOeuvres),
				'userCurrency' => json_decode($_COOKIE['userCurrency']),
				'userLocale' => json_decode($_COOKIE['userLocale']),
				'trad' => Traducteur::getTraduction('00287', $defaultLanguage),
				'tradVues' => Traducteur::getTraduction('00251', $defaultLanguage)
			];
			// Si temps de chargement est mesuré, ajoutez-le à la réponse
			if (isset($temps_de_chargement)) {
				$retVal['tps'] = $temps_de_chargement;
			}
			print(json_encode($retVal));
			break;
		}
	default: {
			require_once 'core/controllers/controller_error.php';
		}
}
function getOeuvres($idArtiste, $scandale, $postedCriteresStr): array
{
	if (!is_null($idArtiste)) {
		return DAOOeuvre::getByArtisteIdPublic($idArtiste, withScandale: (isset($userLogged) && ($userLogged->acceteArtScandale() || $userLogged->isAdmin())), light: true);
	} else {
		$oeuvresScandales = ($scandale == 'scandale');
		return DAOOeuvre::getAll(artScandale: $oeuvresScandales, light: (trim($postedCriteresStr) === '{}'));
	}
}

function filtrerOeuvres($toutesLesOeuvres, $postedCriteresStr, $categorieUrl, $styleUrl, $themeUrl, $seuilVuesArtistes, $seuilVuesOeuvres): array
{
	$lesOeuvres = [];
	$idOeuvresFiltered = [];
	$postedCriteres = json_decode($postedCriteresStr);
	foreach ($toutesLesOeuvres as $uneOeuvre) {
		if (in_array($uneOeuvre->getId(), $idOeuvresFiltered, true))
			continue;
		if (!appliquerFiltresGeneraux($uneOeuvre, $categorieUrl, $styleUrl, $themeUrl))
			continue;
		if (!appliquerFiltresPersonnalises($uneOeuvre, $postedCriteres, $seuilVuesArtistes, $seuilVuesOeuvres))
			continue;
		// Charger les informations de localisation et tags
		enrichirInformationsOeuvre($uneOeuvre);
		$lesOeuvres[] = $uneOeuvre;
		$idOeuvresFiltered[] = $uneOeuvre->getId();
	}
	return $lesOeuvres;
}

function appliquerFiltresGeneraux($oeuvre, $categorieUrl, $styleUrl, $themeUrl): bool
{
	if ($categorieUrl != 'all' && !in_array($categorieUrl, $oeuvre->getMediums()))
		return false;
	if ($styleUrl != 'all' && !in_array($styleUrl, $oeuvre->getStyles()))
		return false;
	if ($themeUrl != 'all' && !in_array($themeUrl, $oeuvre->getThemes()))
		return false;
	return true;
}

/**
 * @param Oeuvre $oeuvre
 * @param $postedCriteres
 * @param $seuilVuesArtistes
 * @param $seuilVuesOeuvres
 * @return bool
 */

function appliquerFiltresPersonnalises(Oeuvre $oeuvre, $postedCriteres, $seuilVuesArtistes, $seuilVuesOeuvres): bool
{
	if (empty($postedCriteres)) return true;

	foreach ($postedCriteres as $typeCritere => $idsCritere) {
		switch ($typeCritere) {
			case 'popularite':
				$categorie = DAOArtiste::getById($oeuvre->getIdArtiste())->getCategorieArtiste()->getLibelle();
				if (($idsCritere[0] == 1 && $categorie != 'Artiste Professionnel') ||
					($idsCritere[0] == 2 && $categorie != 'Artiste non professionnel') ||
					($idsCritere[0] == 3 && DAOArtiste::getById($oeuvre->getIdArtiste())->getNbVues() < $seuilVuesArtistes) ||
					($idsCritere[0] == 5 && $oeuvre->getNbVues() < $seuilVuesOeuvres)
				) {
					return false;
				}
				break;

			case 'medium':
				if (!array_intersect($idsCritere, array_column($oeuvre->getMediums(), 'id'))) return false;
				break;

			case 'technique':
				$techniquesIds = [];
				foreach ($oeuvre->getTechniques() as $technique) {
					if ($technique instanceof TechniqueMere) {
						$techniquesIds = array_merge($techniquesIds, array_column($technique->getTechniquesFilles(), 'id'));
					} else {
						$techniquesIds[] = $technique->getId();
					}
				}
				if (!array_intersect($idsCritere, $techniquesIds)) return false;
				break;

			case 'style':
				if (!array_intersect($idsCritere, array_column($oeuvre->getStyles(), 'id'))) return false;
				break;

			case 'theme':
				if (!array_intersect($idsCritere, array_column($oeuvre->getThemes(), 'id'))) return false;
				break;

			case 'occasion':
				if (!array_intersect($idsCritere, array_column($oeuvre->getOccasions(), 'id'))) return false;
				break;

			case 'couleur':
				if (!array_intersect($idsCritere, array_column($oeuvre->getCouleurs(), 'id'))) return false;
				break;

			case 'orientation':
				if (!is_null($oeuvre->getOrientation()) && !in_array($oeuvre->getOrientation()->getId(), $idsCritere)) return false;
				break;

			case 'prix_min':
				if ($oeuvre->getPrixUnitaire() < $idsCritere[0]) return false;
				break;

			case 'prix_max':
				if ($oeuvre->getPrixUnitaire() > $idsCritere[0]) return false;
				break;

			case 'hauteur_min':
				if ($oeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('hauteur'))->getValeur() < $idsCritere[0]) return false;
				break;

			case 'hauteur_max':
				if ($oeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('hauteur'))->getValeur() > $idsCritere[0]) return false;
				break;

			case 'largeur_min':
				if ($oeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('largeur'))->getValeur() < $idsCritere[0]) return false;
				break;

			case 'largeur_max':
				if ($oeuvre->getCaracteristique(DAOCaracteristique::getByLibelle('largeur'))->getValeur() > $idsCritere[0]) return false;
				break;

			case 'pays_expedition':
				$paysExpedition = DAOPays::getPaysResidenceByArtisteId($oeuvre->getIdArtiste());
				if ($paysExpedition->getId() != $idsCritere) return false;
				break;

			default:
		}
	}
	return true;
}

function enrichirInformationsOeuvre($oeuvre): void
{
	$tags = DAOMotsClefs::getByArtisteId($oeuvre->getIdArtiste());
	$artisteAdresses = DAOAdresse::getByArtisteId($oeuvre->getIdArtiste());
	$regionArtiste = count($artisteAdresses) > 0 ? (is_null($artisteAdresses[0]->getRegion()) ? '' : $artisteAdresses[0]->getRegion()->getNom()) : '';
	$paysArtiste = DAOPays::getPaysNationaliteByArtisteId($oeuvre->getIdArtiste());
	$paysExpedition = DAOPays::getPaysResidenceByArtisteId($oeuvre->getIdArtiste());
	$oeuvre->nbTagsArtiste = count($tags);
	$oeuvre->tagsArtiste = $tags;
	$oeuvre->regionArtiste = $regionArtiste;
	$oeuvre->paysArtiste = $paysArtiste->getNomFr();
	$oeuvre->paysExpedition = $paysExpedition->getNomFr();
}
