<?php
	abstract class Modele_DAO extends BDD{
		protected static function parseRecord(mixed $SQLRow): Classe{
			//			$var = new Class($SQLRow['...']);
			//			$var->setId(intval($SQLRow['...']));
			// 			return $retVal;
		}

		public static function getByLibelle(string $libelle): Classe{
			//			$conn = parent::getConnexion();
			//
			//			$SQLQuery = "
			//			";
			//
			//			$SQLStmt = $conn->prepare($SQLQuery);
			//			$SQLStmt->bindValue(':libelle', $libelle, PDO::PARAM_STR);
			//			$SQLStmt->execute();
			//
			//			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			//
			//			$var = self::parseRecord($SQLRow);
			//
			//			$SQLStmt->closeCursor();
			//
			//			return $var;
		}

		public static function getById(int $id): Classe{
			//			$conn = parent::getConnexion();
			//
			//			$SQLQuery = "
			//			";
			//
			//			$SQLStmt = $conn->prepare($SQLQuery);
			//			$SQLStmt->bindValue(':libelle', $libelle, PDO::PARAM_STR);
			//			$SQLStmt->execute();
			//
			//			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			//
			//			$var = self::parseRecord($SQLRow);
			//
			//			$SQLStmt->closeCursor();
			//
			//			return $var;
		}

		public static function getAll(): array{
			//			$conn = parent::getConnexion();
			//
			//			$SQLQuery = "
			//			";
			//
			//			$SQLStmt = $conn->prepare($SQLQuery);
			//			$SQLStmt->execute();
			//
			//			$tab = array();
			//			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
			//				$var = self::parseRecord($SQLRow);
			//
			//				$tab[] = $var;
			//			}
			//
			//			$SQLStmt->closeCursor();
			//
			//			return $tab;
		}


		public static function insert(stdClass $class): bool{

		}

		public static function update(stdClass $class): bool{

		}

		public static function delete(stdClass $class ): bool{

		}
	}