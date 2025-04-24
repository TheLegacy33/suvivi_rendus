<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données de la page d'accueil
	 */
	require_once 'core/views/template/header.phtml';

	switch ($page){
		case 'index':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-accueil.js"];
				$lstEcoles = DAOEcoles::getAll();
				require_once 'core/views/view_index.phtml';
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';
