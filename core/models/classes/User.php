<?php

	class User{
		private int $id;
		private string $email, $password, $pseudo;
		private string $nom, $prenom;
		private bool $admin, $authentified;

		/**
		 * @param string $pseudo
		 * @param string $email
		 * @param string $password
		 * @param string $nom
		 * @param string $prenom
		 * @param bool   $admin
		 */
		public function __construct(string $pseudo, string $email, string $password = '', string $nom = '', string $prenom = '', bool $admin = false){
			$this->id = 0;
			$this->pseudo = $pseudo;
			$this->email = $email;
			$this->password = $password;
			$this->nom = $nom;
			$this->prenom = $prenom;
			$this->admin = $admin;
			$this->authentified = false;
		}

		/**
		 * @return string
		 */
		public function getPseudo(): string{
			return $this->pseudo;
		}

		/**
		 * @param string $pseudo
		 */
		public function setPseudo(string $pseudo): void{
			$this->pseudo = $pseudo;
		}

		/**
		 * @return string|null
		 */
		public function getPhoto(): ?string{
			return $this->photo;
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

		public function getNom(): string{
			return $this->nom;
		}

		public function setNom(string $nom): void{
			$this->nom = $nom;
		}

		public function getPrenom(): string{
			return $this->prenom;
		}

		public function setPrenom(string $prenom): void{
			$this->prenom = $prenom;
		}

		public function getFullName(bool $prenomPremier = true): string{
			$retVal = '';
			if ($prenomPremier){
				$retVal = $this->prenom . ' ' . $this->nom;
			}else{
				$retVal = $this->nom . ' ' . $this->prenom;
			}
			return $retVal;
		}

		/**
		 * @return string
		 */
		public function getHashedName(): string{
			return md5($this->getNom() . '-' . $this->getPrenom());
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

		public function getPasswordHash(): string{
			return password_hash($this->password, PASSWORD_BCRYPT);
		}

		public function isAuthentified(): bool{
			return $this->authentified;
		}

		public function setAuthentified(bool $authentified): void{
			$this->authentified = $authentified;
		}
	}
