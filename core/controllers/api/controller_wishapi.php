<?php
	/**
	 * @var string $section
	 * @var string $action
	 *
	 * Controller pour les appels API
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	switch ($action){
		case 'getUserWishes':
			{
				$idUser = Session::getActiveSession()->getUserId();
				if ($idUser>0){
					$user = DAOUser::getById($idUser);
					$wishList = DAOListeSouhait::getAllOeuvresByUserId($idUser);
					http_response_code(200);
					print(json_encode(['userFetched' => true, 'wishesContent' => $wishList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['userFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'addToWishes':
			{
				$idUserSession = Session::getActiveSession()->getUserId();
				$idUserForWishList = intval($_POST['userId'] ?? 0);
				if ($idUserForWishList>0 && $idUserSession == $idUserForWishList){
					$idOeuvre = intval($_POST['oeuvreId'] ?? 0);
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					$user = DAOUser::getById($idUserForWishList);
					$wishList = DAOUser::addToWishList($user, $oeuvre);
					http_response_code(200);
					print(json_encode(['wishListUpdated' => true, 'wishesContent' => $wishList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['wishListUpdated' => false, 'result' => 'error']));
				}
				break;
			}
		case 'delFromWishes':
			{
				$idUserSession = Session::getActiveSession()->getUserId();
				$idUserForWishList = intval($_POST['userId'] ?? 0);
				if ($idUserForWishList>0 && $idUserSession == $idUserForWishList){
					$idOeuvre = intval($_POST['oeuvreId'] ?? 0);
					$oeuvre = DAOOeuvre::getById($idOeuvre);
					$user = DAOUser::getById($idUserForWishList);
					$wishList = DAOUser::removeFromWishList($user, $oeuvre);
					http_response_code(200);
					print(json_encode(['wishListUpdated' => true, 'wishesContent' => $wishList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['wishListUpdated' => false, 'result' => 'error']));
				}
				break;
			}
		case 'addCollectionCoupCoeur':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['chActionForm'] ?? '');
					$idCollection = intval($_POST['chIdCollection'] ?? 0);
					$nomCollection = htmlentities($_POST['chNomCollection'] ?? '');
					$description = htmlentities($_POST['chDescriptionCollection'] ?? '');
					$userLogged = Session::getActiveSession()->isUserLogged();
					if ($userLogged && $actionForm == 'add' && intval($idCollection) == 0){
						$newListe = new ListeSouhait($nomCollection, default: false, description: $description);
						if (DAOListeSouhait::insert($newListe, DAOUser::getById(Session::getActiveSession()->getUserId()))){
							http_response_code(200);
							print(json_encode(['collectionCreated' => true, 'result' => 'success']));
						}else{
							http_response_code(500);
							print(json_encode(['collectionCreated' => false, 'result' => 'error']));
						}
					}else{
						http_response_code(500);
						print(json_encode(['collectionCreated' => false, 'result' => 'error']));
					}
				}
				break;
			}
		case 'editCollectionCoupCoeur':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['chActionForm'] ?? '');
					$idCollection = intval($_POST['chIdCollection'] ?? 0);
					$nomCollection = htmlentities($_POST['chNomCollection'] ?? '');
					$description = htmlentities($_POST['chDescriptionCollection'] ?? '');
					$userLogged = Session::getActiveSession()->isUserLogged();
					if ($userLogged && $actionForm == 'edit' && intval($idCollection) != 0){
						$listeAUpdater = DAOListeSouhait::getById($idCollection, DAOUser::getById(Session::getActiveSession()->getUserId()));
						$updateToDo = false;
						if ($nomCollection != $listeAUpdater->getNom()){
							$listeAUpdater->setNom($nomCollection);
							$updateToDo = true;
						}
						if ($description != $listeAUpdater->getDescription()){
							$listeAUpdater->setDescription($description);
							$updateToDo = true;
						}
						if ($updateToDo){
							if (DAOListeSouhait::update($listeAUpdater, DAOUser::getById(Session::getActiveSession()->getUserId()))){
								http_response_code(200);
								print(json_encode(['collectionUpdate' => true, 'result' => 'success']));
							}else{
								http_response_code(500);
								print(json_encode(['collectionUpdate' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(200);
							print(json_encode(['collectionUpdate' => false, 'result' => 'success']));
						}
					}else{
						http_response_code(500);
						print(json_encode(['collectionUpdate' => false, 'result' => 'error']));
					}
				}
				break;
			}
		case 'delCollectionCoupCoeur':
			{
				if (!empty($_POST)){
					$actionForm = htmlentities($_POST['action'] ?? '');
					$idCollection = intval($_POST['idcollection'] ?? 0);
					$userLogged = Session::getActiveSession()->isUserLogged();
					if ($userLogged && $actionForm == 'del' && intval($idCollection) != 0){
						$serieASupprimer = DAOListeSouhait::getById($idCollection, DAOUser::getById(Session::getActiveSession()->getUserId()));
						if ($serieASupprimer->getId() != 0){
							if (DAOListeSouhait::delete($serieASupprimer, DAOUser::getById(Session::getActiveSession()->getUserId()))){
								http_response_code(200);
								print(json_encode(['collectionDeleted' => true, 'result' => 'success']));
							}else{
								http_response_code(500);
								print(json_encode(['collectionDeleted' => false, 'result' => 'error']));
							}
						}else{
							http_response_code(500);
							print(json_encode(['collectionDeleted' => false, 'result' => 'error']));
						}
					}else{
						http_response_code(500);
						print(json_encode(['collectionDeleted' => false, 'result' => 'error']));
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
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}
