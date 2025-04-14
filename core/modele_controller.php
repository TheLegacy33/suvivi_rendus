<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 *
	 * Gestion des données
	 */
	require_once 'core/views/template/header.phtml';
	switch ($page){
		case '':{
				require_once 'core/views/';
				break;
			}
		case 'other':{
				break;
			}
		default:{
				require_once 'core/controllers/controller_error.php';
			}
	}
	require_once 'core/views/template/footer.phtml';