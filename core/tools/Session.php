<?php
	class Session{
		private string $id, $name;
		private int $userId;
		private bool $userLogged;

		private static Session $activeSession;

		private array $properties;

		/**
		 * @param string $id
		 * @param string $name
		 * @param int    $userId
		 */
		public function __construct(string $id, string $name, int $userId = 0){
			$this->id = $id;
			$this->name = $name;
			$this->userId = $userId;
			$this->userLogged = false;
			$this->properties = [];
			$_SESSION[$name] = $this;
		}

		/**
		 * @return string
		 */
		public function getId(): string{
			return $this->id;
		}

		/**
		 * @param string $id
		 */
		public function setId(string $id): void{
			$this->id = $id;
		}

		/**
		 * @return string
		 */
		public function getName(): string{
			return $this->name;
		}

		/**
		 * @param string $name
		 */
		public function setName(string $name): void{
			$this->name = $name;
		}

		/**
		 * @param int $userId
		 */
		public function setUserId(int $userId): void{
			$this->userId = $userId;
			$this->userLogged = true;
		}

		/**
		 * @return int
		 */
		public function getUserId(): int{
			return $this->userId;
		}

		/**
		 * @param bool $userLogged
		 */
		public function setUserLogged(bool $userLogged): void{
			$this->userLogged = $userLogged;
		}

		/**
		 * @return bool
		 */
		public function isUserLogged(): bool{
			return $this->userLogged;
		}

		public static function initialise(string $appName): void{
			if (session_status() == PHP_SESSION_NONE || empty($_SESSION) || !isset($_SESSION[$appName])){
				self::$activeSession = new Session(session_create_id(), $appName);
			}else{
				self::$activeSession = $_SESSION[$appName];
				if (!self::$activeSession->propertyExists('LASTACTIONTIME') || (time() - intval(self::$activeSession->getProperty('LASTACTIONTIME'))) > 60){
					self::$activeSession->setProperty('LASTACTIONTIME', time());
				}
			}
		}

		public static function refresh(): void{
			if (!(session_status() == PHP_SESSION_NONE || empty($_SESSION) || !(isset(self::$activeSession)))){
				if (!self::$activeSession->propertyExists('LASTACTIONTIME') || (time() - intval(self::$activeSession->getProperty('LASTACTIONTIME'))) > 60){
					self::$activeSession->setProperty('LASTACTIONTIME', time());
				}
			}
		}

		public static function destroy(): void{
			session_unset();
			session_destroy();
		}

		public static function getActiveSession(): Session{
			return self::$activeSession;
		}

		public function setProperty(string $propertyName, string $propertyValue): void{
			$this->properties[$propertyName] = $propertyValue;
			$_SESSION[$this->getName()] = $this;
		}

		public function getProperty(string $propertyName): string{
			return $this->properties[$propertyName];
		}

		public function removeProperty(string $propertyName): void{
			unset($this->properties[$propertyName]);
		}

		public function getProperties(): array{
			return $this->properties;
		}

		public function propertyExists(string $propertyName): bool{
			return array_key_exists($propertyName, $this->properties);
		}
	}