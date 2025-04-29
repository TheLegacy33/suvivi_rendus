<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 *
	 * @var User   $userLogged
	 *
	 */

	if ($page == 'lost-password'){
		$referer = $_SERVER['HTTP_REFERER'] ?? '';
		if (DEV_MODE){
			if ($action == 'ask-renew' && $referer != EXTERNAL_URL . getUrl('utilisateur', 'login')){
				header('Location: ' . EXTERNAL_URL . getUrl('utilisateur', 'login'));
			}
		}
	}
	// Inclusion de CSS additionnel
	$includedCssScripts = [];
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case 'login':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . 'js-gestion-login.js'];
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
					print('<script>location.replace("' . getUrl('main') . '"); </script>');
				}else{
					$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-tdb.js"];

					require_once 'core/views/view_tableau_de_bord.phtml';
				}
				break;
			}
		case 'mon-compte':
			{
				if (!isset($userLogged) or $userLogged->getId() == 0){
					print('<script>location.replace("' . getUrl('main') . '"); </script>');
				}else{
					// Si utilisateur acheteur charger vue utilisateur sinon charger vue artiste
					$view = 'core/views/view_error.phtml';
					$includedJSScripts[] = HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-profils.js";
					$profilLogged = DAOUser::getById($userLogged->getId());
					require_once 'core/views/view_utilisateur_mon_compte.phtml';
				}
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';