<?php

	abstract class DAOEtudiants extends BDD{
		protected static function parseRecord(mixed $SQLRow): Etudiant{
			$retVal = new Etudiant($SQLRow['nom'], $SQLRow['prenom'], $SQLRow['email']);
			$retVal->setId(intval($SQLRow['id_etudiant']));
			$retVal->setIdClasse(intval($SQLRow['id_classe']));

			$retVal->setPassword($SQLRow['mot_de_passe']);
			$retVal->setCodeConnexion($SQLRow['code_connexion']);
			$retVal->setDateExpirationCodeConnexion(is_null($SQLRow['expiration_code_connexion']) ? null : date_create($SQLRow['expiration_code_connexion']));
			return $retVal;
		}

		public static function getById(int $id): Etudiant{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_etudiant, nom, prenom, email, id_classe, mot_de_passe, code_connexion, expiration_code_connexion
				FROM etudiant
				WHERE id_etudiant = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByName(string $nom): Etudiant{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_etudiant, nom, prenom, email, id_classe, mot_de_passe, code_connexion, expiration_code_connexion
				FROM etudiant
				WHERE nom = :nom
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $nom, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_etudiant, nom, prenom, email, id_classe, mot_de_passe, code_connexion, expiration_code_connexion
				FROM etudiant
				ORDER BY nom
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$listeEcoles = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeEcoles[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeEcoles;
		}


		public static function getAllByClasse(Classe $uneClasse): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_etudiant, nom, prenom, email, id_classe, mot_de_passe, code_connexion, expiration_code_connexion
				FROM etudiant
				WHERE id_classe = :id_classe
				ORDER BY nom
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_classe', $uneClasse->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$listeEcoles = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeEcoles[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeEcoles;
		}

		public static function insert(Etudiant $unEtudiant): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO etudiant(nom, prenom, email, id_classe, mot_de_passe, code_connexion, expiration_code_connexion) 
				VALUES (:nom, :prenom, :email, :id_classe, :mot_de_passe, :code_connexion, :expiration_code_connexion)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $unEtudiant->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':prenom', $unEtudiant->getPrenom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':email', $unEtudiant->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_classe', $unEtudiant->getIdClasse(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':mot_de_passe', is_null($unEtudiant->getPassword()) ? null : password_hash($unEtudiant->getPassword(), PASSWORD_BCRYPT), PDO::PARAM_STR);
			$SQLStmt->bindValue(':code_connexion', $unEtudiant->getCodeConnexion(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':expiration_code_connexion', $unEtudiant->getDateExpirationCodeConnexion()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$unEtudiant->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Etudiant $unEtudiant): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE etudiant 
				SET nom = :nom,
				    prenom = :prenom,
				    email = :email,
				    id_classe = :id_classe,
				    mot_de_passe = :mot_de_passe,
				    code_connexion = :code_connexion,
				    expiration_code_connexion = :expiration_code_connexion
				WHERE id_etudiant = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unEtudiant->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $unEtudiant->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':prenom', $unEtudiant->getPrenom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':email', $unEtudiant->getEmail(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_classe', $unEtudiant->getIdClasse(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':mot_de_passe', is_null($unEtudiant->getPassword()) ? null : password_hash($unEtudiant->getPassword(), PASSWORD_BCRYPT), PDO::PARAM_STR);
			$SQLStmt->bindValue(':code_connexion', $unEtudiant->getCodeConnexion(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':expiration_code_connexion', $unEtudiant->getDateExpirationCodeConnexion()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Etudiant $unEtudiant): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM etudiant WHERE id_etudiant = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unEtudiant->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}
	}