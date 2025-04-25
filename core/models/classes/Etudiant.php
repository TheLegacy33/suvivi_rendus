<?php
	class Etudiant implements JsonSerializable{
		private int $id, $idClasse;
		private string $nom, $prenom, $email;

		private string|null $password, $codeConnexion;
		private DateTime|null $dateExpirationCodeConnexion;
		/**
		 * @param string $nom
		 * @param string $prenom
		 * @param string $email
		 */
		public function __construct(string $nom, string $prenom, string $email){
			$this->idClasse = 0;
			$this->nom = $nom;
			$this->prenom = $prenom;
			$this->email = $email;
			$this->id = 0;
			$this->password = null;
			$this->codeConnexion = null;
			$this->dateExpirationCodeConnexion = null;
		}

		public function getId(): int{
			return $this->id;
		}

		public function setId(int $id): void{
			$this->id = $id;
		}

		public function getIdClasse(): int{
			return $this->idClasse;
		}

		public function setIdClasse(int $idClasse): void{
			$this->idClasse = $idClasse;
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

		public function getEmail(): string{
			return $this->email;
		}

		public function setEmail(string $email): void{
			$this->email = $email;
		}

		public function getPassword(): ?string{
			return $this->password;
		}

		public function setPassword(?string $password): void{
			$this->password = $password;
		}

		public function getCodeConnexion(): ?string{
			return $this->codeConnexion;
		}

		public function setCodeConnexion(?string $codeConnexion): void{
			$this->codeConnexion = $codeConnexion;
		}

		public function getDateExpirationCodeConnexion(): ?DateTime{
			return $this->dateExpirationCodeConnexion;
		}

		public function setDateExpirationCodeConnexion(?DateTime $dateExpirationCodeConnexion): void{
			$this->dateExpirationCodeConnexion = $dateExpirationCodeConnexion;
		}

		public function getFullName(bool $prenomFirst = true): string{
			return $prenomFirst ? $this->prenom.' '.$this->nom : $this->nom.' '.$this->prenom;
		}

		public function jsonSerialize(): array{
			return get_object_vars($this);
		}


	}