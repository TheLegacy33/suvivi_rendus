<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données de la page des évaluations
	 */
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case 'files':
			{
				switch ($action){
					case 'view':
						{
							$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-fichiers.js"];
							$lstEcoles = DAOEcoles::getAll();
							require_once 'core/views/view_gestion_fichiers.phtml';
							break;
						}
					default:
						{
							debug($action);
							break;
						}
				}
				break;
			}
		case 'evaluations':
			{
				switch ($action){
					case 'view':
						{
							$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-evaluations.js"];
							$lstEcoles = DAOEcoles::getAll();
							require_once 'core/views/view_gestion_evaluations.phtml';
							break;
						}
					default:
						{
							require_once('core/controllers/controller_error.php');
							break;
						}
				}
				break;
			}
		default:
			{
				require_once('core/controllers/controller_error.php');
			}
	}
	require_once 'core/views/template/footer.phtml';
