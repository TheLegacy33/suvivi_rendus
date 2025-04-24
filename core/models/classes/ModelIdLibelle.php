<?php

	abstract class ModelIdLibelle implements JsonSerializable{
		protected int $id;
		protected string $libelle;

		/**
		 * @param string $libelle
		 */
		public function __construct(string $libelle = ''){
			$this->libelle = $libelle;
			$this->id = 0;
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

		public function jsonSerialize(): array{
			return get_object_vars($this);
		}
	}
