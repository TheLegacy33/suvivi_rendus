<?php

	abstract class DAOEcoles extends BDD{
		protected static function parseRecord(mixed $SQLRow): Ecole{
			$retVal = new Ecole($SQLRow['nom']);
			$retVal->setId(intval($SQLRow['id_ecole']));
			return $retVal;
		}

		public static function getById(int $id): Ecole{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_ecole, nom
				FROM ecole
				WHERE id_ecole = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByName(string $nom): Ecole{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_ecole, nom
				FROM ecole
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
					SELECT id_ecole, nom
					FROM ecole
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

		public static function insert(Ecole $uneEcole): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO ecole(nom) VALUES (:nom)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $uneEcole->getNom(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$uneEcole->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Ecole $uneEcole): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE ecole SET nom = :nom WHERE id_ecole = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $uneEcole->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $uneEcole->getNom(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Ecole $uneEcole): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM ecole WHERE id_ecole = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $uneEcole->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}
	}