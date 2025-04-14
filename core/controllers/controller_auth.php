<?php
	/**
	 * @var User   $unUser
	 * @var string $action
	 * @var bool   $enableActions
	 */

	switch ($action){
		case 'login':
			{
				$location = getUrl('utilisateur', 'connexion-inscription');
				if (!empty($_POST)){
					$loginSaisi = $_POST['chIdentifiant'] ?? '';
					$mdpSaisi = $_POST['chPassword'] ?? '';
					$origine = $_GET['referer'] ?? '';
					if (DAOUser::userExists($loginSaisi)){
						if (DAOUser::checkAuth($loginSaisi, $mdpSaisi)){
							$idUser = DAOUser::getIdByLogin($loginSaisi);
							$userLogged = DAOUser::getById($idUser);
							Session::getActiveSession()->setUserId($idUser);
							DAOUser::updateLastLogin($userLogged);
							DAOUser::logUserAction(DAOUser::getById($idUser), 'connexion');
							// Si l'utilisateur a un panier local je dois le mettre dans le panier en bdd
							// Si l'utilisateur a déjà un panier en bdd je dois ajouter ce qu'il y a dans le local si ca n'y est pas déjà
							$panierLocal = null;
							$panierDistant = null;
							$transactionOK = true;
							if (BDD::openTransaction()){
								if (isset($_COOKIE['panier']) and $_COOKIE['panier'] != '' and $_COOKIE['panier'] != '[]'){
									$panierLocal = json_decode($_COOKIE['panier']);
									if (!is_null($panierLocal)){
										$panierDistant = DAOPanier::getByUniqueId($panierLocal->id);
										if (!is_null($panierDistant)){
											if ($panierDistant->getStatutPanier()->getId() == DAOStatutPanier::getByLibelle('Temporaire')->getId() or $panierDistant->getStatutPanier()->getId() == DAOStatutPanier::getByLibelle('Actif')->getId()){
												// Je bascule le panier temporaire en actif sur le user loggé
												$panierDistant->setLignes(DAOPanier::getLignesByIdPanier($panierDistant->getId()));
												$panierDistant->setUser($userLogged);
												$panierDistant->setStatutPanier(DAOStatutPanier::getByLibelle('Actif'));
												if (!DAOPanier::updateUserForCart($panierDistant)){
													$transactionOK = false;
												}
											}else{
											}
										}else{
											//Je dois créer le panier distant
										}
									}
								}
								if ($transactionOK){
									BDD::commitTransaction();
									//Je supprime le panier local
									//unset($_COOKIE['panier']);
									//setcookie('panier', '', time() - 3600, '/');
								}else{
									BDD::rollbackTransaction();
								}
							}
							if ($origine == 'panier'){
								$location = getUrl('panier', 'adresse');
							}else{
								$location = getUrl('index');
							}
						}else{
							if ($origine == 'panier'){
								$location = getUrl('panier', 'identification');
							}else{
								$location = getUrl('utilisateur', 'connexion-inscription');
							}
						}
					}else{
						if ($origine == 'panier'){
							$location = getUrl('panier', 'identification');
						}else{
							$location = getUrl('utilisateur', 'connexion-inscription');
						}
					}
				}
				header('Location: ' . $location);
				break;
			}
		case 'logout':
			{
				if (Session::getActiveSession()->isUserLogged()){
					Session::destroy();
				}
				if (isset($_COOKIE['panier'])){
					unset($_COOKIE['panier']);
					setcookie('panier', '', time() - 3600, '/');
				}
				header('Location: ' . getUrl('index'));
				break;
			}
		case 'resetpassword':
			{
				break;
			}
		default:
			{
				header('Location: ' . getUrl('index'));
			}
	}