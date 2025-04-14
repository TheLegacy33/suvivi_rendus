<?php
	abstract class DAOParametres extends BDD{
		public static function getByLibelle(string $libelle): ?Parametre{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_parametre, libelle, valeur
				FROM parametre
				WHERE libelle = :libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':libelle', $libelle, PDO::PARAM_STR);
			$SQLStmt->execute();
			if ($SQLStmt->rowCount() == 0){
				$param = null;
			}else{
				$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
				$param = new Parametre(html_entity_decode($SQLRow['libelle']), $SQLRow['valeur']);
				$param->setId($SQLRow['id_parametre']);
			}
			$SQLStmt->closeCursor();
			return $param;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_parametre, libelle, valeur
				FROM parametre
				ORDER BY libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$lesParams = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$param = new Parametre(html_entity_decode($SQLRow['libelle']), $SQLRow['valeur']);
				$param->setId($SQLRow['id_parametre']);
				$lesParams[] = $param;
			}
			$SQLStmt->closeCursor();
			return $lesParams;
		}
	}