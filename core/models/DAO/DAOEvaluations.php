<?php

	abstract class DAOEvaluations extends BDD{
		protected static function parseRecord(mixed $SQLRow): Evaluation{
			$retVal = new Evaluation($SQLRow['nom']);
			$retVal->setId(intval($SQLRow['id_evaluation']));
			return $retVal;
		}

		public static function getById(int $id): Evaluation{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_evaluation, nom
				FROM evaluation
				WHERE id_evaluation = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByName(string $nom): Evaluation{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_evaluation, nom
				FROM evaluation
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
				SELECT id_evaluation, nom
				FROM evaluation
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
				SELECT evaluation.id_evaluation, evaluation.nom
				FROM evaluation INNER JOIN affecter_evaluation ON evaluation.id_evaluation = affecter_evaluation.id_evaluation
				WHERE affecter_evaluation.id_classe = :id_classe
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

		public static function insert(Evaluation $uneEvaluation): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO evaluation(nom) VALUES (:nom)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $uneEvaluation->getNom(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$uneEvaluation->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Evaluation $uneEvaluation): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE evaluation SET nom = :nom WHERE id_evaluation = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $uneEvaluation->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $uneEvaluation->getNom(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Evaluation $uneEcole): bool{
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