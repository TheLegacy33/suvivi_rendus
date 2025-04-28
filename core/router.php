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
	require_once 'config/config.php';
	require_once "core/globals.php";
	require_once 'core/tools/toolbox.php';

	$section = $_GET['section'] ?? 'main';
	$page = $_GET['page'] ?? '';
	$action = $_GET['action'] ?? '';

	$tabSections = ['auth', 'main', 'utilisateur', 'admin', 'api', 'evaluations'];
	if (!in_array($section, $tabSections, true)){
		header('Location:' . getUrl('main'));
	}

	session_name(APP_NAME);
	session_start();
	Session::initialise(APP_NAME);
	CoreApplication::initialise();

	$userLogged = new User('User', 'Utilisateur');
	$userLogged->setAuthentified(false);
	$artisteLogged = null;
	if (Session::getActiveSession()->isUserLogged()){
		$userLogged = DAOUser::getById(Session::getActiveSession()->getUserId());
		$userLogged->setAuthentified(true);
	}
	/**
	 * Vérification des droits d'accès
	 */
	function authNeeded(string $section, string $page): bool{
		$pagesNeedAuth = ['main' => [], 'utilisateur' => ['dashboard', 'files'], 'auth' => [], 'admin' => ['*'], 'api' => [], 'evaluations' => []];
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
		if (authNeeded($section, $page)){
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

	function getUrl(string $section = '', string $page = '', string $action = '', array $otherParams = []): string{
		global $tabSections;
		$url = '/';
		if (!($section == 'main' and $page == 'index' and $action == 'view')){
			if (!in_array($section, $tabSections, true)){
				$url .= 'main';
			}else{
				$url .= $section;
				if (trim($page) !== ''){
					$url .= '/' . $page;
				}
				if (trim($action) !== ''){
					$url .= '/' . $action;
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
			if ($section == 'admin' && !$userLogged->isAdmin()){
				header('Location:' . getUrl('main'));
			}else{
				header('Location:' . getUrl('utilisateur', 'login'));
			}
		}
	}else{
		Session::refresh();
	}
	/**
	 * Lien vers le controller qui concerne la destination demandée
	 */
	switch ($section){
		case 'main':
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
		case 'admin':
			{
				require_once 'core/controllers/admin/controller_admin.php';
				break;
			}
		case 'api':
			{
				switch ($page){
					case 'mainapi':
						{
							require_once 'core/controllers/api/controller_mainapi.php';
							break;
						}
					case 'listapi':
						{
							require_once 'core/controllers/api/controller_listapi.php';
							break;
						}
					case 'tokenapi':
						{
							require_once 'core/controllers/api/controller_tokenapi.php';
							break;
						}
				}
				break;
			}
		case 'evaluations':
			{
				require_once 'core/controllers/controller_evaluations.php';
				break;
			}
		default:
			{
				require_once 'core/controllers/controller_error.php';
			}
	}
