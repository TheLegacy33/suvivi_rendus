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
		case 'updatemessagestatus':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				$idmessage = intval($_GET['idmessage'] ?? 0);
				$idstatut = intval(intval($_GET['idstatut'] ?? 2));
				if ($iduser>0 and $idmessage>0){
					$message = DAOMessagerie::getMessageForUser($iduser, $idmessage);
					// Je mets à jour le statut de lecture
					$message->setStatutMessageDestinataire(DAOStatutMessageDestinataire::getById($idstatut));
					DAOMessagerie::updateStatutDestinataire($message);
					http_response_code(200);
					print(json_encode(['statusUpdated' => true, 'messageUpdated' => $message, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['statusUpdated' => false, 'result' => 'error']));
				}
				break;
			}
		case 'getmessagecontent':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				$idmessage = intval($_GET['idmessage'] ?? 0);
				if ($iduser>0 and $idmessage>0){
					$message = DAOMessagerie::getMessageForUser($iduser, $idmessage);
					// Je mets à jour le statut de lecture
					$message->setStatutMessageDestinataire(DAOStatutMessageDestinataire::getById(2));
					DAOMessagerie::updateStatutDestinataire($message);
					http_response_code(200);
					print(json_encode(['messageFetched' => $message, 'result' => 'success', 'imgfleche' => HTML_PUBLIC_IMAGES_DIR . 'new_img/arrow_long.png']));
				}else{
					http_response_code(500);
					print(json_encode(['messageFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'delmessagedestinataire':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				$idmessage = intval($_GET['idmessage'] ?? 0);
				if ($iduser>0 and $idmessage>0){
					$message = DAOMessagerie::delMessageFromDestinataire($iduser, $idmessage);
					http_response_code(200);
					print(json_encode(['messageDeleted' => true, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['messageDeleted' => false, 'result' => 'error']));
				}
				break;
			}
		case 'delmessageexpediteur':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				$idmessage = intval($_GET['idmessage'] ?? 0);
				if ($iduser>0 and $idmessage>0){
					$message = DAOMessagerie::delMessageFromExpediteur($iduser, $idmessage);
					http_response_code(200);
					print(json_encode(['messageDeleted' => true, 'result' => 'success']));
				}else{
					http_response_code(500);
					print(json_encode(['messageDeleted' => false, 'result' => 'error']));
				}
				break;
			}
		case 'getsentmessagecontent':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				$idmessage = intval($_GET['idmessage'] ?? 0);
				if ($iduser>0 and $idmessage>0){
					$message = DAOMessagerie::getSentMessageForUser($iduser, $idmessage);
					http_response_code(200);
					print(json_encode(['messageFetched' => $message, 'result' => 'success', 'imgfleche' => HTML_PUBLIC_IMAGES_DIR . 'new_img/arrow_long.png']));
				}else{
					http_response_code(500);
					print(json_encode(['messageFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'getmessagesforuser':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				if ($iduser>0){
					$messages = DAOMessagerie::getReceivedMessagesForUser($iduser);
					http_response_code(200);
					print(json_encode(['messagesFetched' => $messages, 'result' => 'success', 'imgfleche' => HTML_PUBLIC_IMAGES_DIR . 'new_img/arrow_long.png']));
				}else{
					http_response_code(500);
					print(json_encode(['messagesFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'getsentmessagesforuser':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				if ($iduser>0){
					$messages = DAOMessagerie::getSentMessagesForUser($iduser);
					http_response_code(200);
					print(json_encode(['messagesFetched' => $messages, 'result' => 'success', 'imgfleche' => HTML_PUBLIC_IMAGES_DIR . 'new_img/arrow_long.png']));
				}else{
					http_response_code(500);
					print(json_encode(['messagesFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'getnewsmessagesforuser':
			{
				$iduser = intval($_GET['iduser'] ?? 0);
				if ($iduser>0){
					$messages = DAOMessagerie::getNewsMessagesForUser($iduser);
					http_response_code(200);
					print(json_encode(['messagesFetched' => $messages, 'result' => 'success', 'imgfleche' => HTML_PUBLIC_IMAGES_DIR . 'new_img/arrow_long.png']));
				}else{
					http_response_code(500);
					print(json_encode(['messagesFetched' => false, 'result' => 'error']));
				}
				break;
			}
		case 'sendmessagetoai':
			{
				$idexpediteur = intval($_POST['idexpediteur'] ?? 0);
				$objet = $_POST['objet'] ?? 'Autres';
				$contenu = htmlentities($_POST['contenu'] ?? '');
				if ($idexpediteur>0 and $contenu != ''){
					$message = new Message($contenu, $objet);
					$message->setIdExpediteur($idexpediteur);
					$message->setDestinataires(DAOMessagerie::getAIUsers());
					$message->setDateEnvoi(date_create('now'));
					$message->setCategorieMessage(DAOCategorieMessage::getById(1));
					$message->setStatutMessageExpediteur(DAOStatutMessageExpediteur::getById(1));
					$message->setObjetMessage(DAOObjetMessage::getByLibelle($objet));
					if (BDD::openTransaction()){
						if (DAOMessagerie::insert($message)){
							http_response_code(200);
							print(json_encode(['messageSent' => true, 'result' => 'success']));
							BDD::commitTransaction();
							@MailToolBox::sendMessageToMailBoxes($message);
						}else{
							http_response_code(500);
							print(json_encode(['messageSent' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					}else{
						http_response_code(500);
						print(json_encode(['messageSent' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}else{
					http_response_code(500);
					print(json_encode(['messageSent' => false, 'result' => 'error']));
				}
				break;
			}
		case 'sendmessage':
			{
				$idexpediteur = intval($_POST['idexpediteur'] ?? 0);
				$objet = $_POST['objet'] ?? 'Autres';
				$contenu = htmlentities($_POST['contenu'] ?? '');
				$destinataire = htmlentities(trim($_POST['destinataire']) ?? '');
				$categorie = intval($_POST['categorie'] ?? 1);
				if (!str_starts_with($destinataire, '*') and intval($destinataire)>0){
					$destinataires[] = DAOMessagerie::getUserInfos(intval($destinataire));
				}else{
					if ($destinataire == '*artistes'){
						$destinataires = DAOMessagerie::getAllArtistes();
					}else if ($destinataire == '*gestionnaires'){
						$destinataires = DAOMessagerie::getAIUsers();
					}else{
						$destinataires = [];
					}
				}
				if ($idexpediteur>0 and $contenu != '' and $destinataire !== null and !empty($destinataires)){
					$message = new Message($contenu, $objet);
					$message->setIdExpediteur($idexpediteur);
					$message->setDestinataires($destinataires);
					$message->setDateEnvoi(date_create('now'));
					$message->setCategorieMessage(DAOCategorieMessage::getById($categorie));
					$message->setStatutMessageExpediteur(DAOStatutMessageExpediteur::getById($categorie));
					$message->setObjetMessage(DAOObjetMessage::getByLibelle($objet));
					if (BDD::openTransaction()){
						if (DAOMessagerie::insert($message)){
							http_response_code(200);
							print(json_encode(['messageSent' => true, 'result' => 'success']));
							BDD::commitTransaction();
							@@MailToolBox::sendMessageToMailBoxes($message);
						}else{
							http_response_code(500);
							print(json_encode(['messageSent' => false, 'result' => 'error']));
							BDD::rollbackTransaction();
						}
					}else{
						http_response_code(500);
						print(json_encode(['messageSent' => false, 'result' => 'error']));
						BDD::rollbackTransaction();
					}
				}else{
					http_response_code(500);
					print(json_encode(['messageSent' => false, 'result' => 'error']));
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
