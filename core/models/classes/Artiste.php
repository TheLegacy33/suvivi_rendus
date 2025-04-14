<?php

	class Artiste{
		private int $id, $nbFollowers, $nbVues, $popularite;
		private string|null $pseudo, $imageAtelier, $imagePrincipale, $titreImagePrincipale, $titreImageAtelier;
		private User|null $user;
		private Entreprise|null $entreprise;
		private Pays|null $nationalite, $residence;
		private array|null $adresses, $oeuvres, $informations, $mediums, $documents, $formations, $recompenses, $expositions, $evenements, $presses, $series, $reperes, $motsclefs, $collections;
		private DateTime|null $dateAjout, $dateModif;
		private CategorieArtiste|null $categorieArtiste;
		private TauxNegociation|null $tauxNegociation;
		private bool $accepteNegociation, $accepteDedicace, $accepteCommande, $afficherPseudo, $afficherNaissance;
		private bool|null $assujettiTVA;
		private TauxTVA|null $tauxTVA;
		private Document|null $attestationTVA;
		private PeriodeAbsence|null $periodeAbsence;
		// documents
		// oeuvres
		private string $personalFolder;
		private bool $artisteDeConfiance;

		/**
		 * @param string|null $pseudo
		 */
		public function __construct(?string $pseudo){
			$this->id = 0;
			$this->user = null;
			$this->pseudo = $pseudo;
			$this->categorieArtiste = null;
			$this->entreprise = null;
			$this->adresses = [];
			$this->oeuvres = [];
			$this->series = [];
			$this->informations = [];
			$this->mediums = [];
			$this->documents = [];
			$this->formations = [];
			$this->recompenses = [];
			$this->expositions = [];
			$this->collections = [];
			$this->evenements = [];
			$this->presses = [];
			$this->reperes = [];
			$this->motsclefs = [];
			$this->personalFolder = '';
			$this->nationalite = null;
			$this->residence = null;
			$this->nbFollowers = 0;
			$this->nbVues = 0;
			$this->popularite = 0;
			$this->dateAjout = new DateTime('now');
			$this->dateModif = null;
			$this->imageAtelier = null;
			$this->imagePrincipale = null;
			$this->titreImagePrincipale = null;
			$this->titreImageAtelier = null;
			$this->tauxNegociation = null;
			$this->accepteDedicace = false;
			$this->accepteNegociation = false;
			$this->accepteCommande = false;
			$this->artisteDeConfiance = false;
			$this->afficherPseudo = false;
			$this->afficherNaissance = false;
			$this->periodeAbsence = null;
			$this->assujettiTVA = null;
			$this->attestationTVA = null;
			$this->tauxTVA = null;
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

		public function getPseudo(): ?string{
			return $this->pseudo;
		}

		public function setPseudo(?string $pseudo): void{
			$this->pseudo = $pseudo;
		}

		public function getNom(): string{
			return $this->user->getNom();
		}

		public function setNom(string $nom): void{
			$this->user->setNom($nom);
		}

		public function getPrenom(): string{
			return $this->user->getPrenom();
		}

		public function setPrenom(string $prenom): void{
			$this->user->setPrenom($prenom);
		}

		public function getPrenomInitiale(): string{
			return substr($this->getPrenom(), 0, 1);
		}

		public function getNomInitiale(): string{
			return substr($this->getNom(), 0, 1);
		}

		public function getPseudoInitiale(): string{
			return substr($this->getPseudo(), 0, 1);
		}

		/**
		 * @return User
		 */
		public function getUser(): User{
			return $this->user;
		}

		/**
		 * @param User $user
		 */
		public function setUser(User $user): void{
			$this->user = $user;
		}

		/**
		 * @return Entreprise|null
		 */
		public function getEntreprise(): ?Entreprise{
			return $this->entreprise;
		}

		/**
		 * @param Entreprise|null $entreprise
		 */
		public function setEntreprise(?Entreprise $entreprise): void{
			$this->entreprise = $entreprise;
		}

		/**
		 * @return array
		 */
		public function getAdresses(): array{
			return $this->adresses;
		}

		/**
		 * @param array $adresses
		 */
		public function setAdresses(array $adresses): void{
			$this->adresses = $adresses;
		}

		/**
		 * @param Adresse $adresse
		 * @return void
		 */
		public function addAdresse(Adresse $adresse): void{
			$this->adresses[] = $adresse;
		}

		/**
		 * @return array
		 */
		public function getOeuvres(): array{
			return $this->oeuvres;
		}

		/**
		 * @param array $oeuvres
		 */
		public function setOeuvres(array $oeuvres): void{
			$this->oeuvres = $oeuvres;
		}

		/**
		 * @param Oeuvre $oeuvre
		 * @return void
		 */
		public function addOeuvre(Oeuvre $oeuvre): void{
			$this->oeuvres[] = $oeuvre;
		}

		/**
		 * @return array
		 */
		public function getSeries(): array{
			return $this->series;
		}

		/**
		 * @param array $series
		 */
		public function setSeries(array $series): void{
			$this->series = $series;
		}

		/**
		 * @param Serie $serie
		 * @return void
		 */
		public function addSerie(Serie $serie): void{
			$this->series[] = $serie;
		}

		/**
		 * @return array
		 */
		public function getDocuments(): array{
			return $this->documents;
		}

		/**
		 * @param array $documents
		 */
		public function setDocuments(array $documents): void{
			$this->documents = $documents;
		}

		/**
		 * @param Document $document
		 * @return void
		 */
		public function addDocument(Document $document): void{
			$this->documents[] = $document;
		}

		/**
		 * @return array
		 */
		public function getInformations(): array{
			return $this->informations;
		}

		/**
		 * @param array $informations
		 */
		public function setInformations(array $informations): void{
			$this->informations = $informations;
		}

		/**
		 * @param Information $information
		 * @param string      $valeur
		 * @return void
		 */
		public function addInformation(Information $information, string $valeur): void{
			$information->setValeur($valeur);
			$this->informations[] = $information;
		}

		/**
		 * @return array
		 */
		public function getMediums(): array{
			return $this->mediums;
		}

		/**
		 * @param array $mediums
		 */
		public function setMediums(array $mediums): void{
			$this->mediums = $mediums;
		}

		/**
		 * @param Medium $medium
		 * @return void
		 */
		public function addMedium(Medium $medium): void{
			$this->mediums[] = $medium;
		}

		/**
		 * @return void
		 */
		public function createPersonalFolder(): void{
			$this->personalFolder = PHP_UPLOAD_DIR_ARTISTES . $this->getHashedName() . '/';
			if (!is_writeable(PHP_UPLOAD_DIR_ARTISTES)){
				@chmod(PHP_UPLOAD_DIR_ARTISTES, 0777);
			}
			if (!(file_exists($this->personalFolder))){
				mkdir($this->personalFolder, recursive: true);
				@chmod($this->personalFolder, 0777);
			}elseif (!is_writable($this->personalFolder)){
				@chmod($this->personalFolder, 0777);
			}
		}

		public function setPersonalFolder(): void{
			$this->personalFolder = PHP_UPLOAD_DIR_ARTISTES . $this->getHashedName() . '/';
		}

		/**
		 * @return string
		 */
		public function getPersonalFolder(): string{
			return $this->personalFolder;
		}

		/**
		 * @return string
		 */
		public function getHashedName(): string{
			return md5($this->user->getNom() . '-' . $this->user->getPrenom());
		}

		public function getNationalite(): ?Pays{
			return $this->nationalite;
		}

		public function setNationalite(?Pays $nationalite): void{
			$this->nationalite = $nationalite;
		}

		public function getResidence(): ?Pays{
			return $this->residence;
		}

		public function setResidence(?Pays $residence): void{
			$this->residence = $residence;
		}

		public function getNbFollowers(): int{
			return $this->nbFollowers;
		}

		public function setNbFollowers(int $nbFollowers): void{
			$this->nbFollowers = $nbFollowers;
		}

		public function getNbVues(): int{
			return $this->nbVues;
		}

		public function setNbVues(int $nbVues): void{
			$this->nbVues = $nbVues;
		}

		public function getPopularite(): int{
			return $this->popularite;
		}

		public function setPopularite(int $popularite): void{
			$this->popularite = $popularite;
		}

		public function getCategorieArtiste(): ?CategorieArtiste{
			return $this->categorieArtiste;
		}

		public function setCategorieArtiste(?CategorieArtiste $categorieArtiste): void{
			$this->categorieArtiste = $categorieArtiste;
		}

		public function getDateAjout(): ?DateTime{
			return $this->dateAjout;
		}

		public function setDateAjout(?DateTime $dateAjout): void{
			$this->dateAjout = $dateAjout;
		}

		public function getDateModif(): ?DateTime{
			return $this->dateModif;
		}

		public function setDateModif(?DateTime $dateModif): void{
			$this->dateModif = $dateModif;
		}

		public function getRandomOeuvre(): Oeuvre|bool{
			if (count($this->oeuvres)>0){
				$numRamdom = rand(0, count($this->oeuvres) - 1);
				return $this->oeuvres[$numRamdom];
			}else{
				return false;
			}
		}

		public function getNbOeuvres(): int{
			return count($this->oeuvres);
		}

		public function hasInformation(Information $informationSearched): bool{
			$retVal = false;
			foreach ($this->informations as $information){
				if ($information->getId() == $informationSearched->getId() and $information->getLibelle() == $informationSearched->getLibelle()){
					$retVal = true;
				}
			}
			return $retVal;
		}

		public function getInformation(Information $informationSearched): Information{
			$retVal = new Information("");
			foreach ($this->informations as $information){
				if ($information->getId() == $informationSearched->getId() and $information->getLibelle() == $informationSearched->getLibelle()){
					$retVal = $information;
				}
			}
			return $retVal;
		}

		/**
		 * @param string|null $imageAtelier
		 */
		public function setImageAtelier(?string $imageAtelier): void{
			$this->imageAtelier = $imageAtelier;
		}

		/**
		 * @param string|null $imagePrincipale
		 */
		public function setImagePrincipale(?string $imagePrincipale): void{
			$this->imagePrincipale = $imagePrincipale;
		}

		public function getImagePrincipale(): ?string{
			return $this->imagePrincipale;
		}

		public function getImageAtelier(): ?string{
			return $this->imageAtelier;
		}

		public function setTitreImagePrincipale(?string $titreImagePrincipale): void{
			$this->titreImagePrincipale = $titreImagePrincipale;
		}

		public function getTitreImagePrincipale(): ?string{
			return is_null($this->getImagePrincipale()) ? 'Oeuvre' : $this->titreImagePrincipale;
		}

		public function setTitreImageAtelier(?string $titreImageAtelier): void{
			$this->titreImageAtelier = $titreImageAtelier;
		}

		public function getTitreImageAtelier(): ?string{
			return is_null($this->getImageAtelier()) ? 'Oeuvre' : $this->titreImageAtelier;
		}

		public function setPhoto(string $typeImage, string $photo): void{
			if ($typeImage == 'Atelier'){
				$this->setImageAtelier($photo);
			}else if ($typeImage == 'Principale'){
				$this->setImagePrincipale($photo);
			}else{
				$this->setImageAtelier($this->getImageAtelier() ?? null);
				$this->setImagePrincipale($this->getImagePrincipale() ?? null);
			}
		}

		public function getImagePrincipalePublicFile(): string{
			return str_replace(PHP_PUBLIC_DIR, '', $this->imagePrincipale ?? '');
		}

		public function getImageAtelierPublicFile(): string{
			return str_replace(PHP_PUBLIC_DIR, '', $this->imageAtelier ?? '');
		}

		public function getFormations(): array{
			return $this->formations;
		}

		public function setFormations(array $formations): void{
			$this->formations = $formations;
		}

		public function addFormation(Formation $formation): void{
			$this->formations[] = $formation;
		}

		public function getRecompenses(): array{
			return $this->recompenses;
		}

		public function setRecompenses(array $recompenses): void{
			$this->recompenses = $recompenses;
		}

		public function addRecompense(Recompense $recompense): void{
			$this->recompenses[] = $recompense;
		}

		public function getExpositions(): array{
			return $this->expositions;
		}

		public function setExpositions(array $expositions): void{
			$this->expositions = $expositions;
		}

		public function addExposition(Exposition $exposition): void{
			$this->expositions[] = $exposition;
		}

		public function getCollections(): array{
			return $this->collections;
		}

		public function setCollections(array $collections): void{
			$this->collections = $collections;
		}

		public function addCollections(Collection $collection): void{
			$this->collections[] = $collection;
		}

		public function getEvenements(): array{
			return $this->evenements;
		}

		public function setEvenements(array $evenements): void{
			$this->evenements = $evenements;
		}

		public function addEvenement(Evenement $evenement): void{
			$this->evenements[] = $evenement;
		}

		public function getPresses(): array{
			return $this->presses;
		}

		public function setPresses(array $presses): void{
			$this->presses = $presses;
		}

		public function addPresses(Presse $presse): void{
			$this->presses[] = $presse;
		}

		public function getReperes(): array{
			return $this->reperes;
		}

		public function setReperes(array $reperes): void{
			$this->reperes = $reperes;
		}

		public function addRepere(Repere $repere): void{
			$this->reperes[] = $repere;
		}

		public function getMotsClefs(): array{
			return $this->motsclefs;
		}

		public function setMotsClefs(array $motsclefs): void{
			$this->motsclefs = $motsclefs;
		}

		public function addMotClef(MotClef $motclef): void{
			$this->motsclefs[] = $motclef;
		}

		public function getTauxNegociation(): ?TauxNegociation{
			return $this->tauxNegociation;
		}

		public function setTauxNegociation(?TauxNegociation $tauxNegociation): void{
			$this->tauxNegociation = $tauxNegociation;
		}

		public function accepteNegociation(): bool{
			return $this->accepteNegociation;
		}

		public function setAccepteNegociation(bool $accepteNegociation): void{
			$this->accepteNegociation = $accepteNegociation;
		}

		public function accepteDedicace(): bool{
			return $this->accepteDedicace;
		}

		public function setAccepteDedicace(bool $accepteDedicace): void{
			$this->accepteDedicace = $accepteDedicace;
		}

		public function accepteCommande(): bool{
			return $this->accepteCommande;
		}

		public function setAccepteCommande(bool $accepteCommande): void{
			$this->accepteCommande = $accepteCommande;
		}

		public function afficherPseudo(): bool{
			return $this->afficherPseudo;
		}

		public function setAfficherPseudo(bool $afficherPseudo): void{
			$this->afficherPseudo = $afficherPseudo;
		}

		public function afficherNaissance(): bool{
			return $this->afficherNaissance;
		}

		public function setAfficherNaissance(bool $afficherNaissance): void{
			$this->afficherNaissance = $afficherNaissance;
		}

		public function isArtisteDeConfiance(): bool{
			return $this->artisteDeConfiance;
		}

		public function setArtisteDeConfiance(bool $artisteDeConfiance): void{
			$this->artisteDeConfiance = $artisteDeConfiance;
		}

		public function getPeriodeAbsence(): ?PeriodeAbsence{
			return $this->periodeAbsence;
		}

		public function setPeriodeAbsence(?PeriodeAbsence $periodeAbsence): void{
			$this->periodeAbsence = $periodeAbsence;
		}

		public function getAssujettiTVA(): ?bool{
			return $this->assujettiTVA;
		}

		public function isAssujettiTVA(): ?bool{
			return $this->assujettiTVA;
		}

		public function setAssujettiTVA(?bool $assujettiTVA): void{
			$this->assujettiTVA = $assujettiTVA;
		}

		public function getAttestationTVA(): ?Document{
			return $this->attestationTVA;
		}

		public function setAttestationTVA(?Document $attestationTVA): void{
			$this->attestationTVA = $attestationTVA;
		}

		public function getTauxTVA(): ?TauxTVA{
			return $this->tauxTVA;
		}

		public function setTauxTVA(?TauxTVA $tauxTVA): void{
			$this->tauxTVA = $tauxTVA;
		}

		public function getNbVentes(): int{
			return 0;
		}

		public function getMotsClefsFromOeuvres(): array{
			$lstMotsClef = [];
			$lstIdMotsClefs = [];
			/**
			 * @var Oeuvre $oeuvre
			 */
			foreach ($this->oeuvres as $oeuvre){
				if (count($oeuvre->getMotsclefs())>0){
					foreach ($oeuvre->getMotsclefs() as $motClef){
						if (!in_array($motClef->getId(), $lstIdMotsClefs)){
							$lstMotsClef[] = $motClef;
							$lstIdMotsClefs[] = $motClef->getId();
						}
					}
				}
			}
			return $lstMotsClef;
		}

		public function getOeuvresVendues(): array{
			/**
			 * @var Oeuvre $oeuvre
			 */
			$lesOeuvresVendues = [];
			foreach ($this->oeuvres as $oeuvre){
				if ($oeuvre->getStatutOeuvreShop()->getLibelle() == 'Vendue'){
					$lesOeuvresVendues[] = $oeuvre;
				}
			}
			return $lesOeuvresVendues;
		}

		public function getLastOeuvres(): array{
			$myLimit = intval(DAOParametres::getByLibelle('limit-new-oeuvre-maxi')->getValeur());
			return array_filter($this->oeuvres, function ($element) use ($myLimit){
				return estRecente($element, $myLimit);
			}, ARRAY_FILTER_USE_BOTH);
		}
	}
