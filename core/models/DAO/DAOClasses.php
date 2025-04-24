<?php

	abstract class DAOClasses extends BDD{
		protected static function parseRecord(mixed $SQLRow): Classe{
			$retVal = new Classe($SQLRow['nom']);
			$retVal->setId(intval($SQLRow['id_classe']));
			$retVal->setIdEcole(intval($SQLRow['id_ecole']));
			return $retVal;
		}

		public static function getById(int $id): Classe{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_classe, nom, id_ecole
				FROM classe
				WHERE id_classe = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByName(string $nom): Classe{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_classe, nom, id_ecole
				FROM classe
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
				SELECT id_classe, nom, id_ecole
				FROM classe
				ORDER BY nom
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


		public static function getAllByEcole(Ecole $uneEcole): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_classe, nom, id_ecole
				FROM classe
				WHERE id_ecole = :id_ecole
				ORDER BY nom
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_ecole', $uneEcole->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$listeClasses = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeClasses[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeClasses;
		}

		public static function insert(Classe $uneClasse): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO classe(nom, id_ecole) VALUES (:nom, :id_ecole)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $uneClasse->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_ecole', $uneClasse->getIdEcole(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$uneClasse->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Classe $uneClasse): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE classe SET nom = :nom WHERE id_classe = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $uneClasse->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $uneClasse->getNom(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Classe $uneClasse): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM classe WHERE id_classe = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $uneClasse->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}
	}