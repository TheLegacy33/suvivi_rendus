<?php

	abstract class ModelIdLibelle implements JsonSerializable{
		protected int $id;
		protected string $libelle;
		protected string|null $libelleEn;

		/**
		 * @param string $libelle
		 */
		public function __construct(string $libelle = '', ?string $libelleEn = null){
			$this->libelle = $libelle;
			$this->id = 0;
			$this->libelleEn = $libelleEn;
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
		public function getLibelle(): string{
			return $this->libelle;
		}

		/**
		 * @param string $libelle
		 */
		public function setLibelle(string $libelle): void{
			$this->libelle = $libelle;
		}

		/**
		 * @return string
		 */
		public function getLibelleEn(): ?string{
			return $this->libelleEn;
		}

		/**
		 * @param string $libelleEn
		 */
		public function setLibelleEn(?string $libelleEn): void{
			$this->libelleEn = $libelleEn;
		}

		public function jsonSerialize(): array{
			return get_object_vars($this);
		}
	}
