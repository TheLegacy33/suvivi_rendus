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
		case 'getUserFollows':
			{
				$idUser = Session::getActiveSession()->getUserId();
				if ($idUser>0){
					$user = DAOUser::getById($idUser);
					$followList = DAOUser::getFollowList($user);
					http_response_code(200);
					print(json_encode(['userFetched' => true, 'followContent' => $followList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['userFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'addToFollows':
			{
				$idUserSession = Session::getActiveSession()->getUserId();
				$idUserForFollowList = intval($_POST['userId'] ?? 0);
				if ($idUserForFollowList>0 && $idUserSession == $idUserForFollowList){
					$idArtiste = intval($_POST['artisteId'] ?? 0);
					$artiste = DAOArtiste::getById($idArtiste);
					$user = DAOUser::getById($idUserForFollowList);
					$followList = DAOUser::addToFollowList($user, $artiste);
					http_response_code(200);
					print(json_encode(['followListUpdated' => true, 'followContent' => $followList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['followListUpdated' => false, 'result' => 'error']));
				}
				break;
			}
		case 'removeFromUserFollows':
			{
				$idUserSession = Session::getActiveSession()->getUserId();
				$idUserForFollowList = intval($_POST['userId'] ?? 0);
				if ($idUserForFollowList>0 && $idUserSession == $idUserForFollowList){
					$idArtiste = intval($_POST['artisteId'] ?? 0);
					$artiste = DAOArtiste::getById($idArtiste);
					$user = DAOUser::getById($idUserForFollowList);
					$followList = DAOUser::removeFromFollowList($user, $artiste);
					http_response_code(200);
					print(json_encode(['followListUpdated' => true, 'followContent' => $followList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['followListUpdated' => false, 'result' => 'error']));
				}
				break;
			}
		case 'updateUserFollows':
			{
				$idUserSession = Session::getActiveSession()->getUserId();
				$idUserForFollowList = intval($_POST['userId'] ?? 0);
				$followList = json_decode($_POST['followList'] ?? '{}', true);
				if ($idUserForFollowList>0 && $idUserSession == $idUserForFollowList){
					$followList = []; // TODO
					http_response_code(200);
					print(json_encode(['followListUpdated' => true, 'followContent' => $followList, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['followListUpdated' => false, 'result' => 'error']));
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
