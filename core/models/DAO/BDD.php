<?php

	abstract class BDD{
		private static string $dsnserveur = 'mysql:host=%MYSQL_HOST%;port=%MYSQL_PORT%;charset=utf8;dbname=%MYSQL_BDD%';
		private static string $user = '';
		private static string $pass = '';
		private static PDO $_instance;

		public function __construct(){
		}

		protected static function getConnexion(): PDO{
			global $config;
			self::$dsnserveur = str_replace('%MYSQL_HOST%', $config['DB_HOST'], self::$dsnserveur);
			self::$dsnserveur = str_replace('%MYSQL_PORT%', $config['DB_PORT'], self::$dsnserveur);
			self::$dsnserveur = str_replace('%MYSQL_BDD%', $config['DB_NAME'], self::$dsnserveur);
			self::$user = $config['DB_USER'];
			self::$pass = $config['DB_PASSWORD'];
			if (!isset(self::$_instance) or is_null(self::$_instance)){
				try{
					//Connexion sur le serveur
					self::$_instance = new PDO(self::$dsnserveur, self::$user, self::$pass);
				}catch (PDOException $ex){
					exit($ex->getMessage());
				}
			}
			return self::$_instance;
		}

		public static function openTransaction(): bool{
			return self::getConnexion()->beginTransaction();
		}

		public static function commitTransaction(): bool{
			return self::getConnexion()->commit();
		}

		public static function rollbackTransaction(): bool{
			return self::getConnexion()->rollBack();
		}

		protected abstract static function parseRecord(mixed $SQLRow): mixed;
	}