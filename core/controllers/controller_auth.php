<?php
	/**
	 * @var User   $unUser
	 * @var string $action
	 */
	switch ($action){
		case 'login':
			{
				$location = getUrl('utilisateur', 'login');
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
							$location = getUrl('main');
						}else{
							$location = getUrl('utilisateur', 'login');
						}
					}else{
						$location = getUrl('utilisateur', 'login');
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
				header('Location: ' . getUrl('main'));
				break;
			}
		case 'resetpassword':
			{
				break;
			}
		default:
			{
				header('Location: ' . getUrl('main'));
			}
	}