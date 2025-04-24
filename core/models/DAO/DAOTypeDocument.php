<?php


	abstract class DAOTypeDocument extends BDD{
		public static function getByLibelle(string $libelle): TypeDocument{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_type_document, libelle
				FROM type_document
				WHERE libelle = :libelle
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':libelle', $libelle, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$typeDoc = new TypeDocument($SQLRow['libelle']);
			$typeDoc->id = intval($SQLRow['id_type_document']);
			$SQLStmt->closeCursor();
			return $typeDoc;
		}

		public static function getById(int $id): TypeDocument{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_type_document, libelle
				FROM type_document
				WHERE id_type_document = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$typeDoc = new TypeDocument($SQLRow['libelle']);
			$typeDoc->id = intval($SQLRow['id_type_document']);
			$SQLStmt->closeCursor();
			return $typeDoc;
		}
	}