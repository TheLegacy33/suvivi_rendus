<?php
	class Evaluation implements JsonSerializable{
		private int $id;
		private string $nom;

		/**
		 * @param string $nom
		 */
		public function __construct(string $nom){
			$this->nom = $nom;
			$this->id = 0;
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

		public function jsonSerialize(): array{
			return get_object_vars($this);
		}
	}