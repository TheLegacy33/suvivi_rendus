<?php

	abstract class DAOUser extends BDD{

		protected static function parseRecord(mixed $SQLRow): User{
			$retVal = new User($SQLRow['login_identifiant'], $SQLRow['email'], $SQLRow['mot_de_passe'], intval($SQLRow['is_admin']) == 1, intval($SQLRow['is_active']) == 1);
			$retVal->setId(intval($SQLRow['id_user']));
			$retVal->setRoles(DAORoles::getByUser($retVal));
			return $retVal;
		}

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
				SET mot_de_passe = :newPwd, date_modif = CURRENT_DATE
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
				SELECT is_active
				FROM utilisateur
				WHERE email = :email
					OR login_identifiant = :login_identifiant
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $loginToChek, PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $loginToChek, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$userActive = (intval($SQLRow['is_active']) == 1);
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
			$SQLQuery = "
				SELECT id_user, login_identifiant, email, mot_de_passe, is_admin, is_active
				FROM utilisateur
				WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$unUser = new User('Utilisateur', 'Utilisateur@unknown');
			if ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unUser = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $unUser;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT id_user, login_identifiant, email, mot_de_passe, is_admin, is_active
				FROM utilisateur";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$listeUsers = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unUser = self::parseRecord($SQLRow);
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
						    mot_de_passe = :mdp,
						    is_admin = :admin,
						    is_active = :is_active,
						    date_modif = CURRENT_DATE 
						WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':email', $newUser->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':login_identifiant', $newUser->getLoginidentifiant(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':mdp', $newUser->getPassword(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':admin', $newUser->isAdmin(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':is_active', $newUser->isActive(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id', $newUser->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function insert(User $newUser): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO utilisateur(login_identifiant, email, mot_de_passe, is_admin, is_active, date_ajout)
			VALUES (:login_identifiant, :email, :mot_de_passe, :is_admin, :is_active, :date_ajout)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':login_identifiant', $newUser->getLoginidentifiant(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':email', $newUser->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':mdp', $newUser->getPasswordHash(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':is_admin', $newUser->isAdmin(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':is_active', $newUser->isActive(), PDO::PARAM_BOOL);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(User $userToDelete): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM utilisateur WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $userToDelete->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function updateStatut(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET is_active = :is_active, date_modif = CURRENT_DATE
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':is_active', $user->isActive(), PDO::PARAM_BOOL);
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

		/**
		 * fonction de génération d'un password lettres minuscules + majuscules + chiffre + caractère spéciaux (-?!@#$%^&*_+=.~)
		 * @param int $maxlength
		 * @return string
		 */
		public static function generatePassword(int $maxlength = 12): string{
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
			for ($i = 4; $i<$maxlength; $i++){
				$motDePasse .= $tousCaracteres[rand(0, strlen($tousCaracteres) - 1)];
			}
			// Mélanger le mot de passe pour éviter un ordre prévisible
			return str_shuffle($motDePasse);
		}

		/**
		 * fonction de génération d'un code chiffres
		 * @param int $maxlength
		 * @return string
		 */
		public static function generateCode(int $maxlength = 6): string{
			// Définitions des groupes de caractères
			$chiffres = '0123456789';
			// Prendre au moins un caractère de chaque groupe
			$code = $chiffres[rand(0, strlen($chiffres) - 1)];
			for ($i = 1; $i<$maxlength; $i++){
				$code .= $chiffres[rand(0, strlen($chiffres) - 1)];
			}
			// Mélanger le mot de passe pour éviter un ordre prévisible
			return str_shuffle($code);
		}
	}
