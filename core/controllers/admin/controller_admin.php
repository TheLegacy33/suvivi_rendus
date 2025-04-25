<?php
	/**
	 * @var string $page
	 * @var string $section
	 * @var string $action
	 * @var User   $userLogged
	 *
	 * Gestion des données
	 */

	header("Cache-Control: no-cache, must-revalidate");
	$includedCssScripts = [['href' => HTML_PUBLIC_STYLES_DIR . 'final_views_gestion.css']];
	require_once 'core/views/template/header.phtml';
	/**
	 * liens spécifiques :
	 * ?section=admin&page=maintenance&action=regenerer-miniatures => régénération de toutes les miniatures
	 * ?section=admin&page=maintenance&action=regenerer-miniature&id=xxx => régénération des miniatures pour 1 oeuvre
	 * ?section=admin&page=maintenance&action=resample-image => Test resample image (cas Réjane)
	 * ?section=admin&page=maintenance&action=infos-gd => informations concernant les librairies GD et images
	 * ?section=admin&page=maintenance&action=get-php-info => informations concernant les librairies GD et images
	 * ?section=admin&page=maintenance&action=test-email-contact => test de l'envoi d'un mail de contact
	 * ?section=admin&page=comptes-stripe-connect&action={view|create|update|delete|transfer} => tests pour Stripe
	 * ?section=admin&page=maintenance&action=rencrypt-ibans => réencryptage des IBAN
	 * ?section=admin&page=maintenance&action=test-emails => tester les mails
	 */
	switch ($page){
		case 'parametres':
			{
				$includedJSScripts = [HTML_PUBLIC_SCRIPTS_DIR . "js-gestion-parametres.js"];
				$paramShowInfos = DAOParametres::getByLibelle('SHOW_DEBUG');
				if (!is_null($paramShowInfos) and intval($paramShowInfos->getValeur()) == 1){
					debug('DEV_MODE : ' . DEV_MODE);
				}
				$lstParametres = DAOParametres::getAll();
				require_once 'core/views/admin/view_gestion_parametres.phtml';
				break;
			}
		case 'maintenance':
			{
				switch ($action){
					case 'infos-gd':
						{
							debug(gd_info());
							debug('Format PNG : ' . (imagetypes() & IMG_PNG));
							debug('Format AVIF : ' . (imagetypes() & IMG_AVIF));
							debug('Format WebP : ' . (imagetypes() & IMG_WEBP));
							debug('Format JPEG : ' . (imagetypes() & IMG_JPEG));
							break;
						}
					case 'get-php-info':
						{
							phpinfo();
							break;
						}
				}
				break;
			}
		case 'other':
			{
				break;
			}
		default:
			{
				require_once 'core/controllers/controller_error.php';
			}
	}
	require_once 'core/views/template/footer.phtml';
