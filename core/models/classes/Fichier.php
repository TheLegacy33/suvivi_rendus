<?php
	class Fichier implements JsonSerializable{
		private int $id, $idEtudiant, $idEvaluation;
		private string $nom, $chemin, $cheminPublic;
		private DateTime $dateEnvoi;
		private float|null $note;
		private string|null $commentaire;

		/**
		 * @param string     $nom
		 * @param string     $chemin
		 * @param Etudiant   $etudiant
		 * @param Evaluation $evaluation
		 */
		public function __construct(string $nom, string $chemin, Etudiant $etudiant, Evaluation $evaluation,){
			$this->idEtudiant = $etudiant->getId();
			$this->idEvaluation = $evaluation->getId();
			$this->nom = $nom;
			$this->chemin = $chemin;
			$this->cheminPublic = $this->getCheminPublicFile();
			$this->dateEnvoi = date_create('now');
			$this->note = null;
			$this->commentaire = null;
			$this->id = 0;
		}

		public function getId(): int{
			return $this->id;
		}

		public function setId(int $id): void{
			$this->id = $id;
		}

		public function getIdEtudiant(): int{
			return $this->idEtudiant;
		}

		public function setIdEtudiant(int $idEtudiant): void{
			$this->idEtudiant = $idEtudiant;
		}

		public function getIdEvaluation(): int{
			return $this->idEvaluation;
		}

		public function setIdEvaluation(int $idEvaluation): void{
			$this->idEvaluation = $idEvaluation;
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
			$this->cheminPublic = $this->getCheminPublicFile();
		}

		public function getDateEnvoi(): DateTime{
			return $this->dateEnvoi;
		}

		public function setDateEnvoi(DateTime $dateEnvoi): void{
			$this->dateEnvoi = $dateEnvoi;
		}

		public function getNote(): ?float{
			return $this->note;
		}

		public function setNote(?float $note): void{
			$this->note = $note;
		}

		public function getCommentaire(): ?string{
			return $this->commentaire;
		}

		public function setCommentaire(?string $commentaire): void{
			$this->commentaire = $commentaire;
		}

		public function getCheminPublicFile(): string{
			$partiel = str_replace(PHP_PUBLIC_DIR, '', $this->chemin);
			return EXTERNAL_URL.'/'.$partiel;
		}

		public function jsonSerialize(): array{
			return get_object_vars($this);
		}
	}