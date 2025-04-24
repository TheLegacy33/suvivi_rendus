<?php


	abstract class DAOUserAdmin extends BDD{
		public static function getAllAcheteurs(): array{
			$conn = parent::getConnexion();
			$SQLQuery = "SELECT utilisateur.id_user, utilisateur.pseudo, email, mot_de_passe, nom, prenom, date_naissance, tel_portable, tel_fixe, photo, is_admin, id_statut_user, id_type_acheteur, 
       			id_civilite, utilisateur.date_ajout, utilisateur.date_modif, stripe_customer_id, id_devise_preferee, id_default_locale, id_default_unit, utilisateur.id_pays_nationalite,
       			has_problem
				FROM utilisateur
				LEFT JOIN artiste ON utilisateur.id_user = artiste.id_user 
				WHERE artiste.id_user IS NULL 
				  AND utilisateur.is_admin = :isadmin ";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':isadmin', 0, PDO::PARAM_INT);
			$SQLStmt->execute();
			$listeUsers = array();
			while ($SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC)){
				$unUser = new User(html_entity_decode($SQLRow['pseudo']), html_entity_decode($SQLRow['email']), $SQLRow['mot_de_passe'], html_entity_decode($SQLRow['nom']), html_entity_decode($SQLRow['prenom']), date_create($SQLRow['date_naissance']), $SQLRow['tel_portable'], $SQLRow['tel_fixe'], $SQLRow['photo'], $SQLRow['is_admin']);
				$unUser->setId($SQLRow['id_user']);
				// Personnal folder
				$unUser->setPersonalFolder();
				//Civilite
				$unUser->setCivilite(DAOCivilite::getById($SQLRow['id_civilite']));
				// adresses
				$unUser->setAdresses(DAOAdresse::getByUserId($SQLRow['id_user']));
				// informations
				$unUser->setInformations(DAOInformation::getByUserId($SQLRow['id_user']));
				// abonnements
				$unUser->setAbonnements(DAOAbonnement::getByUserId($SQLRow['id_user']));
				// documents
				$unUser->setDocuments(DAODocument::getByUserId($SQLRow['id_user']));
				// Statut
				$unUser->setStatut(DAOStatutUser::getById($SQLRow['id_statut_user']));
				// Type acheteur
				$unUser->setTypesAcheteur(is_null($SQLRow['id_type_acheteur']) ? null : DAOTypeAcheteur::getById($SQLRow['id_type_acheteur']));
				// Roles
				$unUser->setRoles(DAORoleUser::getByUserId($SQLRow['id_user']));
				// Carte Paiement
				$unUser->setCartePaiement(DAOUser::getInfosCartePaiement($unUser, DAOMoyenPaiement::getByLibelle('Carte Bancaire')));
				$unUser->setDateAjout(date_create($SQLRow['date_ajout']));
				$unUser->setDateModif(is_null($SQLRow['date_modif']) ? null : date_create($SQLRow['date_modif']));
				$unUser->setStripeCustomerId($SQLRow['stripe_customer_id']);
				$unUser->setInfosBanque(DAOInfosBanque::getByIdUser($SQLRow['id_user']));
				$unUser->setTypesArtsScandale(DAOTypeArtScandale::getAllByIdUser($SQLRow['id_user']));
				$unUser->setDevisePreferee(is_null($SQLRow['id_devise_preferee']) ? null : DAODevises::getById($SQLRow['id_devise_preferee']));
				$unUser->setLocaleTrad(is_null($SQLRow['id_default_locale']) ? null : DAOLocale::getById($SQLRow['id_default_locale']));
				$unUser->setPreferedUnit(is_null($SQLRow['id_default_unit']) ? null : DAOUnite::getById($SQLRow['id_default_unit']));
				$unUser->setNationalite(is_null($SQLRow['id_pays_nationalite']) ? null : DAOPays::getById($SQLRow['id_pays_nationalite']));
				$unUser->setHasProblem(intval($SQLRow['has_problem'])>0);
				$listeUsers[] = $unUser;
			}
			$SQLStmt->closeCursor();
			return $listeUsers;
		}

		public static function getNbCommandesEnCours(User $acheteur): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(*) as nbEnCours 
				FROM commande 
				WHERE id_user = :id_user 
				  AND id_statut_commande IN (1, 2, 3) 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $acheteur->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$nb = intval($SQLRow['nbEnCours']);
			$SQLStmt->closeCursor();
			return $nb;
		}

		public static function getNbCommandesAnnulees(User $acheteur): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(*) as nbEnCours 
				FROM commande 
				WHERE id_user = :id_user 
				  AND id_statut_commande = 4 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $acheteur->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$nb = intval($SQLRow['nbEnCours']);
			$SQLStmt->closeCursor();
			return $nb;
		}

		public static function getNbCommandesCloturees(User $acheteur): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(*) as nbEnCours 
				FROM commande 
				WHERE id_user = :id_user 
				  AND id_statut_commande = 5
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $acheteur->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$nb = intval($SQLRow['nbEnCours']);
			$SQLStmt->closeCursor();
			return $nb;
		}

		public static function getNbCommandesEnAttente(User $acheteur): int{
			$conn = parent::getConnexion();
			$SQLQuery = "
				SELECT COUNT(*) as nbEnCours 
				FROM commande 
				WHERE id_user = :id_user 
				  AND id_statut_commande = 1
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $acheteur->getId(), PDO::PARAM_INT);
			$SQLStmt->execute();
			$SQLRow = $SQLStmt->fetch(PDO::FETCH_ASSOC);
			$nb = intval($SQLRow['nbEnCours']);
			$SQLStmt->closeCursor();
			return $nb;
		}

		public static function updateStatutProbleme(User $acheteur): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
				UPDATE utilisateur
				SET has_problem = :has_problem
				WHERE id_user = :id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $acheteur->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':has_problem', $acheteur->hasProblem(), PDO::PARAM_BOOL);
			return $SQLStmt->execute();
		}

		public static function delete(User $user): bool{
			$conn = parent::getConnexion();
			$SQLQuery = "
						UPDATE utilisateur
						SET id_statut_user = :id_statut_user
						WHERE id_user=:id_user 
			";
			$SQLStmt = $conn->prepare($SQLQuery);
			$SQLStmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
			$SQLStmt->bindValue(':id_statut_user', $user->getStatut()->getId(), PDO::PARAM_INT);
			return $SQLStmt->execute();
		}
	}