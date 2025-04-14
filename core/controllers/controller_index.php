<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données de la page d'accueil
	 */
	// Pour le cache control
	header("Cache-Control: no-cache, must-revalidate");
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case 'main':
			{
				debug('Accueil');
				$includedJSScripts = [
					HTML_PUBLIC_SCRIPTS_DIR . 'connexion-inscription.js'
				];
				require_once 'core/views/view_index.phtml';
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';
