<?php
	/**
	 * @description :
	 *              Fichier de routage pour les url
	 *
	 *              Par défaut :
	 *              url : domain/index.php?section=_section&page=_page&action=_action
	 *
	 *              où :
	 *              _section = nom de la section à charger (défaut : index : page d'accueil)
	 *              _page = page active dans la section correspondante  correspondantes (défaut : main : page d'accueil de la section)
	 *              _action = action à appliquer (défaut : view : visualisation simple)
	 */
	require_once "core/globals.php";
	require_once 'config/config.php';
	require_once 'core/tools/toolbox.php';

	//	ini_set('memory_limit', '4096M');
	$arr_cookie_options = array('lifetime' => 0, 'path' => '/', 'domain' => '', // leading dot for compatibility or use subdomain
		'secure' => true,     // or false
		'httponly' => true,    // or false
		'samesite' => 'None' // None || Lax  || Strict
	);
	session_name(APP_NAME);
	session_set_cookie_params($arr_cookie_options);
	session_start();
	Session::initialise(APP_NAME);
	debug(Session::getActiveSession());
	$userLogged = new User('User', 'Utilisateur');
	$userLogged->setAuthentified(false);
	$artisteLogged = null;
	if (Session::getActiveSession()->isUserLogged()){
		$userLogged = DAOUser::getById(Session::getActiveSession()->getUserId());
		$userLogged->setAuthentified(true);
	}
	/**
	 * Définition des section actives de l'application
	 */
	CoreApplication::initialise();
	//	Session::destroy();
	//	var_dump(password_hash('Artinteractivities1!', PASSWORD_BCRYPT)); // mot de passe luc
	//	var_dump(password_hash('sat@niKm33', PASSWORD_BCRYPT)); // mot de passe michel
	//	var_dump(password_hash('Astrid33@aiCom', PASSWORD_BCRYPT)); // mot de passe astrid
	//	var_dump(password_hash('Theo33@aiCom', PASSWORD_BCRYPT)); // mot de passe theo
	//	var_dump(password_hash('Beatrice33@aiCom', PASSWORD_BCRYPT)); // mot de passe beatrice
	// $secret_key = bin2hex(random_bytes(32));
	// echo "Votre clé secrète est : " . $secret_key;
	$section = $_GET['section'] ?? 'index';
	$page = $_GET['page'] ?? 'main';
	$action = $_GET['action'] ?? 'view';
	/**
	 * Vérification des droits d'accès
	 */
	$pagesNeedAuth = ['index' => [], 'utilisateur' => ['mon-compte', 'mes-coups-de-coeur', 'mes-artistes', 'mes-commandes', 'detail-commande', 'facture', 'detail-livraison-commande', 'probleme-commande', 'evaluer-vendeur', 'commenter-oeuvre', 'retour-oeuvre', 'perso-newsletters', 'perso-notifications', 'messagerie',], 'auth' => [], 'admin' => ['*'], 'api' => []];
	function authNeeded(string $section, string $page, string $action): bool{
		global $pagesNeedAuth;
		if (array_key_exists($section, $pagesNeedAuth)){
			if (in_array($page, $pagesNeedAuth[$section]) || (isset($pagesNeedAuth[$section][0]) and $pagesNeedAuth[$section][0] == '*')){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	function userAuthValid(string $section, string $page, string $action): bool{
		global $userLogged;
		if (authNeeded($section, $page, $action)){
			if ($userLogged->isAuthentified()){
				if ($section == 'admin' && !$userLogged->isAdmin()){
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
		}else{
			return true;
		}
	}

	function getUrl(string $section, string $page = 'main', string $action = 'view', array $otherParams = []): string{
		$tabSections = [
			'auth',
			'index',
			'utilisateur',
			'connexion',
			'admin',
			'api',
			'gestion'
		];
		$url = '/';
		if (!($section == 'index' and $page == 'main' and $action == 'view')){
			if (!in_array($section, $tabSections, true)){
				//				$url .= '?section=index';
				$url .= 'index';
			}else{
				$url .= '?section=' . $section;
				//				$url .= $section;
				if (trim($page) === ''){
					$url .= '&page=main';
					//					$url .= '/main';
				}else{
					$url .= '&page=' . $page;
					//					$url .= '/' . $page;
				}
				if (trim($action) === ''){
					$url .= '&action=view';
					//					$url .= '/view';
				}else{
					$url .= '&action=' . $action;
					//					$url .= '/' . $action;
				}
				if (is_array($otherParams) and !empty($otherParams)){
					foreach ($otherParams as $paramName => $paramValue){
						$url .= '&' . $paramName . '=' . $paramValue;
					}
				}
			}
		}
		return $url;
	}

	if (!DEV_MODE){
		//ini_set('display_errors', 'off');
		error_reporting(E_ERROR & ~E_WARNING & ~E_NOTICE);
		if (!userAuthValid($section, $page, $action)){
			if ($section == 'panier'){
				header('Location:' . getUrl('panier', 'identification'));
			}else if ($section == 'admin' && !$userLogged->isAdmin()){
				header('Location:' . getUrl('index'));
			}else{
				header('Location:' . getUrl('utilisateur', 'connexion-inscription'));
			}
		}
	}else{
		Session::refresh();
	}

	/**
	 * Lien vers le controller qui concerne la destination demandée
	 */
	switch ($section){
		case 'index':
			{
				require_once 'core/controllers/controller_index.php';
				break;
			}
		case 'utilisateur':
			{
				require_once('core/controllers/controller_utilisateur.php');
				break;
			}
		case 'auth':
			{
				require_once 'core/controllers/controller_auth.php';
				break;
			}
		case 'inscription':
			{
				require_once 'core/controllers/controller_inscription.php';
				break;
			}
		case 'admin':
			{
				require_once 'core/controllers/admin/controller_admin.php';
				break;
			}
		case 'api':
			{
				require_once 'core/controllers/api/controller_mainapi.php';
				break;
			}
		default:
			{
				require_once 'core/controllers/controller_error.php';
			}
	}
