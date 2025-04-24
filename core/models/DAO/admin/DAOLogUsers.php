<?php


	abstract class DAOLogUsers extends BDD{
		protected static function parseRecord(mixed $SQLRow): LogUser{
			$unLog = new LogUser($SQLRow['id_user'], date_create($SQLRow['date_log']));
			$unLog->setId(intval($SQLRow['id_user_log']));
			$unLog->setActionLog($SQLRow['action_log']);
			$unLog->setReferer($SQLRow['http_referer']);
			$unLog->setUserAgent($SQLRow['http_user_agent']);
			$unLog->setRemoteAddr($SQLRow['remote_addr']);
			$unLog->setRequestTime($SQLRow['request_time']);
			$unLog->setRequestTimeStr($SQLRow['request_time_str']);
			return $unLog;
		}

		public static function getByAction(string $action = '*'): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_user_log, id_user, date_log, action_log, http_user_agent, http_referer, remote_addr, request_time, request_time_str
				FROM user_log
				WHERE action_log = :action_log
					OR :action_log = '*'
				ORDER BY request_time DESC
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':action_log', $action, PDO::PARAM_STR);
			$SQLStmt->execute();
			$lesLogs = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$lesLogs[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $lesLogs;
		}

		public static function getByDate(DateTime $dateLog = null): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_user_log, id_user, date_log, action_log, http_user_agent, http_referer, remote_addr, request_time, request_time_str
				FROM user_log
				WHERE date_log = :date_log
				ORDER BY action_log
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':date_log', is_null($dateLog) ? date('Y-m-d') : $dateLog->format('Y-m-d'), PDO::PARAM_STR);
			$SQLStmt->execute();
			$lesLogs = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$lesLogs[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $lesLogs;
		}

		public static function getById(int $id): LogUser{
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
			//			$var = new Class($SQLRow['...']);
			//			$var->setId(intval($SQLRow['...']));
			//
			//			$SQLStmt->closeCursor();
			//
			//			return $var;
		}

		public static function getAll(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_user_log, id_user, date_log, action_log, http_user_agent, http_referer, remote_addr, request_time, request_time_str
				FROM user_log
				ORDER BY request_time DESC
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$lesLogs = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$lesLogs[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $lesLogs;
		}

		public static function getByUserId(int $idUser): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_user_log, id_user, date_log, action_log, http_user_agent, http_referer, remote_addr, request_time, request_time_str
				FROM user_log
				WHERE id_user = :id_user
				ORDER BY request_time DESC
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $idUser, PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesLogs = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$lesLogs[] = self::parseRecord($SQLRow);
			}
			$SQLStmt->closeCursor();
			return $lesLogs;
		}

		public static function getLastLoginDate(int $idUser): ?DateTime{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT MAX(date_log)
				FROM user_log
				WHERE id_user = :id_user
					AND action_log = 'connexion'
				ORDER BY request_time DESC
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $idUser, PDO::PARAM_INT);
			$SQLStmt->execute();
			$retVal = null;
			if ($SQLStmt->rowCount()>0){
				$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
				if (!is_null($SQLRow[0])){
					$retVal = date_create($SQLRow[0]);
				}
			}
			$SQLStmt->closeCursor();
			return $retVal;
		}
	}