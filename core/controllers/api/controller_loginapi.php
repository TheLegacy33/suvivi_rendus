<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */

	use Random\RandomException;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'checkUserExists':
			{
				$loginToTest = $_POST['identifiant'] ?? '';
				$userExists = false;
				try{
					$userExists = DAOUser::userExists($loginToTest);
					http_response_code(200);
					print(json_encode(['testedLogin' => $loginToTest, 'userExists' => $userExists]));
				}catch (Exception $ex){
					http_response_code(500);
					print(json_encode(['testedLogin' => $loginToTest, 'userExists' => false]));
				}
				break;
			}
		case 'checkActive':
			{
				$loginToTest = $_POST['identifiant'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedLogin' => $loginToTest, 'userActive' => DAOUser::checkActive($loginToTest)]));
				break;
			}
		case 'checkAuth':
			{
				$loginToTest = $_POST['identifiant'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedLogin' => $loginToTest, 'userAuthentified' => DAOUser::checkAuth($loginToTest, $passwordToCheck)]));
				break;
			}
		case 'checkUserPassword':
			{
				$idUser = $_POST['userId'];
				$passwordToCheck = $_POST['password'];
				http_response_code(200);
				print(json_encode(['testedUser' => $idUser, 'passChecked' => DAOUser::checkUserPassword($idUser, $passwordToCheck)]));
				break;
			}
		case 'askForPasswordRenew':
			{
				$mailToProcess = htmlentities($_POST['email'] ?? '');
				try{
					$token = DAOUser::getTokenForNewPassword($mailToProcess);
				}catch (Exception $e){
					$token = null;
				}
				if (($token ?? '') != ''){
					MailToolBox::sendEmailReinitPassword($token, $mailToProcess);

				}
				break;
			}
		case 'renewUserPassword':
			{
				$token = htmlentities($_POST['token'] ?? '');
				$newPwd = $_POST['password'] ?? '';
				if ($token != '' && $newPwd != ''){
					if (BDD::openTransaction()){
						if (DAOUser::renewPassword($token, $newPwd)){
							http_response_code(200);
							print(json_encode(['userUpdated' => true, 'passwordUpdated' => true, 'processedResult' => 'success', 'returnUrl' => getUrl('utilisateur', 'login')]));
							BDD::commitTransaction();
						}else{
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'passwordUpdated' => false, 'processedResult' => 'error', 'erreur' => 'Erreur update']));
							BDD::rollbackTransaction();
						}
					}
				}
				break;
			}
		case 'createUserPassword':
			{
				$token = htmlentities($_POST['token'] ?? '');
				$newPwd = $_POST['password'] ?? '';
				if ($token != '' && $newPwd != ''){
					if (BDD::openTransaction()){
						if (DAOUser::createPassword($token, $newPwd)){
							http_response_code(200);
							print(json_encode(['userUpdated' => true, 'passwordUpdated' => true, 'processedResult' => 'success', 'returnUrl' => getUrl('utilisateur', 'login')]));
							BDD::commitTransaction();
						}else{
							http_response_code(500);
							print(json_encode(['userUpdated' => false, 'passwordUpdated' => false, 'processedResult' => 'error', 'erreur' => 'Erreur update']));
							BDD::rollbackTransaction();
						}
					}
				}
				break;
			}
		case 'isUserLogged':
			{
				http_response_code(200);
				print(json_encode(['userLogged' => Session::getActiveSession()->isUserLogged(), 'userId' => Session::getActiveSession()->getUserId()]));
				break;
			}
		case 'other':
			{
				break;
			}
		default:
			{
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}
