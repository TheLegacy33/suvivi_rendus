<?php

	abstract class DAORoles extends BDD{
		protected static function parseRecord(mixed $SQLRow): Role{
			$retVal = new Role($SQLRow['libelle']);
			$retVal->setId(intval($SQLRow['id_role']));
			return $retVal;
		}

		public static function getById(int $id): Role{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_role, libelle
				FROM role_user
				WHERE id_role = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByLibelle(string $libelle): Role{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_role, libelle
				FROM role_user
				WHERE libelle = :libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':libelle', $libelle);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_role, libelle
				FROM role_user
				ORDER BY libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$listeClasses = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeClasses[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeClasses;
		}


		public static function getByUser(User $unUser): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT role_user.id_role, libelle
				FROM role_user INNER JOIN user_role on role_user.id_role = user_role.id_role
				WHERE user_role.id_user = :id_user
				ORDER BY libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $unUser->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$listeRoles = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeRoles[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeRoles;
		}

		public static function insert(Role $unRole): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO role_user(libelle) VALUES (:libelle)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':libelle', $unRole->getLibelle());
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$unRole->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Role $unRole): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE role_user SET libelle = :libelle WHERE id_role = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unRole->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $unRole->getLibelle());
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Role $unRole): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM role_user WHERE id_role = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unRole->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}
	}