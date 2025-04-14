<?php

	class Document{
		private int $id;
		private string $nom, $chemin;
		private StatutDocument $statut;
		private TypeDocument $typeDocument;
		private DateTime $dateCreation;

		/**
		 * @param string         $nom
		 * @param string         $chemin
		 * @param StatutDocument $statut
		 * @param TypeDocument   $typeDocument
		 * @param DateTime|null  $dateCreation
		 */
		public function __construct(string $nom, string $chemin, StatutDocument $statut, TypeDocument $typeDocument, DateTime $dateCreation = null){
			$this->id = 0;
			$this->nom = $nom;
			$this->chemin = $chemin;
			$this->statut = $statut;
			$this->typeDocument = $typeDocument;
			$this->dateCreation = is_null($dateCreation) ? date_create(date('Y-m-d')) : $dateCreation;
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

		public function getChemin(): string{
			return $this->chemin;
		}

		public function setChemin(string $chemin): void{
			$this->chemin = $chemin;
		}

		public function getStatut(): StatutDocument{
			return $this->statut;
		}

		public function setStatut(StatutDocument $statut): void{
			$this->statut = $statut;
		}

		public function getTypeDocument(): TypeDocument{
			return $this->typeDocument;
		}

		public function setTypeDocument(TypeDocument $typeDocument): void{
			$this->typeDocument = $typeDocument;
		}

		public function getDateCreation(): DateTime{
			return $this->dateCreation;
		}

		public function setDateCreation(DateTime $dateCreation): void{
			$this->dateCreation = $dateCreation;
		}

		public function getCheminPublicFile(): string{
			return str_replace(PHP_PUBLIC_DIR, '', $this->chemin);
		}
	}