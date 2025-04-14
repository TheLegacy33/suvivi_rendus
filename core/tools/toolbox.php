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
	 * Fonction permettant d'intégrer des url pour les fichiers CSS
	 * @param array $cssScriptsToInclude
	 * @return void
	 */
	function includeCssScripts(array $cssScriptsToInclude): void{
		foreach ($cssScriptsToInclude as $includedCssScript){
			if (file_exists(PHP_PUBLIC_DIR . $includedCssScript['href'])){
				$ver = hash('sha256', filesize(PHP_PUBLIC_DIR . $includedCssScript['href']));
			}else{
				$ver = '1';
			}
			print('<link rel="stylesheet" href="' . $includedCssScript['href'] . '?ver=' . $ver . '" integrity="' . ($includedCssScript['integrity'] ?? $ver) . '" crossorigin="' . ($includedCssScript['crossorigin'] ?? '') . '" />' . "\r");
		}
	}

	/**
	 * Fonction permettant d'intégrer des url pour les fichiers javascript
	 * @param array $jsScriptsToInclude
	 * @return void
	 */
	function includeJsScripts(array $jsScriptsToInclude): void{
		foreach ($jsScriptsToInclude as $includedJSScript){
			if (file_exists(PHP_PUBLIC_DIR . $includedJSScript)){
				$ver = hash('sha256', filesize(PHP_PUBLIC_DIR . $includedJSScript));
			}else{
				$ver = '1';
			}
			print('<script type="text/javascript" src="' . $includedJSScript . '?ver=' . $ver . '"></script>' . "\r");
		}
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
		$pageDescription = "Student App : tous les rendus des étudiants";
		return $pageDescription;
	}

	/**
	 * Fonctions pour les mots clés des pages
	 */
	function getPageKeywords(): string{
		return '';
	}

	function getPageTitle(): string{
		global $page, $section, $action, $defaultLanguage;
		$baseTitle = 'Student App';
		$pageTitle = '';
		switch ($section){
			case 'index':
				{
					$pageTitle = "Student App : tout les rendus des étudiants";
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