<?php
	class Classe implements JsonSerializable{
		private int $id, $idEcole;
		private string $nom;
		private string|null $emailRendu;

		/**
		 * @param string $nom
		 */
		public function __construct(string $nom){
			$this->nom = $nom;
			$this->emailRendu = null;
			$this->id = 0;
			$this->idEcole = 0;
		}

		public function getId(): int{
			return $this->id;
		}

		public function setId(int $id): void{
			$this->id = $id;
		}

		public function getNom(): string{
			return $this->nom;
		}

		public function setNom(string $nom): void{
			$this->nom = $nom;
		}

		public function getEmailRendu(): ?string{
			return $this->emailRendu;
		}

		public function setEmailRendu(?string $emailRendu): void{
			$this->emailRendu = $emailRendu;
		}

		public function getIdEcole(): int{
			return $this->idEcole;
		}

		public function setIdEcole(int $idEcole): void{
			$this->idEcole = $idEcole;
		}
		public function jsonSerialize(): array{
			return get_object_vars($this);
		}
	}