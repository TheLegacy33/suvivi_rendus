<?php

	class User{
		private int $id;
		private string $email, $password, $loginidentifiant;
		private bool $admin, $authentified, $active;
		private array $roles;

		/**
		 * @param string $loginidentifiant
		 * @param string $email
		 * @param string $password
		 * @param bool   $admin
		 * @param bool   $active
		 */
		public function __construct(string $loginidentifiant, string $email, string $password = '', bool $admin = false, bool $active = true){
			$this->id = 0;
			$this->loginidentifiant = $loginidentifiant;
			$this->email = $email;
			$this->password = $password;
			$this->admin = $admin;
			$this->authentified = false;
			$this->active = $active;
			$this->roles = array();
		}

		public function getLoginidentifiant(): string{
			return $this->loginidentifiant;
		}

		public function setLoginidentifiant(string $loginidentifiant): void{
			$this->loginidentifiant = $loginidentifiant;
		}

		/**
		 * @return int
		 */
		public function getId(): int{
			return $this->id;
		}

		/**
		 * @param int $id
		 */
		public function setId(int $id): void{
			$this->id = $id;
		}

		/**
		 * @return string
		 */
		public function getEmail(): string{
			return $this->email;
		}

		/**
		 * @param string $email
		 */
		public function setEmail(string $email): void{
			$this->email = $email;
		}

		/**
		 * @return string
		 */
		public function getPassword(): string{
			return $this->password;
		}

		/**
		 * @param string $password
		 */
		public function setPassword(string $password): void{
			$this->password = $password;
		}

		/**
		 * @return bool
		 */
		public function isAdmin(): bool{
			return $this->admin;
		}

		/**
		 * @param bool $admin
		 */
		public function setAdmin(bool $admin): void{
			$this->admin = $admin;
		}

		public function isActive(): bool{
			return $this->active;
		}

		public function setActive(bool $active): void{
			$this->active = $active;
		}

		public function getPasswordHash(): string{
			return password_hash($this->password, PASSWORD_BCRYPT);
		}

		public function isAuthentified(): bool{
			return $this->authentified;
		}

		public function setAuthentified(bool $authentified): void{
			$this->authentified = $authentified;
		}

		public function getRoles(): array{
			return $this->roles;
		}

		public function setRoles(array $roles): void{
			$this->roles = $roles;
		}

		public function hasRole(Role $role): bool{
			return in_array($role, $this->roles);
		}
	}
