<?php
	abstract class DAODocument extends BDD{
		public static function getById(int $id): Document{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, user_document.date_import
				FROM document INNER JOIN user_document on document.id_document = user_document.id_document
				WHERE document.id_document = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $id, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$doc = new Document(html_entity_decode($SQLRow['nom']), $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
			$doc->setId(intval($SQLRow['id_document']));
			$SQLStmt->closeCursor();
			return $doc;
		}

		public static function getByName(string $name): Document{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, user_document.date_import
				FROM document INNER JOIN user_document on document.id_document = user_document.id_document
				WHERE nom = :name
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':name', $name, PDO::PARAM_STR);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$doc = new Document(html_entity_decode($SQLRow['nom']), $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
			$doc->setId(intval($SQLRow['id_document']));
			$SQLStmt->closeCursor();
			return $doc;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, user_document.date_import
				FROM document INNER JOIN user_document on document.id_document = user_document.id_document";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$listeDocuments = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unDocument = new Document(html_entity_decode($SQLRow['nom']), $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
				$unDocument->setId($SQLRow['id_document']);
				$listeDocuments[] = $unDocument;
			}
			$SQLStmt->closeCursor();
			return $listeDocuments;
		}

		public static function getByUserId(int $id_user): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, date_import
				FROM document INNER JOIN user_document ON document.id_document = user_document.id_document
				WHERE user_document.id_user = :id_user
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesDocuments = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$document = new Document($SQLRow['nom'], $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
				$document->setId(intval($SQLRow['id_document']));
				$lesDocuments[] = $document;
			}
			$SQLStmt->closeCursor();
			return $lesDocuments;
		}

		public static function getByArtisteId(int $id_artiste): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, date_import
				FROM document INNER JOIN artiste_document ON document.id_document = artiste_document.id_document
				WHERE artiste_document.id_artiste = :id_artiste
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $id_artiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesDocuments = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$document = new Document($SQLRow['nom'], $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
				$document->setId(intval($SQLRow['id_document']));
				$lesDocuments[] = $document;
			}
			$SQLStmt->closeCursor();
			return $lesDocuments;
		}

		public static function insert(Document $unDocument): bool{
			// INSERT DANS LA BDD
			$conn = parent::getConnexion();
			$SQLQuery = "INSERT INTO document(nom, chemin, id_statut_doc, id_type_document)
			VALUES (:nom, :chemin, :id_statut_doc, :id_type_document)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':nom', $unDocument->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin', $unDocument->getChemin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_statut_doc', $unDocument->getStatut()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_document', $unDocument->getTypeDocument()->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				$unDocument->setId($conn->lastInsertId());
				return true;
			}
		}

		public static function update(Document $unDocument): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE document SET nom = :nom, 
                    chemin = :chemin, 
                    id_type_document = :id_type_document, 
                    id_statut_doc = :id_statut_doc
			WHERE id_document = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unDocument->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':nom', $unDocument->getNom(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin', $unDocument->getChemin(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_statut_doc', $unDocument->getStatut()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_document', $unDocument->getTypeDocument()->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function delete(Document $unDocument): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "DELETE FROM document WHERE id_document = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $unDocument->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}
	}