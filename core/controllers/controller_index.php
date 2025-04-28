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
				require_once 'core/views/view_index.phtml';
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';
