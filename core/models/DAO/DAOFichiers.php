<?php
	abstract class DAOFichiers extends BDD{
		protected static function parseRecord(mixed $SQLRow): Fichier{
			$retVal = new Fichier($SQLRow['nom_fichier'], $SQLRow['chemin'], DAOEtudiants::getById($SQLRow['id_etudiant']), DAOEvaluations::getById($SQLRow['id_evaluation']));
			$retVal->setId(intval($SQLRow['id_fichier']));
			$retVal->setDateEnvoi(date_create($SQLRow['date_envoi']));
			$retVal->setNote($SQLRow['note']);
			$retVal->setCommentaire($SQLRow['correction_texte']);
			return $retVal;
		}

		public static function getById(int $id): Fichier{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_fichier, nom_fichier, chemin, date_envoi, id_etudiant, id_evaluation, note, correction_texte
				FROM fichier
				WHERE id_fichier = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$ecole = self::parseRecord($SQLRow);
			$SQLStmt->closeCursor();
			return $ecole;
		}

		public static function getByName(string $nom): Fichier{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_fichier, nom_fichier, chemin, date_envoi, id_etudiant, id_evaluation, note, correction_texte
				FROM fichier
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
				SELECT id_fichier, nom_fichier, chemin, date_envoi, id_etudiant, id_evaluation, note, correction_texte
				FROM fichier
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

		public static function getAllByEtudiant(Etudiant $unEtudiant): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_fichier, nom_fichier, chemin, date_envoi, id_etudiant, id_evaluation, note, correction_texte
				FROM fichier 
				ORDER BY nom
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_classe', $unEtudiant->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$listeEcoles = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$listeEcoles[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $listeEcoles;
		}

		public static function insert(Fichier $unFichier): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO fichier(nom_fichier, chemin, date_envoi, id_etudiant, id_evaluation, note, correction_texte) 
				VALUES (:nom_fichier, :chemin, :date_envoi, :id_etudiant, :id_evaluation, :note, :correction_texte)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom_fichier', $unFichier->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin', $unFichier->getChemin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':date_envoi', $unFichier->getDateEnvoi()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_etudiant', $unFichier->getIdEtudiant(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_evaluation', $unFichier->getIdEvaluation(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':note', $unFichier->getNote());
			$SQLStmt->bindValue(':correction_texte', $unFichier->getCommentaire(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$unFichier->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Fichier $unFichier): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE fichier 
				SET nom_fichier = :nom_fichier,
				    chemin = :chemin,
				    date_envoi = :date_envoi,
				    id_etudiant = :id_etudiant,
				    id_evaluation = :id_evaluation,
				    note = :note,
				    correction_texte = :correction_texte
				WHERE id_fichier = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_fichier', $unFichier->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom_fichier', $unFichier->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin', $unFichier->getChemin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':date_envoi', $unFichier->getDateEnvoi()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_etudiant', $unFichier->getIdEtudiant(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_evaluation', $unFichier->getIdEvaluation(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':note', $unFichier->getNote());
			$SQLStmt->bindValue(':correction_texte', $unFichier->getCommentaire(), PDO::PARAM_STR);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Fichier $unFichier): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM fichier WHERE id_fichier = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unFichier->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				unlink($unFichier->getChemin());
				return false;
			}else{
				return true;
			}
		}
	}