<?php

	class Parametre extends ModelIdLibelle{
		private string|null $valeur;

		/**
		 * @param string      $libelle
		 * @param string|null $valeur
		 */
		public function __construct(string $libelle, ?string $valeur){
			parent::__construct($libelle);
			$this->valeur = $valeur;
		}

		public function getValeur(): string|null{
			return $this->valeur;
		}

		public function setValeur(?string $valeur): void{
			$this->valeur = $valeur;
		}
	}