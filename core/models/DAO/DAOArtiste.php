<?php

	abstract class DAOArtiste extends BDD{
		protected static function parseRecord(mixed $SQLRow): Artiste{
			$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
			$unArtiste->setId($SQLRow['id_artiste']);
			$unArtiste->setDateAjout(date_create($SQLRow['date_ajout']));
			$unArtiste->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
			$unArtiste->setNbFollowers(DAOArtiste::getNbFollowers($unArtiste->getId()));
			$unArtiste->setNbVues(DAOArtiste::getNbVues($unArtiste->getId()));
			$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
			if (!is_null($SQLRow['id_entreprise'])){
				$unArtiste->setEntreprise(DAOEntreprise::getById($SQLRow['id_entreprise']));
			}
			if (!is_null($SQLRow['id_categorie'])){
				$unArtiste->setCategorieArtiste(DAOCategorieArtiste::getById($SQLRow['id_categorie']));
			}
			$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setInformations(DAOInformation::getByArtisteId($SQLRow['id_artiste']));
			if (!is_null($SQLRow['id_pays_nationalite'])){
				$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
			}
			if (!is_null($SQLRow['id_pays_residence'])){
				$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
			}
			$unArtiste->setMediums(DAOMedium::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setPersonalFolder();
			$unArtiste->setFormations(DAOFormation::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setRecompenses(DAORecompense::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setExpositions(DAOExposition::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setCollections(DAOCollection::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setEvenements(DAOEvenement::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setPresses(DAOPresse::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setReperes(DAORepere::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setImageAtelier(is_null($SQLRow['imageAtelier']) ? null : $SQLRow['imageAtelier']);
			$unArtiste->setImagePrincipale(is_null($SQLRow['imagePrincipale']) ? null : $SQLRow['imagePrincipale']);
			$unArtiste->setAccepteDedicace($SQLRow['accepte_demande_dedicace']);
			$unArtiste->setAccepteNegociation($SQLRow['accepte_negociation']);
			$unArtiste->setTauxNegociation(is_null($SQLRow['id_taux_negociation']) ? null : DAOTauxNegociation::getById($SQLRow['id_taux_negociation']));
			$unArtiste->setAccepteCommande($SQLRow['accepter_commande_sur_mesure']);
			$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
			$unArtiste->setArtisteDeConfiance($SQLRow['artiste_confiance']);
			$unArtiste->setAfficherNaissance($SQLRow['afficher_date_naissance']);
			$unArtiste->setAssujettiTVA($SQLRow['assujetti_tva']);
			$unArtiste->setPeriodeAbsence(intval($SQLRow['absence_active']) == 0 ? null : new PeriodeAbsence(true, date_create($SQLRow['date_debut_absence']), date_create($SQLRow['date_fin_absence'])));
			$unArtiste->setAttestationTVA(DAOArtiste::getLastAttestationTVA($unArtiste->getId()));
			$unArtiste->setTauxTVA(is_null($SQLRow['id_tauxtva']) ? null : DAOTauxTVA::getById($SQLRow['id_tauxtva']));
			return $unArtiste;
		}

		protected static function parseRecordLight(mixed $SQLRow): Artiste{
			$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
			$unArtiste->setId($SQLRow['id_artiste']);
			$unArtiste->setDateAjout(date_create($SQLRow['date_ajout']));
			$unArtiste->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
			$unArtiste->setNbFollowers($SQLRow['nb_followers']);
			$unArtiste->setNbVues($SQLRow['nb_vues']);
			$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
			if (!is_null($SQLRow['id_entreprise'])){
				$unArtiste->setEntreprise(DAOEntreprise::getById($SQLRow['id_entreprise']));
			}
			if (!is_null($SQLRow['id_categorie'])){
				$unArtiste->setCategorieArtiste(DAOCategorieArtiste::getById($SQLRow['id_categorie']));
			}
			$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
			if (!is_null($SQLRow['id_pays_nationalite'])){
				$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
			}
			if (!is_null($SQLRow['id_pays_residence'])){
				$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
			}
			$unArtiste->setPersonalFolder();
			$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
			$unArtiste->setImageAtelier(is_null($SQLRow['imageAtelier']) ? null : $SQLRow['imageAtelier']);
			$unArtiste->setImagePrincipale(is_null($SQLRow['imagePrincipale']) ? null : $SQLRow['imagePrincipale']);
			$unArtiste->setAccepteDedicace($SQLRow['accepte_demande_dedicace']);
			$unArtiste->setAccepteNegociation($SQLRow['accepte_negociation']);
			$unArtiste->setTauxNegociation(is_null($SQLRow['id_taux_negociation']) ? null : DAOTauxNegociation::getById($SQLRow['id_taux_negociation']));
			$unArtiste->setAccepteCommande($SQLRow['accepter_commande_sur_mesure']);
			$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
			$unArtiste->setArtisteDeConfiance($SQLRow['artiste_confiance']);
			$unArtiste->setAfficherNaissance($SQLRow['afficher_date_naissance']);
			$unArtiste->setAssujettiTVA($SQLRow['assujetti_tva']);
			$unArtiste->setPeriodeAbsence(intval($SQLRow['absence_active']) == 0 ? null : new PeriodeAbsence(true, date_create($SQLRow['date_debut_absence']), date_create($SQLRow['date_fin_absence'])));
			$unArtiste->setTauxTVA(is_null($SQLRow['id_tauxtva']) ? null : DAOTauxTVA::getById($SQLRow['id_tauxtva']));
//			$unArtiste->setAttestationTVA(DAOArtiste::getLastAttestationTVA($unArtiste->getId()));
			return $unArtiste;
		}

		public static function getById(int $idArtiste): Artiste|bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_artiste, artiste.pseudo, id_entreprise, artiste.id_user, artiste.id_pays_nationalite, id_pays_residence, artiste.date_ajout, artiste.date_modif, id_categorie, 
				       imageAtelier, imagePrincipale, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, artiste_confiance, afficher_pseudo, afficher_date_naissance,
				       absence_active, date_debut_absence, date_fin_absence, assujetti_tva, id_tauxtva,
				       (SELECT COUNT(id_user) FROM user_artiste_suivi WHERE id_artiste = artiste.id_artiste) as nb_followers,
				       (SELECT COUNT(id) FROM vues_artiste WHERE id_artiste = artiste.id_artiste) as nb_vues
				FROM artiste
				WHERE id_artiste = :id
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			if ($SQLStmt->rowCount() == 0){
				return false;
			}else{
				$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
				$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
				$unArtiste->setId($SQLRow['id_artiste']);
				$unArtiste->setDateAjout(date_create($SQLRow['date_ajout']));
				$unArtiste->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
				$unArtiste->setNbFollowers($SQLRow['nb_followers']);
				$unArtiste->setNbVues($SQLRow['nb_vues']);
				$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
				if (!is_null($SQLRow['id_entreprise'])){
					$unArtiste->setEntreprise(DAOEntreprise::getById($SQLRow['id_entreprise']));
				}
				$unArtiste->setDocuments(DAODocument::getByArtisteId($SQLRow['id_artiste']));
				if (!is_null($SQLRow['id_categorie'])){
					$unArtiste->setCategorieArtiste(DAOCategorieArtiste::getById($SQLRow['id_categorie']));
				}
				$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setInformations(DAOInformation::getByArtisteId($SQLRow['id_artiste']));
				if (!is_null($SQLRow['id_pays_nationalite'])){
					$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
				}
				if (!is_null($SQLRow['id_pays_residence'])){
					$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
				}
				$unArtiste->setMediums(DAOMedium::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setPersonalFolder();
				$unArtiste->setImageAtelier(is_null($SQLRow['imageAtelier']) ? null : $SQLRow['imageAtelier']);
				$unArtiste->setImagePrincipale(is_null($SQLRow['imagePrincipale']) ? null : $SQLRow['imagePrincipale']);
				$unArtiste->setFormations(DAOFormation::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setRecompenses(DAORecompense::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setExpositions(DAOExposition::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setCollections(DAOCollection::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setEvenements(DAOEvenement::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setPresses(DAOPresse::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setReperes(DAORepere::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setAccepteDedicace($SQLRow['accepte_demande_dedicace']);
				$unArtiste->setAccepteNegociation($SQLRow['accepte_negociation']);
				$unArtiste->setTauxNegociation(is_null($SQLRow['id_taux_negociation']) ? null : DAOTauxNegociation::getById($SQLRow['id_taux_negociation']));
				$unArtiste->setAccepteCommande($SQLRow['accepter_commande_sur_mesure']);
				$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
				$unArtiste->setArtisteDeConfiance($SQLRow['artiste_confiance']);
				$unArtiste->setAfficherNaissance($SQLRow['afficher_date_naissance']);
				$unArtiste->setAssujettiTVA($SQLRow['assujetti_tva']);
				$unArtiste->setPeriodeAbsence(intval($SQLRow['absence_active']) == 0 ? null : new PeriodeAbsence(true, date_create($SQLRow['date_debut_absence']), date_create($SQLRow['date_fin_absence'])));
				$unArtiste->setAttestationTVA(DAOArtiste::getLastAttestationTVA($idArtiste));
				$unArtiste->setTauxTVA(is_null($SQLRow['id_tauxtva']) ? null : DAOTauxTVA::getById($SQLRow['id_tauxtva']));
				$SQLStmt->closeCursor();
				return $unArtiste;
			}
		}

		public static function getByIdUser(int $idUser): Artiste|bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_artiste, artiste.pseudo, id_entreprise, artiste.id_user, artiste.id_pays_nationalite, id_pays_residence, artiste.date_ajout, artiste.date_modif, id_categorie,
				       imageAtelier, imagePrincipale, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, artiste_confiance, afficher_pseudo, afficher_date_naissance,
				       absence_active, date_debut_absence, date_fin_absence, assujetti_tva, id_tauxtva,
				       (SELECT COUNT(id_user) FROM user_artiste_suivi WHERE id_artiste = artiste.id_artiste) as nb_followers,
				       (SELECT COUNT(id) FROM vues_artiste WHERE id_artiste = artiste.id_artiste) as nb_vues
				FROM artiste
				WHERE id_user = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $idUser, PDO::PARAM_INT);
			$SQLStmt->execute();
			if ($SQLStmt->rowCount() == 0){
				return false;
			}else{
				$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
				$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
				$unArtiste->setId($SQLRow['id_artiste']);
				$unArtiste->setDateAjout(date_create($SQLRow['date_ajout']));
				$unArtiste->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
				$unArtiste->setNbFollowers($SQLRow['nb_followers']);
				$unArtiste->setNbVues($SQLRow['nb_vues']);
				$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
				if (!is_null($SQLRow['id_entreprise'])){
					$unArtiste->setEntreprise(DAOEntreprise::getById($SQLRow['id_entreprise']));
				}
				if (!is_null($SQLRow['id_categorie'])){
					$unArtiste->setCategorieArtiste(DAOCategorieArtiste::getById($SQLRow['id_categorie']));
				}
				$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setInformations(DAOInformation::getByArtisteId($SQLRow['id_artiste']));
				if (!is_null($SQLRow['id_pays_nationalite'])){
					$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
				}
				if (!is_null($SQLRow['id_pays_residence'])){
					$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
				}
				$unArtiste->setMediums(DAOMedium::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setPersonalFolder();
				$unArtiste->setImageAtelier(is_null($SQLRow['imageAtelier']) ? null : $SQLRow['imageAtelier']);
				$unArtiste->setImagePrincipale(is_null($SQLRow['imagePrincipale']) ? null : $SQLRow['imagePrincipale']);
				$unArtiste->setFormations(DAOFormation::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setRecompenses(DAORecompense::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setExpositions(DAOExposition::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setCollections(DAOCollection::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setEvenements(DAOEvenement::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setPresses(DAOPresse::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setReperes(DAORepere::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setAccepteDedicace($SQLRow['accepte_demande_dedicace']);
				$unArtiste->setAccepteNegociation($SQLRow['accepte_negociation']);
				$unArtiste->setTauxNegociation(is_null($SQLRow['id_taux_negociation']) ? null : DAOTauxNegociation::getById($SQLRow['id_taux_negociation']));
				$unArtiste->setAccepteCommande($SQLRow['accepter_commande_sur_mesure']);
				$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
				$unArtiste->setArtisteDeConfiance($SQLRow['artiste_confiance']);
				$unArtiste->setAfficherNaissance($SQLRow['afficher_date_naissance']);
				$unArtiste->setAssujettiTVA($SQLRow['assujetti_tva']);
				$unArtiste->setPeriodeAbsence(intval($SQLRow['absence_active']) == 0 ? null : new PeriodeAbsence(true, date_create($SQLRow['date_debut_absence']), date_create($SQLRow['date_fin_absence'])));
				$unArtiste->setAttestationTVA(DAOArtiste::getLastAttestationTVA($unArtiste->getId()));
				$unArtiste->setTauxTVA(is_null($SQLRow['id_tauxtva']) ? null : DAOTauxTVA::getById($SQLRow['id_tauxtva']));

				$SQLStmt->closeCursor();
				return $unArtiste;
			}
		}

		public static function getAll(StatutUser $statutUser = null, bool $light = false): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_artiste, artiste.pseudo, id_entreprise, artiste.id_user, artiste.id_pays_nationalite, id_pays_residence, artiste.date_ajout, artiste.date_modif, id_categorie, statut_user.id_statut_user,
				       imageAtelier, imagePrincipale, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, artiste_confiance, afficher_pseudo, afficher_date_naissance,
				       absence_active, date_debut_absence, date_fin_absence, assujetti_tva, id_tauxtva,
				       (SELECT COUNT(id_user) FROM user_artiste_suivi WHERE id_artiste = artiste.id_artiste) as nb_followers,
				       (SELECT COUNT(id) FROM vues_artiste WHERE id_artiste = artiste.id_artiste) as nb_vues
				FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
					INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user 
				WHERE statut_user.id_statut_user = :id_statut_user 
				ORDER BY utilisateur.nom
			";
			if (is_null($statutUser)){
				$statutUser = DAOStatutUser::getById(2); // 2 : Validé
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_statut_user', $statutUser->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesArtistes = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				if (is_null($statutUser) or ($SQLRow['id_statut_user'] == $statutUser->getId())){
					$lesArtistes[] = $light ? self::parseRecordLight($SQLRow) : self::parseRecord($SQLRow);
				}
			}
			$SQLStmt->closeCursor();
			return $lesArtistes;
		}

		public static function getAllByName(StatutUser $statutUser = null, bool $light = false): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
			SELECT id_artiste, artiste.pseudo, id_entreprise, artiste.id_user, artiste.id_pays_nationalite, id_pays_residence, artiste.date_ajout, artiste.date_modif, id_categorie, statut_user.id_statut_user,
				imageAtelier, imagePrincipale, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, artiste_confiance, afficher_pseudo, afficher_date_naissance,
				absence_active, date_debut_absence, date_fin_absence, assujetti_tva, id_tauxtva,
				(SELECT COUNT(id_user) FROM user_artiste_suivi WHERE id_artiste = artiste.id_artiste) as nb_followers,
				(SELECT COUNT(id) FROM vues_artiste WHERE id_artiste = artiste.id_artiste) as nb_vues
			FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
				INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user 
			WHERE statut_user.id_statut_user = :id_statut_user 
				AND COALESCE(artiste.afficher_pseudo, 0) = 0 
			ORDER BY utilisateur.nom
		";
			if (is_null($statutUser)){
				$statutUser = DAOStatutUser::getById(2); // 2 : Validé
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_statut_user', $statutUser->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesArtistes = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				if (is_null($statutUser) or ($SQLRow['id_statut_user'] == $statutUser->getId())){
					$lesArtistes[] = $light ? self::parseRecordLight($SQLRow) : self::parseRecord($SQLRow);
				}
			}
			$SQLStmt->closeCursor();
			return $lesArtistes;
		}

		public static function getAllByPseudo(StatutUser $statutUser = null, bool $light = false): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT id_artiste, artiste.pseudo, id_entreprise, artiste.id_user, artiste.id_pays_nationalite, id_pays_residence, artiste.date_ajout, artiste.date_modif, id_categorie, statut_user.id_statut_user,
				       imageAtelier, imagePrincipale, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, artiste_confiance, afficher_pseudo, afficher_date_naissance,
				       absence_active, date_debut_absence, date_fin_absence, assujetti_tva, id_tauxtva,
				       (SELECT COUNT(id_user) FROM user_artiste_suivi WHERE id_artiste = artiste.id_artiste) as nb_followers,
				       (SELECT COUNT(id) FROM vues_artiste WHERE id_artiste = artiste.id_artiste) as nb_vues
				FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
					INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user 
				WHERE statut_user.id_statut_user = :id_statut_user 
					AND COALESCE(artiste.afficher_pseudo, 0) = 1
				ORDER BY utilisateur.nom
			";
			if (is_null($statutUser)){
				$statutUser = DAOStatutUser::getById(2); // 2 : Validé
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_statut_user', $statutUser->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$lesArtistes = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				if (is_null($statutUser) or ($SQLRow['id_statut_user'] == $statutUser->getId())){
					$lesArtistes[] = $light ? self::parseRecordLight($SQLRow) : self::parseRecord($SQLRow);
				}
			}
			$SQLStmt->closeCursor();
			return $lesArtistes;
		}

		public static function getNbFollowers(int $idArtiste){
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT COUNT(id_user)
				FROM user_artiste_suivi
				WHERE id_artiste = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
			$retVal = $SQLRow[0];
			$SQLStmt->closeCursor();
			return $retVal;
		}

		public static function getNbVues(int $idArtiste){
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT COUNT(id)
				FROM vues_artiste
				WHERE id_artiste = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
			$retVal = $SQLRow[0];
			$SQLStmt->closeCursor();
			return $retVal;
		}

		public static function updateCompteurVues(string $idViewer, int $idArtiste): void{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT COUNT(id)
				FROM vues_artiste
				WHERE id_viewer = :id_viewer
					AND id_artiste = :id_artiste";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_viewer', $idViewer, PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_artiste', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
			$existe = $SQLRow[0];
			$SQLStmt->closeCursor();
			if ($existe == 0){
				$SQLQuery = "INSERT INTO vues_artiste(date_vue, id_artiste, id_viewer) VALUES (CURRENT_DATE, :id_artiste, :id_viewer)";
			}else{
				$SQLQuery = "UPDATE vues_artiste SET date_vue = CURRENT_DATE WHERE id_artiste = :id_artiste AND id_viewer = :id_viewer";
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_viewer', $idViewer, PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_artiste', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
		}

		public static function getCountByInitiale(): array{
			$conn = parent::getConnexion();
			/*$SQLQuery = "
						SELECT LEFT(UPPER(nom), 1) as initiale, COUNT(artiste.id_artiste) as nb
						FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
							INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user
						WHERE statut_user.id_statut_user = 2
						GROUP BY LEFT(UPPER(nom), 1), statut_user.id_statut_user
						ORDER BY initiale
					";*/
			$SQLQuery = "
		SELECT initiale, SUM(nb) as decompte
		FROM 
			(SELECT LEFT(UPPER(nom), 1) as initiale, COUNT(artiste.id_artiste) as nb
			FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
			WHERE utilisateur.id_statut_user = 2
				AND COALESCE(artiste.afficher_pseudo, 0) = 0
			GROUP BY LEFT(UPPER(nom), 1)
		UNION ALL
			SELECT LEFT(UPPER(artiste.pseudo), 1) as initiale, COUNT(artiste.id_artiste) as nb
			FROM artiste INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
			WHERE utilisateur.id_statut_user = 2
				AND COALESCE(artiste.afficher_pseudo, 0) = 1
			GROUP BY LEFT(UPPER(artiste.pseudo), 1)
			ORDER BY initiale) as ssreq
		GROUP BY initiale
		ORDER BY initiale";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$tabToReturn = [];
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$tabToReturn[$SQLRow['initiale']] = $SQLRow['decompte'];
			}
			$SQLStmt->closeCursor();
			return $tabToReturn;
		}

		public static function insert(Artiste $artiste): bool{
			$conn = parent::getConnexion();
			// Je traite d'abord l'artiste en lui même
			$SQLQuery = "INSERT INTO artiste(pseudo, id_entreprise, id_user, id_pays_nationalite, id_pays_residence, date_ajout, date_modif, id_categorie, accepte_demande_dedicace, accepte_negociation, id_taux_negociation, accepter_commande_sur_mesure, afficher_pseudo, afficher_date_naissance, assujetti_tva, id_tauxtva)
			VALUES (:pseudo, :id_entreprise, :id_user, :id_pays_nationalite, :id_pays_residence, CURRENT_DATE, NULL, :id_categorie, :accepte_demande_dedicace, :accepte_negociation, :id_taux_negociation, :accepter_commande_sur_mesure, :afficher_pseudo, :afficher_date_naissance, :assujetti_tva, :id_tauxtva)";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_entreprise', (is_null($artiste->getEntreprise()) ? NULL : $artiste->getEntreprise()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_categorie', (is_null($artiste->getCategorieArtiste()) ? NULL : $artiste->getCategorieArtiste()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_nationalite', (is_null($artiste->getNationalite()) ? NULL : $artiste->getNationalite()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_residence', (is_null($artiste->getResidence()) ? NULL : $artiste->getResidence()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepte_demande_dedicace', $artiste->accepteDedicace(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':accepte_negociation', $artiste->accepteNegociation(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_taux_negociation', (is_null($artiste->getTauxNegociation()) ? NULL : $artiste->getTauxNegociation()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepter_commande_sur_mesure', $artiste->accepteCommande(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':assujetti_tva', $artiste->getAssujettiTVA(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_user', $artiste->getUser()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':pseudo', $artiste->getPseudo(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':afficher_pseudo', $artiste->afficherPseudo(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':afficher_date_naissance', $artiste->afficherNaissance(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_tauxtva', is_null($artiste->getTauxTVA()) ? null : $artiste->getTauxTVA()->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				// Puis les adresses
				// Ensuite les abonnements
				// Puis les informations
				// Puis les documents
				// Puis les oeuvres
				// Puis les mediums
				$artiste->setId($conn->lastInsertId());
				/**
				 * @var Adresse $adresse
				 */
				foreach ($artiste->getAdresses() as $adresse){
					$SQLQuery = "INSERT INTO artiste_adresse(id_adresse, id_artiste, valide)
								VALUES (:idadresse, :idartiste, :valide)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idadresse', $adresse->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':idartiste', $artiste->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
					$SQLStmt->execute();
				}
				/**
				 * @var Information $information
				 */
				foreach ($artiste->getInformations() as $information){
					$SQLQuery = "INSERT INTO artiste_information(id_information, id_artiste, contenu, valide)
								VALUES (:idinformation, :idartiste, :contenu, :valide)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idinformation', $information->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':idartiste', $artiste->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':contenu', $information->getValeur(), PDO::PARAM_STR);
					$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
					$SQLStmt->execute();
				}
				/**
				 * @var Document $document
				 */
				foreach ($artiste->getDocuments() as $document){
					DAODocument::insert($document);
					$SQLQuery = "INSERT INTO artiste_document(id_document, id_artiste, date_import)
								VALUES (:id_document, :id_artiste, :date_import)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':id_document', $document->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':date_import', date('Y-m-d'), PDO::PARAM_STR);
					$SQLStmt->execute();
				}
				/**
				 * @var Oeuvre $oeuvre
				 */
				foreach ($artiste->getOeuvres() as $oeuvre){
					$oeuvre->setIdArtiste($artiste->getId());
					DAOOeuvre::insert($oeuvre);
				}
				/**
				 * @var Medium $medium
				 */
				foreach ($artiste->getMediums() as $medium){
					$SQLQuery = "INSERT INTO artiste_medium(id_artiste, id_medium)
								VALUES (:idartiste, :idmedium)";
					$SQLStmt = $conn->prepare($SQLQuery);
					$SQLStmt->bindValue(':idmedium', $medium->getId(), PDO::PARAM_INT);
					$SQLStmt->bindValue(':idartiste', $artiste->getId(), PDO::PARAM_INT);
					$SQLStmt->execute();
				}
				return true;
			}
		}

		public static function update(Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "UPDATE artiste 
						SET pseudo = :pseudo,
							id_entreprise=:id_entreprise, 
							id_user=:id_user, 
							id_pays_nationalite=:id_pays_nationalite, 
							id_pays_residence=:id_pays_residence, 
							id_qualification=:id_qualification, 
							popularite=:popularite,
							id_categorie=:id_categorie,						    
						    date_modif = CURRENT_DATE,
						    accepte_demande_dedicace = :accepte_demande_dedicace, 
						    accepte_negociation = :accepte_negociation, 
						    id_taux_negociation = :id_taux_negociation,
							accepter_commande_sur_mesure = :accepter_commande_sur_mesure,
							afficher_pseudo = :afficher_pseudo,
							afficher_date_naissance = :afficher_date_naissance,
							assujetti_tva = :assujetti_tva,
							id_tauxtva = :id_tauxtva
						WHERE id_artiste = :id";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':pseudo', $artiste->getPseudo(), PDO::PARAM_STR);
			$SQLStmt->bindValue(':id_entreprise', is_null($artiste->getEntreprise()) ? $artiste->getEntreprise() : $artiste->getEntreprise()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_user', $artiste->getUser()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_nationalite', (is_null($artiste->getNationalite()) ? NULL : $artiste->getNationalite()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_pays_residence', (is_null($artiste->getResidence()) ? NULL : $artiste->getResidence()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_qualification', null, PDO::PARAM_INT);
			$SQLStmt->bindValue(':popularite', $artiste->getPopularite(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_categorie', $artiste->getCategorieArtiste()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepte_demande_dedicace', $artiste->accepteDedicace(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':accepte_negociation', $artiste->accepteNegociation(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_taux_negociation', (is_null($artiste->getTauxNegociation()) ? NULL : $artiste->getTauxNegociation()->getId()), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepter_commande_sur_mesure', $artiste->accepteCommande(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':afficher_pseudo', $artiste->afficherPseudo(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':afficher_date_naissance', $artiste->afficherNaissance(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':assujetti_tva', $artiste->getAssujettiTVA(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_tauxtva', is_null($artiste->getTauxTVA()) ? null : $artiste->getTauxTVA()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id', $artiste->getId(), PDO::PARAM_INT);
			if (!$SQLStmt->execute()){
				return false;
			}else{
				return true;
			}
		}

		public static function updateInformation(Artiste $artiste, Information $information, string $texteInfo): bool{
			$conn = parent::getConnexion();
			if ($artiste->hasInformation($information)){
				$SQLQuery = "
					UPDATE artiste_information 
					SET contenu = :contenu 
					WHERE id_artiste = :id_artiste 
						AND id_information = :id_information
				";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':id_information', $information->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':contenu', $texteInfo);
			}else{
				$SQLQuery = "
						INSERT INTO artiste_information(id_information, id_artiste, contenu, valide) 
						VALUES (:id_information, :id_artiste, :contenu, :valide)
					";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':id_information', $information->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':contenu', $texteInfo);
				$SQLStmt->bindValue(':valide', true, PDO::PARAM_BOOL);
			}
			$retVal = $SQLStmt->execute();
			return $retVal;
		}

		public static function updateImage(string $image, Artiste $artiste): bool{
			$conn = parent::getConnexion();
			if ($image == 'Principale'){
				$SQLQuery = "
						UPDATE artiste
						SET imagePrincipale = :image
						WHERE id_artiste = :id_artiste 
				";
			}else{
				$SQLQuery = "
						UPDATE artiste
						SET imageAtelier = :image
						WHERE id_artiste = :id_artiste 
				";
			}
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			if ($image == 'Principale'){
				$SQLStmt->bindValue(':image', $artiste->getImagePrincipale(), PDO::PARAM_STR);
			}else{
				$SQLStmt->bindValue(':image', $artiste->getImageAtelier(), PDO::PARAM_STR);
			}
			return $SQLStmt->execute();
		}

		public static function deleteFormations(Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_formation
					WHERE id_artiste = :id_artiste 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addFormation(Artiste $artiste, mixed $formation): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_formation(periode, organisme, libelle, lieu, num_ordre, id_artiste)
				VALUES (:periode, :organisme, :libelle, :lieu, :num_ordre, :id_artiste)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $formation->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($formation->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':organisme', htmlspecialchars($formation->organisme), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($formation->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($formation->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteRecompenses(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_recompense
					WHERE id_artiste = :id_artiste 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addRecompense(Artiste $artiste, mixed $recompense): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_recompense(periode, organisme, libelle, lieu, num_ordre, id_artiste)
				VALUES (:periode, :organisme, :libelle, :lieu, :num_ordre, :id_artiste)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $recompense->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($recompense->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':organisme', htmlspecialchars($recompense->organisme), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($recompense->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($recompense->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteExposIndividuelles(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_exposition
					WHERE id_artiste = :id_artiste
					AND id_type_exposition = (SELECT id_type_exposition FROM type_exposition WHERE libelle = 'Individuelles')
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addExpoIndividuelle(Artiste $artiste, mixed $exposition): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_exposition(periode, libelle, lieu, num_ordre, id_artiste, id_type_exposition)
				VALUES (:periode, :libelle, :lieu, :num_ordre, :id_artiste, :id_type_exposition)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_exposition', DAOTypeExposition::getByLibelle('Individuelles')->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $exposition->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($exposition->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($exposition->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($exposition->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteExposCollectives(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_exposition
					WHERE id_artiste = :id_artiste
						AND id_type_exposition = (SELECT id_type_exposition FROM type_exposition WHERE libelle = 'Collectives')
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addExpoCollective(Artiste $artiste, mixed $exposition): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_exposition(periode, libelle, lieu, num_ordre, id_artiste, id_type_exposition)
				VALUES (:periode, :libelle, :lieu, :num_ordre, :id_artiste, :id_type_exposition)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_exposition', DAOTypeExposition::getByLibelle('Collectives')->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $exposition->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($exposition->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($exposition->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($exposition->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteExposPermanentes(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_exposition
					WHERE id_artiste = :id_artiste
						AND id_type_exposition = (SELECT id_type_exposition FROM type_exposition WHERE libelle = 'Permanentes')
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addExpoPermanentes(Artiste $artiste, mixed $exposition): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_exposition(periode, libelle, lieu, num_ordre, id_artiste, id_type_exposition)
				VALUES (:periode, :libelle, :lieu, :num_ordre, :id_artiste, :id_type_exposition)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_exposition', DAOTypeExposition::getByLibelle('Permanentes')->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $exposition->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($exposition->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($exposition->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($exposition->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteCollections(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_collection
					WHERE id_artiste = :id_artiste
					
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addCollections(Artiste $artiste, mixed $collections): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_collection(periode, nombre_oeuvre , organisme , ville, pays, num_ordre, id_artiste)
				VALUES (:periode, :nboeuvre , :organisme, :ville, :pays, :num_ordre, :id_artiste)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $collections->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($collections->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':organisme', htmlspecialchars($collections->organisme), PDO::PARAM_STR);
			$SQLStmt->bindValue(':ville', htmlspecialchars($collections->ville), PDO::PARAM_STR);
			$SQLStmt->bindValue(':pays', htmlspecialchars($collections->pays), PDO::PARAM_STR);
			$SQLStmt->bindValue(':nboeuvre', htmlspecialchars($collections->nboeuvre), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteFoires(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_evenement
					WHERE id_artiste = :id_artiste 
						AND id_type_evenement = (SELECT id_type_evenement FROM type_evenement WHERE libelle = 'Foires')
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addFoire(Artiste $artiste, mixed $foires): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_evenement(periode, libelle, lieu, num_ordre, id_artiste, id_type_evenement)
				VALUES (:periode, :libelle, :lieu, :num_ordre, :id_artiste, :id_type_evenement)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_evenement', DAOTypeEvenement::getByLibelle('Foires')->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $foires->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($foires->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($foires->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($foires->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deleteSalons(bool|Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_evenement
					WHERE id_artiste = :id_artiste 
						AND id_type_evenement = (SELECT id_type_evenement FROM type_evenement WHERE libelle = 'Salons')
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addSalon(Artiste $artiste, mixed $foires): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_evenement(periode, libelle, lieu, num_ordre, id_artiste, id_type_evenement)
				VALUES (:periode, :libelle, :lieu, :num_ordre, :id_artiste, :id_type_evenement)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_type_evenement', DAOTypeEvenement::getByLibelle('Salons')->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $foires->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($foires->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':libelle', htmlspecialchars($foires->libelle), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lieu', htmlspecialchars($foires->lieu), PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function deletePresses(Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
					DELETE FROM artiste_presse
					WHERE id_artiste = :id_artiste 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function addPresse(Artiste $artiste, mixed $presse): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_presse(periode, magazine, titre_article, lien_article,chemin_photo, chemin_photo_miniature,  num_ordre, id_artiste)
				VALUES (:periode, :magazine, :titre_article, :lien_article, :chemin_photo, :chemin_photo_miniature,  :num_ordre, :id_artiste)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':num_ordre', $presse->numOrdre, PDO::PARAM_INT);
			$SQLStmt->bindValue(':periode', htmlspecialchars($presse->periode), PDO::PARAM_STR);
			$SQLStmt->bindValue(':magazine', htmlspecialchars($presse->magazine), PDO::PARAM_STR);
			$SQLStmt->bindValue(':titre_article', htmlspecialchars($presse->titre), PDO::PARAM_STR);
			$SQLStmt->bindValue(':lien_article', $presse->lien, PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin_photo', $presse->cheminphoto ?? null, PDO::PARAM_STR);
			$SQLStmt->bindValue(':chemin_photo_miniature', $presse->cheminminiature ?? null, PDO::PARAM_STR);
			return $SQLStmt->execute();
		}

		public static function updateUserOptions(Artiste $artiste): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE artiste 
				SET accepte_negociation = :accepte_negociation,
				    accepte_demande_dedicace = :accepte_demande_dedicace,
				    id_taux_negociation = :id_taux_negociation,
					accepter_commande_sur_mesure = :accepter_commande_sur_mesure
					
				WHERE id_artiste = :id_artiste
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepte_negociation', $artiste->accepteNegociation(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':accepte_demande_dedicace', $artiste->accepteDedicace(), PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':id_taux_negociation', is_null($artiste->getTauxNegociation()) ? $artiste->getTauxNegociation() : $artiste->getTauxNegociation()->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':accepter_commande_sur_mesure', $artiste->accepteCommande(), PDO::PARAM_BOOL);
			return $SQLStmt->execute();
		}

		public static function updateAbsence(Artiste $artiste, PeriodeAbsence $periodeAbsence): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE artiste 
				SET absence_active = :absence_active,
				    date_debut_absence = :date_debut_absence,
				    date_fin_absence = :date_fin_absence
				WHERE id_artiste = :id_artiste
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':absence_active', $periodeAbsence->isActive() ? 1 : 0, PDO::PARAM_BOOL);
			$SQLStmt->bindValue(':date_debut_absence', is_null($periodeAbsence->getDateDebut()) ? null : $periodeAbsence->getDateDebut()->format('Y-m-d'));
			$SQLStmt->bindValue(':date_fin_absence', is_null($periodeAbsence->getDateFin()) ? null : $periodeAbsence->getDateFin()->format('Y-m-d'));
			return $SQLStmt->execute();
		}

		public static function addArtistKeyword(Artiste $artiste, int $keywordId): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				INSERT INTO artiste_motsclefs(id_mot_clef, id_artiste) VALUES (:id_mot_clef, :id_artiste)
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_mot_clef', $keywordId, PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function delArtistKeyword(Artiste $artiste, int $keywordId): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				DELETE FROM artiste_motsclefs WHERE id_mot_clef = :id_mot_clef AND id_artiste = :id_artiste
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_mot_clef', $keywordId, PDO::PARAM_INT);
			return $SQLStmt->execute();
		}

		public static function getSeuilVues(int $quartile = 4): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT MIN(nombre_de_vues)
				FROM (
					SELECT COUNT(id_artiste) AS nombre_de_vues, id_artiste,
						   NTILE(4) OVER (ORDER BY COUNT(id_artiste) DESC) AS quartile
					FROM vues_artiste
					GROUP BY id_artiste
				) AS quartiles
				WHERE quartile <= :quartile
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':quartile', $quartile, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
			$retVal = $SQLRow[0];
			$SQLStmt->closeCursor();
			return $retVal;
		}

		public static function checkUserArtist(int $idUser): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(id_artiste)
				FROM artiste
				WHERE id_user = :id_user
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $idUser, PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_NUM);
			$retVal = ($SQLRow[0]>0);
			$SQLStmt->closeCursor();
			return $retVal;
		}

		public static function searchArtistByName(string $motSearch): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT DISTINCT artiste.id_artiste, artiste.pseudo,utilisateur.photo, artiste.id_user,utilisateur.nom,utilisateur.prenom,statut_user.id_statut_user ,
				artiste.id_pays_nationalite, id_pays_residence, artiste.imagePrincipale, artiste.imageAtelier, artiste.afficher_pseudo
				FROM artiste 
				INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
				INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user 
				LEFT JOIN artiste_motsclefs ON artiste.id_artiste = artiste_motsclefs.id_artiste LEFT JOIN mots_clefs ON mots_clefs.id_mot_clef = artiste_motsclefs.id_mot_clef
				WHERE statut_user.id_statut_user = 2 AND (utilisateur.nom LIKE :motsearch OR utilisateur.prenom LIKE :motsearch OR mots_clefs.libelle LIKE :motsearch) 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':motsearch', $motSearch . '%', PDO::PARAM_STR);
			$SQLStmt->execute();
			$arraysearch = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
				$unArtiste->setId($SQLRow['id_artiste']);
				$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
				$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
				if (!is_null($SQLRow['id_pays_nationalite'])){
					$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
				}
				if (!is_null($SQLRow['id_pays_residence'])){
					$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
				}
				$unArtiste->setMediums(DAOMedium::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setImageAtelier(is_null($SQLRow['imageAtelier']) ? null : $SQLRow['imageAtelier']);
				$unArtiste->setImagePrincipale(is_null($SQLRow['imagePrincipale']) ? null : $SQLRow['imagePrincipale']);
				$unArtiste->setReperes(DAORepere::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
				$arraysearch[] = $unArtiste;
			}
			$SQLStmt->closeCursor();
			return $arraysearch;
		}

		public static function searByMotClef(string $motsearch): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT artiste.id_artiste, artiste.pseudo,utilisateur.photo, artiste.id_user,utilisateur.nom,utilisateur.prenom,statut_user.id_statut_user ,
				artiste.id_pays_nationalite, id_pays_residence, mots_clefs.libelle,artiste.afficher_pseudo
				FROM artiste 
				INNER JOIN utilisateur ON artiste.id_user = utilisateur.id_user
				INNER JOIN statut_user ON utilisateur.id_statut_user = statut_user.id_statut_user 
				INNER JOIN artiste_motsclefs ON artiste.id_artiste = artiste_motsclefs.id_artiste INNER JOIN mots_clefs ON mots_clefs.id_mot_clef = artiste_motsclefs.id_mot_clef
				WHERE mots_clefs.libelle LIKE :motsearch AND statut_user.id_statut_user = 2
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':motsearch', '%' . $motsearch . '%', PDO::PARAM_STR);
			$SQLStmt->execute();
			$arraysearch = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unArtiste = new Artiste(html_entity_decode($SQLRow['pseudo'] ?? ''));
				$unArtiste->setId($SQLRow['id_artiste']);
				$unArtiste->setUser(DAOUser::getById($SQLRow['id_user']));
				$unArtiste->setAdresses(DAOAdresse::getByArtisteId($SQLRow['id_artiste']));
				if (!is_null($SQLRow['id_pays_nationalite'])){
					$unArtiste->setNationalite(DAOPays::getById($SQLRow['id_pays_nationalite']));
				}
				if (!is_null($SQLRow['id_pays_residence'])){
					$unArtiste->setResidence(DAOPays::getById($SQLRow['id_pays_residence']));
				}
				$unArtiste->setMediums(DAOMedium::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setReperes(DAORepere::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setMotsClefs(DAOMotsClefs::getByArtisteId($SQLRow['id_artiste']));
				$unArtiste->setAfficherPseudo($SQLRow['afficher_pseudo']);
				$arraysearch[] = $unArtiste;
			}
			$SQLStmt->closeCursor();
			return $arraysearch;
		}

		public static function getPaysResidences(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT DISTINCT pays.id_pays, pays.nom_fr
				FROM artiste INNER JOIN pays ON artiste.id_pays_residence = pays.id_pays
				ORDER BY pays.nom_fr
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->execute();
			$lesPays = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unPays = DAOPays::getById($SQLRow['id_pays']);
				$lesPays[] = $unPays;
			}
			$SQLStmt->closeCursor();
			return $lesPays;
		}

		public static function getLastAttestationTVA(int $idArtiste): ?Document{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT document.id_document, nom, chemin, id_statut_doc, id_type_document, date_import
				FROM artiste INNER JOIN artiste_document ON artiste.id_artiste = artiste_document.id_artiste 
					INNER JOIN document ON artiste_document.id_document = document.id_document 
				WHERE artiste.id_artiste = :id_artiste
					AND document.id_type_document = 12
				ORDER BY artiste_document.date_import DESC, document.id_document DESC;
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_artiste', $idArtiste, PDO::PARAM_INT);
			$SQLStmt->execute();
			$attestationTVA = null;
			if ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$attestationTVA = new Document($SQLRow['nom'], $SQLRow['chemin'], DAOStatutDocument::getById($SQLRow['id_statut_doc']), DAOTypeDocument::getById($SQLRow['id_type_document']), date_create($SQLRow['date_import']));
				$attestationTVA->setId(intval($SQLRow['id_document']));
			}
			$SQLStmt->closeCursor();
			return $attestationTVA;
		}

		public static function addAttestationTVA(Artiste $artiste, Document $attestation): bool{
			$conn = parent::getConnexion();
			if (DAODocument::insert($attestation)){
				$SQLQuery = "INSERT INTO artiste_document (id_artiste, id_document, date_import) VALUES (:id_artiste, :id_document, CURRENT_DATE)";
				$SQLStmt = $conn->prepare($SQLQuery);
				$SQLStmt->bindValue(':id_artiste', $artiste->getId(), PDO::PARAM_INT);
				$SQLStmt->bindValue(':id_document', $attestation->getId(), PDO::PARAM_INT);
				if (!$SQLStmt->execute()){
					return false;
				}
			}else{
				return false;
			}
			return true;
		}
	}
