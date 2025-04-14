<?php
	/**
	 * // Autochargement des classes pour optimiser les temps d'inclusion des scripts
	 */
	spl_autoload_register(function ($class_name) {
		$directories = [
			'core/tools/',
			'core/models/classes/',
			'core/models/classes/admin/',
			'core/models/DAO/',
			'core/models/DAO/admin/',
			'core/controllers/',
			'core/controllers/admin/',
			'core/controllers/shipping/',
			'core/views/PDF/'
		];

		foreach ($directories as $directory) {
			$file = $directory . $class_name . '.php';
			if (file_exists($file)) {
				require_once $file;
				return;
			}
		}
	});

	/**
	 * Formate un chiffre en montant
	 * @param float  $val
	 * @param string $locale
	 * @param bool   $showDevise
	 * @param string $devise
	 * @return string
	 */
	function formatMontant(float $val, string $locale = 'fr-FR', bool $showDevise = true, string $devise = 'EUR'): string{
		global $defaultLanguage, $defaultCurrency;
		if ($defaultLanguage != $locale){
			$locale = $defaultLanguage;
		}
		if ($defaultCurrency->getDevisesAbrege() != $devise){
			$devise = $defaultCurrency->getDevisesAbrege();
		}
		if ($showDevise){
			$fmt = numfmt_create($locale, NumberFormatter::CURRENCY);
			$fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
			return numfmt_format_currency($fmt, $val, $devise);
		}else{
			$fmt = numfmt_create($locale, NumberFormatter::DECIMAL);
			$fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
			return numfmt_format($fmt, $val);
		}
	}

	/**
	 * Génère un affichage pour le débug
	 * @param mixed $variable
	 * @return void
	 */
	function debug(mixed $variable): void{
		print('<pre>');
		var_dump($variable);
		print('</pre>');
	}

	/**
	 * Génère un tableau contenant toutes les lettres de l'alphabet et un # en premier
	 * @return array
	 */
	function genereTabLettres(): array{
		$retVal[chr(35)] = 0;
		for ($numCar = 65; $numCar<=90; $numCar++){
			$retVal[chr($numCar)] = 0;
		}
		return $retVal;
	}

	/**
	 * Changement mois de Date en lettre
	 *
	 */
	function moisLettres(string $change): false|string{
		ini_set('date.timezone', 'UTC');
		ini_set('intl.default_locale', 'fr_FR');
		$newdate = IntlCalendar::fromDateTime($change, "Europe/Paris");
		return IntlDateFormatter::formatObject($newdate, "d  MMMM y");
	}

	/**
	 * Fonction de vérification d'un code Captcha
	 * @param string      $code
	 * @param string|null $ipClient
	 * @return bool
	 */
	function isValidCaptcha(string $code, string $ipClient = null): bool{
		if (empty($code)){
			return false;
		}
		$params = ['secret' => '6LfBmHgpAAAAAFKbbPGh_Mnz2hfMTkSJa5HLmK1d', 'response' => $code];
		if ($ipClient){
			$params['remoteip'] = $ipClient;
		}
		$url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
		if (function_exists('curl_version')){
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
		}else{
			// Si curl n'est pas dispo, un bon vieux file_get_contents
			$response = file_get_contents($url);
		}
		if (empty($response) || is_null($response)){
			return false;
		}
		$json = json_decode($response);
		return $json->success;
	}

	/**
	 * Fonction de vérification du bon domaine
	 */
	function isValidDomain(): bool{
		$retVal = false;
		if (!isset($_SERVER['HTTP_ORIGIN'])){
			$retVal = true;
		}else{
			switch ($_SERVER['HTTP_ORIGIN']){
				case EXTERNAL_URL:
				case 'https://www.artinteractivities.com':
				case 'https://prod.artinteractivities.com':
				case 'https://dev.artinteractivities.com':
				case 'http://localhost:8081':
				case 'http://192.168.254.74:8081':
				case 'http://192.168.10.103:8081':
					{
						$retVal = true;
					}
			}
		}
		return $retVal;
	}

	/**
	 * Utilisée pour filtrer les oeuvres récentes fonction array_filter
	 * @param Oeuvre $uneOeuvre
	 * @param int    $limit
	 * @return bool
	 */
	function estRecente(Oeuvre $uneOeuvre, int $limit = 0): bool{
		if ($limit == 0)
			$limit = intval(DAOParametres::getByLibelle('limit-new-oeuvre-maxi')->getValeur());
		$interval = $uneOeuvre->getDateUpload()->diff(new DateTime('now'))->days;
		return ($interval<=$limit);
	}

	/**
	 * Savoir si un pays est en europe
	 */
	function estEnEurope(string $codePays): bool{
		return DAOPays::isInUE($codePays);
	}

	/**
	 * Fonction permettant de récupérer la liste de tous les membre de l'union européenne
	 * @return array
	 */
	function getAllEuropeMembers(): array{
		return DAOPays::getAllInUE();
	}

	/**
	 * Fonction permettant d'envoyer un message par mail
	 * @param Message $message
	 * @return bool
	 */
	function sendMessageToMailBoxes(Message $message): bool{
		$mailer = new Mailer();
		try{
			if ($mailer->isDefined()){
				$content = [
					'connexion_link' => getUrl('utilisateur', 'connexion-inscription'),
					'objet' => $message->getObjetMessage()->getLibelle(),
					'contenu' => $message->getContenu()
				];
				$mailer->setFrom(DAOParametres::getByLibelle('senderemail-noreply')->getValeur(), $mailer->getSenderApp());
				$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $mailer->getSenderApp());
				/**
				 * @var User $destinataire
				 */
				foreach ($message->getDestinataires() as $destinataire){
//					$mailer->addAddress($destinataire->getEmail(), $destinataire->getFullName());
					$mailer->addBCC($destinataire->getEmail(), $destinataire->getFullName());
				}
				//Content
				$mailer->isHTML(); // Défini le mail au format HTML
				$mailer->Subject = $mailer->getSenderApp() . ": Vous avez reçu un message";
				$mailer->setVariables([
					'url_image_logo' => 'cid:art_interactivities_logo',
					'senderapp' => $mailer->getSenderApp(),
					'connexion_link' => $content['connexion_link'],
					'sender_object' => $content['objet'],
					'sender_message' => html_entity_decode($content['contenu']),
					'sender_name' => DAOUser::getById($message->getIdExpediteur())->getFullName(),
					'sender_email' => DAOUser::getById($message->getIdExpediteur())->getEmail(),
					'annee' => date('Y'),
					'nom_entreprise' => $mailer->getSenderApp()
				]);
				$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR.'logo.png', 'art_interactivities_logo');

				$mailer->setTemplateHtml('core/views/template/mails/mail_messagerie.phtml');
				$mailer->Body = $mailer->compileHTML();
				$mailer->setTemplateText('core/views/template/mails/mail_messagerie.txt');
				$mailer->AltBody = $mailer->compileText();
				if ($mailer->send()){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}catch (Exception $e){
			debug($e);
			return false;
		}
	}

	/**
	 * Fonction de vérification de l'existence d'une url
	 * @param $url
	 * @return bool
	 */
	function urlExists($url): bool{
		// Vérifier si l'URL est bien formée
		if (!filter_var($url, FILTER_VALIDATE_URL)){
			return false;
		}
		// Obtenir les en-têtes de l'URL
		$headers = @get_headers($url);
		// Vérifier si la réponse est valide
		//		if($headers && strpos($headers[0], '200')) {
		if ($headers && !strpos($headers[0], '404')){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param LocaleTrad $defaultLocale
	 * @param Devises    $defaultCurrency
	 * @param Unite      $defaultUnit
	 * @return void
	 */
	function setDefaultCookies(LocaleTrad $defaultLocale, Devises $defaultCurrency, Unite $defaultUnit): void{
		$arr_cookie_options = array (
			'expires' => time() + (86400 * 3650),
			'path' => '/',
			'domain' => '', // leading dot for compatibility or use subdomain
			'secure' => true,     // or false
			'httponly' => true,    // or false
			'samesite' => 'None' // None || Lax  || Strict
		);

		setcookie('userLocale', json_encode($defaultLocale), $arr_cookie_options);
		setcookie('userLanguage', $defaultLocale->getLibelle(), $arr_cookie_options);
		setcookie('userCurrency', json_encode($defaultCurrency), $arr_cookie_options);
		setcookie('userUnit', json_encode($defaultUnit), $arr_cookie_options);
	}

	/**
	 * Fonction de sauvegarde d'un document base 64 transmis par DHLApi
	 * @param string $base64string
	 * @param string $file_name
	 * @param string $folder
	 * @return false|int
	 */
	function uploadFileFromBlobString(string $base64string = '', string $file_name = '', string $folder = ''): false|int{

		$file_path = "";
		$result = 0;

		// Convert blob (base64 string) back to PDF
		if (!empty($base64string)) {

			// Detects if there is base64 encoding header in the string.
			// If so, it needs to be removed prior to saving the content to a phisical file.
			if (str_contains($base64string, ',')) {
				@list($encode, $base64string) = explode(',', $base64string);
			}

			$base64data = base64_decode($base64string, true);
			$file_path  = "{$folder}/{$file_name}";

			// Return the number of bytes saved, or false on failure
			$result = file_put_contents("{$file_path}", $base64data);
			@chmod("{$file_path}", 0777);

		}

		return $result;
	}

	/**
	 * Fonction de recherche d'une valeur dans un tableau
	 */
	function my_in_array(mixed $needle, array $haystack, bool $strict = false): bool{
		$newArray = array_flip($haystack);
		return isset($newArray[$needle]);
	}

	/**
	 * Fonctions pour le titre et la description des pages
	 */
	function getPageDescription(): string{
		global $page, $section, $action, $defaultLanguage;
		$pageDescription = "ART INTERACTIVITIES est une place de marché internationale qui présente de l'art original et de qualité, sélectionné par des experts amoureux de l'art.";
		switch ($section){
			case 'index':{
				switch ($page){
					case 'adn':{
						$pageDescription = "ART INTERACTIVITIES : Notre A.D.N., notre équipe et nos valeurs, tout ce qui nous identifie à l'Art";
						break;
					}
					case 'concept':{
						$pageDescription = "ART INTERACTIVITIES : un nom, un logo, notre identité, découvre notre concept";
						break;
					}
					case 'mentions-legal':{
						$pageDescription = "ART INTERACTIVITIES : Nos mentions légales";
						break;
					}
					case 'cgu':{
						$pageDescription = "ART INTERACTIVITIES : Les conditions générales d'utilisation de notre place de marché internationale";
						break;
					}
					case 'cgv':{
						$pageDescription = "ART INTERACTIVITIES : Les conditions générale de ventes pour que nos artistes mettent leurs oeuvres à votre disposition";
						break;
					}
					case 'rgpd':{
						$pageDescription = "ART INTERACTIVITIES : Les conditions générale d'utilisation et protection de vos données personnelles'";
						break;
					}
				}
				break;
			}
			case 'recherche':{
				$pageDescription = "ART INTERACTIVITIES : Recherchez l'artiste ou l'oeuvre qui saura vous combler";
				break;
			}
			case 'e-carte-cadeaux':{
				$pageDescription = "ART INTERACTIVITIES : Envie de faire plaisir, effrez une carte cadeau valable 12 mois sur l'intégralité de notre place de marché";
				break;
			}
			case 'messagerie':{
				switch ($page){
					case 'contact-us':{
						$pageDescription = "ART INTERACTIVITIES : Contactez-nous ou consultez notre FAQ, notre équipe vous répond";
						break;
					}
				}
				break;
			}
			case 'faq':{
				$pageDescription = "ART INTERACTIVITIES : Trouvez les réponses à vos questions";
				break;
			}
			case 'utilisateur':{
				switch ($page){
					case 'mes-coups-de-coeur':{
						$pageDescription = "ART INTERACTIVITIES : Vos coups de coeur pour votre collection";
						break;
					}
					case 'connexion-inscription':{
						$pageDescription = "ART INTERACTIVITIES : Connectez-vous ou inscrivez-vous";
						break;
					}
				}
				break;
			}
			case 'panier':{
				$pageDescription = "ART INTERACTIVITIES : Sélectionnez et commandez les oeuvres qui vous combleront chaque jour";
				break;
			}
			case 'inscription':{
				switch ($page){
					case 'artiste':{
						$pageDescription = "ART INTERACTIVITIES : Inscrivez-vous pour commencer à déposer vos oeuvres et les exposer à la vente à travers le monde entier";
						break;
					}

					case 'amateur-art':{
						$pageDescription = "ART INTERACTIVITIES : Inscrivez-vous pour commencer à explorer les oeuvres sélectionnées à travers le monde entier";
						break;
					}
					default:{
						$pageDescription = "ART INTERACTIVITIES : Inscrivez-vous, explorez et bénéficiez de nos services pour acheter ou vendre des oeuvres d'Art";
					}
				}
				break;
			}
			case 'artistes':{
				switch ($page){
					case 'galerie':{
						$pageDescription = "ART INTERACTIVITIES : Découvrez tous nos artistes et leurs réalisations dans leurs domaines artisitiques";
						break;
					}

					case 'profil':{
						$pageDescription = 'Apprenez en plus sur cet artiste';
						switch ($action){
							case 'view':{
								if (isset($_GET['id'])){
									$artiste = DAOArtiste::getById($_GET['id']);
									if ($artiste !== false){
										if ($artiste->afficherPseudo()){
											$pageDescription .= ' : '.$artiste->getPseudo();
										}else{
											$pageDescription.= ' : '.$artiste->getPrenom().' '.$artiste->getNom();
										}

										if (count($artiste->getMotsClefs()) > 0){
											$pageDescription .= ' : ';
											foreach ($artiste->getMotsClefs() as $motsClef){
												$pageDescription .= $motsClef->getLibelle().', ';
											}
											$pageDescription = substr($pageDescription, 0, strlen($pageDescription) - 2);
										}
										$pageDescription .= ', sa biographie, son histoire, son atelier, ses oeuvres et séries';

										if (!is_null($artiste->getNationalite())){
											$pageDescription .= ' - '.(str_starts_with($defaultLanguage, 'fr') ? $artiste->getNationalite()->getNomFr() : $artiste->getNationalite()->getNomEn());
										}
									}
								}
								break;
							}
						}
						$pageDescription .= ' - ART INTERACTIVITIES';
					}

					case 'oeuvres':{
						switch ($action){
							case 'view':{
								if (isset($_GET['idartiste'])){
									$artiste = DAOArtiste::getById($_GET['idartiste']);
									if ($artiste !== false){
										if ($artiste->afficherPseudo()){
											$pageDescription = $artiste->getPseudo();
										}else{
											$pageDescription = $artiste->getPrenom().' '.$artiste->getNom();
										}

										$pageDescription .= ' : Découvrez toutes ses oeuvres';
									}
								}
								break;
							}
						}
						break;
					}
				}
				break;
			}
			case 'oeuvres':{
				switch ($page){
					case 'presentation':{
						switch ($action){
							case 'view':{
								if (isset($_GET['id'])){
									$oeuvre = DAOOeuvre::getById($_GET['id']);
									$artiste = DAOArtiste::getById($oeuvre->getIdArtiste());
									$pageDescription = $oeuvre->getTitre(true);
									if ($artiste !== false){
										$pageDescription .= ' de ';
										if ($artiste->afficherPseudo()){
											$pageDescription .= $artiste->getPseudo();
										}else{
											$pageDescription .= $artiste->getPrenom().' '.$artiste->getNom();
										}
									}
									$pageDescription .= ' ('.$oeuvre->getAnneeCreation().')';
									$pageDescription .= ' : ';
									if (count($oeuvre->getMediums()) > 0){
										foreach ($oeuvre->getMediums() as $medium){
											$pageDescription .= $medium->getLibelle().', ';
										}
									}

									if (count($oeuvre->getThemes()) > 0){
										foreach ($oeuvre->getThemes() as $theme){
											$pageDescription .= $theme->getLibelle().', ';
										}
									}

									if (count($oeuvre->getTechniquesFille()) > 0){
										foreach ($oeuvre->getTechniquesFille() as $technique){
											$pageDescription .= $technique->getLibelle().', ';
										}
									}
									$pageDescription = substr($pageDescription, 0, strlen($pageDescription) - 2);
								}
								break;
							}
						}
						break;
					}
					case 'galerie':{
						$titleFiltre = '';
						if (isset($_GET['categorie'])){
							$titleFiltre = 'Explorez la catégorie '.$_GET['categorie'].', ';
						}elseif (isset($_GET['style'])){
							$titleFiltre = 'Explorez le style '.$_GET['style'].', ';
						}elseif (isset($_GET['theme'])){
							$titleFiltre = 'Explorez le thème '.$_GET['theme'].', ';
						}
						$pageDescription = 'ART INTERACTIVITIES : '.$titleFiltre.($titleFiltre == '' ? 'P' : 'p').'arcourez nos collections, choisissez vos coups de coeurs, sélectionnez vos artistes favoris, commandez vos oeuvres';
						break;
					}
					default:{
						$pageDescription = 'ART INTERACTIVITIES : Parcourez nos collections, choisissez vos coups de coeurs, sélectionnez vos artistes favoris, commandez vos oeuvres';
					}
				}
				break;
			}
			case 'culture':{
				switch ($page){
					case 'articles':
						{
							$pageDescription = 'ART INTERACTIVITIES : Consultez notre blog et découvrez les articles sur la culture';
							break;
						}
				}
				break;
			}

		}

		return $pageDescription;
	}

	/**
	 * Fonctions pour les mots clés des pages
	 */
	function getPageKeywords(): string{
		global $page, $section, $action, $defaultLanguage;
		return $meta ?? ($nomArtiste ?? '')."artistes, artistes célèbres, artistes célèbres, acheter de l'art en ligne, art abordable, art original, art, artistes, art contemporain, oeuvres, sculpture";
	}

	function getPageTitle(): string{
		global $page, $section, $action, $defaultLanguage;
		$baseTitle = 'ART INTERACTIVITIES';
		$pageTitle = '';
		switch ($section){
			case 'index':{
				switch ($page){
					case 'adn':{
						$baseTitle = "ART INTERACTIVITIES : Notre A.D.N., notre équipe et nos valeurs";
						break;
					}
					case 'concept':{
						$baseTitle = "ART INTERACTIVITIES : un nom, un logo, notre identité, notre concept";
						break;
					}
					case 'mentions-legal':{
						$baseTitle = "ART INTERACTIVITIES : Nos mentions légales";
						break;
					}
					case 'cgu':{
						$baseTitle = "ART INTERACTIVITIES : Les conditions générales d'utilisation";
						break;
					}
					case 'cgv':{
						$baseTitle = "ART INTERACTIVITIES : Les conditions générale de ventes";
						break;
					}
					case 'rgpd':{
						$baseTitle = "ART INTERACTIVITIES : L'utilisation et la protection de vos données personnelles";
						break;
					}
					default:{
						$baseTitle = "ART INTERACTIVITIES : Votre place de marché internationale d'art en ligne";
					}
				}
				break;
			}
			case 'recherche':{
				$baseTitle = "ART INTERACTIVITIES : Cherchez l'artiste ou l'oeuvre qui vous ressemble";
				break;
			}
			case 'e-carte-cadeaux':{
				$baseTitle = "ART INTERACTIVITIES : Envie de faire plaisir, offrez une carte cadeau";
				break;
			}
			case 'messagerie':{
				switch ($page){
					case 'contact-us':{
						$baseTitle = "ART INTERACTIVITIES : Contactez-nous ou consultez notre FAQ, notre équipe vous répond";
						break;
					}
				}
				break;
			}
			case 'faq':{
				$baseTitle = "ART INTERACTIVITIES : Trouvez les réponses à vos questions";
				break;
			}
			case 'utilisateur':{
				switch ($page){
					case 'mes-coups-de-coeur':{
						$baseTitle = "ART INTERACTIVITIES : Vos coups de coeur pour votre collection";
						break;
					}
					case 'connexion-inscription':{
						$baseTitle = "ART INTERACTIVITIES : Connectez-vous ou inscrivez-vous";
						break;
					}
				}
				break;
			}
			case 'panier':{
				$baseTitle = "ART INTERACTIVITIES : Sélectionnez et commandez vos oeuvres d'art";
				break;
			}
			case 'inscription':{
				switch ($page){
					case 'artiste':{
						$baseTitle = "ART INTERACTIVITIES : Inscrivez-vous pour commencer à déposer vos oeuvres et les exposer à la vente à travers le monde entier";
						break;
					}

					case 'amateur-art':{
						$baseTitle = "ART INTERACTIVITIES : Inscrivez-vous pour commencer à explorer les oeuvres sélectionnées à travers le monde entier";
						break;
					}
					default:{
						$baseTitle = "ART INTERACTIVITIES : Inscrivez-vous, explorez et bénéficiez de nos services pour acheter ou vendre des oeuvres d'Art";
					}
				}
				break;
			}
			case 'artistes':{
				switch ($page){
					case 'galerie':{
						$pageTitle = "ART INTERACTIVITIES : Découvrez tous nos artistes et leurs réalisations dans leurs domaines artisitiques";
						break;
					}

					case 'profil':{
						$pageTitle = 'Découvrez cet artiste';

						switch ($action){
							case 'view':{
								if (isset($_GET['id'])){
									$artiste = DAOArtiste::getById($_GET['id']);
									if ($artiste !== false){
										if ($artiste->afficherPseudo()){
											$pageTitle = $artiste->getPseudo();
										}else{
											$pageTitle = $artiste->getPrenom().' '.$artiste->getNom();
										}

										if (count($artiste->getMotsClefs()) > 0){
											$pageTitle .= ' : ';
											foreach ($artiste->getMotsClefs() as $motsClef){
												$pageTitle .= $motsClef->getLibelle().', ';
											}
											$pageTitle = substr($pageTitle, 0, strlen($pageTitle) - 2);
										}
										$pageTitle .= ', son histoire, sa biographie et ses oeuvres';

										if (!is_null($artiste->getNationalite())){
											$pageTitle .= ' - '.(str_starts_with($defaultLanguage, 'fr') ? $artiste->getNationalite()->getNomFr() : $artiste->getNationalite()->getNomEn());
										}
									}
								}
								break;
							}
						}
						$pageTitle .= ' - ART INTERACTIVITIES';
					}

					case 'oeuvres':{
						switch ($action){
							case 'view':{
								if (isset($_GET['idartiste'])){
									$artiste = DAOArtiste::getById($_GET['idartiste']);
									if ($artiste !== false){
										if ($artiste->afficherPseudo()){
											$pageTitle = $artiste->getPseudo();
										}else{
											$pageTitle = $artiste->getPrenom().' '.$artiste->getNom();
										}

										$pageTitle .= ' : Découvrez toutes ses oeuvres';
									}
								}
								break;
							}
						}
						break;
					}
				}
				break;
			}
			case 'oeuvres':{
				switch ($page){
					case 'presentation':{
						switch ($action){
							case 'view':{
								if (isset($_GET['id'])){
									$oeuvre = DAOOeuvre::getById($_GET['id']);
									$artiste = DAOArtiste::getById($oeuvre->getIdArtiste());
									$pageTitle = $oeuvre->getTitre(true);
									if ($artiste !== false){
										$pageTitle .= ' de ';
										if ($artiste->afficherPseudo()){
											$pageTitle .= $artiste->getPseudo();
										}else{
											$pageTitle .= $artiste->getPrenom().' '.$artiste->getNom();
										}
									}
									$pageTitle .= ' ('.$oeuvre->getAnneeCreation().')';
									$pageTitle .= ' : ';
									if (count($oeuvre->getMediums()) > 0){
										foreach ($oeuvre->getMediums() as $medium){
											$pageTitle .= $medium->getLibelle().', ';
										}
									}

									if (count($oeuvre->getThemes()) > 0){
										foreach ($oeuvre->getThemes() as $theme){
											$pageTitle .= $theme->getLibelle().', ';
										}
									}

									if (count($oeuvre->getTechniquesFille()) > 0){
										foreach ($oeuvre->getTechniquesFille() as $technique){
											$pageTitle .= $technique->getLibelle().', ';
										}
									}
									$pageTitle = substr($pageTitle, 0, strlen($pageTitle) - 2);
								}
								break;
							}
						}
						break;
					}
					case 'galerie':{
						$titleFiltre = '';
						if (isset($_GET['categorie'])){
							$titleFiltre = 'Explorez la catégorie '.$_GET['categorie'].', ';
						}elseif (isset($_GET['style'])){
							$titleFiltre = 'Explorez le style '.$_GET['style'].', ';
						}elseif (isset($_GET['theme'])){
							$titleFiltre = 'Explorez le thème '.$_GET['theme'].', ';
						}
						$pageTitle = 'ART INTERACTIVITIES : '.$titleFiltre.($titleFiltre == '' ? 'P' : 'p').'arcourez nos collections, choisissez vos coups de coeurs, sélectionnez vos artistes favoris, commandez vos oeuvres';
						break;
					}
					default:{
						$baseTitle = 'ART INTERACTIVITIES : Explorez et commandez vos oeuvres favorites';
					}
				}
				break;
			}
			case 'culture':{
				switch ($page){
					case 'articles':
						{
							$baseTitle = "ART INTERACTIVITIES : Consultez notre blog et découvrez l'art";
							break;
						}
				}
				break;
			}

		}

		return ($pageTitle == '' ? $baseTitle : ($pageTitle.' - '.$baseTitle));
	}

	function decrypt_data($data): bool|string{
		if (!is_null(DAOParametres::getByLibelle('INTERNAL_SKEY'))){
			$key = DAOParametres::getByLibelle('INTERNAL_SKEY')->getValeur();
			$data = base64_decode($data);
			$iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
			return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
		}else{
			return false;
		}
	}

	function encrypt_data($data): string {
		if (!is_null(DAOParametres::getByLibelle('INTERNAL_SKEY'))){
			$key = DAOParametres::getByLibelle('INTERNAL_SKEY')->getValeur();
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
			return base64_encode($iv.$encrypted);
		}else{
			return $data;
		}
	}